<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$orderId = trim($_GET['order_id'] ?? '');

if ($orderId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT status, mpesa_receipt, paid_amount, result_description
     FROM mpesa_transactions
     WHERE order_id = ?
     ORDER BY id DESC LIMIT 1'
);
$stmt->execute([$orderId]);
$row = $stmt->fetch();

echo json_encode([
    'success' => true,
    'payment' => $row ?: null
]);
