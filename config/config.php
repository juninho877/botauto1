<?php
// Include database configuration first
require_once __DIR__ . '/database.php';

// Configurações gerais do sistema
define('BASE_URL', 'http://localhost');
define('BASE_PATH', dirname(__DIR__));

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mudar para 1 em HTTPS
session_start();

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de segurança
define('BCRYPT_COST', 12);
define('SESSION_TIMEOUT', 3600); // 1 hora

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configurações de debug (apenas para desenvolvimento)
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');

// Criar diretório de logs se não existir
if (!file_exists(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

// Função para log personalizado do WhatsApp
function whatsappLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] WHATSAPP: $message";
    
    if ($data !== null) {
        $logMessage .= " | DATA: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    $logMessage .= "\n";
    
    // Log para arquivo específico do WhatsApp
    file_put_contents(BASE_PATH . '/logs/whatsapp.log', $logMessage, FILE_APPEND | LOCK_EX);
    
    // Log também para error_log padrão
    error_log($logMessage);
}

// Função para verificar timeout de sessão
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para log de auditoria
function auditLog($acao, $detalhes = '', $company_id = null, $usuario_id = null) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (company_id, usuario_id, acao, detalhes, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $company_id,
            $usuario_id,
            $acao,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Erro no audit log: " . $e->getMessage());
    }
}

// Função para sanitizar dados
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Autoload das classes
spl_autoload_register(function ($className) {
    $directories = ['models', 'controllers'];
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . '/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>