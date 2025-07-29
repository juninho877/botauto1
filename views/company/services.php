<?php 
$title = 'Gerenciar Serviços - ' . $_SESSION['user_name'];
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
                    <a class="nav-link active" href="/company/services">
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
                    <a class="nav-link" href="/company/whatsapp">
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
                        <h1 class="h3 mb-0">Gerenciar Serviços</h1>
                        <p class="text-muted mb-0">Configure os serviços oferecidos pela sua empresa</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                        <i class="bi bi-plus-lg me-2"></i>
                        Novo Serviço
                    </button>
                </div>

                <!-- Services Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-list-check me-2"></i>
                            Lista de Serviços (<?= count($services) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($services)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Serviço</th>
                                            <th>Duração</th>
                                            <th>Preço</th>
                                            <th>Status</th>
                                            <th width="120">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($service['nome']) ?></strong>
                                                        <?php if ($service['descricao']): ?>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($service['descricao']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $service['duracao_minutos'] ?> min</span>
                                                </td>
                                                <td>
                                                    <strong>R$ <?= number_format($service['preco'], 2, ',', '.') ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($service['ativo']): ?>
                                                        <span class="status-badge status-confirmado">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Ativo
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-cancelado">
                                                            <i class="bi bi-pause-circle me-1"></i>
                                                            Inativo
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                onclick="editService(<?= $service['id'] ?>)"
                                                                title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                            <input type="hidden" name="action" value="toggle">
                                                            <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                                            <button type="submit" 
                                                                    class="btn btn-outline-<?= $service['ativo'] ? 'warning' : 'success' ?>"
                                                                    onclick="return confirmAction('<?= $service['ativo'] ? 'desativar' : 'ativar' ?> este serviço?')"
                                                                    title="<?= $service['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                                                <i class="bi bi-<?= $service['ativo'] ? 'pause' : 'play' ?>"></i>
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
                                <i class="bi bi-list-check text-muted display-4 mb-3"></i>
                                <h5 class="text-muted">Nenhum serviço cadastrado</h5>
                                <p class="text-muted">Clique no botão "Novo Serviço" para começar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Service Modal -->
<div class="modal fade" id="createServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>
                    Novo Serviço
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Serviço *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duracao_minutos" class="form-label">Duração (minutos) *</label>
                            <input type="number" class="form-control" id="duracao_minutos" name="duracao_minutos" 
                                   min="15" step="15" value="60" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="preco" class="form-label">Preço (R$) *</label>
                            <input type="number" class="form-control" id="preco" name="preco" 
                                   min="0" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-2"></i>
                        Criar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editService(id) {
    // TODO: Implementar modal de edição
    alert('Funcionalidade de edição será implementada em breve. ID: ' + id);
}
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>