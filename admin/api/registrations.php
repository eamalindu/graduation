<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();

try {
    $pdo = getDbConnection();

    $stmt = $pdo->query(
        "SELECT s.full_name,
                s.seatNo,
                s.program,
                s.registration_number,
                r.reg_timestamp
           FROM students s
           JOIN registration r
             ON r.reg_number = s.registration_number
          WHERE r.status = 'Present'
          ORDER BY r.reg_timestamp"
    );

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'students' => $students]);
} catch (Throwable $e) {
    error_log('student data error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load students.']);
}