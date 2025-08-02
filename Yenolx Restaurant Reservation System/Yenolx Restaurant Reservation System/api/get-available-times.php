<?php
require_once __DIR__ . '/../includes/class-database.php';
require_once __DIR__ . '/../includes/class-slot-helper.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
if (!$date) {
  echo json_encode([]);
  exit;
}

$db = new Database();
$pdo = $db->connect();

// Load existing reservations for that day
$stmt = $pdo->prepare("SELECT time FROM reservations WHERE date = ?");
$stmt->execute([$date]);
$booked_times = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'time');

// Generate available time slots from SlotHelper
$slots = SlotHelper::generateTimeSlots('11:00', '22:00', 30); // 11 AM to 10 PM, 30-min slots

$available = array_filter($slots, function ($slot) use ($booked_times) {
  return !in_array($slot, $booked_times);
});

echo json_encode(array_values($available));
