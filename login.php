<?php
// login.php
session_start();
require 'db.php';                // gives $pdo

/* --------- collect POST data --------- */
$email = trim($_POST['email']    ?? '');
$pass  =        $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
    die('Missing e‑mail or password.');
}

/* --------- fetch user row --------- */
$stmt = $pdo->prepare("SELECT id, first_name, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password'])) {
    // Show popup on login page for invalid credentials
    echo '<script>localStorage.setItem("loginInvalid", "1"); window.location.href = "login.html";</script>';
    exit;
}

/* --------- credentials OK; create session --------- */
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['first_name'];

/* --------- set login success flag for popup --------- */
echo '<script>
localStorage.setItem("loginSuccess", "1");
localStorage.setItem("currentUserId", "' . $user['id'] . '");
// Move guest cart to user cart on login
(function() {
    var userId = "' . $user['id'] . '";
    var guestCart = localStorage.getItem("cartItems_guest");
    if (guestCart && guestCart !== "[]") {
        localStorage.setItem("cartItems_" + userId, guestCart);
        localStorage.removeItem("cartItems_guest");
    }
})();
window.location.href = "login_success_popup.html";
</script>';
exit;
?>
