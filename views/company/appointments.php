<?php 
$title = 'Agendamentos - ' . $_SESSION['user_name'];
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
                    <a class="nav-link active" href="/company/appointments">
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
                        <h1 class="h3 mb-0">Agendamentos</h1>
                        <p class="text-muted mb-0">Gerencie todos os agendamentos da sua empresa</p>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-funnel me-2"></i>
                            Filtrar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?status=agendado">Agendados</a></li>
                            <li><a class="dropdown-item" href="?status=confirmado">Confirmados</a></li>
                            <li><a class="dropdown-item" href="?status=cancelado">Cancelados</a></li>
                            <li><a class="dropdown-item" href="?status=concluido">Concluídos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/company/appointments">Todos</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-calendar-check me-2"></i>
                            Lista de Agendamentos (<?= count($appointments) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($appointments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Serviço</th>
                                            <th>Data/Hora</th>
                                            <th>Status</th>
                                            <th>Valor</th>
                                            <th width="120">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle p-2 me-3">
                                                            <i class="bi bi-person text-white"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($appointment['cliente_nome']) ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="bi bi-whatsapp me-1"></i>
                                                                <?= htmlspecialchars($appointment['telefone']) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($appointment['servico_nome']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?= $appointment['duracao_minutos'] ?> minutos
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?= date('d/m/Y', strtotime($appointment['data_agendamento'])) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?= substr($appointment['hora_inicio'], 0, 5) ?> - <?= substr($appointment['hora_fim'], 0, 5) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?= $appointment['status'] ?>">
                                                        <?= ucfirst($appointment['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>R$ <?= number_format($appointment['preco'], 2, ',', '.') ?></strong>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-gear"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if ($appointment['status'] === 'agendado'): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_status">
                                                                        <input type="hidden" name="id" value="<?= $appointment['id'] ?>">
                                                                        <input type="hidden" name="status" value="confirmado">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="bi bi-check-circle me-2"></i>
                                                                            Confirmar
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (in_array($appointment['status'], ['agendado', 'confirmado'])): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_status">
                                                                        <input type="hidden" name="id" value="<?= $appointment['id'] ?>">
                                                                        <input type="hidden" name="status" value="concluido">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="bi bi-check2-all me-2"></i>
                                                                            Concluir
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_status">
                                                                        <input type="hidden" name="id" value="<?= $appointment['id'] ?>">
                                                                        <input type="hidden" name="status" value="cancelado">
                                                                        <button type="submit" class="dropdown-item text-danger"
                                                                                onclick="return confirmAction('cancelar este agendamento?')">
                                                                            <i class="bi bi-x-circle me-2"></i>
                                                                            Cancelar
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x text-muted display-4 mb-3"></i>
                                <h5 class="text-muted">Nenhum agendamento encontrado</h5>
                                <p class="text-muted">Os agendamentos aparecerão aqui quando os clientes começarem a usar o WhatsApp</p>
                                <a href="/company/whatsapp" class="btn btn-primary">
                                    <i class="bi bi-whatsapp me-2"></i>
                                    Conectar WhatsApp
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>