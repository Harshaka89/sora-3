<?php
session_start();
require_once __DIR__ . '/includes/class-database.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$db = new Database();
$pdo = $db->connect();

$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
  $_SESSION['admin_logged_in'] = true;
  $_SESSION['admin_username'] = $username;
  header("Location: views/admin/dashboard.php");
} else {
  header("Location: login.php?error=1");
}
