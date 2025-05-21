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
$pageTitle = "Admin Dashboard";

// Count total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

// Count recent posts (last 24 hours)
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE post_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$recentPosts = $stmt->fetchColumn();

// Count total forums
$stmt = $pdo->query("SELECT COUNT(*) FROM forums");
$totalForums = $stmt->fetchColumn();

// Count total topics
$stmt = $pdo->query("SELECT COUNT(*) FROM topics");
$totalTopics = $stmt->fetchColumn();

// Get latest posts
$stmt = $pdo->query("
    SELECT p.*, t.title as topic_title, u.username as author_name, 
           t.id as topic_id, t.forum_id
    FROM posts p
    JOIN topics t ON p.topic_id = t.id
    JOIN users u ON p.author_id = u.id
    ORDER BY p.post_date DESC
    LIMIT 10
");
$recentPostsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="index.php">
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
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="../index.php" class="btn btn-sm btn-outline-secondary">View Forum</a>
                        <a href="settings.php" class="btn btn-sm btn-outline-secondary">Settings</a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Forums</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalForums ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-folder fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Topics</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalTopics ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalUsers ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Posts (24h)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $recentPosts ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-comment-dots fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Posts</h6>
                    <a href="../forums.php" class="btn btn-sm btn-primary">View All Forums</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentPostsList)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No posts found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Post</th>
                                        <th>Author</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPostsList as $post): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <a href="../topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post['id'] ?>" target="_blank">
                                                        <?= htmlspecialchars(substr($post['topic_title'], 0, 50)) ?>
                                                        <?= strlen($post['topic_title']) > 50 ? '...' : '' ?>
                                                    </a>
                                                </strong>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars(substr(strip_tags($post['content']), 0, 100)) ?>
                                                    <?= strlen(strip_tags($post['content'])) > 100 ? '...' : '' ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($post['author_name']) ?></td>
                                            <td><?= format_date($post['post_date']) ?></td>
                                            <td>
                                                <a href="../topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post['id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="../edit-post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Access Widgets -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">User Management</h6>
                        </div>
                        <div class="card-body">
                            <p>Manage your forum users and their permissions.</p>
                            <a href="users.php" class="btn btn-block btn-primary">
                                <i class="fas fa-users mr-1"></i> User Management
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Forum Management</h6>
                        </div>
                        <div class="card-body">
                            <p>Manage your forum categories and structure.</p>
                            <a href="forums.php" class="btn btn-block btn-info">
                                <i class="fas fa-folder mr-1"></i> Forum Management
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Site Settings</h6>
                        </div>
                        <div class="card-body">
                            <p>Configure site settings and preferences.</p>
                            <a href="settings.php" class="btn btn-block btn-warning">
                                <i class="fas fa-cog mr-1"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Maintenance</h6>
                        </div>
                        <div class="card-body">
                            <p>Access system maintenance tools and utilities.</p>
                            <a href="maintenance.php" class="btn btn-block btn-secondary">
                                <i class="fas fa-tools mr-1"></i> Maintenance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../templates/admin_footer.php'; ?>
