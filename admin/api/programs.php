<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();

try {
    $pdo = getDbConnection();

    $students = $pdo->query("SELECT id, name,assigned
         FROM program
         ORDER BY id")->fetchAll();

    echo json_encode(['success' => true, 'programs' => $students]);
} catch (Throwable $e) {
    error_log('programs data error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load programs.']);
}