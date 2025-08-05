<?php
session_start();
require_once 'db.php';
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) { header('Location: login.html'); exit; }

// Move this block to the top so only the detail is output for AJAX/modal
if (isset($_GET['detail'])) {
    $orderId = (int)$_GET['detail'];
    $order = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $order->execute([$orderId, $userId]);
    $order = $order->fetch(PDO::FETCH_ASSOC);
    if (!$order) { echo '<div class="alert alert-danger">Order not found.</div>'; exit; }
    $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items->execute([$orderId]);
    $items = $items->fetchAll(PDO::FETCH_ASSOC);
    $status = strtolower($order['status'] ?? 'processing');
    $statusMap = [
        'processing' => ['Processing', 'fa-hourglass-half', 'status-processing'],
        'shipped'    => ['Shipped', 'fa-truck', 'status-shipped'],
        'delivered'  => ['Delivered', 'fa-check-circle', 'status-delivered'],
        'cancelled'  => ['Cancelled', 'fa-times-circle', 'status-cancelled'],
    ];
    $statusLabel = $statusMap[$status][0] ?? ucfirst($status);
    $statusIcon = $statusMap[$status][1] ?? 'fa-box';
    $statusClass = $statusMap[$status][2] ?? 'status-processing';
    $invoicePath = "invoices/invoice_{$order['id']}.pdf";
    $hasInvoice = is_file($invoicePath);
    echo '<div class="row">';
    echo '<div class="col-md-5">';
    echo '<div class="order-detail-section"><span class="order-detail-label"><i class="fas fa-hashtag order-detail-icon"></i>Order ID:</span> <strong>'.htmlspecialchars($order['id']).'</strong></div>';
    echo '<div class="order-detail-section"><span class="order-detail-label"><i class="fas fa-calendar-alt order-detail-icon"></i>Placed:</span> '.htmlspecialchars($order['created_at']).'</div>';
    echo '<div class="order-detail-section"><span class="order-detail-label"><i class="fas '.$statusIcon.' order-detail-icon"></i>Status:</span> <span class="order-status-badge '.$statusClass.'">'.$statusLabel.'</span></div>';
    echo '<div class="order-detail-section"><span class="order-detail-label"><i class="fas fa-map-marker-alt order-detail-icon"></i>Shipping Address:</span><br><span style="white-space:pre-line">'.nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A')).'</span></div>';
    echo '<div class="order-detail-section"><span class="order-detail-label"><i class="fas fa-credit-card order-detail-icon"></i>Payment Method:</span> '.htmlspecialchars($order['payment_method'] ?? 'N/A').'</div>';
    echo '<div class="order-detail-section"><span class="order-detail-label"><i class="fas fa-truck order-detail-icon"></i>Tracking:</span> <a href="#">Track Package</a></div>';
    if ($hasInvoice) {
        echo '<a href="'.$invoicePath.'" class="download-invoice-btn" target="_blank"><i class="fas fa-file-invoice"></i> Download Invoice</a>';
    }
    echo '</div>';
    echo '<div class="col-md-7">';
    echo '<table class="table table-bordered order-detail-table mt-3"><thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $sub = $it['price'] * $it['qty'];
        echo '<tr><td>'.htmlspecialchars($it['product_name']).'</td><td>₹'.number_format($it['price'],2).'</td><td>'.$it['qty'].'</td><td>₹'.number_format($sub,2).'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<div class="order-detail-totals">'
        . 'Subtotal: ₹' . number_format($order['subtotal'] ?? 0,2) . '<br>'
        . 'Shipping: ₹' . number_format($order['shipping'] ?? 0,2) . '<br>'
        . 'Taxes: ₹' . number_format($order['taxes'] ?? 0,2) . '<br>'
        . '<strong>Grand Total: ₹' . number_format($order['grand_total'],2) . '</strong>'
        . '</div>';
    echo '</div>';
    echo '</div>';
    exit;
}

