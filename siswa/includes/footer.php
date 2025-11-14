        <!-- Footer Content -->
        <?php if (isset($show_footer) && $show_footer): ?>
        <footer class="mt-5 py-4 bg-white border-top">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted mb-0">
                            &copy; <?php echo date('Y'); ?> <?php echo get_setting('site_name'); ?> - 
                            <?php echo get_setting('school_name'); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="text-muted mb-0">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo get_user_name(); ?> 
                            <span class="ms-2">
                                <i class="bi bi-building me-1"></i>
                                <?php echo get_user_kelas(); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Add active class to current menu item based on URL
            const currentUrl = window.location.pathname;
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentUrl.split('/').pop()) {
                    link.classList.add('active');
                }
            });
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Form validation enhancement
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
                        
                        // Re-enable after 10 seconds in case of error
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
                        }, 10000);
                    }
                });
            });
            
            // Store original button text
            document.querySelectorAll('button[type="submit"]').forEach(btn => {
                btn.setAttribute('data-original-text', btn.innerHTML);
            });
            
            // Dynamic table search (if exists)
            const searchInputs = document.querySelectorAll('[data-table-search]');
            searchInputs.forEach(input => {
                input.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const tableId = this.getAttribute('data-table-search');
                    const table = document.getElementById(tableId);
                    
                    if (table) {
                        const rows = table.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    }
                });
            });
            
            // Confirmation for delete actions
            const deleteBtns = document.querySelectorAll('[data-confirm-delete]');
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm-delete') || 'Apakah Anda yakin ingin menghapus ini?';
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
            
            // Tooltip initialization
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-resize textareas
            const textareas = document.querySelectorAll('textarea[data-auto-resize]');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
                // Initial resize
                textarea.style.height = 'auto';
                textarea.style.height = (textarea.scrollHeight) + 'px';
            });
        });
        
        // Helper function to show notifications
        function showNotification(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }
        
        // AJAX helper function
        function ajaxRequest(url, method = 'GET', data = null) {
            return fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data ? JSON.stringify(data) : null
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
        }
    </script>
    
    <?php if (isset($custom_js)): ?>
        <script>
            <?php echo $custom_js; ?>
        </script>
    <?php endif; ?>
</body>
</html>