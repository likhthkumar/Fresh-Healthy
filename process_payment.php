<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

// Razorpay credentials (test keys)
$razorpay_key_id = 'rzp_test_5hWuEYPTaUTZ2Z';
$razorpay_key_secret = 's3rD2RebVvHj3KzTTKRaGXi9';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Get POST data
$amount = (int) ($_POST['amount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? '';
$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$coupon = isset($_POST['coupon']) ? trim($_POST['coupon']) : '';

// Recalculate subtotal and shipping from session cart
$subtotal = 0;
foreach ($cart as $item) {
    $qty = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : 1);
    $price = isset($item['price']) ? floatval(preg_replace('/[^0-9.]/', '', $item['price'])) : 0;
    $subtotal += $price * $qty;
}
$shipping = ($subtotal >= 500) ? 0 : ($subtotal > 0 ? 40 : 0);
$amount = $subtotal + $shipping;

// Coupon logic (apply after shipping is added)
$discount = 0;
if (strtoupper($coupon) === 'FIRST20') {
    // Check if user has previous orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $orderCount = $stmt->fetchColumn();
    if ($orderCount == 0) {
        // Apply 20% discount
        $discount = round($amount * 0.2);
        $amount = $amount - $discount;
    }
}

// Fetch selected address or latest address as fallback
$address = null;
if (!empty($_SESSION['selected_address_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$_SESSION['selected_address_id'], $user_id]);
    $address = $stmt->fetch();
}
if (!$address) {
    // Fallback to latest address
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $address = $stmt->fetch();
}

if (!$cart || !$address || $amount <= 0) {
    echo 'Invalid order/cart/address.';
    exit;
}

if ($payment_method === 'cod') {
    // Store order as COD
    $status = 'pending';
    $method = 'COD';
    $shippingAddress = $address['full_name'] . "\n" .
        $address['street_name'] . ', ' . $address['city'] . ', ' . $address['state'] . ' – ' . $address['postal_code'] . "\n" .
        'Phone: ' . $address['mobile_number'];
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, shipping_address, grand_total, payment_method, payment_status, coupon, discount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $shippingAddress,
        $amount,
        $method,
        $status,
        $coupon,
        $discount
    ]);
    $order_id = $pdo->lastInsertId();
    // Insert each item into order_items
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($cart as $it) {
        $qty = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['qty']) ? (int)$it['qty'] : 1);
        $price = isset($it['price']) ? floatval(preg_replace('/[^0-9.]/', '', $it['price'])) : 0;
        $name = isset($it['name']) ? $it['name'] : 'Item';
        $sub = $price * $qty;
        $itemStmt->execute([$order_id, $name, $price, $qty, $sub]);
    }
    // Update order with subtotal, shipping, and taxes
    $taxes = 0.00;
    $pdo->prepare("UPDATE orders SET subtotal = ?, shipping = ?, taxes = ? WHERE id = ?")
        ->execute([$subtotal, $shipping, $taxes, $order_id]);
    // Send order confirmation email (COD)
    $userEmail = '';
    $userName = '';
    $stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
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
                '<strong>Order ID:</strong> ' . $order_id . '<br><br>' .
                '<table border="1" cellpadding="6" cellspacing="0" width="100%">'
                . '<tr><th>Product</th><th>Qty</th><th>Price</th></tr>' . $productRows . '</table><br>' .
                '<strong>Subtotal:</strong> ₹' . number_format($subtotal, 2) . '<br>' .
                '<strong>Shipping:</strong> ₹' . number_format($shipping, 2) . '<br>' .
                '<strong>Grand Total:</strong> ₹' . number_format($amount, 2) . '<br><br>' .
                '<strong>Delivery Address:</strong><br>' . nl2br(htmlspecialchars($shippingAddress));
            $mail->send();
        } catch (Exception $e) {
            file_put_contents('mail_error.log', date('Y-m-d H:i:s') . ' ' . $mail->ErrorInfo . "\n", FILE_APPEND);
            echo '<div style="color:red;background:#fff3f3;border:1px solid #e57373;padding:1rem;margin:1rem 0;">Mail Error: ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
            echo '<div style="color:red;background:#fff3f3;border:1px solid #e57373;padding:1rem;margin:1rem 0;">Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    
    // Generate PDF invoice for COD orders
    
    // Calculate user's order serial number
    $userOrderSerial = 1;
    if ($user_id) {
        $serialStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND id <= ?");
        $serialStmt->execute([$user_id, $order_id]);
        $userOrderSerial = $serialStmt->fetchColumn();
    }
    
    // Status styling
    $status = 'pending';
    $statusMap = [
        'processing' => ['Processing', '#fff3cd', '#856404'],
        'shipped' => ['Shipped', '#d1ecf1', '#0c5460'],
        'delivered' => ['Delivered', '#d4edda', '#155724'],
        'pending' => ['Pending', '#fff3cd', '#856404'],
        'completed' => ['Completed', '#d4edda', '#155724']
    ];
    $statusInfo = $statusMap[strtolower($status)] ?? ['Pending', '#fff3cd', '#856404'];
    $statusLabel = $statusInfo[0];
    $statusBg = $statusInfo[1];
    $statusColor = $statusInfo[2];
    
    $paymentMethod = 'COD';
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
    $html .= '<div class="invoice-section"><span class="invoice-label">Order ID:</span> <strong>' . htmlspecialchars($order_id) . '</strong></div>';
    $html .= '<div class="invoice-section"><span class="invoice-label">Placed:</span> ' . date('Y-m-d H:i:s') . '</div>';
    $html .= '<div class="invoice-section"><span class="invoice-label">Status:</span> <span class="status-badge">' . $statusLabel . '</span></div>';
    $html .= '<div class="invoice-section"><span class="invoice-label">Placed by:</span> <strong>' . htmlspecialchars($userName) . '</strong></div>';
    $html .= '<div class="invoice-section"><span class="invoice-label">Shipping Address:</span>';
    $html .= '<div class="address-block">' . nl2br(htmlspecialchars($shippingAddress)) . '</div></div>';
    $html .= '<div class="invoice-section"><span class="invoice-label">Payment Method:</span> ' . htmlspecialchars($paymentMethod) . '</div>';
    $html .= '<div class="invoice-section"><span class="invoice-label">Tracking:</span> <a href="' . $trackingLink . '">Track Package</a></div>';
    
    // Item table
    $html .= '<table class="invoice-table"><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>';
    foreach ($cart as $it) {
        $qty = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['qty']) ? (int)$it['qty'] : 1);
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
        . 'Taxes: ₹' . number_format($taxes, 2) . '<br>'
        . '<strong>Grand Total: ₹' . number_format($amount, 2) . '</strong>'
        . '</div>';
    $html .= '</div>';
    
    $dompdf = new Dompdf();
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $pdfPath = "invoices/invoice_{$order_id}.pdf";
    file_put_contents($pdfPath, $dompdf->output());
    
    // Clear cart
    unset($_SESSION['cart']);
    
    // Add JavaScript to clear localStorage cart before redirect
    echo '<script>';
    echo 'localStorage.removeItem("cartItems_guest");';
    echo 'localStorage.removeItem("cartItems");';
    echo 'var userId = localStorage.getItem("currentUserId");';
    echo 'if (userId) {';
    echo '  localStorage.removeItem("cartItems_" + userId);';
    echo '}';
    echo 'localStorage.setItem("orderJustPlaced", "1");';
    echo '</script>';
    
    header('Location: order_confirmation.php?order_id=' . $order_id);
    exit;
}

