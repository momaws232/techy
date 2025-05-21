<?php
/**
 * Moderation Helper Functions
 * Used for content moderation, profanity filtering, and attachment handling
 */

/**
 * Check text for profanity using the database
 *
 * @param PDO $db Database connection
 * @param string $text Text to check
 * @return array Array containing filtered text and if profanity was found
 */
function check_profanity($db, $text) {
    // Get all profanity words from database
    $stmt = $db->query("SELECT word, replacement FROM profanity_filters");
    $profanity_words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filtered_text = $text;
    $has_profanity = false;
    
    foreach ($profanity_words as $word) {
        // Case insensitive replacement
        $pattern = '/\b' . preg_quote($word['word'], '/') . '\b/i';
        if (preg_match($pattern, $text)) {
            $has_profanity = true;
            $filtered_text = preg_replace($pattern, $word['replacement'], $filtered_text);
        }
    }
    
    return [
        'filtered_text' => $filtered_text,
        'has_profanity' => $has_profanity
    ];
}

/**
 * Check for suspicious URLs
 *
 * @param string $text Text to check
 * @return array Array containing filtered text and if suspicious URLs were found
 */
function check_suspicious_urls($text) {
    // List of suspicious domains - this should be expanded or moved to database
    $suspicious_domains = [
        'example-malware.com', 
        'phishing-site.net', 
        'malicious-domain.org'
    ];
    
    $has_suspicious_url = false;
    $filtered_text = $text;
    
    // Extract URLs from text
    $url_pattern = '/(https?:\/\/[^\s<>"\']+)/i';
    preg_match_all($url_pattern, $text, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $url) {
            $domain = parse_url($url, PHP_URL_HOST);
            
            foreach ($suspicious_domains as $suspicious) {
                if (stripos($domain, $suspicious) !== false) {
                    $has_suspicious_url = true;
                    $filtered_text = str_replace($url, '[BLOCKED URL]', $filtered_text);
                    break;
                }
            }
        }
    }
    
    return [
        'filtered_text' => $filtered_text,
        'has_suspicious_url' => $has_suspicious_url
    ];
}

/**
 * Process content for moderation
 *
 * @param PDO $db Database connection
 * @param string $content Content to moderate
 * @return array Moderation results
 */
function moderate_content($db, $content) {
    // Check for profanity
    $profanity_check = check_profanity($db, $content);
    
    // Check for suspicious URLs
    $url_check = check_suspicious_urls($profanity_check['filtered_text']);
    
    $should_flag = $profanity_check['has_profanity'] || $url_check['has_suspicious_url'];
    $moderation_status = $should_flag ? 'flagged' : 'approved';
    
    return [
        'filtered_content' => $url_check['filtered_text'],
        'should_flag' => $should_flag,
        'moderation_status' => $moderation_status,
        'has_profanity' => $profanity_check['has_profanity'],
        'has_suspicious_url' => $url_check['has_suspicious_url']
    ];
}

/**
 * Process and save an uploaded file attachment
 *
 * @param array $file $_FILES array element
 * @param string $content_type Type of content (topic, post, news)
 * @param int $content_id ID of the related content
 * @param int $user_id ID of the uploader
 * @param PDO $db Database connection
 * @return array Status and message
 */
