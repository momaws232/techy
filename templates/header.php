<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? '') . ' | ' . htmlspecialchars(get_site_title($conn)) ?></title>
    <!-- Early dark mode initialization to prevent flash -->
    <script>
        // Apply dark mode immediately if saved in localStorage
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-theme');
            document.body.classList.add('dark-theme');
            document.querySelector('html').style.backgroundColor = '#121212';
            document.querySelector('body').style.backgroundColor = '#121212';
        }
    </script>
    <style>
        /* Critical CSS for preventing flash of light mode */
        html.dark-theme, body.dark-theme {
            background-color: #121212 !important;
            color: #e0e0e0 !important;
        }
    </style>    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/post-likes.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/notification-cards.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <link rel="stylesheet" href="css/forums.css">

    <!-- Add notifications container -->
    <style>
        .notifications-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .maintenance-banner {
            background-color: #dc3545;
            color: white;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Notifications container -->
    <div id="notificationsContainer" class="notifications-container"></div>
    
    <?php
    // Check if site is in maintenance mode
    $maintenanceMode = get_setting($conn, 'maintenanceMode');
    $maintenanceMessage = get_setting($conn, 'maintenanceMessage');
    
    // If maintenance mode is active and user is not admin, show the message
    if ($maintenanceMode === 'yes' && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')):
    ?>
    <div class="maintenance-banner">
        <i class="fas fa-tools me-2"></i>
        <?= !empty($maintenanceMessage) ? htmlspecialchars($maintenanceMessage) : 'Site is currently under maintenance. Please check back later.' ?>
    </div>
    <?php endif; ?>
    
    <nav class="navbar navbar-expand-lg navbar-dark" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?= htmlspecialchars(get_site_title($conn)) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'forums.php' ? 'active' : '' ?>" href="forums.php">
                            <i class="fas fa-comments me-1"></i> Forums
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>" href="products.php">
                            <i class="fas fa-shopping-cart me-1"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : '' ?>" href="news.php">
                            <i class="fas fa-newspaper me-1"></i> News
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'spec-checker.php' ? 'active' : '' ?>" href="spec-checker.php">
                            <i class="fas fa-microchip me-1"></i> Spec Checker
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <a href="admin/index.php" class="btn btn-danger btn-sm me-2">
                            <i class="fas fa-lock me-1"></i> Admin Panel
                        </a>
                        <?php endif; ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php 
                                // Display small profile image if available
                                $userImagePath = !empty($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'assets/images/default-avatar.png';
                                if(file_exists($userImagePath)):
                                ?>
                                <img src="<?= htmlspecialchars($userImagePath) ?>" class="rounded-circle me-2" width="24" height="24" alt="">
                                <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="profile-edit.php"><i class="fas fa-edit me-2"></i>Edit Profile</a></li>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link btn btn-sm btn-outline-light me-2" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        <a class="nav-link btn btn-sm btn-accent" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                    <?php endif; ?>
                    
                    <a class="nav-link ms-2" href="#" id="darkModeToggle">
                        <i class="fas fa-moon"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container my-4">
