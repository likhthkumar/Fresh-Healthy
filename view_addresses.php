<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die('Please log in to view your addresses.');
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM user_addresses
        WHERE user_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching addresses: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Saved Addresses</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 2rem;
            background: #f4f4f4;
        }
        .address {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .address h3 {
            margin-bottom: 0.5rem;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <h1>Your Saved Addresses</h1>

    <?php if (count($addresses) === 0): ?>
        <p>No saved addresses found.</p>
    <?php else: ?>
        <?php foreach ($addresses as $addr): ?>
            <div class="address">
                <h3><?= htmlspecialchars($addr['full_name']) ?></h3>
                <p>Mobile: <?= htmlspecialchars($addr['mobile_number']) ?></p>
                <p><?= htmlspecialchars($addr['street_name']) ?>, <?= htmlspecialchars($addr['city']) ?></p>
                <p><?= htmlspecialchars($addr['state']) ?> – <?= htmlspecialchars($addr['postal_code']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
