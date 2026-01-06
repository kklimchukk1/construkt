<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Quick login handling
if (isset($_GET['quick'])) {
    $quickUsers = [
        'admin' => ['email' => 'admin@construkt.com', 'password' => 'password'],
        'manager' => ['email' => 'manager@construkt.com', 'password' => 'password'],
        'customer' => ['email' => 'customer1@test.com', 'password' => 'password'],
    ];

    if (isset($quickUsers[$_GET['quick']])) {
        $creds = $quickUsers[$_GET['quick']];
        $result = loginUser($creds['email'], $creds['password']);
        if ($result['success']) {
            header('Location: /');
            exit;
        }
    }
}

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$pageTitle = 'Sign In';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            $redirect = $_GET['redirect'] ?? '/';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Construkt</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(135deg, rgba(44, 62, 80, 0.6) 0%, rgba(52, 73, 94, 0.55) 100%),
                url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=1920') center/cover no-repeat fixed;
        }
        body::after {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.02) 10px, rgba(255,255,255,0.02) 20px);
            z-index: -1;
            pointer-events: none;
        }
        .auth-form-container {
            background: rgba(255,255,255,0.95);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .auth-form-container h2 {
            margin: 0 0 25px;
            text-align: center;
            color: #2c3e50;
        }
        .auth-error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #0275d8;
            outline: none;
        }
        .auth-button {
            width: 100%;
            padding: 14px;
            background: #0275d8;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .auth-button:hover {
            background: #0269c2;
        }
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        .auth-links a {
            color: #0275d8;
            text-decoration: none;
        }
        .quick-login-section {
            margin-top: 30px;
        }
        .quick-login-divider {
            text-align: center;
            position: relative;
            margin-bottom: 20px;
        }
        .quick-login-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            border-top: 1px solid #ddd;
        }
        .quick-login-divider span {
            background: white;
            padding: 0 15px;
            color: #888;
            font-size: 14px;
            position: relative;
        }
        .quick-login-buttons {
            display: flex;
            gap: 10px;
        }
        .quick-login-button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .quick-login-button:hover {
            opacity: 0.9;
        }
        .quick-login-button.admin { background: #dc3545; }
        .quick-login-button.manager { background: #6f42c1; }
        .quick-login-button.customer { background: #007bff; }
    </style>
</head>
<body>
    <div class="auth-form-container">
        <h2>Sign In</h2>

        <?php if ($error): ?>
        <div class="auth-error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="auth-button">Login</button>
        </form>

        <div class="auth-links">
            <a href="/register.php">Don't have an account?</a>
        </div>

        <div class="quick-login-section">
            <div class="quick-login-divider">
                <span>Quick Login (Testing)</span>
            </div>
            <div class="quick-login-buttons">
                <a href="/login.php?quick=admin" class="quick-login-button admin">Admin</a>
                <a href="/login.php?quick=manager" class="quick-login-button manager">Manager</a>
                <a href="/login.php?quick=customer" class="quick-login-button customer">Customer</a>
            </div>
        </div>
    </div>
</body>
</html>
