<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$productId = intval($_POST['product_id'] ?? 0);
$quantity = max(1, intval($_POST['quantity'] ?? 1));
// Stay on the same page after adding to cart
$redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '/';

if (!$productId) {
    header('Location: ' . $redirect);
    exit;
}

$db = getDB();
$user = getCurrentUser();

// Check if product exists and has stock
$stmt = $db->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();
$stock = $product['stock_quantity'] ?? 0;

if (!$product || $stock < 1) {
    header('Location: ' . $redirect);
    exit;
}

// Check if already in cart
$stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user['id'], $productId]);
$existing = $stmt->fetch();

if ($existing) {
    // Update quantity
    $newQty = min($existing['quantity'] + $quantity, $stock);
    $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQty, $existing['id']]);
} else {
    // Insert new item
    $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $productId, min($quantity, $stock)]);
}

header('Location: ' . $redirect);
exit;
