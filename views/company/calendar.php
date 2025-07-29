<?php 
$title = 'Calendário - ' . $_SESSION['user_name'];
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
                    <a class="nav-link active" href="/company/calendar">
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
                        <h1 class="h3 mb-0">Calendário</h1>
                        <p class="text-muted mb-0">Visualize seus agendamentos em formato de calendário</p>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="calendar.changeView('dayGridMonth')">
                            <i class="bi bi-calendar3 me-1"></i>
                            Mês
                        </button>
                        <button class="btn btn-outline-primary" onclick="calendar.changeView('timeGridWeek')">
                            <i class="bi bi-calendar-week me-1"></i>
                            Semana
                        </button>
                        <button class="btn btn-outline-primary" onclick="calendar.changeView('timeGridDay')">
                            <i class="bi bi-calendar-day me-1"></i>
                            Dia
                        </button>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="card">
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar-event me-2"></i>
                    Detalhes do Agendamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="eventDetails">
                    <!-- Event details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/pt-br.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    window.calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        height: 'auto',
        events: '/company/calendar?api=events',
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        eventDidMount: function(info) {
            // Add tooltip
            info.el.setAttribute('title', 
                info.event.extendedProps.cliente + ' - ' + 
                info.event.extendedProps.servico + ' - ' +
                'R$ ' + parseFloat(info.event.extendedProps.preco).toFixed(2).replace('.', ',')
            );
        }
    });
    
    calendar.render();
});

function showEventDetails(event) {
    const props = event.extendedProps;
    const startTime = event.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
    const endTime = event.end.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
    const date = event.start.toLocaleDateString('pt-BR');
    
    const statusColors = {
        'agendado': 'primary',
        'confirmado': 'success',
        'cancelado': 'danger',
        'concluido': 'secondary'
    };
    
    const statusColor = statusColors[props.status] || 'primary';
    
    document.getElementById('eventDetails').innerHTML = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Cliente:</label>
                <p class="mb-0">${props.cliente}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Telefone:</label>
                <p class="mb-0">
                    <i class="bi bi-whatsapp text-success me-1"></i>
                    ${props.telefone}
                </p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Serviço:</label>
                <p class="mb-0">${props.servico}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Valor:</label>
                <p class="mb-0">
                    <strong>R$ ${parseFloat(props.preco).toFixed(2).replace('.', ',')}</strong>
                </p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Data:</label>
                <p class="mb-0">${date}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Horário:</label>
                <p class="mb-0">${startTime} - ${endTime}</p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Status:</label>
                <p class="mb-0">
                    <span class="badge bg-${statusColor}">${props.status.charAt(0).toUpperCase() + props.status.slice(1)}</span>
                </p>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('eventModal'));
    modal.show();
}
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>