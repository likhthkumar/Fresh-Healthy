<?php
session_start();
require 'db.php';

// Remove the redirect for not-logged-in users
// We'll handle the popup for skipped users via JS
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    /* — 1. Fetch orders — */
    $stmt = $pdo->prepare("
        SELECT id, grand_total, payment_ref, created_at
        FROM orders
        WHERE user_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    /* Pre‑load items for all orders in one query */
    $orderIds = array_column($orders, 'id');
    $itemsMap = [];
    if ($orderIds) {
        $in = str_repeat('?,', count($orderIds) - 1) . '?';
        $itemStmt = $pdo->prepare("
            SELECT * FROM order_items
            WHERE order_id IN ($in)
            ORDER BY order_id DESC
        ");
        $itemStmt->execute($orderIds);
        foreach ($itemStmt as $it) {
            $itemsMap[$it['order_id']][] = $it;
        }
    }
} else {
    $orders = [];
    $itemsMap = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders</title>
<style>
    body {
        font-family: 'Segoe UI', system-ui, sans-serif;
        background: #f4f8fb;
        padding: 2rem;
        color: #2c3e50;
    }
    h2 {
        margin-bottom: 2rem;
        font-size: 2.2rem;
        color: #2e7d32;
        font-weight: 700;
        letter-spacing: 1px;
        text-align: center;
    }
    .order-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(44,62,80,0.08);
        margin-bottom: 2rem;
        padding: 0;
        overflow: hidden;
        transition: box-shadow 0.2s;
        border: none;
    }
    .order-card:hover {
        box-shadow: 0 8px 32px rgba(44,62,80,0.16);
    }
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #e8f5e9;
        padding: 1.2rem 1.5rem;
        cursor: pointer;
        font-size: 1.1rem;
        font-weight: 500;
        border-bottom: 1px solid #e0e0e0;
    }
    .order-header strong {
        font-size: 1.15rem;
        color: #1b5e20;
    }
    .order-header div:last-child {
        font-size: 1.2rem;
        color: #388e3c;
        font-weight: 700;
    }
    .items {
        display: none;
        padding: 1.2rem 1.5rem 1.5rem 1.5rem;
        background: #fafbfc;
    }
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0.5rem;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(44,62,80,0.04);
    }
    th, td {
        padding: 10px 8px;
        text-align: left;
    }
    th {
        background: #e0f2f1;
        color: #00695c;
        font-weight: 600;
        font-size: 1rem;
    }
    tr:nth-child(even) td {
        background: #f7fafc;
    }
    .inv-link {
        margin-top: 12px;
        display: inline-block;
        background: #2e7d32;
        color: #fff;
        padding: 8px 18px;
        border-radius: 22px;
        text-decoration: none;
        font-weight: 500;
        font-size: 1rem;
        box-shadow: 0 2px 8px rgba(44,62,80,0.08);
        transition: background 0.2s;
    }
    .inv-link:hover {
        background: #1b5e20;
    }
    /* Modal styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(44,62,80,0.18);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 8px 32px rgba(44,62,80,0.18);
        padding: 2.2rem 2rem 1.5rem 2rem;
        max-width: 350px;
        width: 90vw;
        text-align: center;
        border: 2px solid #2e7d32;
        animation: popin 0.2s;
    }
    @keyframes popin {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .modal h3 {
        margin: 0 0 1.2rem 0;
        font-size: 1.25rem;
        color: #2e7d32;
        font-weight: 700;
    }
    .modal .modal-btns {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }
    .modal button, .modal a {
        border: none;
        border-radius: 22px;
        padding: 0.7rem 0;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s;
        box-shadow: 0 2px 8px rgba(44,62,80,0.08);
        text-align: center;
    }
    .modal .btn-primary {
        background: #2e7d32;
        color: #fff;
    }
    .modal .btn-primary:hover {
        background: #1b5e20;
    }
    .modal .btn-secondary {
        background: #e0e0e0;
        color: #2e7d32;
        border: 1px solid #2e7d32;
    }
    .modal .btn-secondary:hover {
        background: #c8e6c9;
    }
    .modal .btn-cancel {
        background: #f5f5f5;
        color: #666;
        border: 1px solid #ddd;
    }
    .modal .btn-cancel:hover {
        background: #e0e0e0;
    }
    @media (max-width: 600px) {
        .order-header, .items { padding: 1rem; }
        th, td { padding: 7px 4px; font-size: 0.95rem; }
        h2 { font-size: 1.4rem; }
        .modal { padding: 1.2rem 0.5rem 1rem 0.5rem; }
    }
</style>
<script>
function toggleItems(id){
    const el = document.getElementById('items-'+id);
    el.style.display = (el.style.display==='none' || el.style.display==='') ? 'block' : 'none';
}

// Modal logic for skipped login
window.addEventListener('DOMContentLoaded', function() {
    var userLoggedIn = <?php echo $userId ? 'true' : 'false'; ?>;
    var ordersSection = document.getElementById('orders-section');
    var modalOverlay = document.getElementById('login-modal-overlay');
    if (!userLoggedIn) {
        // Check if user skipped login
        if (localStorage.getItem('skippedLogin') === 'true') {
            if (ordersSection) ordersSection.style.display = 'none';
            if (modalOverlay) modalOverlay.style.display = 'flex';
        } else {
            // If not skipped, redirect to login
            window.location.href = 'login.html';
        }
    }
    // Modal close buttons
    var closeBtns = document.querySelectorAll('.modal-close');
    closeBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (modalOverlay) modalOverlay.style.display = 'none';
            if (ordersSection) ordersSection.style.display = 'block';
        });
    });
});
</script>
</head>
<body>

<!-- Modal for skipped login -->
<div class="modal-overlay" id="login-modal-overlay">
  <div class="modal">
    <h3>Please log in or register to view your orders.</h3>
    <div class="modal-btns">
      <a href="login.html" class="btn-primary">Login</a>
      <a href="register.html" class="btn-secondary">Register</a>
      <button class="modal-close btn-cancel">Cancel</button>
    </div>
  </div>
</div>

<div id="orders-section">
<h2>My Orders</h2>

<?php if ($userId && !$orders): ?>
    <p>You have no orders yet.</p>
<?php elseif ($userId): ?>
    <?php foreach ($orders as $o): ?>
        <div class="order-card">
            <div class="order-header" onclick="toggleItems(<?= $o['id']?>)">
                <div>
                    <strong>Order #<?= $o['id']?></strong><br>
                    <?= date('d M Y H:i', strtotime($o['created_at'])) ?>
                </div>
                <div>
                    ₹<?= number_format($o['grand_total'],2) ?>
                </div>
            </div>
            <div id="items-<?= $o['id']?>" class="items">
                <table>
                    <tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
                    <?php foreach ($itemsMap[$o['id']] ?? [] as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['product_name']) ?></td>
                            <td><?= number_format($it['price'],2) ?></td>
                            <td><?= $it['qty'] ?></td>
                            <td><?= number_format($it['subtotal'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php
                  $inv = "invoices/invoice_{$o['id']}.pdf";
                  if (is_file($inv)):
                ?>
                    <a class="inv-link" href="<?= $inv ?>" target="_blank">📄 Download Invoice</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

</body>
</html>
