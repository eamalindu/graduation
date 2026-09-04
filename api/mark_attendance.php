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
    echo json_encode(['success' => false, 'message' => 'Registration number is required.']);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        'SELECT id, full_name, attendance_status, attendance_time, roster_status
         FROM students
         WHERE UPPER(registration_number) = UPPER(:reg_number)
         LIMIT 1'
    );
    $stmt->execute(['reg_number' => $regNumber]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'No record found for that registration number.']);
        exit;
    }

    if ($student['roster_status'] !== 'registered') {
        echo json_encode(['success' => false, 'message' => "Registration isn't complete yet."]);
        exit;
    }

    if ($student['attendance_status'] === 'present') {
        echo json_encode([
            'success'         => false,
            'already_marked'  => true,
            'message'         => 'Already recorded.',
            'attendance_time' => $student['attendance_time'],
        ]);
        exit;
    }

    // The status='pending' guard means only one of two overlapping requests
    // (e.g. a double-tap) can ever win this update.
    $update = $pdo->prepare(
        "UPDATE students
         SET attendance_status = 'present', attendance_time = NOW(), marked_by = 'self'
         WHERE id = :id AND attendance_status = 'pending'"
    );
    $update->execute(['id' => $student['id']]);

    if ($update->rowCount() === 0) {
        echo json_encode([
            'success'        => false,
            'already_marked' => true,
            'message'        => 'Already recorded.',
        ]);
        exit;
    }

    $confirm = $pdo->prepare('SELECT attendance_time FROM students WHERE id = :id');
    $confirm->execute(['id' => $student['id']]);
    $attendanceTime = $confirm->fetchColumn();

    echo json_encode([
        'success'         => true,
        'message'         => 'Attendance recorded.',
        'full_name'       => $student['full_name'],
        'attendance_time' => $attendanceTime,
    ]);
} catch (Throwable $e) {
    error_log('mark_attendance error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
}