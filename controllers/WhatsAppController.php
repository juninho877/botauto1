<?php
require_once 'config/config.php';
require_once 'models/Appointment.php';
require_once 'models/Service.php';

class WhatsAppController {
    private $appointmentModel;
    private $serviceModel;
    
    public function __construct() {
        $this->appointmentModel = new Appointment();
        $this->serviceModel = new Service();
    }
    
    public function processWebhook() {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                return;
            }
            
            // Log da mensagem recebida
            error_log("WhatsApp Webhook: " . $input);
            
            // Verificar se é uma mensagem de texto
            if (isset($data['data']['key']['fromMe']) && $data['data']['key']['fromMe'] === false) {
                $phone = $this->cleanPhone($data['data']['key']['remoteJid']);
                $message = $data['data']['message']['conversation'] ?? 
                          $data['data']['message']['extendedTextMessage']['text'] ?? '';
                
                if (!empty($message)) {
                    $this->processMessage($phone, $message);
                }
            }
            
            http_response_code(200);
            echo json_encode(['status' => 'ok']);
            
        } catch (Exception $e) {
            error_log("Erro no webhook WhatsApp: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
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
            // Buscar configurações de IA
            $db = new Database();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
            $config = $stmt->fetch();
            
            if (!$config) {
                return "Desculpe, estou com problemas técnicos. Tente novamente mais tarde.";
            }
            
            // Determinar qual IA usar
            $ia_tipo = $company['ia_preferida'] === 'padrao' ? $config['ia_padrao'] : $company['ia_preferida'];
            
            // Contexto da empresa
            $services = $this->serviceModel->getByCompany($company['id']);
            $recent_appointments = $this->appointmentModel->getByPhone($company['id'], $phone, 3);
            
            $context = [
                'empresa' => $company['nome'],
                'telefone_empresa' => $company['telefone'],
                'servicos' => $services,
                'agendamentos_recentes' => $recent_appointments,
                'horario_funcionamento' => json_decode($company['horario_funcionamento'], true)
            ];
            
            if ($ia_tipo === 'chatgpt' && !empty($config['openai_key'])) {
                return $this->processWithChatGPT($message, $context, $config['openai_key']);
            } elseif ($ia_tipo === 'gemini' && !empty($config['gemini_key'])) {
                return $this->processWithGemini($message, $context, $config['gemini_key']);
            }
            
            // Fallback para resposta simples
            return $this->processWithSimpleBot($message, $context);
            
        } catch (Exception $e) {
            error_log("Erro no processamento de IA: " . $e->getMessage());
            return "Desculpe, estou com problemas técnicos. Tente novamente mais tarde.";
        }
    }
    
    private function processWithChatGPT($message, $context, $api_key) {
        $prompt = $this->buildPrompt($message, $context);
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um assistente de agendamento para ' . $context['empresa'] . '. Seja prestativo e profissional.'
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
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? 'Desculpe, não entendi. Pode reformular?';
        }
        
        return $this->processWithSimpleBot($message, $context);
    }
    
    private function processWithGemini($message, $context, $api_key) {
        $prompt = $this->buildPrompt($message, $context);
        
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
        curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Desculpe, não entendi. Pode reformular?';
        }
        
        return $this->processWithSimpleBot($message, $context);
    }
    
    private function processWithSimpleBot($message, $context) {
        $message_lower = strtolower($message);
        
        // Saudações
        if (preg_match('/\b(oi|olá|ola|bom dia|boa tarde|boa noite)\b/', $message_lower)) {
            return "Olá! Bem-vindo(a) ao {$context['empresa']}! 😊\n\nComo posso ajudá-lo(a) hoje? Posso:\n• Mostrar nossos serviços\n• Ajudar com agendamentos\n• Verificar disponibilidade\n\nO que gostaria de fazer?";
        }
        
        // Solicitar serviços
        if (preg_match('/\b(serviços|serviços|preços|precos|tabela|valores)\b/', $message_lower)) {
            $response = "📋 Nossos serviços:\n\n";
            foreach ($context['servicos'] as $service) {
                $response .= "• {$service['nome']}\n";
                $response .= "  ⏱️ {$service['duracao_minutos']} minutos\n";
                $response .= "  💰 R$ " . number_format($service['preco'], 2, ',', '.') . "\n\n";
            }
            $response .= "Gostaria de agendar algum serviço?";
            return $response;
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
            return "📍 Entre em contato conosco:\n\n📞 Telefone: {$context['telefone_empresa']}\n\n💬 Você pode continuar nossa conversa aqui mesmo pelo WhatsApp!\n\nComo posso ajudá-lo(a) mais?";
        }
        
        // Default
        return "Desculpe, não entendi completamente sua mensagem. 😅\n\nPosso ajudá-lo(a) com:\n• Informações sobre serviços\n• Agendamentos\n• Horário de funcionamento\n• Contato\n\nO que gostaria de saber?";
    }
    
    private function buildPrompt($message, $context) {
        $prompt = "Cliente disse: \"$message\"\n\n";
        $prompt .= "Contexto da empresa {$context['empresa']}:\n";
        $prompt .= "Serviços disponíveis:\n";
        
        foreach ($context['servicos'] as $service) {
            $prompt .= "- {$service['nome']}: {$service['duracao_minutos']}min, R$ {$service['preco']}\n";
        }
        
        $prompt .= "\nResponda de forma natural e prestativa. Se for sobre agendamento, solicite detalhes específicos.";
        
        return $prompt;
    }
    
    private function sendMessage($phone, $message, $company) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->query("SELECT * FROM admin_config WHERE id = 1");
            $config = $stmt->fetch();
            
            if (!$config) {
                return false;
            }
            
            $data = [
                'number' => $phone,
                'text' => $message
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['api_whatsapp_url'] . '/message/sendText/' . $company['whatsapp_instance']);
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
            
            return $httpCode === 200;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar mensagem WhatsApp: " . $e->getMessage());
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