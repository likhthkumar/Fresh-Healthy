<?php
// Show all errors while we’re debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';   // gives us $pdo

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

/* ---------- Collect & validate form values ---------- */
$first  = trim($_POST['first_name']        ?? '');
$last   = trim($_POST['last_name']         ?? '');
$email  = trim($_POST['email']             ?? '');
$pass1  =        $_POST['password']        ?? '';
$pass2  =        $_POST['confirm_password']?? '';
$terms  = isset($_POST['terms']);
$phone   = trim($_POST['phone']   ?? '');
$address = trim($_POST['address']?? '');

if (!$terms)                die('You must accept the terms.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                            die('Invalid e‑mail address.');
if ($pass1 !== $pass2)      die('Passwords do not match.');

/* ---------- Insert into DB ---------- */
$hash = password_hash($pass1, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
      'INSERT INTO users (first_name, last_name, email, password)
       VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$first, $last, $email, $hash]);
    echo '<script>localStorage.setItem("registerSuccess", "1"); window.location.href = "register_success_popup.html";</script>';
    exit;
} catch (PDOException $e) {
    // 23000 = duplicate key (email already taken)
    if ($e->getCode() == 23000) {
        echo '<script>localStorage.setItem("registerEmailExists", "1"); window.location.href = "register.html";</script>';
        exit;
    } else {
        echo 'Database error: ' . $e->getMessage();
    }
}
?>
