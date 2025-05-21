<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'config/moderation_helpers.php';
require_once 'config/attachment_helpers.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validate input
$topic_id = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
$content = isset($_POST['content']) ? $_POST['content'] : '';

if ($topic_id <= 0 || empty($content)) {
    $_SESSION['error'] = 'Invalid input. Please try again.';
    header('Location: topic.php?id=' . $topic_id);
    exit;
}

try {
    // Check if topic exists
    $stmt = $pdo->prepare("SELECT id, forum_id FROM topics WHERE id = ?");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        $_SESSION['error'] = 'Topic does not exist.';
        header('Location: index.php');
        exit;
    }
    
    // Process content for moderation
    $moderation_result = moderate_content($pdo, $content);
    $filtered_content = $moderation_result['filtered_content'];
    $moderation_status = $moderation_result['moderation_status'];
    
    // Process video embed URLs
    $content_with_videos = process_video_embeds($filtered_content);
    
    $pdo->beginTransaction();
    
    // Insert reply
    $stmt = $pdo->prepare("
        INSERT INTO posts (topic_id, author_id, content, post_date, moderation_status) 
        VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)
    ");
    $stmt->execute([$topic_id, $_SESSION['user_id'], $content_with_videos, $moderation_status]);
    $post_id = $pdo->lastInsertId();
    
    // Update topic's last post info
    $stmt = $pdo->prepare("
        UPDATE topics 
        SET last_post_at = CURRENT_TIMESTAMP, last_post_user_id = ? 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $topic_id]);
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, object_type, object_id, object_name) 
        VALUES (?, 'posted reply', 'topic', ?, NULL)
    ");
    $stmt->execute([$_SESSION['user_id'], $topic_id]);
    
    // Process attachments if any
    $attachment_error = '';
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
                
                $attachment_result = process_attachment($file, 'post', $post_id, $_SESSION['user_id'], $pdo);
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
        
        flag_content($pdo, 'post', $post_id, $reason, 'Automatically flagged by system', $_SESSION['user_id']);
    }
    
    $pdo->commit();
    
    // Create success message to display in notification
    $message = "Your reply has been posted successfully.";
    if($moderation_status === 'flagged') {
        $message .= " Your post contains content that has been flagged for moderation review.";
    }
    if(!empty($attachment_error)) {
        $message .= " Note: " . $attachment_error;
    }
    
    $_SESSION['post_success'] = $message;
    
    // Redirect to the post
    header('Location: topic.php?id=' . $topic_id . '#post-' . $post_id);
    exit;
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = 'Error posting reply: ' . $e->getMessage();
    header('Location: topic.php?id=' . $topic_id);
    exit;
}
?>
