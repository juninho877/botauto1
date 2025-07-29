<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Service.php';

class AIController {
    private $appointmentModel;
    private $serviceModel;
    
    public function __construct() {
        $this->appointmentModel = new Appointment();
        $this->serviceModel = new Service();
    }
    
    public function analyzeIntent($message, $context) {
        // Análise básica de intenção usando regex
        $message_lower = strtolower($message);
        $intent = [];
        
        // Detectar intenção de agendamento
        if (preg_match('/\b(agendar|marcar|quero|preciso|gostaria).*\b(horário|horario|consulta|serviço|servico)\b/', $message_lower)) {
            $intent['action'] = 'schedule';
            
            // Extrair serviço mencionado
            foreach ($context['servicos'] as $service) {
                $service_name = strtolower($service['nome']);
                if (strpos($message_lower, $service_name) !== false) {
                    $intent['service_id'] = $service['id'];
                    $intent['service_name'] = $service['nome'];
                    break;
                }
            }
            
            // Extrair data
            $intent['date'] = $this->extractDate($message);
            
            // Extrair horário
            $intent['time'] = $this->extractTime($message);
        }
        
        // Detectar intenção de cancelamento
        if (preg_match('/\b(cancelar|desmarcar|remover)\b/', $message_lower)) {
            $intent['action'] = 'cancel';
        }
        
        // Detectar consulta de disponibilidade
        if (preg_match('/\b(disponível|disponivel|tem vaga|tem horário|tem horario)\b/', $message_lower)) {
            $intent['action'] = 'check_availability';
            $intent['date'] = $this->extractDate($message);
            $intent['time'] = $this->extractTime($message);
        }
        
        // Detectar solicitação de informações
        if (preg_match('/\b(preço|preco|quanto custa|valor|serviços|servicos)\b/', $message_lower)) {
            $intent['action'] = 'info';
            $intent['type'] = 'services';
        }
        
        return $intent;
    }
    
