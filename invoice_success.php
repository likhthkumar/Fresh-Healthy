<?php
session_start();
file_put_contents('order_debug.log', date('Y-m-d H:i:s') . "\nPOST: " . print_r($_POST, true) . "\n", FILE_APPEND);
require 'db.php';
require 'vendor/autoload.php';  // dompdf

use Dompdf\Dompdf;

/* -------- 1. Collect data -------- */
// $amount = (float)($_POST['amount'] ?? 0);
$amount = 0;
$cartJson = $_POST['cart'] ?? '[]';
$cart = json_decode($cartJson, true);
$userId = $_SESSION['user_id'] ?? 0;  // or 0 if guest

// Fetch selected address from session and DB
$shippingAddress = '';
if (!empty($_SESSION['selected_address_id'])) {
    $addressId = (int)$_SESSION['selected_address_id'];
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$addressId, $userId]);
    $addr = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($addr) {
        $shippingAddress = $addr['full_name'] . "\n" .
            $addr['street_name'] . ', ' . $addr['city'] . ', ' . $addr['state'] . ' – ' . $addr['postal_code'] . "\n" .
            'Phone: ' . $addr['mobile_number'];
    }
}

if (empty($cart)) {
    die('Cart empty or amount invalid.');
}

/* -------- 2. Save order in DB -------- */
$pdo->beginTransaction();

$stmt = $pdo->prepare("INSERT INTO orders (user_id, grand_total, payment_ref, shipping_address) VALUES (?, ?, 'OFFLINE', ?)");
$stmt->execute([$userId, $amount, $shippingAddress]);
$orderId = $pdo->lastInsertId();

// Insert each item and calculate subtotal
$subtotal = 0;
$itemStmt = $pdo->prepare("
  INSERT INTO order_items (order_id, product_name, price, qty, subtotal)
  VALUES (?, ?, ?, ?, ?)
");
foreach ($cart as $it) {
    file_put_contents('order_debug.log', "Inserting item: " . print_r($it, true) . "\n", FILE_APPEND);
    $qty   = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['qty']) ? (int)$it['qty'] : 1);
    if (isset($it['price']) && preg_match('/([0-9]+(?:\.[0-9]+)?)/', $it['price'], $m)) {
        $price = floatval($m[1]);
    } else {
        $price = 0;
    }
    $name  = isset($it['name']) ? $it['name'] : 'Item';
    $sub   = $price * $qty;
    $itemStmt->execute([$orderId, $name, $price, $qty, $sub]);
    $subtotal += $sub;
}
$shipping = 4.99;
$taxes = 0.00;
$grandTotal = $subtotal + $shipping + $taxes;
$pdo->prepare("UPDATE orders SET grand_total = ?, subtotal = ?, shipping = ?, taxes = ?, payment_ref = ? WHERE id = ?")
    ->execute([$grandTotal, $subtotal, $shipping, $taxes, 'OFFLINE', $orderId]);
$pdo->commit();

// 4. Send order confirmation email
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch user email
$userEmail = '';
$userName = '';
$stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $userEmail = $row['email'];
    $userName = $row['first_name'];
}
// DEBUG: Show email being used
echo '<div style="background:#e3f2fd;color:#1565c0;padding:0.7rem 1.2rem;margin:1rem 0;border-radius:8px;font-size:1.1rem;">Debug: Sending order confirmation to <b>' . htmlspecialchars($userEmail ?: 'NO EMAIL') . '</b></div>';
// Fallback test email if not set
if (empty($userEmail)) {
    $userEmail = 'yourtestemail@example.com'; // <-- CHANGE THIS TO YOUR EMAIL FOR TESTING
    $userName = 'Test User';
    echo '<div style="background:#fff3cd;color:#b26a00;padding:0.7rem 1.2rem;margin:1rem 0;border-radius:8px;font-size:1.1rem;">Debug: Using fallback test email: <b>' . htmlspecialchars($userEmail) . '</b></div>';
}

