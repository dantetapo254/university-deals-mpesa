<?php
require __DIR__ . '/db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name && $email && $phone && $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone, $hash]);
            $success = 'Account created successfully! <a href="login.php">Login here</a>';
        } catch (PDOException $e) {
            $error = 'Email address already exists or DB error.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Register - Wallet App</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
.card{background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);width:340px}
input{width:100%;padding:10px;margin:8px 0 16px;box-sizing:border-box;border:1px solid #ccc;border-radius:6px}
button{width:100%;padding:12px;background:#28a745;color:#fff;border:0;border-radius:6px;font-weight:bold;cursor:pointer}
.msg{margin-bottom:12px;padding:10px;border-radius:6px;font-size:14px}
.error{background:#f8d7da;color:#721c24}.success{background:#d4edda;color:#155724}
</style>
</head>
<body>
<div class="card">
    <h2>Register Account</h2>
    <?php if ($error): ?><div class="msg error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg success"><?= $success ?></div><?php endif; ?>
    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="name" required placeholder="John Doe">
        <label>Email</label>
        <input type="email" name="email" required placeholder="john@example.com">
        <label>M-PESA Phone Number</label>
        <input type="tel" name="phone" required placeholder="0712345678">
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Register</button>
    </form>
    <p style="text-align:center;font-size:14px;margin-top:15px">Already registered? <a href="login.php">Login</a></p>
</div>
</body>
</html>