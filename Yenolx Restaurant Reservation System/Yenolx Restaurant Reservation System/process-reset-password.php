<?php
session_start();
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/class-database.php';

if (
    !isset($_POST['nonce'], $_SESSION['reset_password_nonce']) ||
    !hash_equals($_SESSION['reset_password_nonce'], $_POST['nonce'])
) {
    header("Location: reset-password.php?error=" . urlencode('Invalid request'));
    exit;
}
unset($_SESSION['reset_password_nonce']);

$current = trim(filter_input(INPUT_POST, 'current_password', FILTER_SANITIZE_STRING) ?? '');
$new = trim(filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING) ?? '');
$confirm = trim(filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING) ?? '');
$user = $_SESSION['admin_username'];

if (!$current || !$new || !$confirm) {
  header("Location: reset-password.php?error=" . urlencode('Missing fields'));
  exit;
}

if ($new !== $confirm) {
  header("Location: reset-password.php?error=" . urlencode('Passwords do not match'));
  exit;
}

try {
  $db = new Database();
  $pdo = $db->connect();

  $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE username = ?");
  $stmt->execute([$user]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row || !password_verify($current, $row['password'])) {
    header("Location: reset-password.php?error=" . urlencode('Incorrect current password'));
    exit;
  }

  $new_hashed = password_hash($new, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
  $stmt->execute([$new_hashed, $user]);

  header("Location: reset-password.php?success=1");
  exit;
} catch (Exception $e) {
  header("Location: reset-password.php?error=" . urlencode('Error: ' . $e->getMessage()));
  exit;
}
