<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Get site title from settings
$siteTitle = get_setting($conn, 'siteTitle');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

// Get product details
$productId = (int)$_GET['id']; // Cast to integer for security
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id AND status = 'active'");
$stmt->bindValue(':id', $productId, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// If product not found, redirect to products page
if (!$product) {
    $_SESSION['error_message'] = "Product not found or no longer available.";
    header('Location: products.php');
    exit;
}

// Get related products from the same category
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE category = :category AND id != :id AND status = 'active' 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->bindValue(':category', $product['category'], PDO::PARAM_STR);
$stmt->bindValue(':id', $productId, PDO::PARAM_INT);
$stmt->execute();
$relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $product['name']; // Set page title to product name
include 'templates/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <?php if (!empty($product['category'])): ?>
                <li class="breadcrumb-item"><a href="products.php?category=<?= urlencode($product['category']) ?>"><?= htmlspecialchars(ucfirst($product['category'])) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="bg-light text-center py-5 rounded">
                        <i class="fas fa-box fa-5x text-muted"></i>
                    </div>
                </div>
                <div class="col-md-7">
                    <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($product['category'])) ?></span>
                        <?php if (isset($product['in_stock']) && $product['in_stock']): ?>
                            <span class="badge bg-success">In Stock</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="price-tag mb-4">
                        <h2>$<?= number_format($product['price'], 2) ?></h2>
                    </div>
                    
                    <div class="mb-4">
                        <h4>Description:</h4>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                    
                    <?php if (!empty($product['specifications'])): ?>
                        <div class="mb-4">
                            <h4>Specifications:</h4>
                            <?= nl2br(htmlspecialchars($product['specifications'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['amazon_link'])): ?>
                        <a href="<?= htmlspecialchars($product['amazon_link']) ?>" class="btn btn-primary btn-lg" target="_blank">
                            <i class="fab fa-amazon me-2"></i> Buy on Amazon
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-shopping-cart me-2"></i> Not Available
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-5">
            <h3>Related Products</h3>
            <div class="row">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-img-top bg-light text-center py-5">
                                <i class="fas fa-box fa-2x text-muted"></i>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($relatedProduct['name']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($relatedProduct['description'], 0, 80)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag">$<?= number_format($relatedProduct['price'], 2) ?></span>
                                    <a href="product.php?id=<?= $relatedProduct['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?> 