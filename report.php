<?php
// Include necessary files
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'config/report_helpers.php';
require_once 'config/error_logger.php';

// Ensure user is logged in
if (!is_logged_in()) {
    $_SESSION['error_message'] = "You must be logged in to report content.";
    header('Location: login.php');
    exit;
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $contentType = $_POST['content_type'] ?? '';
    $contentId = (int)($_POST['content_id'] ?? 0);
    $contentOwnerId = (int)($_POST['content_owner_id'] ?? 0);
    $reportReason = $_POST['report_reason'] ?? '';
    $reportDetails = $_POST['report_details'] ?? '';
    $returnUrl = $_POST['return_url'] ?? 'index.php';
    
    // Validate data
    $errors = [];
    
    if (!in_array($contentType, ['post', 'topic', 'user', 'other'])) {
        $errors[] = "Invalid content type.";
    }
    
    if ($contentId <= 0) {
        $errors[] = "Invalid content ID.";
    }
    
    if (empty($reportReason)) {
        $errors[] = "Please select a reason for your report.";
    }
    
    // Verify content exists
    try {
        $contentExists = false;
        
        switch ($contentType) {
            case 'post':
                $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
                $stmt->execute([$contentId]);
                $contentExists = $stmt->rowCount() > 0;
                break;
                
            case 'topic':
                $stmt = $pdo->prepare("SELECT id FROM topics WHERE id = ?");
                $stmt->execute([$contentId]);
                $contentExists = $stmt->rowCount() > 0;
                break;
                
            case 'user':
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->execute([$contentId]);
                $contentExists = $stmt->rowCount() > 0;
                break;
                
            default:
                $contentExists = true; // For 'other' type, assume it exists
        }
        
        if (!$contentExists) {
            $errors[] = "The reported content does not exist.";
        }
        
    } catch (PDOException $e) {
        log_error("Error verifying content for report: " . $e->getMessage());
        $errors[] = "An error occurred while verifying the content.";
    }
    
    // Check if user is trying to report their own content
    if ($contentOwnerId == $_SESSION['user_id']) {
        $errors[] = "You cannot report your own content.";
    }
    
    // Check if this content has already been reported by this user
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM reports 
            WHERE content_type = ? AND content_id = ? AND reported_by = ? AND status = 'pending'
        ");
        $stmt->execute([$contentType, $contentId, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "You have already reported this content. Your report is pending review.";
        }
    } catch (PDOException $e) {
        log_error("Error checking existing reports: " . $e->getMessage());
        $errors[] = "An error occurred while checking existing reports.";
    }
    
    // If no errors, create the report
    if (empty($errors)) {
        // Combine reason and details
        $fullReason = $reportReason;
        if (!empty($reportDetails)) {
            $fullReason .= ": " . $reportDetails;
        }
        
        // Create the report
        if (create_report($pdo, $contentType, $contentId, $contentOwnerId, $fullReason, $_SESSION['user_id'])) {
            // Log the report
            log_user_activity("reported $contentType", $_SESSION['user_id'], [
                'content_type' => $contentType,
                'content_id' => $contentId
            ]);
            
            $_SESSION['success_message'] = "Thank you for your report. It has been submitted for review by our moderators.";
        } else {
            $_SESSION['error_message'] = "There was an error submitting your report. Please try again.";
        }
    } else {
        // Set error message
        $_SESSION['error_message'] = implode(" ", $errors);
    }
    
    // Redirect back to the page
    header("Location: $returnUrl");
    exit;
} else {
    // If accessed directly without POST data, redirect to home
    header('Location: index.php');
    exit;
} 