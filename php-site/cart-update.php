<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$itemId = intval($_POST['item_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$itemId) {
    header('Location: /cart.php');
    exit;
}

$db = getDB();
$user = getCurrentUser();

// Get cart item
$stmt = $db->prepare("
    SELECT ci.*, p.stock_quantity as stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.id = ? AND ci.user_id = ?
");
$stmt->execute([$itemId, $user['id']]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: /cart.php');
    exit;
}

if ($action === 'increase' && $item['quantity'] < $item['stock']) {
    $stmt = $db->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?");
    $stmt->execute([$itemId]);
} elseif ($action === 'decrease') {
    if ($item['quantity'] > 1) {
        $stmt = $db->prepare("UPDATE cart_items SET quantity = quantity - 1 WHERE id = ?");
        $stmt->execute([$itemId]);
    } else {
        $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmt->execute([$itemId]);
    }
}

header('Location: /cart.php');
exit;
