<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = getCurrentUser();
$role = $user['role'] ?? 'customer';

// Only managers, suppliers, and admins can access
if (!in_array($role, ['manager', 'supplier', 'admin'])) {
    header('Location: /');
    exit;
}

$db = getDB();
$pageTitle = 'Manager Panel';
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update order status
    if ($action === 'update_order_status' && isset($_POST['order_id'], $_POST['status'])) {
        $orderId = intval($_POST['order_id']);
        $status = $_POST['status'];
        if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            $message = 'Order status updated';
        }
    }

    // Delete product
    if ($action === 'delete_product' && isset($_POST['product_id'])) {
        $productId = intval($_POST['product_id']);
        // Delete image if exists
        $imagePath = __DIR__ . '/images/products/' . $productId . '.jpg';
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        $message = 'Product deleted';
    }

    // Delete category
    if ($action === 'delete_category' && isset($_POST['category_id'])) {
        $categoryId = intval($_POST['category_id']);
        // Check if has products
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Cannot delete category with products';
        } else {
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$categoryId]);
            $message = 'Category deleted';
        }
    }
}

// Get tab
$tab = $_GET['tab'] ?? 'products';

// Fetch data
$products = [];
$categories = [];
$orders = [];

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($tab === 'products') {
    $products = $db->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
    ")->fetchAll();
}

