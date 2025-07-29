<?php 
$title = 'Conversas WhatsApp - ' . $_SESSION['user_name'];
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
                    <a class="nav-link" href="/company/calendar">
                        <i class="bi bi-calendar3"></i>
                        Calendário
                    </a>
                    <a class="nav-link active" href="/company/conversations">
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
                        <h1 class="h3 mb-0">Conversas WhatsApp</h1>
                        <p class="text-muted mb-0">Histórico de conversas com seus clientes</p>
                    </div>
                    <button class="btn btn-outline-primary" onclick="refreshConversations()">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Atualizar
                    </button>
                </div>

                <div class="row">
                    <!-- Conversations List -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-chat-dots me-2"></i>
                                    Conversas (<?= count($conversations) ?>)
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($conversations)): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($conversations as $conversation): ?>
                                            <a href="#" class="list-group-item list-group-item-action conversation-item" 
                                               data-conversation-id="<?= $conversation['id'] ?>"
                                               data-phone="<?= htmlspecialchars($conversation['telefone']) ?>"
                                               data-name="<?= htmlspecialchars($conversation['nome_cliente'] ?: 'Cliente') ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-success rounded-circle p-2 me-3">
                                                            <i class="bi bi-whatsapp text-white"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <?= htmlspecialchars($conversation['nome_cliente'] ?: 'Cliente') ?>
                                                            </h6>
                                                            <small class="text-muted"><?= htmlspecialchars($conversation['telefone']) ?></small>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= date('d/m H:i', strtotime($conversation['ultima_interacao'])) ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1 text-truncate">
                                                    <?= htmlspecialchars($conversation['ultima_mensagem']) ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-chat me-1"></i>
                                                    <?= $conversation['total_mensagens'] ?> mensagens
                                                </small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-chat-dots text-muted display-6 mb-3"></i>
                                        <p class="text-muted">Nenhuma conversa ainda</p>
                                        <small class="text-muted">As conversas aparecerão aqui quando os clientes enviarem mensagens</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Panel -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header" id="chatHeader" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success rounded-circle p-2 me-3">
                                        <i class="bi bi-whatsapp text-white"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0" id="chatName">Cliente</h6>
                                        <small class="text-muted" id="chatPhone">+55 11 99999-9999</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body" id="messagesContainer">
                                <div class="text-center py-5" id="noConversationSelected">
                                    <i class="bi bi-chat-square-dots text-muted display-4 mb-3"></i>
                                    <h5 class="text-muted">Selecione uma conversa</h5>
                                    <p class="text-muted">Clique em uma conversa à esquerda para ver as mensagens</p>
                                </div>
                                <div id="messagesList" style="display: none; max-height: 400px; overflow-y: auto;">
                                    <!-- Messages will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentConversationId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Add click event to conversation items
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all items
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Load messages
            const conversationId = this.dataset.conversationId;
            const phone = this.dataset.phone;
            const name = this.dataset.name;
            
            loadMessages(conversationId, phone, name);
        });
    });
});

function loadMessages(conversationId, phone, name) {
    currentConversationId = conversationId;
    
    // Update chat header
    document.getElementById('chatName').textContent = name;
    document.getElementById('chatPhone').textContent = phone;
    document.getElementById('chatHeader').style.display = 'block';
    document.getElementById('noConversationSelected').style.display = 'none';
    document.getElementById('messagesList').style.display = 'block';
    
    // Show loading
    document.getElementById('messagesList').innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    // Load messages via AJAX
    fetch(`/company/conversations?api=messages&conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(messages => {
            displayMessages(messages);
        })
        .catch(error => {
            console.error('Erro ao carregar mensagens:', error);
            document.getElementById('messagesList').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Erro ao carregar mensagens
                </div>
            `;
        });
}

function displayMessages(messages) {
    const container = document.getElementById('messagesList');
    
    if (messages.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3">
                <i class="bi bi-chat text-muted display-6 mb-2"></i>
                <p class="text-muted">Nenhuma mensagem nesta conversa</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    messages.forEach(message => {
        const isReceived = message.tipo === 'recebida';
        const time = new Date(message.created_at).toLocaleString('pt-BR');
        
        html += `
            <div class="d-flex ${isReceived ? 'justify-content-start' : 'justify-content-end'} mb-3">
                <div class="message-bubble ${isReceived ? 'received' : 'sent'}" style="max-width: 70%;">
                    <div class="message-content p-3 rounded-3 ${isReceived ? 'bg-light' : 'bg-primary text-white'}">
                        <p class="mb-1">${escapeHtml(message.conteudo)}</p>
                        <small class="text-muted ${isReceived ? '' : 'text-white-50'}">${time}</small>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function refreshConversations() {
    location.reload();
}
</script>

<style>
.conversation-item.active {
    background-color: #e3f2fd !important;
    border-left: 4px solid #007bff;
}

.message-bubble {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>