<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the profile update attempt
    error_log("Profile update attempt for user ID: " . $user_id);
    
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($phone) < 10) {
        $error = "Please enter a valid phone number.";
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = "This email address is already registered by another user.";
            } else {
                // Start transaction
                $pdo->beginTransaction();
                
                // Update users table with all fields
                $stmt = $pdo->prepare("UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    address = ? 
                    WHERE id = ?");
                
                // Split name into first and last name
                $nameParts = explode(' ', $name, 2);
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                
                $stmt->execute([$firstName, $lastName, $email, $phone, $address, $user_id]);

                // Insert or update edited_profile table
                $stmt = $pdo->prepare("INSERT INTO edited_profile (user_id, name, email, phone, address, updated_at)
                                       VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                                       ON DUPLICATE KEY UPDATE 
                                       name = VALUES(name),
                                       email = VALUES(email),
                                       phone = VALUES(phone),
                                       address = VALUES(address),
                                       updated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$user_id, $name, $email, $phone, $address]);

                // Commit transaction
                $pdo->commit();
                
                $message = "Profile updated successfully!";
                error_log("Profile update successful for user ID: " . $user_id);
            }
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = "An error occurred while updating your profile. Please try again.";
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Fetch profile data
try {
    // First try to get from edited_profile table
    $stmt = $pdo->prepare("SELECT name, email, phone, address FROM edited_profile WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    if (!$profile) {
        // Fallback to users table for first-time users
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name, email, phone, address FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    }

    $name    = $profile['name'] ?? '';
    $email   = $profile['email'] ?? '';
    $phone   = $profile['phone'] ?? '';
    $address = $profile['address'] ?? '';
} catch (PDOException $e) {
    $error = "Error loading profile data. Please try again.";
    error_log("Profile load error: " . $e->getMessage());
    $name = $email = $phone = $address = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f4f7fa;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        /* Soft animated background shapes */
        .bg-shape {
            position: fixed;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.35;
            z-index: -1;
            animation: float 12s ease-in-out infinite alternate;
            pointer-events: none;
        }
        .bg-shape1 {
            width: 340px; height: 340px; background: #a1c4fd; left: -120px; top: -80px; animation-delay: 0s;
        }
        .bg-shape2 {
            width: 260px; height: 260px; background: #c2e9fb; right: -100px; top: 60px; animation-delay: 2s;
        }
        .bg-shape3 {
            width: 200px; height: 200px; background: #b2fefa; left: 40vw; bottom: -80px; animation-delay: 4s;
        }
        @keyframes float {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-30px) scale(1.08); }
        }
        .profile-card {
            max-width: 430px;
            margin: 3.5rem auto 2.5rem auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13);
            padding: 2.5rem 2rem 2rem 2rem;
            animation: fadein 0.5s;
            position: relative;
            z-index: 1;
        }
        @keyframes fadein {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: none; }
        }
        .profile-card h2 {
            font-size: 2rem;
            color: #2e7d32;
            font-weight: 700;
            margin-bottom: 0.3rem;
            text-align: center;
        }
        .profile-card .subtitle {
            color: #789262;
            font-size: 1.08rem;
            margin-bottom: 2.2rem;
            text-align: center;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.2rem;
            margin-bottom: 1.2rem;
        }
        .form-group label {
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 0.2rem;
            font-size: 1.04rem;
        }
        .input-row {
            display: flex;
            align-items: center;
            background: #fafbfc;
            border-radius: 8px;
            border: 1.5px solid #d1d5db;
            transition: border 0.18s, box-shadow 0.18s, background 0.18s;
        }
        .input-row:focus-within {
            border-color: #2e7d32;
            background: #fff;
            box-shadow: 0 2px 8px rgba(46,125,50,0.08);
        }
        .input-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 48px;
            font-size: 1.2em;
            color: #aaa;
        }
        .input-field {
            flex: 1 1 auto;
            height: 48px;
            padding-left: 0.2rem;
            border: none;
            background: transparent;
            font-size: 1.08rem;
            color: #222;
            outline: none;
            box-shadow: none;
        }
        textarea.input-field {
            min-height: 48px;
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 1.2rem;
            margin-top: 2.2rem;
            justify-content: center;
            position: relative;
            z-index: 5;
        }
        .btn-primary {
            background: linear-gradient(90deg, #43a047 0%, #388e3c 100%);
            color: #fff;
            border: none;
            border-radius: 24px;
            padding: 0.85rem 2.2rem;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.18s, box-shadow 0.18s;
            position: relative;
            z-index: 10;
            display: inline-block;
            text-align: center;
            line-height: 1.2;
            min-height: 48px;
            width: auto;
            min-width: 140px;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #388e3c 0%, #43a047 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.13);
        }
        .btn-secondary {
            background: #f5f5f5;
            color: #2e7d32;
            border: 1.5px solid #2e7d32;
            border-radius: 24px;
            padding: 0.85rem 2.2rem;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, color 0.18s, border 0.18s;
        }
        .btn-secondary:hover {
            background: #e6f4ea;
            color: #1b5e20;
            border-color: #1b5e20;
        }
        @media (max-width: 600px) {
            .profile-card {
                padding: 1.2rem 0.5rem;
            }
            .form-actions {
                flex-direction: column;
                gap: 0.7rem;
            }
        }
        .input-field:focus, .btn:focus {
            outline: 2px solid #2e7d32;
            outline-offset: 2px;
        }
        /* Error message styling */
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #ffcdd2;
            font-size: 0.95rem;
        }
        /* Modal for success message */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(44,62,80,0.18);
            align-items: center;
            justify-content: center;
            animation: fadeInModalBg 0.3s;
        }
        @keyframes fadeInModalBg {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.18);
            padding: 2.2rem 2rem 1.5rem 2rem;
            max-width: 350px;
            width: 90vw;
            text-align: center;
            position: relative;
            animation: fadeInModal 0.4s cubic-bezier(.4,0,.2,1);
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: scale(0.92); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-check {
            font-size: 2.7rem;
            color: #2e7d32;
            margin-bottom: 0.7rem;
            animation: popCheck 0.5s cubic-bezier(.4,0,.2,1);
        }
        @keyframes popCheck {
            0% { transform: scale(0.7); opacity: 0; }
            70% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); }
        }
        .modal-message {
            font-size: 1.13rem;
            color: #1b5e20;
            margin-bottom: 1.2rem;
        }
        .modal-btn {
            background: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s;
        }
        .modal-btn:hover {
            background: #1b5e20;
        }
    </style>
