<?php
require 'db.php';

echo "<h2>Cleaning Up OTP Data</h2>";

// Set timezone
date_default_timezone_set('Asia/Kolkata');

echo "<h3>1. Current Status</h3>";
$current_time = date('Y-m-d H:i:s');
echo "Current time: $current_time<br>";

// Check if otp_logins table exists
$stmt = $pdo->prepare("SHOW TABLES LIKE 'otp_logins'");
$stmt->execute();
$table_exists = $stmt->fetch();

if ($table_exists) {
    // Count OTPs before cleanup
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM otp_logins");
    $stmt->execute();
    $total_otps = $stmt->fetch()['total'];
    echo "Total OTPs in database: $total_otps<br>";
    
    // Count active OTPs
    $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM otp_logins WHERE is_verified = 0 AND expires_at > NOW()");
    $stmt->execute();
    $active_otps = $stmt->fetch()['active'];
    echo "Active OTPs: $active_otps<br>";
    
    echo "<h3>2. Cleanup Operations</h3>";
    
    // Delete all OTP data
    $stmt = $pdo->prepare("DELETE FROM otp_logins");
    $stmt->execute();
    $deleted_count = $stmt->rowCount();
    echo "Deleted $deleted_count OTP records<br>";
    
    echo "<h3>3. Drop OTP Table</h3>";
    
    // Drop the otp_logins table
    $pdo->prepare("DROP TABLE IF EXISTS otp_logins")->execute();
    echo "Dropped otp_logins table<br>";
    
    echo "<h3>4. Verification</h3>";
    
    // Check if table still exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'otp_logins'");
    $stmt->execute();
    $table_still_exists = $stmt->fetch();
    
    if (!$table_still_exists) {
        echo "<span style='color: green;'>✅ OTP table successfully removed</span><br>";
    } else {
        echo "<span style='color: red;'>❌ OTP table still exists</span><br>";
    }
    
} else {
    echo "<p style='color: orange;'>OTP table does not exist. No cleanup needed.</p>";
}

echo "<h3>5. Remaining Tables</h3>";
$stmt = $pdo->prepare("SHOW TABLES");
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Current tables in database:<br>";
foreach ($tables as $table) {
    echo "- $table<br>";
}

echo "<br><strong>✅ OTP cleanup completed! Only email password reset functionality remains.</strong>";
?> 