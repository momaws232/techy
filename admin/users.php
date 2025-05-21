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
$pageTitle = "User Management";

// Process form actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process user status changes
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'ban':
                $stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "User has been banned successfully.";
                break;
                
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "User has been activated successfully.";
                break;
                
            case 'make_admin':
                $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "User has been promoted to admin successfully.";
                break;
                
            case 'make_moderator':
                $stmt = $pdo->prepare("UPDATE users SET role = 'staff' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "User has been set as moderator successfully.";
                break;
                
            case 'make_user':
                $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "User role has been reset to regular user.";
                break;
                
            case 'delete':
                // Get the reason for deletion if provided
                $deleteReason = isset($_POST['delete_reason']) ? trim($_POST['delete_reason']) : 'Deleted by administrator';
                
                // Update the user status to deleted and add the reason to notes
                $stmt = $pdo->prepare("UPDATE users SET status = 'deleted', notes = CONCAT(IFNULL(notes, ''), '\nDeleted on ', NOW(), ': ', ?) WHERE id = ?");
                $stmt->execute([$deleteReason, $userId]);
                
                $message = "User has been marked as deleted.";
                break;
                
            default:
                $error = "Invalid action specified.";
                break;
        }
    }
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = "WHERE username LIKE ? OR email LIKE ? OR role LIKE ?";
    $searchParams = ["%$search%", "%$search%", "%$search%"];
}

// Get total users count for pagination
$countQuery = "SELECT COUNT(*) FROM users " . $searchCondition;
$stmt = $pdo->prepare($countQuery);
if (!empty($searchParams)) {
    $stmt->execute($searchParams);
} else {
    $stmt->execute();
}
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users with pagination
$query = "SELECT id, username, email, role, status, joined_date, last_login 
          FROM users 
          $searchCondition
          ORDER BY joined_date DESC 
          LIMIT $offset, $perPage";

$stmt = $pdo->prepare($query);
if (!empty($searchParams)) {
    $stmt->execute($searchParams);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter options
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

if (!empty($roleFilter) || !empty($statusFilter)) {
    $filterConditions = [];
    $filterParams = [];
    
    if (!empty($roleFilter)) {
        $filterConditions[] = "role = ?";
        $filterParams[] = $roleFilter;
    }
    
    if (!empty($statusFilter)) {
        $filterConditions[] = "status = ?";
        $filterParams[] = $statusFilter;
    }
    
    $filterWhere = implode(" AND ", $filterConditions);
    $query = "SELECT id, username, email, role, status, joined_date, last_login 
              FROM users 
              WHERE $filterWhere
              ORDER BY joined_date DESC 
              LIMIT $offset, $perPage";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute($filterParams);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update count for pagination
    $countQuery = "SELECT COUNT(*) FROM users WHERE $filterWhere";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($filterParams);
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
}

// Get counts for filter badges
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roleCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

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
                        <a class="nav-link active text-white" href="users.php">
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
                <h1 class="h2">User Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportUsersModal">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                    </div>
                </div>
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

            <!-- Filter and Search -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filters & Search</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <form action="" method="GET" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <a href="?role=admin" class="badge bg-primary rounded-pill text-decoration-none me-1">Admins (<?= $roleCounts['admin'] ?? 0 ?>)</a>
                                <a href="?role=staff" class="badge bg-info rounded-pill text-decoration-none me-1">Moderators (<?= $roleCounts['staff'] ?? 0 ?>)</a>
                                <a href="?status=banned" class="badge bg-danger rounded-pill text-decoration-none me-1">Banned (<?= $statusCounts['banned'] ?? 0 ?>)</a>
                                <a href="?status=pending" class="badge bg-warning rounded-pill text-decoration-none me-1">Pending (<?= $statusCounts['pending'] ?? 0 ?>)</a>
                                <a href="users.php" class="badge bg-secondary rounded-pill text-decoration-none">Clear All</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Registered Users</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <a href="../profile.php?id=<?= $user['id'] ?>" target="_blank">
                                            <?= htmlspecialchars($user['username']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= getUserRoleColor($user['role']) ?>">
                                            <?= ucfirst(htmlspecialchars($user['role'] === 'staff' ? 'moderator' : $user['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= getUserStatusColor($user['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($user['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= format_date($user['joined_date']) ?></td>
                                    <td><?= $user['last_login'] ? format_date($user['last_login']) : 'Never' ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li>
                                                    <a class="dropdown-item" href="../profile.php?id=<?= $user['id'] ?>" target="_blank">
                                                        <i class="fas fa-user me-1"></i> View Profile
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                
                                                <!-- Role management -->
                                                <?php if ($user['role'] != 'admin'): ?>
                                                <li>
                                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to make this user an admin?');">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="action" value="make_admin">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-user-shield me-1"></i> Make Admin
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['role'] != 'staff'): ?>
                                                <li>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="action" value="make_moderator">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-gavel me-1"></i> Make Moderator
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['role'] != 'user'): ?>
                                                <li>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="action" value="make_user">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-user me-1"></i> Reset to User
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                                
                                                <li><hr class="dropdown-divider"></li>
                                                
                                                <!-- Status management -->
                                                <?php if ($user['status'] != 'banned'): ?>
                                                <li>
                                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to ban this user?');">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="action" value="ban">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-ban me-1"></i> Ban User
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php else: ?>
                                                <li>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="fas fa-check-circle me-1"></i> Activate User
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                                
                                                <li><hr class="dropdown-divider"></li>
                                                
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user['id'] ?>">
                                                        <i class="fas fa-trash me-1"></i> Delete User
                                                    </button>
                                                    
                                                    <!-- Delete User Modal -->
                                                    <div class="modal fade" id="deleteModal<?= $user['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $user['id'] ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?= $user['id'] ?>">Delete User: <?= htmlspecialchars($user['username']) ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="" method="POST">
                                                                    <div class="modal-body">
                                                                        <div class="alert alert-danger">
                                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                                            Are you sure you want to delete this user? This action cannot be undone.
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="deleteReason<?= $user['id'] ?>" class="form-label">Reason for deletion (optional):</label>
                                                                            <textarea class="form-control" id="deleteReason<?= $user['id'] ?>" name="delete_reason" rows="3" placeholder="Enter the reason for deleting this user..."></textarea>
                                                                        </div>
                                                                        
                                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                        <input type="hidden" name="action" value="delete">
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-danger">Delete User</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Export Users Modal -->
<div class="modal fade" id="exportUsersModal" tabindex="-1" aria-labelledby="exportUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportUsersModalLabel">Export Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Choose the export format:</p>
                <div class="d-grid gap-2">
                    <a href="export_users.php?format=csv" class="btn btn-outline-primary">
                        <i class="fas fa-file-csv me-1"></i> Export as CSV
                    </a>
                    <a href="export_users.php?format=json" class="btn btn-outline-primary">
                        <i class="fas fa-file-code me-1"></i> Export as JSON
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions
function getUserRoleColor($role) {
    switch ($role) {
        case 'admin':
            return 'danger';
        case 'staff':
            return 'info';
        default:
            return 'secondary';
    }
}

function getUserStatusColor($status) {
    switch ($status) {
        case 'active':
            return 'success';
        case 'banned':
            return 'danger';
        case 'pending':
            return 'warning';
        case 'deleted':
            return 'dark';
        default:
            return 'secondary';
    }
}

include '../templates/admin_footer.php';
?> 