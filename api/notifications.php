<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and notification functions
require_once '../config/database.php';
require_once '../config/notification_helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Verify AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access not allowed']);
    exit;
}

// Handle requests
$action = $_REQUEST['action'] ?? '';
$response = ['success' => false];

switch ($action) {
    case 'get_notifications':
        // Get user notifications from session
        $notifications = [];
        
        if (isset($_SESSION['login_success'])) {
            $notifications[] = [
                'type' => 'success',
                'title' => 'Welcome back, ' . htmlspecialchars($_SESSION['username']) . '!',
                'message' => 'You\'ve successfully logged in. Enjoy your experience on our forum.',
                'id' => 'login-notification-' . time()
            ];
            unset($_SESSION['login_success']);
        }
        
        if (isset($_SESSION['post_success'])) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Post published successfully!',
                'message' => htmlspecialchars($_SESSION['post_success']),
                'id' => 'post-notification-' . time()
            ];
            unset($_SESSION['post_success']);
        }
        
        if (isset($_SESSION['profile_updated'])) {
            $notifications[] = [
                'type' => 'success',
                'title' => 'Profile updated!',
                'message' => 'Your profile information has been successfully updated.',
                'id' => 'profile-notification-' . time(),
                'buttons' => [
                    ['text' => 'View Profile', 'url' => 'profile.php'],
                    ['text' => 'Dismiss', 'url' => '#']
                ]
            ];
            unset($_SESSION['profile_updated']);
        }
        
        if (isset($_SESSION['error_message'])) {
            $notifications[] = [
                'type' => 'error',
                'title' => 'Oops! Something went wrong',
                'message' => htmlspecialchars($_SESSION['error_message']),
                'id' => 'error-notification-' . time()
            ];
            unset($_SESSION['error_message']);
        }
        
        // Check for notification general system
        if (isset($_SESSION['notification'])) {
            $notif = $_SESSION['notification'];
            $notifications[] = [
                'type' => $notif['type'],
                'title' => ucfirst($notif['type']),
                'message' => htmlspecialchars($notif['message']),
                'id' => 'system-notification-' . time()
            ];
            unset($_SESSION['notification']);
        }
        
        // If user is logged in, also get DB notifications (if you implement this)
        if (isset($_SESSION['user_id'])) {
            // Example: Get unread notifications from database
            try {
                $stmt = $pdo->prepare("SELECT id, type, title, message, created_at FROM user_notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
                $stmt->execute([$_SESSION['user_id']]);
                $dbNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($dbNotifications as $notification) {
                    $notifications[] = [
                        'type' => $notification['type'],
                        'title' => $notification['title'],
                        'message' => $notification['message'],
                        'id' => 'db-notification-' . $notification['id'],
                        'timestamp' => strtotime($notification['created_at']),
                        'database_id' => $notification['id']
                    ];
                }
                
                // Mark notifications as read
                if (!empty($dbNotifications)) {
                    $ids = array_column($dbNotifications, 'id');
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                }
            } catch (PDOException $e) {
                // Silently fail - this is just for enhancement, not critical functionality
            }
        }
        
        $response['success'] = true;
        $response['notifications'] = $notifications;
        break;
        
    case 'mark_read':
        // Mark notification as read in database
        if (isset($_SESSION['user_id']) && isset($_POST['notification_id'])) {
            try {
                $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['notification_id'], $_SESSION['user_id']]);
                $response['success'] = true;
            } catch (PDOException $e) {
                $response['error'] = 'Failed to mark notification as read';
            }
        } else {
            $response['error'] = 'User ID or notification ID not provided';
        }
        break;
        
    default:
        $response['error'] = 'Unknown action';
        break;
}

// Output JSON response
echo json_encode($response);
exit; 