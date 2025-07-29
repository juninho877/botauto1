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
                                    <?php if (isset($whatsapp_connected) && $whatsapp_connected): ?>
                                        <i class="bi bi-whatsapp text-success display-1 mb-3"></i>
                                        <h5>Status da Conexão</h5>
                                        <span class="status-badge status-confirmado">Conectado</span>
                                        <p class="text-muted mt-3">
                                            WhatsApp conectado com sucesso! Você está recebendo mensagens automaticamente.
                                        </p>
                                        <p class="text-muted">
                                            <strong>Instância:</strong> <?= htmlspecialchars($instance_name ?? '') ?>
                                        </p>
                                    <?php else: ?>
                                        <i class="bi bi-whatsapp text-warning display-1 mb-3"></i>
                                        <h5>Status da Conexão</h5>
                                        <span class="status-badge status-agendado">Desconectado</span>
                                        <p class="text-muted mt-3">
                                            Conecte seu WhatsApp para começar a receber agendamentos automaticamente
                                        </p>
                                    <?php endif; ?>
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
                <?php if (!isset($whatsapp_connected) || !$whatsapp_connected): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-gear me-2"></i>
                            Configuração da Conexão
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="connect">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Número do WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-whatsapp"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="5511999999999" value="<?= htmlspecialchars($company_phone ?? '') ?>">
                                    </div>
                                    <div class="form-text">Número com código do país (ex: 5511999999999)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="instance_name" class="form-label">Nome da Instância</label>
                                    <input type="text" class="form-control" id="instance_name" name="instance_name" 
                                           placeholder="minha-empresa" value="<?= htmlspecialchars($instance_name ?? '') ?>" readonly>
                                    <div class="form-text">Nome único gerado automaticamente para sua empresa</div>
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
                <?php endif; ?>

                <!-- QR Code Section -->
                <?php if (isset($qr_code)): ?>
                <div class="card mb-4" id="qrCodeSection">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="bi bi-qr-code me-2"></i>
                            Escaneie o QR Code
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div id="qrCodeContainer">
                            <?php if ($qr_code): ?>
                                <img src="data:image/png;base64,<?= $qr_code ?>" alt="QR Code WhatsApp" class="img-fluid mb-3" style="max-width: 300px;">
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>QR Code gerado com sucesso!</strong>
                                    <br>
                                    <small>Este QR Code é válido por alguns minutos. Se expirar, clique em "Atualizar QR Code".</small>
                                </div>
                            <?php else: ?>
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Gerando QR Code...</span>
                                </div>
                                <p>Gerando QR Code...</p>
                            <?php endif; ?>
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
                        
                        <div class="mt-3">
                            <button class="btn btn-outline-primary me-2" onclick="refreshQRCode()">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Atualizar QR Code
                            </button>
                            <button class="btn btn-outline-secondary" onclick="checkConnectionStatus()">
                                <i class="bi bi-check-circle me-2"></i>
                                Verificar Conexão
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Pairing Code Section -->
                <?php if (isset($pairing_code)): ?>
                <div class="card mb-4" id="pairingCodeSection">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="bi bi-phone me-2"></i>
                            Código de Pareamento
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Código de Pareamento Gerado!</strong>
                        </div>
                        
                        <div class="bg-light p-4 rounded mb-4">
                            <h2 class="display-4 font-monospace text-primary mb-0"><?= htmlspecialchars($pairing_code) ?></h2>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Instruções para Pareamento:</strong>
                            <ol class="mb-0 mt-2 text-start">
                                <li>Abra o WhatsApp no seu celular</li>
                                <li>Toque em "Mais opções" (três pontos) > "Dispositivos conectados"</li>
                                <li>Toque em "Conectar com código de telefone"</li>
                                <li>Digite o código: <strong><?= htmlspecialchars($pairing_code) ?></strong></li>
                            </ol>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-outline-primary me-2" onclick="refreshQRCode()">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Gerar Novo Código
                            </button>
                            <button class="btn btn-outline-secondary" onclick="checkConnectionStatus()">
                                <i class="bi bi-check-circle me-2"></i>
                                Verificar Conexão
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Disconnect Section -->
                <?php if (isset($whatsapp_connected) && $whatsapp_connected): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            Desconectar WhatsApp
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Desconectar o WhatsApp irá parar o recebimento de mensagens automáticas. 
                            Você pode reconectar a qualquer momento.
                        </p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="disconnect">
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirmAction('desconectar o WhatsApp? Isso irá parar o atendimento automático.')">
                                <i class="bi bi-x-circle me-2"></i>
                                Desconectar
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

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
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="update_ai">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ai_preference" class="form-label">IA Preferida</label>
                                    <select class="form-select" id="ai_preference" name="ai_preference">
                                        <option value="padrao" <?= ($ai_preference ?? 'padrao') === 'padrao' ? 'selected' : '' ?>>
                                            Usar padrão do sistema
                                        </option>
                                        <option value="chatgpt" <?= ($ai_preference ?? '') === 'chatgpt' ? 'selected' : '' ?>>
                                            ChatGPT (OpenAI)
                                        </option>
                                        <option value="gemini" <?= ($ai_preference ?? '') === 'gemini' ? 'selected' : '' ?>>
                                            Gemini (Google)
                                        </option>
                                    </select>
                                    <div class="form-text">Escolha qual IA usar para processar as mensagens dos clientes</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="webhook_url" class="form-label">URL do Webhook</label>
                                    <input type="url" class="form-control" id="webhook_url" name="webhook_url" 
                                           value="<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/webhook/whatsapp.php" readonly>
                                    <div class="form-text">URL configurada automaticamente na Evolution API</div>
                                </div>
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
function checkConnectionStatus() {
    // Simulate checking connection status
    const statusDiv = document.getElementById('connectionStatus');
    
    // Show loading
    statusDiv.innerHTML = `
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Verificando...</span>
        </div>
        <p>Verificando status da conexão...</p>
    `;
    
    // Make actual AJAX call to check connection status
    fetch('/company/whatsapp?action=check_status', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.connected) {
            statusDiv.innerHTML = `
                <i class="bi bi-whatsapp text-success display-1 mb-3"></i>
                <h5>Status da Conexão</h5>
                <span class="status-badge status-confirmado">Conectado</span>
                <p class="text-muted mt-3">WhatsApp conectado com sucesso!</p>
            `;
            
            // Hide QR code and pairing code sections if they exist
            const qrSection = document.getElementById('qrCodeSection');
            const pairingSection = document.getElementById('pairingCodeSection');
            if (qrSection) qrSection.style.display = 'none';
            if (pairingSection) pairingSection.style.display = 'none';
            
            // Show success message and reload after delay
            setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            statusDiv.innerHTML = `
                <i class="bi bi-whatsapp text-warning display-1 mb-3"></i>
                <h5>Status da Conexão</h5>
                <span class="status-badge status-agendado">Desconectado</span>
                <p class="text-muted mt-3">Ainda não conectado. Continue tentando escanear o QR Code.</p>
            `;
        }
    })
    .catch(error => {
        console.error('Erro ao verificar status:', error);
        statusDiv.innerHTML = `
            <i class="bi bi-whatsapp text-danger display-1 mb-3"></i>
            <h5>Status da Conexão</h5>
            <span class="status-badge status-cancelado">Erro</span>
            <p class="text-muted mt-3">Erro ao verificar status da conexão.</p>
        `;
    });
}

// Auto-refresh QR code every 30 seconds if displayed
<?php if (isset($qr_code) && !$whatsapp_connected): ?>
// Auto-refresh QR code every 2 minutes (120 seconds) instead of 30 seconds
let qrRefreshInterval = setInterval(() => {
    const qrSection = document.getElementById('qrCodeSection');
    if (qrSection && qrSection.style.display !== 'none') {
        console.log('Auto-checking connection status...');
        checkConnectionStatus();
    } else {
        // Clear interval if QR section is not visible
        clearInterval(qrRefreshInterval);
    }
}, 10000); // Check every 10 seconds

// Add manual refresh button functionality
function refreshQRCode() {
    const qrContainer = document.getElementById('qrCodeContainer');
    qrContainer.innerHTML = `
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Atualizando QR Code...</span>
        </div>
        <p>Atualizando QR Code...</p>
    `;
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}
<?php endif; ?>
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>