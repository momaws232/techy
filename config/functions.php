<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to clean input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Check if user is staff
function is_staff() {
    return isset($_SESSION['role']) && ($_SESSION['role'] == 'staff' || $_SESSION['role'] == 'admin');
}

// Check if user is a moderator (general check)
function is_moderator() {
    return isset($_SESSION['role']) && ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff');
}

// Format date
function format_date($date) {
    $timestamp = strtotime($date);
    return date('F j, Y, g:i a', $timestamp);
}

// Generate a secure token
function generate_token() {
    return bin2hex(random_bytes(32));
}

// Get setting value
function get_setting($db_connection, $setting_name) {
    // Handle case where null is passed
    if (!$db_connection) {
        throw new Exception("Database connection is null in get_setting function");
    }
    
    try {
        $stmt = $db_connection->prepare("SELECT setting_value FROM settings WHERE setting_name = ?");
        $stmt->execute([$setting_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['setting_value'];
        } else {
            return null;
        }
    } catch (PDOException $e) {
        // Handle error or log it
        return null;
    }
}

// Get user by ID
function get_user($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, username, email, role, status, joined_date FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Safe string truncation function (with mbstring fallback)
function safe_substr($string, $start, $length) {
    if (function_exists('mb_substr')) {
        return mb_substr($string, $start, $length);
    } else {
        // Fallback to regular substr - note this may not handle multibyte characters correctly
        return substr($string, $start, $length);
    }
}

// Get site title for all pages
function get_site_title($conn) {
    static $cachedTitle = null;
    
    if ($cachedTitle !== null) {
        return $cachedTitle;
    }
    
    try {
        $cachedTitle = get_setting($conn, 'siteTitle') ?? 'Tech Forum';
        return $cachedTitle;
    } catch (Exception $e) {
        return 'Tech Forum';
    }
}
?>
