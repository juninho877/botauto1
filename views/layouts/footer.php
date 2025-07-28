<!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            var sidebar = document.querySelector('.sidebar');
            var toggleBtn = document.querySelector('[onclick="toggleSidebar()"]');
            
            if (window.innerWidth <= 768 && sidebar && toggleBtn) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
        
        // Format currency inputs
        function formatCurrency(input) {
            let value = input.value.replace(/\D/g, '');
            value = (value / 100).toFixed(2);
            input.value = value.replace('.', ',');
        }
        
        // Format phone inputs
        function formatPhone(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            input.value = value;
        }
        
        // Confirm actions
        function confirmAction(message) {
            return confirm(message);
        }
        
        // Loading state for forms
        function setLoading(form, loading = true) {
            var buttons = form.querySelectorAll('button[type="submit"]');
            buttons.forEach(function(btn) {
                if (loading) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="loading me-2"></span>Processando...';
                } else {
                    btn.disabled = false;
                    btn.innerHTML = btn.getAttribute('data-original-text') || 'Salvar';
                }
            });
        }
        
        // Handle form submissions
        document.addEventListener('DOMContentLoaded', function() {
            var forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    var submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
                        setLoading(form, true);
                    }
                });
            });
        });
    </script>
</body>
</html>