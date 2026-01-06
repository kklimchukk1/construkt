<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$itemId = intval($_POST['item_id'] ?? 0);

if ($itemId) {
    $db = getDB();
    $user = getCurrentUser();

    $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $user['id']]);
}

header('Location: /cart.php');
exit;
