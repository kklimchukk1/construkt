<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$pageTitle = 'Products';
$db = getDB();

$categoryId = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

$where = ['p.is_active = 1'];
$params = [];

if ($categoryId) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
}

if ($search) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);

$orderBy = match($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name' => 'p.name ASC',
    default => 'p.created_at DESC'
};

$countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereClause ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Product Catalog</h1>
        <p>Found: <?= $total ?> products</p>
    </div>

    <div class="catalog-layout">
        <aside class="catalog-sidebar">
            <div class="filter-section">
                <h3>Categories</h3>
                <ul class="category-list">
                    <li><a href="/products.php" class="<?= !$categoryId ? 'active' : '' ?>">All Categories</a></li>
                    <?php foreach ($categories as $cat): ?>
                    <li><a href="/products.php?category=<?= $cat['id'] ?>" class="<?= $categoryId == $cat['id'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <div class="catalog-main">
            <div class="catalog-toolbar">
                <form action="/products.php" method="GET" class="search-form">
                    <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search products...">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                <div class="sort-select">
                    <label>Sort by:</label>
                    <select onchange="location.href=this.value">
                        <option value="?sort=newest<?= $categoryId ? "&category=$categoryId" : '' ?>" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="?sort=price_asc<?= $categoryId ? "&category=$categoryId" : '' ?>" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="?sort=price_desc<?= $categoryId ? "&category=$categoryId" : '' ?>" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                        <option value="?sort=name<?= $categoryId ? "&category=$categoryId" : '' ?>" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <div class="no-results"><p>No products found</p></div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <?php $pStock = $product['stock_quantity'] ?? $product['stock'] ?? 0; ?>
                <div class="product-card">
                    <a href="/product.php?id=<?= $product['id'] ?>">
                        <div class="product-image">
                            <img src="<?= htmlspecialchars(getProductImage($product)) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.onerror=null; this.style.opacity='0'">
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?= htmlspecialchars($product['category_name'] ?? '') ?></span>
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                            <span class="stock <?= $pStock > 0 ? 'in-stock' : 'out-of-stock' ?>"><?= $pStock > 0 ? 'In Stock' : 'Out of Stock' ?></span>
                        </div>
                    </a>
                    <?php if (isLoggedIn() && $pStock > 0): ?>
                    <form action="/cart-add.php" method="POST" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Add to Cart</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $categoryId ? "&category=$categoryId" : '' ?><?= $sort ? "&sort=$sort" : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
