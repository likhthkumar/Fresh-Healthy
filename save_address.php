<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die('You must be logged in to save an address.');
}

// Get and sanitize form data
$fullName     = trim($_POST['fullName'] ?? '');
$mobileNumber = trim($_POST['mobileNumber'] ?? '');
$state        = trim($_POST['state'] ?? '');
$postalCode   = trim($_POST['postalCode'] ?? '');
$city         = trim($_POST['city'] ?? '');
$streetName   = trim($_POST['streetName'] ?? '');
$user_id      = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        INSERT INTO user_addresses
        (user_id, full_name, mobile_number, state, postal_code, city, street_name)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, $fullName, $mobileNumber, $state,
        $postalCode, $city, $streetName
    ]);

    header('Location: cart.html');
    exit;
} catch (PDOException $e) {
    echo 'Error saving address: ' . $e->getMessage();
}
?>
