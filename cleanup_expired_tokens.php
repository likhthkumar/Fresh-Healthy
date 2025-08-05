<?php
require 'db.php';

echo "<h2>Cleaning Up Expired Password Reset Tokens</h2>";

// Set timezone
date_default_timezone_set('Asia/Kolkata');

echo "<h3>1. Current Status</h3>";
$current_time = date('Y-m-d H:i:s');
echo "Current time: $current_time<br>";

// Count total tokens
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM password_resets");
$stmt->execute();
$total_tokens = $stmt->fetch()['total'];
echo "Total tokens in database: $total_tokens<br>";

// Count expired tokens
$stmt = $pdo->prepare("SELECT COUNT(*) as expired FROM password_resets WHERE expires_at < NOW()");
$stmt->execute();
$expired_tokens = $stmt->fetch()['expired'];
echo "Expired tokens: $expired_tokens<br>";

// Count used tokens
$stmt = $pdo->prepare("SELECT COUNT(*) as used FROM password_resets WHERE is_used = 1");
$stmt->execute();
$used_tokens = $stmt->fetch()['used'];
echo "Used tokens: $used_tokens<br>";

// Count active tokens
$stmt = $pdo->prepare("SELECT COUNT(*) as active FROM password_resets WHERE is_used = 0 AND expires_at > NOW()");
$stmt->execute();
$active_tokens = $stmt->fetch()['active'];
echo "Active tokens: $active_tokens<br>";

echo "<h3>2. Cleanup Operations</h3>";

// Delete expired tokens
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
$stmt->execute();
$deleted_expired = $stmt->rowCount();
echo "Deleted $deleted_expired expired tokens<br>";

// Optionally delete used tokens older than 24 hours
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE is_used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$deleted_old_used = $stmt->rowCount();
echo "Deleted $deleted_old_used old used tokens (older than 24 hours)<br>";

echo "<h3>3. Post-Cleanup Status</h3>";

// Count remaining tokens
$stmt = $pdo->prepare("SELECT COUNT(*) as remaining FROM password_resets");
$stmt->execute();
$remaining_tokens = $stmt->fetch()['remaining'];
echo "Remaining tokens: $remaining_tokens<br>";

// Show remaining active tokens
$stmt = $pdo->prepare("SELECT email, created_at, expires_at FROM password_resets WHERE is_used = 0 AND expires_at > NOW() ORDER BY created_at DESC");
$stmt->execute();
$active_tokens_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($active_tokens_list) {
    echo "<h4>Active Tokens:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Email</th><th>Created</th><th>Expires</th></tr>";
    foreach ($active_tokens_list as $token) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($token['email']) . "</td>";
        echo "<td>" . $token['created_at'] . "</td>";
        echo "<td>" . $token['expires_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No active tokens found.</p>";
}

echo "<br><strong>Cleanup completed!</strong>";
?> 