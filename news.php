<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/functions.php';

// Get site title from settings
$siteTitle = get_setting($conn, 'siteTitle');

// Check if viewing single article
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Single article view
    $articleId = (int)$_GET['id'];
    
    // Get article details
    $stmt = $conn->prepare("
        SELECT n.*, u.username as author, u.id as author_id
        FROM news n
        JOIN users u ON n.author_id = u.id
        WHERE n.id = :id AND n.status = 'published'
    ");
    $stmt->bindValue(':id', $articleId, PDO::PARAM_INT);
    $stmt->execute();
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        $_SESSION['error_message'] = "Article not found or no longer available.";
        header('Location: news.php');
        exit;
    }
    
    // Get related articles from same category
    $relatedArticles = [];
    if (!empty($article['category'])) {        $stmt = $conn->prepare("
            SELECT n.id, n.title, n.summary, COALESCE(n.image, '') as image, n.publish_date, u.username as author, u.id as author_id
            FROM news n
            JOIN users u ON n.author_id = u.id
            WHERE n.category = :category AND n.id != :id AND n.status = 'published'
            ORDER BY n.publish_date DESC
            LIMIT 3
        ");
        $stmt->bindValue(':category', $article['category'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $articleId, PDO::PARAM_INT);
        $stmt->execute();
        $relatedArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $pageTitle = $article['title'];
    include 'templates/header.php';
    ?>

    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="news.php">News</a></li>
                <?php if (!empty($article['category'])): ?>
                    <li class="breadcrumb-item"><a href="news.php?category=<?= urlencode($article['category'] ?? '') ?>"><?= htmlspecialchars($article['category'] ?? '') ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($article['title'] ?? '') ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-md-8">
                <article class="card mb-4">
                    <?php if (!empty($article['image'])): ?>
                        <img src="<?= htmlspecialchars($article['image'] ?? '') ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title'] ?? '') ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h1 class="card-title"><?= htmlspecialchars($article['title'] ?? '') ?></h1>
                        <div class="card-subtitle mb-3 text-muted">
                            <span>By <a href="profile.php?id=<?= $article['author_id'] ?? 0 ?>"><?= htmlspecialchars($article['author'] ?? '') ?></a></span>
                            <span> • <?= format_date($article['publish_date'] ?? '') ?></span>
                            <?php if (!empty($article['category'])): ?>
                                <span> • Category: <a href="news.php?category=<?= urlencode($article['category'] ?? '') ?>"><?= htmlspecialchars($article['category'] ?? '') ?></a></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="article-content">
                            <?= nl2br(htmlspecialchars($article['content'] ?? '')) ?>
                        </div>
                        
                        <div class="mt-4">
                            <a href="news.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i> Back to News
                            </a>
                            <?php if (!empty($article['category'])): ?>
                                <a href="news.php?category=<?= urlencode($article['category'] ?? '') ?>" class="btn btn-outline-secondary ms-2">
                                    More in <?= htmlspecialchars($article['category'] ?? '') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>
            
            <div class="col-md-4">
                <?php if (!empty($relatedArticles)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Related Articles</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($relatedArticles as $related): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <h5><a href="news.php?id=<?= $related['id'] ?>"><?= htmlspecialchars($related['title'] ?? '') ?></a></h5>
                                    <div class="small text-muted mb-2">
                                        <span><?= format_date($related['publish_date'] ?? '') ?></span>
                                    </div>
                                    <p class="mb-0"><?= htmlspecialchars(substr($related['summary'] ?? '', 0, 120)) ?>...</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Categories</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php
                            $categories = $conn->query("SELECT category, COUNT(*) as count FROM news WHERE status = 'published' GROUP BY category ORDER BY count DESC")->fetchAll();
                            if (count($categories) > 0):
                                foreach ($categories as $cat):
                                    if (empty($cat['category'])) continue;
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="news.php?category=<?= urlencode($cat['category']) ?>">
                                    <?= htmlspecialchars($cat['category']) ?>
                                </a>
                                <span class="badge bg-primary rounded-pill"><?= $cat['count'] ?></span>
                            </li>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <li class="list-group-item">No categories found</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include 'templates/footer.php';
    exit; // End script for single article view
}

// Get selected category
$selectedCategory = isset($_GET['category']) ? clean_input($_GET['category']) : null;

// Get news with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;    // Build query based on category filter
if ($selectedCategory) {
    $query = "
        SELECT n.id, n.title, n.summary, n.content, COALESCE(n.image, '') as image, n.publish_date, u.username as author, u.id as author_id, n.category
        FROM news n
        JOIN users u ON n.author_id = u.id
        WHERE n.status = 'published' AND n.category = :category
        ORDER BY n.publish_date DESC
        LIMIT :offset, :limit
    ";
    $countQuery = "SELECT COUNT(*) FROM news WHERE status = 'published' AND category = :category";
} else {
    $query = "
        SELECT n.id, n.title, n.summary, n.content, COALESCE(n.image, '') as image, n.publish_date, u.username as author, u.id as author_id, n.category
        FROM news n
        JOIN users u ON n.author_id = u.id
        WHERE n.status = 'published'
        ORDER BY n.publish_date DESC
        LIMIT :offset, :limit
    ";
    $countQuery = "SELECT COUNT(*) FROM news WHERE status = 'published'";
}

// Execute query with parameters
$stmt = $conn->prepare($query);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
if ($selectedCategory) {
    $stmt->bindValue(':category', $selectedCategory);
}
$stmt->execute();
$newsList = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $conn->prepare($countQuery);
if ($selectedCategory) {
    $countStmt->bindValue(':category', $selectedCategory);
}
$countStmt->execute();
$totalNews = $countStmt->fetchColumn();
$totalPages = ceil($totalNews / $perPage);

$pageTitle = $selectedCategory ? "News: " . htmlspecialchars($selectedCategory) : "Latest News";
include 'templates/header.php';
?>

<div class="container">
    <div class="page-header mb-4">
        <h1><?= $selectedCategory ? htmlspecialchars($selectedCategory) . " News" : "Latest News" ?></h1>
        <p>Stay updated with the latest technology news and announcements</p>
        <?php if ($selectedCategory): ?>
            <a href="news.php" class="btn btn-outline-primary btn-sm mb-3">
                <i class="fas fa-arrow-left me-1"></i> Back to All News
            </a>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <?php if (empty($newsList)): ?>
                <div class="alert alert-info">No news articles found.</div>
            <?php else: ?>
                <?php foreach ($newsList as $article): ?>
                    <div class="card mb-4">
                        <?php if (!empty($article['image'])): ?>
                            <img src="<?= htmlspecialchars($article['image'] ?? '') ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title'] ?? '') ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($article['title'] ?? '') ?></h3>
                            <div class="card-subtitle mb-2 text-muted">
                                <span>By <a href="profile.php?id=<?= $article['author_id'] ?? 0 ?>"><?= htmlspecialchars($article['author'] ?? '') ?></a></span>
                                <span> • <?= format_date($article['publish_date'] ?? '') ?></span>
                                <?php if (!empty($article['category'])): ?>
                                    <span> • Category: <a href="news.php?category=<?= urlencode($article['category'] ?? '') ?>"><?= htmlspecialchars($article['category'] ?? '') ?></a></span>
                                <?php endif; ?>
                            </div>
                            <p class="card-text"><?= htmlspecialchars($article['summary'] ?? '') ?></p>
                            <a href="news.php?id=<?= $article['id'] ?>" class="btn btn-primary">Read More</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="News pagination">
                        <ul class="pagination">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= $selectedCategory ? 'category='.urlencode($selectedCategory).'&' : '' ?>page=<?= $page-1 ?>">Previous</a>
                            </li>
                            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= $selectedCategory ? 'category='.urlencode($selectedCategory).'&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= $selectedCategory ? 'category='.urlencode($selectedCategory).'&' : '' ?>page=<?= $page+1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Categories</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $categories = $conn->query("SELECT category, COUNT(*) as count FROM news WHERE status = 'published' GROUP BY category ORDER BY count DESC")->fetchAll();
                        if (count($categories) > 0):
                            foreach ($categories as $cat):
                                // Skip if category is null or empty
                                if (empty($cat['category'])) continue;
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center <?= ($selectedCategory == $cat['category']) ? 'active' : '' ?>">
                            <a href="news.php?category=<?= urlencode($cat['category']) ?>" class="<?= ($selectedCategory == $cat['category']) ? 'text-white' : '' ?>">
                                <?= htmlspecialchars($cat['category']) ?>
                            </a>
                            <span class="badge <?= ($selectedCategory == $cat['category']) ? 'bg-white text-primary' : 'bg-primary text-white' ?> rounded-pill">
                                <?= $cat['count'] ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <li class="list-group-item">No categories found</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
