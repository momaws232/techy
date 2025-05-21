<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure only admins can access this page
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = "You must be logged in as an administrator to access this page.";
    header('Location: ../login.php');
    exit;
}

// Set page title
$pageTitle = "Maintenance Tools";

// Process requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$error = '';

switch ($action) {
    case 'clear_cache':
        // Clear any cache directories
        $dirs = [
            '../cache/',
            '../tmp/'
        ];
        
        $clearedFiles = 0;
        
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                if ($handle = opendir($dir)) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != ".." && $file != ".htaccess" && $file != "index.html") {
                            if (is_file($dir . $file)) {
                                unlink($dir . $file);
                                $clearedFiles++;
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        }
        
        $message = "Cache cleared successfully. Removed {$clearedFiles} cached files.";
        break;
        
    case 'backup_db':
        // Create a database backup
        try {
            // Get all tables
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            if (empty($tables)) {
                $error = "No tables found in database.";
                break;
            }
            
            // Generate SQL backup content
            $sqlContent = "-- Tech Forum Database Backup\n";
            $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Add DROP and CREATE statements for each table
            foreach ($tables as $table) {
                // Get table creation SQL
                $tableResult = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $row = $tableResult->fetch(PDO::FETCH_NUM);
                $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sqlContent .= $row[1] . ";\n\n";
                
                // Get table data
                $dataResult = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $dataResult->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $sqlContent .= "INSERT INTO `{$table}` (`" . implode("`, `", $columns) . "`) VALUES\n";
                    
                    $rowsData = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            $rowValues[] = is_null($value) ? 'NULL' : $pdo->quote($value);
                        }
                        $rowsData[] = "(" . implode(", ", $rowValues) . ")";
                    }
                    $sqlContent .= implode(",\n", $rowsData) . ";\n\n";
                }
            }
            
            // Set headers for download
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "database_backup_{$timestamp}.sql";
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sqlContent));
            
            // Output SQL content
            echo $sqlContent;
            exit;
            
        } catch (PDOException $e) {
            $error = "Database backup failed: " . $e->getMessage();
        }
        break;
        
    case 'rebuild_stats':
        // Rebuild various statistics
        try {
            // Update topic counts
            $pdo->exec("UPDATE forums f SET 
                        f.topic_count = (SELECT COUNT(*) FROM topics t WHERE t.forum_id = f.id)");
            
            // Update post counts
            $pdo->exec("UPDATE topics t SET 
                        t.post_count = (SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.id)");
                        
            // Update last post info
            $pdo->exec("UPDATE topics t SET 
                        t.last_post_id = (SELECT p.id FROM posts p WHERE p.topic_id = t.id ORDER BY p.post_date DESC LIMIT 1),
                        t.last_post_date = (SELECT p.post_date FROM posts p WHERE p.topic_id = t.id ORDER BY p.post_date DESC LIMIT 1),
                        t.last_post_user_id = (SELECT p.author_id FROM posts p WHERE p.topic_id = t.id ORDER BY p.post_date DESC LIMIT 1)
                        WHERE EXISTS (SELECT 1 FROM posts p WHERE p.topic_id = t.id)");
            
            $message = "Forum statistics rebuilt successfully.";
        } catch (PDOException $e) {
            $error = "Failed to rebuild statistics: " . $e->getMessage();
        }
        break;
        
    case 'system_info':
        // Display system information but continue to the page
        break;
        
    case 'create_admin':
        // Create admin user
        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $username = trim($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $email = !empty($_POST['email']) ? trim($_POST['email']) : $username . '@example.com';
            
            try {
                // Check if user already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    $error = "Username already exists!";
                } else {
                    // Create admin user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, joined_date) 
                                        VALUES (?, ?, ?, 'admin', 'active', NOW())");
                    $stmt->execute([$username, $email, $password]);
                    
                    $message = "Admin user created successfully!";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Username and password are required.";
        }
        break;
        
    case 'add_profanity':
        // Add profanity filter
        if (!empty($_POST['word']) && !empty($_POST['replacement'])) {
            $word = trim($_POST['word']);
            $replacement = trim($_POST['replacement']);
            
            try {
                // Get user ID for created_by
                $created_by = $_SESSION['user_id'] ?? 1;
                
                // Check if profanity_filters table exists
                $tableExists = false;
                $tables = $pdo->query("SHOW TABLES LIKE 'profanity_filters'")->fetchAll();
                if (count($tables) > 0) {
                    $tableExists = true;
                }
                
                if ($tableExists) {
                    // Add to profanity filter
                    $stmt = $pdo->prepare("INSERT INTO profanity_filters (word, replacement, created_by) VALUES (?, ?, ?)
                                          ON DUPLICATE KEY UPDATE replacement = VALUES(replacement)");
                    $stmt->execute([$word, $replacement, $created_by]);
                    
                    $message = "Word added to profanity filter!";
                } else {
                    $error = "Profanity filter table does not exist. Please create it first.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Word and replacement are required.";
        }
        break;
        
    default:
        // No specific action requested, show the maintenance page
        break;
}

// Only include header if we're not downloading a file
if ($action !== 'backup_db') {
    include '../templates/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="sidebar-header">
                <i class="fas fa-shield-alt me-2"></i> Admin Panel
            </div>
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="moderation.php">
                            <i class="fas fa-flag me-2"></i>
                            Moderation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="users.php">
                            <i class="fas fa-users me-2"></i>
                            User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="forums.php">
                            <i class="fas fa-list me-2"></i>
                            Forum Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="settings.php">
                            <i class="fas fa-cog me-2"></i>
                            Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="maintenance.php">
                            <i class="fas fa-tools me-2"></i>
                            Maintenance
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-white" href="../index.php">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back to Forum
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Maintenance</h1>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Cache Management -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Cache Management</h5>
                        </div>
                        <div class="card-body">
                            <p>Clear all cached data from the system to ensure the latest content is displayed.</p>
                            <a href="maintenance.php?action=clear_cache" class="btn btn-primary" onclick="return confirm('Are you sure you want to clear all cached data?');">
                                <i class="fas fa-broom me-1"></i> Clear Cache
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Database Backup -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Database Backup</h5>
                        </div>
                        <div class="card-body">
                            <p>Create a backup of your forum database to prevent data loss.</p>
                            <a href="maintenance.php?action=backup_db" class="btn btn-success">
                                <i class="fas fa-download me-1"></i> Backup Database
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Rebuild -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Rebuild Statistics</h5>
                        </div>
                        <div class="card-body">
                            <p>Recalculate all forum statistics to ensure accurate counts and information.</p>
                            <a href="maintenance.php?action=rebuild_stats" class="btn btn-warning" onclick="return confirm('Are you sure you want to rebuild all statistics?');">
                                <i class="fas fa-chart-bar me-1"></i> Rebuild Statistics
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($action === 'system_info'): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <tbody>
                                            <tr>
                                                <th>PHP Version</th>
                                                <td><?= PHP_VERSION ?></td>
                                            </tr>
                                            <tr>
                                                <th>Server Software</th>
                                                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                                            </tr>
                                            <tr>
                                                <th>MySQL Version</th>
                                                <td><?= $pdo->query('SELECT VERSION()')->fetchColumn() ?></td>
                                            </tr>
                                            <tr>
                                                <th>Operating System</th>
                                                <td><?= PHP_OS ?></td>
                                            </tr>
                                            <tr>
                                                <th>Max Upload Size</th>
                                                <td><?= ini_get('upload_max_filesize') ?></td>
                                            </tr>
                                            <tr>
                                                <th>Post Max Size</th>
                                                <td><?= ini_get('post_max_size') ?></td>
                                            </tr>
                                            <tr>
                                                <th>Memory Limit</th>
                                                <td><?= ini_get('memory_limit') ?></td>
                                            </tr>
                                            <tr>
                                                <th>Max Execution Time</th>
                                                <td><?= ini_get('max_execution_time') ?> seconds</td>
                                            </tr>
                                            <tr>
                                                <th>Loaded Extensions</th>
                                                <td>
                                                    <?php
                                                    $extensions = get_loaded_extensions();
                                                    sort($extensions);
                                                    echo implode(', ', array_slice($extensions, 0, 15));
                                                    if (count($extensions) > 15) {
                                                        echo ' and ' . (count($extensions) - 15) . ' more...';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>View detailed information about your server and PHP configuration.</p>
                                <a href="maintenance.php?action=system_info" class="btn btn-info">
                                    <i class="fas fa-info-circle me-1"></i> View System Info
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Tools Section (from admin_check.php) -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Admin Tools</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Create Admin User -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Create Admin User</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="">
                                                <input type="hidden" name="action" value="create_admin">
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Username</label>
                                                    <input type="text" class="form-control" id="username" name="username" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="password" class="form-label">Password</label>
                                                    <input type="password" class="form-control" id="password" name="password" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Create Admin User</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Profanity Filters -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Profanity Filters</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="">
                                                <input type="hidden" name="action" value="add_profanity">
                                                <div class="mb-3">
                                                    <label for="word" class="form-label">Word to Filter</label>
                                                    <input type="text" class="form-control" id="word" name="word" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="replacement" class="form-label">Replacement</label>
                                                    <input type="text" class="form-control" id="replacement" name="replacement" required placeholder="e.g. ***">
                                                </div>
                                                <button type="submit" class="btn btn-primary">Add Filter</button>
                                            </form>
                                            
                                            <?php 
                                            // Display existing profanity filters
                                            try {
                                                $filtersStmt = $pdo->query("SELECT * FROM profanity_filters ORDER BY word LIMIT 10");
                                                $filters = $filtersStmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                if (!empty($filters)):
                                            ?>
                                            <hr>
                                            <h6>Current Filters (showing first 10)</h6>
                                            <div class="table-responsive mt-3">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Word</th>
                                                            <th>Replacement</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($filters as $filter): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($filter['word']) ?></td>
                                                            <td><?= htmlspecialchars($filter['replacement']) ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php 
                                                endif;
                                            } catch (PDOException $e) {
                                                echo '<div class="alert alert-warning mt-3">Profanity filter table not found. <a href="../create_profanity_table.php" class="alert-link">Create it now</a>.</div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
    include '../templates/admin_footer.php';
}
?> 