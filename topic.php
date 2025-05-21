<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'config/likes_helpers.php'; // Include our new likes helper functions
require_once 'config/moderation_helpers.php'; // Include moderation helpers
require_once 'config/attachment_helpers.php'; // Include attachment helpers

$pageTitle = 'Topic';
$error = '';

// Check for initial post_likes table
if (!function_exists('check_post_likes_table')) {
    function check_post_likes_table($conn) {
        // Check if post_likes table exists
        $tables = $conn->query("SHOW TABLES LIKE 'post_likes'")->fetchAll();
        if (count($tables) == 0) {
            // Redirect to create table script
            header('Location: database/config/add_likes_table.php');
            exit;
        }
    }
}

// Make sure post_likes table exists
check_post_likes_table($conn);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$topic_id = clean_input($_GET['id']);

try {
    // Update views counter
    $stmt = $conn->prepare("UPDATE topics SET views = views + 1 WHERE id = ?");
    $stmt->execute([$topic_id]);
    
    // Fetch topic data
    $stmt = $conn->prepare("
        SELECT t.*, f.id as forum_id, f.name as forum_name, u.username as author_name
        FROM topics t
        JOIN forums f ON t.forum_id = f.id
        JOIN users u ON t.author_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        header('Location: index.php');
        exit;
    }
    
    $pageTitle = $topic['title'];
    
    // Pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $posts_per_page = (int)(get_setting($conn, 'postsPerPage') ?? 10);
    $offset = ($page - 1) * $posts_per_page;
    
    // Get total posts count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    $total_posts = $stmt->fetchColumn();
    $total_pages = ceil($total_posts / $posts_per_page);
    
    // Get posts with SQL_CALC_FOUND_ROWS for proper pagination
    $stmt = $conn->prepare("
        SELECT SQL_CALC_FOUND_ROWS p.*, u.username, u.role, u.joined_date, u.profile_image,
               (SELECT COUNT(*) FROM posts WHERE author_id = p.author_id AND post_date <= p.post_date) as author_post_count
        FROM posts p
        JOIN users u ON p.author_id = u.id
        WHERE p.topic_id = ?
        ORDER BY p.post_date
        LIMIT ?, ?
    ");
    
    // Fix LIMIT and OFFSET parameters by using bindParam with PDO::PARAM_INT
    $stmt->bindParam(1, $topic_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
    $stmt->bindParam(3, $posts_per_page, PDO::PARAM_INT);
    $stmt->execute();
    
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total records for pagination
    $total_records = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
    $total_pages = ceil($total_records / $posts_per_page);
    
    // Check if logged-in user is moderator for this forum
    $isModerator = false;
    if (is_logged_in()) {
        if (is_admin()) {
            $isModerator = true;
        } else {
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM forum_moderators 
                WHERE forum_id = ? AND user_id = ?
            ");
            $stmt->execute([$topic['forum_id'], $_SESSION['user_id']]);
            $isModerator = $stmt->fetchColumn() > 0;
        }
    }
    
    // Check for post success message
    if (isset($_SESSION['post_success'])) {
        $success = $_SESSION['post_success'];
        unset($_SESSION['post_success']);
    }
    
} catch (PDOException $e) {
    $error = 'Database Error: ' . $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="forums.php">Forums</a></li>
            <li class="breadcrumb-item"><a href="forum.php?id=<?= $topic['forum_id'] ?>"><?= htmlspecialchars($topic['forum_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($topic['title']) ?></li>
        </ol>
    </nav>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php else: ?>        <!-- New posts notification placeholder will be inserted here by JavaScript -->
        <div id="notifications-container"></div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h1><?= htmlspecialchars($topic['title']) ?></h1>
                <?php if (is_logged_in()): ?>
                    <div>
                        <a href="#reply-form" class="btn btn-primary">Reply</a>
                        <?php if (is_admin() || $isModerator || $_SESSION['user_id'] == $topic['author_id']): ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if ($_SESSION['user_id'] == $topic['author_id'] || is_admin() || $isModerator): ?>
                                        <li><a class="dropdown-item" href="edit-topic.php?id=<?= $topic['id'] ?>">Edit Topic</a></li>
                                    <?php endif; ?>
                                    
                                    <?php if (is_admin() || $isModerator): ?>
                                        <li><a class="dropdown-item" href="moderate-topic.php?id=<?= $topic['id'] ?>&action=sticky"><?= $topic['is_sticky'] ? 'Unsticky' : 'Sticky' ?></a></li>
                                        <li><a class="dropdown-item" href="moderate-topic.php?id=<?= $topic['id'] ?>&action=lock"><?= isset($topic['is_locked']) && $topic['is_locked'] ? 'Unlock Topic' : 'Lock Topic' ?></a></li>
                                        <li><a class="dropdown-item" href="moderate-topic.php?id=<?= $topic['id'] ?>&action=move">Move Topic</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['user_id'] == $topic['author_id'] || is_admin() || $isModerator): ?>
                                        <li><a class="dropdown-item text-danger" href="delete-topic.php?id=<?= $topic['id'] ?>">Delete Topic</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <?php foreach ($posts as $index => $post): ?>
                    <div class="post mb-4" id="post-<?= $post['id'] ?>">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="user-info text-center">
                                    <?php 
                                    // Get user's profile image
                                    $stmt2 = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
                                    $stmt2->execute([$post['author_id']]);
                                    $profileImage = $stmt2->fetchColumn();
                                    
                                    // Default avatar path
                                    $defaultAvatar = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                                    
                                    // Use profile image if it exists, otherwise use default
                                    $displayPath = !empty($profileImage) && file_exists($profileImage) 
                                        ? $profileImage 
                                        : $defaultAvatar;
                                    ?>
                                    
                                    <div class="mb-3">
                                        <img src="<?= htmlspecialchars($displayPath) ?>" 
                                            alt="<?= htmlspecialchars($post['username']) ?>" 
                                            class="rounded-circle img-fluid" 
                                            style="width: 100px; height: 100px; object-fit: cover;">
                                    </div>
                                    
                                    <div class="username">
                                        <a href="profile.php?id=<?= $post['author_id'] ?>"><?= htmlspecialchars($post['username']) ?></a>
                                    </div>
                                    <div class="badge bg-<?= $post['role'] == 'admin' ? 'danger' : ($post['role'] == 'staff' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst(htmlspecialchars($post['role'])) ?>
                                    </div>
                                    <div class="joined">
                                        <small>Joined: <?= format_date($post['joined_date']) ?></small>
                                    </div>
                                    <div class="post-count">
                                        <small>Posts: <?= $post['author_post_count'] ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-10">
                                <div class="post-content">
                                    <div class="post-header d-flex justify-content-between mb-2">
                                        <div class="post-meta">
                                            <small>Posted: <?= format_date($post['post_date']) ?></small>
                                            <?php if ($post['edited_at']): ?>
                                                <small class="text-muted">(Edited: <?= format_date($post['edited_at']) ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="post-actions">
                                            <a href="#post-<?= $post['id'] ?>" class="text-secondary" title="Permalink">#<?= $offset + $index + 1 ?></a>
                                            <?php if (is_logged_in() && ($_SESSION['user_id'] == $post['author_id'] || is_admin() || $isModerator)): ?>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="edit-post.php?id=<?= $post['id'] ?>">Edit</a></li>
                                                        <li><a class="dropdown-item text-danger" href="delete-post.php?id=<?= $post['id'] ?>">Delete</a></li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="post-body">
                                        <?php 
                                        // Display warning if post is flagged
                                        if (isset($post['moderation_status']) && $post['moderation_status'] === 'flagged' && !is_moderator()): ?>
                                            <div class="alert alert-warning mb-2">
                                                <i class="fas fa-exclamation-triangle"></i> This post has been flagged for moderation review.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php echo $post['content']; ?>                                        <?php 
                                        // Display attachments if any
                                        $attachments = get_attachments($pdo, 'post', $post['id']);
                                        if (!empty($attachments)): ?>
                                            <div class="post-attachments mt-3">
                                                <h6>Attachments:</h6>
                                                <div class="row g-2">
                                                    <?php foreach ($attachments as $attachment): ?>
                                                        <?php if (strpos($attachment['file_type'], 'image/') === 0): ?>
                                                            <div class="col-md-4 col-6">
                                                                <div class="attachment-image">
                                                                    <a href="<?= htmlspecialchars($attachment['url']) ?>" target="_blank">
                                                                        <img src="<?= htmlspecialchars($attachment['url']) ?>" 
                                                                            alt="<?= htmlspecialchars($attachment['file_name']) ?>"
                                                                            class="img-fluid rounded"
                                                                            onerror="this.onerror=null; this.src='images/broken-image.png'; this.alt='Image not found';">
                                                                    </a>
                                                                    <?php if (!$attachment['file_exists']): ?>
                                                                        <div class="attachment-missing">
                                                                            <span class="badge bg-danger">File Missing</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <?php /* Add troubleshooting help for admins */ ?>
                                                                    <?php if (is_admin()): ?>
                                                                        <div class="attachment-debug">
                                                                            <a href="<?= htmlspecialchars($attachment['url'] . '&debug=1') ?>" target="_blank" class="text-muted">
                                                                                <small><i class="fas fa-bug"></i> Debug</small>
                                                                            </a>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="col-12">
                                                                <a href="<?= htmlspecialchars($attachment['url']) ?>" target="_blank">
                                                                    <i class="fas fa-file"></i> <?= htmlspecialchars($attachment['file_name']) ?>
                                                                </a>
                                                                <span class="text-muted">(<?= round($attachment['file_size'] / 1024, 1) ?> KB)</span>
                                                                <?php if (!$attachment['file_exists']): ?>
                                                                    <span class="badge bg-danger">File Missing</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-footer mt-3">
                                        <?php
                                        // Use our helper functions
                                        $likeCount = get_post_likes_count($conn, $post['id']);
                                        $userLiked = is_logged_in() ? has_user_liked_post($conn, $post['id'], $_SESSION['user_id']) : false;
                                        
                                        // Get the first few users who liked this post (for hover tooltip)
                                        $likers = [];
                                        if ($likeCount > 0) {
                                            $likers = get_post_likers($conn, $post['id'], 3);
                                        }
                                        
                                        // Create likers text for tooltip
                                        $likersText = '';
                                        if (!empty($likers)) {
                                            $likerNames = array_map(function($liker) {
                                                return htmlspecialchars($liker['username']);
                                            }, $likers);
                                            
                                            if (count($likerNames) === 1) {
                                                $likersText = $likerNames[0] . ' liked this post';
                                            } else if (count($likerNames) === 2) {
                                                $likersText = $likerNames[0] . ' and ' . $likerNames[1] . ' liked this post';
                                            } else {
                                                $remaining = $likeCount - 2;
                                                $likersText = $likerNames[0] . ', ' . $likerNames[1] . ' and ' . ($remaining > 0 ? $remaining . ' others' : $likerNames[2]) . ' liked this post';
                                            }
                                        }
                                        ?>
                                        <div class="post-likes" data-post-id="<?= $post['id'] ?>">
                                            <button class="btn btn-sm <?= $userLiked ? 'btn-primary' : 'btn-outline-primary' ?> like-button" <?= is_logged_in() ? '' : 'disabled' ?> <?= $likersText ? 'title="' . $likersText . '"' : '' ?> data-bs-toggle="tooltip">
                                                <i class="fas fa-thumbs-up"></i> <span class="like-count"><?= $likeCount ?></span> <?= $likeCount == 1 ? 'Like' : 'Likes' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($index < count($posts) - 1): ?>
                        <hr>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $topic_id ?>&page=<?= $page - 1 ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?id=<?= $topic_id ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?id=<?= $topic_id ?>&page=<?= $page + 1 ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (is_logged_in()): ?>
            <div class="card" id="reply-form">
                <div class="card-header">
                    <h2>Reply to this topic</h2>
                </div>
                <div class="card-body">
                    <form action="post-reply.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                        <div class="mb-3">
                            <textarea class="form-control editor" name="content" rows="5" required></textarea>
                            <small class="text-muted">You can include YouTube or Vimeo links - they will be automatically embedded.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="attachments" class="form-label">Attachments (Images only, max 5MB each)</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple accept="image/*">
                            <small class="text-muted">You can upload up to 3 image files (JPEG, PNG, GIF, WEBP). Maximum file size: 5MB each.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Post Reply</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You need to <a href="login.php">login</a> or <a href="register.php">register</a> to reply to this topic.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Add some CSS for attachments and videos -->
<style>
.video-embed {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    max-width: 100%;
    margin: 1rem 0;
}

.video-embed iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.attachment-image img {
    max-height: 200px;
    object-fit: contain;
    width: 100%;
    border: 1px solid #dee2e6;
    padding: 3px;
    background-color: #fff;
}

.post-attachments {
    border-top: 1px solid rgba(0,0,0,.1);
    padding-top: 10px;
}
</style>

<?php include 'templates/footer.php'; ?>
