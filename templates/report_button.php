<?php
/**
 * Report Button Template
 * 
 * This template displays a "Report" button that opens a modal dialog for reporting content.
 * 
 * Usage:
 * include 'templates/report_button.php' with the following variables:
 * - $contentType: The type of content (post, topic, user)
 * - $contentId: The ID of the content
 * - $contentOwnerId: The ID of the content owner
 * - $buttonClass: Optional CSS class for the button (default: 'btn-sm btn-outline-danger')
 * - $buttonIcon: Optional icon class (default: 'fa-flag')
 * - $buttonText: Optional button text (default: 'Report')
 */

// Default values
$buttonClass = $buttonClass ?? 'btn-sm btn-outline-danger';
$buttonIcon = $buttonIcon ?? 'fa-flag';
$buttonText = $buttonText ?? 'Report';

// Generate a unique ID for the modal
$modalId = 'reportModal_' . $contentType . '_' . $contentId;

// Only show report button to logged-in users and not to content owners
if (is_logged_in() && $_SESSION['user_id'] != $contentOwnerId):
?>
<!-- Report Button -->
<button type="button" class="btn <?php echo $buttonClass; ?>" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">
    <i class="fas <?php echo $buttonIcon; ?> me-1"></i> <?php echo $buttonText; ?>
</button>

<!-- Report Modal -->
<div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo $modalId; ?>Label">Report <?php echo ucfirst($contentType); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="report.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="content_type" value="<?php echo htmlspecialchars($contentType); ?>">
                    <input type="hidden" name="content_id" value="<?php echo htmlspecialchars($contentId); ?>">
                    <input type="hidden" name="content_owner_id" value="<?php echo htmlspecialchars($contentOwnerId); ?>">
                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    
                    <div class="mb-3">
                        <label for="report_reason_<?php echo $contentId; ?>" class="form-label">Reason for Report</label>
                        <select class="form-select" id="report_reason_<?php echo $contentId; ?>" name="report_reason" required>
                            <option value="">-- Select a Reason --</option>
                            <option value="Inappropriate content">Inappropriate content</option>
                            <option value="Harassment or bullying">Harassment or bullying</option>
                            <option value="Spam">Spam</option>
                            <option value="Offensive language">Offensive language</option>
                            <option value="Off-topic content">Off-topic content</option>
                            <option value="Other">Other (specify below)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="report_details_<?php echo $contentId; ?>" class="form-label">Additional Details</label>
                        <textarea class="form-control" id="report_details_<?php echo $contentId; ?>" name="report_details" rows="3" placeholder="Please provide more details about your report..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Reports are reviewed by moderators. Abuse of the reporting system may result in account actions.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?> 