<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure request is AJAX and user is logged in
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

// Get post data
$data = json_decode(file_get_contents('php://input'), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
$action = isset($data['action']) ? $data['action'] : '';

if ($post_id <= 0 || !in_array($action, ['like', 'unlike'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Check if post exists
    $stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    if ($action === 'like') {
        // Check if already liked
        $stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        if (!$stmt->fetch()) {
            // Insert new like
            $stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $user_id]);
        }
        
        $message = 'Post liked successfully';
    } else {
        // Unlike the post
        $stmt = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $message = 'Post unliked successfully';
    }
    
    // Get updated like count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetchColumn();
    
    // Check if user likes this post
    $stmt = $conn->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $user_likes = $stmt->fetchColumn() > 0;
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'likes' => $like_count,
        'user_likes' => $user_likes
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
