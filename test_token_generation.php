<?php
require 'db.php';

echo "<h2>Testing Password Reset Token Generation</h2>";

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Test email
$test_email = 'test@example.com';

echo "<h3>1. Current Time</h3>";
$current_time = date('Y-m-d H:i:s');
echo "Current time: $current_time<br>";

echo "<h3>2. Generate Test Token</h3>";
$token = bin2hex(random_bytes(32));
$created_at = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

echo "Token: $token<br>";
echo "Created at: $created_at<br>";
echo "Expires at: $expires_at<br>";

// Verify expiration is 1 hour after creation
$created_timestamp = strtotime($created_at);
$expires_timestamp = strtotime($expires_at);
$difference_hours = ($expires_timestamp - $created_timestamp) / 3600;

echo "Time difference: " . round($difference_hours, 2) . " hours<br>";

if ($difference_hours >= 1.0) {
    echo "<span style='color: green;'>✅ Expiration time is correct (1 hour after creation)</span><br>";
} else {
    echo "<span style='color: red;'>❌ Expiration time is incorrect</span><br>";
}

echo "<h3>3. Database Operations</h3>";

// Clean up old expired tokens
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND expires_at < NOW()");
$stmt->execute([$test_email]);
$deleted_count = $stmt->rowCount();
echo "Deleted $deleted_count expired tokens<br>";

// Mark existing unused tokens as used
$stmt = $pdo->prepare("UPDATE password_resets SET is_used = 1 WHERE email = ? AND is_used = 0");
$stmt->execute([$test_email]);
$updated_count = $stmt->rowCount();
echo "Marked $updated_count existing tokens as used<br>";

// Insert new token
$stmt = $pdo->prepare("
    INSERT INTO password_resets (email, token, created_at, expires_at, is_used)
    VALUES (?, ?, ?, ?, 0)
");
$stmt->execute([$test_email, $token, $created_at, $expires_at]);
echo "Inserted new token into database<br>";

echo "<h3>4. Verify Database Entry</h3>";

// Check the inserted record
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$test_email]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($record) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($record as $field => $value) {
        echo "<tr><td>$field</td><td>$value</td></tr>";
    }
    echo "</table>";
    
    // Verify the times
    $db_created = $record['created_at'];
    $db_expires = $record['expires_at'];
    $db_created_timestamp = strtotime($db_created);
    $db_expires_timestamp = strtotime($db_expires);
    $db_difference_hours = ($db_expires_timestamp - $db_created_timestamp) / 3600;
    
    echo "<br>Database verification:<br>";
    echo "Created: $db_created<br>";
    echo "Expires: $db_expires<br>";
    echo "Difference: " . round($db_difference_hours, 2) . " hours<br>";
    
    if ($db_difference_hours >= 1.0) {
        echo "<span style='color: green;'>✅ Database expiration time is correct</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Database expiration time is incorrect</span><br>";
    }
} else {
    echo "<span style='color: red;'>❌ No record found in database</span><br>";
}

echo "<h3>5. Check for Expired Tokens</h3>";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM password_resets WHERE expires_at < NOW()");
$stmt->execute();
$expired_count = $stmt->fetch()['count'];
echo "Total expired tokens in database: $expired_count<br>";

echo "<h3>6. Generate Reset Link</h3>";
$reset_link = "http://localhost/myshop/reset_password.php?token=" . $token;
echo "Reset link: <a href='$reset_link' target='_blank'>$reset_link</a><br>";

echo "<h3>7. Cleanup</h3>";
// Clean up test data
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
$stmt->execute([$test_email]);
$cleaned_count = $stmt->rowCount();
echo "Cleaned up $cleaned_count test records<br>";

echo "<br><strong>Test completed!</strong>";
?> 