// Fetch orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function orderStatusLabel($status) {
    switch (strtolower($status)) {
        case 'processing': return '<span class="badge bg-warning text-dark">Processing</span>';
        case 'shipped':    return '<span class="badge bg-info text-dark">Shipped</span>';
        case 'delivered':  return '<span class="badge bg-success">Delivered</span>';
        default:           return '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - Fresh & Healthy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .orders-list { max-width: 900px; margin: 2rem auto; }
        .order-card {
            background: var(--card-background);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            padding: 2rem 2rem 1.5rem 2rem;
            border: 1px solid #e6e6e6;
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
            position: relative;
        }

        /* Responsive Design for My Orders */
        @media (max-width: 1024px) {
            .orders-list {
                max-width: 95%;
                margin: 1.5rem auto;
            }
            
            .order-card {
                padding: 1.5rem 1.5rem 1rem 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .orders-list {
                margin: 1rem auto;
                padding: 0 1rem;
            }
            
            .order-card {
                padding: 1rem 1rem 0.8rem 1rem;
                border-radius: 12px;
                margin-bottom: 1.5rem;
            }
            
            .order-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .order-id {
                font-size: 1.1rem;
            }
            
            .order-date {
                font-size: 0.9rem;
            }
            
            .order-total {
                font-size: 1.1rem;
            }
            
            .order-status {
                font-size: 0.9rem;
            }
            
            .order-actions {
                flex-direction: column;
                gap: 0.8rem;
                width: 100%;
            }
            
            .order-actions button {
                width: 100%;
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            
            .modal-dialog {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .order-detail-section {
                font-size: 0.9rem;
                margin-bottom: 0.8rem;
            }
            
            .order-detail-table {
                font-size: 0.85rem;
            }
            
            .order-detail-table th,
            .order-detail-table td {
                padding: 0.5rem 0.3rem;
            }
        }

        @media (max-width: 480px) {
            .orders-list {
                padding: 0 0.5rem;
            }
            
            .order-card {
                padding: 0.8rem 0.8rem 0.6rem 0.8rem;
                border-radius: 8px;
                margin-bottom: 1rem;
            }
            
            .order-id {
                font-size: 1rem;
            }
            
            .order-date {
                font-size: 0.85rem;
            }
            
            .order-total {
                font-size: 1rem;
            }
            
            .order-status {
                font-size: 0.85rem;
            }
            
            .order-actions button {
                padding: 0.7rem;
                font-size: 0.85rem;
            }
            
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            
            .modal-body {
                padding: 0.8rem;
            }
            
            .order-detail-section {
                font-size: 0.85rem;
                margin-bottom: 0.6rem;
            }
            
            .order-detail-table {
                font-size: 0.8rem;
            }
            
            .order-detail-table th,
            .order-detail-table td {
                padding: 0.4rem 0.2rem;
            }
        }
        .order-card:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
            transform: translateY(-4px) scale(1.01);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        .order-id {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1.15rem;
        }
        .order-date {
            color: var(--text-secondary);
            font-size: 0.98rem;
            margin-left: 1rem;
        }
        .order-status-badge {
            font-size: 1rem;
            font-weight: 600;
            padding: 0.35em 1.2em;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
        }
        .status-processing { background: #fff3cd; color: #856404; }
        .status-shipped { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-items-list {
            margin: 0.5rem 0 0 0; padding: 0; list-style: none;
            display: flex; flex-wrap: wrap; gap: 1.2rem;
        }
        .order-items-list li {
            font-size: 1rem; color: #444; background: #f8faf9; border-radius: 8px; padding: 0.5rem 1rem;
        }
        .order-total {
            font-weight: 600; color: var(--primary-color); margin-top: 1.2rem; font-size: 1.1rem;
        }
        .view-details-btn {
            margin-top: 1.2rem;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7em 1.6em;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition-base);
            box-shadow: var(--shadow-sm);
        }
        .view-details-btn:hover {
            background: var(--primary-dark);
            color: #fff;
            transform: translateY(-2px);
        }
        .empty-orders {
            text-align: center; margin: 5rem 0 3rem 0;
        }
        .empty-orders h2 {
            color: var(--primary-color); margin-bottom: 1rem;
        }
        .empty-orders .cta-button {
            margin-top: 1.5rem;
        }
        .empty-orders-illustration {
            font-size: 4.5rem;
            color: var(--primary-light);
            margin-bottom: 1.2rem;
            opacity: 0.8;
        }
        /* Modal redesign */
        .order-detail-section {
            margin-bottom: 1.2rem;
        }
        .order-detail-section strong {
            color: var(--primary-color);
        }
        .order-detail-icon {
            color: var(--primary-light);
            margin-right: 0.5em;
            font-size: 1.1em;
        }
        .order-detail-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-right: 0.5em;
        }
        .order-detail-table th {
            background: #e0f2f1; color: #00695c;
        }
        .order-detail-table td {
            background: #fff;
        }
        .order-detail-totals {
            font-size: 1.08rem;
            margin-top: 1.2rem;
        }
        .download-invoice-btn {
            margin-top: 1.5rem;
            background: var(--accent-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7em 1.6em;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition-base);
            box-shadow: var(--shadow-sm);
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
        }
        .download-invoice-btn:hover {
            background: var(--accent-dark);
            color: #fff;
            transform: translateY(-2px);
        }
        @media (max-width: 600px) {
            .order-card { padding: 1.2rem 0.7rem; }
            .order-header { flex-direction: column; gap: 0.5rem; align-items: flex-start; }
            .order-items-list { flex-direction: column; gap: 0.7rem; }
        }
        .themed-back-btn {
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
            padding: 0.7em 1.6em;
            font-size: 1rem;
            transition: var(--transition-base);
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
            text-decoration: none;
        }
        .themed-back-btn:hover, .themed-back-btn:focus {
            background: var(--primary-dark);
            color: #fff;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container orders-list">
        <a href="home_page.html" class="btn btn-outline-success mb-3" style="float:right; font-weight:600; border-radius:8px; box-shadow:var(--shadow-sm);"><i class="fas fa-home"></i> Home</a>
        <h1 class="mb-4">My Orders</h1>
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div class="empty-orders-illustration">🍃</div>
                <h2>No orders yet</h2>
                <p>You haven't placed any orders. Start shopping now!</p>
                <a href="home_page.html" class="btn btn-success cta-button">Go to Storefront</a>
            </div>
        <?php else: ?>
            <?php
            $totalOrders = count($orders);
            $serial = $totalOrders;
            foreach ($orders as $order):
                $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $itemStmt->execute([$order['id']]);
                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                $status = strtolower($order['status'] ?? 'processing');
                $statusMap = [
                    'processing' => ['Processing', 'fa-hourglass-half', 'status-processing'],
                    'shipped'    => ['Shipped', 'fa-truck', 'status-shipped'],
                    'delivered'  => ['Delivered', 'fa-check-circle', 'status-delivered'],
                    'cancelled'  => ['Cancelled', 'fa-times-circle', 'status-cancelled'],
                ];
                $statusLabel = $statusMap[$status][0] ?? ucfirst($status);
                $statusIcon = $statusMap[$status][1] ?? 'fa-box';
                $statusClass = $statusMap[$status][2] ?? 'status-processing';
                $invoicePath = "invoices/invoice_{$order['id']}.pdf";
                $hasInvoice = is_file($invoicePath);
                $displaySerial = $serial;
            ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-id"><strong>Order #<?= $displaySerial ?></strong></span>
                    <span class="order-date"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($order['created_at']) ?></span>
                    <div class="order-status-badge <?= $statusClass ?>">
                        <i class="fas <?= $statusIcon ?>"></i> <?= $statusLabel ?>
                    </div>
                </div>
                <ul class="order-items-list">
                    <?php foreach ($items as $it): ?>
                        <li><?= htmlspecialchars($it['product_name']) ?> × <?= $it['qty'] ?> — ₹<?= number_format($it['price'],2) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="order-total">Total: ₹<?= number_format($order['grand_total'],2) ?></div>
                <button class="view-details-btn" onclick="showOrderDetail(<?= $order['id'] ?>); event.stopPropagation();">
                    <i class="fas fa-eye"></i> View Details
                </button>
                <?php if ($status === 'processing'): ?>
                <button class="btn btn-danger cancel-order-btn" data-order-id="<?= $order['id'] ?>" style="margin-left:1rem;">
                    <i class="fas fa-times-circle"></i> Cancel
                </button>
                <?php endif; ?>
                <?php if ($hasInvoice): ?>
                    <a href="<?= $invoicePath ?>" class="download-invoice-btn" target="_blank"><i class="fas fa-file-invoice"></i> Invoice</a>
                <?php endif; ?>
            </div>
            <?php
            $serial--;
            endforeach;
            ?>
        <?php endif; ?>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Order Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="orderDetailBody">
            <!-- Populated by JS -->
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showOrderDetail(orderId) {
        fetch('my_orders.php?detail=' + orderId)
            .then(r => r.text())
            .then(html => {
                document.getElementById('orderDetailBody').innerHTML = html;
                var modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
                modal.show();
            });
    }
    // Cancel order AJAX
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.cancel-order-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          if (!confirm('Are you sure you want to cancel this order?')) return;
          var orderId = this.getAttribute('data-order-id');
          var button = this;
          button.disabled = true;
          fetch('cancel_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_id=' + encodeURIComponent(orderId)
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              button.closest('.order-card').querySelector('.order-status-badge').innerHTML = '<i class="fas fa-times-circle"></i> Cancelled';
              button.closest('.order-card').querySelector('.order-status-badge').className = 'order-status-badge status-cancelled';
              button.remove();
            } else {
              alert(data.error || 'Failed to cancel order.');
              button.disabled = false;
            }
          })
          .catch(() => {
            alert('Failed to cancel order.');
            button.disabled = false;
          });
        });
      });
    });
    </script>
</body>
</html> 