</head>
<body>
    <div class="bg-shape bg-shape1"></div>
    <div class="bg-shape bg-shape2"></div>
    <div class="bg-shape bg-shape3"></div>
    <div class="profile-card">
        <button type="button" onclick="window.history.back()" style="margin-bottom: 1.2rem; background: #e6f4ea; color: #2e7d32; border: 1.5px solid #2e7d32; border-radius: 24px; padding: 0.6rem 1.5rem; font-size: 1.02rem; font-weight: 600; cursor: pointer; transition: background 0.18s, color 0.18s, border 0.18s;">← Back</button>
        <h2>Edit Profile</h2>
        <div class="subtitle">Manage your account details</div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" autocomplete="off" aria-label="Edit Profile Form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-row">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" name="name" class="input-field" value="<?= htmlspecialchars($name) ?>" required placeholder="Enter your full name" aria-label="Full Name" id="name">
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-row">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($email) ?>" required placeholder="Enter your email address" aria-label="Email Address" id="email">
                </div>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-row">
                    <span class="input-icon"><i class="fas fa-phone"></i></span>
                    <input type="tel" name="phone" class="input-field" value="<?= htmlspecialchars($phone) ?>" required placeholder="Enter your phone number" aria-label="Phone Number" id="phone">
                </div>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <div class="input-row">
                    <span class="input-icon"><i class="fas fa-map-marker-alt"></i></span>
                    <textarea name="address" class="input-field" required placeholder="Enter your complete address" aria-label="Address" id="address"><?= htmlspecialchars($address) ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary" id="saveButton">
                    Save Changes
                </button>
                <button type="button" class="btn-secondary" onclick="history.back()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
    <!-- Modal for success message -->
    <div class="modal" id="successModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDesc">
        <div class="modal-content">
            <div class="modal-check"><i class="fas fa-check-circle"></i></div>
            <div class="modal-message" id="modalDesc"></div>
            <button class="modal-btn" id="modalOkBtn">OK</button>
        </div>
    </div>
    <script>
    // Floating label support for autofill
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.querySelectorAll('.input-field').forEach(function(input) {
                if (input.value) {
                    input.classList.add('autofilled');
                }
            });
        }, 300);
    });

    // Show modal if PHP set $message
<?php if ($message): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('successModal');
        var msg = document.getElementById('modalDesc');
        msg.textContent = <?= json_encode($message) ?>;
        modal.style.display = 'flex';
        document.getElementById('modalOkBtn').focus();
        document.getElementById('modalOkBtn').onclick = function() {
            modal.style.display = 'none';
            window.location.href = 'home_page.html';
        };
        // Trap focus inside modal
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                document.getElementById('modalOkBtn').focus();
            }
        });
    });
<?php endif; ?>

    // Form loaded successfully
    console.log('Edit Profile form loaded');
    </script>
</body>
</html>
