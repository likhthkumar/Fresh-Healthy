<?php
session_start();
require 'db.php';

// For demo purposes, let's create a test user session if none exists
if (!isset($_SESSION['user_id'])) {
    // Check if we have any users in the database
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        echo "<p>Demo: Created session for user ID: " . $user['id'] . "</p>";
    } else {
        echo "<p>No users found in database. Please register a user first.</p>";
        exit;
    }
}

$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $pdo->prepare("SELECT first_name, last_name, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch();

// Get edited profile data if exists
$stmt = $pdo->prepare("SELECT name, email, phone, address, updated_at FROM edited_profile WHERE user_id = ?");
$stmt->execute([$user_id]);
$editedProfile = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Demo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .demo-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .current-data { background: #f0f8ff; }
        .edited-data { background: #f0fff0; }
        .button { background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>Profile Functionality Demo</h1>
    
    <div class="demo-section current-data">
        <h2>Current User Data (from users table)</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($userData['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($userData['phone'] ?? 'Not set') ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($userData['address'] ?? 'Not set') ?></p>
    </div>

    <?php if ($editedProfile): ?>
    <div class="demo-section edited-data">
        <h2>Edited Profile Data (from edited_profile table)</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($editedProfile['name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($editedProfile['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($editedProfile['phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($editedProfile['address']) ?></p>
        <p><strong>Last Updated:</strong> <?= htmlspecialchars($editedProfile['updated_at']) ?></p>
    </div>
    <?php else: ?>
    <div class="demo-section">
        <h2>No Edited Profile Data</h2>
        <p>This user hasn't edited their profile yet. The system will use data from the users table.</p>
    </div>
    <?php endif; ?>

    <div class="demo-section">
        <h2>Test the Profile Functionality</h2>
        <p>Click the button below to test the edit profile functionality:</p>
        <a href="edited_profile.php" class="button">Edit Profile</a>
        <a href="home_page.html" class="button">Back to Home</a>
    </div>

    <div class="demo-section">
        <h2>How It Works</h2>
        <ol>
            <li><strong>Data Loading:</strong> The system first tries to load data from the <code>edited_profile</code> table</li>
            <li><strong>Fallback:</strong> If no edited profile exists, it falls back to the <code>users</code> table</li>
            <li><strong>Form Submission:</strong> When the form is submitted, it updates both tables</li>
            <li><strong>Validation:</strong> The system validates email format, phone length, and checks for duplicate emails</li>
            <li><strong>Success Message:</strong> A modal popup confirms successful updates</li>
        </ol>
    </div>

    <div class="demo-section">
        <h2>Features Implemented</h2>
        <ul>
            <li>✅ Edit name, email, phone, and address</li>
            <li>✅ Form validation (client-side and server-side)</li>
            <li>✅ Email uniqueness check</li>
            <li>✅ Database transaction handling</li>
            <li>✅ Error handling and user feedback</li>
            <li>✅ Success confirmation popup</li>
            <li>✅ Responsive design</li>
            <li>✅ Data persistence in both tables</li>
        </ul>
    </div>
</body>
</html> 