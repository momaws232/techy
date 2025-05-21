<?php
// Simple file serving script for attachments
require_once 'config/database.php';

// Get the requested filename
$filename = isset($_GET['path']) ? basename($_GET['path']) : '';

if (empty($filename)) {
    header('HTTP/1.0 404 Not Found');
    echo "No file specified";
    exit;
}

// Direct path to attachments folder - keep this simple
$file_path = __DIR__ . '/uploads/attachments/' . $filename;

// Debug mode for troubleshooting
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;

if ($debug) {
    header('Content-Type: text/plain');
    echo "Debug Information:\n";
    echo "Requested filename: " . $filename . "\n";
    echo "Full path: " . $file_path . "\n";
    echo "File exists: " . (file_exists($file_path) ? 'Yes' : 'No') . "\n";
    echo "Current directory: " . __DIR__ . "\n";
    exit;
}

// Check if file exists
if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    echo "File not found";
    exit;
}

// Get extension
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Map common extensions to MIME types
$mime_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'pdf' => 'application/pdf',
    'txt' => 'text/plain'
];

// Default to binary if extension not recognized
$mime_type = isset($mime_types[$extension]) ? $mime_types[$extension] : 'application/octet-stream';

// Set headers and serve the file
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: max-age=86400'); // Cache for 24 hours

// Output the file
readfile($file_path);
exit;
