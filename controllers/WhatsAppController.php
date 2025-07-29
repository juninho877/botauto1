<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/AIController.php';

class WhatsAppController {
    private $appointmentModel;
    private $serviceModel;
    private $aiController;
    
    public function __construct() {
        $this->appointmentModel = new Appointment();
        $this->serviceModel = new Service();
        $this->aiController = new AIController();
    }
    
    public function handleMessageUpsert($messageData) {
        try {
            // Log da mensagem recebida
            error_log("Processing message: " . json_encode($messageData));
            
            // Verificar se é uma mensagem de texto
            if (isset($messageData['key']['fromMe']) && $messageData['key']['fromMe'] === false) {
                $phone = $this->cleanPhone($messageData['key']['remoteJid']);
                $message = $messageData['message']['conversation'] ?? 
                          $messageData['message']['extendedTextMessage']['text'] ?? '';
                
                if (!empty($message)) {
                    $this->processMessage($phone, $message);
                }
            }
            
        } catch (Exception $e) {
            error_log("Erro ao processar mensagem WhatsApp: " . $e->getMessage());
        }
    }
    
    private function processMessage($phone, $message) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            // Identificar empresa pelo número (primeira empresa ativa por enquanto)
            $stmt = $pdo->query("SELECT * FROM companies WHERE ativo = 1 LIMIT 1");
            $company = $stmt->fetch();
            
            if (!$company) {
                return;
            }
            
