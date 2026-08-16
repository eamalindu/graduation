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
    echo json_encode(['success' => false, 'message' => 'Enter your registration number to continue.']);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT registration_number, full_name, course, faculty, batch, attendance_status, attendance_time
         FROM students
         WHERE UPPER(registration_number) = UPPER(:reg_number)
         LIMIT 1');
    $stmt->execute(['reg_number' => $regNumber]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'No record found for that registration number.',]);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $student,]);
} catch (Throwable $e) {
    error_log('search_student error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
}
