<?php
require_once __DIR__ . '/../config/database.php';

class Appointment {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }
    
    public function create($data) {
        try {
            error_log("APPOINTMENT CREATE: Tentando criar agendamento com dados: " . json_encode($data));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO appointments (company_id, cliente_nome, telefone, service_id, 
                                        data_agendamento, hora_inicio, hora_fim, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['company_id'],
                $data['cliente_nome'],
                $data['telefone'],
                $data['service_id'],
                $data['data_agendamento'],
                $data['hora_inicio'],
                $data['hora_fim'],
                $data['observacoes'] ?? ''
            ]);
            
            if ($result) {
                $appointment_id = $this->pdo->lastInsertId();
                error_log("APPOINTMENT CREATE: ✅ Agendamento criado com sucesso! ID: $appointment_id");
                error_log("APPOINTMENT CREATE: Cliente: {$data['cliente_nome']}, Telefone: {$data['telefone']}, Data: {$data['data_agendamento']}, Hora: {$data['hora_inicio']}");
                return true;
            } else {
                error_log("APPOINTMENT CREATE: ❌ Falha na execução da query");
                error_log("APPOINTMENT CREATE: Erro PDO: " . json_encode($stmt->errorInfo()));
                return false;
            }
        } catch (Exception $e) {
            error_log("APPOINTMENT CREATE: ❌ Exception: " . $e->getMessage());
            error_log("APPOINTMENT CREATE: Dados que causaram erro: " . json_encode($data));
            return false;
        }
    }
    
    public function checkAvailability($company_id, $data, $hora_inicio, $hora_fim, $exclude_id = null) {
        try {
            $sql = "
                SELECT COUNT(*) as conflitos 
                FROM appointments 
                WHERE company_id = ? 
                AND data_agendamento = ? 
                AND status != 'cancelado'
                AND (
                    (hora_inicio <= ? AND hora_fim > ?) OR
                    (hora_inicio < ? AND hora_fim >= ?) OR
                    (hora_inicio >= ? AND hora_fim <= ?)
                )
            ";
            $params = [$company_id, $data, $hora_inicio, $hora_inicio, $hora_fim, $hora_fim, $hora_inicio, $hora_fim];
            
            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch()['conflitos'] == 0;
        } catch (Exception $e) {
            error_log("Erro ao verificar disponibilidade: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAvailableSlots($company_id, $service_id, $date) {
        try {
            // Buscar informações do serviço
            $serviceStmt = $this->pdo->prepare("SELECT duracao_minutos FROM services WHERE id = ?");
            $serviceStmt->execute([$service_id]);
            $service = $serviceStmt->fetch();
            
            if (!$service) {
                return [];
            }
            
            // Buscar horário de funcionamento da empresa
            $companyStmt = $this->pdo->prepare("SELECT horario_funcionamento FROM companies WHERE id = ?");
            $companyStmt->execute([$company_id]);
            $company = $companyStmt->fetch();
            
            if (!$company) {
                return [];
            }
            
            $horarios = json_decode($company['horario_funcionamento'], true);
            $dayOfWeek = strtolower(date('l', strtotime($date)));
            $dayOfWeekPt = [
                'monday' => 'segunda',
                'tuesday' => 'terca',
                'wednesday' => 'quarta',
                'thursday' => 'quinta',
                'friday' => 'sexta',
                'saturday' => 'sabado',
                'sunday' => 'domingo'
            ];
            
            $dayName = $dayOfWeekPt[$dayOfWeek];
            
            if (!isset($horarios[$dayName]) || isset($horarios[$dayName]['fechado'])) {
                return [];
            }
            
            $inicio = $horarios[$dayName]['inicio'];
            $fim = $horarios[$dayName]['fim'];
            
            // Buscar agendamentos existentes
            $stmt = $this->pdo->prepare("
                SELECT hora_inicio, hora_fim 
                FROM appointments 
                WHERE company_id = ? AND data_agendamento = ? AND status != 'cancelado'
                ORDER BY hora_inicio
            ");
            $stmt->execute([$company_id, $date]);
            $agendamentos = $stmt->fetchAll();
            
            // Gerar slots disponíveis
            $slots = [];
            $currentTime = new DateTime($date . ' ' . $inicio);
            $endTime = new DateTime($date . ' ' . $fim);
            $duration = new DateInterval('PT' . $service['duracao_minutos'] . 'M');
            
            while ($currentTime < $endTime) {
                $slotEnd = clone $currentTime;
                $slotEnd->add($duration);
                
                if ($slotEnd <= $endTime) {
                    $available = true;
                    
                    // Verificar conflitos
                    foreach ($agendamentos as $agendamento) {
                        $agendInicio = new DateTime($date . ' ' . $agendamento['hora_inicio']);
                        $agendFim = new DateTime($date . ' ' . $agendamento['hora_fim']);
                        
                        if (($currentTime < $agendFim) && ($slotEnd > $agendInicio)) {
                            $available = false;
                            break;
                        }
                    }
                    
                    if ($available) {
                        $slots[] = $currentTime->format('H:i');
                    }
                }
                
                $currentTime->add(new DateInterval('PT30M')); // Intervalos de 30min
            }
            
            return $slots;
        } catch (Exception $e) {
            error_log("Erro ao calcular slots disponíveis: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($id, $status, $company_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE appointments 
                SET status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND company_id = ?
            ");
            
            return $stmt->execute([$status, $id, $company_id]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar status do agendamento: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByPhone($company_id, $telefone, $limit = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, s.nome as servico_nome 
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                WHERE a.company_id = ? AND a.telefone = ? 
                ORDER BY a.data_agendamento DESC, a.hora_inicio DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$company_id, $telefone, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar agendamentos por telefone: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPendingReminders() {
        try {
            // Lembretes para amanhã (confirmação)
            $stmt = $this->pdo->prepare("
                SELECT a.*, c.telefone as company_phone, c.nome as company_name,
                       s.nome as servico_nome
                FROM appointments a
                JOIN companies c ON a.company_id = c.id
                JOIN services s ON a.service_id = s.id
                WHERE a.data_agendamento = DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY)
                AND a.status = 'agendado'
                AND a.lembrete_enviado = 0
                AND c.ativo = 1
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar agendamentos para lembrete: " . $e->getMessage());
            return [];
        }
    }
    
    public function markReminderSent($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE appointments SET lembrete_enviado = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Erro ao marcar lembrete como enviado: " . $e->getMessage());
            return false;
        }
    }
}
?>