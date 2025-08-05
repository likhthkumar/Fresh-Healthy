<?php
session_start();
require 'db.php';

// 1. Enforce login
if (!isset($_SESSION['user_id'])) {
    echo '<p style="color:red;">Please <a href="login.html">Login</a> or <a href="register.html">Register</a> to continue.</p>';
    exit;
}

// 2. Get cart items
$cart = $_SESSION['cart'] ?? [];
$grand = 0;
foreach ($cart as $item) {
    $qty = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : 1);
    $price = isset($item['price']) ? floatval(preg_replace('/[^0-9.]/', '', $item['price'])) : 0;
    $grand += $price * $qty;
}
$shipping = ($grand >= 500) ? 0 : ($grand > 0 ? 40 : 0);
file_put_contents('order_debug.log', "PAYMENT.PHP: grand=$grand, shipping=$shipping\n", FILE_APPEND);
$_SESSION['shipping'] = $shipping;
$total = $grand + $shipping;

// 3. Fetch selected address for user
$user_id = $_SESSION['user_id'];
$address = null;
if (!empty($_SESSION['selected_address_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND id = ?");
    $stmt->execute([$user_id, $_SESSION['selected_address_id']]);
    $address = $stmt->fetch();
}
if (!$address) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $address = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review & Pay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<style>
    body {
        background: linear-gradient(120deg, #e0f7fa 0%, #f8fafc 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', system-ui, sans-serif;
        position: relative;
    }
    .checkout-progress {
        position: absolute;
        top: 2.5rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 2.5rem;
        z-index: 2;
    }
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 0.98rem;
        color: #bdbdbd;
        font-weight: 500;
    }
    .progress-step.active {
        color: #2e7d32;
        font-weight: 700;
    }
    .progress-dot {
        width: 18px; height: 18px;
        border-radius: 50%;
        background: #bdbdbd;
        margin-bottom: 0.3rem;
        border: 2.5px solid #fff;
        box-shadow: 0 1px 4px rgba(44,62,80,0.10);
    }
    .progress-step.active .progress-dot {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        border: 2.5px solid #2e7d32;
    }
    .progress-bar {
        width: 60px; height: 4px;
        background: #bdbdbd;
        border-radius: 2px;
        margin: 0 0.5rem;
    }
    .progress-bar.active {
        background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
    }
    .payment-main {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 10px 40px rgba(44,62,80,0.13);
        max-width: 950px;
        width: 100%;
        padding: 0;
        margin: 3.5rem 0 2rem 0;
        display: flex;
        gap: 0;
        overflow: hidden;
        border: 1.5px solid #e0e0e0;
        position: relative;
    }

    /* Responsive Design for Payment */
    @media (max-width: 1024px) {
        .payment-main {
            max-width: 95%;
            margin: 2rem auto;
        }
        
        .payment-left,
        .payment-right {
            padding: 2rem 1.5rem;
        }
        
        .section-title {
            font-size: 1.3rem;
        }
        
        .order-table th,
        .order-table td {
            padding: 6px 8px;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 0.8rem;
            align-items: flex-start;
            padding-top: 2rem;
        }
        
        .checkout-progress {
            top: 1rem;
            gap: 1.5rem;
        }
        
        .progress-step {
            font-size: 0.85rem;
        }
        
        .progress-dot {
            width: 16px;
            height: 16px;
        }
        
        .progress-bar {
            width: 40px;
            height: 3px;
        }
        
        .payment-main {
            flex-direction: column;
            border-radius: 16px;
            margin: 1rem auto;
            max-width: 100%;
        }
        
        .payment-left {
            border-right: none;
            border-bottom: 1.5px solid #e0e0e0;
            padding: 1.5rem 1rem;
        }
        
        .payment-right {
            padding: 1.5rem 1rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .order-table {
            font-size: 0.85rem;
        }
        
        .order-table th,
        .order-table td {
            padding: 5px 6px;
            font-size: 0.8rem;
        }
        
        .breakdown {
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
        }
        
        .breakdown-row {
            font-size: 0.9rem;
        }
        
        .breakdown-row:last-child {
            font-size: 1.1rem;
        }
        
        .address-box {
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
        }
        
        .method-list {
            gap: 0.8rem;
        }
        
        .pay-method {
            padding: 0.8rem;
            font-size: 0.9rem;
        }
        
        .pay-method span {
            font-size: 0.9rem;
        }
        
        .coupon-section input {
            width: 150px;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }
        
        .coupon-section button {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }
        
        .payBtn {
            padding: 1rem 1.5rem;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 0.3rem;
            align-items: flex-start;
            padding-top: 1.5rem;
        }
        
        .checkout-progress {
            top: 0.8rem;
            gap: 1rem;
        }
        
        .progress-step {
            font-size: 0.75rem;
        }
        
        .progress-dot {
            width: 14px;
            height: 14px;
        }
        
        .progress-bar {
            width: 30px;
            height: 2px;
        }
        
        .payment-main {
            border-radius: 12px;
            margin: 0.3rem auto;
            max-width: 100%;
        }
        
        .payment-left,
        .payment-right {
            padding: 1rem 0.8rem;
        }
        
        .section-title {
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
        }
        
        .order-table {
            font-size: 0.8rem;
        }
        
        .order-table th,
        .order-table td {
            padding: 4px 5px;
            font-size: 0.75rem;
        }
        
        .breakdown {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .breakdown-row {
            font-size: 0.8rem;
        }
        
        .breakdown-row:last-child {
            font-size: 1rem;
        }
        
        .address-box {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .method-list {
            gap: 0.6rem;
        }
        
        .pay-method {
            padding: 0.6rem;
            font-size: 0.8rem;
        }
        
        .pay-method span {
            font-size: 0.8rem;
        }
        
        .coupon-section input {
            width: 120px;
            padding: 0.5rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .coupon-section button {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .payBtn {
            padding: 0.8rem 1.2rem;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 375px) {
        body {
            padding: 0.2rem;
            padding-top: 1.2rem;
        }
        
        .checkout-progress {
            top: 0.6rem;
            gap: 0.8rem;
        }
        
        .progress-step {
            font-size: 0.7rem;
        }
        
        .progress-dot {
            width: 12px;
            height: 12px;
        }
        
        .progress-bar {
            width: 25px;
            height: 2px;
        }
        
        .payment-main {
            border-radius: 10px;
            margin: 0.2rem auto;
        }
        
        .payment-left,
        .payment-right {
            padding: 0.8rem 0.6rem;
        }
        
        .section-title {
            font-size: 1rem;
            margin-bottom: 0.6rem;
        }
        
        .order-table {
            font-size: 0.75rem;
        }
        
        .order-table th,
        .order-table td {
            padding: 3px 4px;
            font-size: 0.7rem;
        }
        
        .breakdown {
            padding: 0.5rem 0.6rem;
            font-size: 0.8rem;
        }
        
        .breakdown-row {
            font-size: 0.75rem;
        }
        
        .breakdown-row:last-child {
            font-size: 0.9rem;
        }
        
        .address-box {
            padding: 0.5rem 0.6rem;
            font-size: 0.75rem;
        }
        
        .method-list {
            gap: 0.5rem;
        }
        
        .pay-method {
            padding: 0.5rem;
            font-size: 0.75rem;
        }
        
        .pay-method span {
            font-size: 0.75rem;
        }
        
        .coupon-section input {
            width: 100px;
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
        }
        
        .coupon-section button {
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
        }
        
        .payBtn {
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
        }
    }
    .payment-left {
        flex: 1.2;
        padding: 2.7rem 2.2rem 2.2rem 2.2rem;
        background: linear-gradient(120deg, #f8fafc 60%, #e0f7fa 100%);
        border-right: 1.5px solid #e0e0e0;
        position: relative;
    }
    .payment-right {
        flex: 1;
        padding: 2.7rem 2.2rem 2.2rem 2.2rem;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
    }
    .section-title {
        font-size: 1.45rem;
        font-weight: 700;
        color: #2e7d32;
        margin-bottom: 1.2rem;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }
    .section-title i {
        font-size: 1.2em;
        color: #43e97b;
    }
    .order-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1.5rem;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(44,62,80,0.04);
    }
    .order-table th, .order-table td {
        padding: 8px 10px;
        text-align: left;
    }
    .order-table th {
        background: #f5f5f5;
        font-weight: 600;
        color: #333;
    }
    .order-table tr:not(:last-child) td {
        border-bottom: 1px solid #eee;
    }
    .breakdown {
        margin-bottom: 1.5rem;
        border-radius: 10px;
        background: #f8fafc;
        padding: 1rem 1.2rem;
        font-size: 1.08rem;
        box-shadow: 0 1px 4px rgba(44,62,80,0.04);
    }
    .breakdown-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        align-items: center;
    }
    .breakdown-row:last-child {
        font-weight: bold;
        font-size: 1.18rem;
        color: #2e7d32;
        margin-bottom: 0;
    }
    .shipping-pill {
        display: inline-block;
        padding: 0.18em 1.1em;
        border-radius: 20px;
        font-size: 0.98em;
        font-weight: 600;
        background: #e0f2f1;
        color: #009688;
        margin-left: 0.5em;
        vertical-align: middle;
    }
    .shipping-pill.free {
        background: #e8f5e9;
        color: #388e3c;
    }
    .address-box {
        border: 1.5px solid #e0e0e0;
        border-radius: 10px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.5rem;
        background: #f9fbe7;
        font-size: 1.05rem;
        box-shadow: 0 1px 4px rgba(44,62,80,0.04);
    }
    .method-list {
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
        margin-bottom: 2.2rem;
    }
    .pay-method {
        background: #f5f5f5;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 1.2rem 1.1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        font-size: 1.13rem;
        font-weight: 500;
        transition: border 0.2s, background 0.2s, box-shadow 0.2s;
        box-shadow: 0 1px 4px rgba(44,62,80,0.04);
        position: relative;
    }
    .pay-method input[type="radio"] {
        accent-color: #2e7d32;
        margin-right: 0.7rem;
        transform: scale(1.2);
    }
    .pay-method.selected, .pay-method:hover {
        border: 2px solid #2e7d32;
        background: linear-gradient(90deg, #e8f5e9 60%, #f8fafc 100%);
        box-shadow: 0 2px 8px rgba(44,62,80,0.10);
    }
    .pay-method span {
        display: flex;
        align-items: center;
        gap: 0.5em;
    }
    .pay-method .fa {
        font-size: 1.25em;
        color: #2e7d32;
    }
    .payBtn {
        width: 100%;
        padding: 18px 0;
        background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 1.22rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 0.7rem;
        box-shadow: 0 4px 16px rgba(44,62,80,0.10);
        letter-spacing: 0.5px;
        transition: background 0.18s, transform 0.18s;
    }
    .payBtn:hover {
        background: linear-gradient(90deg, #38f9d7 0%, #43e97b 100%);
        transform: translateY(-2px) scale(1.03);
    }
    @media (max-width: 900px) {
        .payment-main { flex-direction: column; max-width: 98vw; }
        .payment-left, .payment-right { padding: 1.2rem 0.8rem; }
        .payment-left, .payment-right { border-right: none; border-bottom: 1.5px solid #e0e0e0; }
        .payment-right { border-bottom: none; }
    }
    @media (max-width: 600px) {
        .payment-main { flex-direction: column; padding: 0; }
        .payment-left, .payment-right { padding: 1rem 0.3rem; }
        .checkout-progress { top: 0.7rem; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method card selection UI
    document.querySelectorAll('.pay-method input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.pay-method').forEach(function(card) {
                card.classList.remove('selected');
            });
            this.closest('.pay-method').classList.add('selected');
        });
    });
    // Select first by default
    var first = document.querySelector('.pay-method input[type="radio"]');
    if (first) first.closest('.pay-method').classList.add('selected');

    var applyBtn = document.getElementById('applyCouponBtn');
    if (!applyBtn) return;
    applyBtn.onclick = function() {
        var codeInput = document.getElementById('couponCode');
        var messageDiv = document.getElementById('couponMessage');
        if (!codeInput || !messageDiv) return;
        const code = codeInput.value.trim();
        if (code.toUpperCase() === 'FIRST20') {
            fetch('check_first_order.php')
                .then(res => res.json())
                .then(data => {
                    if (data.isFirstOrder) {
                        // Apply 20% discount
                        const totalElem = document.querySelector('.breakdown-row:last-child span:last-child');
                        let total = parseFloat(totalElem.textContent.replace(/[^\d.]/g, ''));
                        let discount = total * 0.2;
                        let newTotal = total - discount;
                        totalElem.textContent = '₹' + newTotal.toFixed(2);
                        messageDiv.style.color = '#2e7d32';
                        messageDiv.textContent = 'Coupon applied! 20% off on your first order.';
                        // Update Proceed to Pay button
                        var payBtn = document.getElementById('proceedToPayBtn');
                        if (payBtn) {
                            payBtn.textContent = 'Proceed to Pay ₹' + newTotal.toFixed(2);
                        }
                        // Add hidden input for backend
                        if (!document.getElementById('couponInputHidden')) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'coupon';
                            input.value = 'FIRST20';
                            input.id = 'couponInputHidden';
                            document.getElementById('paymentForm').appendChild(input);
                        }
                    } else {
                        messageDiv.style.color = '#e53935';
                        messageDiv.textContent = 'Coupon only valid for your first order.';
                    }
                })
                .catch(function() {
                    messageDiv.style.color = '#e53935';
                    messageDiv.textContent = 'Error checking coupon. Please try again.';
                });
        } else {
            messageDiv.style.color = '#e53935';
            messageDiv.textContent = 'Invalid coupon code.';
        }
    };
});
</script>
</head>
<body>
    <!-- Fixed Stepper Start -->
    <div class="fixed-stepper">
      <div class="step">CART</div>
      <div class="step-separator"></div>
      <div class="step">ADDRESS</div>
      <div class="step-separator"></div>
      <div class="step active">PAYMENT</div>
</div>
    <style>
      .fixed-stepper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        background: #fff;
        box-shadow: 0 2px 16px rgba(44,62,80,0.07);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.7rem 0 0.7rem 0;
      }
      .fixed-stepper .step {
        color: #546e7a;
        font-size: 1.15rem;
        font-weight: 600;
        letter-spacing: 0.25em;
        text-transform: uppercase;
        position: relative;
        background: none;
        border: none;
        padding: 0 0.5rem 0.5rem 0.5rem;
        transition: color 0.2s;
      }
      .fixed-stepper .step.active {
        color: #2ec492;
      }
      .fixed-stepper .step.active::after {
        content: '';
        display: block;
        margin: 0 auto;
        width: 32px;
        height: 3px;
        background: #2ec492;
        border-radius: 2px;
        margin-top: 6px;
      }
      .fixed-stepper .step-separator {
        flex: 1 1 0;
        border-bottom: 2px dotted #bdbdbd;
        margin: 0 0.5rem 0.2rem 0.5rem;
        height: 0;
        min-width: 40px;
        max-width: 120px;
      }
      @media (max-width: 700px) {
        .fixed-stepper {
          font-size: 0.98rem;
          padding: 0.4rem 0 0.4rem 0;
        }
        .fixed-stepper .step {
          font-size: 0.98rem;
          padding: 0 0.3rem 0.3rem 0.3rem;
        }
        .fixed-stepper .step-separator {
          min-width: 18px;
        }
      }
      body, .payment-main {
        padding-top: 70px !important;
      }
    </style>
    <!-- Fixed Stepper End -->
<!-- Back Button -->
<div class="back-btn-container">
    <button class="back-btn-pro" onclick="history.back()"><i class="fa fa-arrow-left"></i> Back</button>
</div>
<style>
  .back-btn-container {
    position: absolute;
    top: 1.5rem;
    left: 1.5rem;
    z-index: 10;
    padding: 0.5rem;
    display: flex;
    align-items: flex-start;
    margin-top: 80px;
  }
  .back-btn-pro {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.6rem;
    background: #f4f8fb;
    color: #2e7d32;
    border: 1.5px solid #2e7d32;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1.08rem;
    box-shadow: 0 2px 8px rgba(44,62,80,0.04);
    cursor: pointer;
    transition: background 0.18s, color 0.18s, border 0.18s, box-shadow 0.18s;
    outline: none;
  }
  .back-btn-pro:hover, .back-btn-pro:focus {
    background: #e8f5e9;
    color: #1b5e20;
    border-color: #1b5e20;
    box-shadow: 0 4px 16px rgba(44,62,80,0.10);
  }
</style>
<div class="payment-main">
    <div class="payment-left">
        <div class="section-title"><i class="fa fa-receipt"></i>Order Summary</div>
        <?php if ($cart): ?>
        <table class="order-table">
            <tr><th>Product</th><th>Price (₹)</th><th>Qty</th><th>Subtotal (₹)</th></tr>
            <?php foreach ($cart as $item): ?>
                <?php
                    $qty = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : 1);
                    $price = isset($item['price']) ? floatval(preg_replace('/[^0-9.]/', '', $item['price'])) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= number_format($price) ?></td>
                    <td><?= $qty ?></td>
                    <td><?= number_format($price * $qty) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="breakdown">
            <div class="breakdown-row"><span>Subtotal</span><span>₹<?= number_format($grand, 2) ?></span></div>
            <div class="breakdown-row"><span>Shipping
                <?php if ($shipping === 0): ?><span class="shipping-pill free">Free</span><?php else: ?><span class="shipping-pill">₹<?= number_format($shipping, 2) ?></span><?php endif; ?>
            </span><span></span></div>
            <div class="breakdown-row"><span>Grand Total</span><span>₹<?= number_format($total, 2) ?></span></div>
        </div>
        <div class="section-title" style="font-size:1.18rem; margin-bottom:0.7rem; color:#333;"><i class="fa fa-location-dot"></i>Delivery Address</div>
        <?php if ($address): ?>
        <div class="address-box">
            <strong><?= htmlspecialchars($address['full_name']) ?></strong><br>
            <?= htmlspecialchars($address['street_name']) ?>,<br>
            <?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?> - <?= htmlspecialchars($address['postal_code']) ?><br>
            <span style="color:#666;">Mobile: <?= htmlspecialchars($address['mobile_number']) ?></span>
        </div>
        <?php else: ?>
            <p style="color:red;">No address found. <a href="address.html">Add Address</a></p>
        <?php endif; ?>
        <?php else: ?>
            <p><a href="cart.html">Go back to cart</a></p>
        <?php endif; ?>
    </div>
    <div class="payment-right">
        <div class="section-title" style="font-size:1.18rem; margin-bottom:0.7rem; color:#333;"><i class="fa fa-credit-card"></i>Select Payment Method</div>
        <?php if ($cart): ?>
        <form id="paymentForm" method="POST" action="process_payment.php">
            <input type="hidden" name="amount" value="<?= $grand ?>">
            <div class="method-list">
                <label class="pay-method"><input type="radio" name="payment_method" value="card" checked> <span><i class="fa fa-credit-card"></i>Card</span></label>
                <label class="pay-method"><input type="radio" name="payment_method" value="upi"> <span><i class="fa fa-mobile-screen-button"></i>UPI</span></label>
                <label class="pay-method"><input type="radio" name="payment_method" value="wallet"> <span><i class="fa fa-wallet"></i>Wallet</span></label>
                <label class="pay-method"><input type="radio" name="payment_method" value="cod"> <span><i class="fa fa-money-bill-wave"></i>Cash on Delivery</span></label>
            </div>
            
            <!-- UPI Testing Note (initially hidden) -->
            <div id="upiTestingNote" style="display: none; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 1rem; margin: 1rem 0; color: #856404; font-size: 0.9rem;">
                <i class="fas fa-info-circle" style="margin-right: 0.5rem; color: #f39c12;"></i>
                <strong>Testing Note:</strong> Please use UPI ID <code style="background: #f8f9fa; padding: 0.2rem 0.4rem; border-radius: 4px; font-family: monospace;">razorpay@success</code> for testing purposes. Other UPI IDs may not work in this test environment.
            </div>
            <!-- Coupon Section -->
            <div class="coupon-section" style="margin: 1.2rem 0 1.5rem 0; text-align: center;">
                <input type="text" id="couponCode" placeholder="Enter coupon code" style="padding: 0.7rem 1.2rem; border-radius: 8px; border: 1.5px solid #e0e0e0; font-size: 1.08rem; width: 180px;">
                <button type="button" id="applyCouponBtn" style="padding: 0.7rem 1.5rem; border-radius: 8px; background: #2e7d32; color: white; border: none; font-weight: 600; margin-left: 0.7rem; cursor: pointer;">Apply</button>
                <div id="couponMessage" style="color: #e53935; margin-top: 0.5rem; font-size: 1.02rem;"></div>
            </div>
            <button type="submit" class="payBtn" id="proceedToPayBtn">Proceed to Pay ₹<?= number_format($total, 2) ?></button>
            <button type="button" onclick="window.history.back()" style="width:100%;margin-top:0.7rem;padding:18px 0;background:#f5f5f5;color:#2e7d32;border:1.5px solid #2e7d32;border-radius:10px;font-size:1.18rem;font-weight:700;cursor:pointer;transition:background 0.18s, color 0.18s, border 0.18s;">Cancel</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Show/hide UPI testing note based on payment method selection
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const upiTestingNote = document.getElementById('upiTestingNote');
        
        if (this.value === 'upi') {
            upiTestingNote.style.display = 'block';
        } else {
            upiTestingNote.style.display = 'none';
        }
    });
});
</script>

</body>
</html>
