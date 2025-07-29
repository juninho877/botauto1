<?php 
$title = 'Gerenciar Empresas - Admin';
include BASE_PATH . '/views/layouts/header.php'; 
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
                    <a class="nav-link active" href="/admin/companies">
                        <i class="bi bi-building"></i>
                        Empresas
                    </a>
                    <a class="nav-link" href="/admin/settings">
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
                        <h1 class="h3 mb-0">Gerenciar Empresas</h1>
                        <p class="text-muted mb-0">Visualize e gerencie todas as empresas do sistema</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
                        <i class="bi bi-plus-lg me-2"></i>
                        Nova Empresa
                    </button>
                </div>

                <!-- Companies Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-building me-2"></i>
                            Lista de Empresas (<?= count($companies) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($companies)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Empresa</th>
                                            <th>Email</th>
                                            <th>Telefone</th>
                                            <th>WhatsApp</th>
                                            <th>Status</th>
                                            <th>Cadastro</th>
                                            <th width="120">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($companies as $company): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle p-2 me-3">
                                                            <i class="bi bi-building text-white"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($company['nome']) ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($company['email']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($company['telefone']): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($company['telefone']) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($company['whatsapp_connected']): ?>
                                                        <span class="status-badge status-confirmado">
                                                            <i class="bi bi-whatsapp me-1"></i>
                                                            Conectado
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-cancelado">
                                                            <i class="bi bi-x-circle me-1"></i>
                                                            Desconectado
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($company['ativo']): ?>
                                                        <span class="status-badge status-confirmado">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Ativa
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-cancelado">
                                                            <i class="bi bi-pause-circle me-1"></i>
                                                            Inativa
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($company['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                onclick="editCompany(<?= $company['id'] ?>)"
                                                                title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST" action="/admin/toggle-company" class="d-inline">
                                                            <input type="hidden" name="id" value="<?= $company['id'] ?>">
                                                            <button type="submit" 
                                                                    class="btn btn-outline-<?= $company['ativo'] ? 'warning' : 'success' ?>"
                                                                    onclick="return confirmAction('<?= $company['ativo'] ? 'desativar' : 'ativar' ?> esta empresa?')"
                                                                    title="<?= $company['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                                                <i class="bi bi-<?= $company['ativo'] ? 'pause' : 'play' ?>"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-building text-muted display-4 mb-3"></i>
                                <h5 class="text-muted">Nenhuma empresa cadastrada</h5>
                                <p class="text-muted">Clique no botão "Nova Empresa" para começar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Company Modal -->
<div class="modal fade" id="createCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Nova Empresa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/admin/create-company">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email de Acesso *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="senha" class="form-label">Senha *</label>
                            <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefone" class="form-label">Telefone WhatsApp</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" 
                                   onkeyup="formatPhone(this)" placeholder="(11) 99999-9999">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Nota:</strong> A empresa será criada com horário de funcionamento padrão (8h às 18h). 
                        Ela poderá alterar essas configurações após o primeiro login.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-2"></i>
                        Criar Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCompany(id) {
    // Por enquanto, mostrar um alert
    alert('Funcionalidade de edição será implementada em breve. ID: ' + id);
    
    // TODO: Implementar modal de edição
    // Carregar dados da empresa via AJAX
    // Preencher formulário de edição
    // Mostrar modal de edição
}
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>