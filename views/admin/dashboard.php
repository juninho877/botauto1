<?php 
$title = 'Dashboard Admin - Sistema SaaS WhatsApp';
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
                    <a class="nav-link active" href="/admin/dashboard">
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
                        <h1 class="h3 mb-0">Dashboard Administrativo</h1>
                        <p class="text-muted mb-0">Visão geral do sistema</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?></small>
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
                                            Empresas Ativas
                                        </div>
                                        <div class="metric-number">
                                            <?= $stats['empresas_ativas'] ?? 0 ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-building text-primary display-6"></i>
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
                                            Agendamentos Hoje
                                        </div>
                                        <div class="metric-number text-success">
                                            <?= $stats['agendamentos_hoje'] ?? 0 ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check text-success display-6"></i>
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
                                            Mensagens Hoje
                                        </div>
                                        <div class="metric-number text-warning">
                                            <?= $stats['mensagens_hoje'] ?? 0 ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-chat-dots text-warning display-6"></i>
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
                                            Sistema
                                        </div>
                                        <div class="h6 mb-0 font-weight-bold text-info">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Online
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-server text-info display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-xl-8 col-lg-7">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-graph-up me-2"></i>
                                    Agendamentos dos Últimos 7 Dias
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="appointmentsChart" width="100%" height="40"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-5">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-pie-chart me-2"></i>
                                    Uso de IA
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="aiUsageChart" width="100%" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Companies Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-building me-2"></i>
                                    Empresas Cadastradas Recentemente
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($stats['empresas_recentes'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Empresa</th>
                                                    <th>Email</th>
                                                    <th>Data de Cadastro</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stats['empresas_recentes'] as $empresa): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-primary rounded-circle p-2 me-3">
                                                                    <i class="bi bi-building text-white"></i>
                                                                </div>
                                                                <strong><?= htmlspecialchars($empresa['nome']) ?></strong>
                                                            </div>
                                                        </td>
                                                        <td><?= htmlspecialchars($empresa['email']) ?></td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?= date('d/m/Y H:i', strtotime($empresa['created_at'])) ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="status-badge status-confirmado">
                                                                <i class="bi bi-check-circle me-1"></i>
                                                                Ativa
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-building text-muted display-4 mb-3"></i>
                                        <p class="text-muted">Nenhuma empresa cadastrada ainda</p>
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
// Gráfico de agendamentos
const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
const appointmentsChart = new Chart(appointmentsCtx, {
    type: 'line',
    data: {
        labels: ['6 dias', '5 dias', '4 dias', '3 dias', '2 dias', 'Ontem', 'Hoje'],
        datasets: [{
            label: 'Agendamentos',
            data: [12, 19, 15, 25, 22, 30, 18],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true
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

// Gráfico de uso de IA
const aiUsageCtx = document.getElementById('aiUsageChart').getContext('2d');
const aiUsageChart = new Chart(aiUsageCtx, {
    type: 'doughnut',
    data: {
        labels: ['ChatGPT', 'Gemini', 'Bot Simples'],
        datasets: [{
            data: [60, 25, 15],
            backgroundColor: [
                '#28a745',
                '#ffc107',
                '#6c757d'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        }
    }
});
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>