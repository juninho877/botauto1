<?php
require_once __DIR__ . '/../config/database.php';

class Service {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }
    
    public function getByCompany($company_id, $active_only = true) {
        try {
            $sql = "SELECT * FROM services WHERE company_id = ?";
            $params = [$company_id];
            
            if ($active_only) {
                $sql .= " AND ativo = 1";
            }
            
            $sql .= " ORDER BY nome";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar serviços: " . $e->getMessage());
            return [];
        }
    }
    
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO services (company_id, nome, descricao, duracao_minutos, preco) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $data['company_id'],
                $data['nome'],
                $data['descricao'],
                $data['duracao_minutos'],
                $data['preco']
            ]);
        } catch (Exception $e) {
            error_log("Erro ao criar serviço: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE services 
                SET nome = ?, descricao = ?, duracao_minutos = ?, preco = ?
                WHERE id = ? AND company_id = ?
            ");
            
            return $stmt->execute([
                $data['nome'],
                $data['descricao'],
                $data['duracao_minutos'],
                $data['preco'],
                $id,
                $data['company_id']
            ]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar serviço: " . $e->getMessage());
            return false;
        }
    }
    
    public function toggleStatus($id, $company_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE services 
                SET ativo = NOT ativo 
                WHERE id = ? AND company_id = ?
            ");
            
            return $stmt->execute([$id, $company_id]);
        } catch (Exception $e) {
            error_log("Erro ao alterar status do serviço: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id, $company_id = null) {
        try {
            $sql = "SELECT * FROM services WHERE id = ?";
            $params = [$id];
            
            if ($company_id) {
                $sql .= " AND company_id = ?";
                $params[] = $company_id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao buscar serviço: " . $e->getMessage());
            return false;
        }
    }
}
?>