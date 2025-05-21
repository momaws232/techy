<?php
/**
 * Create a report for content
 * 
 * @param PDO $pdo Database connection
 * @param string $contentType Type of content (post, topic, user, other)
 * @param int $contentId ID of the content
 * @param int $contentOwnerId ID of the content owner
 * @param string $reason Reason for the report
 * @param int $reportedBy ID of the user making the report
 * @return bool Whether the report was created successfully
 */
function create_report($pdo, $contentType, $contentId, $contentOwnerId, $reason, $reportedBy) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reports 
            (content_type, content_id, content_owner_id, reason, reported_by) 
            VALUES 
            (:content_type, :content_id, :content_owner_id, :reason, :reported_by)
        ");
        
        return $stmt->execute([
            ':content_type' => $contentType,
            ':content_id' => $contentId,
            ':content_owner_id' => $contentOwnerId,
            ':reason' => $reason,
            ':reported_by' => $reportedBy
        ]);
    } catch (PDOException $e) {
        error_log('Error creating report: ' . $e->getMessage());
        return false;
    }
}
?>