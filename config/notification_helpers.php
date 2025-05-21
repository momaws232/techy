<?php
/**
 * Notification Helper Functions
 * These functions assist with notifications functionality across the forum
 */

/**
 * Generate a notification card HTML
 *
 * @param string $type The notification type (green, blue, red)
 * @param string $title The notification title
 * @param string $message The notification message
 * @param string $id Optional unique ID for the notification
 * @param array $buttons Optional array of buttons [['text' => 'Button Text', 'url' => 'url.php', 'class' => 'additional-class']]
 * @return string The HTML for the notification card
 */
function generate_notification_card($type, $title, $message, $id = null, $buttons = []) {
    // Generate a random ID if none provided
    if (!$id) {
        $id = 'notification-' . bin2hex(random_bytes(5));
    }
    
    // Icon based on type
    $icon = '';
    switch ($type) {
        case 'green':
            $icon = '<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />';
            break;
        case 'blue':
            $icon = '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm.53 5.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72v5.69a.75.75 0 0 0 1.5 0v-5.69l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />';
            break;
        case 'red':
            $icon = '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />';
            break;
        default:
            $icon = '<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm0 8.625a1.125 1.125 0 100 2.25 1.125 1.125 0 000-2.25zM12 7.5a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V8.25A.75.75 0 0112 7.5z" clip-rule="evenodd" />';
    }
    
    // Start building HTML
    $html = '<div class="notify-card notify-' . $type . ' notify-fade-out" id="' . $id . '">';
    
    // Header with close button
    $html .= '<div class="notify-card-header">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="notify-close" onclick="closeNotification(\'' . $id . '\')">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </div>';
    
    // Body with icon and message
    $html .= '<div class="notify-card-body">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="notify-icon">
            ' . $icon . '
        </svg>
        <div>
            <h3>' . htmlspecialchars($title) . '</h3>
            <p>' . htmlspecialchars($message) . '</p>
        </div>
    </div>';
    
    // Progress/buttons
    $html .= '<div class="notify-progress">';
    
    // Add buttons if provided
    if (!empty($buttons)) {
        foreach ($buttons as $index => $button) {
            $class = $index === 0 ? 'notify-btn-first' : 'notify-btn-second';
            if (!empty($button['class'])) {
                $class .= ' ' . $button['class'];
            }
            
            $url = !empty($button['url']) ? $button['url'] : '#';
            $onclick = $url === '#' ? ' onclick="closeNotification(\'' . $id . '\')"' : '';
            
            $html .= '<a href="' . $url . '" class="' . $class . '"' . $onclick . '>' . htmlspecialchars($button['text']) . '</a>';
        }
    } else {
        // Default dismiss button
        $html .= '<a href="#" class="notify-btn-first" onclick="closeNotification(\'' . $id . '\')">Dismiss</a>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Store a notification in the session to be displayed on the next page load
 *
 * @param string $type The notification type (success, info, error)
 * @param string $message The notification message
 * @param string $sessionKey The session key to use
 */
function set_notification($type, $message, $sessionKey = 'notification') {
    $_SESSION[$sessionKey] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Check if there's a notification in the session and display it
 * 
 * @param string $sessionKey The session key to check
 * @return string|null The notification HTML or null if no notification
 */
function display_notification($sessionKey = 'notification') {
    if (isset($_SESSION[$sessionKey])) {
        $notification = $_SESSION[$sessionKey];
        
        // Map notification types to card types
        $typeMap = [
            'success' => 'green',
            'info' => 'blue',
            'error' => 'red',
            'warning' => 'red'
        ];
        
        // Map notification types to titles
        $titleMap = [
            'success' => 'Success',
            'info' => 'Information',
            'error' => 'Error',
            'warning' => 'Warning'
        ];
        
        $type = $typeMap[$notification['type']] ?? 'blue';
        $title = $titleMap[$notification['type']] ?? 'Notification';
        
        // Clear the notification from session
        unset($_SESSION[$sessionKey]);
        
        // Return the notification HTML
        return generate_notification_card($type, $title, $notification['message']);
    }
    
    return null;
} 