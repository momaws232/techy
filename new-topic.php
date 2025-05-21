<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'config/moderation_helpers.php';
require_once 'config/attachment_helpers.php';

$pageTitle = 'New Topic';
$error = '';
$success = '';
$attachment_error = '';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['forum']) || empty($_GET['forum'])) {
    header('Location: forums.php');
    exit;
}

$forum_id = clean_input($_GET['forum']);

// Check if forum exists
try {
    $stmt = $conn->prepare("SELECT * FROM forums WHERE id = ? AND status = 'active'");
    $stmt->execute([$forum_id]);
    $forum = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$forum) {
        header('Location: forums.php');
        exit;
    }
    
    $pageTitle = 'New Topic in ' . $forum['name'];
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = clean_input($_POST['title']);
        $content = $_POST['content']; // We'll allow HTML content for rich text
        
        if (empty($title) || empty($content)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Process content for moderation
            $moderation_result = moderate_content($conn, $content);
            $filtered_content = $moderation_result['filtered_content'];
            $moderation_status = $moderation_result['moderation_status'];
            
            // Process video embed URLs
            $content_with_videos = process_video_embeds($filtered_content);
            
            $conn->beginTransaction();
            try {
                // Insert topic
                $stmt = $conn->prepare("
                    INSERT INTO topics (title, forum_id, author_id, content, created_at, last_post_at, last_post_user_id, moderation_status) 
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ?, ?)
                ");
                $stmt->execute([$title, $forum_id, $_SESSION['user_id'], $content_with_videos, $_SESSION['user_id'], $moderation_status]);
                $topic_id = $conn->lastInsertId();
                
                // Insert first post
                $stmt = $conn->prepare("
                    INSERT INTO posts (topic_id, author_id, content, post_date, moderation_status) 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)
                ");
                $stmt->execute([$topic_id, $_SESSION['user_id'], $content_with_videos, $moderation_status]);
                
                // Log activity
                $stmt = $conn->prepare("
                    INSERT INTO activity_log (user_id, action, object_type, object_id, object_name) 
                    VALUES (?, 'created topic', 'topic', ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $topic_id, $title]);
                
                // Process attachments if any
                if(isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    $file_count = count($_FILES['attachments']['name']);
                    
                    for($i = 0; $i < $file_count; $i++) {
                        if($_FILES['attachments']['error'][$i] == 0) {
                            $file = [
                                'name' => $_FILES['attachments']['name'][$i],
                                'type' => $_FILES['attachments']['type'][$i],
                                'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                                'error' => $_FILES['attachments']['error'][$i],
                                'size' => $_FILES['attachments']['size'][$i]
                            ];
                            
                            $attachment_result = process_attachment($file, 'topic', $topic_id, $_SESSION['user_id'], $conn);
                            if(!$attachment_result['status']) {
                                $attachment_error = $attachment_result['message'];
                            }
                        }
                    }
                }
                
                // Flag content if it contains profanity or suspicious URLs
                if($moderation_result['should_flag']) {
                    $reason = '';
                    if($moderation_result['has_profanity']) {
                        $reason .= 'Profanity detected';
                    }
                    if($moderation_result['has_suspicious_url']) {
                        $reason .= ($reason ? ', ' : '') . 'Suspicious URL detected';
                    }
                    
                    flag_content($conn, 'topic', $topic_id, $reason, 'Automatically flagged by system', $_SESSION['user_id']);
                }
                
                $conn->commit();
                
                // Success message
                $message = "Topic created successfully!";
                if($moderation_status === 'flagged') {
                    $message .= " Your topic contains content that has been flagged for moderation review.";
                }
                if(!empty($attachment_error)) {
                    $message .= " Note: " . $attachment_error;
                }
                
                $_SESSION['success_message'] = $message;
                header("Location: topic.php?id=$topic_id");
                exit;
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = 'Error creating topic: ' . $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="forums.php">Forums</a></li>
            <li class="breadcrumb-item"><a href="forum.php?id=<?= $forum['id'] ?>"><?= htmlspecialchars($forum['name']) ?></a></li>
            <li class="breadcrumb-item active">New Topic</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            <h1>Create New Topic in <?= htmlspecialchars($forum['name']) ?></h1>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Topic Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control editor" id="content" name="content" rows="10" required></textarea>
                    <small class="text-muted">You can include YouTube or Vimeo links - they will be automatically embedded.</small>
                </div>
                
                <div class="mb-3">
                    <label for="attachments" class="form-label">Attachments (Images only, max 5MB each)</label>
                    <input type="file" class="form-control" id="attachments" name="attachments[]" multiple accept="image/*">
                    <small class="text-muted">You can upload up to 3 image files (JPEG, PNG, GIF, WEBP). Maximum file size: 5MB each.</small>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Create Topic</button>
                    <a href="forum.php?id=<?= $forum_id ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
