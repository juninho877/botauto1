<?php 
$title = 'Login - Sistema SaaS WhatsApp';
include BASE_PATH . '/views/layouts/header.php'; 
?>

<div class="container-fluid">
    <div class="row min-vh-100">
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary">
            <div class="text-center text-white">
                <i class="bi bi-whatsapp display-1 mb-4"></i>
                <h1 class="h2 mb-3">Sistema SaaS WhatsApp</h1>
                <p class="lead">Automatize seu atendimento e agendamentos com inteligência artificial</p>
                <div class="row mt-5">
                    <div class="col-4">
                        <i class="bi bi-robot display-4 mb-2"></i>
                        <h6>IA Integrada</h6>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-calendar-check display-4 mb-2"></i>
                        <h6>Agendamento</h6>
                    </div>
                    <div class="col-4">
                        <i class="bi bi-graph-up display-4 mb-2"></i>
                        <h6>Analytics</h6>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-whatsapp text-success display-4 mb-3"></i>
                            <h4 class="fw-bold">Fazer Login</h4>
                            <p class="text-muted">Acesse sua conta para continuar</p>
                        </div>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="seu@email.com" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Digite sua senha" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Tipo de Acesso</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="company">Empresa</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Entrar
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Não tem uma conta? 
                                <a href="/register" class="text-primary text-decoration-none">
                                    Cadastre sua empresa
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <strong>Dados de teste:</strong><br>
                        Empresa: salao@glamour.com / senha: 123456<br>
                        Admin: admin@sistema.com / senha: 123456
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

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