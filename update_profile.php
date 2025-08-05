<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.html'); exit; }

$uid    = $_SESSION['user_id'];
$first  = trim($_POST['first_name'] ?? '');
$last   = trim($_POST['last_name']  ?? '');
$email  = trim($_POST['email']      ?? '');
$pass1  = $_POST['password']        ?? '';
$pass2  = $_POST['password_confirm']?? '';

/* Basic validation */
if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: edit_profile.php?msg=Invalid input'); exit;
}
if ($pass1 !== $pass2) {
    header('Location: edit_profile.php?msg=Passwords do not match'); exit;
}

/* Check for duplicate e‑mail (other users) */
$chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
$chk->execute([$email, $uid]);
if ($chk->fetch()) {
    header('Location: edit_profile.php?msg=E‑mail already taken'); exit;
}

/* Build query dynamically */
$set = "first_name = ?, last_name = ?, email = ?";
$params = [$first, $last, $email, $uid];

if ($pass1 !== '') {
    $hash  = password_hash($pass1, PASSWORD_DEFAULT);
    $set  .= ", password = ?";
    array_splice($params, 3, 0, [$hash]);  // insert before $uid
}

$pdo->prepare("UPDATE users SET $set WHERE id = ?")->execute($params);

header('Location: edit_profile.php?msg=Profile updated successfully');
exit;
?>
