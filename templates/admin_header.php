<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Admin Panel' : 'Admin Panel' ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- jQuery (needed for modal fixes) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Main CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../css/admin.css">
    <!-- Admin Fixes CSS -->
    <link rel="stylesheet" href="../css/admin-fixes.css">
    <!-- Custom Styles -->
    <style>
        body {
            padding-top: 56px;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top navbar-admin">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-shield-alt me-2"></i>
                Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarToggler" aria-controls="navbarToggler" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarToggler">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <button id="darkModeToggle" class="btn dark-mode-toggle me-2">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell me-1"></i>
                            <?php 
                            // Get pending reports count
                            try {
                                $reportsStmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'");
                                $pendingReports = $reportsStmt->fetchColumn();
                                
                                if ($pendingReports > 0) {
                                    echo '<span class="badge bg-danger">' . $pendingReports . '</span>';
                                }
                            } catch (Exception $e) {
                                // Silently fail if reports table doesn't exist yet
                            }
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if (isset($pendingReports) && $pendingReports > 0): ?>
                                <li><a class="dropdown-item" href="moderation.php">
                                    <i class="fas fa-flag text-danger me-1"></i> 
                                    <?= $pendingReports ?> pending reports
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="view_logs.php">
                                <i class="fas fa-file-alt me-1"></i> View Logs
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i> Back to Site
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="../profile.php">
                                <i class="fas fa-user me-1"></i> My Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Notifications Container -->
    <div class="toast-container"></div>

    <!-- Main Content Container -->
    <div class="container-fluid p-0"><?= isset($error) ? '<div class="alert alert-danger">' . $error . '</div>' : '' ?>
