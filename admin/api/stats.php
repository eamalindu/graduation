<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();

try {
    $pdo = getDbConnection();

    $totals = $pdo->query("SELECT
            COUNT(*) AS total,
            SUM(attendance_status = 'present') AS present
         FROM students where roster_status = 'registered'")->fetch();

    $total = (int)$totals['total'];
    $present = (int)$totals['present'];
    $pending = $total - $present;
    $percent = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

    $recent = $pdo->query("SELECT registration_number, full_name, attendance_time
         FROM students
         WHERE attendance_status = 'present'
         ORDER BY attendance_time DESC
         LIMIT 8")->fetchAll();

    echo json_encode(['success' => true, 'total' => $total, 'present' => $present, 'pending' => $pending, 'percent' => $percent, 'recent' => $recent,]);
} catch (Throwable $e) {
    error_log('admin stats error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load stats.']);
}
