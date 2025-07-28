<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Appointment.php';

class CompanyController {
    private $companyModel;
    private $serviceModel;
    private $appointmentModel;
    
    public function __construct() {
        $this->companyModel = new Company();
        $this->serviceModel = new Service();
        $this->appointmentModel = new Appointment();
        $this->checkAuth();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'company') {
            header('Location: /login');
            exit;
        }
        
        if (!checkSessionTimeout()) {
            header('Location: /login');
            exit;
        }
    }
    
    public function dashboard() {
        $company_id = $_SESSION['user_id'];
        $stats = $this->companyModel->getDashboardStats($company_id);
        $recent_appointments = $this->companyModel->getAppointments($company_id, 10);
        
        include 'views/company/dashboard.php';
    }
    
    public function services() {
        $company_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido';
                header('Location: /company/services');
                exit;
            }
            
            $action = $_POST['action'];
            
            if ($action === 'create') {
                $data = [
                    'company_id' => $company_id,
                    'nome' => sanitize($_POST['nome']),
                    'descricao' => sanitize($_POST['descricao']),
                    'duracao_minutos' => (int)$_POST['duracao_minutos'],
                    'preco' => (float)$_POST['preco']
                ];
                
                if ($this->serviceModel->create($data)) {
                    $_SESSION['success'] = 'Serviço criado com sucesso!';
                    auditLog('service_created', "Serviço criado: {$data['nome']}", $company_id, $_SESSION['user_id']);
                } else {
                    $_SESSION['error'] = 'Erro ao criar serviço';
                }
            } elseif ($action === 'update') {
                $id = (int)$_POST['id'];
                $data = [
                    'company_id' => $company_id,
                    'nome' => sanitize($_POST['nome']),
                    'descricao' => sanitize($_POST['descricao']),
                    'duracao_minutos' => (int)$_POST['duracao_minutos'],
                    'preco' => (float)$_POST['preco']
                ];
                
                if ($this->serviceModel->update($id, $data)) {
                    $_SESSION['success'] = 'Serviço atualizado com sucesso!';
                    auditLog('service_updated', "Serviço atualizado: {$data['nome']}", $company_id, $_SESSION['user_id']);
                } else {
                    $_SESSION['error'] = 'Erro ao atualizar serviço';
                }
            } elseif ($action === 'toggle') {
                $id = (int)$_POST['id'];
                
                if ($this->serviceModel->toggleStatus($id, $company_id)) {
                    $_SESSION['success'] = 'Status do serviço alterado com sucesso!';
                    auditLog('service_toggle', "Status do serviço ID $id alterado", $company_id, $_SESSION['user_id']);
                } else {
                    $_SESSION['error'] = 'Erro ao alterar status do serviço';
                }
            }
            
            header('Location: /company/services');
            exit;
        }
        
        $services = $this->serviceModel->getByCompany($company_id, false);
        include 'views/company/services.php';
    }
    
    public function appointments() {
        $company_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'];
            
            if ($action === 'update_status') {
                $id = (int)$_POST['id'];
                $status = sanitize($_POST['status']);
                
                if ($this->appointmentModel->updateStatus($id, $status, $company_id)) {
                    $_SESSION['success'] = 'Status do agendamento atualizado com sucesso!';
                    auditLog('appointment_status_updated', "Status do agendamento ID $id alterado para $status", $company_id, $_SESSION['user_id']);
                } else {
                    $_SESSION['error'] = 'Erro ao atualizar status do agendamento';
                }
            }
            
            header('Location: /company/appointments');
            exit;
        }
        
        $appointments = $this->companyModel->getAppointments($company_id);
        include 'views/company/appointments.php';
    }
    
    public function calendar() {
        $company_id = $_SESSION['user_id'];
        
        // API endpoint para eventos do calendário
        if (isset($_GET['api']) && $_GET['api'] === 'events') {
            header('Content-Type: application/json');
            
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            
            try {
                $db = new Database();
                $pdo = $db->getConnection();
                
                $stmt = $pdo->prepare("
                    SELECT a.*, s.nome as servico_nome, s.preco
                    FROM appointments a
                    JOIN services s ON a.service_id = s.id
                    WHERE a.company_id = ?
                    AND a.data_agendamento BETWEEN ? AND ?
                    ORDER BY a.data_agendamento, a.hora_inicio
                ");
                $stmt->execute([$company_id, $start, $end]);
                $appointments = $stmt->fetchAll();
                
                $events = [];
                foreach ($appointments as $appointment) {
                    $color = [
                        'agendado' => '#007bff',
                        'confirmado' => '#28a745',
                        'cancelado' => '#dc3545',
                        'concluido' => '#6c757d'
                    ][$appointment['status']] ?? '#007bff';
                    
                    $events[] = [
                        'id' => $appointment['id'],
                        'title' => $appointment['cliente_nome'] . ' - ' . $appointment['servico_nome'],
                        'start' => $appointment['data_agendamento'] . 'T' . $appointment['hora_inicio'],
                        'end' => $appointment['data_agendamento'] . 'T' . $appointment['hora_fim'],
                        'backgroundColor' => $color,
                        'borderColor' => $color,
                        'extendedProps' => [
                            'cliente' => $appointment['cliente_nome'],
                            'telefone' => $appointment['telefone'],
                            'servico' => $appointment['servico_nome'],
                            'status' => $appointment['status'],
                            'preco' => $appointment['preco']
                        ]
                    ];
                }
                
                echo json_encode($events);
                exit;
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao carregar eventos']);
                exit;
            }
        }
        
        include 'views/company/calendar.php';
    }
    
    public function conversations() {
        $company_id = $_SESSION['user_id'];
        
        // API para mensagens de uma conversa
        if (isset($_GET['api']) && $_GET['api'] === 'messages') {
            header('Content-Type: application/json');
            $conversation_id = (int)$_GET['conversation_id'];
            $messages = $this->companyModel->getMessages($conversation_id);
            echo json_encode($messages);
            exit;
        }
        
        $conversations = $this->companyModel->getConversations($company_id);
        include 'views/company/conversations.php';
    }
    
    public function whatsapp() {
        $company_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'];
            
            if ($action === 'connect') {
                // Conectar instância do WhatsApp
                // Aqui seria implementada a conexão com a API Evolution
                $_SESSION['success'] = 'Conectando ao WhatsApp...';
                auditLog('whatsapp_connect_attempt', 'Tentativa de conexão ao WhatsApp', $company_id, $_SESSION['user_id']);
            }
            
            header('Location: /company/whatsapp');
            exit;
        }
        
        include 'views/company/whatsapp.php';
    }
}
?>