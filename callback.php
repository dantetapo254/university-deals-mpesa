<?php
require __DIR__ . '/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
// Inside callback.php after decoding JSON payload
if ($resultCode == 0) {
    // 1. Fetch pending transaction using CheckoutRequestID
    $stmt = $pdo->prepare("SELECT user_id, amount FROM transactions WHERE checkout_request_id = :checkout_id AND status = 'PENDING'");
    $stmt->execute([':checkout_id' => $checkoutRequestID]);
    $tx = $stmt->fetch();

    if ($tx) {
        // 2. Update wallet balance
        $updateWallet = $pdo->prepare("UPDATE users SET balance = balance + :amount WHERE id = :user_id");
        $updateWallet->execute([
            ':amount' => $tx['amount'],
            ':user_id' => $tx['user_id']
        ]);

        // 3. Mark transaction as SUCCESS
        $updateTx = $pdo->prepare("UPDATE transactions SET status = 'SUCCESS', mpesa_receipt = :receipt WHERE checkout_request_id = :checkout_id");
        $updateTx->execute([
            ':receipt' => $mpesaReceiptNumber,
            ':checkout_id' => $checkoutRequestID
        ]);
    }
}
file_put_contents(__DIR__ . '/callback.log', date('c') . ' ' . $raw . PHP_EOL, FILE_APPEND);

try {
    $callback = $data['Body']['stkCallback'] ?? null;
    if (!$callback) {
        exit;
    }

    $checkoutRequestId = $callback['CheckoutRequestID'] ?? '';
    $merchantRequestId = $callback['MerchantRequestID'] ?? '';
    $resultCode = (int) ($callback['ResultCode'] ?? -1);
    $resultDesc = $callback['ResultDesc'] ?? '';

    $pdo->beginTransaction();

    if ($resultCode === 0) {
        $items = $callback['CallbackMetadata']['Item'] ?? [];
        $receipt = null;
        $amount = null;
        $phone = null;
        $transactionDate = null;

        foreach ($items as $item) {
            switch ($item['Name'] ?? '') {
                case 'MpesaReceiptNumber': $receipt = $item['Value'] ?? null; break;
                case 'Amount': $amount = $item['Value'] ?? null; break;
                case 'PhoneNumber': $phone = $item['Value'] ?? null; break;
                case 'TransactionDate': $transactionDate = $item['Value'] ?? null; break;
            }
        }

        // 1. Update mpesa_transactions
        $stmt = $pdo->prepare(
            'UPDATE mpesa_transactions
             SET status = "PAID", merchant_request_id = ?, result_code = ?, result_description = ?,
                 mpesa_receipt = ?, paid_amount = ?, paid_phone = ?, transaction_date = ?, updated_at = NOW()
             WHERE checkout_request_id = ?'
        );
        $stmt->execute([$merchantRequestId, $resultCode, $resultDesc, $receipt, $amount, $phone, $transactionDate, $checkoutRequestId]);

        // 2. Fetch associated transaction details
        $getTx = $pdo->prepare('SELECT user_id, order_id, amount FROM mpesa_transactions WHERE checkout_request_id = ?');
        $getTx->execute([$checkoutRequestId]);
        $tx = $getTx->fetch(PDO::FETCH_ASSOC);

        if ($tx) {
            $userId = $tx['user_id'];
            $orderId = $tx['order_id'];
            $creditAmount = $tx['amount'];

            // 3. Update Wallet Balance
            $updateUser = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
            $updateUser->execute([$creditAmount, $userId]);

            // 4. Update Wallet Transaction Status
            $updateWalletTx = $pdo->prepare('UPDATE wallet_transactions SET status = "completed", mpesa_receipt = ? WHERE transaction_reference = ?');
            $updateWalletTx->execute([$receipt, $orderId]);
        }
    } else {
        // Handle Failed Transaction
        $stmt = $pdo->prepare(
            'UPDATE mpesa_transactions SET status = "FAILED", merchant_request_id = ?, result_code = ?, result_description = ?, updated_at = NOW() WHERE checkout_request_id = ?'
        );
        $stmt->execute([$merchantRequestId, $resultCode, $resultDesc, $checkoutRequestId]);

        $getTx = $pdo->prepare('SELECT order_id FROM mpesa_transactions WHERE checkout_request_id = ?');
        $getTx->execute([$checkoutRequestId]);
        $orderId = $getTx->fetchColumn();

        if ($orderId) {
            $updateWalletTx = $pdo->prepare('UPDATE wallet_transactions SET status = "failed" WHERE transaction_reference = ?');
            $updateWalletTx->execute([$orderId]);
        }
    }

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Error processing callback']);
}