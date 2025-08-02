<?php
require_once __DIR__ . '/../includes/class-slot-helper.php';

header('Content-Type: application/json');

// Optional nonce verification to guard against CSRF for authenticated users
if (function_exists('is_user_logged_in') && function_exists('wp_verify_nonce') && is_user_logged_in()) {
    $nonce = $_GET['_wpnonce'] ?? '';
    if (!$nonce || !wp_verify_nonce($nonce, 'yrr_get_available_times')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid nonce']);
        exit;
    }
}

// Sanitize inputs
$date        = trim(filter_input(INPUT_GET, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$party_size  = filter_input(INPUT_GET, 'party_size', FILTER_VALIDATE_INT, [
    'options' => ['default' => 2, 'min_range' => 1],
]);
$location_id = filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT, [
    'options' => ['default' => 1, 'min_range' => 1],
]);

// Validate date format
$dt = $date ? DateTime::createFromFormat('Y-m-d', $date) : false;
if (!$dt || $dt->format('Y-m-d') !== $date || $party_size === false || $location_id === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request parameters']);
    exit;
}

// Generate available time slots using YRR_Slot_Helper
$available = YRR_Slot_Helper::get_available_slots($date, $party_size, $location_id);

echo json_encode(array_values($available));
