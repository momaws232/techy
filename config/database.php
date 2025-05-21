<?php
$host = 'localhost';
$db_name = 'techforum';
$username = 'root';
$password = '1234567890';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // For backward compatibility with existing code that uses $conn
    $conn = $pdo;
    
} catch(PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
    exit;
}
?>
