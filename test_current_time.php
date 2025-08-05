<?php
require 'db.php';

echo "<h2>Current Time Test</h2>";

// Set timezone
date_default_timezone_set('Asia/Kolkata');

echo "<h3>PHP Time</h3>";
echo "Current PHP time: " . date('Y-m-d H:i:s') . "<br>";
echo "Timezone: " . date_default_timezone_get() . "<br>";

echo "<h3>Database Time</h3>";
$stmt = $pdo->prepare("SELECT NOW() as db_time");
$stmt->execute();
$db_time = $stmt->fetch()['db_time'];
echo "Database time: $db_time<br>";

echo "<h3>Token Generation Test</h3>";
$created_at = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

echo "Created at: $created_at<br>";
echo "Expires at: $expires_at<br>";

// Calculate difference
$created_timestamp = strtotime($created_at);
$expires_timestamp = strtotime($expires_at);
$difference_hours = ($expires_timestamp - $created_timestamp) / 3600;

echo "Time difference: " . round($difference_hours, 2) . " hours<br>";

if ($difference_hours >= 1.0) {
    echo "<span style='color: green;'>✅ Expiration time is correct (1 hour after creation)</span><br>";
} else {
    echo "<span style='color: red;'>❌ Expiration time is incorrect</span><br>";
}

echo "<br><strong>Test completed!</strong>";
?> 