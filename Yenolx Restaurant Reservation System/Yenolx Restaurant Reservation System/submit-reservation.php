<?php
require_once __DIR__ . '/includes/class-database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Invalid request');
}

$name   = $_POST['name'] ?? '';
$email  = $_POST['email'] ?? '';
$phone  = $_POST['phone'] ?? '';
$date   = $_POST['date'] ?? '';
$time   = $_POST['time'] ?? '';
$guests = $_POST['guests'] ?? 1;

if (!$name || !$email || !$phone || !$date || !$time || !$guests) {
  die("Missing required fields.");
}

try {
  $db = new Database();
  $pdo = $db->connect();

  $stmt = $pdo->prepare("INSERT INTO reservations (name, email, phone, date, time, guests, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
  $stmt->execute([$name, $email, $phone, $date, $time, $guests]);

  echo "<script>alert('Reservation submitted!');window.location.href='public-reservation-form.php';</script>";
} catch (Exception $e) {
  die("Error saving reservation: " . $e->getMessage());
}
