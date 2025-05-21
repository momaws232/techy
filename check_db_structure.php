<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Output proper HTML headers
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Structure</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>";

// Get table structure
echo "<h1>Database Structure</h1>";
echo "<h2>Users Table Columns:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

try {
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6'>Error: Could not execute query</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='6'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

echo "</table>";

// Get ENUM values for role column
echo "<h2>Role Column ENUM Values:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Value</th></tr>";

try {
    // Query to get ENUM values for role column
    $result = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
        // Extract ENUM values from the Type field
        $type = $row['Type'];
        if (preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches)) {
            $values = explode("','", $matches[1]);
            foreach ($values as $value) {
                echo "<tr><td>" . htmlspecialchars($value) . "</td></tr>";
            }
        } else {
            echo "<tr><td>Not an ENUM type: " . htmlspecialchars($type) . "</td></tr>";
        }
    } else {
        echo "<tr><td>Could not find role column</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

echo "</table>";

echo "<p>After examining the table structure, modify your edit_profile.php file to only use columns that actually exist.</p>";
echo "</body></html>";
?>
