<?php
/**
 * Authentication functions - MySQL
 */

session_start();

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Login user
 */
function loginUser(string $email, string $password): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'User not found'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid password'];
    }

    if (!$user['is_active']) {
        return ['success' => false, 'error' => 'Account is disabled'];
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['first_name'];

    return ['success' => true, 'user' => $user];
}

/**
 * Register new user
 */
function registerUser(array $data): array {
    $db = getDB();

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered'];
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (email, password, first_name, last_name, phone, role, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, 'customer', 1, NOW())
    ");

    try {
        $stmt->execute([
            $data['email'],
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null
        ]);

        $userId = $db->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_name'] = $data['first_name'];

        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Logout user
 */
function logoutUser(): void {
    session_destroy();
    $_SESSION = [];
}

/**
 * Check if user has role
 */
function hasRole(string $role): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin(): void {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: /');
        exit;
    }
}

/**
 * Require supplier role
 */
function requireSupplier(): void {
    requireLogin();
    if (!hasRole('supplier') && !hasRole('admin')) {
        header('Location: /');
        exit;
    }
}

/**
 * Require manager role
 */
function requireManager(): void {
    requireLogin();
    if (!hasRole('manager') && !hasRole('supplier') && !hasRole('admin')) {
        header('Location: /');
        exit;
    }
}

/**
 * Check if user is admin
 */
function isAdmin(): bool {
    return hasRole('admin');
}

/**
 * Check if user is manager or higher
 */
function isManager(): bool {
    return hasRole('manager') || hasRole('supplier') || hasRole('admin');
}
