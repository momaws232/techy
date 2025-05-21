<?php
// Initialize the database connection
require_once 'config/database.php';

try {
    echo "<h2>Database Structure Verification</h2>";
    
    // Check products table
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Products Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column" . ($column == 'created_at' ? " ✓" : "") . "</li>";
    }
    echo "</ul>";
    
    // Check news table
    $stmt = $pdo->query("DESCRIBE news");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>News Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column" . ($column == 'image' ? " ✓" : "") . "</li>";
    }
    echo "</ul>";
    
    // Check users table
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Users Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column" . ($column == 'fullname' ? " ✓" : "") . "</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='index.php'>Return to homepage</a></p>";
} catch (PDOException $e) {
    echo "Error checking database structure: " . $e->getMessage();
}
?>
