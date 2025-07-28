<?php
require_once '../config/config.php';
require_once '../controllers/WhatsAppController.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $controller = new WhatsAppController();
    $controller->processWebhook();
} catch (Exception $e) {
    error_log("Erro no webhook WhatsApp: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>