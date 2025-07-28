-- Sistema SaaS WhatsApp com Agendamento Inteligente
-- Criação das tabelas do banco de dados

-- Configurações globais do administrador master
CREATE TABLE admin_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_whatsapp_url VARCHAR(255) NOT NULL,
    api_whatsapp_token VARCHAR(255) NOT NULL,
    openai_key VARCHAR(255),
    gemini_key VARCHAR(255),
    ia_padrao ENUM('chatgpt', 'gemini') DEFAULT 'chatgpt',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Usuários administradores master
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Empresas (tenants)
CREATE TABLE companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    whatsapp_instance VARCHAR(50),
    whatsapp_connected BOOLEAN DEFAULT FALSE,
    horario_funcionamento JSON,
    ia_preferida ENUM('chatgpt', 'gemini', 'padrao') DEFAULT 'padrao',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Usuários das empresas
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'atendente') DEFAULT 'atendente',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_company (email, company_id)
);

-- Serviços oferecidos pelas empresas
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    duracao_minutos INT NOT NULL,
    preco DECIMAL(10,2) DEFAULT 0.00,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Agendamentos
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    cliente_nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    service_id INT NOT NULL,
    data_agendamento DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    status ENUM('agendado', 'confirmado', 'cancelado', 'concluido') DEFAULT 'agendado',
    observacoes TEXT,
    lembrete_enviado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_company_date (company_id, data_agendamento),
    INDEX idx_telefone (telefone)
);

-- Conversas do WhatsApp
CREATE TABLE whatsapp_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    nome_cliente VARCHAR(100),
    ultima_mensagem TEXT,
    ultima_interacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativo', 'finalizado') DEFAULT 'ativo',
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_telefone (company_id, telefone)
);

-- Mensagens do WhatsApp
CREATE TABLE whatsapp_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    company_id INT NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    tipo ENUM('recebida', 'enviada') NOT NULL,
    conteudo TEXT NOT NULL,
    processado_ia BOOLEAN DEFAULT FALSE,
    resposta_ia TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES whatsapp_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_data (company_id, created_at),
    INDEX idx_telefone_data (telefone, created_at)
);

-- Logs do sistema
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    detalhes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    INDEX idx_company_data (company_id, created_at)
);

-- Inserir dados iniciais
INSERT INTO admin_config (api_whatsapp_url, api_whatsapp_token, ia_padrao) 
VALUES ('http://localhost:8080', 'seu_token_aqui', 'chatgpt');

INSERT INTO admin_users (nome, email, senha) 
VALUES ('Administrador Master', 'admin@sistema.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Dados de exemplo para demonstração
INSERT INTO companies (nome, email, senha, telefone, whatsapp_instance, horario_funcionamento) VALUES 
('Salão de Beleza Glamour', 'salao@glamour.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11999999999', 'glamour_instance', 
'{"segunda": {"inicio": "08:00", "fim": "18:00"}, "terca": {"inicio": "08:00", "fim": "18:00"}, "quarta": {"inicio": "08:00", "fim": "18:00"}, "quinta": {"inicio": "08:00", "fim": "18:00"}, "sexta": {"inicio": "08:00", "fim": "20:00"}, "sabado": {"inicio": "08:00", "fim": "16:00"}, "domingo": {"fechado": true}}');

INSERT INTO services (company_id, nome, descricao, duracao_minutos, preco) VALUES 
(1, 'Corte Feminino', 'Corte de cabelo feminino com lavagem', 60, 50.00),
(1, 'Escova', 'Escova modeladora', 45, 35.00),
(1, 'Manicure', 'Cuidados com as unhas das mãos', 30, 25.00),
(1, 'Pedicure', 'Cuidados com as unhas dos pés', 45, 30.00);