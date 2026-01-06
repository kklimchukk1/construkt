<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireAdmin();

$db = getDB();
$user = getCurrentUser();
$pageTitle = 'Admin Panel';
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // User actions
    if ($action === 'delete_user' && isset($_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        if ($userId !== $user['id']) {
            $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $message = 'User deleted';
        }
    }

    if ($action === 'update_role' && isset($_POST['user_id'], $_POST['role'])) {
        $userId = intval($_POST['user_id']);
        $role = $_POST['role'];
        if (in_array($role, ['admin', 'manager', 'supplier', 'customer']) && $userId !== $user['id']) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $userId]);
            $message = 'User role updated';
        }
    }

    // Order actions
    if ($action === 'update_order_status' && isset($_POST['order_id'], $_POST['status'])) {
        $orderId = intval($_POST['order_id']);
        $status = $_POST['status'];
        if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            $message = 'Order status updated';
        }
    }

    // Product actions
    if ($action === 'delete_product' && isset($_POST['product_id'])) {
        $productId = intval($_POST['product_id']);
        $imagePath = __DIR__ . '/images/products/' . $productId . '.jpg';
        if (file_exists($imagePath)) unlink($imagePath);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        $message = 'Product deleted';
    }

    // Category actions
    if ($action === 'delete_category' && isset($_POST['category_id'])) {
        $categoryId = intval($_POST['category_id']);
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
$tab = $_GET['tab'] ?? 'dashboard';

// Fetch data based on tab
$stats = [];
$users = [];
$products = [];
$categories = [];
$orders = [];
$reports = [];

// Dashboard stats
if ($tab === 'dashboard' || $tab === 'reports') {
    $stats = [
        'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'total_orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0,
        'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0,
        'total_revenue' => $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0,
        'month_revenue' => $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?: 0,
        'today_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0,
        'low_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10 AND is_active = 1")->fetchColumn() ?: 0,
    ];
}

if ($tab === 'users') {
    $users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count FROM users u ORDER BY u.created_at DESC")->fetchAll();
}

if ($tab === 'products') {
    $products = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
}

if ($tab === 'categories') {
    $categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

if ($tab === 'orders') {
    $orders = $db->query("SELECT o.*, u.first_name, u.last_name, u.email, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
}

if ($tab === 'reports') {
    // Orders by status
    $reports['orders_by_status'] = $db->query("SELECT status, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders GROUP BY status")->fetchAll();

    // Top products
    $reports['top_products'] = $db->query("
        SELECT p.name, SUM(oi.quantity) as sold, SUM(oi.subtotal) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status != 'cancelled'
        GROUP BY p.id
        ORDER BY sold DESC
        LIMIT 10
    ")->fetchAll();

    // Sales by category
    $reports['sales_by_category'] = $db->query("
        SELECT c.name, COUNT(DISTINCT o.id) as orders, COALESCE(SUM(oi.subtotal), 0) as revenue
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
        GROUP BY c.id
        ORDER BY revenue DESC
    ")->fetchAll();

    // Monthly revenue
    $reports['monthly_revenue'] = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
        FROM orders
        WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month DESC
    ")->fetchAll();

    // Users by role
    $reports['users_by_role'] = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();
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

<div class="admin-container">
    <div class="admin-header">
        <h1>Admin Panel</h1>
        <p>Full System Control</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-tabs">
        <a href="?tab=dashboard" class="tab <?= $tab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="?tab=products" class="tab <?= $tab === 'products' ? 'active' : '' ?>">Products</a>
        <a href="?tab=categories" class="tab <?= $tab === 'categories' ? 'active' : '' ?>">Categories</a>
        <a href="?tab=orders" class="tab <?= $tab === 'orders' ? 'active' : '' ?>">Orders</a>
        <a href="?tab=users" class="tab <?= $tab === 'users' ? 'active' : '' ?>">Users</a>
        <a href="?tab=reports" class="tab <?= $tab === 'reports' ? 'active' : '' ?>">Reports</a>
    </div>

    <div class="admin-content">
        <?php if ($tab === 'dashboard'): ?>
        <!-- Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">&#128176;</span>
                <div class="stat-info">
                    <span class="stat-number">$<?= number_format($stats['total_revenue'], 2) ?></span>
                    <span class="stat-label">Total Revenue</span>
                </div>
            </div>
            <div class="stat-card highlight">
                <span class="stat-icon">&#128200;</span>
                <div class="stat-info">
                    <span class="stat-number">$<?= number_format($stats['month_revenue'], 2) ?></span>
                    <span class="stat-label">Monthly Revenue</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#128722;</span>
                <div class="stat-info">
                    <span class="stat-number"><?= $stats['total_orders'] ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>
            </div>
            <div class="stat-card warning">
                <span class="stat-icon">&#9888;</span>
                <div class="stat-info">
                    <span class="stat-number"><?= $stats['pending_orders'] ?></span>
                    <span class="stat-label">Pending Orders</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#128100;</span>
                <div class="stat-info">
                    <span class="stat-number"><?= $stats['total_users'] ?></span>
                    <span class="stat-label">Users</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#128230;</span>
                <div class="stat-info">
                    <span class="stat-number"><?= $stats['total_products'] ?></span>
                    <span class="stat-label">Products</span>
                </div>
            </div>
        </div>

        <div class="quick-links">
            <a href="?tab=products" class="quick-link">
                <span>&#128230;</span>
                <span>Manage Products</span>
            </a>
            <a href="?tab=orders" class="quick-link">
                <span>&#128722;</span>
                <span>View Orders</span>
            </a>
            <a href="?tab=users" class="quick-link">
                <span>&#128100;</span>
                <span>Manage Users</span>
            </a>
            <a href="?tab=reports" class="quick-link">
                <span>&#128202;</span>
                <span>View Reports</span>
            </a>
        </div>

        <?php elseif ($tab === 'products'): ?>
        <!-- Products CRUD -->
        <div class="section-header">
            <h2>Products</h2>
            <a href="/product-edit.php" class="btn btn-success">+ Add Product</a>
        </div>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
            <div class="product-card-admin">
                <div class="product-image">
                    <img src="<?= htmlspecialchars(getProductImage($p)) ?>" onerror="this.onerror=null; this.style.opacity='0'">
                    <span class="product-status <?= $p['is_active'] ? 'active' : 'inactive' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <p class="category"><?= htmlspecialchars($p['category_name'] ?? 'No category') ?></p>
                    <div class="product-meta">
                        <span class="price">$<?= number_format($p['price'], 2) ?></span>
                        <span class="stock">Stock: <?= $p['stock_quantity'] ?? $p['stock'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="product-actions">
                    <a href="/product-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
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
                $count = $db->query("SELECT COUNT(*) FROM products WHERE category_id = " . $c['id'])->fetchColumn();
            ?>
            <div class="category-card">
                <div class="category-image">
                    <img src="<?= htmlspecialchars(getCategoryImage($c)) ?>" onerror="this.onerror=null; this.style.opacity='0'">
                </div>
                <div class="category-info">
                    <h3><?= htmlspecialchars($c['name']) ?></h3>
                    <p><?= htmlspecialchars($c['description'] ?? '') ?></p>
                    <span class="product-count"><?= $count ?> products</span>
                </div>
                <div class="category-actions">
                    <a href="/category-edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <?php if ($count == 0): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
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
        <!-- Orders -->
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
                    <td><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?><br><small><?= htmlspecialchars($o['email']) ?></small></td>
                    <td><?= $o['item_count'] ?></td>
                    <td><strong>$<?= number_format($o['total_amount'], 2) ?></strong></td>
                    <td><?= date('M d, Y H:i', strtotime($o['created_at'])) ?></td>
                    <td>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="status" onchange="this.form.submit()" style="background: <?= $statusLabels[$o['status']]['color'] ?>20">
                                <?php foreach ($statusLabels as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php elseif ($tab === 'users'): ?>
        <!-- Users Management -->
        <div class="section-header">
            <h2>Users</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Registered</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= $u['order_count'] ?></td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="role" onchange="this.form.submit()" <?= $u['id'] == $user['id'] ? 'disabled' : '' ?>>
                                <option value="customer" <?= $u['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                <option value="supplier" <?= $u['role'] === 'supplier' ? 'selected' : '' ?>>Supplier</option>
                                <option value="manager" <?= $u['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($u['id'] != $user['id']): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this user and all their data?')">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                        <?php else: ?>
                        <span class="badge">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php elseif ($tab === 'reports'): ?>
        <!-- Reports -->
        <div class="section-header">
            <h2>Reports & Analytics</h2>
        </div>

        <div class="reports-grid">
            <!-- Orders by Status -->
            <div class="report-card">
                <h3>Orders by Status</h3>
                <div class="report-table">
                    <table>
                        <thead><tr><th>Status</th><th>Count</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($reports['orders_by_status'] as $r): ?>
                        <tr>
                            <td><span class="status-badge" style="background: <?= $statusLabels[$r['status']]['color'] ?>20; color: <?= $statusLabels[$r['status']]['color'] ?>"><?= $statusLabels[$r['status']]['label'] ?></span></td>
                            <td><?= $r['count'] ?></td>
                            <td>$<?= number_format($r['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users by Role -->
            <div class="report-card">
                <h3>Users by Role</h3>
                <div class="report-table">
                    <table>
                        <thead><tr><th>Role</th><th>Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($reports['users_by_role'] as $r): ?>
                        <tr>
                            <td><?= ucfirst($r['role']) ?></td>
                            <td><?= $r['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Products -->
            <div class="report-card wide">
                <h3>Top Selling Products</h3>
                <div class="report-table">
                    <table>
                        <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($reports['top_products'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= $r['sold'] ?></td>
                            <td>$<?= number_format($r['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="report-card">
                <h3>Sales by Category</h3>
                <div class="report-table">
                    <table>
                        <thead><tr><th>Category</th><th>Orders</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($reports['sales_by_category'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= $r['orders'] ?></td>
                            <td>$<?= number_format($r['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="report-card wide">
                <h3>Monthly Revenue (Last 12 Months)</h3>
                <div class="report-table">
                    <table>
                        <thead><tr><th>Month</th><th>Orders</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($reports['monthly_revenue'] as $r): ?>
                        <tr>
                            <td><?= $r['month'] ?></td>
                            <td><?= $r['orders'] ?></td>
                            <td>$<?= number_format($r['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
.admin-header { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); padding: 30px; border-radius: 12px; margin-bottom: 20px; color: white; }
.admin-header h1 { margin: 0 0 5px 0; }
.admin-header p { margin: 0; opacity: 0.9; }

.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.admin-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.tab { padding: 12px 24px; background: rgba(255,255,255,0.8); border-radius: 8px; text-decoration: none; color: #475569; font-weight: 500; }
.tab:hover { background: rgba(255,255,255,0.95); }
.tab.active { background: #dc2626; color: white; }

.admin-content { background: rgba(255,255,255,0.95); padding: 30px; border-radius: 12px; }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.section-header h2 { margin: 0; color: #1e3a5f; }

/* Stats Grid */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { display: flex; align-items: center; gap: 15px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; }
.stat-card.highlight { background: #dcfce7; border-color: #86efac; }
.stat-card.warning { background: #fef3c7; border-color: #fcd34d; }
.stat-icon { font-size: 2rem; }
.stat-number { font-size: 1.5rem; font-weight: 700; color: #1e3a5f; }
.stat-label { font-size: 0.85rem; color: #64748b; }
.stat-info { display: flex; flex-direction: column; }

/* Quick Links */
.quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
.quick-link { display: flex; align-items: center; gap: 15px; padding: 20px; background: #f8fafc; border-radius: 12px; text-decoration: none; color: #1e3a5f; font-weight: 500; border: 1px solid #e2e8f0; transition: all 0.2s; }
.quick-link:hover { background: #e2e8f0; transform: translateY(-2px); }
.quick-link span:first-child { font-size: 1.5rem; }

/* Products & Categories Grid */
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.product-card-admin { background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.product-card-admin .product-image { position: relative; height: 160px; background: #f1f5f9; }
.product-card-admin .product-image img { width: 100%; height: 100%; object-fit: cover; }
.product-status { position: absolute; top: 10px; right: 10px; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.product-status.active { background: #d1fae5; color: #065f46; }
.product-status.inactive { background: #fee2e2; color: #991b1b; }
.product-card-admin .product-info { padding: 15px; }
.product-card-admin h3 { margin: 0 0 5px 0; font-size: 1rem; color: #1e3a5f; }
.product-card-admin .category { color: #64748b; font-size: 0.85rem; margin: 0 0 10px 0; }
.product-meta { display: flex; justify-content: space-between; }
.product-meta .price { font-weight: 700; color: #10b981; }
.product-meta .stock { color: #64748b; font-size: 0.85rem; }
.product-actions { display: flex; gap: 10px; padding: 15px; border-top: 1px solid #e2e8f0; background: #f8fafc; }

.categories-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.category-card { display: flex; gap: 15px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; align-items: center; }
.category-image { width: 80px; height: 80px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: #f1f5f9; }
.category-image img { width: 100%; height: 100%; object-fit: cover; }
.category-info { flex: 1; }
.category-info h3 { margin: 0 0 5px 0; color: #1e3a5f; }
.category-info p { margin: 0 0 8px 0; color: #64748b; font-size: 0.85rem; }
.product-count { font-size: 0.8rem; color: #3b82f6; font-weight: 500; }
.category-actions { display: flex; flex-direction: column; gap: 8px; }

/* Data Table */
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { background: #f8fafc; font-weight: 600; color: #1e3a5f; }
.data-table tr:hover { background: #f8fafc; }

/* Reports */
.reports-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
.report-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
.report-card.wide { grid-column: span 2; }
.report-card h3 { margin: 0 0 15px 0; color: #1e3a5f; font-size: 1.1rem; }
.report-table table { width: 100%; border-collapse: collapse; }
.report-table th, .report-table td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.report-table th { background: #f8fafc; font-size: 0.85rem; color: #64748b; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }

/* Buttons */
.btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; }
.btn-sm { padding: 6px 14px; font-size: 0.85rem; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: #10b981; color: white; }
.btn-success:hover { background: #059669; }
.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }
.inline { display: inline; }
.badge { padding: 4px 10px; background: #e2e8f0; border-radius: 20px; font-size: 0.8rem; color: #64748b; }

select { padding: 8px 12px; border-radius: 6px; border: 2px solid #e2e8f0; font-weight: 500; cursor: pointer; }

@media (max-width: 768px) {
    .admin-tabs { flex-direction: column; }
    .products-grid, .categories-grid { grid-template-columns: 1fr; }
    .reports-grid { grid-template-columns: 1fr; }
    .report-card.wide { grid-column: span 1; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
