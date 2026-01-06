<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$db = getDB();
$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: /products.php');
    exit;
}

$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, s.company_name as supplier_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /products.php');
    exit;
}

$pageTitle = $product['name'];

// Get related products
$relatedStmt = $db->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
    LIMIT 4
");
$relatedStmt->execute([$product['category_id'], $productId]);
$relatedProducts = $relatedStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="/">Home</a> &raquo;
        <a href="/products.php">Catalog</a> &raquo;
        <?php if ($product['category_name']): ?>
        <a href="/products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a> &raquo;
        <?php endif; ?>
        <span><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="product-detail">
        <div class="product-gallery">
            <img src="<?= htmlspecialchars(getProductImage($product)) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.onerror=null; this.style.opacity='0'" class="main-image">
        </div>

        <div class="product-info-detail">
            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <div class="product-meta">
                <?php if ($product['category_name']): ?>
                <span class="category"><?= htmlspecialchars($product['category_name']) ?></span>
                <?php endif; ?>
                <span class="sku">ID: #<?= $product['id'] ?></span>
            </div>

            <div class="product-price-block">
                <span class="price">$<?= number_format($product['price'], 2) ?></span>
                <?php if ($product['unit']): ?>
                <span class="unit">/ <?= htmlspecialchars($product['unit']) ?></span>
                <?php endif; ?>
            </div>

            <div class="product-stock">
                <?php $stock = $product['stock_quantity'] ?? $product['stock'] ?? 0; ?>
                <?php if ($stock > 0): ?>
                <span class="in-stock">In Stock (<?= $stock ?> available)</span>
                <?php else: ?>
                <span class="out-of-stock">Out of Stock</span>
                <?php endif; ?>
            </div>

            <?php if (isLoggedIn() && $stock > 0): ?>
            <form action="/cart-add.php" method="POST" class="add-to-cart-form-detail">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="redirect" value="/product.php?id=<?= $product['id'] ?>">
                <div class="quantity-input">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Add to Cart</button>
            </form>
            <?php elseif (!isLoggedIn()): ?>
            <p class="login-prompt"><a href="/login.php">Sign in</a> to add items to cart</p>
            <?php endif; ?>

            <?php if ($product['supplier_name']): ?>
            <div class="supplier-info">
                <span>Supplier: <?= htmlspecialchars($product['supplier_name']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($product['description']): ?>
    <div class="product-description">
        <h2>Description</h2>
        <div class="description-content">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($relatedProducts)): ?>
    <section class="related-products">
        <h2>Related Products</h2>
        <div class="products-grid">
            <?php foreach ($relatedProducts as $related): ?>
            <div class="product-card">
                <a href="/product.php?id=<?= $related['id'] ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars(getProductImage($related)) ?>" alt="<?= htmlspecialchars($related['name']) ?>" onerror="this.onerror=null; this.style.opacity='0'">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($related['name']) ?></h3>
                        <p class="product-price">$<?= number_format($related['price'], 2) ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
