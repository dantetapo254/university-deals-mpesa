<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';

$user = getLoggedInUser($pdo);

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $amount = (float) ($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        throw new Exception('Enter a valid withdrawal amount.');
    }

    $pdo->beginTransaction();

    // Lock user record for update to prevent race condition
    $stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$user['id']]);
    $currentBalance = (float) $stmt->fetchColumn();

    if ($currentBalance < $amount) {
        throw new Exception('Insufficient wallet balance.');
    }

    // Deduct balance
    $deduct = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?');
    $deduct->execute([$amount, $user['id']]);

    // Record wallet withdrawal transaction
    $ref = 'WD' . date('YmdHis') . random_int(1000, 9999);
    $tx = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, amount, status, transaction_reference) VALUES (?, "withdrawal", ?, "completed", ?)');
    $tx->execute([$user['id'], $amount, $ref]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal of KSh ' . number_format($amount, 2) . ' successful!'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}