<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();

try {
    $pdo = getDbConnection();

    $stmt = $pdo->query(
        "SELECT
            COUNT(CASE WHEN reg_timestamp >= '2026-11-21' AND reg_timestamp < '2026-11-22' THEN 1 END) AS nov_21,
            COUNT(CASE WHEN reg_timestamp >= '2026-11-22' AND reg_timestamp < '2026-11-23' THEN 1 END) AS nov_22,
            COUNT(CASE WHEN reg_timestamp >= '2026-11-25' AND reg_timestamp < '2026-11-26' THEN 1 END) AS nov_25
           FROM registration
          WHERE reg_timestamp >= '2026-11-21'
            AND reg_timestamp <  '2026-11-23'"
    );

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'counts'  => [
            'nov_21' => (int) $row['nov_21'],
            'nov_22' => (int) $row['nov_22'],
            'nov_25' => (int) $row['nov_25'],
        ],
    ]);
} catch (Throwable $e) {
    error_log('registration counts error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load counts.']);
}