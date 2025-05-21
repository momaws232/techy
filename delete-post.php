<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// Validate post ID
if ($post_id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Get post details and check permissions
    $stmt = $conn->prepare("
        SELECT p.*, t.id as topic_id, t.forum_id, t.title as topic_title
        FROM posts p
        JOIN topics t ON p.topic_id = t.id
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        $_SESSION['error_message'] = "Post not found.";
        header('Location: index.php');
        exit;
    }
    
    // Check if user is authorized to delete the post
    $isAuthor = ($post['author_id'] == $_SESSION['user_id']);
    $isAdmin = is_admin();
    
    // Check if user is a moderator
    $isModerator = false;
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM forum_moderators 
        WHERE forum_id = ? AND user_id = ?
    ");
    $stmt->execute([$post['forum_id'], $_SESSION['user_id']]);
    $isModerator = $stmt->fetchColumn() > 0;
    
    if (!$isAuthor && !$isAdmin && !$isModerator) {
        $_SESSION['error_message'] = "You don't have permission to delete this post.";
        header('Location: topic.php?id=' . $post['topic_id']);
        exit;
    }
    
    // Check if this is the only post in the topic
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE topic_id = ?");
    $stmt->execute([$post['topic_id']]);
    $postCount = $stmt->fetchColumn();
    
    if ($postCount <= 1) {
        $_SESSION['error_message'] = "Cannot delete the only post in a topic. Delete the entire topic instead.";
        header('Location: topic.php?id=' . $post['topic_id']);
        exit;
    }
    
    // If confirmed, delete the post
    if ($confirm) {
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Delete the post
            $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            
            // Log the deletion
            $stmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action, object_type, object_id, object_name) 
                VALUES (?, 'deleted post', 'post', ?, NULL)
            ");
            $stmt->execute([$_SESSION['user_id'], $post_id]);
            
            // Update topic's last post info if needed
            $stmt = $conn->prepare("
                SELECT p.id, p.author_id, p.post_date
                FROM posts p
                WHERE p.topic_id = ?
                ORDER BY p.post_date DESC
                LIMIT 1
            ");
            $stmt->execute([$post['topic_id']]);
            $lastPost = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lastPost) {
                $stmt = $conn->prepare("
                    UPDATE topics 
                    SET last_post_at = ?, last_post_user_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$lastPost['post_date'], $lastPost['author_id'], $post['topic_id']]);
            }
            
            $conn->commit();
            $_SESSION['post_success'] = "Post deleted successfully.";
            header('Location: topic.php?id=' . $post['topic_id']);
            exit;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "Error deleting post: " . $e->getMessage();
            header('Location: topic.php?id=' . $post['topic_id']);
            exit;
        }
    }
    
    // If not confirmed, show confirmation page
    $pageTitle = "Delete Post";
    include 'templates/header.php';
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>

<div class="container">
    <div class="card text-center">
        <div class="card-header bg-danger text-white">
            <h2>Delete Post</h2>
        </div>
        <div class="card-body">
            <h5>Are you sure you want to delete this post?</h5>
            <p>This action cannot be undone.</p>
            
            <div class="card mb-4">
                <div class="card-body">
                    <p class="text-muted">Post preview:</p>
                    <div class="post-preview">
                        <?php 
                        // Use substr as a fallback if mb_substr is not available
                        $content = strip_tags($post['content']);
                        if (function_exists('mb_substr')) {
                            echo mb_substr($content, 0, 200) . '...';
                        } else {
                            echo substr($content, 0, 200) . '...';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-center gap-3">
                <a href="delete-post.php?id=<?= $post_id ?>&confirm=yes" class="btn btn-danger">Yes, Delete</a>
                <a href="topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post_id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>