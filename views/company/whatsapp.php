<?php 
$title = 'WhatsApp - ' . $_SESSION['user_name'];
include BASE_PATH . '/views/layouts/header.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-0">
                        <i class="bi bi-building me-2"></i>
                        <?= htmlspecialchars(substr($_SESSION['user_name'], 0, 15)) ?>
                    </h5>
                    <small class="text-white-50">Painel da Empresa</small>
                </div>
                
                <nav class="nav flex-column px-3">
                    <a class="nav-link" href="/company/dashboard">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                    <a class="nav-link" href="/company/services">
                        <i class="bi bi-list-check"></i>
                        Serviços
                    </a>
                    <a class="nav-link" href="/company/appointments">
                        <i class="bi bi-calendar-check"></i>
                        Agendamentos
                    </a>
                    <a class="nav-link" href="/company/calendar">
                        <i class="bi bi-calendar3"></i>
                        Calendário
                    </a>
                    <a class="nav-link" href="/company/conversations">
                        <i class="bi bi-chat-dots"></i>
                        Conversas
                    </a>
                    <a class="nav-link active" href="/company/whatsapp">
                        <i class="bi bi-whatsapp"></i>
                        WhatsApp
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="/logout">
                        <i class="bi bi-box-arrow-right"></i>
                        Sair
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <button class="btn btn-outline-primary d-md-none me-3" onclick="toggleSidebar()">
                            <i class="bi bi-list"></i>
                        </button>
                        <h1 class="h3 mb-0">Integração WhatsApp</h1>
                        <p class="text-muted mb-0">Configure e gerencie sua conexão com o WhatsApp</p>
                    </div>
                </div>

                <!-- Connection Status -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <div id="connectionStatus">
                                    <i class="bi bi-whatsapp text-warning display-1 mb-3"></i>
                                    <h5>Status da Conexão</h5>
                                    <span class="status-badge status-agendado">Desconectado</span>
                                    <p class="text-muted mt-3">
                                        Conecte seu WhatsApp para começar a receber agendamentos automaticamente
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Como funciona?
                                </h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Conecte seu número do WhatsApp
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Clientes enviam mensagens
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        IA processa e responde automaticamente
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        Agendamentos são criados automaticamente
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Connection Setup -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-gear me-2"></i>
                            Configuração da Conexão
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="connect">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Número do WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-whatsapp"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="(11) 99999-9999" onkeyup="formatPhone(this)">
                                    </div>
                                    <div class="form-text">Número que será usado para receber mensagens dos clientes</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="instance_name" class="form-label">Nome da Instância</label>
                                    <input type="text" class="form-control" id="instance_name" name="instance_name" 
                                           placeholder="minha-empresa" value="<?= strtolower(str_replace(' ', '-', $_SESSION['user_name'])) ?>">
                                    <div class="form-text">Nome único para identificar sua instância</div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Importante:</strong> Após conectar, você receberá um QR Code para escanear com seu WhatsApp. 
                                Mantenha o WhatsApp Web desconectado de outros dispositivos durante o processo.
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-whatsapp me-2"></i>
                                Conectar WhatsApp
                            </button>
                        </form>
                    </div>
                </div>

                <!-- QR Code Section (Hidden by default) -->
                <div class="card mb-4" id="qrCodeSection" style="display: none;">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="bi bi-qr-code me-2"></i>
                            Escaneie o QR Code
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div id="qrCodeContainer">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Gerando QR Code...</span>
                            </div>
                            <p>Gerando QR Code...</p>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Instruções:</strong>
                            <ol class="mb-0 mt-2 text-start">
                                <li>Abra o WhatsApp no seu celular</li>
                                <li>Toque em "Mais opções" (três pontos) > "Dispositivos conectados"</li>
                                <li>Toque em "Conectar um dispositivo"</li>
                                <li>Escaneie o QR Code acima</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- AI Configuration -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-robot me-2"></i>
                            Configurações de IA
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_ai">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ai_preference" class="form-label">IA Preferida</label>
                                    <select class="form-select" id="ai_preference" name="ai_preference">
                                        <option value="padrao">Usar padrão do sistema</option>
                                        <option value="chatgpt">ChatGPT (OpenAI)</option>
                                        <option value="gemini">Gemini (Google)</option>
                                    </select>
                                    <div class="form-text">Escolha qual IA usar para processar as mensagens dos clientes</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="response_delay" class="form-label">Delay de Resposta (segundos)</label>
                                    <input type="number" class="form-control" id="response_delay" name="response_delay" 
                                           min="0" max="30" value="2">
                                    <div class="form-text">Tempo de espera antes de enviar a resposta (mais natural)</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="welcome_message" class="form-label">Mensagem de Boas-vindas</label>
                                <textarea class="form-control" id="welcome_message" name="welcome_message" rows="3" 
                                          placeholder="Olá! Bem-vindo(a) ao nosso atendimento..."></textarea>
                                <div class="form-text">Mensagem enviada automaticamente para novos contatos</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>
                                Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simulate QR Code generation (in production, this would come from the Evolution API)
function showQRCode() {
    document.getElementById('qrCodeSection').style.display = 'block';
    
    // Simulate QR code generation
    setTimeout(() => {
        document.getElementById('qrCodeContainer').innerHTML = `
            <div class="qr-code-placeholder bg-light border rounded p-4 d-inline-block">
                <i class="bi bi-qr-code display-1 text-muted"></i>
                <p class="mt-2 mb-0">QR Code aqui</p>
                <small class="text-muted">Em produção, seria gerado pela API Evolution</small>
            </div>
        `;
    }, 2000);
    
    // Simulate successful connection
    setTimeout(() => {
        document.getElementById('connectionStatus').innerHTML = `
            <i class="bi bi-whatsapp text-success display-1 mb-3"></i>
            <h5>Status da Conexão</h5>
            <span class="status-badge status-confirmado">Conectado</span>
            <p class="text-muted mt-3">
                WhatsApp conectado com sucesso! Agora você pode receber agendamentos automaticamente.
            </p>
        `;
        document.getElementById('qrCodeSection').style.display = 'none';
    }, 10000);
}

// Handle form submission
document.querySelector('form[action="connect"]')?.addEventListener('submit', function(e) {
    e.preventDefault();
    showQRCode();
});
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>