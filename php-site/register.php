<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$pageTitle = 'Register';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? '')
    ];
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Please fill in all required fields';
    } elseif ($data['password'] !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $result = registerUser($data);
        if ($result['success']) {
            header('Location: /');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Construkt</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.6) 0%, rgba(52, 73, 94, 0.55) 100%),
                url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=1920') center/cover no-repeat fixed;
        }
        .auth-form-container {
            background: rgba(255,255,255,0.95);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        .auth-form-container h2 { margin: 0 0 25px; text-align: center; color: #2c3e50; }
        .auth-error-message { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: bold; color: #333; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .auth-button { width: 100%; padding: 14px; background: #0275d8; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .auth-button:hover { background: #0269c2; }
        .auth-links { text-align: center; margin-top: 20px; }
        .auth-links a { color: #0275d8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="auth-form-container">
        <h2>Create Account</h2>
        <?php if ($error): ?><div class="auth-error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="auth-button">Register</button>
        </form>
        <div class="auth-links"><a href="/login.php">Already have an account? Sign In</a></div>
    </div>
</body>
</html>
