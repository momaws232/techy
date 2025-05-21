<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'config/likes_helpers.php'; // Include likes helper functions

$pageTitle = 'User Profile';
$error = '';

// Determine which user profile to display
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : (is_logged_in() ? $_SESSION['user_id'] : 0);

if ($profile_id <= 0) {
    header('Location: login.php');
    exit;
}

try {
    // Get user information
    $stmt = $conn->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM topics WHERE author_id = u.id) as topic_count,
               (SELECT COUNT(*) FROM posts WHERE author_id = u.id) as post_count,
               (SELECT COUNT(*) FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.author_id = u.id) as likes_received
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$profile_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: index.php');
        exit;
    }
    
    $pageTitle = $user['username'] . '\'s Profile';
    
    // Get recent activity
    $stmt = $conn->prepare("
        SELECT al.*, t.title as topic_title
        FROM activity_log al
        LEFT JOIN topics t ON al.object_type = 'topic' AND al.object_id = t.id
        WHERE al.user_id = ?
        ORDER BY al.activity_time DESC
        LIMIT 10
    ");
    $stmt->execute([$profile_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent posts
    $stmt = $conn->prepare("
        SELECT p.*, t.title as topic_title, t.id as topic_id
        FROM posts p
        JOIN topics t ON p.topic_id = t.id
        WHERE p.author_id = ?
        ORDER BY p.post_date DESC
        LIMIT 5
    ");
    $stmt->execute([$profile_id]);
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container profile-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php else: ?>
        <div class="profile-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-3 col-md-4 text-center">
                        <?php 
                        // Default avatar path
                        $defaultAvatar = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                        
                        // Use profile image if it exists, otherwise use default
                        $displayPath = !empty($user['profile_image']) && file_exists($user['profile_image']) 
                            ? $user['profile_image'] 
                            : $defaultAvatar;
                        ?>
                        <div class="profile-avatar-wrapper mb-3">
                            <img src="<?= htmlspecialchars($displayPath) ?>" 
                                alt="<?= htmlspecialchars($user['username']) ?>" 
                                class="profile-avatar"
                                style="width: 160px; height: 160px; object-fit: cover; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                        </div>
                        <?php if (is_logged_in() && $_SESSION['user_id'] == $user['id']): ?>
                            <a href="upload_profile_image.php" class="btn btn-sm btn-primary mb-3">
                                <i class="fas fa-camera me-1"></i> Change Photo
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-9 col-md-8">
                        <div class="profile-info">
                            <h1><?= htmlspecialchars($user['username']) ?></h1>
                            <p class="mb-2">
                                <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'staff' ? 'warning' : 'secondary') ?>">
                                    <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                </span>
                            </p>
                            <p class="mb-1"><i class="fas fa-calendar-alt me-2"></i> Member since: <?= format_date($user['joined_date']) ?></p>
                            <?php if ($user['last_login']): ?>
                                <p class="mb-1"><i class="fas fa-clock me-2"></i> Last seen: <?= format_date($user['last_login']) ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($user['bio'])): ?>
                                <div class="mt-3">
                                    <h5>About</h5>
                                    <p><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="profile-stats">
                                <div class="stat-box">
                                    <h5><?= $user['topic_count'] ?></h5>
                                    <span>Topics</span>
                                </div>
                                <div class="stat-box">
                                    <h5><?= $user['post_count'] ?></h5>
                                    <span>Posts</span>
                                </div>
                                <div class="stat-box">
                                    <h5><?= $user['likes_received'] ?? 0 ?></h5>
                                    <span><i class="fas fa-thumbs-up text-primary"></i> Likes</span>
                                </div>
                                <?php if (is_logged_in() && (is_admin() || $_SESSION['user_id'] == $user['id'])): ?>
                                    <a href="profile-edit.php" class="btn btn-light btn-lg mt-0">
                                        <i class="fas fa-pencil-alt me-2"></i>Edit Profile
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-comments me-2"></i>Recent Posts</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_posts): ?>
                            <?php foreach ($recent_posts as $post): ?>
                                <div class="post-preview">
                                    <h5>
                                        <a href="topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post['id'] ?>">
                                            <i class="fas fa-reply me-2"></i><?= htmlspecialchars($post['topic_title']) ?>
                                        </a>
                                    </h5>
                                    <span class="text-muted"><i class="far fa-clock me-1"></i> <?= format_date($post['post_date']) ?></span>
                                    <div class="post-excerpt mt-2">
                                        <?php 
                                        echo substr(strip_tags($post['content']), 0, 200) . '...'; 
                                        ?>
                                    </div>
                                    <a href="topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post['id'] ?>" class="btn btn-sm btn-outline-dark mt-2">
                                        Read More
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comment-slash fa-3x mb-3 text-secondary"></i>
                                <p class="mb-0">This user hasn't posted any content yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card activity-panel">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h4>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($activities): ?>
                            <ul class="list-group">
                                <?php foreach ($activities as $activity): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <i class="fas <?= $activity['action'] == 'created topic' ? 'fa-plus-circle text-success' : 'fa-comment text-primary' ?> me-2"></i>
                                                <?= htmlspecialchars($activity['action']) ?> 
                                                <?= htmlspecialchars($activity['object_type']) ?>
                                                <?php if ($activity['object_type'] == 'topic' && $activity['topic_title']): ?>
                                                    <a href="topic.php?id=<?= $activity['object_id'] ?>">
                                                        <?= htmlspecialchars($activity['topic_title']) ?>
                                                    </a>
                                                <?php elseif ($activity['object_name']): ?>
                                                    "<?= htmlspecialchars($activity['object_name']) ?>"
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?= format_date($activity['activity_time']) ?></small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x mb-3 text-secondary"></i>
                                <p class="mb-0">No recent activity.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
