<?php
session_start();
require 'db.php';
require 'vendor/autoload.php'; // dompdf and razorpay

use Dompdf\Dompdf;

// Razorpay credentials (test keys)
$razorpay_key_id = 'rzp_test_5hWuEYPTaUTZ2Z';
$razorpay_key_secret = 's3rD2RebVvHj3KzTTKRaGXi9';

// 1. 🔒 Verify Razorpay payment signature
if (!isset($_GET['payment_id'], $_GET['order_id'], $_GET['signature'])) {
    die('Missing payment details.');
}

$razorpay_payment_id = $_GET['payment_id'];
$razorpay_order_id = $_GET['order_id'];
$razorpay_signature = $_GET['signature'];

// Use Razorpay SDK to verify signature
$attributes = [
    'razorpay_order_id' => $razorpay_order_id,
    'razorpay_payment_id' => $razorpay_payment_id,
    'razorpay_signature' => $razorpay_signature
];

try {
    $api = new Razorpay\Api\Api($razorpay_key_id, $razorpay_key_secret);
    $api->utility->verifyPaymentSignature($attributes);
} catch (Exception $e) {
    echo '<h2 style="color:red;">Payment verification failed!</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// 2. Build order in DB
$userId = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$amount = $_SESSION['amount'] ?? 0;
// Fetch selected address or fallback to address_id from session
$addressId = $_SESSION['selected_address_id'] ?? $_SESSION['address_id'] ?? null;

// Fetch and construct shipping address
$shippingAddress = '';
if ($addressId) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$addressId, $userId]);
    $addr = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($addr) {
        $shippingAddress = $addr['full_name'] . "\n" .
            $addr['street_name'] . ', ' . $addr['city'] . ', ' . $addr['state'] . ' – ' . $addr['postal_code'] . "\n" .
            'Phone: ' . $addr['mobile_number'];
    }
}

// Calculate subtotal before inserting order
$subtotal = 0;
foreach ($cart as $it) {
    $qty   = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['qty']) ? (int)$it['qty'] : 1);
    if (isset($it['price']) && preg_match('/([0-9]+(?:\.[0-9]+)?)/', $it['price'], $m)) {
        $price = floatval($m[1]);
    } else {
        $price = 0;
    }
    $name  = isset($it['name']) ? $it['name'] : 'Item';
    $sub   = $price * $qty;
    $subtotal += $sub;
}
$sessionShipping = isset($_SESSION['shipping']) ? $_SESSION['shipping'] : 'NOT SET';
$fallbackShipping = ($subtotal >= 500) ? 0 : ($subtotal > 0 ? 40 : 0);
file_put_contents('order_debug.log', "DEBUG: _SESSION['shipping']=" . var_export($sessionShipping, true) . ", fallbackShipping=$fallbackShipping\n", FILE_APPEND);
$shipping = (isset($_SESSION['shipping']) && $_SESSION['shipping'] > 0) ? $_SESSION['shipping'] : $fallbackShipping;
$taxes = 0.00;
$grandTotal = $subtotal + $shipping + $taxes;
file_put_contents('order_debug.log', "ORDER DEBUG: subtotal=$subtotal, shipping=$shipping, grandTotal=$grandTotal\n", FILE_APPEND);

$pdo->beginTransaction();

// Insert order with payment status, using correct values
$stmt = $pdo->prepare("INSERT INTO orders (user_id, grand_total, subtotal, shipping, taxes, payment_ref, shipping_address, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$userId, $grandTotal, $subtotal, $shipping, $taxes, $razorpay_payment_id, $shippingAddress, 'Razorpay', 'paid']);
$orderId = $pdo->lastInsertId();

// Insert items
$itemStmt = $pdo->prepare("
  INSERT INTO order_items (order_id, product_name, price, qty, subtotal)
  VALUES (?, ?, ?, ?, ?)
");
foreach ($cart as $it) {
    $qty   = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['qty']) ? (int)$it['qty'] : 1);
    if (isset($it['price']) && preg_match('/([0-9]+(?:\.[0-9]+)?)/', $it['price'], $m)) {
        $price = floatval($m[1]);
    } else {
        $price = 0;
    }
    $name  = isset($it['name']) ? $it['name'] : 'Item';
    $sub   = $price * $qty;
    $itemStmt->execute([$orderId, $name, $price, $qty, $sub]);
}
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
if (empty($userEmail)) {
    $userEmail = 'yourtestemail@example.com'; // <-- CHANGE THIS TO YOUR EMAIL FOR TESTING
    $userName = 'Test User';
}

