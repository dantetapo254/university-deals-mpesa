<?php
    
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
requireLogin();

$user = getLoggedInUser($pdo);

// Fetch recent transactions
$stmt =$pdo->prepare('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$transactions =$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Dashboard</title>
    <style>
        header('Link: <style.css>; rel=stylesheet');
        body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .balance-card { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .balance-card h3 { margin: 0 0 10px; font-weight: 400; }
        .balance-card .amount { font-size: 36px; font-weight: bold; }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        label { display: block; margin: 10px 0 5px; font-weight: 600; }
        input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 12px; }
        button { width: 100%; padding: 12px; border: 0; border-radius: 6px; font-weight: bold; color: #fff; cursor: pointer; }
        .btn-deposit { background: #28a745; }
        .btn-withdraw { background: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .completed { background: #d4edda; color: #155724; }
        .pending { background: #fff3cd; color: #856404; }
        .failed { background: #f8d7da; color: #721c24; }
        #alert { display: none; padding: 12px; margin-bottom: 15px; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Welcome, <?= htmlspecialchars($user['name']) ?></h2>
        <a href="logout.php" style="color:#dc3545;text-decoration:none;font-weight:bold">Logout</a>
    </div>

    <div id="alert"></div>

    <div class="balance-card">
        <h3>Current Wallet Balance</h3>
        <div class="amount">KSh <?= number_format($user['wallet_balance'], 2) ?></div>
    </div>

    <div class="actions">
        <!-- DEPOSIT FORM -->
        <div class="card">
            <h3>Deposit via M-PESA</h3>
            <form id="depositForm">
                <label for="dep_amount">Amount (KSh)</label>
                <input type="number" id="dep_amount" name="amount" min="1" step="0.01" required placeholder="100">
                <label for="dep_phone">M-PESA Phone Number</label>
                <input type="tel" id="dep_phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                <button type="submit" class="btn-deposit">Send M-PESA Prompt</button>
            </form>
        </div>

        <!-- WITHDRAW FORM -->
        <div class="card">
            <h3>Withdraw Funds</h3>
            <form id="withdrawForm">
                <label for="wd_amount">Amount (KSh)</label>
                <input type="number" id="wd_amount" name="amount" min="1" step="0.01" required placeholder="100">
                <button type="submit" class="btn-withdraw">Withdraw</button>
            </form>
        </div>
    </div>

    <!-- TRANSACTION HISTORY -->
    <div class="card">
        <h3>Transaction History</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" style="text-align:center">No transactions found.</td></tr>
                <?php else: foreach ($transactions as$tx): ?>
                    <tr>
                        <td style="text-transform:capitalize"><strong><?= htmlspecialchars($tx['type']) ?></strong></td>
                        <td><?= htmlspecialchars($tx['transaction_reference']) ?></td>
                        <td>KSh <?= number_format($tx['amount'], 2) ?></td>
                        <td><span class="badge <?= htmlspecialchars($tx['status']) ?>"><?= strtoupper($tx['status']) ?></span></td>
                        <td><?= htmlspecialchars($tx['created_at']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const showAlert = (msg, isError = false) => {
    const alertBox = document.getElementById('alert');
    alertBox.style.display = 'block';
    alertBox.style.background = isError ? '#f8d7da' : '#d4edda';
    alertBox.style.color = isError ? '#721c24' : '#155724';
    alertBox.textContent = msg;
};

// Deposit Handler
document.getElementById('depositForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true;
    showAlert('Initiating M-PESA prompt...');

    try {
        const res = await fetch('stkpush_deposit.php', {
            method: 'POST',
            body: new URLSearchParams(new FormData(this))
        });
        
        const data = await res.json();
        
        if (data.success) {
            showAlert(data.message);
            pollStatus(data.order_id);
        } else {
            showAlert(data.message, true);
            btn.disabled = false;
        }
    } catch (err) {
        showAlert('Failed to connect to payment endpoint.', true);
        btn.disabled = false;
    }
});

// Withdraw Handler
document.getElementById('withdrawForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    showAlert('Processing withdrawal...');

    try {
        const res = await fetch('withdraw.php', {
            method: 'POST',
            body: new URLSearchParams(new FormData(this))
        });
        const data = await res.json();
        
        if (data.success) {
            showAlert(data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, true);
        }
    } catch (err) {
        showAlert('Withdrawal failed.', true);
    }
});

// Poll Deposit Status until completed/failed
function pollStatus(orderId) {
    let attempts = 0;
    const interval = setInterval(async () => {
        attempts++;
        if (attempts > 12) { // 60 seconds timeout
            clearInterval(interval);
            showAlert('Transaction timed out. Please refresh.', true);
            return;
        }

        try {
            const res = await fetch(`status.php?order_id=${orderId}`);
            const data = await res.json();

            if (data.payment && data.payment.status !== 'PENDING') {
                clearInterval(interval);
                if (data.payment.status === 'PAID') {
                    showAlert('Deposit received! Updating balance...');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('M-PESA transaction failed or cancelled.', true);
                    setTimeout(() => location.reload(), 2000);
                }
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }, 5000);
}
</script>
</body>
</html>