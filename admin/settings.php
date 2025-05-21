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
$pageTitle = "Site Settings";

// Process form actions
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update settings
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update each setting
            foreach ($_POST['settings'] as $name => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) 
                                       VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$name, $value, $value]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            $message = "Settings updated successfully.";
        } catch (PDOException $e) {
            // Rollback on error
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_name, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $error = "Could not retrieve settings: " . $e->getMessage();
}

// Default values for settings if not set
$defaultSettings = [
    'siteTitle' => 'Tech Forum',
    'siteDescription' => 'A community for tech enthusiasts',
    'postsPerPage' => '10',
    'allowRegistration' => 'yes',
    'requireEmailVerification' => 'no',
    'maintenanceMode' => 'no',
    'maintenanceMessage' => 'Site is currently under maintenance. Please check back later.',
    'contactEmail' => 'admin@example.com',
    'enableProfanityFilter' => 'yes',
    'welcomeMessage' => 'Welcome to our tech forum. Join the discussion!',
];

// Merge defaults with database settings
$settings = array_merge($defaultSettings, $settings);

// Include header
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
                    <li class="nav-item mb-3">
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
                        <a class="nav-link active text-white" href="settings.php">
                            <i class="fas fa-cog me-2"></i>
                            Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="maintenance.php">
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
                <h1 class="h2">Site Settings</h1>
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

            <!-- Settings Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="siteTitle" class="form-label">Site Title</label>
                                    <input type="text" class="form-control" id="siteTitle" name="settings[siteTitle]" value="<?= htmlspecialchars($settings['siteTitle']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contactEmail" class="form-label">Contact Email</label>
                                    <input type="email" class="form-control" id="contactEmail" name="settings[contactEmail]" value="<?= htmlspecialchars($settings['contactEmail']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="siteDescription" class="form-label">Site Description</label>
                            <textarea class="form-control" id="siteDescription" name="settings[siteDescription]" rows="2"><?= htmlspecialchars($settings['siteDescription']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="welcomeMessage" class="form-label">Welcome Message</label>
                            <textarea class="form-control" id="welcomeMessage" name="settings[welcomeMessage]" rows="2"><?= htmlspecialchars($settings['welcomeMessage']) ?></textarea>
                            <div class="form-text">This message is displayed on the homepage to welcome users.</div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mt-4 mb-3">Content Settings</h5>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="postsPerPage" class="form-label">Posts Per Page</label>
                                    <input type="number" class="form-control" id="postsPerPage" name="settings[postsPerPage]" value="<?= htmlspecialchars($settings['postsPerPage']) ?>" min="5" max="50" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="enableProfanityFilter" class="form-label">Enable Profanity Filter</label>
                                    <select class="form-select" id="enableProfanityFilter" name="settings[enableProfanityFilter]">
                                        <option value="yes" <?= $settings['enableProfanityFilter'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                        <option value="no" <?= $settings['enableProfanityFilter'] === 'no' ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mt-4 mb-3">Registration Settings</h5>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="allowRegistration" class="form-label">Allow User Registration</label>
                                    <select class="form-select" id="allowRegistration" name="settings[allowRegistration]">
                                        <option value="yes" <?= $settings['allowRegistration'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                        <option value="no" <?= $settings['allowRegistration'] === 'no' ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="requireEmailVerification" class="form-label">Require Email Verification</label>
                                    <select class="form-select" id="requireEmailVerification" name="settings[requireEmailVerification]">
                                        <option value="yes" <?= $settings['requireEmailVerification'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                        <option value="no" <?= $settings['requireEmailVerification'] === 'no' ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mt-4 mb-3">Maintenance Settings</h5>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="maintenanceMode" class="form-label">Maintenance Mode</label>
                                    <select class="form-select" id="maintenanceMode" name="settings[maintenanceMode]">
                                        <option value="no" <?= $settings['maintenanceMode'] === 'no' ? 'selected' : '' ?>>No</option>
                                        <option value="yes" <?= $settings['maintenanceMode'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="maintenanceMessage" class="form-label">Maintenance Message</label>
                                    <input type="text" class="form-control" id="maintenanceMessage" name="settings[maintenanceMessage]" value="<?= htmlspecialchars($settings['maintenanceMessage']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Advanced Settings Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Advanced Settings</h6>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSettings">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div class="collapse" id="advancedSettings">
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Warning: These actions can affect your forum. Use with caution.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Clear Cache</h5>
                                        <p class="card-text">Clear all cached data to refresh content.</p>
                                        <a href="maintenance.php?action=clear_cache" class="btn btn-primary">Clear Cache</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Database Backup</h5>
                                        <p class="card-text">Download a backup of your forum database.</p>
                                        <a href="maintenance.php?action=backup_db" class="btn btn-primary">Backup Database</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Forum Statistics</h5>
                                        <p class="card-text">Recalculate forum statistics and counts.</p>
                                        <a href="maintenance.php?action=rebuild_stats" class="btn btn-primary">Rebuild Statistics</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">System Information</h5>
                                        <p class="card-text">View detailed system information.</p>
                                        <a href="maintenance.php?action=system_info" class="btn btn-primary">View Info</a>
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

<?php include '../templates/admin_footer.php'; ?> 