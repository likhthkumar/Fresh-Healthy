<?php
require 'db.php';

echo "<h2>Generate Test Reset Link</h2>";

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Generate a test token
$test_email = 'test@example.com';
$token = bin2hex(random_bytes(32));
$created_at = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

echo "<h3>Token Details:</h3>";
echo "Email: $test_email<br>";
echo "Token: <strong>$token</strong><br>";
echo "Created: $created_at<br>";
echo "Expires: $expires_at<br>";

// Clean up any existing test data
$pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$test_email]);

// Insert the token into database
$stmt = $pdo->prepare("
    INSERT INTO password_resets (email, token, created_at, expires_at, is_used)
    VALUES (?, ?, ?, ?, 0)
");
$stmt->execute([$test_email, $token, $created_at, $expires_at]);

echo "<h3>Complete Reset Link:</h3>";
$reset_link = "http://localhost/myshop/reset_password.php?token=" . $token;
echo "<a href='$reset_link' target='_blank' style='font-size: 16px; color: blue; text-decoration: underline;'>$reset_link</a><br><br>";

echo "<h3>Copy this token:</h3>";
echo "<textarea style='width: 100%; height: 60px; font-family: monospace; font-size: 14px;'>$token</textarea><br><br>";

echo "<h3>Or manually construct the link:</h3>";
echo "http://localhost/myshop/reset_password.php?token=<strong>$token</strong><br><br>";

echo "<p style='color: green;'><strong>✅ Token generated and saved to database. Click the link above to test the password reset.</strong></p>";

echo "<p><em>Note: This token will expire in 1 hour. You can run this script again to generate a new token.</em></p>";
?> 