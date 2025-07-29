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
        $prompt .= "8. Para agendamentos, sempre confirme os detalhes antes de finalizar\n\n";
        
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
        
        // Horários por extenso
        $times = [
            'meio dia' => '12:00',
            'meio-dia' => '12:00',
            'manhã' => '09:00',
            'manha' => '09:00',
            'tarde' => '14:00',
            'noite' => '19:00'
        ];
        
        $message_lower = strtolower($message);
        foreach ($times as $text => $time) {
            if (strpos($message_lower, $text) !== false) {
                return $time;
            }
        }
        
        return null;
    }
    
    public function processScheduleRequest($intent, $context, $phone) {
        try {
            $response = [];
            
            // Verificar se temos todas as informações necessárias
            if (!isset($intent['service_id'])) {
                $response['message'] = "Qual serviço você gostaria de agendar?\n\n";
                foreach ($context['servicos'] as $service) {
                    $response['message'] .= "• {$service['nome']} ({$service['duracao_minutos']}min - R$ {$service['preco']})\n";
                }
                $response['status'] = 'need_service';
                return $response;
            }
            
            if (!isset($intent['date'])) {
                $response['message'] = "Para qual data você gostaria de agendar o {$intent['service_name']}?";
                $response['status'] = 'need_date';
                return $response;
            }
            
            if (!isset($intent['time'])) {
                // Mostrar horários disponíveis
                $available_slots = $this->appointmentModel->getAvailableSlots(
                    $context['empresa']['id'],
                    $intent['service_id'], 
                    $intent['date']
                );
                
                if (empty($available_slots)) {
                    $response['message'] = "Infelizmente não temos horários disponíveis para {$intent['date']}. Gostaria de escolher outra data?";
                    $response['status'] = 'no_availability';
                    return $response;
                }
                
                $response['message'] = "Horários disponíveis para {$intent['date']}:\n\n";
                foreach ($available_slots as $slot) {
                    $response['message'] .= "• $slot\n";
                }
                $response['message'] .= "\nQual horário prefere?";
                $response['status'] = 'need_time';
                return $response;
            }
            
            // Temos todas as informações, criar agendamento
            $service = $this->serviceModel->getById($intent['service_id']);
            $start_time = $intent['time'];
            $end_time = date('H:i', strtotime($start_time . ' +' . $service['duracao_minutos'] . ' minutes'));
            
            // Verificar disponibilidade final
            if (!$this->appointmentModel->checkAvailability($context['empresa']['id'], $intent['date'], $start_time, $end_time)) {
                $response['message'] = "Desculpe, esse horário não está mais disponível. Gostaria de escolher outro?";
                $response['status'] = 'conflict';
                return $response;
            }
            
            // Criar agendamento
            $appointment_data = [
                'company_id' => $context['empresa']['id'],
                'cliente_nome' => 'Cliente WhatsApp', // Será atualizado depois
                'telefone' => $phone,
                'service_id' => $intent['service_id'],
                'data_agendamento' => $intent['date'],
                'hora_inicio' => $start_time,
                'hora_fim' => $end_time
            ];
            
            if ($this->appointmentModel->create($appointment_data)) {
                $response['message'] = "✅ Agendamento confirmado!\n\n";
                $response['message'] .= "📅 Data: " . date('d/m/Y', strtotime($intent['date'])) . "\n";
                $response['message'] .= "🕒 Horário: $start_time às $end_time\n";
                $response['message'] .= "💼 Serviço: {$intent['service_name']}\n";
                $response['message'] .= "💰 Valor: R$ {$service['preco']}\n\n";
                $response['message'] .= "Qual é o seu nome para confirmarmos o agendamento?";
                $response['status'] = 'scheduled';
                
                auditLog('whatsapp_appointment_created', "Agendamento criado via WhatsApp para $phone", $context['empresa']['id']);
            } else {
                $response['message'] = "Ocorreu um erro ao criar seu agendamento. Tente novamente ou entre em contato conosco.";
                $response['status'] = 'error';
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Erro ao processar agendamento via IA: " . $e->getMessage());
            return [
                'message' => 'Desculpe, ocorreu um erro interno. Tente novamente mais tarde.',
                'status' => 'error'
            ];
        }
    }
}
?>