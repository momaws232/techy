        </div> <!-- End content -->
    </div> <!-- End wrapper -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Panel Scripts -->
    <script>
        // Enable all tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Activate current sidebar item based on URL
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
            
            // Initialize toasts
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function (toastEl) {
                return new bootstrap.Toast(toastEl);
            });
            
            // Show all toasts
            toastList.forEach(toast => toast.show());
            
            // Auto-close any success alert after 5 seconds
            setTimeout(function() {
                document.querySelectorAll('.alert-success').forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            // Dark mode toggle
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.body;
            
            // Check for saved theme preference or respect OS preference
            const savedTheme = localStorage.getItem('admin-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                body.classList.add('dark-mode');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', () => {
                if (body.classList.contains('dark-mode')) {
                    body.classList.remove('dark-mode');
                    darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('admin-theme', 'light');
                } else {
                    body.classList.add('dark-mode');
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('admin-theme', 'dark');
                }
            });

            // Add animation classes to cards
            document.querySelectorAll('.card').forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });
        
        // Confirm delete actions
        function confirmDelete(event, message) {
            if (!confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.')) {
                event.preventDefault();
                return false;
            }
            return true;
        }
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Show loading overlay when submitting forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                // Only show loading for forms that are not quick actions or have the no-loading class
                if (!this.classList.contains('no-loading') && !this.closest('.modal')) {
                    showLoading();
                }
            });
        });
        
        // Show loading overlay
        function showLoading() {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="spinner-border spinner-border-lg text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-text mt-3">Processing your request...</div>
            `;
            document.body.appendChild(overlay);
        }
        
        // Create a toast notification
        function createToast(title, message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            
            // Set CSS classes based on type
            let bgClass = 'bg-success';
            let iconClass = 'fa-check-circle';
            
            if (type === 'error') {
                bgClass = 'bg-danger';
                iconClass = 'fa-exclamation-circle';
            } else if (type === 'warning') {
                bgClass = 'bg-warning';
                iconClass = 'fa-exclamation-triangle';
            } else if (type === 'info') {
                bgClass = 'bg-info';
                iconClass = 'fa-info-circle';
            }
            
            // Create toast HTML
            toast.className = `toast admin-toast fade-in`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header ${bgClass} text-white">
                    <i class="fas ${iconClass} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <small>Just now</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            
            // Add to container and show
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove after hiding
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }

        // Modal handling improvements
        document.addEventListener('DOMContentLoaded', () => {
            // Fix for multiple modals and backdrop issues
            const fixModals = () => {
                const modals = document.querySelectorAll('.modal');
                
                modals.forEach(modal => {
                    // Clean up backdrops on modal hide
                    modal.addEventListener('hidden.bs.modal', function () {
                        // Remove any stray backdrops
                        const extraBackdrops = document.querySelectorAll('.modal-backdrop:not(:first-child)');
                        extraBackdrops.forEach(backdrop => backdrop.remove());
                        
                        // Fix scrolling
                        if (document.querySelectorAll('.modal.show').length === 0) {
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }
                    });
                    
                    // Fix z-index for nested modals
                    modal.addEventListener('show.bs.modal', function () {
                        const openModals = document.querySelectorAll('.modal.show');
                        if (openModals.length > 0) {
                            // If there are already open modals, set z-index higher
                            const highestZIndex = Math.max(...Array.from(openModals).map(m => +getComputedStyle(m).zIndex));
                            modal.style.zIndex = highestZIndex + 2;
                            
                            // Find and position the backdrop properly
                            setTimeout(() => {
                                const backdrop = document.querySelector('.modal-backdrop:last-child');
                                if (backdrop) {
                                    backdrop.style.zIndex = highestZIndex + 1;
                                }
                            }, 10);
                        }
                    });
                    
                    // Fix for modal shifting
                    modal.addEventListener('shown.bs.modal', function() {
                        const body = document.body;
                        body.style.paddingRight = '0';
                        document.querySelector('.navbar-admin').style.paddingRight = '0';
                    });
                });
            };
            
            // Run the fix immediately and after any AJAX content loads
            fixModals();
            
            // Fix for the custom modal glitching when clicking buttons
            document.addEventListener('click', function(event) {
                const isButton = event.target.tagName === 'BUTTON' || 
                                event.target.closest('button') || 
                                event.target.tagName === 'A' || 
                                event.target.closest('a');
                
                // If it's a button within a modal, prevent any default body scrolling
                if (isButton && event.target.closest('.modal-content')) {
                    event.stopPropagation();
                    
                    // For form submissions inside modals
                    const form = event.target.closest('form');
                    if (form && event.target.type === 'submit') {
                        // Let the form submission happen naturally
                        // but prevent the modal from moving around
                        setTimeout(() => {
                            document.body.classList.add('modal-open');
                        }, 10);
                    }
                }
            }, true);
            
            // For dynamically added modals
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.addedNodes.length) {
                        Array.from(mutation.addedNodes).forEach(node => {
                            if (node.classList && node.classList.contains('modal')) {
                                fixModals();
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
</body>
</html>
