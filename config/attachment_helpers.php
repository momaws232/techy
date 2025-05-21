<?php
/**
 * Attachment Helper Functions
 * This file contains functions specifically for handling attachments in a consistent way
 */

/**
 * Get the standard attachment path for storing in the database
 * This ensures all attachment paths follow the same convention
 * 
 * @param string $filename The filename of the attachment
 * @return string The standardized path to store in the database
 */
function get_standard_attachment_path($filename) {
    // Always store with this path pattern in the database 
    return 'uploads/attachments/' . basename($filename);
}

/**
 * Get the absolute filesystem path for saving an attachment
 * 
 * @param string $filename The filename of the attachment
 * @return string The absolute filesystem path
 */
function get_attachment_save_path($filename) {
    $script_dir = dirname(dirname(__FILE__)); // Get the parent directory of config (root directory)
    return $script_dir . '/uploads/attachments/' . basename($filename);
}

/**
 * Get the URL to serve an attachment via the attachment server
 * 
 * @param string $file_path The file_path from the database, or a filename
 * @param bool $absolute Whether to return an absolute URL (with domain) or a relative URL
 * @return string The URL to access the attachment
 */
function get_attachment_url($file_path, $absolute = false) {
    // Extract just the filename from whatever path was provided
    $filename = basename($file_path);
    
    // Simplify URL generation - just use the filename directly
    // This avoids path issues with check_attachment_exists that may cause problems
    $relative_url = 'serve_attachment.php?path=' . urlencode($filename);
    
    if (!$absolute) {
        return $relative_url;
    }
    
    // Generate absolute URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($script_name);
    
    // Make sure we don't have double slashes
    $dir = rtrim($dir, '/');
    if ($dir !== '') {
        $dir .= '/';
    }
    
    return "$protocol://$host$dir$relative_url";
}

/**
 * Check if an attachment physically exists by testing various potential locations
 * 
 * @param string $file_path The file path from the database
 * @return array [exists (bool), accessible_path (string or null)]
 */
function check_attachment_exists($file_path) {
    $filename = basename($file_path);
    $script_dir = dirname(dirname(__FILE__)); // Get the parent directory of config
    
    $potential_paths = [
        // Original path
        $file_path,
        
        // Standard paths using filename only
        'uploads/attachments/' . $filename,
        'TestDatabase/uploads/attachments/' . $filename,
        
        // Absolute paths
        $script_dir . '/uploads/attachments/' . $filename,
        $script_dir . '/TestDatabase/uploads/attachments/' . $filename,
        
        // More variations
        dirname($script_dir) . '/uploads/attachments/' . $filename,
        dirname($script_dir) . '/TestDatabase/uploads/attachments/' . $filename,
        
        // Try standard path with and without TestDatabase prefix
        get_standard_attachment_path($filename),
        'TestDatabase/' . get_standard_attachment_path($filename),
    ];
    
    foreach ($potential_paths as $path) {
        if (file_exists($path) && is_file($path)) {
            return ['exists' => true, 'path' => $path];
        }
    }
    
    // Check for any images with the same name but different extension
    $base_name = pathinfo($filename, PATHINFO_FILENAME);
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    foreach ($extensions as $ext) {
        $alt_filename = $base_name . '.' . $ext;
        $alt_path = 'uploads/attachments/' . $alt_filename;
        
        if (file_exists($alt_path) && is_file($alt_path)) {
            return ['exists' => true, 'path' => $alt_path];
        }
        
        $alt_path = $script_dir . '/uploads/attachments/' . $alt_filename;
        if (file_exists($alt_path) && is_file($alt_path)) {
            return ['exists' => true, 'path' => $alt_path];
        }
    }
    
    return ['exists' => false, 'path' => null];
}

/**
 * Ensure attachment directories exist
 */
function ensure_attachment_directories() {
    $script_dir = dirname(dirname(__FILE__)); // Get the parent directory of config
    $paths = [
        $script_dir . '/uploads/attachments/',
        $script_dir . '/TestDatabase/uploads/attachments/'
    ];
    
    foreach ($paths as $path) {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
}

/**
 * Get the site root directory (absolute path)
 * 
 * @return string The absolute path to the site root
 */
function get_site_root() {
    return dirname(dirname(__FILE__)); // From config directory to parent (site root)
}

/**
 * Generate an absolute filesystem path from a relative path
 * 
 * @param string $relative_path The relative path from site root
 * @return string The absolute filesystem path
 */
function get_absolute_path($relative_path) {
    return rtrim(get_site_root(), '/') . '/' . ltrim($relative_path, '/');
}
