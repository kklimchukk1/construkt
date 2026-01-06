<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();
$pageTitle = 'Order Confirmed';
$orderId = intval($_GET['id'] ?? 0);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="success-page">
        <div class="success-icon">&#10003;</div>
        <h1>Order Placed Successfully!</h1>
        <p>Order Number: <strong>#<?= $orderId ?></strong></p>
        <p>We will contact you shortly to confirm your order.</p>
        <div class="success-actions">
            <a href="/orders.php" class="btn btn-primary">My Orders</a>
            <a href="/products.php" class="btn btn-outline">Continue Shopping</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
