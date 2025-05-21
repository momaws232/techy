<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get post data
$data = json_decode(file_get_contents('php://input'), true);
$topic_id = isset($data['topic_id']) ? (int)$data['topic_id'] : 0;
$last_update = isset($data['last_update']) ? $data['last_update'] : null;

if ($topic_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid topic ID']);
    exit;
}

try {    // Check if there are new posts since last update
    $params = [$topic_id];
    $sql = "SELECT COUNT(*) FROM posts WHERE topic_id = ?";
    
    if ($last_update) {
        $sql .= " AND (post_date > ? OR edited_at > ?)";
        $params[] = $last_update;
        $params[] = $last_update;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $new_post_count = $stmt->fetchColumn();
    
    // For better debugging, get the newest post date
    $stmt = $conn->prepare("SELECT MAX(post_date) FROM posts WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    $newest_post_date = $stmt->fetchColumn();
    
    // Get updated like counts for all posts in the topic
    $stmt = $conn->prepare("
        SELECT p.id, 
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
               " . (is_logged_in() ? "(SELECT COUNT(*) > 0 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_likes," : "0 as user_likes,") . "
               p.post_date,
               p.edited_at
        FROM posts p
        WHERE p.topic_id = ?
    ");
    
    if (is_logged_in()) {
        $stmt->execute([$_SESSION['user_id'], $topic_id]);
    } else {
        $stmt->execute([$topic_id]);
    }
    
    $post_data = $stmt->fetchAll(PDO::FETCH_ASSOC);    echo json_encode([
        'success' => true,
        'has_new_posts' => $new_post_count > 0,
        'post_data' => $post_data,
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => [
            'new_post_count' => $new_post_count,
            'topic_id' => $topic_id,
            'last_update' => $last_update,
            'newest_post_date' => $newest_post_date,
            'now' => date('Y-m-d H:i:s'),
            'server_time' => time()
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
