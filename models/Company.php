<?php
require_once 'config/database.php';

class Company {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }
    
    public function getDashboardStats($company_id) {
        try {
            $stats = [];
            
            // Total de agendamentos no mês
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total 
                FROM appointments 
                WHERE company_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE())
            ");
            $stmt->execute([$company_id]);
            $stats['agendamentos_mes'] = $stmt->fetch()['total'];
            
            // Agendamentos por status
            $stmt = $this->pdo->prepare("
                SELECT status, COUNT(*) as total 
                FROM appointments 
                WHERE company_id = ? AND DATE(data_agendamento) >= CURRENT_DATE()
                GROUP BY status
            ");
            $stmt->execute([$company_id]);
            $stats['por_status'] = $stmt->fetchAll();
            
            // Receita estimada do mês
            $stmt = $this->pdo->prepare("
                SELECT SUM(s.preco) as receita 
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                WHERE a.company_id = ? AND MONTH(a.created_at) = MONTH(CURRENT_DATE())
                AND a.status != 'cancelado'
            ");
            $stmt->execute([$company_id]);
            $stats['receita_mes'] = $stmt->fetch()['receita'] ?? 0;
            
            // Serviços mais solicitados
            $stmt = $this->pdo->prepare("
                SELECT s.nome, COUNT(a.id) as total 
                FROM services s 
                LEFT JOIN appointments a ON s.id = a.service_id 
                WHERE s.company_id = ? AND MONTH(a.created_at) = MONTH(CURRENT_DATE())
                GROUP BY s.id, s.nome 
                ORDER BY total DESC 
                LIMIT 5
            ");
            $stmt->execute([$company_id]);
            $stats['servicos_populares'] = $stmt->fetchAll();
            
            return $stats;
        } catch (Exception $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAppointments($company_id, $limit = null, $status = null) {
        try {
            $sql = "
                SELECT a.*, s.nome as servico_nome, s.duracao_minutos, s.preco 
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                WHERE a.company_id = ?
            ";
            $params = [$company_id];
            
            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY a.data_agendamento DESC, a.hora_inicio DESC";
            
            if ($limit) {
                $sql .= " LIMIT ?";
                $params[] = $limit;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar agendamentos: " . $e->getMessage());
            return [];
        }
    }
    
    public function getConversations($company_id, $limit = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, COUNT(m.id) as total_mensagens,
                       MAX(m.created_at) as ultima_mensagem_data
                FROM whatsapp_conversations c
                LEFT JOIN whatsapp_messages m ON c.id = m.conversation_id
                WHERE c.company_id = ?
                GROUP BY c.id
                ORDER BY ultima_mensagem_data DESC
                LIMIT ?
            ");
            $stmt->execute([$company_id, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar conversas: " . $e->getMessage());
            return [];
        }
    }
    
    public function getMessages($conversation_id, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM whatsapp_messages 
                WHERE conversation_id = ? 
                ORDER BY created_at ASC 
                LIMIT ?
            ");
            $stmt->execute([$conversation_id, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar mensagens: " . $e->getMessage());
            return [];
        }
    }
}
?>