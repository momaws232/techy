<?php
// Simple script to verify PHP installation and database connection

echo "<h1>PHP Installation Verification</h1>";

// Check PHP version
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if common extensions are loaded
$requiredExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'mbstring'];
echo "<h2>Required Extensions</h2>";
echo "<ul>";
foreach ($requiredExtensions as $ext) {
    echo "<li>$ext: " . (extension_loaded($ext) ? "✓ Loaded" : "✗ Not loaded") . "</li>";
}
echo "</ul>";

// If mbstring is not loaded, provide guidance
if (!extension_loaded('mbstring')) {
    echo "<div style='background-color:#ffdddd;padding:10px;border:1px solid red;margin:10px 0;'>";
    echo "<h3>Important: mbstring Extension Missing</h3>";
    echo "<p>The mbstring extension is required but not loaded. You need to:</p>";
    echo "<ol>";
    echo "<li>Open your php.ini file (typically in C:/xampp/php/php.ini)</li>";
    echo "<li>Find the line <code>;extension=mbstring</code></li>";
    echo "<li>Remove the semicolon at the beginning to uncomment it: <code>extension=mbstring</code></li>";
    echo "<li>Save the file and restart your web server</li>";
    echo "</ol>";
    echo "</div>";
}

// Try database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'database.php';
    echo "<p style='color:green'>✓ Successfully connected to database</p>";
    
    // Try a simple query
    $result = $conn->query("SELECT 1")->fetchColumn();
    echo "<p style='color:green'>✓ Query test successful</p>";
    
    // Check if tables exist
    $tables = ['users', 'forums', 'topics', 'posts', 'news'];
    echo "<h3>Database Tables</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->rowCount() > 0;
        echo "<li>$table: " . ($exists ? "✓ Exists" : "✗ Not found") . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    
    echo "<h3>Troubleshooting</h3>";
    echo "<ol>";
    echo "<li>Make sure your database server (MySQL/MariaDB) is running</li>";
    echo "<li>Check your database configuration in database.php</li>";
    echo "<li>Verify the database exists and the user has proper permissions</li>";
    echo "</ol>";
}

// PHP configuration info
echo "<h2>PHP Configuration</h2>";
$configValues = [
    'display_errors' => ini_get('display_errors'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
foreach ($configValues as $key => $value) {
    echo "<tr><td>$key</td><td>$value</td></tr>";
}
echo "</table>";

// Server information
echo "<h2>Server Information</h2>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Server Name: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Script: " . $_SERVER['PHP_SELF'] . "</p>";

// End of file
echo "<hr><p><em>End of verification</em></p>";
?>
