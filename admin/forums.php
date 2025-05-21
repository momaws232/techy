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
$pageTitle = "Forum Management";

// Process form actions
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new forum
    if (isset($_POST['action']) && $_POST['action'] === 'create_forum') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        // Check if using new category
        if (isset($_POST['category']) && $_POST['category'] === 'new' && !empty($_POST['new_category'])) {
            $category = trim($_POST['new_category']);
        } else {
            $category = trim($_POST['category']);
        }
        
        $icon = trim($_POST['icon'] ?? 'fas fa-comments');
        
        if (empty($name) || empty($description) || empty($category)) {
            $error = "All fields are required.";
        } else {
            try {
                // Generate a unique ID from the name (create a slug)
                $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
                $id = trim($id, '-');
                
                // Make sure the ID is unique by checking if it exists already
                $checkStmt = $pdo->prepare("SELECT id FROM forums WHERE id = ?");
                $checkStmt->execute([$id]);
                
                if ($checkStmt->rowCount() > 0) {
                    // ID already exists, append a random string
                    $id = $id . '-' . substr(md5(time()), 0, 5);
                }
                
                // Insert with the ID included
                $stmt = $pdo->prepare("INSERT INTO forums (id, name, description, category, icon, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
                $stmt->execute([$id, $name, $description, $category, $icon]);
                $message = "Forum created successfully.";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Update forum
    if (isset($_POST['action']) && $_POST['action'] === 'update_forum') {
        $forumId = (int)$_POST['forum_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);
        $icon = trim($_POST['icon'] ?? 'fas fa-comments');
        $status = $_POST['status'];
        
        if (empty($name) || empty($description) || empty($category)) {
            $error = "All fields are required.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE forums SET name = ?, description = ?, category = ?, icon = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $description, $category, $icon, $status, $forumId]);
                $message = "Forum updated successfully.";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Delete forum
    if (isset($_POST['action']) && $_POST['action'] === 'delete_forum') {
        $forumId = $_POST['forum_id'];
        
        try {
            // Check if forum has topics
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE forum_id = ?");
            $stmt->execute([$forumId]);
            $topicCount = $stmt->fetchColumn();
            
            if ($topicCount > 0) {
                // Just mark as inactive instead of deleting
                $stmt = $pdo->prepare("UPDATE forums SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$forumId]);
                $message = "Forum has been marked as inactive (it has topics).";
            } else {
                // No topics, safe to delete
                $stmt = $pdo->prepare("DELETE FROM forums WHERE id = ?");
                if ($stmt->execute([$forumId])) {
                    $message = "Forum deleted successfully.";
                } else {
                    $error = "Failed to delete forum.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get all forums grouped by category
$stmt = $pdo->query("
    SELECT f.*, 
           (SELECT COUNT(*) FROM topics WHERE forum_id = f.id) as topic_count,
           (SELECT COUNT(*) FROM posts p JOIN topics t ON p.topic_id = t.id WHERE t.forum_id = f.id) as post_count
    FROM forums f
    ORDER BY f.category, f.name
");
$forumsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group forums by category
$forumsByCategory = [];
foreach ($forumsData as $forum) {
    $forumsByCategory[$forum['category']][] = $forum;
}

// Get all categories for dropdown
$stmt = $pdo->query("SELECT DISTINCT category FROM forums ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
                        <a class="nav-link text-white" href="users.php">
                            <i class="fas fa-users me-2"></i>
                            User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="forums.php">
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
                <h1 class="h2">Forum Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createForumModal">
                        <i class="fas fa-plus me-1"></i> Create New Forum
                    </button>
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

            <!-- Forum Management Section -->
            <?php if (empty($forumsByCategory)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No forums found. Create a new forum to get started.
                </div>
            <?php else: ?>
                <?php foreach ($forumsByCategory as $category => $forums): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary"><?= htmlspecialchars(ucfirst($category)) ?></h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#createForumModal" 
                                    data-category="<?= htmlspecialchars($category) ?>">
                                <i class="fas fa-plus me-1"></i> Add to Category
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Topics</th>
                                            <th>Posts</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($forums as $forum): ?>
                                        <tr>
                                            <td>
                                                <i class="<?= htmlspecialchars($forum['icon']) ?> me-2"></i>
                                                <a href="../forum.php?id=<?= $forum['id'] ?>" target="_blank">
                                                    <?= htmlspecialchars($forum['name']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($forum['description']) ?></td>
                                            <td><?= $forum['topic_count'] ?></td>
                                            <td><?= $forum['post_count'] ?></td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?= $forum['status'] === 'active' ? 'success' : 'danger' ?>">
                                                    <?= ucfirst(htmlspecialchars($forum['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-info edit-forum-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editForumModal"
                                                            data-id="<?= $forum['id'] ?>"
                                                            data-name="<?= htmlspecialchars($forum['name']) ?>"
                                                            data-description="<?= htmlspecialchars($forum['description']) ?>"
                                                            data-category="<?= htmlspecialchars($forum['category']) ?>"
                                                            data-icon="<?= htmlspecialchars($forum['icon']) ?>"
                                                            data-status="<?= htmlspecialchars($forum['status']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="" method="post" class="d-inline delete-forum-form" id="deleteForum<?= $forum['id'] ?>">
                                                        <input type="hidden" name="action" value="delete_forum">
                                                        <input type="hidden" name="forum_id" value="<?= $forum['id'] ?>">
                                                        <button type="button" class="btn btn-sm btn-danger delete-forum-btn">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Create Forum Modal -->
<div class="modal fade" id="createForumModal" tabindex="-1" aria-labelledby="createForumModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createForumModalLabel">Create New Forum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post">
                    <input type="hidden" name="action" value="create_forum">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Forum Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <div class="input-group">
                            <select class="form-select" id="category" name="category">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars(ucfirst($cat)) ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ New Category</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="newCategoryField" style="display:none;">
                        <label for="newCategory" class="form-label">New Category Name</label>
                        <input type="text" class="form-control" id="newCategory" name="new_category">
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label">Icon (FontAwesome Class)</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="fas fa-comments">
                        <div class="form-text">Examples: fas fa-laptop, fas fa-gamepad, fas fa-code</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Create Forum</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Forum Modal -->
<div class="modal fade" id="editForumModal" tabindex="-1" aria-labelledby="editForumModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editForumModalLabel">Edit Forum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post">
                    <input type="hidden" name="action" value="update_forum">
                    <input type="hidden" name="forum_id" id="edit_forum_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Forum Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Category</label>
                        <select class="form-select" id="edit_category" name="category">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars(ucfirst($cat)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">Icon (FontAwesome Class)</label>
                        <input type="text" class="form-control" id="edit_icon" name="icon">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Forum</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete forum buttons
    document.querySelectorAll('.delete-forum-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const form = this.closest('.delete-forum-form');
            
            if (confirm('Are you sure you want to delete this forum?')) {
                form.submit();
            }
        });
    });
    
    // Handle category selection in create form
    document.querySelector('#category').addEventListener('change', function() {
        if (this.value === 'new') {
            document.querySelector('#newCategoryField').style.display = 'block';
        } else {
            document.querySelector('#newCategoryField').style.display = 'none';
        }
    });
    
    // Handle setting modal data for editing forums
    document.querySelectorAll('.edit-forum-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const description = this.getAttribute('data-description');
            const category = this.getAttribute('data-category');
            const icon = this.getAttribute('data-icon');
            const status = this.getAttribute('data-status');
            
            document.querySelector('#edit_forum_id').value = id;
            document.querySelector('#edit_name').value = name;
            document.querySelector('#edit_description').value = description;
            document.querySelector('#edit_category').value = category;
            document.querySelector('#edit_icon').value = icon;
            document.querySelector('#edit_status').value = status;
        });
    });
    
    // Handle category preselection when adding to category
    document.querySelector('#createForumModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const category = button.getAttribute('data-category');
        
        if (category) {
            const categorySelect = document.querySelector('#category');
            for (let i = 0; i < categorySelect.options.length; i++) {
                if (categorySelect.options[i].value === category) {
                    categorySelect.selectedIndex = i;
                    break;
                }
            }
        }
    });
});
</script>

<?php include '../templates/admin_footer.php'; ?> 