<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$pageTitle = 'Home';

$db = getDB();
$featuredProducts = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1>Construction Materials for Your Project</h1>
        <p>Wide range of quality materials at the best prices</p>
        <a href="/products.php" class="btn btn-primary btn-lg">Browse Catalog</a>
    </div>
</section>

<section class="categories-section">
    <div class="container">
        <h2>Product Categories</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <a href="/products.php?category=<?= $category['id'] ?>" class="category-card">
                <div class="category-icon">
                    <img src="<?= htmlspecialchars(getCategoryImage($category)) ?>" alt="<?= htmlspecialchars($category['name']) ?>" onerror="this.onerror=null; this.style.display='none'">
                </div>
                <h3><?= htmlspecialchars($category['name']) ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="featured-section">
    <div class="container">
        <h2>Featured Products</h2>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <a href="/product.php?id=<?= $product['id'] ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars(getProductImage($product)) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.onerror=null; this.style.opacity='0'">
                    </div>
                    <div class="product-info">
                        <span class="product-category"><?= htmlspecialchars($product['category_name'] ?? '') ?></span>
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                    </div>
                </a>
                <?php if (isLoggedIn()): ?>
                <form action="/cart-add.php" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Add to Cart</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center">
            <a href="/products.php" class="btn btn-outline">View All Products</a>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3>Fast Delivery</h3>
                <p>We deliver nationwide</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Best Prices</h3>
                <p>Competitive pricing guaranteed</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <h3>Quality</h3>
                <p>Only certified materials</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>24/7 Support</h3>
                <p>Always here to help</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
