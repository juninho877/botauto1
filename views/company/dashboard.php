<?php 
$title = 'Dashboard - ' . $_SESSION['user_name'];
include 'views/layouts/header.php'; 
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
                    <a class="nav-link active" href="/company/dashboard">
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
                        <h1 class="h3 mb-0">Dashboard</h1>
                        <p class="text-muted mb-0">Visão geral do seu negócio</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Bem-vindo de volta!</small>
                        <small class="text-muted"><?= date('d/m/Y H:i') ?></small>
                    </div>
                </div>

                <!-- Metrics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Agendamentos Mês
                                        </div>
                                        <div class="metric-number">
                                            <?= $stats['agendamentos_mes'] ?? 0 ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check text-primary display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card success h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Receita Mês
                                        </div>
                                        <div class="metric-number text-success">
                                            R$ <?= number_format($stats['receita_mes'] ?? 0, 2, ',', '.') ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-currency-dollar text-success display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card warning h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Confirmados
                                        </div>
                                        <div class="metric-number text-warning">
                                            <?php 
                                            $confirmados = 0;
                                            if (isset($stats['por_status'])) {
                                                foreach ($stats['por_status'] as $status) {
                                                    if ($status['status'] === 'confirmado') {
                                                        $confirmados = $status['total'];
                                                        break;
                                                    }
                                                }
                                            }
                                            echo $confirmados;
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle text-warning display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card danger h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            WhatsApp
                                        </div>
                                        <div class="h6 mb-0 font-weight-bold text-info">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Conectado
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-whatsapp text-info display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Recent Activity -->
                <div class="row mb-4">
                    <div class="col-xl-8 col-lg-7">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-graph-up me-2"></i>
                                    Agendamentos por Status
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" width="100%" height="40"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-5">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-star me-2"></i>
                                    Serviços Populares
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($stats['servicos_populares'])): ?>
                                    <?php foreach ($stats['servicos_populares'] as $servico): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <strong><?= htmlspecialchars($servico['nome']) ?></strong>
                                            </div>
                                            <div>
                                                <span class="badge bg-primary"><?= $servico['total'] ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="bi bi-list-ul text-muted display-6 mb-2"></i>
                                        <p class="text-muted mb-0">Nenhum agendamento ainda</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Agendamentos Recentes
                                </h6>
                                <a href="/company/appointments" class="btn btn-sm btn-outline-primary">
                                    Ver todos
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recent_appointments)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Cliente</th>
                                                    <th>Serviço</th>
                                                    <th>Data/Hora</th>
                                                    <th>Status</th>
                                                    <th>Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($recent_appointments, 0, 5) as $appointment): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-primary rounded-circle p-2 me-3">
                                                                    <i class="bi bi-person text-white"></i>
                                                                </div>
                                                                <div>
                                                                    <strong><?= htmlspecialchars($appointment['cliente_nome']) ?></strong>
                                                                    <br>
                                                                    <small class="text-muted"><?= htmlspecialchars($appointment['telefone']) ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($appointment['servico_nome']) ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= $appointment['duracao_minutos'] ?>min</small>
                                                        </td>
                                                        <td>
                                                            <strong><?= date('d/m/Y', strtotime($appointment['data_agendamento'])) ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= substr($appointment['hora_inicio'], 0, 5) ?> - <?= substr($appointment['hora_fim'], 0, 5) ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="status-badge status-<?= $appointment['status'] ?>">
                                                                <?= ucfirst($appointment['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <strong>R$ <?= number_format($appointment['preco'], 2, ',', '.') ?></strong>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-x text-muted display-4 mb-3"></i>
                                        <h5 class="text-muted">Nenhum agendamento ainda</h5>
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
    </div>
</div>

<script>
// Gráfico de status dos agendamentos
const statusCtx = document.getElementById('statusChart').getContext('2d');

// Preparar dados do PHP para JavaScript
const statusData = <?php 
$status_counts = ['agendado' => 0, 'confirmado' => 0, 'cancelado' => 0, 'concluido' => 0];
if (isset($stats['por_status'])) {
    foreach ($stats['por_status'] as $status) {
        $status_counts[$status['status']] = $status['total'];
    }
}
echo json_encode(array_values($status_counts));
?>;

const statusChart = new Chart(statusCtx, {
    type: 'bar',
    data: {
        labels: ['Agendado', 'Confirmado', 'Cancelado', 'Concluído'],
        datasets: [{
            label: 'Agendamentos',
            data: statusData,
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#dc3545',
                '#6c757d'
            ],
            borderRadius: 8,
            barThickness: 30
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                },
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php include 'views/layouts/footer.php'; ?>