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
        
        include BASE_PATH . '/views/company/dashboard.php';
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
        include BASE_PATH . '/views/company/services.php';
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
        include BASE_PATH . '/views/company/appointments.php';
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
        
        include BASE_PATH . '/views/company/calendar.php';
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
        include BASE_PATH . '/views/company/conversations.php';
    }
    
    public function whatsapp() {
        $company_id = $_SESSION['user_id'];
        
        // Handle AJAX status check
        if (isset($_GET['action']) && $_GET['action'] === 'check_status') {
            header('Content-Type: application/json');
            
            try {
                $db = new Database();
                $pdo = $db->getConnection();
                
                // Get company data
                $stmt = $pdo->prepare("SELECT whatsapp_connected, whatsapp_instance FROM companies WHERE id = ?");
                $stmt->execute([$company_id]);
                $company = $stmt->fetch();
                
                if (!$company) {
                    echo json_encode(['connected' => false, 'error' => 'Company not found']);
                    exit;
                }
                
                // Check real connection status with Evolution API
                $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
                $config = $stmt->fetch();
                
                if ($config && $company['whatsapp_instance']) {
                    $realStatus = $this->checkWhatsAppConnectionState($config, $company['whatsapp_instance']);
                    
                    // Update database if status changed
                    if ($realStatus !== (bool)$company['whatsapp_connected']) {
                        $stmt = $pdo->prepare("UPDATE companies SET whatsapp_connected = ? WHERE id = ?");
                        $stmt->execute([$realStatus ? 1 : 0, $company_id]);
                        
                        auditLog('whatsapp_status_sync', "Status WhatsApp sincronizado: " . ($realStatus ? 'conectado' : 'desconectado'), $company_id);
                    }
                    
                    echo json_encode(['connected' => $realStatus]);
                } else {
                    echo json_encode(['connected' => (bool)$company['whatsapp_connected']]);
                }
                
            } catch (Exception $e) {
                error_log("Erro ao verificar status WhatsApp: " . $e->getMessage());
                echo json_encode(['connected' => false, 'error' => 'Internal error']);
            }
            exit;
        }
        
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            // Get company data
            $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            $company = $stmt->fetch();
            
            if (!$company) {
                $_SESSION['error'] = 'Empresa não encontrada';
                header('Location: /company/dashboard');
                exit;
            }
            
            // Get admin config for Evolution API
            $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
            $config = $stmt->fetch();
            
            if (!$config) {
                $_SESSION['error'] = 'Configurações da API não encontradas. Entre em contato com o administrador.';
                header('Location: /company/dashboard');
                exit;
            }
            
            // Generate instance name based on company
            $instance_name = 'company_' . $company_id;
            
            // Variables to pass to view
            $whatsapp_connected = $company['whatsapp_connected'];
            $company_phone = $company['telefone'];
            $ai_preference = $company['ia_preferida'];
            $qr_code = null;
            $pairing_code = null;
            
            // Check for QR code or pairing code data from session (after redirect)
            if (isset($_SESSION['qr_code_data'])) {
                $qr_code = $_SESSION['qr_code_data'];
                unset($_SESSION['qr_code_data']);
            }
            
            if (isset($_SESSION['pairing_code_data'])) {
                $pairing_code = $_SESSION['pairing_code_data'];
                unset($_SESSION['pairing_code_data']);
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verifyCSRFToken($_POST['csrf_token'])) {
                    $_SESSION['error'] = 'Token CSRF inválido';
                    header('Location: /company/whatsapp');
                    exit;
                }
                
                $action = $_POST['action'];
                
                if ($action === 'connect') {
                    $phone = sanitize($_POST['phone'] ?? '');
                    
                    if (empty($phone)) {
                        $_SESSION['error'] = 'Número do telefone é obrigatório';
                        header('Location: /company/whatsapp');
                        exit;
                    }
                    
                    // Update company phone and instance name
                    $stmt = $pdo->prepare("UPDATE companies SET telefone = ?, whatsapp_instance = ? WHERE id = ?");
                    $stmt->execute([$phone, $instance_name, $company_id]);
                    
                    // Try to connect to Evolution API
                    $result = $this->connectWhatsAppInstance($config, $instance_name, $phone);
                    
                    if ($result['success']) {
                        if (isset($result['qr_code'])) {
                            $_SESSION['qr_code_data'] = $result['qr_code'];
                            $_SESSION['success'] = 'QR Code gerado! Escaneie com seu WhatsApp.';
                        } elseif (isset($result['pairing_code'])) {
                            $_SESSION['pairing_code_data'] = $result['pairing_code'];
                            $_SESSION['success'] = 'Código de pareamento gerado! Use o código: ' . $result['pairing_code'];
                        } elseif (isset($result['already_connected']) && $result['already_connected']) {
                            // Update database immediately if already connected
                            $stmt = $pdo->prepare("UPDATE companies SET whatsapp_connected = 1 WHERE id = ?");
                            $stmt->execute([$company_id]);
                            $_SESSION['success'] = 'WhatsApp já está conectado!';
                        } else {
                            $_SESSION['success'] = 'Instância criada com sucesso! Aguarde a conexão.';
                        }
                        auditLog('whatsapp_connect_attempt', "Tentativa de conexão WhatsApp - Instância: $instance_name", $company_id, $_SESSION['user_id']);
                    } else {
                        $_SESSION['error'] = 'Erro ao conectar: ' . $result['message'];
                        auditLog('whatsapp_connect_error', "Erro ao conectar WhatsApp: " . $result['message'], $company_id, $_SESSION['user_id']);
                    }
                } elseif ($action === 'disconnect') {
                    // Disconnect WhatsApp instance
                    $result = $this->disconnectWhatsAppInstance($config, $instance_name);
                    
                    if ($result['success']) {
                        $stmt = $pdo->prepare("UPDATE companies SET whatsapp_connected = 0 WHERE id = ?");
                        $stmt->execute([$company_id]);
                        $_SESSION['success'] = 'WhatsApp desconectado com sucesso!';
                        auditLog('whatsapp_disconnect', "WhatsApp desconectado - Instância: $instance_name", $company_id, $_SESSION['user_id']);
                    } else {
                        $_SESSION['error'] = 'Erro ao desconectar: ' . $result['message'];
                    }
                } elseif ($action === 'update_ai') {
                    $ai_preference = sanitize($_POST['ai_preference']);
                    
                    $stmt = $pdo->prepare("UPDATE companies SET ia_preferida = ? WHERE id = ?");
                    if ($stmt->execute([$ai_preference, $company_id])) {
                        $_SESSION['success'] = 'Configurações de IA atualizadas com sucesso!';
                        auditLog('ai_config_updated', "Configurações de IA atualizadas: $ai_preference", $company_id, $_SESSION['user_id']);
                    } else {
                        $_SESSION['error'] = 'Erro ao atualizar configurações de IA';
                    }
                }
                
                header('Location: /company/whatsapp');
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Erro na página WhatsApp: " . $e->getMessage());
            $_SESSION['error'] = 'Erro interno do sistema';
            $whatsapp_connected = false;
            $company_phone = '';
            $ai_preference = 'padrao';
            $instance_name = '';
            $qr_code = null;
            $pairing_code = null;
        }
        
        include BASE_PATH . '/views/company/whatsapp.php';
    }
    
    private function checkWhatsAppConnectionState($config, $instance_name) {
        try {
            $api_url = rtrim($config['api_whatsapp_url'], '/');
            $api_token = $config['api_whatsapp_token'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$api_url/instance/connectionState/$instance_name");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $api_token
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if (is_array($data) && isset($data['instance']['state'])) {
                    $state = $data['instance']['state'];
                    return $state === 'open';
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao verificar estado da conexão WhatsApp: " . $e->getMessage());
            return false;
        }
    }
    
    private function connectWhatsAppInstance($config, $instance_name, $phone) {
        try {
            $api_url = rtrim($config['api_whatsapp_url'], '/');
            $api_token = $config['api_whatsapp_token'];
            
            // Step 1: Check if instance exists and its state
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$api_url/instance/fetchInstances");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $api_token
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return ['success' => false, 'message' => "Erro ao conectar com a API Evolution (HTTP $httpCode)"];
            }
            
            $instances = json_decode($response, true);
            $instance_exists = false;
            $current_state = null;
            
            if (is_array($instances)) {
                foreach ($instances as $instance) {
                    if (isset($instance['instance']['instanceName']) && $instance['instance']['instanceName'] === $instance_name) {
                        $instance_exists = true;
                        $current_state = $instance['instance']['state'] ?? 'unknown';
                        break;
                    }
                }
            }
            
            // If instance exists and is already connected
            if ($instance_exists && $current_state === 'open') {
                return ['success' => true, 'already_connected' => true, 'message' => 'WhatsApp já está conectado'];
            }
            
            // Step 2: Create instance if it doesn't exist
            if (!$instance_exists) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $webhook_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/webhook/whatsapp.php';
                
                $create_data = [
                    'instanceName' => $instance_name,
                    'number' => $phone,
                    'integration' => 'WHATSAPP-BAILEYS',
                    'qrcode' => true,
                    'webhook' => [
                        'url' => $webhook_url,
                        'byEvents' => true,
                        'base64' => true,
                        'events' => [
                            'QRCODE_UPDATED',
                            'CONNECTION_UPDATE',
                            'MESSAGES_UPSERT'
                        ]
                    ]
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "$api_url/instance/create");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($create_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'apikey: ' . $api_token
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 201 && $httpCode !== 200) {
                    $error_data = json_decode($response, true);
                    $error_message = 'Erro ao criar instância';
                    
                    if (is_array($error_data) && isset($error_data['message'])) {
                        $error_message = $error_data['message'];
                    }
                    
                    return ['success' => false, 'message' => $error_message];
                }
                
                // Wait for instance to initialize
                sleep(3);
            }
            
            // Step 3: Connect to get QR code
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$api_url/instance/connect/$instance_name");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $api_token
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $connect_data = json_decode($response, true);
                
                if (is_array($connect_data)) {
                    // Check for QR code
                    if (isset($connect_data['base64']) && !empty($connect_data['base64'])) {
                        $qr_base64 = $connect_data['base64'];
                        if (strpos($qr_base64, 'data:image/png;base64,') === 0) {
                            $qr_base64 = substr($qr_base64, strlen('data:image/png;base64,'));
                        }
                        return ['success' => true, 'qr_code' => $qr_base64];
                    }
                    
                    if (isset($connect_data['qrcode']['base64']) && !empty($connect_data['qrcode']['base64'])) {
                        $qr_base64 = $connect_data['qrcode']['base64'];
                        if (strpos($qr_base64, 'data:image/png;base64,') === 0) {
                            $qr_base64 = substr($qr_base64, strlen('data:image/png;base64,'));
                        }
                        return ['success' => true, 'qr_code' => $qr_base64];
                    }
                    
                    // Check for pairing code
                    if (isset($connect_data['pairingCode']) && !empty($connect_data['pairingCode'])) {
                        return ['success' => true, 'pairing_code' => $connect_data['pairingCode']];
                    }
                    
                    // Check instance state
                    $state = $connect_data['instance']['state'] ?? $connect_data['state'] ?? null;
                    
                    if ($state === 'open') {
                        return ['success' => true, 'already_connected' => true, 'message' => 'WhatsApp já está conectado'];
                    }
                }
            }
            
            return ['success' => false, 'message' => 'Não foi possível gerar QR Code. Tente novamente.'];
            
        } catch (Exception $e) {
            error_log("Erro ao conectar WhatsApp: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao conectar'];
        }
    }
    
    private function disconnectWhatsAppInstance($config, $instance_name) {
        try {
            $api_url = rtrim($config['api_whatsapp_url'], '/');
            $api_token = $config['api_whatsapp_token'];
            
            // Logout instance
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$api_url/instance/logout/$instance_name");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $api_token
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return ['success' => true, 'message' => 'Desconectado com sucesso'];
            }
            
            return ['success' => false, 'message' => "Erro ao desconectar (HTTP $httpCode): $response"];
            
        } catch (Exception $e) {
            error_log("Erro ao desconectar WhatsApp: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno ao desconectar'];
        }
    }
}
?>