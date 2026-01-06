<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();
$pageTitle = 'My Orders';
$db = getDB();
$user = getCurrentUser();

// Get orders
$stmt = $db->prepare("
    SELECT o.*, COUNT(oi.id) as items_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>My Orders</h1>

    <div class="profile-layout">
        <aside class="profile-sidebar">
            <nav class="profile-nav">
                <a href="/profile.php">Personal Info</a>
                <a href="/orders.php" class="active">My Orders</a>
                <a href="/change-password.php">Change Password</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </aside>

        <div class="profile-main">
            <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <p>You don't have any orders yet</p>
                <a href="/products.php" class="btn btn-primary">Browse Catalog</a>
            </div>
            <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-number">Order #<?= $order['id'] ?></span>
                            <span class="order-date"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <span class="order-status status-<?= $order['status'] ?>">
                            <?php
                            echo match($order['status']) {
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                                default => $order['status']
                            };
                            ?>
                        </span>
                    </div>
                    <div class="order-info">
                        <p>Items: <?= $order['items_count'] ?></p>
                        <p class="order-total">Total: $<?= number_format($order['total_amount'], 2) ?></p>
                    </div>
                    <a href="/order.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm">View Details</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
