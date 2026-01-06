<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();
$pageTitle = 'Cart';
$db = getDB();
$user = getCurrentUser();

$stmt = $db->prepare("SELECT ci.*, p.name, p.price, p.stock_quantity as stock FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?");
$stmt->execute([$user['id']]);
$cartItems = $stmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
    <div class="empty-cart">
        <p>Your cart is empty</p>
        <a href="/products.php" class="btn btn-primary">Browse Products</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">
        <div class="cart-items">
            <?php foreach ($cartItems as $item): ?>
            <div class="cart-item" data-id="<?= $item['id'] ?>">
                <div class="cart-item-image">
                    <img src="/images/products/<?= $item['product_id'] ?>.jpg" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.onerror=null; this.style.opacity='0'">
                </div>
                <div class="cart-item-info">
                    <h3><a href="/product.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a></h3>
                    <p class="cart-item-price">$<?= number_format($item['price'], 2) ?></p>
                </div>
                <div class="cart-item-quantity">
                    <form action="/cart-update.php" method="POST" class="quantity-form">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <button type="submit" name="action" value="decrease" class="qty-btn">-</button>
                        <span class="qty-value"><?= $item['quantity'] ?></span>
                        <button type="submit" name="action" value="increase" class="qty-btn">+</button>
                    </form>
                </div>
                <div class="cart-item-total">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                <form action="/cart-remove.php" method="POST">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <button type="submit" class="btn-remove" title="Remove">&times;</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <h3>Order Summary</h3>
            <div class="summary-row"><span>Items (<?= count($cartItems) ?>)</span><span>$<?= number_format($subtotal, 2) ?></span></div>
            <div class="summary-row total"><span>Total</span><span>$<?= number_format($subtotal, 2) ?></span></div>
            <a href="/checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
            <a href="/products.php" class="btn btn-outline btn-block">Continue Shopping</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
