<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Now pass $pdo (or $conn) to the function
$siteTitle = get_setting($pdo, 'siteTitle');

// Get all forum categories
$stmt = $conn->query("SELECT DISTINCT category FROM forums WHERE status = 'active'");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

include 'templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Forums</h1>
        <p>Join the discussions in our various forum categories</p>
    </div>
    
    <?php foreach ($categories as $category): ?>
    <div class="card mb-4">
        <div class="card-header forum-category-header">
            <h3><?= htmlspecialchars(ucfirst($category)) ?></h3>
        </div>
        <div class="card-body">
            <div class="forums-list">
                <?php
                $stmt = $conn->prepare("SELECT id, name, description, icon, 
                    (SELECT COUNT(*) FROM topics WHERE forum_id = forums.id) as topic_count 
                    FROM forums WHERE category = ? AND status = 'active'");
                $stmt->execute([$category]);
                $forums = $stmt->fetchAll();
                
                foreach ($forums as $forum):
                ?>
                <div class="forum d-flex align-items-center mb-3 p-3 border-bottom">
                    <div class="forum-icon me-3">
                        <i class="<?= htmlspecialchars($forum['icon']) ?> fa-2x text-primary"></i>
                    </div>
                    <div class="forum-info flex-grow-1">
                        <h4><a href="forum.php?id=<?= htmlspecialchars($forum['id']) ?>"><?= htmlspecialchars($forum['name']) ?></a></h4>
                        <p class="text-muted mb-0"><?= htmlspecialchars($forum['description']) ?></p>
                    </div>
                    <div class="forum-stats text-center">
                        <div><strong><?= $forum['topic_count'] ?></strong></div>
                        <div class="text-muted">Topics</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'templates/footer.php'; ?>
