<?php
require_once __DIR__ . '/../includes/class-slot-helper.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
if (!$date) {
  echo json_encode([]);
  exit;
}

$party_size = isset($_GET['party_size']) ? (int) $_GET['party_size'] : 2;
$location_id = isset($_GET['location_id']) ? (int) $_GET['location_id'] : 1;

// Generate available time slots using YRR_Slot_Helper
$available = YRR_Slot_Helper::get_available_slots($date, $party_size, $location_id);

echo json_encode(array_values($available));
