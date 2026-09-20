<?php
// Demo checkout for University Deals Center.
// Replace the fixed amount with your real cart/order amount.

$orderId = 'UDC' . date('YmdHis') . random_int(100, 999);
$amount = 100000.00; // Replace with your actual verified server-side order total.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>University Deals Center - M-PESA Checkout</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:40px}
.card{max-width:480px;margin:auto;background:#fff;padding:28px;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,.08)}
h1{margin-top:0}.amount{font-size:30px;font-weight:700;margin:15px 0 25px}
label{display:block;font-weight:600;margin:14px 0 7px}
input{width:100%;box-sizing:border-box;padding:13px;border:1px solid #ccc;border-radius:8px;font-size:16px}
button{width:100%;margin-top:20px;padding:14px;border:0;border-radius:8px;font-size:16px;font-weight:700;cursor:pointer}
#message{margin-top:18px;padding:12px;border-radius:8px;display:none}
.small{color:#666;font-size:13px}
</style>
</head>
<body>
<div class="card">
    <h1>M-PESA Checkout</h1>
    <div>University Deals Center</div>
    <div class="amount">KSh <?= number_format($amount, 2) ?></div>

    <form id="payForm">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId) ?>">
        <input type="hidden" name="amount" value="<?= htmlspecialchars($amount) ?>">

        <label for="phone">M-PESA Number</label>
        <input id="phone" name="phone" type="tel" placeholder="0712345678" required>

        <div class="small">You will receive an M-PESA prompt on this phone. Enter your M-PESA PIN on your phone to authorize the payment.</div>

        <button type="submit">Pay KSh <?= number_format($amount, 2) ?></button>
    </form>

    <div id="message"></div>
</div>

<script>
document.getElementById('payForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const message = document.getElementById('message');
    const button = this.querySelector('button');

    button.disabled = true;
    button.textContent = 'Sending M-PESA prompt...';
    message.style.display = 'block';
    message.textContent = 'Please wait...';

    try {
        const response = await fetch('stkpush.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(new FormData(this))
        });

        const data = await response.json();

        message.textContent = data.message || 'Request completed.';

        if (!data.success) {
            button.disabled = false;
            button.textContent = 'Try Again';
        } else {
            button.textContent = 'Prompt Sent';
        }
    } catch (error) {
        message.textContent = 'Could not contact the payment server. Try again.';
        button.disabled = false;
        button.textContent = 'Try Again';
    }
});
</script>
</body>
</html>
