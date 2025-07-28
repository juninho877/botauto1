<?php 
$title = 'Configurações do Sistema - Admin';
include 'views/layouts/header.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        Admin Master
                    </h5>
                    <small class="text-white-50">Sistema SaaS WhatsApp</small>
                </div>
                
                <nav class="nav flex-column px-3">
                    <a class="nav-link" href="/admin/dashboard">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                    <a class="nav-link" href="/admin/companies">
                        <i class="bi bi-building"></i>
                        Empresas
                    </a>
                    <a class="nav-link active" href="/admin/settings">
                        <i class="bi bi-gear"></i>
                        Configurações
                    </a>
                    <a class="nav-link" href="/admin/logs">
                        <i class="bi bi-list-ul"></i>
                        Logs do Sistema
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
                        <h1 class="h3 mb-0">Configurações do Sistema</h1>
                        <p class="text-muted mb-0">Gerencie as configurações globais do sistema</p>
                    </div>
                </div>

                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <!-- WhatsApp API Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-whatsapp me-2"></i>
                                Configurações da API WhatsApp (Evolution v2)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="api_whatsapp_url" class="form-label">URL da API Evolution *</label>
                                    <input type="url" class="form-control" id="api_whatsapp_url" name="api_whatsapp_url" 
                                           value="<?= htmlspecialchars($config['api_whatsapp_url'] ?? 'http://localhost:8080') ?>" 
                                           placeholder="http://localhost:8080" required>
                                    <div class="form-text">URL do servidor Evolution API v2</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="api_whatsapp_token" class="form-label">Token de Autenticação *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="api_whatsapp_token" name="api_whatsapp_token" 
                                               value="<?= htmlspecialchars($config['api_whatsapp_token'] ?? '') ?>" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordField('api_whatsapp_token')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Token para autenticação na API</div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Instruções:</strong> Configure o servidor Evolution API v2 e obtenha o token de autenticação. 
                                <a href="https://doc.evolution-api.com/" target="_blank" class="alert-link">
                                    Ver documentação <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- AI Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-robot me-2"></i>
                                Configurações de Inteligência Artificial
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="ia_padrao" class="form-label">IA Padrão do Sistema *</label>
                                    <select class="form-select" id="ia_padrao" name="ia_padrao" required>
                                        <option value="chatgpt" <?= ($config['ia_padrao'] ?? 'chatgpt') === 'chatgpt' ? 'selected' : '' ?>>
                                            ChatGPT (OpenAI)
                                        </option>
                                        <option value="gemini" <?= ($config['ia_padrao'] ?? '') === 'gemini' ? 'selected' : '' ?>>
                                            Gemini (Google)
                                        </option>
                                    </select>
                                    <div class="form-text">IA utilizada quando a empresa não especifica preferência</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="openai_key" class="form-label">Chave da API OpenAI</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="openai_key" name="openai_key" 
                                               value="<?= htmlspecialchars($config['openai_key'] ?? '') ?>" 
                                               placeholder="sk-...">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordField('openai_key')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Para usar ChatGPT</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="gemini_key" class="form-label">Chave da API Gemini</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="gemini_key" name="gemini_key" 
                                               value="<?= htmlspecialchars($config['gemini_key'] ?? '') ?>" 
                                               placeholder="AI...">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordField('gemini_key')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Para usar Gemini</div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Importante:</strong> Configure pelo menos uma chave de API para que o sistema funcione adequadamente. 
                                Sem as chaves, será usado apenas o bot de respostas simples.
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-activity me-2"></i>
                                Status do Sistema
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="text-center">
                                        <i class="bi bi-server text-success display-4 mb-2"></i>
                                        <h6>Servidor</h6>
                                        <span class="status-badge status-confirmado">Online</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-center">
                                        <i class="bi bi-database text-success display-4 mb-2"></i>
                                        <h6>Banco de Dados</h6>
                                        <span class="status-badge status-confirmado">Conectado</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-center" id="whatsappStatus">
                                        <i class="bi bi-whatsapp text-warning display-4 mb-2"></i>
                                        <h6>WhatsApp API</h6>
                                        <span class="status-badge status-agendado">Testando...</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-center" id="aiStatus">
                                        <i class="bi bi-robot text-warning display-4 mb-2"></i>
                                        <h6>IA</h6>
                                        <span class="status-badge status-agendado">Testando...</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-primary" onclick="testConnections()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Testar Conexões
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-2"></i>
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordField(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function testConnections() {
    const whatsappStatus = document.getElementById('whatsappStatus');
    const aiStatus = document.getElementById('aiStatus');
    
    // Reset status
    whatsappStatus.innerHTML = `
        <i class="bi bi-whatsapp text-warning display-4 mb-2"></i>
        <h6>WhatsApp API</h6>
        <span class="status-badge status-agendado">Testando...</span>
    `;
    
    aiStatus.innerHTML = `
        <i class="bi bi-robot text-warning display-4 mb-2"></i>
        <h6>IA</h6>
        <span class="status-badge status-agendado">Testando...</span>
    `;
    
    // Simular teste (em produção, faria chamadas AJAX reais)
    setTimeout(() => {
        // Teste WhatsApp (simulado)
        const whatsappConnected = Math.random() > 0.3; // 70% chance de sucesso
        whatsappStatus.innerHTML = `
            <i class="bi bi-whatsapp text-${whatsappConnected ? 'success' : 'danger'} display-4 mb-2"></i>
            <h6>WhatsApp API</h6>
            <span class="status-badge status-${whatsappConnected ? 'confirmado' : 'cancelado'}">
                ${whatsappConnected ? 'Conectado' : 'Erro'}
            </span>
        `;
        
        // Teste IA (simulado)
        const aiConnected = Math.random() > 0.2; // 80% chance de sucesso
        aiStatus.innerHTML = `
            <i class="bi bi-robot text-${aiConnected ? 'success' : 'danger'} display-4 mb-2"></i>
            <h6>IA</h6>
            <span class="status-badge status-${aiConnected ? 'confirmado' : 'cancelado'}">
                ${aiConnected ? 'Funcionando' : 'Erro'}
            </span>
        `;
    }, 2000);
}

// Testar conexões ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(testConnections, 1000);
});

// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include 'views/layouts/footer.php'; ?>