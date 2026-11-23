<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';


requireAdminLoginApi();

try {
    $pdo = getDbConnection();

    $stmt = $pdo->query(
        "SELECT id, username, full_name, created_at, superadmin
           FROM admins
          ORDER BY id"
    );

    echo json_encode([
        'success' => true,
        'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);


} catch (Throwable $e) {
    error_log('users error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load users.']);
}