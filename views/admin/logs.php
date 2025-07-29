<?php 
$title = 'Logs do Sistema - Admin';
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
                    <a class="nav-link" href="/admin/companies">
                        <i class="bi bi-building"></i>
                        Empresas
                    </a>
                    <a class="nav-link" href="/admin/settings">
                        <i class="bi bi-gear"></i>
                        Configurações
                    </a>
                    <a class="nav-link active" href="/admin/logs">
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
                        <h1 class="h3 mb-0">Logs do Sistema</h1>
                        <p class="text-muted mb-0">Auditoria e monitoramento de atividades</p>
                    </div>
                    <button class="btn btn-outline-primary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Atualizar
                    </button>
                </div>

                <!-- Logs Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-list-ul me-2"></i>
                            Registro de Atividades
                            <?php if (isset($total)): ?>
                                (<?= number_format($total) ?> registros)
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($logs)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Ação</th>
                                            <th>Empresa</th>
                                            <th>Detalhes</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= getActionColor($log['acao']) ?>">
                                                        <?= htmlspecialchars($log['acao']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($log['company_nome']): ?>
                                                        <small><?= htmlspecialchars($log['company_nome']) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Sistema</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($log['detalhes']): ?>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars(substr($log['detalhes'], 0, 100)) ?>
                                                            <?= strlen($log['detalhes']) > 100 ? '...' : '' ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted font-monospace">
                                                        <?= htmlspecialchars($log['ip_address']) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if (isset($totalPages) && $totalPages > 1): ?>
                                <nav aria-label="Navegação dos logs">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                                            <li class="page-item <?= ($page ?? 1) == $i ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($totalPages > 10): ?>
                                            <li class="page-item">
                                                <span class="page-link">...</span>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-list-ul text-muted display-4 mb-3"></i>
                                <h5 class="text-muted">Nenhum log encontrado</h5>
                                <p class="text-muted">Os logs de atividade aparecerão aqui conforme o sistema for usado</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getActionColor($action) {
    $colors = [
        'login' => 'success',
        'login_failed' => 'danger',
        'logout' => 'secondary',
        'company_created' => 'primary',
        'service_created' => 'info',
        'appointment_created' => 'success',
        'whatsapp_connect' => 'success',
        'settings_updated' => 'warning',
        'admin_' => 'dark'
    ];
    
    foreach ($colors as $key => $color) {
        if (strpos($action, $key) !== false) {
            return $color;
        }
    }
    
    return 'secondary';
}
?>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>