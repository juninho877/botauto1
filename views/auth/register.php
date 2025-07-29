<?php 
$title = 'Cadastro de Empresa - Sistema SaaS WhatsApp';
include BASE_PATH . '/views/layouts/header.php'; 
?>

<div class="container-fluid">
    <div class="row min-vh-100">
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-gradient bg-success">
            <div class="text-center text-white">
                <i class="bi bi-building display-1 mb-4"></i>
                <h1 class="h2 mb-3">Cadastre sua Empresa</h1>
                <p class="lead">Comece a automatizar seu atendimento hoje mesmo</p>
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card bg-transparent border-light">
                            <div class="card-body">
                                <h6 class="text-white mb-3">✨ Recursos inclusos:</h6>
                                <ul class="list-unstyled text-start">
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Atendimento automático 24/7</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Agendamento inteligente</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Integração com IA (ChatGPT/Gemini)</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Dashboard completo</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Lembretes automáticos</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 500px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-building text-success display-4 mb-3"></i>
                            <h4 class="fw-bold">Cadastrar Empresa</h4>
                            <p class="text-muted">Preencha os dados para começar</p>
                        </div>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="nome" class="form-label">Nome da Empresa *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-building"></i>
                                        </span>
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               placeholder="Ex: Salão de Beleza Glamour" required>
                                    </div>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="email" class="form-label">Email de Acesso *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="admin@suaempresa.com" required>
                                    </div>
                                    <div class="form-text">Este será seu login no sistema</div>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="telefone" class="form-label">Telefone WhatsApp</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-whatsapp"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="telefone" name="telefone" 
                                               placeholder="(11) 99999-9999" onkeyup="formatPhone(this)">
                                    </div>
                                    <div class="form-text">Número que será conectado ao sistema</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="senha" class="form-label">Senha *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="senha" name="senha" 
                                               placeholder="Mínimo 6 caracteres" required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha')">
                                            <i class="bi bi-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock-fill"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                                               placeholder="Repita a senha" required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmar_senha')">
                                            <i class="bi bi-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="termos" name="termos" required>
                                    <label class="form-check-label" for="termos">
                                        Concordo com os <a href="#" class="text-primary">Termos de Uso</a> 
                                        e <a href="#" class="text-primary">Política de Privacidade</a>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="bi bi-check-circle me-2"></i>
                                Criar Conta
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Já tem uma conta? 
                                <a href="/login" class="text-primary text-decoration-none">
                                    Fazer login
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(fieldId === 'senha' ? 'toggleIcon1' : 'toggleIcon2');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

// Validar senhas iguais
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const senha = document.getElementById('senha').value;
    const confirmarSenha = this.value;
    
    if (confirmarSenha && senha !== confirmarSenha) {
        this.setCustomValidity('As senhas não coincidem');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        if (confirmarSenha) this.classList.add('is-valid');
    }
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

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>