    public function buildPrompt($message, $context) {
    public function processScheduleRequest($message, $context, $phone, $flow_data = []) {
        try {
            error_log("WHATSAPP AI FLOW: Processando passo - " . json_encode($flow_data));
            
            $step = $flow_data['step'] ?? 'start';
            $schedule_data = $flow_data['schedule_data'] ?? [];
            
            switch ($step) {
                case 'start':
                case 'waiting_service':
                    // Primeiro passo: escolher serviço
                    if ($step === 'start') {
                        $response_message = "Qual serviço você gostaria de agendar?\n\n";
                        foreach ($context['servicos'] as $index => $service) {
                            $response_message .= ($index + 1) . ". {$service['nome']} ({$service['duracao_minutos']}min - R$ " . number_format($service['preco'], 2, ',', '.') . ")\n";
                        }
                        $response_message .= "\nDigite o número ou nome do serviço:";
                        
                        return [
                            'message' => $response_message,
                            'status' => 'waiting_service',
                            'flow_data' => [
                                'in_flow' => true,
                                'flow_type' => 'schedule',
                                'step' => 'waiting_service',
                                'schedule_data' => $schedule_data
                            ]
                        ];
                    } else {
                        // Processar escolha do serviço
                        $selectedService = $this->findServiceFromMessage($message, $context['servicos']);
                        
                        if (!$selectedService) {
                            return [
                                'message' => "Não encontrei esse serviço. Por favor, escolha um dos serviços listados ou digite o número correspondente.",
                                'status' => 'waiting_service',
                                'flow_data' => $flow_data
                            ];
                        }
                        
                        $schedule_data['service_id'] = $selectedService['id'];
                        $schedule_data['service_name'] = $selectedService['nome'];
                        $schedule_data['service_duration'] = $selectedService['duracao_minutos'];
                        $schedule_data['service_price'] = $selectedService['preco'];
                        
                        return [
                            'message' => "Perfeito! Você escolheu *{$selectedService['nome']}* ({$selectedService['duracao_minutos']}min - R$ " . number_format($selectedService['preco'], 2, ',', '.') . ").\n\nPara qual data você gostaria de agendar? Pode ser hoje, amanhã, ou uma data específica (ex: 15/01).",
                            'status' => 'waiting_date',
                            'flow_data' => [
                                'in_flow' => true,
                                'flow_type' => 'schedule',
                                'step' => 'waiting_date',
                                'schedule_data' => $schedule_data
                            ]
                        ];
                    }
                    break;
                    
                case 'waiting_date':
                    // Processar data
                    $date = $this->extractDate($message);
                    
                    if (!$date) {
                        return [
                            'message' => "Não consegui entender a data. Pode me informar de forma mais clara? Por exemplo: 'amanhã', 'sexta-feira' ou '15/01'.",
                            'status' => 'waiting_date',
                            'flow_data' => $flow_data
                        ];
                    }
                    
                    $schedule_data['date'] = $date;
                    
                    // Verificar horários disponíveis
                    $available_slots = $this->appointmentModel->getAvailableSlots(
                        $context['empresa']['id'],
                        $schedule_data['service_id'],
                        $date
                    );
                    
                    if (empty($available_slots)) {
                        return [
                            'message' => "Infelizmente não temos horários disponíveis para " . date('d/m/Y', strtotime($date)) . ". Gostaria de escolher outra data?",
                            'status' => 'waiting_date',
                            'flow_data' => $flow_data
                        ];
                    }
                    
                    $response_message = "Horários disponíveis para " . date('d/m/Y', strtotime($date)) . ":\n\n";
                    foreach ($available_slots as $slot) {
                        $response_message .= "• $slot\n";
                    }
                    $response_message .= "\nQual horário prefere?";
                    
                    return [
                        'message' => $response_message,
                        'status' => 'waiting_time',
                        'flow_data' => [
                            'in_flow' => true,
                            'flow_type' => 'schedule',
                            'step' => 'waiting_time',
                            'schedule_data' => $schedule_data
                        ]
                    ];
                    break;
                    
                case 'waiting_time':
                    // Processar horário
                    $time = $this->extractTime($message);
                    
                    if (!$time) {
                        return [
                            'message' => "Não consegui entender o horário. Pode escolher um dos horários disponíveis listados acima?",
                            'status' => 'waiting_time',
                            'flow_data' => $flow_data
                        ];
                    }
                    
                    // Verificar se o horário ainda está disponível
                    $end_time = date('H:i', strtotime($time . ' +' . $schedule_data['service_duration'] . ' minutes'));
                    
                    if (!$this->appointmentModel->checkAvailability($context['empresa']['id'], $schedule_data['date'], $time, $end_time)) {
                        return [
                            'message' => "Desculpe, esse horário não está mais disponível. Gostaria de escolher outro?",
                            'status' => 'waiting_time',
                            'flow_data' => $flow_data
                        ];
                    }
                    
                    $schedule_data['time'] = $time;
                    
                    return [
                        'message' => "Ótimo! Seu agendamento será:\n\n📅 Data: " . date('d/m/Y', strtotime($schedule_data['date'])) . "\n🕒 Horário: $time\n💼 Serviço: {$schedule_data['service_name']}\n💰 Valor: R$ " . number_format($schedule_data['service_price'], 2, ',', '.') . "\n\nQual é o seu nome para confirmarmos o agendamento?",
                        'status' => 'waiting_name',
                        'flow_data' => [
                            'in_flow' => true,
                            'flow_type' => 'schedule',
                            'step' => 'waiting_name',
                            'schedule_data' => $schedule_data
                        ]
                    ];
                    break;
                    
                case 'waiting_name':
                    // Processar nome
                    $name = trim($message);
                    
                    if (strlen($name) < 2) {
                        return [
                            'message' => "Por favor, me informe seu nome completo para confirmar o agendamento.",
                            'status' => 'waiting_name',
                            'flow_data' => $flow_data
                        ];
                    }
                    
                    $schedule_data['client_name'] = $name;
                    
                    return [
                        'message' => 'Agendamento pronto para finalização',
                        'status' => 'completed',
                        'flow_data' => [
                            'in_flow' => false,
                            'flow_type' => 'schedule',
                            'step' => 'completed',
                            'schedule_data' => $schedule_data
                        ]
                    ];
                    break;
            }
            
            return [
                'message' => 'Desculpe, houve um problema no fluxo. Vamos começar novamente?',
                'status' => 'error',
                'flow_data' => ['in_flow' => false, 'flow_type' => null, 'step' => null, 'schedule_data' => []]
            ];
            
        } catch (Exception $e) {
            error_log("Erro no fluxo de agendamento: " . $e->getMessage());
            return [
                'message' => 'Desculpe, ocorreu um erro interno. Tente novamente mais tarde.',
                'status' => 'error',
                'flow_data' => ['in_flow' => false, 'flow_type' => null, 'step' => null, 'schedule_data' => []]
            ];
        }
    }
    
    public function finalizeAppointment($company_id, $client_name, $phone, $service_id, $date, $time, $service_duration, $service_price) {
        try {
            error_log("WHATSAPP AI: Finalizando agendamento - Cliente: $client_name, Telefone: $phone");
            
            $end_time = date('H:i', strtotime($time . ' +' . $service_duration . ' minutes'));
            
            // Verificação final de disponibilidade
            if (!$this->appointmentModel->checkAvailability($company_id, $date, $time, $end_time)) {
                error_log("WHATSAPP AI: Horário não disponível na verificação final");
                return false;
            }
            
            // Criar agendamento
            $appointment_data = [
                'company_id' => $company_id,
                'cliente_nome' => $client_name,
                'telefone' => $phone,
                'service_id' => $service_id,
                'data_agendamento' => $date,
                'hora_inicio' => $time,
                'hora_fim' => $end_time
            ];
            
            $success = $this->appointmentModel->create($appointment_data);
            
            if ($success) {
                error_log("WHATSAPP AI: ✅ Agendamento criado com sucesso para $client_name");
            } else {
                error_log("WHATSAPP AI: ❌ Falha ao criar agendamento para $client_name");
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Erro ao finalizar agendamento: " . $e->getMessage());
            return false;
        }
    }
    
    private function findServiceFromMessage($message, $services) {
        $message_lower = strtolower($message);
        
        // Tentar encontrar por número
        if (preg_match('/(\d+)/', $message, $matches)) {
            $number = (int)$matches[1];
            if ($number > 0 && $number <= count($services)) {
                return $services[$number - 1];
            }
        }
        
        // Tentar encontrar por nome
        foreach ($services as $service) {
            $service_name = strtolower($service['nome']);
            if (strpos($message_lower, $service_name) !== false) {
                return $service;
            }
            
            // Verificar palavras-chave do serviço
            $keywords = explode(' ', $service_name);
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 3 && strpos($message_lower, $keyword) !== false) {
                    return $service;
                }
            }
        }
        
        return null;
    }
    
