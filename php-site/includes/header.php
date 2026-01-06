<?php
$currentUser = getCurrentUser();
$cartCount = 0;
if ($currentUser) {
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
    $stmt->execute([$currentUser['id']]);
    $cartCount = $stmt->fetch()['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Construkt' ?> - Construction Materials</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="/" class="logo">CONSTRUKT</a>
            <nav class="nav">
                <a href="/">Home</a>
                <a href="/products.php">Products</a>
                <a href="/calculator.php">Calculator</a>
                <a href="/about.php">About</a>
            </nav>
            <div class="header-actions">
                <?php if ($currentUser): ?>
                    <a href="/cart.php" class="cart-link">
                        Cart <?php if ($cartCount > 0): ?><span class="cart-count"><?= $cartCount ?></span><?php endif; ?>
                    </a>
                    <div class="user-menu">
                        <span class="user-name"><?= htmlspecialchars($currentUser['first_name']) ?></span>
                        <div class="user-dropdown">
                            <a href="/profile.php">Profile</a>
                            <a href="/orders.php">My Orders</a>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <a href="/admin.php" class="panel-link admin-link">Admin Panel</a>
                            <?php elseif (in_array($currentUser['role'], ['manager', 'supplier'])): ?>
                                <a href="/manager.php" class="panel-link manager-link">Manager Panel</a>
                            <?php endif; ?>
                            <a href="/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-outline">Sign In</a>
                    <a href="/register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="main">
