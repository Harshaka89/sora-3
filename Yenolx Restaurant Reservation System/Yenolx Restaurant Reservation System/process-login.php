<?php
session_start();
require_once __DIR__ . '/includes/class-database.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$nonce    = $_POST['yenolx_login_nonce'] ?? '';

// Verify CSRF token using a WordPress nonce when available.
if (function_exists('wp_verify_nonce')) {
  if (!wp_verify_nonce($nonce, 'yenolx_admin_login')) {
    header("Location: login.php?error=csrf");
    exit;
  }
} else {
  if (empty($_SESSION['yenolx_login_nonce']) || !hash_equals($_SESSION['yenolx_login_nonce'], $nonce)) {
    header("Location: login.php?error=csrf");
    exit;
  }
}

// Sanitize user input before using in database queries.
if (function_exists('sanitize_user')) {
  $username = sanitize_user($username);
} else {
  $username = filter_var($username, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}

if (function_exists('sanitize_text_field')) {
  $password = sanitize_text_field($password);
} else {
  $password = filter_var($password, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}

$db = new Database();
$pdo = $db->connect();

$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
  session_regenerate_id(true);
  $_SESSION['admin_logged_in'] = true;
  $_SESSION['admin_username'] = $username;
  header("Location: views/admin/dashboard.php");
  exit;
} else {
  header("Location: login.php?error=1");
  exit;
}
