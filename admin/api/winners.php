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
                s.program,
                s.seatNo,
                p.name AS prize_name,
                d.drawn_at
           FROM lucky_draw d
           JOIN students s ON s.registration_number = d.reg_number
           JOIN prizes   p ON p.id = d.prize_id
          ORDER BY d.drawn_at DESC"
    );

    echo json_encode([
        'success' => true,
        'winners' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    error_log('winners error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load winners.']);
}