<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Please log in');
}
$addressId = $_POST['address_id'] ?? null;
if (!$addressId) {
    die('No address selected.');
}

/* Save the chosen address ID in the session for later use */
$_SESSION['selected_address_id'] = $addressId;

/* Redirect straight to payment */
header('Location: payment.php');
exit;
?>
