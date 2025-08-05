<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if (!$userId || !$orderId) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

// Check if the order belongs to the user and is processing
$stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order || strtolower($order['status']) !== 'processing') {
    echo json_encode(['success' => false, 'error' => 'Order cannot be cancelled.']);
    exit;
}

// Update status to cancelled
$stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
$stmt->execute([$orderId]);

echo json_encode(['success' => true]); 