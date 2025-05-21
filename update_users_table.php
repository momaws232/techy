<?php
require_once 'config/database.php';

// Check if admin is logged in (you might want to add proper admin authentication)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. You must be an administrator to run this script.");
}

// Check if bio column exists in the users table
$stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'bio'");
$stmt->execute();
if ($stmt->rowCount() == 0) {
    // Add bio column
    $conn->exec("ALTER TABLE users ADD COLUMN bio TEXT");
    echo "Added 'bio' column to users table.<br>";
}

// Check if profile_image column exists
$stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'profile_image'");
$stmt->execute();
if ($stmt->rowCount() == 0) {
    // Add profile_image column
    $conn->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)");
    echo "Added 'profile_image' column to users table.<br>";
}

// Check if phone column exists
$stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
$stmt->execute();
if ($stmt->rowCount() == 0) {
    // Add phone column
    $conn->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20)");
    echo "Added 'phone' column to users table.<br>";
}

// Check if address column exists
$stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'address'");
$stmt->execute();
if ($stmt->rowCount() == 0) {
    // Add address column
    $conn->exec("ALTER TABLE users ADD COLUMN address TEXT");
    echo "Added 'address' column to users table.<br>";
}

echo "Users table update completed successfully!";
?>
