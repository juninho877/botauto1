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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    // Log do webhook recebido
    error_log("WhatsApp Webhook received: " . $input);
    
    // Processar eventos de conexão
    if (isset($data['event']) && $data['event'] === 'connection.update') {
        processConnectionUpdate($data);
    }
    
    // Processar eventos de QR Code
    if (isset($data['event']) && $data['event'] === 'qrcode.updated') {
        processQRCodeUpdate($data);
    }
    
    // Processar mensagens
    if (isset($data['event']) && $data['event'] === 'messages.upsert') {
        $controller = new WhatsAppController();
        $controller->processWebhook();
    }
    
    // Processar mensagens no formato antigo (compatibilidade)
    if (isset($data['data']['key']['fromMe']) && $data['data']['key']['fromMe'] === false) {
        $controller = new WhatsAppController();
        $controller->processWebhook();
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    
} catch (Exception $e) {
    error_log("Erro no webhook WhatsApp: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function processConnectionUpdate($data) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Extrair informações do evento
        $instanceName = $data['instance'] ?? null;
        $state = $data['data']['state'] ?? null;
        
        if (!$instanceName || !$state) {
            error_log("Dados insuficientes no evento de conexão: " . json_encode($data));
            return;
        }
        
        error_log("Processando atualização de conexão - Instância: $instanceName, Estado: $state");
        
        // Buscar empresa pela instância
        $stmt = $pdo->prepare("SELECT id, nome FROM companies WHERE whatsapp_instance = ?");
        $stmt->execute([$instanceName]);
        $company = $stmt->fetch();
        
        if (!$company) {
            error_log("Empresa não encontrada para instância: $instanceName");
            return;
        }
        
        // Atualizar status baseado no estado
        $connected = 0;
        if ($state === 'open') {
            $connected = 1;
            error_log("WhatsApp conectado para empresa: {$company['nome']} (ID: {$company['id']})");
        } elseif (in_array($state, ['close', 'closed', 'disconnected'])) {
            $connected = 0;
            error_log("WhatsApp desconectado para empresa: {$company['nome']} (ID: {$company['id']})");
        } else {
            error_log("Estado de conexão não processado: $state para empresa: {$company['nome']}");
            return;
        }
        
        // Atualizar no banco de dados
        $stmt = $pdo->prepare("UPDATE companies SET whatsapp_connected = ? WHERE id = ?");
        if ($stmt->execute([$connected, $company['id']])) {
            error_log("Status WhatsApp atualizado com sucesso para empresa ID: {$company['id']} - Conectado: $connected");
            
            // Log de auditoria
            auditLog('whatsapp_status_updated', "Status WhatsApp atualizado via webhook: $state", $company['id']);
        } else {
            error_log("Erro ao atualizar status WhatsApp para empresa ID: {$company['id']}");
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar atualização de conexão: " . $e->getMessage());
    }
}

function processQRCodeUpdate($data) {
    try {
        $instanceName = $data['instance'] ?? null;
        $qrCode = $data['data']['qrcode'] ?? null;
        
        if (!$instanceName || !$qrCode) {
            return;
        }
        
        error_log("QR Code atualizado para instância: $instanceName");
        
        // Aqui você pode implementar lógica adicional para armazenar o QR Code
        // Por exemplo, em cache ou sessão para exibir na interface
        
    } catch (Exception $e) {
        error_log("Erro ao processar atualização de QR Code: " . $e->getMessage());
    }
}
?>