<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../config/likes_helpers.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if topic_id is provided
if (!isset($_GET['topic_id']) || empty($_GET['topic_id'])) {
    echo json_encode(['error' => 'Topic ID is required']);
    exit;
}

$topic_id = clean_input($_GET['topic_id']);
$last_post_id = isset($_GET['last_post_id']) ? (int)clean_input($_GET['last_post_id']) : 0;

try {
    // Get new posts that have been added since last_post_id
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.role, u.joined_date, u.profile_image,
               (SELECT COUNT(*) FROM posts WHERE author_id = p.author_id AND post_date <= p.post_date) as author_post_count
        FROM posts p
        JOIN users u ON p.author_id = u.id
        WHERE p.topic_id = ? AND p.id > ?
        ORDER BY p.post_date
    ");
    
    $stmt->execute([$topic_id, $last_post_id]);
    $new_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each post, get the like count and whether current user liked it
    foreach ($new_posts as &$post) {
        $post['like_count'] = get_post_likes_count($conn, $post['id']);
        $post['user_liked'] = is_logged_in() ? has_user_liked_post($conn, $post['id'], $_SESSION['user_id']) : false;
        
        // Get attachments
        $post['attachments'] = get_attachments($conn, 'post', $post['id']);
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $new_posts
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database Error: ' . $e->getMessage()
    ]);
}
?>
