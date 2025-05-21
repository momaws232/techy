<?php
// Initialize the database connection
require_once 'config/database.php';

try {
    // Update products table - add created_at column
    $pdo->exec("ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Added 'created_at' column to products table.<br>";
    
    // Update news table - add image column
    $pdo->exec("ALTER TABLE news ADD COLUMN image VARCHAR(255)");
    echo "Added 'image' column to news table.<br>";
    
    // Update users table - add fullname column
    $pdo->exec("ALTER TABLE users ADD COLUMN fullname VARCHAR(100)");
    echo "Added 'fullname' column to users table.<br>";
    
    echo "<p>Database updated successfully!</p>";
    echo "<p><a href='index.php'>Return to homepage</a></p>";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
