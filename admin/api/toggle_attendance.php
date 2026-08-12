<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Session expired. Refresh and try again.']);
    exit;
}

$studentId = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($studentId <= 0 || !in_array($action, ['present', 'pending'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    $pdo = getDbConnection();

    $check = $pdo->prepare('SELECT id FROM students WHERE id = :id');
    $check->execute(['id' => $studentId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    if ($action === 'present') {
        $stmt = $pdo->prepare(
            "UPDATE students
             SET attendance_status = 'present', attendance_time = NOW(), marked_by = :marked_by
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $studentId,
            'marked_by' => 'admin:' . $_SESSION['admin_username'],
        ]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE students
             SET attendance_status = 'pending', attendance_time = NULL, marked_by = NULL
             WHERE id = :id"
        );
        $stmt->execute(['id' => $studentId]);
    }

    $fetch = $pdo->prepare(
        'SELECT id, registration_number, full_name, attendance_status, attendance_time, marked_by
         FROM students WHERE id = :id'
    );
    $fetch->execute(['id' => $studentId]);

    echo json_encode(['success' => true, 'student' => $fetch->fetch()]);
} catch (Throwable $e) {
    error_log('toggle_attendance error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong.']);
}
