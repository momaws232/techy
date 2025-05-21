<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate post ID
if ($post_id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Check if post exists and get topic id
    $stmt = $conn->prepare("
        SELECT p.*, t.id as topic_id, t.forum_id, t.title as topic_title, f.name as forum_name
        FROM posts p
        JOIN topics t ON p.topic_id = t.id
        JOIN forums f ON t.forum_id = f.id
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        $_SESSION['error_message'] = "Post not found.";
        header('Location: index.php');
        exit;
    }
    
    // Check if user is authorized to edit the post
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
        $_SESSION['error_message'] = "You don't have permission to edit this post.";
        header('Location: topic.php?id=' . $post['topic_id']);
        exit;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        
        if (empty($content)) {
            $error = "Post content cannot be empty.";
        } else {
            $stmt = $conn->prepare("
                UPDATE posts 
                SET content = ?, edited_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$content, $post_id]);
            
            // Log the edit action
            $stmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action, object_type, object_id, object_name) 
                VALUES (?, 'edited post', 'post', ?, NULL)
            ");
            $stmt->execute([$_SESSION['user_id'], $post_id]);
            
            $_SESSION['post_success'] = "Your post has been updated successfully.";
            header('Location: topic.php?id=' . $post['topic_id'] . '#post-' . $post_id);
            exit;
        }
    }
    
    $pageTitle = "Edit Post";
    include 'templates/header.php';
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    include 'templates/header.php';
}
?>

<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="forums.php">Forums</a></li>
            <li class="breadcrumb-item"><a href="forum.php?id=<?= $post['forum_id'] ?>"><?= htmlspecialchars($post['forum_name']) ?></a></li>
            <li class="breadcrumb-item"><a href="topic.php?id=<?= $post['topic_id'] ?>"><?= htmlspecialchars($post['topic_title']) ?></a></li>
            <li class="breadcrumb-item active">Edit Post</li>
        </ol>
    </nav>
    
    <div class="card">
        <div class="card-header">
            <h2>Edit Post</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form action="edit-post.php?id=<?= $post_id ?>" method="post">
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control editor" id="content" name="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post_id ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>