function process_attachment($file, $content_type, $content_id, $user_id, $db) {
    // Include attachment helpers if not already included
    if (!function_exists('get_standard_attachment_path')) {
        require_once __DIR__ . '/attachment_helpers.php';
    }
    
    // Check if file is valid
    if ($file['error'] != 0) {
        return ['status' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5242880) { // 5MB in bytes
        return ['status' => false, 'message' => 'File exceeds the maximum size limit of 5MB'];
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['status' => false, 'message' => 'Only JPEG, PNG, GIF and WEBP images are allowed'];
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('attachment_') . '.' . $file_extension;
    
    // Ensure attachment directories exist
    ensure_attachment_directories();
    
    // Get save path (physical location) and storage path (for DB)
    $file_path = get_attachment_save_path($new_filename);
    $db_file_path = get_standard_attachment_path($new_filename);
    
    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Save attachment record to database
        $stmt = $db->prepare("
            INSERT INTO attachments 
            (content_type, content_id, file_name, file_type, file_size, file_path, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $content_type,
            $content_id,
            $file['name'],
            $file['type'],
            $file['size'],
            $db_file_path,
            $user_id
        ]);
        
        return [
            'status' => true, 
            'message' => 'File uploaded successfully',
            'attachment_id' => $db->lastInsertId(),
            'file_path' => $db_file_path
        ];
    } else {
        return ['status' => false, 'message' => 'Failed to move uploaded file'];
    }
}

/**
 * Process content to embed videos from URLs
 *
 * @param string $content The content to process
 * @return string Content with embedded videos
 */
function process_video_embeds($content) {
    // YouTube URL patterns
    $youtube_patterns = [
        '~https?://(?:www\.)?youtube\.com/watch\?v=([^\s&]+)~i',
        '~https?://(?:www\.)?youtu\.be/([^\s]+)~i'
    ];
    
    // Loop through patterns and replace with embed code
    foreach ($youtube_patterns as $pattern) {
        $content = preg_replace_callback($pattern, function($matches) {
            $video_id = $matches[1];
            return '<div class="video-embed"><iframe width="560" height="315" src="https://www.youtube.com/embed/'.$video_id.'" frameborder="0" allowfullscreen></iframe></div>';
        }, $content);
    }
    
    // Vimeo pattern
    $vimeo_pattern = '~https?://(?:www\.)?vimeo\.com/([0-9]+)~i';
    $content = preg_replace_callback($vimeo_pattern, function($matches) {
        $video_id = $matches[1];
        return '<div class="video-embed"><iframe width="560" height="315" src="https://player.vimeo.com/video/'.$video_id.'" frameborder="0" allowfullscreen></iframe></div>';
    }, $content);
    
    return $content;
}

/**
 * Flag content for moderation
 *
 * @param PDO $db Database connection
 * @param string $content_type Type of content (topic, post, news)
 * @param int $content_id ID of the content
 * @param string $reason Reason for flagging
 * @param string $details Additional details
 * @param int $user_id User ID who flagged the content
 * @return bool Success or failure
 */
function flag_content($db, $content_type, $content_id, $reason, $details, $user_id) {
    try {
        $stmt = $db->prepare("
            INSERT INTO flagged_content 
            (content_type, content_id, reason, details, flagged_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$content_type, $content_id, $reason, $details, $user_id]);
        
        // Update the content's moderation status to flagged
        $table = ($content_type == 'topic') ? 'topics' : (($content_type == 'post') ? 'posts' : 'news');
        
        $stmt = $db->prepare("
            UPDATE $table 
            SET moderation_status = 'flagged' 
            WHERE id = ?
        ");
        
        $stmt->execute([$content_id]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get attachments for a specific content
 *
 * @param PDO $db Database connection
 * @param string $content_type Type of content
 * @param int $content_id ID of the content
 * @return array List of attachments
 */
function get_attachments($db, $content_type, $content_id) {
    // Include attachment helpers if not already included
    if (!function_exists('get_attachment_url')) {
        require_once __DIR__ . '/attachment_helpers.php';
    }
    
    $stmt = $db->prepare("
        SELECT * FROM attachments 
        WHERE content_type = ? AND content_id = ? 
        ORDER BY created_at
    ");
    
    $stmt->execute([$content_type, $content_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enhance attachments with additional useful information
    foreach ($attachments as &$attachment) {
        $original_path = $attachment['file_path'];
        $filename = basename($original_path);
        
        // Check if file exists and get accessible path
        $file_check = check_attachment_exists($original_path);
        $attachment['file_exists'] = $file_check['exists'];
        $attachment['accessible_path'] = $file_check['path'];
        
        // Add url for displaying the attachment through serve_attachment.php
        $attachment['url'] = get_attachment_url($filename);
        
        // Add filename for reference
        $attachment['filename'] = $filename;
    }
    
    return $attachments;
}
?>