            // Buscar ou criar conversa
            $stmt = $pdo->prepare("
                SELECT id FROM whatsapp_conversations 
                WHERE company_id = ? AND telefone = ?
            ");
            $stmt->execute([$company['id'], $phone]);
            $conversation = $stmt->fetch();
            
            if (!$conversation) {
                $stmt = $pdo->prepare("
                    INSERT INTO whatsapp_conversations (company_id, telefone, ultima_mensagem, ultima_interacao) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$company['id'], $phone, $message]);
                $conversation_id = $pdo->lastInsertId();
            } else {
                $conversation_id = $conversation['id'];
                
                // Atualizar conversa
                $stmt = $pdo->prepare("
                    UPDATE whatsapp_conversations 
                    SET ultima_mensagem = ?, ultima_interacao = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$message, $conversation_id]);
            }
            
            // Salvar mensagem
            $stmt = $pdo->prepare("
                INSERT INTO whatsapp_messages (conversation_id, company_id, telefone, tipo, conteudo) 
                VALUES (?, ?, ?, 'recebida', ?)
            ");
            $stmt->execute([$conversation_id, $company['id'], $phone, $message]);
            
            // Processar com IA
            $response = $this->processWithAI($message, $company, $phone);
            
            if ($response) {
                // Enviar resposta
                $this->sendMessage($phone, $response, $company);
                
                // Salvar resposta
                $stmt = $pdo->prepare("
                    INSERT INTO whatsapp_messages (conversation_id, company_id, telefone, tipo, conteudo) 
                    VALUES (?, ?, ?, 'enviada', ?)
                ");
                $stmt->execute([$conversation_id, $company['id'], $phone, $response]);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao processar mensagem: " . $e->getMessage());
        }
    }
    
    private function processWithAI($message, $company, $phone) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            // Buscar configurações de IA
            $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
            $config = $stmt->fetch();
            
            if (!$config) {
                return "Desculpe, estou com problemas técnicos. Tente novamente mais tarde.";
            }
            
            // Determinar qual IA usar
            $ia_tipo = $company['ia_preferida'] === 'padrao' ? $config['ia_padrao'] : $company['ia_preferida'];
            
            // Criar contexto rico para a IA
            $services = $this->serviceModel->getByCompany($company['id']);
            $recent_appointments = $this->appointmentModel->getByPhone($company['id'], $phone, 3);
            
            $context = [
                'empresa' => $company,
                'servicos' => $services,
                'agendamentos_recentes' => $recent_appointments,
                'horario_funcionamento' => json_decode($company['horario_funcionamento'], true),
                'telefone_cliente' => $phone
            ];
            
            error_log("WHATSAPP AI: Analisando mensagem: '$message' para empresa: {$company['nome']}");
            
            // Analisar intenção do usuário
            $intent = $this->aiController->analyzeIntent($message, $context);
            error_log("WHATSAPP AI: Intenção detectada: " . json_encode($intent));
            
            // Processar baseado na intenção
            if (isset($intent['action'])) {
                switch ($intent['action']) {
                    case 'schedule':
                        error_log("WHATSAPP AI: Processando solicitação de agendamento");
                        $scheduleResponse = $this->aiController->processScheduleRequest($intent, $context, $phone);
                        if (isset($scheduleResponse['message'])) {
                            return $scheduleResponse['message'];
                        }
                        break;
                        
                    case 'cancel':
                        return $this->handleCancelRequest($context, $phone);
                        
                    case 'check_availability':
                        return $this->handleAvailabilityCheck($intent, $context);
                        
                    case 'info':
                        if ($intent['type'] === 'services') {
                            return $this->buildServicesInfo($context['servicos']);
                        }
                        break;
                }
            }
            
            // Se não foi uma intenção específica ou falhou, usar IA geral
            error_log("WHATSAPP AI: Usando IA geral ($ia_tipo) para resposta");
            
            if ($ia_tipo === 'chatgpt' && !empty($config['openai_key'])) {
                $prompt = $this->aiController->buildPrompt($message, $context);
                return $this->processWithChatGPT($prompt, $config['openai_key']);
            } elseif ($ia_tipo === 'gemini' && !empty($config['gemini_key'])) {
                $prompt = $this->aiController->buildPrompt($message, $context);
                return $this->processWithGemini($prompt, $config['gemini_key']);
            }
            
            // Fallback para resposta simples
            error_log("WHATSAPP AI: Usando bot simples (sem chaves de IA configuradas)");
            return $this->processWithSimpleBot($message, $context);
            
        } catch (Exception $e) {
            error_log("Erro no processamento de IA: " . $e->getMessage());
            return "Desculpe, estou com problemas técnicos. Tente novamente mais tarde.";
        }
    }
    
    private function handleCancelRequest($context, $phone) {
        $recent_appointments = $context['agendamentos_recentes'];
        
        if (empty($recent_appointments)) {
            return "Não encontrei agendamentos recentes em seu nome. Se você tem um agendamento, por favor me informe mais detalhes como a data ou serviço.";
        }
        
        $response = "📋 Seus agendamentos recentes:\n\n";
        foreach ($recent_appointments as $index => $appointment) {
            if ($appointment['status'] !== 'cancelado') {
                $response .= ($index + 1) . ". {$appointment['servico_nome']}\n";
                $response .= "   📅 " . date('d/m/Y', strtotime($appointment['data_agendamento'])) . " às " . substr($appointment['hora_inicio'], 0, 5) . "\n";
                $response .= "   Status: " . ucfirst($appointment['status']) . "\n\n";
            }
        }
        
        $response .= "Para cancelar, me informe qual agendamento (número) ou a data específica.";
        return $response;
    }
    
    private function handleAvailabilityCheck($intent, $context) {
        $date = $intent['date'] ?? date('Y-m-d', strtotime('+1 day'));
        $service_id = $intent['service_id'] ?? null;
        
        if (!$service_id && !empty($context['servicos'])) {
            $service_id = $context['servicos'][0]['id']; // Usar primeiro serviço como padrão
        }
        
        if ($service_id) {
            $available_slots = $this->appointmentModel->getAvailableSlots($context['empresa']['id'], $service_id, $date);
            
            if (empty($available_slots)) {
                return "❌ Não temos horários disponíveis para " . date('d/m/Y', strtotime($date)) . ".\n\nGostaria de verificar outra data?";
            }
            
            $response = "✅ Horários disponíveis para " . date('d/m/Y', strtotime($date)) . ":\n\n";
            foreach ($available_slots as $slot) {
                $response .= "• $slot\n";
            }
            $response .= "\nQual horário prefere?";
            return $response;
        }
        
        return "Para verificar disponibilidade, preciso saber qual serviço você deseja. Qual serviço tem interesse?";
    }
    
    private function buildServicesInfo($services) {
        if (empty($services)) {
            return "Desculpe, não temos serviços cadastrados no momento.";
        }
        
        $response = "💼 Nossos serviços:\n\n";
        foreach ($services as $service) {
            $response .= "• *{$service['nome']}*\n";
            if ($service['descricao']) {
                $response .= "  {$service['descricao']}\n";
            }
            $response .= "  ⏱️ {$service['duracao_minutos']} minutos\n";
            $response .= "  💰 R$ " . number_format($service['preco'], 2, ',', '.') . "\n\n";
        }
        $response .= "Gostaria de agendar algum desses serviços?";
        return $response;
    }
    
    private function processWithChatGPT($prompt, $api_key) {
        error_log("WHATSAPP AI: Enviando para ChatGPT: " . substr($prompt, 0, 200) . "...");
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um assistente de agendamento inteligente. Seja prestativo, profissional e natural na conversa. Use emojis quando apropriado.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("WHATSAPP AI: ChatGPT HTTP Code: $httpCode");
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $aiResponse = $result['choices'][0]['message']['content'] ?? 'Desculpe, não entendi. Pode reformular?';
            error_log("WHATSAPP AI: ChatGPT respondeu: " . substr($aiResponse, 0, 200) . "...");
            return $aiResponse;
        } else {
            error_log("WHATSAPP AI: Erro ChatGPT HTTP $httpCode: $response");
        }
        
        return "Desculpe, estou com problemas técnicos. Tente novamente mais tarde.";
    }
    
    private function processWithGemini($prompt, $api_key) {
        error_log("WHATSAPP AI: Enviando para Gemini: " . substr($prompt, 0, 200) . "...");
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("WHATSAPP AI: Gemini HTTP Code: $httpCode");
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Desculpe, não entendi. Pode reformular?';
            error_log("WHATSAPP AI: Gemini respondeu: " . substr($aiResponse, 0, 200) . "...");
            return $aiResponse;
        } else {
            error_log("WHATSAPP AI: Erro Gemini HTTP $httpCode: $response");
        }
        
        return "Desculpe, estou com problemas técnicos. Tente novamente mais tarde.";
    }
    
    private function processWithSimpleBot($message, $context) {
        error_log("WHATSAPP AI: Usando bot simples para: '$message'");
        $message_lower = strtolower($message);
        
        // Saudações
        if (preg_match('/\b(oi|olá|ola|bom dia|boa tarde|boa noite)\b/', $message_lower)) {
            return "Olá! Bem-vindo(a) ao {$context['empresa']['nome']}! 😊\n\nComo posso ajudá-lo(a) hoje? Posso:\n• Mostrar nossos serviços\n• Ajudar com agendamentos\n• Verificar disponibilidade\n\nO que gostaria de fazer?";
        }
        
        // Solicitar serviços
        if (preg_match('/\b(serviços|serviços|preços|precos|tabela|valores)\b/', $message_lower)) {
            return $this->buildServicesInfo($context['servicos']);
        }
        
        // Agendamento
        if (preg_match('/\b(agendar|marcar|horário|horario|agendamento)\b/', $message_lower)) {
            return "📅 Para agendar seu horário, preciso de algumas informações:\n\n1. Qual serviço deseja?\n2. Qual data prefere?\n3. Qual horário?\n\nPor favor, me informe esses detalhes e verificarei a disponibilidade!";
        }
        
        // Horário de funcionamento
        if (preg_match('/\b(horário|horario|funcionamento|aberto|fecha|abre)\b/', $message_lower)) {
            $horarios = $context['horario_funcionamento'];
            $response = "🕒 Nosso horário de funcionamento:\n\n";
            
            $dias = [
                'segunda' => 'Segunda-feira',
                'terca' => 'Terça-feira', 
                'quarta' => 'Quarta-feira',
                'quinta' => 'Quinta-feira',
                'sexta' => 'Sexta-feira',
                'sabado' => 'Sábado',
                'domingo' => 'Domingo'
            ];
            
            foreach ($dias as $key => $dia) {
                if (isset($horarios[$key]['fechado'])) {
                    $response .= "$dia: Fechado\n";
                } else {
                    $response .= "$dia: {$horarios[$key]['inicio']} às {$horarios[$key]['fim']}\n";
                }
            }
            
            return $response;
        }
        
        // Localização/contato
        if (preg_match('/\b(endereço|endereco|localização|localizacao|onde|telefone|contato)\b/', $message_lower)) {
            $telefone = $context['empresa']['telefone'] ?: 'Não informado';
            return "📍 Entre em contato conosco:\n\n📞 Telefone: $telefone\n\n💬 Você pode continuar nossa conversa aqui mesmo pelo WhatsApp!\n\nComo posso ajudá-lo(a) mais?";
        }
        
        // Default
        return "Desculpe, não entendi completamente sua mensagem. 😅\n\nPosso ajudá-lo(a) com:\n• Informações sobre serviços\n• Agendamentos\n• Horário de funcionamento\n• Contato\n\nO que gostaria de saber?";
    }
    
    private function sendMessage($phone, $message, $company) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
            $config = $stmt->fetch();
            
            if (!$config) {
                error_log("WHATSAPP SEND ERROR: Configurações da API não encontradas no banco de dados");
                return false;
            }
            
            // Log das configurações (sem expor o token completo)
            $masked_token = substr($config['api_whatsapp_token'], 0, 8) . '***';
            error_log("WHATSAPP SEND: Tentando enviar mensagem para $phone via {$config['api_whatsapp_url']} com token $masked_token");
            error_log("WHATSAPP SEND: Instância: {$company['whatsapp_instance']}");
            error_log("WHATSAPP SEND: Mensagem: " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''));
            
            $data = [
                'number' => $phone,
                'text' => $message
            ];
            
            $api_url = rtrim($config['api_whatsapp_url'], '/') . '/message/sendText/' . $company['whatsapp_instance'];
            error_log("WHATSAPP SEND: URL completa: $api_url");
            error_log("WHATSAPP SEND: Dados enviados: " . json_encode($data));
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $config['api_whatsapp_token']
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log detalhado da resposta
            error_log("WHATSAPP SEND: HTTP Code: $httpCode");
            if ($curlError) {
                error_log("WHATSAPP SEND: cURL Error: $curlError");
            }
            error_log("WHATSAPP SEND: Resposta da API: $response");
            
            if ($httpCode === 200 || $httpCode === 201) {
                error_log("WHATSAPP SEND: ✅ Mensagem enviada com sucesso para $phone");
                return true;
            } else {
                error_log("WHATSAPP SEND: ❌ Falha no envio - HTTP $httpCode: $response");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("WHATSAPP SEND: ❌ Exception: " . $e->getMessage());
            error_log("WHATSAPP SEND: Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    private function cleanPhone($phone) {
        // Remove caracteres não numéricos e padroniza formato
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Se começar com 55 (Brasil), manter
        if (substr($phone, 0, 2) === '55') {
            return $phone;
        }
        
        // Se começar com 0, remover
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        // Adicionar código do país se necessário
        if (strlen($phone) === 11) {
            return '55' . $phone;
        }
        
        return $phone;
    }
}
?>