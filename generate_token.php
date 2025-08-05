<?php
date_default_timezone_set('Asia/Kolkata'); // Change as per your location

$token = bin2hex(random_bytes(32));  // 64-character secure token
$created_at = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

echo "<strong>Token:</strong> $token<br>";
echo "<strong>Created At:</strong> $created_at<br>";
echo "<strong>Expires At:</strong> $expires_at<br>";
?>