if ($userEmail) {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 2; // Show SMTP debug output
        $mail->Debugoutput = function($str, $level) { file_put_contents('mail_debug.log', $str . "\n", FILE_APPEND); echo "<pre style='color:#333;background:#f8f8f8;border:1px solid #ccc;padding:8px;'>".htmlspecialchars($str)."</pre>"; };
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'freshhealthy999@gmail.com'; // Updated SMTP username
        $mail->Password = 'lzpj llaf awji pbje'; // Updated SMTP app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('freshhealthy999@gmail.com', 'Fresh & Healthy');
        $mail->addAddress($userEmail, $userName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Order Has Been Confirmed!';
        $productRows = '';
        foreach ($cart as $it) {
            $qty = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['qty']) ? (int)$it['qty'] : 1);
            $name = isset($it['name']) ? $it['name'] : 'Item';
            $price = isset($it['price']) ? $it['price'] : 0;
            $productRows .= '<tr><td>' . htmlspecialchars($name) . '</td><td>' . $qty . '</td><td>' . htmlspecialchars($price) . '</td></tr>';
        }
        $mail->Body = 'Thank you for your order!<br><br>' .
            'Your order has been confirmed. Here are your order details:<br><br>' .
            '<strong>Order ID:</strong> ' . $orderId . '<br><br>' .
            '<table border="1" cellpadding="6" cellspacing="0" width="100%">'
            . '<tr><th>Product</th><th>Qty</th><th>Price</th></tr>' . $productRows . '</table><br>' .
            '<strong>Subtotal:</strong> ₹' . number_format($subtotal, 2) . '<br>' .
            '<strong>Shipping:</strong> ₹' . number_format($shipping, 2) . '<br>' .
            '<strong>Grand Total:</strong> ₹' . number_format($grandTotal, 2) . '<br><br>' .
            '<strong>Delivery Address:</strong><br>' . nl2br(htmlspecialchars($shippingAddress));
        $mail->send();
    } catch (Exception $e) {
        file_put_contents('mail_error.log', date('Y-m-d H:i:s') . ' ' . $mail->ErrorInfo . "\n", FILE_APPEND);
        echo '<div style="color:red;background:#fff3f3;border:1px solid #e57373;padding:1rem;margin:1rem 0;">Mail Error: ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
        echo '<div style="color:red;background:#fff3f3;border:1px solid #e57373;padding:1rem;margin:1rem 0;">Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Fetch the full order record for invoice details
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

// Status label logic
$status = strtolower($order['status'] ?? 'processing');
$statusMap = [
    'processing' => ['Processing', '#fff3cd', '#856404'],
    'shipped'    => ['Shipped', '#cce5ff', '#004085'],
    'delivered'  => ['Delivered', '#d4edda', '#155724'],
    'cancelled'  => ['Cancelled', '#f8d7da', '#721c24'],
];
$statusLabel = $statusMap[$status][0] ?? ucfirst($status);
$statusBg = $statusMap[$status][1] ?? '#fff3cd';
$statusColor = $statusMap[$status][2] ?? '#856404';

// Payment method (if available)
$paymentMethod = $order['payment_method'] ?? 'N/A';
$trackingLink = '#'; // Placeholder, update if you have real tracking

// Calculate user's order serial number
$userOrderSerial = 1;
if ($userId) {
    $serialStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND id <= ?");
    $serialStmt->execute([$userId, $orderId]);
    $userOrderSerial = $serialStmt->fetchColumn();
}

// Build the invoice HTML
$html = '<style>
.invoice-box { font-family: "DejaVu Sans", Arial, sans-serif; color: #222; max-width: 700px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 12px; box-shadow: 0 4px 18px rgba(44,62,80,0.08); padding: 2rem; background: #fff; }
.invoice-header { font-size: 2rem; font-weight: 700; color: #2e7d32; margin-bottom: 1.2rem; }
.invoice-section { margin-bottom: 1.1rem; }
.invoice-label { color: #388e3c; font-weight: 600; margin-right: 0.5em; }
.status-badge { display: inline-block; padding: 0.35em 1.2em; border-radius: 20px; font-size: 1rem; font-weight: 600; background: '.$statusBg.'; color: '.$statusColor.'; }
.invoice-table { width: 100%; border-collapse: collapse; margin-top: 1.2rem; margin-bottom: 1.2rem; }
.invoice-table th, .invoice-table td { border: 1px solid #e0e0e0; padding: 10px 8px; text-align: left; }
.invoice-table th { background: #e0f2f1; color: #00695c; font-weight: 600; }
.invoice-table tr:nth-child(even) td { background: #f7fafc; }
.invoice-totals { margin-top: 1.2rem; font-size: 1.08rem; }
.invoice-totals strong { font-size: 1.15rem; }
.address-block { background: #f8f9fa; border-radius: 8px; padding: 0.8rem 1.2rem; margin-top: 0.5rem; margin-bottom: 0.5rem; border-left: 4px solid #66bb6a; font-size: 1.05rem; }
</style>';
$html .= '<div class="invoice-box">';
$html .= '<div class="invoice-header">Order Invoice</div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Order No.:</span> <strong>' . htmlspecialchars($userOrderSerial) . '</strong></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Order ID:</span> <strong>' . htmlspecialchars($order['id']) . '</strong></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Placed:</span> ' . htmlspecialchars($order['created_at']) . '</div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Status:</span> <span class="status-badge">' . $statusLabel . '</span></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Placed by:</span> <strong>' . htmlspecialchars($addr['full_name'] ?? $userName) . '</strong></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Shipping Address:</span>';
$html .= '<div class="address-block">' . nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A')) . '</div></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Payment Method:</span> ' . htmlspecialchars($paymentMethod) . '</div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Tracking:</span> <a href="' . $trackingLink . '">Track Package</a></div>';

// Item table
$html .= '<table class="invoice-table"><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>';
foreach ($cart as $it) {
    $qty = $it['qty'] ?? $it['quantity'] ?? 1;
    $price = (preg_match('/[0-9]+(?:\.[0-9]+)?/', $it['price'], $m) ? floatval($m[0]) : 0);
    $sub = $price * $qty;
    $html .= '<tr>'
           . '<td>' . htmlspecialchars($it['name'] ?? 'Item') . '</td>'
           . '<td>₹' . number_format($price, 2) . '</td>'
           . '<td>' . $qty . '</td>'
           . '<td>₹' . number_format($sub, 2) . '</td>'
           . '</tr>';
}
$html .= '</table>';

// Totals
$html .= '<div class="invoice-totals">'
    . 'Subtotal: ₹' . number_format($subtotal, 2) . '<br>'
    . 'Shipping: ₹' . number_format($shipping, 2) . '<br>'
    . 'Taxes: ₹' . number_format($taxes, 2) . '<br>'
    . '<strong>Grand Total: ₹' . number_format($grandTotal, 2) . '</strong>'
    . '</div>';
$html .= '</div>';

$dompdf = new Dompdf();
$dompdf->set_option('defaultFont', 'DejaVu Sans');
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$pdfPath = "invoices/invoice_{$orderId}.pdf";
file_put_contents($pdfPath, $dompdf->output());

/* -------- 4. Clear cart -------- */
unset($_SESSION['cart']);

/* -------- 5. Show thank‑you + invoice link -------- */
echo <<<HTML
<style>
.confirmation-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: #f4f8fb;
    padding: 2rem;
}
.animated-check {
    width: 140px;
    height: 140px;
    margin-bottom: 1.5rem;
}
.success-title {
    color: #2e7d32;
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-align: center;
}
.success-message {
    color: #333;
    font-size: 1.15rem;
    margin-bottom: 2rem;
    text-align: center;
}
.cta-btn {
    display: inline-block;
    background: #2e7d32;
    color: #fff;
    padding: 0.9rem 2.2rem;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px rgba(44,62,80,0.08);
    transition: background 0.2s;
    margin: 0.5rem;
}
.cta-btn:hover {
    background: #1b5e20;
}
@media (max-width: 600px) {
    .success-title { font-size: 1.4rem; }
    .success-message { font-size: 1rem; }
    .animated-check { width: 70px; height: 70px; }
}
</style>
<div class="confirmation-container">
    <svg class="animated-check" viewBox="0 0 120 120">
      <circle cx="60" cy="60" r="54" fill="none" stroke="#2e7d32" stroke-width="8"/>
      <path fill="none" stroke="#2e7d32" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" d="M35 65 L55 90 L90 35"/>
    </svg>
    <div class="success-title">Order Successful!</div>
    <div class="success-message">Thank you for your purchase.<br>Your order has been placed successfully.</div>
    <a href="my_orders.php" class="cta-btn">View My Orders</a>
    <a href="home_page.html" class="cta-btn" style="background:#fff;color:#2e7d32;border:2px solid #2e7d32;">Continue Shopping</a>
</div>
HTML;
// Output cart-clearing script after confirmation HTML
echo '<script>';
echo 'localStorage.removeItem("cartItems_guest");';
echo 'localStorage.removeItem("cartItems");';
echo 'var userId = localStorage.getItem("currentUserId");';
echo 'if (userId) {';
echo '  localStorage.removeItem("cartItems_user_" + userId);';
echo '}';
echo 'console.log("All localStorage after COD order:");';
echo 'for (var i = 0; i < localStorage.length; i++) {';
echo '  var key = localStorage.key(i);';
echo '  console.log(key + " = " + localStorage.getItem(key));';
echo '}';
echo '</script>';
?>
