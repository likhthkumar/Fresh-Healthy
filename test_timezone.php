<?php
require 'db.php';

echo "<h2>Timezone and Time Testing</h2>";

echo "<h3>1. PHP Timezone Settings</h3>";
echo "Default timezone: " . date_default_timezone_get() . "<br>";
echo "Current PHP time: " . date('Y-m-d H:i:s') . "<br>";

// Set timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');
echo "After setting Asia/Kolkata timezone: " . date('Y-m-d H:i:s') . "<br>";

echo "<h3>2. Database Time</h3>";
$stmt = $pdo->prepare("SELECT NOW() as db_time, @@global.time_zone as global_tz, @@session.time_zone as session_tz");
$stmt->execute();
$db_info = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Database current time: " . $db_info['db_time'] . "<br>";
echo "Database global timezone: " . $db_info['global_tz'] . "<br>";
echo "Database session timezone: " . $db_info['session_tz'] . "<br>";

echo "<h3>3. Token Generation Test</h3>";
$created_at = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

echo "Created at (PHP): $created_at<br>";
echo "Expires at (PHP): $expires_at<br>";

// Calculate difference
$created_timestamp = strtotime($created_at);
$expires_timestamp = strtotime($expires_at);
$difference_hours = ($expires_timestamp - $created_timestamp) / 3600;

echo "Time difference: " . round($difference_hours, 2) . " hours<br>";

echo "<h3>4. Database Insert Test</h3>";
$test_email = 'timezone_test@example.com';
$token = bin2hex(random_bytes(16));

// Clean up any existing test data
$pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$test_email]);

// Insert test token
$stmt = $pdo->prepare("
    INSERT INTO password_resets (email, token, created_at, expires_at, is_used)
    VALUES (?, ?, ?, ?, 0)
");
$stmt->execute([$test_email, $token, $created_at, $expires_at]);

// Retrieve and check
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$test_email]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($record) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>created_at (stored)</td><td>" . $record['created_at'] . "</td></tr>";
    echo "<tr><td>expires_at (stored)</td><td>" . $record['expires_at'] . "</td></tr>";
    echo "</table>";
    
    // Check if the stored times match what we sent
    if ($record['created_at'] === $created_at) {
        echo "<span style='color: green;'>✅ Created time matches</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Created time mismatch</span><br>";
    }
    
    if ($record['expires_at'] === $expires_at) {
        echo "<span style='color: green;'>✅ Expires time matches</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Expires time mismatch</span><br>";
    }
}

echo "<h3>5. Current Time Comparison</h3>";
$current_php_time = date('Y-m-d H:i:s');
$current_db_time = $db_info['db_time'];

echo "Current PHP time: $current_php_time<br>";
echo "Current DB time: $current_db_time<br>";

if ($current_php_time === $current_db_time) {
    echo "<span style='color: green;'>✅ PHP and DB times match</span><br>";
} else {
    echo "<span style='color: red;'>❌ PHP and DB times don't match</span><br>";
    echo "This indicates a timezone issue!<br>";
}

echo "<h3>6. Cleanup</h3>";
$pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$test_email]);
echo "Cleaned up test data<br>";

echo "<br><strong>Test completed!</strong>";
?> 