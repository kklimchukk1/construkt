<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();
$pageTitle = 'Checkout';
$db = getDB();
$user = getCurrentUser();
$error = null;

// Get cart items
$stmt = $db->prepare("
    SELECT ci.*, p.name, p.price, p.stock_quantity as stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ?
");
$stmt->execute([$user['id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: /cart.php');
    exit;
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($address) || empty($phone)) {
        $error = 'Please fill in address and phone number';
    } else {
        try {
            $db->beginTransaction();

            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Create order
            $stmt = $db->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, status, shipping_address, shipping_city, shipping_state, shipping_postal_code, phone, notes, created_at)
                VALUES (?, ?, ?, 'pending', ?, '', '', '', ?, ?, NOW())
            ");
            $stmt->execute([$user['id'], $orderNumber, $subtotal, $address, $phone, $notes]);
            $orderId = $db->lastInsertId();

            // Create order items
            foreach ($cartItems as $item) {
                $itemSubtotal = $item['price'] * $item['quantity'];
                $stmt = $db->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price'], $itemSubtotal]);

                // Update stock
                $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Clear cart
            $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user['id']]);

            $db->commit();
            header('Location: /order-success.php?id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'DB Error: ' . $e->getMessage() . ' | Code: ' . $e->getCode();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1>Checkout</h1>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="checkout-layout">
        <div class="checkout-form">
            <form method="POST">
                <h3>Contact Information</h3>
                <div class="form-group">
                    <label for="phone">Phone *</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                </div>

                <h3>Shipping</h3>
                <div class="form-group">
                    <label for="address">Shipping Address *</label>
                    <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="notes">Order Notes</label>
                    <textarea id="notes" name="notes" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">Place Order</button>
            </form>
        </div>

        <div class="checkout-summary">
            <h3>Your Order</h3>
            <div class="checkout-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="checkout-item">
                    <span><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?></span>
                    <span>$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="checkout-total">
                <span>Total:</span>
                <span>$<?= number_format($subtotal, 2) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