if ($tab === 'orders') {
    $orders = $db->query("
        SELECT o.*, u.first_name, u.last_name, u.email,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ")->fetchAll();
}

$statusLabels = [
    'pending' => ['label' => 'Pending', 'color' => '#f59e0b'],
    'processing' => ['label' => 'Processing', 'color' => '#3b82f6'],
    'shipped' => ['label' => 'Shipped', 'color' => '#8b5cf6'],
    'delivered' => ['label' => 'Delivered', 'color' => '#10b981'],
    'cancelled' => ['label' => 'Cancelled', 'color' => '#ef4444'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="manager-container">
    <div class="manager-header">
        <h1>Manager Panel</h1>
        <p>Product & Order Management</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="manager-tabs">
        <a href="?tab=products" class="tab <?= $tab === 'products' ? 'active' : '' ?>">Products</a>
        <a href="?tab=categories" class="tab <?= $tab === 'categories' ? 'active' : '' ?>">Categories</a>
        <a href="?tab=orders" class="tab <?= $tab === 'orders' ? 'active' : '' ?>">Orders</a>
    </div>

    <div class="manager-content">
        <?php if ($tab === 'products'): ?>
        <!-- Products CRUD -->
        <div class="section-header">
            <h2>Products</h2>
            <a href="/product-edit.php" class="btn btn-success">+ Add Product</a>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card-admin">
                <div class="product-image">
                    <img src="<?= htmlspecialchars(getProductImage($p)) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                         onerror="this.onerror=null; this.style.opacity='0'">
                    <span class="product-status <?= $p['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <p class="category"><?= htmlspecialchars($p['category_name'] ?? 'No category') ?></p>
                    <div class="product-meta">
                        <span class="price">$<?= number_format($p['price'], 2) ?></span>
                        <span class="stock">Stock: <?= $p['stock_quantity'] ?? $p['stock'] ?? 0 ?> <?= $p['unit'] ?></span>
                    </div>
                </div>
                <div class="product-actions">
                    <a href="/product-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this product?')">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'categories'): ?>
        <!-- Categories CRUD -->
        <div class="section-header">
            <h2>Categories</h2>
            <a href="/category-edit.php" class="btn btn-success">+ Add Category</a>
        </div>

        <div class="categories-grid">
            <?php foreach ($categories as $c):
                $productCount = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $productCount->execute([$c['id']]);
                $count = $productCount->fetchColumn();
            ?>
            <div class="category-card">
                <div class="category-image">
                    <img src="<?= htmlspecialchars(getCategoryImage($c)) ?>" alt="<?= htmlspecialchars($c['name']) ?>"
                         onerror="this.onerror=null; this.style.opacity='0'">
                </div>
                <div class="category-info">
                    <h3><?= htmlspecialchars($c['name']) ?></h3>
                    <p><?= htmlspecialchars($c['description'] ?? '') ?></p>
                    <span class="product-count"><?= $count ?> products</span>
                </div>
                <div class="category-actions">
                    <a href="/category-edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if ($count == 0): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'orders'): ?>
        <!-- Orders Management -->
        <div class="section-header">
            <h2>Orders</h2>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td>
                        <?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?><br>
                        <small><?= htmlspecialchars($o['email']) ?></small>
                    </td>
                    <td><?= $o['item_count'] ?></td>
                    <td><strong>$<?= number_format($o['total_amount'], 2) ?></strong></td>
                    <td><?= date('M d, Y H:i', strtotime($o['created_at'])) ?></td>
                    <td>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="status" onchange="this.form.submit()"
                                    style="background: <?= $statusLabels[$o['status']]['color'] ?>20; border-color: <?= $statusLabels[$o['status']]['color'] ?>">
                                <?php foreach ($statusLabels as $key => $val): ?>
                                <option value="<?= $key ?>" <?= $o['status'] === $key ? 'selected' : '' ?>><?= $val['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
.manager-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.manager-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 20px;
    color: white;
}

.manager-header h1 { margin: 0 0 5px 0; }
.manager-header p { margin: 0; opacity: 0.9; }

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.manager-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab {
    padding: 12px 24px;
    background: rgba(255,255,255,0.8);
    border-radius: 8px;
    text-decoration: none;
    color: #475569;
    font-weight: 500;
}
.tab:hover { background: rgba(255,255,255,0.95); }
.tab.active { background: #6366f1; color: white; }

.manager-content {
    background: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 12px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}
.section-header h2 { margin: 0; color: #1e3a5f; }

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-card-admin {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.product-card-admin .product-image {
    position: relative;
    height: 180px;
    background: #f1f5f9;
}
.product-card-admin .product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.product-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.product-status.active { background: #d1fae5; color: #065f46; }
.product-status.inactive { background: #fee2e2; color: #991b1b; }

.product-card-admin .product-info {
    padding: 15px;
}
.product-card-admin h3 {
    margin: 0 0 5px 0;
    font-size: 1rem;
    color: #1e3a5f;
}
.product-card-admin .category {
    color: #64748b;
    font-size: 0.85rem;
    margin: 0 0 10px 0;
}
.product-meta {
    display: flex;
    justify-content: space-between;
}
.product-meta .price {
    font-weight: 700;
    color: #10b981;
}
.product-meta .stock {
    color: #64748b;
    font-size: 0.85rem;
}

.product-actions {
    display: flex;
    gap: 10px;
    padding: 15px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

/* Categories Grid */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.category-card {
    display: flex;
    gap: 15px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
    align-items: center;
}

.category-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f1f5f9;
}
.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.category-info {
    flex: 1;
}
.category-info h3 {
    margin: 0 0 5px 0;
    color: #1e3a5f;
}
.category-info p {
    margin: 0 0 8px 0;
    color: #64748b;
    font-size: 0.85rem;
}
.product-count {
    font-size: 0.8rem;
    color: #3b82f6;
    font-weight: 500;
}

.category-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table th, .data-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.data-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e3a5f;
}
.data-table tr:hover {
    background: #f8fafc;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-sm { padding: 6px 14px; font-size: 0.85rem; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: #10b981; color: white; }
.btn-success:hover { background: #059669; }
.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }

.inline { display: inline; }

select {
    padding: 8px 12px;
    border-radius: 6px;
    border: 2px solid #e2e8f0;
    font-weight: 500;
    cursor: pointer;
}

@media (max-width: 768px) {
    .manager-tabs { flex-wrap: wrap; }
    .products-grid { grid-template-columns: 1fr; }
    .categories-grid { grid-template-columns: 1fr; }
    .section-header { flex-direction: column; gap: 15px; align-items: flex-start; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
