<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Initialize variables
$error = '';
$success = '';
$pageTitle = 'Edit Topic';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: forums.php');
    exit;
}

$topic_id = (int)$_GET['id'];

try {
    // Get topic info
    $stmt = $conn->prepare("
        SELECT t.*, f.id as forum_id, f.name as forum_name 
        FROM topics t
        JOIN forums f ON t.forum_id = f.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        $_SESSION['error_message'] = "Topic not found.";
        header('Location: forums.php');
        exit;
    }
    
    // Security check - only topic author, admin, or moderator can edit
    $canEdit = false;
    
    if ($_SESSION['user_id'] == $topic['author_id']) {
        $canEdit = true; // Topic author can edit
    } else if (is_admin()) {
        $canEdit = true; // Admin can edit any topic
    } else {
        // Check if user is a moderator for this forum
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM forum_moderators 
            WHERE forum_id = ? AND user_id = ?
        ");
        $stmt->execute([$topic['forum_id'], $_SESSION['user_id']]);
        $isModerator = $stmt->fetchColumn() > 0;
        
        if ($isModerator) {
            $canEdit = true; // Moderator can edit topics in their forum
        }
    }
    
    if (!$canEdit) {
        $_SESSION['error_message'] = "You don't have permission to edit this topic.";
        header('Location: topic.php?id=' . $topic_id);
        exit;
    }
    
    // Get the first post (original post) of the topic
    $stmt = $conn->prepare("
        SELECT * FROM posts 
        WHERE topic_id = ? 
        ORDER BY post_date ASC 
        LIMIT 1
    ");
    $stmt->execute([$topic_id]);
    $firstPost = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$firstPost) {
        $_SESSION['error_message'] = "Could not find the original post.";
        header('Location: topic.php?id=' . $topic_id);
        exit;
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $content = $_POST['content'];
        
        // Validate input
        if (empty($title) || empty($content)) {
            $error = "Title and content are required.";
        } else {
            // Update topic title
            $stmt = $conn->prepare("UPDATE topics SET title = ? WHERE id = ?");
            $stmt->execute([$title, $topic_id]);
            
            // Update first post content
            $stmt = $conn->prepare("
                UPDATE posts 
                SET content = ?, edited_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$content, $firstPost['id']]);
            
            $_SESSION['success_message'] = "Topic has been updated successfully.";
            header('Location: topic.php?id=' . $topic_id);
            exit;
        }
    }
    
    // Set variables for form
    $topicTitle = $topic['title'];
    $postContent = $firstPost['content'];
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="forums.php">Forums</a></li>
            <li class="breadcrumb-item"><a href="forum.php?id=<?= $topic['forum_id'] ?>"><?= htmlspecialchars($topic['forum_name']) ?></a></li>
            <li class="breadcrumb-item"><a href="topic.php?id=<?= $topic_id ?>"><?= htmlspecialchars($topic['title']) ?></a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
    
    <div class="card">
        <div class="card-header">
            <h2>Edit Topic</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label for="title" class="form-label">Topic Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($topicTitle) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="6" required><?= htmlspecialchars($postContent) ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="topic.php?id=<?= $topic_id ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 