// For Razorpay, use $amount as the total (already discounted if coupon applied)
$grand = $amount;
$shipping = 0; // Already included in $amount
$total = $grand + $shipping;

if (in_array($payment_method, ['card', 'upi', 'wallet'])) {
    // Razorpay order creation
    require 'vendor/autoload.php';
    $api = new Razorpay\Api\Api($razorpay_key_id, $razorpay_key_secret);
    $razorpay_order = $api->order->create([
        'receipt' => 'order_rcptid_' . time(),
        'amount' => $total * 100, // amount in paise
        'currency' => 'INR',
        'payment_capture' => 1
    ]);
    $razorpay_order_id = $razorpay_order['id'];
    // Store order info in session for later verification
    $_SESSION['razorpay_order_id'] = $razorpay_order_id;
    $_SESSION['cart'] = $cart;
    $_SESSION['amount'] = $total;
    $_SESSION['shipping'] = $shipping;
    $_SESSION['address_id'] = $address['id'];
    // Render Razorpay Checkout page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Pay with Razorpay</title>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    </head>
    <body>
    <h2>Processing Payment...</h2>
    <script>
    var options = {
        "key": "<?= $razorpay_key_id ?>",
        "amount": "<?= $total * 100 ?>",
        "currency": "INR",
        "name": "MyShop",
        "description": "Order Payment",
        "order_id": "<?= $razorpay_order_id ?>",
        "handler": function (response){
            // On successful payment, redirect to payment_success.php
            window.location.href = "payment_success.php?payment_id=" + response.razorpay_payment_id + "&order_id=" + response.razorpay_order_id + "&signature=" + response.razorpay_signature;
        },
        "prefill": {
            "name": "<?= htmlspecialchars($address['full_name']) ?>",
            "email": "",
            "contact": "<?= htmlspecialchars($address['mobile_number']) ?>"
        },
        "theme": {"color": "#3399cc"},
        "modal": {
            "ondismiss": function() {
                document.body.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;"><h2 style="color:#d32f2f;margin-bottom:1.5rem;">Payment Cancelled</h2><button onclick="window.history.back()" style="padding:1rem 2.5rem;font-size:1.2rem;background:#e6f4ea;color:#2e7d32;border:1.5px solid #2e7d32;border-radius:24px;font-weight:600;cursor:pointer;">Go Back</button></div>';
            }
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.open();
    </script>
    <p>If you are not redirected, <a href="#" onclick="rzp1.open();return false;">click here</a> to pay.</p>
    </body>
    </html>
    <?php
    exit;
}

echo 'Invalid payment method.'; 