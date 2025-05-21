<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Get site title from settings
$siteTitle = get_setting($conn, 'siteTitle');

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM products WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

// Filter by category
$category = isset($_GET['category']) ? $_GET['category'] : null;
$whereClause = $category ? "WHERE status = 'active' AND category = :category" : "WHERE status = 'active'";

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Fix the issue by ensuring the number of parameters matches the placeholders
if ($category) {
    // Using named parameters consistently
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = :category AND status = :status LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
    $stmt->bindValue(':status', 'active', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT * FROM products WHERE status = :status ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':status', 'active', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$products = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $conn->prepare("SELECT COUNT(*) FROM products $whereClause");
if ($category) {
    $countStmt->bindValue(':category', $category, PDO::PARAM_STR);
}
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

include 'templates/header.php';
?>

<div class="container">
    <div class="page-header mb-4">
        <h1>Products</h1>
        <p>Browse our latest tech products and accessories</p>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>Filter Products</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="products.php" class="btn <?= !$category ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="products.php?category=<?= urlencode($cat) ?>" class="btn <?= $category === $cat ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <?= htmlspecialchars(ucfirst($cat)) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="alert alert-info">No products found.</div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-img-top bg-light text-center py-5">
                            <i class="fas fa-box fa-3x text-muted"></i>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price-tag">$<?= number_format($product['price'], 2) ?></span>
                                <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Product pagination">
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?><?= $category ? '&category='.urlencode($category) : '' ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $category ? '&category='.urlencode($category) : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?><?= $category ? '&category='.urlencode($category) : '' ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
