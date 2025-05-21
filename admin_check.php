<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if we're already logged in as admin
$isAdmin = is_logged_in() && is_admin();

// Redirect to the new maintenance.php page
header('Location: admin/maintenance.php');
exit;

echo "<html><head><title>Admin Check Tool</title>";
echo "<style>body{font-family:Arial,sans-serif; margin:20px; line-height:1.6;}
.container{max-width:800px; margin:0 auto; padding:20px; border:1px solid #ddd; border-radius:5px;}
h1{color:#333;} 
.success{color:green; padding:10px; background:#e8f5e9; border-radius:5px; margin:10px 0;}
.error{color:red; padding:10px; background:#ffebee; border-radius:5px; margin:10px 0;}
table{width:100%; border-collapse:collapse; margin:15px 0;}
table th, table td{padding:8px; text-align:left; border-bottom:1px solid #ddd;}
input[type='text'], input[type='password'], input[type='email']{width:100%; padding:8px; margin:5px 0;}
button, .button{background:#4CAF50; color:white; padding:10px 15px; border:none; cursor:pointer; border-radius:4px;}
</style></head><body>";
echo "<div class='container'>";
echo "<h1>Admin Check Tool</h1>";

// Action handler
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && !empty($_POST['username']) && !empty($_POST['password'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'] ?? $username . '@example.com';
        
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                echo "<div class='error'>Username already exists!</div>";
            } else {
                // Create admin user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, joined_date) 
                                    VALUES (?, ?, ?, 'admin', 'active', NOW())");
                $stmt->execute([$username, $email, $password]);
                
                echo "<div class='success'>Admin user created successfully! You can now login with these credentials.</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>Database error: " . $e->getMessage() . "</div>";
        }
    }
    
    if ($_POST['action'] === 'add_profanity' && !empty($_POST['word']) && !empty($_POST['replacement'])) {
        $word = $_POST['word'];
        $replacement = $_POST['replacement'];
        
        try {
            // Get user ID for created_by
            $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            
            // Add to profanity filter
            $stmt = $pdo->prepare("INSERT INTO profanity_filters (word, replacement, created_by) VALUES (?, ?, ?)
                                  ON DUPLICATE KEY UPDATE replacement = VALUES(replacement)");
            $stmt->execute([$word, $replacement, $created_by]);
            
            echo "<div class='success'>Word added to profanity filter!</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>Database error: " . $e->getMessage() . "</div>";
        }
    }
}

// Display admin status
if ($isAdmin) {
    echo "<div class='success'>You are currently logged in as an administrator.</div>";
} else {
    echo "<div class='error'>You are not logged in as an administrator.</div>";
}

// Check if admin users exist
$stmt = $pdo->query("SELECT id, username, email, role FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Current Admin Users</h2>";
if (empty($admins)) {
    echo "<p>No admin users found in the database.</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td>{$admin['email']}</td>";
        echo "<td>{$admin['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Create admin form
echo "<h2>Create Admin User</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='action' value='create'>";
echo "<div><label>Username:</label><br><input type='text' name='username' required></div>";
echo "<div><label>Email:</label><br><input type='email' name='email'></div>";
echo "<div><label>Password:</label><br><input type='password' name='password' required></div>";
echo "<div style='margin-top:10px;'><button type='submit'>Create Admin User</button></div>";
echo "</form>";

// Check profanity filters
echo "<h2>Profanity Filters</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM profanity_filters ORDER BY word");
    $filters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($filters)) {
        echo "<p>No profanity filters found in the database.</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>Word</th><th>Replacement</th></tr>";
        foreach ($filters as $filter) {
            echo "<tr>";
            echo "<td>{$filter['word']}</td>";
            echo "<td>{$filter['replacement']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>Could not retrieve profanity filters. The table may not exist yet.</div>";
    
    // Offer to create the table
    echo "<div style='margin-top:15px;'>";
    echo "<form method='post' action='create_profanity_table.php'>";
    echo "<button type='submit'>Create Profanity Filter Table</button>";
    echo "</form>";
    echo "</div>";
}

// Add profanity form
echo "<h2>Add Profanity Filter</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='action' value='add_profanity'>";
echo "<div><label>Word to Filter:</label><br><input type='text' name='word' required></div>";
echo "<div><label>Replacement:</label><br><input type='text' name='replacement' required placeholder='e.g. ***'></div>";
echo "<div style='margin-top:10px;'><button type='submit'>Add Filter</button></div>";
echo "</form>";

echo "<div style='margin-top:20px;'>";
echo "<p><a href='login.php' class='button'>Go to Login Page</a></p>";
echo "</div>";

echo "</div></body></html>";
?> 