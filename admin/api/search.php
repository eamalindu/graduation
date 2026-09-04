<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();

$query = trim($_GET['q'] ?? '');

if ($query === '') {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        'SELECT id, registration_number, full_name, email, program, attendance_status, attendance_time, marked_by, roster_status
         FROM students
         WHERE registration_number LIKE :query1 OR full_name LIKE :query2
         ORDER BY full_name
         LIMIT 25'
    );
    $likeQuery = '%' . $query . '%';
    $stmt->execute(['query1' => $likeQuery, 'query2' => $likeQuery]);

    echo json_encode(['success' => true, 'results' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    error_log('admin search error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Search failed.']);
}