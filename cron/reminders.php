<?php
require_once '../config/config.php';
require_once '../models/Appointment.php';

try {
    $appointmentModel = new Appointment();
    $pendingReminders = $appointmentModel->getPendingReminders();
    
    if (empty($pendingReminders)) {
        echo date('Y-m-d H:i:s') . " - Nenhum lembrete para enviar\n";
        exit;
    }
    
    echo date('Y-m-d H:i:s') . " - Processando " . count($pendingReminders) . " lembretes\n";
    
    // Buscar configurações da API WhatsApp
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
    $config = $stmt->fetch();
    
    if (!$config) {
        echo "ERRO: Configurações não encontradas\n";
        exit;
    }
    
    foreach ($pendingReminders as $appointment) {
        $message = "🔔 *Lembrete de Agendamento*\n\n";
        $message .= "Olá! Você tem um agendamento marcado para amanhã:\n\n";
        $message .= "📅 *Data:* " . date('d/m/Y', strtotime($appointment['data_agendamento'])) . "\n";
        $message .= "🕒 *Horário:* " . substr($appointment['hora_inicio'], 0, 5) . "\n";
        $message .= "💼 *Serviço:* " . $appointment['servico_nome'] . "\n";
        $message .= "🏢 *Local:* " . $appointment['company_name'] . "\n\n";
        $message .= "Por favor, confirme sua presença respondendo esta mensagem.\n\n";
        $message .= "Em caso de cancelamento, nos avise com antecedência.\n\n";
        $message .= "Obrigado! 😊";
        
        // Enviar mensagem via API WhatsApp
        $data = [
            'number' => $appointment['telefone'],
            'text' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['api_whatsapp_url'] . '/message/sendText/default'); // instance padrão
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_whatsapp_token']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            // Marcar lembrete como enviado
            $appointmentModel->markReminderSent($appointment['id']);
            echo "✓ Lembrete enviado para {$appointment['telefone']} - {$appointment['cliente_nome']}\n";
        } else {
            echo "✗ Erro ao enviar lembrete para {$appointment['telefone']} - HTTP $httpCode\n";
            error_log("Erro ao enviar lembrete WhatsApp: HTTP $httpCode - $response");
        }
        
        // Pequena pausa entre envios para não sobrecarregar a API
        sleep(1);
    }
    
    echo date('Y-m-d H:i:s') . " - Processamento de lembretes concluído\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    error_log("Erro no cron de lembretes: " . $e->getMessage());
}
?>