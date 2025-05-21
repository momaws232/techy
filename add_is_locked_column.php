<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    echo "Starting script...\n";
    
    // Check if the column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM topics LIKE 'is_locked'");
    echo "Query executed. Row count: " . $stmt->rowCount() . "\n";
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, so add it
        $sql = "ALTER TABLE topics ADD is_locked TINYINT(1) NOT NULL DEFAULT 0";
        $conn->exec($sql);
        echo "Added 'is_locked' column to topics table successfully.\n";
    } else {
        echo "The 'is_locked' column already exists in the topics table.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 