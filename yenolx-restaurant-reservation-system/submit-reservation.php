<?php
require_once __DIR__ . '/includes/class-database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$name   = $_POST['name'] ?? '';
$email  = $_POST['email'] ?? '';
$phone  = $_POST['phone'] ?? '';
$date   = $_POST['date'] ?? '';
$time   = $_POST['time'] ?? '';
$guests = $_POST['guests'] ?? 1;

$errors = [];

if (!$name) {
    $errors[] = 'Name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email is required.';
}

if (!preg_match('/^\+?[0-9\s\-()]{7,}$/', $phone)) {
    $errors[] = 'A valid phone number is required.';
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $errors[] = 'A valid date is required.';
}

if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
    $errors[] = 'A valid time is required.';
}

if (!filter_var($guests, FILTER_VALIDATE_INT) || $guests <= 0) {
    $errors[] = 'Guest count must be a positive integer.';
}

if ($errors) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $db  = new Database();
    $pdo = $db->connect();

    $stmt = $pdo->prepare(
        "INSERT INTO reservations (name, email, phone, date, time, guests, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
    );
    $stmt->execute([$name, $email, $phone, $date, $time, $guests]);

    echo json_encode(['status' => 'success', 'message' => 'Reservation submitted!']);
} catch (Exception $e) {
    error_log('Error saving reservation: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'An unexpected error occurred. Please try again later.',
    ]);
}
