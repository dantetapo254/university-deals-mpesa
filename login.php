<?php
require __DIR__ . '/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Login - Wallet App</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
.card{background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);width:340px}
input{width:100%;padding:10px;margin:8px 0 16px;box-sizing:border-box;border:1px solid #ccc;border-radius:6px}
button{width:100%;padding:12px;background:#007bff;color:#fff;border:0;border-radius:6px;font-weight:bold;cursor:pointer}
.msg{margin-bottom:12px;padding:10px;border-radius:6px;font-size:14px;background:#f8d7da;color:#721c24}
</style>
</head>
<body>
<div class="card">
    <h2>Login</h2>
    <?php if ($error): ?><div class="msg"><?= $error ?></div><?php endif; ?>
    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required placeholder="john@example.com">
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p style="text-align:center;font-size:14px;margin-top:15px">Don't have an account? <a href="register.php">Register</a></p>
</div>
</body>
</html>