<?php
session_start();
require 'db.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$count = $stmt->fetchColumn();
echo json_encode(['isFirstOrder' => $count == 0]); 