<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// Validate topic ID
if ($topic_id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Get topic details and check permissions
    $stmt = $conn->prepare("
        SELECT t.*, f.name as forum_name
        FROM topics t
        JOIN forums f ON t.forum_id = f.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        $_SESSION['error_message'] = "Topic not found.";
        header('Location: index.php');
        exit;
    }
    
    // Check if user is authorized to delete the topic
    $isAuthor = ($topic['author_id'] == $_SESSION['user_id']);
    $isAdmin = is_admin();
    
    // Check if user is a moderator
    $isModerator = false;
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM forum_moderators 
        WHERE forum_id = ? AND user_id = ?
    ");
    $stmt->execute([$topic['forum_id'], $_SESSION['user_id']]);
    $isModerator = $stmt->fetchColumn() > 0;
    
    if (!$isAuthor && !$isAdmin && !$isModerator) {
        $_SESSION['error_message'] = "You don't have permission to delete this topic.";
        header('Location: topic.php?id=' . $topic_id);
        exit;
    }
    
    // If confirmed, delete the topic and all associated posts
    if ($confirm) {
        // Start transaction
        $conn->beginTransaction();
        
        try {
            // Get posts count for activity log
            $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE topic_id = ?");
            $stmt->execute([$topic_id]);
            $postCount = $stmt->fetchColumn();
            
            // Check if topic_subscriptions table exists before attempting to delete
            $stmt = $conn->query("SHOW TABLES LIKE 'topic_subscriptions'");
            if ($stmt->rowCount() > 0) {
                // Delete any topic subscriptions if they exist
                $stmt = $conn->prepare("DELETE FROM topic_subscriptions WHERE topic_id = ?");
                $stmt->execute([$topic_id]);
            }
            
            // Check if topic_votes table exists before attempting to delete
            $stmt = $conn->query("SHOW TABLES LIKE 'topic_votes'");
            if ($stmt->rowCount() > 0) {
                // Delete any topic votes/ratings if they exist
                $stmt = $conn->prepare("DELETE FROM topic_votes WHERE topic_id = ?");
                $stmt->execute([$topic_id]);
            }
            
            // Delete all posts in the topic
            $stmt = $conn->prepare("DELETE FROM posts WHERE topic_id = ?");
            $stmt->execute([$topic_id]);
            
            // Delete the topic
            $stmt = $conn->prepare("DELETE FROM topics WHERE id = ?");
            $stmt->execute([$topic_id]);
            
            // Log the deletion
            $stmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action, object_type, object_id, object_name) 
                VALUES (?, 'deleted topic', 'topic', ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $topic_id, $topic['title']]);
            
            $conn->commit();
            $_SESSION['post_success'] = "Topic deleted successfully with all its " . $postCount . " posts.";
            header('Location: forum.php?id=' . $topic['forum_id']);
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "Error deleting topic: " . $e->getMessage();
            header('Location: topic.php?id=' . $topic_id);
            exit;
        }
    }
    
    // If not confirmed, show confirmation page
    $pageTitle = "Delete Topic";
    include 'templates/header.php';
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>

<div class="container mt-4">
    <div class="card text-center">
        <div class="card-header bg-danger text-white">
            <h2>Delete Topic</h2>
        </div>
        <div class="card-body">
            <h5>Are you sure you want to delete this topic?</h5>
            <p>This will permanently delete the topic and all replies. This action cannot be undone.</p>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?= htmlspecialchars($topic['title']) ?></h5>
                </div>
                <div class="card-body">
                    <p>In forum: <strong><?= htmlspecialchars($topic['forum_name']) ?></strong></p>
                    <p>Created: <?= format_date($topic['created_at']) ?></p>
                    <p>Views: <?= $topic['views'] ?></p>
                    
                    <?php 
                    // Get post count
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE topic_id = ?");
                    $stmt->execute([$topic_id]);
                    $postCount = $stmt->fetchColumn();
                    ?>
                    <p>Total replies: <?= $postCount - 1 ?></p>
                </div>
            </div>
            
            <div class="d-flex justify-content-center gap-3">
                <a href="delete-topic.php?id=<?= $topic_id ?>&confirm=yes" class="btn btn-danger">Yes, Delete Topic</a>
                <a href="topic.php?id=<?= $topic_id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 