    public function buildPrompt($message, $context) {
        $empresa = $context['empresa'];
        $servicos = $context['servicos'];
        $agendamentos_recentes = $context['agendamentos_recentes'];
        $horario_funcionamento = $context['horario_funcionamento'];
        $conversa_anterior = $context['conversa_anterior'] ?? [];
        
        $prompt = "Você é o assistente virtual da empresa '{$empresa['nome']}'.\n\n";
        
        $prompt .= "INFORMAÇÕES DA EMPRESA:\n";
        $prompt .= "- Nome: {$empresa['nome']}\n";
        if ($empresa['telefone']) {
            $prompt .= "- Telefone: {$empresa['telefone']}\n";
        }
        
        $prompt .= "\nSERVIÇOS DISPONÍVEIS:\n";
        foreach ($servicos as $service) {
            $prompt .= "- {$service['nome']}: {$service['duracao_minutos']} minutos, R$ " . number_format($service['preco'], 2, ',', '.') . "\n";
            if ($service['descricao']) {
                $prompt .= "  Descrição: {$service['descricao']}\n";
            }
        }
        
        $prompt .= "\nHORÁRIO DE FUNCIONAMENTO:\n";
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
            if (isset($horario_funcionamento[$key])) {
                if (isset($horario_funcionamento[$key]['fechado'])) {
                    $prompt .= "- $dia: Fechado\n";
                } else {
                    $prompt .= "- $dia: {$horario_funcionamento[$key]['inicio']} às {$horario_funcionamento[$key]['fim']}\n";
                }
            }
        }
        
        if (!empty($agendamentos_recentes)) {
            $prompt .= "\nAGENDAMENTOS RECENTES DO CLIENTE:\n";
            foreach ($agendamentos_recentes as $appointment) {
                $data = date('d/m/Y', strtotime($appointment['data_agendamento']));
                $hora = substr($appointment['hora_inicio'], 0, 5);
                $prompt .= "- {$appointment['servico_nome']} em $data às $hora (Status: {$appointment['status']})\n";
            }
        }
        
        if (!empty($conversa_anterior)) {
            $prompt .= "\nCONTEXTO DA CONVERSA ANTERIOR:\n";
            foreach ($conversa_anterior as $msg) {
                $tipo = $msg['tipo'] === 'recebida' ? 'Cliente' : 'Assistente';
                $prompt .= "- $tipo: {$msg['conteudo']}\n";
            }
        }
        
