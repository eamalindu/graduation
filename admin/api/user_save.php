<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();

function out(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(['success' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $pdo = getDbConnection();

    // Only superadmins can manage users.
    // NOTE: change 'admin_id' to whatever key your auth.php stores in the session.
    $meId = (int)($_SESSION['admin_id'] ?? 0);
    $chk  = $pdo->prepare('SELECT superadmin FROM admins WHERE id = ?');
    $chk->execute([$meId]);
    if (!(int)$chk->fetchColumn()) {
        out(['success' => false, 'message' => 'Superadmin access required.'], 403);
    }

    $in         = json_decode(file_get_contents('php://input') ?: '', true) ?? [];
    $id         = (int)($in['id'] ?? 0);
    $username   = trim((string)($in['username'] ?? ''));
    $fullName   = trim((string)($in['full_name'] ?? ''));
    $password   = (string)($in['password'] ?? '');
    $superadmin = !empty($in['superadmin']) ? 1 : 0;

    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        out(['success' => false, 'message' => 'Username must be 3-50 chars (letters, numbers, . _ -).'], 422);
    }
    if (strlen($fullName) > 150) {
        out(['success' => false, 'message' => 'Full name is too long.'], 422);
    }
    if ($password !== '' && strlen($password) < 8) {
        out(['success' => false, 'message' => 'Password must be at least 8 characters.'], 422);
    }

    if ($id === 0) {
        // CREATE
        if ($password === '') {
            out(['success' => false, 'message' => 'Password is required for new users.'], 422);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO admins (username, password_hash, full_name, superadmin) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $fullName !== '' ? $fullName : null,
            $superadmin,
        ]);
        out(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    // UPDATE
    if ($id === $meId && $superadmin === 0) {
        out(['success' => false, 'message' => "You can't remove your own superadmin access."], 422);
    }

    $sql    = 'UPDATE admins SET username = ?, full_name = ?, superadmin = ?';
    $params = [$username, $fullName !== '' ? $fullName : null, $superadmin];

    if ($password !== '') {
        $sql     .= ', password_hash = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $sql     .= ' WHERE id = ?';
    $params[] = $id;

    $pdo->prepare($sql)->execute($params);
    out(['success' => true]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        out(['success' => false, 'message' => 'That username is already taken.'], 409);
    }
    error_log('user_save error: ' . $e->getMessage());
    out(['success' => false, 'message' => 'Could not save user.'], 500);
} catch (Throwable $e) {
    error_log('user_save error: ' . $e->getMessage());
    out(['success' => false, 'message' => 'Could not save user.'], 500);
}