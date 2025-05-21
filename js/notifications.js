/**
 * Notifications JavaScript - Handles AJAX notifications
 */
document.addEventListener('DOMContentLoaded', function() {
    // Container for notifications
    let notificationsContainer = document.getElementById('notificationsContainer');
    
    // If container doesn't exist, create it
    if (!notificationsContainer) {
        notificationsContainer = document.createElement('div');
        notificationsContainer.id = 'notificationsContainer';
        notificationsContainer.className = 'notifications-container';
        document.body.appendChild(notificationsContainer);
    }
    
    // Function to close a notification
    window.closeNotification = function(notificationId) {
        const notification = document.getElementById(notificationId);
        if (notification) {
            notification.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(() => {
                notification.remove();
            }, 500);
            
            // If notification has a database ID, mark it as read via AJAX
            if (notification.dataset.databaseId) {
                fetch('api/notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=mark_read&notification_id=${notification.dataset.databaseId}`
                }).catch(error => console.error('Error marking notification as read:', error));
            }
        }
    };
    
    // Add event listeners to all notification close buttons
    document.querySelectorAll('.notify-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const notification = this.closest('.notify-card');
            if (notification) {
                closeNotification(notification.id);
            }
        });
    });
    
    // Handle automatic fade-out for notifications
    document.querySelectorAll('.notify-fade-out').forEach(notification => {
        // Reset any existing animation (in case page was reloaded)
        notification.style.animation = 'none';
        notification.offsetHeight; // Trigger reflow
        notification.style.animation = 'fadeOut 0.5s ease-out forwards';
        notification.style.animationDelay = '5s';
        
        // After animation completes, remove the notification
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.remove();
            }
        }, 5500); // 5s delay + 0.5s animation
    });
    
    // Display a notification
    function displayNotification(notification) {
        // Map type to color class
        const typeMap = {
            'success': 'green',
            'info': 'blue',
            'error': 'red',
            'warning': 'red'
        };
        
        // Create notification card
        const notificationCard = document.createElement('div');
        notificationCard.className = `notify-card notify-${typeMap[notification.type] || 'blue'} notify-fade-out`;
        notificationCard.id = notification.id || `notification-${Date.now()}`;
        
        // If this is a database notification, store its ID
        if (notification.database_id) {
            notificationCard.dataset.databaseId = notification.database_id;
        }
        
        // Icon based on type
        let icon = '';
        switch (typeMap[notification.type]) {
            case 'green':
                icon = '<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />';
                break;
            case 'blue':
                icon = '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm.53 5.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72v5.69a.75.75 0 0 0 1.5 0v-5.69l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />';
                break;
            case 'red':
                icon = '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />';
                break;
            default:
                icon = '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm0 8.625a1.125 1.125 0 100 2.25 1.125 1.125 0 000-2.25zM12 7.5a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V8.25A.75.75 0 0112 7.5z" clip-rule="evenodd" />';
        }
        
        // Build HTML structure
        notificationCard.innerHTML = `
            <div class="notify-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="notify-close" onclick="closeNotification('${notificationCard.id}')">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </div>
            <div class="notify-card-body">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="notify-icon">
                    ${icon}
                </svg>
                <div>
                    <h3>${notification.title}</h3>
                    <p>${notification.message}</p>
                </div>
            </div>
            <div class="notify-progress">
                ${notification.buttons ? 
                    notification.buttons.map((btn, index) => 
                        `<a href="${btn.url || '#'}" class="${index === 0 ? 'notify-btn-first' : 'notify-btn-second'}" ${btn.url === '#' ? `onclick="closeNotification('${notificationCard.id}')"` : ''}>${btn.text}</a>`
                    ).join('') 
                    : 
                    `<a href="#" class="notify-btn-first" onclick="closeNotification('${notificationCard.id}')">Dismiss</a>`
                }
            </div>
        `;
        
        // Add to container
        notificationsContainer.appendChild(notificationCard);
        
        // Set auto-dismiss timeout
        setTimeout(() => {
            closeNotification(notificationCard.id);
        }, 5000);
    }
    
    // Function to fetch notifications via AJAX
    function fetchNotifications() {
        fetch('api/notifications.php?action=get_notifications', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications) {
                data.notifications.forEach(notification => {
                    displayNotification(notification);
                });
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
    }
    
    // Fetch notifications when page loads
    fetchNotifications();
    
    // Set up notification polling (optional - every 30 seconds)
    // setInterval(fetchNotifications, 30000);
}); 