<?php
require_once __DIR__ . '/../config/config.php';

// Router simples
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remover trailing slash
$path = rtrim($path, '/');
if (empty($path)) $path = '/';

// Rotas públicas (não precisam de autenticação)
$publicRoutes = ['/', '/login', '/register', '/webhook/whatsapp'];

// Verificar autenticação para rotas protegidas
if (!in_array($path, $publicRoutes)) {
    if (!isset($_SESSION['user_id']) || !checkSessionTimeout()) {
        header('Location: /login');
        exit;
    }
}

try {
    switch ($path) {
        case '/':
        case '/login':
            $controller = new AuthController();
            $controller->login();
            break;
            
        case '/register':
            $controller = new AuthController();
            $controller->register();
            break;
            
        case '/logout':
            $controller = new AuthController();
            $controller->logout();
            break;
            
        // Rotas Admin
        case '/admin/dashboard':
            $controller = new AdminController();
            $controller->dashboard();
            break;
            
        case '/admin/companies':
            $controller = new AdminController();
            $controller->companies();
            break;
            
        case '/admin/create-company':
            $controller = new AdminController();
            $controller->createCompany();
            break;
            
        case '/admin/toggle-company':
            $controller = new AdminController();
            $controller->toggleCompany();
            break;
            
        case '/admin/settings':
            $controller = new AdminController();
            $controller->settings();
            break;
            
        case '/admin/logs':
            $controller = new AdminController();
            $controller->logs();
            break;
            
        // Webhook WhatsApp
        case '/webhook/whatsapp':
            require_once __DIR__ . '/../controllers/WhatsAppController.php';
            $controller = new WhatsAppController();
            $controller->processWebhook();
            break;
            
        // Rotas Company (Empresa)
        case '/company/dashboard':
            $controller = new CompanyController();
            $controller->dashboard();
            break;
            
        case '/company/services':
            $controller = new CompanyController();
            $controller->services();
            break;
            
        case '/company/appointments':
            $controller = new CompanyController();
            $controller->appointments();
            break;
            
        case '/company/calendar':
            $controller = new CompanyController();
            $controller->calendar();
            break;
            
        case '/company/conversations':
            $controller = new CompanyController();
            $controller->conversations();
            break;
            
        case '/company/whatsapp':
            $controller = new CompanyController();
            $controller->whatsapp();
            break;
            
        default:
            http_response_code(404);
            echo "<h1>Página não encontrada</h1>";
            echo "<p>A página solicitada não existe.</p>";
            echo "<a href='/login'>Voltar ao login</a>";
            break;
    }
} catch (Exception $e) {
    error_log("Erro na aplicação: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>Erro interno do servidor</h1>";
    echo "<p>Ocorreu um erro interno. Tente novamente mais tarde.</p>";
    if (defined('DEBUG') && DEBUG) {
        echo "<pre>" . $e->getMessage() . "</pre>";
    }
}
?>