<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();
$db = getDB();
$user = getCurrentUser();
$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: /orders.php');
    exit;
}

// Get order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: /orders.php');
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, p.name, p.thumbnail
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$pageTitle = 'Order #' . $orderId;

$statusLabels = [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="/">Home</a> &raquo;
        <a href="/orders.php">My Orders</a> &raquo;
        <span>Order #<?= $orderId ?></span>
    </div>

    <div class="order-detail-page">
        <div class="order-detail-header">
            <div>
                <h1>Order #<?= $orderId ?></h1>
                <p class="order-date">Placed on <?= date('F d, Y \a\t H:i', strtotime($order['created_at'])) ?></p>
            </div>
            <span class="order-status status-<?= $order['status'] ?>">
                <?= $statusLabels[$order['status']] ?? $order['status'] ?>
            </span>
        </div>

        <div class="order-detail-grid">
            <div class="order-items-section">
                <h3>Order Items</h3>
                <div class="order-items-list">
                    <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <div class="order-item-image">
                            <img src="/images/products/<?= $item['product_id'] ?>.jpg" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.onerror=null; this.style.opacity='0'">
                        </div>
                        <div class="order-item-info">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <p>Quantity: <?= $item['quantity'] ?></p>
                            <p>Price: $<?= number_format($item['unit_price'], 2) ?> each</p>
                        </div>
                        <div class="order-item-total">
                            $<?= number_format($item['subtotal'], 2) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="order-summary-section">
                <h3>Order Summary</h3>
                <div class="order-summary-details">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>

                <h3>Shipping Address</h3>
                <p class="shipping-address"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>

                <h3>Contact</h3>
                <p><?= htmlspecialchars($order['phone']) ?></p>

                <?php if ($order['notes']): ?>
                <h3>Notes</h3>
                <p><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-actions">
            <a href="/orders.php" class="btn btn-outline">Back to Orders</a>
            <a href="/products.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    </div>
</div>

<style>
.order-detail-page {
    margin-top: 20px;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.order-detail-header h1 {
    margin-bottom: 5px;
    color: var(--dark);
}

.order-detail-header .order-date {
    color: var(--gray);
}

.order-detail-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 25px;
}

.order-items-section, .order-summary-section {
    background: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 12px;
}

.order-items-section h3, .order-summary-section h3 {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light);
    color: var(--dark);
}

.order-summary-section h3:not(:first-child) {
    margin-top: 25px;
}

.order-item {
    display: grid;
    grid-template-columns: 80px 1fr auto;
    gap: 20px;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #e0e0e0;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.order-item-info h4 {
    margin-bottom: 5px;
    color: var(--dark);
}

.order-item-info p {
    color: var(--gray);
    font-size: 14px;
    margin: 2px 0;
}

.order-item-total {
    font-weight: bold;
    font-size: 1.1rem;
    color: var(--blue);
}

.order-summary-details .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    color: var(--gray);
}

.order-summary-details .summary-row.total {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--dark);
    border-top: 2px solid var(--light);
    margin-top: 10px;
    padding-top: 15px;
}

.shipping-address {
    color: #555;
    line-height: 1.6;
}

.order-actions {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

@media (max-width: 992px) {
    .order-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
