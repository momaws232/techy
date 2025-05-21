<?php
/**
 * Post Likes Helper Functions
 * These functions assist with likes-related functionality across the forum
 */

/**
 * Get the number of likes for a post
 *
 * @param PDO $pdo Database connection
 * @param int $post_id The post ID
 * @return int The number of likes
 */
function get_post_likes_count($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchColumn();
}

/**
 * Check if a user has liked a post
 *
 * @param PDO $pdo Database connection
 * @param int $post_id The post ID
 * @param int $user_id The user ID
 * @return bool True if user has liked the post, false otherwise
 */
function has_user_liked_post($pdo, $post_id, $user_id) {
    if (!$user_id) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get the users who have liked a post (useful for showing who liked a post)
 *
 * @param PDO $pdo Database connection
 * @param int $post_id The post ID
 * @param int $limit Optional limit on number of users to return
 * @return array Array of user data
 */
function get_post_likers($pdo, $post_id, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.profile_image 
        FROM post_likes pl
        JOIN users u ON pl.user_id = u.id
        WHERE pl.post_id = ?
        ORDER BY pl.created_at DESC
        LIMIT ?
    ");
    // Explicitly bind parameters with proper types
    $stmt->bindParam(1, $post_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get the most liked posts (useful for showing popular content)
 *
 * @param PDO $pdo Database connection
 * @param int $limit Optional limit on number of posts to return
 * @return array Array of post data with like counts
 */
function get_most_liked_posts($pdo, $limit = 5) {
    // Convert limit to integer and use it directly in the query for MySQL compatibility
    $limit = (int)$limit;
    $stmt = $pdo->prepare("
        SELECT p.id, p.content, p.post_date, p.topic_id, 
               t.title as topic_title, 
               u.username as author_name,
               COUNT(pl.id) as likes_count
        FROM posts p
        JOIN topics t ON p.topic_id = t.id
        JOIN users u ON p.author_id = u.id
        LEFT JOIN post_likes pl ON p.id = pl.post_id
        GROUP BY p.id
        ORDER BY likes_count DESC
        LIMIT $limit
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
