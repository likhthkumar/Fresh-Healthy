<?php
require 'db.php';

// Set timezone to match your local time
date_default_timezone_set('Asia/Kolkata');

// Email password reset flow
require 'vendor/autoload.php'; // PHPMailer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = trim($_POST['email'] ?? '');
$success = false;
$message = '';

if ($email === '') {
    $message = 'Please enter your email address.';
} else {
// 1. Find user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
        // 2. Clean up old expired tokens for this email
        $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND expires_at < NOW()")->execute([$email]);
        
        // 3. Mark any existing unused tokens as used
        $pdo->prepare("UPDATE password_resets SET is_used = 1 WHERE email = ? AND is_used = 0")->execute([$email]);
        
        // 4. Generate new token with proper expiration logic
    $token = bin2hex(random_bytes(32));
        $created_at = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 5. Save new token
    $pdo->prepare("
            INSERT INTO password_resets (email, token, created_at, expires_at, is_used)
            VALUES (?, ?, ?, ?, 0)
        ")->execute([$email, $token, $created_at, $expires_at]);

        // 6. Generate proper reset link with actual token
        $resetLink = "http://localhost/myshop/reset_password.php?token=" . $token;

        // 7. Send mail
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'freshhealthy999@gmail.com';
        $mail->Password   = 'lzpj llaf awji pbje';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email content
        $mail->setFrom('freshhealthy999@gmail.com', 'Fresh & Healthy');
        $mail->addAddress($email);
        $mail->Subject = 'Reset Your Password';
        $mail->isHTML(true);
        $mail->Body = "
            <p>Hi,</p>
            <p>We received a request to reset your password. Click the link below to choose a new one:</p>
            <p><a href=\"$resetLink\">Reset Password</a></p>
            <p>This link will expire in 1 hour. If you didn't request it, just ignore this message.</p>
                <p><strong>Token Details:</strong></p>
                <p>Created: $created_at</p>
                <p>Expires: $expires_at</p>
        ";

        $mail->send();
            
            // 8. Log the token generation for debugging
            error_log("Password reset token generated for $email - Created: $created_at, Expires: $expires_at, Token: $token");
            
            $success = true;
            $message = 'Password reset link has been sent to your email address.';
            
    } catch (Exception $e) {
            $message = 'Failed to send email. Please try again later.';
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
    } else {
        $message = 'If this email is registered, a reset link has been sent.';
        $success = true; // Don't reveal if email exists or not for security
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Fresh & Healthy</title>
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
            --background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            --card-background: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
            --transition-base: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: var(--background);
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* Soft animated background shapes */
        .bg-shape {
            position: fixed;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.35;
            z-index: 0;
            animation: float 12s ease-in-out infinite alternate;
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

        .reset-card {
            background: rgba(255,255,255,0.85);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.13), 0 1.5px 8px rgba(46,125,50,0.07);
            padding: 2.7rem 2.2rem 2.2rem 2.2rem;
            max-width: 500px;
            width: 100%;
            margin: 2rem auto;
            animation: fadeIn 0.7s cubic-bezier(.4,0,.2,1);
            position: relative;
            z-index: 2;
            backdrop-filter: blur(8px) saturate(1.2);
            text-align: center;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reset-icon {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: linear-gradient(135deg, <?php echo $success ? '#4caf50' : '#ff9800'; ?> 0%, <?php echo $success ? '#66bb6a' : '#ffb74d'; ?> 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.1rem auto;
            box-shadow: 0 2px 12px rgba(<?php echo $success ? '76,175,80' : '255,152,0'; ?>,0.18);
            font-size: 2.7rem;
            color: #fff;
            position: relative;
            animation: bounceIn 0.8s cubic-bezier(.4,0,.2,1);
        }
        @keyframes bounceIn {
            0% { transform: scale(0.7); opacity: 0; }
            60% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); }
        }

        .reset-card h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 0.3rem;
            text-align: center;
        }

        .reset-card .subtitle {
            text-align: center;
            color: #5e6c7b;
            font-size: 1.08rem;
            margin-bottom: 1.5rem;
            letter-spacing: 0.01em;
        }

        .message-box {
            background: <?php echo $success ? '#e8f5e8' : '#fff3e0'; ?>;
            border: 1px solid <?php echo $success ? '#4caf50' : '#ff9800'; ?>;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: <?php echo $success ? '#2e7d32' : '#f57c00'; ?>;
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .info-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .info-section h4 {
            color: #495057;
            margin: 0 0 0.8rem 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-section ul {
            margin: 0;
            padding-left: 1.5rem;
            color: #6c757d;
        }

        .info-section li {
            margin-bottom: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 1.7rem;
            font-size: 1.08rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            text-decoration: none;
            margin: 0.5rem;
        }

        .btn-primary {
            background: #2e7d32;
            color: #fff;
            box-shadow: 0 2px 8px rgba(46,125,50,0.08);
        }

        .btn-primary:hover {
            background: #1b5e20;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46,125,50,0.15);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            color: #495057;
        }

        .email-display {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 0.8rem 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.1rem;
            color: #1976d2;
            word-break: break-all;
        }

        @media (max-width: 600px) {
            .reset-card {
                padding: 1.5rem 1rem;
                max-width: 98vw;
                margin: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
                margin: 0.25rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shape bg-shape1"></div>
    <div class="bg-shape bg-shape2"></div>
    <div class="bg-shape bg-shape3"></div>
    
    <div class="reset-card">
        <div class="reset-icon">
            <i class="fas <?php echo $success ? 'fa-check' : 'fa-exclamation-triangle'; ?>"></i>
        </div>
        
        <h2><?php echo $success ? 'Check Your Email' : 'Reset Password'; ?></h2>
        <div class="subtitle">
            <?php echo $success ? 'We\'ve sent you a password reset link' : 'Something went wrong'; ?>
        </div>
        
        <div class="message-box">
            <i class="fas <?php echo $success ? 'fa-envelope-open' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>

        <?php if ($success && $email): ?>
        <div class="email-display">
            <i class="fas fa-envelope"></i>
            <?php echo htmlspecialchars($email); ?>
        </div>
        <?php endif; ?>

        <div class="info-section">
            <h4>
                <i class="fas fa-info-circle"></i>
                What happens next?
            </h4>
            <ul>
                <li>Check your email inbox (and spam folder)</li>
                <li>Click the "Reset Password" link in the email</li>
                <li>Create a new password for your account</li>
                <li>The link will expire in 1 hour for security</li>
            </ul>
        </div>

        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem;">
            <a href="login.html" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Login
            </a>
            
            <a href="forgot_password.html" class="btn btn-primary">
                <i class="fas fa-redo"></i>
                Try Another Email
            </a>
        </div>
    </div>
</body>
</html>
