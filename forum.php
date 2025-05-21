<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle = 'Forum';
$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: forums.php');
    exit;
}

$forum_id = clean_input($_GET['id']);

// Fetch forum details
try {
    $stmt = $conn->prepare("SELECT * FROM forums WHERE id = ? AND status = 'active'");
    $stmt->execute([$forum_id]);
    $forum = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$forum) {
        header('Location: forums.php');
        exit;
    }
    
    $pageTitle = $forum['name'];
    
    // Get forum moderators
    $stmt = $conn->prepare("
        SELECT u.id, u.username
        FROM forum_moderators fm
        JOIN users u ON fm.user_id = u.id
        WHERE fm.forum_id = ?
    ");
    $stmt->execute([$forum_id]);
    $moderators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = (int)(get_setting($conn, 'postsPerPage') ?? 20);
    $offset = (int)(($page - 1) * $perPage); // Cast to integer to prevent SQL errors
    
    // Get total topics count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM topics WHERE forum_id = ?");
    $stmt->execute([$forum_id]);
    $total_topics = $stmt->fetchColumn();
    $total_pages = ceil($total_topics / $perPage);
    
    // Get topics
    $stmt = $conn->prepare("
        SELECT t.*, 
               u.username as author_name,
               lu.username as last_poster_name,
               (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) as replies
        FROM topics t
        JOIN users u ON t.author_id = u.id
        LEFT JOIN users lu ON t.last_post_user_id = lu.id
        WHERE t.forum_id = ?
        ORDER BY t.is_sticky DESC, t.is_announcement DESC, t.last_post_at DESC
        LIMIT ? OFFSET ?
    ");
    
    // Explicitly pass integer values to prevent SQL syntax error with quotes
    $stmt->bindParam(1, $forum_id);
    $stmt->bindParam(2, $perPage, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="forums.php">Forums</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($forum['name'] ?? '') ?></li>
        </ol>
    </nav>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h1><?= htmlspecialchars($forum['name'] ?? '') ?></h1>
                    <p class="text-muted mb-0"><?= htmlspecialchars($forum['description'] ?? '') ?></p>
                </div>
                <?php if (is_logged_in()): ?>
                    <a href="new-topic.php?forum=<?= $forum['id'] ?>" class="btn btn-primary">New Topic</a>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <?php if ($moderators): ?>
                <div class="moderators mb-3">
                    <strong>Moderators:</strong>
                    <?php foreach ($moderators as $mod): ?>
                        <a href="profile.php?id=<?= $mod['id'] ?>" class="badge bg-secondary"><?= htmlspecialchars($mod['username'] ?? '') ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (count($topics) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Topic</th>
                                    <th>Author</th>
                                    <th>Replies</th>
                                    <th>Views</th>
                                    <th>Last Post</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topics as $topic): ?>
                                <tr>
                                    <td class="text-center" style="width: 40px;">
                                        <?php if ($topic['is_announcement']): ?>
                                            <i class="fas fa-bullhorn text-danger" title="Announcement"></i>
                                        <?php elseif ($topic['is_sticky']): ?>
                                            <i class="fas fa-thumbtack text-primary" title="Sticky"></i>
                                        <?php elseif ($topic['is_hot']): ?>
                                            <i class="fas fa-fire text-warning" title="Hot"></i>
                                        <?php else: ?>
                                            <i class="far fa-comment"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="topic.php?id=<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title'] ?? '') ?></a>
                                    </td>
                                    <td>
                                        <a href="profile.php?id=<?= $topic['author_id'] ?>"><?= htmlspecialchars($topic['author_name'] ?? '') ?></a>
                                        <div class="text-muted small"><?= format_date($topic['created_at']) ?></div>
                                    </td>
                                    <td><?= $topic['replies'] - 1 ?></td>
                                    <td><?= $topic['views'] ?></td>
                                    <td>
                                        <?php if ($topic['last_post_at']): ?>
                                            <div class="small">
                                                by <a href="profile.php?id=<?= $topic['last_post_user_id'] ?>"><?= htmlspecialchars($topic['last_poster_name'] ?? '') ?></a>
                                                <div><?= format_date($topic['last_post_at']) ?></div>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $forum_id ?>&page=<?= $page - 1 ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?id=<?= $forum_id ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $forum_id ?>&page=<?= $page + 1 ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">No topics found in this forum.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
