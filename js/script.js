document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Initialize rich text editor if element exists
    const editorElements = document.querySelectorAll('.editor');
    if (editorElements.length > 0 && typeof ClassicEditor !== 'undefined') {
        editorElements.forEach(element => {
            ClassicEditor
                .create(element)
                .catch(error => {
                    console.error(error);
                });
        });
    }
    
    // Add confirmation for delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete, [data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Handle post quick reply
    const quickReplyForm = document.getElementById('quick-reply-form');
    if (quickReplyForm) {
        quickReplyForm.addEventListener('submit', function(e) {
            const contentInput = document.getElementById('quick-reply-content');
            if (!contentInput.value.trim()) {
                e.preventDefault();
                alert('Please enter a message.');
            }
        });
    }
    
    // Handle search form
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = document.getElementById('search-input');
            if (!searchInput.value.trim()) {
                e.preventDefault();
            }
        });
    }
    
    // Live character count for post textarea
    const textareas = document.querySelectorAll('textarea[data-max-length]');
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.dataset.maxLength);
        const counterElement = document.getElementById(textarea.dataset.counterElement);
        
        if (counterElement) {
            const updateCounter = () => {
                const remaining = maxLength - textarea.value.length;
                counterElement.textContent = remaining;
                
                if (remaining < 0) {
                    counterElement.classList.add('text-danger');
                } else {
                    counterElement.classList.remove('text-danger');
                }
            };
            
            textarea.addEventListener('input', updateCounter);
            updateCounter(); // Initial count
        }
    });
    
    // Password strength meter
    const passwordInput = document.querySelector('input[type="password"][id="password"]');
    const strengthMeter = document.getElementById('password-strength-meter');
    
    if (passwordInput && strengthMeter) {
        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/\d/)) strength += 1;
            if (password.match(/[^a-zA-Z\d]/)) strength += 1;
            
            strengthMeter.value = strength;
            
            const strengthText = document.getElementById('password-strength-text');
            if (strengthText) {
                const strengthLabels = ['Weak', 'Fair', 'Good', 'Strong'];
                strengthText.textContent = strengthLabels[strength];
                
                // Update classes
                strengthText.className = 'form-text';
                if (strength === 0) strengthText.classList.add('text-danger');
                else if (strength === 1) strengthText.classList.add('text-warning');
                else if (strength === 2) strengthText.classList.add('text-info');
                else strengthText.classList.add('text-success');
            }
        });
    }
    
    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordField = document.getElementById(this.dataset.toggle);
            if (passwordField) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordField.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            }
        });
    });
    
    // Auto-expand textarea
    const autoExpandTextareas = document.querySelectorAll('textarea.auto-expand');
    autoExpandTextareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Initial height adjustment
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });
    
    // Function to close a notification
    window.closeNotification = function(notificationId) {
        const notification = document.getElementById(notificationId);
        if (notification) {
            notification.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(() => {
                notification.remove();
            }, 500);
        }
    };
    
    // Add event listeners to all notification close buttons
    document.querySelectorAll('.notify-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const notification = this.closest('.notify-card');
            if (notification) {
                notification.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }
        });
    });
    
    // Handle specific notification actions
    document.querySelectorAll('.notify-btn-first, .notify-btn-second').forEach(button => {
        if (button.getAttribute('href') === '#') {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const notification = this.closest('.notify-card');
                if (notification) {
                    notification.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }
            });
        }
    });

    // Fix modal popup behavior
    if (typeof bootstrap !== 'undefined') {
        const allModals = document.querySelectorAll('.modal');
        
        allModals.forEach(modal => {
            modal.addEventListener('show.bs.modal', function(event) {
                // Prevent body from shifting
                document.body.style.paddingRight = '0';
                document.body.classList.add('modal-open');
            });
            
            modal.addEventListener('shown.bs.modal', function(event) {
                // Keep body fixed when modal is shown
                document.body.style.overflow = 'hidden';
                document.body.style.paddingRight = '0';
            });
            
            modal.addEventListener('hidden.bs.modal', function(event) {
                // Fix scrolling when modal is closed
                if (document.querySelectorAll('.modal.show').length === 0) {
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    document.body.classList.remove('modal-open');
                }
                
                // Remove any excess backdrops
                const extraBackdrops = document.querySelectorAll('.modal-backdrop:not(:first-child)');
                extraBackdrops.forEach(backdrop => backdrop.remove());
            });
        });
        
        // Fix for clicking buttons inside modals
        document.addEventListener('click', function(event) {
            const modalContent = event.target.closest('.modal-content');
            if (modalContent) {
                const isButton = event.target.tagName === 'BUTTON' || 
                                event.target.closest('button') || 
                                event.target.tagName === 'A' || 
                                event.target.closest('a');
                
                if (isButton) {
                    // Prevent default body scrolling behavior
                    event.stopPropagation();
                }
            }
        }, true);
    }
});