if ($userEmail) {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'freshhealthy999@gmail.com';
        $mail->Password = 'lzpj llaf awji pbje';
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
        // Optionally log email error: $mail->ErrorInfo
    }
}

// 3. Generate PDF invoice
$dompdf = new Dompdf();

// Get order details for comprehensive invoice
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

// Calculate user's order serial number
$userOrderSerial = 1;
if ($userId) {
    $serialStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND id <= ?");
    $serialStmt->execute([$userId, $orderId]);
    $userOrderSerial = $serialStmt->fetchColumn();
}

// Status styling
$status = $order['payment_status'] ?? 'processing';
$statusMap = [
    'processing' => ['Processing', '#fff3cd', '#856404'],
    'shipped' => ['Shipped', '#d1ecf1', '#0c5460'],
    'delivered' => ['Delivered', '#d4edda', '#155724'],
    'pending' => ['Pending', '#fff3cd', '#856404'],
    'completed' => ['Completed', '#d4edda', '#155724']
];
$statusInfo = $statusMap[strtolower($status)] ?? ['Processing', '#fff3cd', '#856404'];
$statusLabel = $statusInfo[0];
$statusBg = $statusInfo[1];
$statusColor = $statusInfo[2];

$paymentMethod = $order['payment_method'] ?? 'Razorpay';
$trackingLink = '#'; // Placeholder, update if you have real tracking

// Build comprehensive invoice HTML
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
$html .= '<div class="invoice-section"><span class="invoice-label">Order ID:</span> <strong>' . htmlspecialchars($orderId) . '</strong></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Placed:</span> ' . htmlspecialchars($order['created_at'] ?? date('Y-m-d H:i:s')) . '</div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Status:</span> <span class="status-badge">' . $statusLabel . '</span></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Placed by:</span> <strong>' . htmlspecialchars($userName) . '</strong></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Shipping Address:</span>';
$html .= '<div class="address-block">' . nl2br(htmlspecialchars($shippingAddress)) . '</div></div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Payment Method:</span> ' . htmlspecialchars($paymentMethod) . '</div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Payment Reference:</span> ' . htmlspecialchars($razorpay_payment_id) . '</div>';
$html .= '<div class="invoice-section"><span class="invoice-label">Tracking:</span> <a href="' . $trackingLink . '">Track Package</a></div>';

// Item table
$html .= '<table class="invoice-table"><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>';
foreach ($cart as $it) {
    $qty = isset($it['qty']) ? $it['qty'] : (isset($it['quantity']) ? $it['quantity'] : 1);
    $price = isset($it['price']) ? floatval(preg_replace('/[^0-9.]/', '', $it['price'])) : 0;
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
    . 'Taxes: ₹' . number_format($taxes ?? 0, 2) . '<br>'
    . '<strong>Grand Total: ₹' . number_format($grandTotal, 2) . '</strong>'
    . '</div>';
$html .= '</div>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();

// save PDF to disk
$pdfPath = "invoices/invoice_{$orderId}.pdf";
file_put_contents($pdfPath, $dompdf->output());

// 4. Clear cart and Razorpay session data
unset($_SESSION['cart'], $_SESSION['razorpay_order_id'], $_SESSION['amount'], $_SESSION['address_id']);

// 5. Redirect to order history or confirmation with proper cart clearing
echo '<script>';
echo 'localStorage.removeItem("cartItems_guest");';
echo 'localStorage.removeItem("cartItems");';
echo 'var userId = localStorage.getItem("currentUserId");';
echo 'if (userId) {';
echo '  localStorage.removeItem("cartItems_" + userId);';
echo '}';
echo 'localStorage.setItem("orderJustPlaced", "1");';
echo 'window.location.href = "my_orders.php";';
echo '</script>';
exit;
?>
