<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Ensure only admins can access this page
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = "You must be logged in as an administrator to access this page.";
    header('Location: ../login.php');
    exit;
}

// Set page title
$pageTitle = "Log Management";

// Message to display
$message = "The log viewing functionality has been removed from the admin panel.";

// Include header
include __DIR__ . '/../templates/admin_header.php';
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
                <h1 class="h2"><i class="fas fa-file-alt me-2"></i> Log Management</h1>
            </div>

            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">Logs Removed</h4>
                <p><?= htmlspecialchars($message) ?></p>
                <hr>
                <p class="mb-0">Please return to the dashboard or use the maintenance tools for system administration tasks.</p>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Alternative Options</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Dashboard</h5>
                                    <p class="card-text">Return to the admin dashboard</p>
                                    <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Maintenance</h5>
                                    <p class="card-text">Access maintenance tools and system settings</p>
                                    <a href="maintenance.php" class="btn btn-primary">Go to Maintenance</a>
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