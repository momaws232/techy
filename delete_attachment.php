<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'config/attachment_helpers.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error_message'] = 'You must be logged in to delete attachments.';
    header('Location: login.php');
    exit;
}

// Check if an attachment ID was provided
if (!isset($_POST['attachment_id']) || empty($_POST['attachment_id'])) {
    $_SESSION['error_message'] = 'No attachment specified.';
    header('Location: index.php');
    exit;
}

$attachment_id = (int)$_POST['attachment_id'];
$return_to = isset($_POST['return_to']) ? $_POST['return_to'] : 'index.php';

try {
    // Get attachment details
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        $_SESSION['error_message'] = 'Attachment not found.';
        header("Location: $return_to");
        exit;
    }
    
    // Check if user has permission to delete this attachment
    $can_delete = false;
    
    // User can delete their own attachments
    if ($attachment['uploaded_by'] == $_SESSION['user_id']) {
        $can_delete = true;
    }
    
    // Moderators, staff and admins can delete any attachment
    if (is_moderator()) {
        $can_delete = true;
    }
    
    // Check content ownership (e.g., post author can delete attachments on their post)
    if ($attachment['content_type'] == 'post') {
        $stmt = $pdo->prepare("SELECT author_id FROM posts WHERE id = ?");
        $stmt->execute([$attachment['content_id']]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post && $post['author_id'] == $_SESSION['user_id']) {
            $can_delete = true;
        }
    }
    
    if (!$can_delete) {
        $_SESSION['error_message'] = 'You do not have permission to delete this attachment.';
        header("Location: $return_to");
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete the attachment record from the database
    $stmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    
    // Try to delete the physical file
    $file_path = $attachment['file_path'];
    $filename = basename($file_path);
    
    // Check various potential locations
    $potential_paths = [
        $file_path,
        'TestDatabase/' . $file_path,
        'uploads/attachments/' . $filename,
        'TestDatabase/uploads/attachments/' . $filename
    ];
    
    $file_deleted = false;
    foreach ($potential_paths as $path) {
        if (file_exists($path) && is_file($path)) {
            if (unlink($path)) {
                $file_deleted = true;
                break;
            }
        }
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Set success message
    if ($file_deleted) {
        $_SESSION['post_success'] = 'Attachment was deleted successfully.';
    } else {
        $_SESSION['post_success'] = 'Attachment was removed from the database, but the file could not be deleted.';
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error_message'] = 'Error deleting attachment: ' . $e->getMessage();
}

// Redirect back to the original page
header("Location: $return_to");
exit; 