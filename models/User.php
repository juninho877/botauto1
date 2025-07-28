<?php
require_once 'config/database.php';

class User {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }
    
    public function authenticate($email, $password, $type = 'company') {
        try {
            if ($type === 'admin') {
                $stmt = $this->pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND ativo = 1");
            } else {
                $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE email = ? AND ativo = 1");
            }
            
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['senha'])) {
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erro na autenticação: " . $e->getMessage());
            return false;
        }
    }
    
    public function createCompany($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO companies (nome, email, senha, telefone, horario_funcionamento) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $hashedPassword = password_hash($data['senha'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            return $stmt->execute([
                $data['nome'],
                $data['email'],
                $hashedPassword,
                $data['telefone'],
                $data['horario_funcionamento'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Erro ao criar empresa: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCompanies($active_only = true) {
        try {
            $sql = "SELECT id, nome, email, telefone, whatsapp_connected, ativo, created_at FROM companies";
            if ($active_only) {
                $sql .= " WHERE ativo = 1";
            }
            $sql .= " ORDER BY nome";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erro ao buscar empresas: " . $e->getMessage());
            return [];
        }
    }
    
    public function getCompanyById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Erro ao buscar empresa: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateCompany($id, $data) {
        try {
            $sql = "UPDATE companies SET nome = ?, telefone = ?, horario_funcionamento = ?, ia_preferida = ?";
            $params = [$data['nome'], $data['telefone'], $data['horario_funcionamento'], $data['ia_preferida'], $id];
            
            if (!empty($data['senha'])) {
                $sql .= ", senha = ?";
                array_splice($params, -1, 0, [password_hash($data['senha'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])]);
            }
            
            $sql .= " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Erro ao atualizar empresa: " . $e->getMessage());
            return false;
        }
    }
    
    public function toggleCompanyStatus($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE companies SET ativo = NOT ativo WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Erro ao alterar status da empresa: " . $e->getMessage());
            return false;
        }
    }
}
?>