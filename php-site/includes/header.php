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
                    <div class="user-menu" id="userMenu">
                        <button class="user-menu-btn" onclick="toggleUserMenu(event)">
                            <span class="user-avatar"><?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?></span>
                            <span class="user-name-text"><?= htmlspecialchars($currentUser['first_name']) ?></span>
                            <span class="dropdown-arrow">&#9662;</span>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <div class="dropdown-header">
                                <div class="dropdown-avatar"><?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?></div>
                                <div class="dropdown-user-info">
                                    <span class="dropdown-user-name"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></span>
                                    <span class="dropdown-user-role"><?= ucfirst($currentUser['role']) ?></span>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="/profile.php" class="dropdown-item">
                                <span class="dropdown-icon">&#128100;</span>
                                Profile
                            </a>
                            <a href="/orders.php" class="dropdown-item">
                                <span class="dropdown-icon">&#128230;</span>
                                My Orders
                            </a>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/admin.php" class="dropdown-item panel-item admin-item">
                                <span class="dropdown-icon">&#9881;</span>
                                Admin Panel
                            </a>
                            <?php elseif (in_array($currentUser['role'], ['manager', 'supplier'])): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/manager.php" class="dropdown-item panel-item manager-item">
                                <span class="dropdown-icon">&#128202;</span>
                                Manager Panel
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="/logout.php" class="dropdown-item logout-item">
                                <span class="dropdown-icon">&#128682;</span>
                                Logout
                            </a>
                        </div>
                    </div>
                    <script>
                    function toggleUserMenu(e) {
                        e.stopPropagation();
                        var dropdown = document.getElementById('userDropdown');
                        var menu = document.getElementById('userMenu');
                        dropdown.classList.toggle('show');
                        menu.classList.toggle('active');
                    }
                    document.addEventListener('click', function(e) {
                        var menu = document.getElementById('userMenu');
                        var dropdown = document.getElementById('userDropdown');
                        if (!menu.contains(e.target)) {
                            dropdown.classList.remove('show');
                            menu.classList.remove('active');
                        }
                    });
                    </script>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-outline">Sign In</a>
                    <a href="/register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="main">
