<?php
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. You must be an administrator to run this script.");
}

// Get column names from users table
$stmt = $conn->prepare("SHOW COLUMNS FROM users");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Columns in users table:</h2>";
echo "<ul>";
foreach ($columns as $column) {
    echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
}
echo "</ul>";
?>
