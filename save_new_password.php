<?php
require 'db.php';

// Set timezone to match your local time
date_default_timezone_set('Asia/Kolkata');

$token = $_POST['token'] ?? '';
$pass  = $_POST['password'] ?? '';

/* 1. Verify token again */
$stmt = $pdo->prepare("
    SELECT email
    FROM password_resets
    WHERE token = ? AND is_used = 0 AND expires_at > NOW()
");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) {
    die('Token invalid or expired.');
}

/* 2. Hash & save new password */
$newHash = password_hash($pass, PASSWORD_DEFAULT);

$pdo->prepare("UPDATE users SET password = ? WHERE email = ?")
    ->execute([$newHash, $row['email']]);

/* 3. Mark the token as used instead of deleting */
$pdo->prepare("UPDATE password_resets SET is_used = 1 WHERE token = ?")
    ->execute([$token]);

echo '✅ Password updated! <a href="login.html">Log in</a>';
?>
