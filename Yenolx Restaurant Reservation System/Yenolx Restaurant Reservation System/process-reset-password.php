<?php
session_start();
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/class-database.php';

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$user = $_SESSION['admin_username'];

if (!$current || !$new || !$confirm) {
  header("Location: reset-password.php?error=Missing fields");
  exit;
}

if ($new !== $confirm) {
  header("Location: reset-password.php?error=Passwords do not match");
  exit;
}

try {
  $db = new Database();
  $pdo = $db->connect();

  $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE username = ?");
  $stmt->execute([$user]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row || !password_verify($current, $row['password'])) {
    header("Location: reset-password.php?error=Incorrect current password");
    exit;
  }

  $new_hashed = password_hash($new, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
  $stmt->execute([$new_hashed, $user]);

  header("Location: reset-password.php?success=1");
} catch (Exception $e) {
  header("Location: reset-password.php?error=Error: " . urlencode($e->getMessage()));
}
