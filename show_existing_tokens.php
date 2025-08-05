<?php
require 'db.php';

echo "<h2>Existing Password Reset Tokens</h2>";

// Set timezone
date_default_timezone_set('Asia/Kolkata');

$current_time = date('Y-m-d H:i:s');
echo "<p><strong>Current time:</strong> $current_time</p>";

// Get all tokens
$stmt = $pdo->prepare("
    SELECT email, token, created_at, expires_at, is_used 
    FROM password_resets 
    ORDER BY created_at DESC
");
$stmt->execute();
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($tokens) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Email</th>";
    echo "<th>Token (first 16 chars)</th>";
    echo "<th>Created</th>";
    echo "<th>Expires</th>";
    echo "<th>Used</th>";
    echo "<th>Status</th>";
    echo "<th>Reset Link</th>";
    echo "</tr>";
    
    foreach ($tokens as $token) {
        $is_expired = strtotime($token['expires_at']) < time();
        $status = '';
        $status_color = '';
        
        if ($token['is_used'] == 1) {
            $status = 'Used';
            $status_color = 'red';
        } elseif ($is_expired) {
            $status = 'Expired';
            $status_color = 'orange';
        } else {
            $status = 'Active';
            $status_color = 'green';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($token['email']) . "</td>";
        echo "<td>" . substr($token['token'], 0, 16) . "...</td>";
        echo "<td>" . $token['created_at'] . "</td>";
        echo "<td>" . $token['expires_at'] . "</td>";
        echo "<td>" . ($token['is_used'] ? 'Yes' : 'No') . "</td>";
        echo "<td style='color: $status_color;'>$status</td>";
        
        if ($status === 'Active') {
            $reset_link = "http://localhost/myshop/reset_password.php?token=" . $token['token'];
            echo "<td><a href='$reset_link' target='_blank'>Test Link</a></td>";
        } else {
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Active Tokens (Ready to Use):</h3>";
    $active_tokens = array_filter($tokens, function($t) {
        return $t['is_used'] == 0 && strtotime($t['expires_at']) > time();
    });
    
    if ($active_tokens) {
        foreach ($active_tokens as $token) {
            $reset_link = "http://localhost/myshop/reset_password.php?token=" . $token['token'];
            echo "<p><strong>Email:</strong> " . htmlspecialchars($token['email']) . "</p>";
            echo "<p><strong>Token:</strong> " . $token['token'] . "</p>";
            echo "<p><strong>Reset Link:</strong> <a href='$reset_link' target='_blank'>$reset_link</a></p>";
            echo "<hr>";
        }
    } else {
        echo "<p style='color: orange;'>No active tokens found. Run generate_test_link.php to create a new one.</p>";
    }
    
} else {
    echo "<p style='color: orange;'>No tokens found in database.</p>";
    echo "<p>Run <a href='generate_test_link.php'>generate_test_link.php</a> to create a test token.</p>";
}
?> 