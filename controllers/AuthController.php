<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $type = $_POST['type'] ?? 'company';
            
            if (empty($email) || empty($password)) {
                $_SESSION['error'] = 'Email e senha são obrigatórios';
                header('Location: /login');
                exit;
            }
            
            $user = $this->userModel->authenticate($email, $password, $type);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $type;
                $_SESSION['last_activity'] = time();
                
                auditLog('login', "Login realizado", $type === 'company' ? $user['id'] : null, $user['id']);
                
                if ($type === 'admin') {
                    header('Location: /admin/dashboard');
                } else {
                    header('Location: /company/dashboard');
                }
                exit;
            } else {
                $_SESSION['error'] = 'Email ou senha inválidos';
                auditLog('login_failed', "Tentativa de login com email: $email");
            }
        }
        
        include 'views/auth/login.php';
    }
    
    public function logout() {
        auditLog('logout', 'Logout realizado', 
                $_SESSION['user_type'] === 'company' ? $_SESSION['user_id'] : null, 
                $_SESSION['user_id']);
        
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido';
                header('Location: /register');
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
            
            if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
                $_SESSION['error'] = 'Todos os campos são obrigatórios';
                header('Location: /register');
                exit;
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Email inválido';
                header('Location: /register');
                exit;
            }
            
            if (strlen($data['senha']) < 6) {
                $_SESSION['error'] = 'Senha deve ter pelo menos 6 caracteres';
                header('Location: /register');
                exit;
            }
            
            if ($this->userModel->createCompany($data)) {
                $_SESSION['success'] = 'Empresa cadastrada com sucesso! Faça login para continuar.';
                auditLog('company_created', "Nova empresa cadastrada: {$data['nome']} - {$data['email']}");
                header('Location: /login');
                exit;
            } else {
                $_SESSION['error'] = 'Erro ao cadastrar empresa. Email pode já estar em uso.';
            }
        }
        
        include 'views/auth/register.php';
    }
}
?>