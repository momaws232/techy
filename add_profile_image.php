<?php
// This script adds the profile_image column to the users table
require_once 'config/database.php';

try {
    // Check if profile_image column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'profile_image'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Add profile_image column if it doesn't exist
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)");
        echo "Added 'profile_image' column to users table successfully.";
    } else {
        echo "The 'profile_image' column already exists in the users table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 