        $prompt .= "\nMENSAGEM DO CLIENTE: \"$message\"\n\n";
        
        $prompt .= "INSTRUÇÕES:\n";
        $prompt .= "1. Responda de forma natural, amigável e profissional\n";
        $prompt .= "2. Use emojis quando apropriado para tornar a conversa mais calorosa\n";
        $prompt .= "3. Considere o contexto da conversa anterior para dar continuidade natural\n";
        $prompt .= "4. Se o cliente quiser agendar, siga um fluxo estruturado: serviço → data → horário → nome\n";
        $prompt .= "5. Se não tiver todas as informações para agendamento, pergunte uma coisa por vez\n";
        $prompt .= "6. Seja prestativo e tente resolver a necessidade do cliente\n";
        $prompt .= "7. Mantenha as respostas concisas mas informativas\n";
        $prompt .= "8. Para agendamentos, sempre confirme os detalhes antes de finalizar\n";
        $prompt .= "9. Se o cliente já foi saudado recentemente, evite repetir saudações completas e vá direto ao ponto ou faça uma pergunta de acompanhamento\n";
        $prompt .= "10. Mantenha um tom natural e evite repetições desnecessárias\n\n";
        
        $prompt .= "Responda agora à mensagem do cliente:";
        
        return $prompt;
    }
    
    private function extractDate($message) {
        $today = new DateTime();
        $message_lower = strtolower($message);
        
        // Hoje, amanhã, depois de amanhã
        if (strpos($message_lower, 'hoje') !== false) {
            return $today->format('Y-m-d');
        }
        
        if (strpos($message_lower, 'amanhã') !== false || strpos($message_lower, 'amanha') !== false) {
            return $today->modify('+1 day')->format('Y-m-d');
        }
        
        if (strpos($message_lower, 'depois de amanhã') !== false || strpos($message_lower, 'depois de amanha') !== false) {
            return $today->modify('+2 days')->format('Y-m-d');
        }
        
        // Dias da semana
        $days = [
            'segunda' => 'next monday',
            'terça' => 'next tuesday',
            'terca' => 'next tuesday',
            'quarta' => 'next wednesday',
            'quinta' => 'next thursday',
            'sexta' => 'next friday',
            'sábado' => 'next saturday',
            'sabado' => 'next saturday',
            'domingo' => 'next sunday'
        ];
        
        foreach ($days as $day_pt => $day_en) {
            if (strpos($message_lower, $day_pt) !== false) {
                $date = new DateTime($day_en);
                // Se o dia já passou esta semana, pegar da próxima
                if ($date <= $today) {
                    $date->modify('+1 week');
                }
                return $date->format('Y-m-d');
            }
        }
        
        // Formato DD/MM ou DD/MM/YYYY
        if (preg_match('/(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/', $message, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = isset($matches[3]) ? $matches[3] : date('Y');
            
            if (strlen($year) == 2) {
                $year = '20' . $year;
            }
            
            $date = DateTime::createFromFormat('Y-m-d', "$year-$month-$day");
            if ($date && $date >= $today) {
                return $date->format('Y-m-d');
            }
        }
        
        return null;
    }
    
    private function extractTime($message) {
        // Formato HH:MM
        if (preg_match('/(\d{1,2}):(\d{2})/', $message, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minute = $matches[2];
            return "$hour:$minute";
        }
        
        // Formato HHh ou HH horas
        if (preg_match('/(\d{1,2})h(?:oras)?/', $message, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            return "$hour:00";
        }
        
        // Horários específicos mencionados (números simples)
        $message_lower = strtolower($message);
        $times = [
            '8' => '08:00', '9' => '09:00', '10' => '10:00', '11' => '11:00',
            '12' => '12:00', '13' => '13:00', '14' => '14:00', '15' => '15:00',
            '16' => '16:00', '17' => '17:00', '18' => '18:00', '19' => '19:00'
        ];
        
        foreach ($times as $hour => $time) {
            if (strpos($message, $hour) !== false) {
                return $time;
            }
        }
        
        // Horários por extenso
        $times_text = [
            'meio dia' => '12:00',
            'meio-dia' => '12:00',
            'manhã' => '09:00',
            'manha' => '09:00',
            'tarde' => '14:00',
            'noite' => '19:00'
        ];
        
        foreach ($times_text as $text => $time) {
            if (strpos($message_lower, $text) !== false) {
                return $time;
            }
        }
        
        return null;
    }
}
?>