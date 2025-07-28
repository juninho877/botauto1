# Sistema SaaS WhatsApp com Agendamento Inteligente

Um sistema completo de atendimento automatizado via WhatsApp para empresas, com integração de IA e agendamento inteligente.

## 🚀 Características Principais

- **Sistema Multi-tenant**: Múltiplas empresas em uma única instalação
- **Atendimento Automatizado**: Bot inteligente com IA (ChatGPT/Gemini)
- **Agendamento Inteligente**: Detecção automática de conflitos e sugestões
- **Dashboard Completo**: Métricas, gráficos e relatórios
- **API WhatsApp Evolution v2**: Integração completa e estável
- **Lembretes Automáticos**: Sistema de cron para notificações
- **Interface Responsiva**: Bootstrap 5 com design moderno

## 📋 Requisitos do Sistema

- PHP 8.0 ou superior
- MySQL 8.0 ou superior
- Apache/Nginx com mod_rewrite
- Extensões PHP: PDO, cURL, JSON, mbstring
- Servidor Evolution API v2 configurado

## 🛠️ Instalação

### 1. Clone e Configure o Projeto

```bash
# Clone o repositório
git clone [url-do-projeto]
cd sistema-whatsapp-saas

# Configure permissões
chmod 755 -R .
chmod 777 cron/
```

### 2. Configure o Banco de Dados

```bash
# Crie o banco de dados
mysql -u root -p
CREATE DATABASE whatsapp_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Execute o schema
mysql -u root -p whatsapp_saas < database/schema.sql
```

### 3. Configure o Apache/Nginx

**Apache (.htaccess já incluído)**
```apache
DocumentRoot /caminho/para/sistema-whatsapp-saas/public
```

**Nginx**
```nginx
server {
    root /caminho/para/sistema-whatsapp-saas/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 4. Configure o Evolution API v2

```bash
# Instale o Evolution API
docker run -d \
  --name evolution-api \
  -p 8080:8080 \
  -e DATABASE_ENABLED=true \
  -e DATABASE_CONNECTION_URI="mysql://user:password@host:3306/evolution_db" \
  atendai/evolution-api:v2.0.0

# Configure o webhook para: http://seudominio.com/webhook/whatsapp.php
```

### 5. Configure o Cron para Lembretes

```bash
# Adicione ao crontab
crontab -e

# Execute a cada hora
0 * * * * php /caminho/para/sistema/cron/reminders.php >> /var/log/whatsapp-reminders.log 2>&1
```

## 🔧 Configuração Inicial

### 1. Acesso Administrador

- **URL**: `http://seudominio.com/login`
- **Email**: `admin@sistema.com`
- **Senha**: `123456`
- **Tipo**: `Administrador`

### 2. Configurações do Sistema

No painel administrativo:

1. Acesse **Configurações**
2. Configure a **API WhatsApp Evolution**:
   - URL: `http://localhost:8080` (ou seu servidor)
   - Token: Obtido na instalação do Evolution
3. Configure as **Chaves de IA**:
   - OpenAI Key: `sk-...` (para ChatGPT)
   - Gemini Key: `AI...` (para Gemini)
4. Salve as configurações

### 3. Empresa de Demonstração

- **Email**: `salao@glamour.com`
- **Senha**: `123456`
- **Tipo**: `Empresa`

## 📱 Como Usar

### Para Administradores

1. **Dashboard**: Visão geral de todas as empresas
2. **Empresas**: Gerenciar contas de empresas
3. **Configurações**: APIs e chaves de integração
4. **Logs**: Auditoria completa do sistema

### Para Empresas

1. **Dashboard**: Métricas do negócio
2. **Serviços**: Cadastro de serviços oferecidos
3. **Agendamentos**: Visualizar e gerenciar agendamentos
4. **Calendário**: Vista mensal/semanal
5. **Conversas**: Histórico de mensagens WhatsApp
6. **WhatsApp**: Conectar instância

### Para Clientes (via WhatsApp)

Exemplos de mensagens que o bot entende:

```
- "Oi, quero agendar um corte"
- "Tem horário disponível amanhã às 15h?"
- "Preciso cancelar meu agendamento"
- "Quais são os preços?"
- "Qual o horário de funcionamento?"
```

## 🤖 Funcionalidades da IA

### Processamento Natural

O sistema entende linguagem natural e extrai:
- **Intenções**: agendar, cancelar, consultar
- **Serviços**: corte, escova, manicure, etc.
- **Datas**: hoje, amanhã, sexta, 15/12
- **Horários**: 15h, 9:30, manhã, tarde

### Respostas Inteligentes

- Sugestão de horários disponíveis
- Informações sobre serviços e preços
- Confirmação de agendamentos
- Lembretes automáticos

## 🔐 Segurança

- **Autenticação**: Bcrypt com custo 12
- **Sessões**: Timeout automático
- **CSRF**: Proteção contra ataques
- **SQL Injection**: Prepared statements
- **XSS**: Sanitização de dados
- **Headers**: Proteções HTTP

## 📊 Métricas e Relatórios

### Dashboard Executivo
- Agendamentos por período
- Receita estimada
- Serviços mais solicitados
- Status dos agendamentos

### Analytics
- Uso de IA por tipo
- Picos de atendimento
- Taxa de conversão
- Satisfação do cliente

## 🔄 API e Integrações

### Webhooks Suportados
- Evolution API v2 (WhatsApp)
- OpenAI API (ChatGPT)
- Google Gemini API

### Endpoints Internos
- `/webhook/whatsapp.php` - Receber mensagens
- `/api/calendar/events` - Eventos do calendário
- `/api/conversations/messages` - Histórico

## 🐛 Troubleshooting

### Problemas Comuns

**WhatsApp não conecta:**
```bash
# Verificar logs do Evolution API
docker logs evolution-api

# Testar conectividade
curl -X GET http://localhost:8080/instance/list
```

**IA não responde:**
```bash
# Verificar chaves da API
tail -f /var/log/apache2/error.log

# Testar conexão OpenAI
curl -H "Authorization: Bearer sk-..." https://api.openai.com/v1/models
```

**Agendamentos não aparecem:**
```bash
# Verificar banco de dados
mysql -u root -p whatsapp_saas
SELECT * FROM appointments ORDER BY created_at DESC LIMIT 5;
```

## 📈 Roadmap

- [ ] Múltiplas instâncias WhatsApp por empresa
- [ ] Relatórios avançados com PDF
- [ ] Integração com calendários externos
- [ ] App mobile para gestores
- [ ] API REST pública
- [ ] Marketplace de templates

## 📝 Licença

Este projeto é proprietário. Entre em contato para licenciamento comercial.

## 🆘 Suporte

- **Documentação**: `docs/`
- **Issues**: GitHub Issues
- **Email**: suporte@sistema.com
- **WhatsApp**: +55 11 99999-9999

---

**Desenvolvido com ❤️ para automatizar atendimentos e aumentar a produtividade dos negócios.**