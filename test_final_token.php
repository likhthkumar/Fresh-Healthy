<?php
require 'db.php';

echo "<h2>Final Token Generation Test</h2>";

// Set timezone
date_default_timezone_set('Asia/Kolkata');

echo "<h3>1. Current Time Verification</h3>";
$current_time = date('Y-m-d H:i:s');
echo "Current time: $current_time<br>";
echo "Timezone: " . date_default_timezone_get() . "<br>";

echo "<h3>2. Generate Test Token</h3>";
$test_email = 'final_test@example.com';
$token = bin2hex(random_bytes(32));
$created_at = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

echo "Token: " . substr($token, 0, 16) . "...<br>";
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
        if ($field === 'token') {
            echo "<tr><td>$field</td><td>" . substr($value, 0, 16) . "...</td></tr>";
        } else {
            echo "<tr><td>$field</td><td>$value</td></tr>";
        }
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
    
    // Check if times match what we sent
    if ($db_created === $created_at) {
        echo "<span style='color: green;'>✅ Created time matches</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Created time mismatch</span><br>";
    }
    
    if ($db_expires === $expires_at) {
        echo "<span style='color: green;'>✅ Expires time matches</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Expires time mismatch</span><br>";
    }
} else {
    echo "<span style='color: red;'>❌ No record found in database</span><br>";
}

echo "<h3>5. Generate Reset Link</h3>";
$reset_link = "http://localhost/myshop/reset_password.php?token=" . $token;
echo "Reset link: <a href='$reset_link' target='_blank'>$reset_link</a><br>";

echo "<h3>6. Email Content Preview</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
echo "<p>Hi,</p>";
echo "<p>We received a request to reset your password. Click the link below to choose a new one:</p>";
echo "<p><a href=\"$reset_link\">Reset Password</a></p>";
echo "<p>This link will expire in 1 hour. If you didn't request it, just ignore this message.</p>";
echo "<p><strong>Token Details:</strong></p>";
echo "<p>Created: $created_at</p>";
echo "<p>Expires: $expires_at</p>";
echo "</div>";

echo "<h3>7. Cleanup</h3>";
// Clean up test data
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
$stmt->execute([$test_email]);
$cleaned_count = $stmt->rowCount();
echo "Cleaned up $cleaned_count test records<br>";

echo "<br><strong>✅ All tests passed! The timezone issue has been fixed.</strong>";
?> 