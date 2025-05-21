/**
 * Enhanced animations and interactive elements for modern website theme
 */
document.addEventListener('DOMContentLoaded', function() {
    // Navbar scroll effect
    const navbar = document.getElementById('mainNav');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                navbar.classList.add('navbar-shrink');
            } else {
                navbar.classList.remove('scrolled');
                navbar.classList.remove('navbar-shrink');
            }
        });
    }
    
    // Dark mode toggle functionality
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark-theme');
            document.body.classList.add('dark-theme');
            darkModeToggle.querySelector('i').classList.replace('fa-moon', 'fa-sun');
        }
        
        // Toggle dark/light theme
        darkModeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.documentElement.classList.toggle('dark-theme');
            document.body.classList.toggle('dark-theme');
            
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-moon')) {
                icon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'dark');
                document.querySelector('html').style.backgroundColor = '#121212';
            } else {
                icon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'light');
                document.querySelector('html').style.backgroundColor = '';
            }
        });
    }
    
    // Apply fade-in effect to page content
    const mainContent = document.querySelector('.container');
    if (mainContent) {
        mainContent.style.opacity = '0';
        mainContent.style.transform = 'translateY(20px)';
        mainContent.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
        
        setTimeout(() => {
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            if (this.getAttribute('href') !== "#" && this.getAttribute('data-bs-toggle') === null) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Flash effect on target element
                    targetElement.style.transition = 'background-color 0.5s ease';
                    targetElement.style.backgroundColor = 'rgba(94, 23, 235, 0.1)';
                    setTimeout(() => {
                        targetElement.style.backgroundColor = '';
                    }, 1000);
                }
            }
        });
    });
    
    // Add hover effects to cards with touch support
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        // Mouse events
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = '0 12px 25px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 3px 15px rgba(0,0,0,0.08)';
        });
        
        // Touch events for mobile
        card.addEventListener('touchstart', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.12)';
        }, {passive: true});
        
        card.addEventListener('touchend', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 3px 15px rgba(0,0,0,0.08)';
        }, {passive: true});
    });
    
    // Add staggered animation to list items
    const listItems = document.querySelectorAll('.list-group-item, .forum, .latest-topic, .news-item');
    listItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-10px)';
        item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 100 + (index * 50));
    });
    
    // Enhanced pulse animation for buttons
    const buttons = document.querySelectorAll('.btn-primary, .btn-accent');
    buttons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.animation = 'pulse 1s infinite';
        });
        
        button.addEventListener('mouseout', function() {
            this.style.animation = 'none';
        });
    });
    
    // Add keyframe animation for pulse effect if not already added
    if (!document.getElementById('pulse-animation')) {
        const style = document.createElement('style');
        style.id = 'pulse-animation';
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            /* Navbar shrinking animation */
            .navbar-shrink {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1) !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Dropdown enhancement for both mouse and touch
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        const dropdownMenu = toggle.nextElementSibling;
        if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
            // Add animation styles but don't interfere with Bootstrap's behavior
            dropdownMenu.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            
            // Add animation when dropdown opens
            toggle.addEventListener('shown.bs.dropdown', function() {
                const items = dropdownMenu.querySelectorAll('.dropdown-item');
                items.forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-10px)';
                    
                    setTimeout(() => {
                        item.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, 50 + (index * 30));
                });
            });
        }
    });
    
    // Parallax effect on header elements
    window.addEventListener('scroll', function() {
        const scrollPosition = window.scrollY;
        
        // Parallax for welcome banner
        const welcomeBanner = document.querySelector('.welcome-banner');
        if (welcomeBanner) {
            welcomeBanner.style.backgroundPosition = `center ${scrollPosition * 0.05}px`;
        }
        
        // Parallax for profile header
        const profileHeader = document.querySelector('.profile-header');
        if (profileHeader) {
            profileHeader.style.backgroundPosition = `center ${scrollPosition * 0.03}px`;
        }
    });
    
    // Handle viewport height for mobile browsers (fixes 100vh issue)
    const setVhProperty = () => {
        // Set the value of the --vh custom property to the actual height of the viewport
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    };
    
    // Set initially and on resize
    setVhProperty();
    window.addEventListener('resize', setVhProperty);
    
    // Enhance forms with floating labels
    const formControls = document.querySelectorAll('.form-control');
    formControls.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Set initial state for inputs with values
        if (input.value !== '') {
            input.parentElement.classList.add('focused');
        }
    });
    
    // Add ripple effect to buttons
    const rippleButtons = document.querySelectorAll('.btn');
    rippleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Add style for ripple effect if not already added
    if (!document.getElementById('ripple-style')) {
        const rippleStyle = document.createElement('style');
        rippleStyle.id = 'ripple-style';
        rippleStyle.textContent = `
            .btn {
                position: relative;
                overflow: hidden;
            }
            
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.4);
                width: 100px;
                height: 100px;
                margin-top: -50px;
                margin-left: -50px;
                animation: ripple-animation 0.6s;
                opacity: 0;
            }
            
            @keyframes ripple-animation {
                from {
                    transform: scale(0);
                    opacity: 1;
                }
                to {
                    transform: scale(3);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(rippleStyle);
    }
});