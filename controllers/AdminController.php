<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->checkAuth();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        if (!checkSessionTimeout()) {
            header('Location: /login');
            exit;
        }
    }
    
    public function dashboard() {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            // Estatísticas do dashboard
            $stats = [];
            
            // Total de empresas
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM companies WHERE ativo = 1");
            $stats['empresas_ativas'] = $stmt->fetch()['total'];
            
            // Total de agendamentos hoje
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE DATE(created_at) = CURRENT_DATE()");
            $stats['agendamentos_hoje'] = $stmt->fetch()['total'];
            
            // Mensagens processadas hoje
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_messages WHERE DATE(created_at) = CURRENT_DATE()");
            $stats['mensagens_hoje'] = $stmt->fetch()['total'];
            
            // Empresas recentes
            $stmt = $pdo->query("SELECT nome, email, created_at FROM companies ORDER BY created_at DESC LIMIT 5");
            $stats['empresas_recentes'] = $stmt->fetchAll();
            
            include 'views/admin/dashboard.php';
            include BASE_PATH . '/views/admin/dashboard.php';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao carregar dashboard';
            error_log("Erro no dashboard admin: " . $e->getMessage());
            include 'views/admin/dashboard.php';
            include BASE_PATH . '/views/admin/dashboard.php';
        }
    }
    
    public function companies() {
        $companies = $this->userModel->getCompanies(false);
        include 'views/admin/companies.php';
        include BASE_PATH . '/views/admin/companies.php';
    }
    
    public function createCompany() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido';
                header('Location: /admin/companies');
                exit;
            }
            
            $data = [
                'nome' => sanitize($_POST['nome']),
                'email' => sanitize($_POST['email']),
                'senha' => $_POST['senha'],
                'telefone' => sanitize($_POST['telefone']),
                'horario_funcionamento' => json_encode([
                    'segunda' => ['inicio' => '08:00', 'fim' => '18:00'],
                    'terca' => ['inicio' => '08:00', 'fim' => '18:00'],
                    'quarta' => ['inicio' => '08:00', 'fim' => '18:00'],
                    'quinta' => ['inicio' => '08:00', 'fim' => '18:00'],
                    'sexta' => ['inicio' => '08:00', 'fim' => '18:00'],
                    'sabado' => ['inicio' => '08:00', 'fim' => '16:00'],
                    'domingo' => ['fechado' => true]
                ])
            ];
            
            if ($this->userModel->createCompany($data)) {
                $_SESSION['success'] = 'Empresa criada com sucesso!';
                auditLog('admin_company_created', "Empresa criada pelo admin: {$data['nome']}", null, $_SESSION['user_id']);
            } else {
                $_SESSION['error'] = 'Erro ao criar empresa';
            }
        }
        
        header('Location: /admin/companies');
        exit;
    }
    
    public function toggleCompany() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            
            if ($this->userModel->toggleCompanyStatus($id)) {
                $_SESSION['success'] = 'Status da empresa alterado com sucesso!';
                auditLog('admin_company_toggle', "Status da empresa ID $id alterado", null, $_SESSION['user_id']);
            } else {
                $_SESSION['error'] = 'Erro ao alterar status da empresa';
            }
        }
        
        header('Location: /admin/companies');
        exit;
    }
    
    public function settings() {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verifyCSRFToken($_POST['csrf_token'])) {
                    $_SESSION['error'] = 'Token CSRF inválido';
                    header('Location: /admin/settings');
                    exit;
                }
                
                $stmt = $pdo->prepare("
                    UPDATE admin_config SET 
                    api_whatsapp_url = ?, 
                    api_whatsapp_token = ?, 
                    openai_key = ?, 
                    gemini_key = ?, 
                    ia_padrao = ?
                    WHERE id = 1
                ");
                
                if ($stmt->execute([
                    sanitize($_POST['api_whatsapp_url']),
                    sanitize($_POST['api_whatsapp_token']),
                    sanitize($_POST['openai_key']),
                    sanitize($_POST['gemini_key']),
                    sanitize($_POST['ia_padrao'])
                ])) {
                    $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
                    auditLog('admin_settings_updated', 'Configurações globais atualizadas', null, $_SESSION['user_id']);
                } else {
                    $_SESSION['error'] = 'Erro ao atualizar configurações';
                }
                
                header('Location: /admin/settings');
                exit;
            }
            
            // Carregar configurações atuais
            $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
            $config = $stmt->fetch();
            
            include 'views/admin/settings.php';
            include BASE_PATH . '/views/admin/settings.php';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao carregar configurações';
            error_log("Erro nas configurações admin: " . $e->getMessage());
            include 'views/admin/settings.php';
            include BASE_PATH . '/views/admin/settings.php';
        }
    }
    
    public function logs() {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = 50;
            $offset = ($page - 1) * $limit;
            
            $stmt = $pdo->prepare("
                SELECT l.*, c.nome as company_nome 
                FROM system_logs l 
                LEFT JOIN companies c ON l.company_id = c.id 
                ORDER BY l.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $logs = $stmt->fetchAll();
            
            // Total de logs para paginação
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs");
            $total = $stmt->fetch()['total'];
            $totalPages = ceil($total / $limit);
            
            include 'views/admin/logs.php';
            include BASE_PATH . '/views/admin/logs.php';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao carregar logs';
            error_log("Erro nos logs admin: " . $e->getMessage());
            include 'views/admin/logs.php';
            include BASE_PATH . '/views/admin/logs.php';
        }
    }
}
?>