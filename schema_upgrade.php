<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Schema Upgrade</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        body { padding-top: 20px; padding-bottom: 40px; }
        .upgrade-item { margin-bottom: 15px; padding: 10px; border-radius: 5px; }
        .upgrade-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .upgrade-warning { background-color: #fff3cd; border-color: #ffeeba; color: #856404; }
        .upgrade-skipped { background-color: #e2e3e5; border-color: #d6d8db; color: #383d41; }
        .upgrade-error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
<div class='container'>
    <h1 class='mb-4'>Database Schema Upgrade</h1>
    <div class='card mb-4'>
        <div class='card-header bg-primary text-white'>
            <h5 class='card-title mb-0'>Running Schema Upgrades</h5>
        </div>
        <div class='card-body'>
";

// Define upgrades - each entry has:
// - table: the table name
// - check: SQL to check if upgrade is needed
// - sql: The SQL to execute for the upgrade
// - description: Human readable description of what the upgrade does
$upgrades = [
    [
        'table' => 'forums',
        'check' => "SHOW COLUMNS FROM forums WHERE Field = 'id' AND Type = 'varchar(50)' AND `Default` IS NULL",
        'sql' => "ALTER TABLE forums MODIFY COLUMN id VARCHAR(50) NOT NULL",
        'description' => "Ensure forums.id column is set to NOT NULL"
    ],
    [
        'table' => 'forums',
        'check' => "SHOW COLUMNS FROM forums WHERE Field = 'category' AND Type != 'varchar(100)'",
        'sql' => "ALTER TABLE forums MODIFY COLUMN category VARCHAR(100) NOT NULL",
        'description' => "Ensure forums.category column is VARCHAR(100)"
    ],
    [
        'table' => 'users',
        'check' => "SHOW COLUMNS FROM users WHERE Field = 'last_login' AND Type != 'datetime'",
        'sql' => "ALTER TABLE users MODIFY COLUMN last_login DATETIME NULL DEFAULT NULL",
        'description' => "Set proper last_login datetime type with proper NULL default"
    ],
    [
        'table' => 'posts',
        'check' => "SHOW COLUMNS FROM posts WHERE Field = 'content' AND Type != 'text'",
        'sql' => "ALTER TABLE posts MODIFY COLUMN content TEXT NOT NULL",
        'description' => "Ensure posts.content is TEXT type and NOT NULL"
    ],
    [
        'table' => 'settings',
        'check' => "SHOW COLUMNS FROM settings WHERE Field = 'setting_name' AND `Key` != 'UNI'",
        'sql' => "ALTER TABLE settings ADD UNIQUE INDEX (setting_name)",
        'description' => "Add unique index to settings.setting_name"
    ],
    [
        'table' => 'profanity_filters',
        'check' => "SHOW TABLES LIKE 'profanity_filters'",
        'sql' => "CREATE TABLE IF NOT EXISTS profanity_filters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            word VARCHAR(50) NOT NULL UNIQUE,
            replacement VARCHAR(50) NOT NULL,
            created_by INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'description' => "Create profanity_filters table if it doesn't exist"
    ],
    [
        'table' => 'products',
        'check' => "SHOW COLUMNS FROM products WHERE Field = 'amazon_link'",
        'sql' => "ALTER TABLE products 
                 ADD COLUMN amazon_link VARCHAR(255) DEFAULT NULL,
                 MODIFY COLUMN image VARCHAR(255) DEFAULT NULL",
        'description' => "Add amazon_link column to products table"
    ]
];

// Track upgrade results
$success_count = 0;
$skipped_count = 0;
$error_count = 0;

// Run each upgrade
foreach ($upgrades as $upgrade) {
    echo "<div class='upgrade-item'>";
    
    try {
        // Check if the table exists first
        $tableExists = $pdo->query("SHOW TABLES LIKE '{$upgrade['table']}'")->rowCount() > 0;
        
        if (!$tableExists && strpos($upgrade['sql'], 'CREATE TABLE') === false) {
            // Skip this upgrade if table doesn't exist and we're not creating it
            echo "<div class='upgrade-warning'>";
            echo "<strong>SKIPPED:</strong> Table {$upgrade['table']} does not exist. Skipping upgrade: {$upgrade['description']}";
            echo "</div>";
            $skipped_count++;
            continue;
        }
        
        // Check if upgrade is needed
        $check_result = $pdo->query($upgrade['check'])->fetchAll();
        
        if (empty($check_result) && strpos($upgrade['sql'], 'CREATE TABLE IF NOT EXISTS') === false) {
            // No rows returned from check, so upgrade not needed
            echo "<div class='upgrade-skipped'>";
            echo "<strong>SKIPPED:</strong> No upgrade needed for {$upgrade['table']}: {$upgrade['description']}";
            echo "</div>";
            $skipped_count++;
        } else {
            // Run the upgrade
            $pdo->exec($upgrade['sql']);
            echo "<div class='upgrade-success'>";
            echo "<strong>SUCCESS:</strong> Upgraded {$upgrade['table']}: {$upgrade['description']}";
            echo "</div>";
            $success_count++;
        }
    } catch (PDOException $e) {
        echo "<div class='upgrade-error'>";
        echo "<strong>ERROR:</strong> Failed to upgrade {$upgrade['table']}: {$e->getMessage()}";
        echo "</div>";
        $error_count++;
    }
    
    echo "</div>";
}

// Display summary
echo "<hr>";
echo "<div class='alert " . ($error_count > 0 ? "alert-warning" : "alert-success") . "'>";
echo "<h4>Upgrade Summary</h4>";
echo "<ul>";
echo "<li>Successful upgrades: {$success_count}</li>";
echo "<li>Skipped upgrades: {$skipped_count}</li>";
echo "<li>Failed upgrades: {$error_count}</li>";
echo "</ul>";
echo "</div>";

// Suggest next steps
echo "<div class='mt-4'>";
echo "<h4>Next Steps</h4>";
if ($error_count > 0) {
    echo "<p>Some upgrades failed. Please review the errors above and fix them manually.</p>";
} else {
    echo "<p>All upgrades completed successfully. Your database schema is now up to date.</p>";
}
echo "<div class='mt-3'>";
echo "<a href='verify_tables.php' class='btn btn-primary me-2'><i class='fas fa-check-circle'></i> Verify Database Tables</a>";
echo "<a href='admin/maintenance.php' class='btn btn-secondary'><i class='fas fa-arrow-left'></i> Back to Maintenance</a>";
echo "</div>";
echo "</div>";
echo "</div></div></div></body></html>";
?> 