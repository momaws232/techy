<?php
/*
 * Sample Database Configuration
 * 
 * Copy this file to database.php and modify the values according to your database setup.
 */

// Database connection parameters
$host = 'localhost';      // Database host (usually localhost)
$dbname = 'tech_forum';   // Your database name
$user = 'root';           // Database username
$password = '';           // Database password (blank for default XAMPP setup)

// Create PDO connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Use native prepared statements for improved performance
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // In production, you might want to log this instead of displaying it
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
