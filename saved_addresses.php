<?php
session_start();
file_put_contents('address_debug.log', 'User ID: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . PHP_EOL, FILE_APPEND);
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saved Addresses - Fresh & Healthy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-light: #66bb6a;
            --primary-dark: #1b5e20;
            --accent-color: #ffa726;
            --accent-dark: #f57c00;
            --text-primary: #2c3e50;
            --text-secondary: #546e7a;
            --background: linear-gradient(135deg, #f8faf9 0%, #e8f5e9 100%);
            --card-background: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
            --transition-base: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 2rem;
            min-height: 100vh;
        }
        .addresses-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }
        h1 {
            color: var(--primary-color);
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 400;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition-base);
            box-shadow: var(--shadow-sm);
            border: none;
            cursor: pointer;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }
        .addresses-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .address-card {
            border: 2px solid #e8f5e9;
            border-radius: var(--border-radius);
            padding: 2rem;
            background: white;
            transition: var(--transition-base);
            position: relative;
            overflow: hidden;
        }
        .address-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), var(--primary-color));
            transform: scaleX(0);
            transition: var(--transition-base);
        }
        .address-card:hover::before {
            transform: scaleX(1);
        }
        .address-card:hover {
            border-color: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        .address-info {
            margin-bottom: 1.5rem;
        }
        .address-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .address-name::before {
            content: '📍';
            font-size: 1.2rem;
        }
        .address-details {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 1rem;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-light);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            background: linear-gradient(135deg, #f8faf9 0%, #e8f5e9 100%);
            border-radius: var(--border-radius);
            border: 2px dashed var(--primary-light);
        }
        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .empty-state-icon {
            font-size: 4rem;
            color: var(--primary-light);
            margin-bottom: 1rem;
            opacity: 0.7;
        }
        .add-address-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition-base);
            box-shadow: var(--shadow-sm);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .add-address-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .addresses-container { padding: 2rem 1.5rem; }
            h1 { font-size: 2.2rem; }
            .address-card { padding: 1.5rem; }
            .address-name { font-size: 1.2rem; }
            .address-details { font-size: 0.9rem; padding: 0.8rem; }
        }
        @media (max-width: 480px) {
            .addresses-container { padding: 1.5rem 1rem; }
            h1 { font-size: 1.8rem; }
            .page-subtitle { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="addresses-container">
        <button type="button" class="back-btn" onclick="history.back()">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <div class="page-header">
            <h1>Saved Addresses</h1>
            <p class="page-subtitle">Manage your delivery addresses</p>
        </div>
        <div class="addresses-list">
            <?php if (count($addresses) === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏠</div>
                    <h3>No saved addresses yet</h3>
                    <p>Add your first delivery address to get started with your shopping experience</p>
                </div>
            <?php else: ?>
                <?php foreach ($addresses as $addr): ?>
                    <div class="address-card">
                        <div class="address-info">
                            <div class="address-name"><?= htmlspecialchars($addr['full_name']) ?></div>
                            <div class="address-details">
                                <strong>Address:</strong> <?= htmlspecialchars($addr['street_name']) ?>, <?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['state']) ?> - <?= htmlspecialchars($addr['postal_code']) ?><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($addr['mobile_number']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button class="add-address-btn" onclick="window.location.href='address.html'">
            <i class="fas fa-plus-circle"></i> Add New Address
        </button>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html> 