<?php
require_once __DIR__ . '/includes/class-database.php';

// Start session to manage CSRF token and rate limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Simple rate limiting: allow only a few failed attempts in a time window
$maxAttempts   = 5;
$lockoutWindow = 600; // seconds
$attempts      = $_SESSION['reservation_failed_attempts'] ?? 0;
$firstAttempt  = $_SESSION['reservation_first_failed_time'] ?? 0;

if ($attempts >= $maxAttempts && (time() - $firstAttempt) < $lockoutWindow) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many failed attempts. Try again later.']);
    exit;
} elseif (time() - $firstAttempt >= $lockoutWindow) {
    $_SESSION['reservation_failed_attempts']    = 0;
    $_SESSION['reservation_first_failed_time'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['reservation_failed_attempts']    = ($attempts + 1);
    $_SESSION['reservation_first_failed_time'] = $firstAttempt ?: time();
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// CSRF token validation
$token = $_POST['token'] ?? '';
if (!$token || !hash_equals($_SESSION['reservation_csrf_token'] ?? '', $token)) {
    $_SESSION['reservation_failed_attempts']    = ($attempts + 1);
    $_SESSION['reservation_first_failed_time'] = $firstAttempt ?: time();
    error_log('Reservation submission failed: Invalid CSRF token from IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token.']);
    exit;
}

// Sanitize inputs before validation
$name   = filter_var(trim($_POST['name'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email  = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone  = preg_replace('/[^0-9\s\-()+]/', '', $_POST['phone'] ?? '');
$date   = preg_replace('/[^0-9-]/', '', $_POST['date'] ?? '');
$time   = preg_replace('/[^0-9:]/', '', $_POST['time'] ?? '');
$guests = filter_var($_POST['guests'] ?? 1, FILTER_SANITIZE_NUMBER_INT);

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
    $_SESSION['reservation_failed_attempts']    = ($attempts + 1);
    $_SESSION['reservation_first_failed_time'] = $firstAttempt ?: time();
    error_log('Reservation submission failed: ' . implode(' ', $errors) . ' IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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

    // Reset failed attempts on success
    $_SESSION['reservation_failed_attempts']    = 0;
    $_SESSION['reservation_first_failed_time'] = 0;

    echo json_encode(['status' => 'success', 'message' => 'Reservation submitted!']);
} catch (Exception $e) {
    $_SESSION['reservation_failed_attempts']    = ($attempts + 1);
    $_SESSION['reservation_first_failed_time'] = $firstAttempt ?: time();
    error_log('Error saving reservation: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'An unexpected error occurred. Please try again later.',
    ]);
}
