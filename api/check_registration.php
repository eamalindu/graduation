<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$regNumber = trim($_POST['registration_number'] ?? '');

if ($regNumber === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Registration number is required.']);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        'SELECT reg_timestamp
         FROM registration
         WHERE reg_number = :reg
         LIMIT 1'
    );
    $stmt->execute(['reg' => $regNumber]);
    $timestamp = $stmt->fetchColumn();

    echo json_encode([
        'success'       => true,
        'reg_timestamp' => $timestamp !== false ? $timestamp : null,
    ]);
} catch (Throwable $e) {
    error_log('check_registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
}