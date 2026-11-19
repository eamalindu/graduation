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
    $pdo->beginTransaction();

    // 1. Must be registered
    $stmt = $pdo->prepare(
        'SELECT s.registration_number, s.full_name
         FROM registration r
         JOIN students s ON s.registration_number = r.reg_number
         WHERE r.reg_number = :reg
         LIMIT 1'
    );
    $stmt->execute(['reg' => $regNumber]);
    $student = $stmt->fetch();

    if (!$student) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Not registered yet. Please register first.']);
        exit;
    }

    $regNumber = $student['registration_number'];

    // 2. Already spun?
    $check = $pdo->prepare(
        'SELECT p.id, p.name
         FROM lucky_draw d JOIN prizes p ON p.id = d.prize_id
         WHERE d.reg_number = :reg'
    );
    $check->execute(['reg' => $regNumber]);
    if ($done = $check->fetch()) {
        $pdo->rollBack();
        echo json_encode([
            'success'      => false,
            'already_spun' => true,
            'message'      => 'You have already spun.',
            'prize_id'     => (int) $done['id'],
            'prize'        => $done['name'],
        ]);
        exit;
    }

    // 3. Lock remaining stock and pick one unit at random
    $prizes = $pdo->query('SELECT id, name, qty_remaining FROM prizes WHERE qty_remaining > 0 FOR UPDATE')
        ->fetchAll();
    $total = array_sum(array_column($prizes, 'qty_remaining'));

    if ($total === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'The lucky draw is over.']);
        exit;
    }

    $roll = random_int(1, $total);
    $won = null;
    foreach ($prizes as $p) {
        $roll -= (int) $p['qty_remaining'];
        if ($roll <= 0) { $won = $p; break; }
    }

    // 4. Deduct stock and record the result
    $pdo->prepare('UPDATE prizes SET qty_remaining = qty_remaining - 1 WHERE id = :id')
        ->execute(['id' => $won['id']]);
    $pdo->prepare('INSERT INTO lucky_draw (reg_number, prize_id) VALUES (:reg, :pid)')
        ->execute(['reg' => $regNumber, 'pid' => $won['id']]);

    $pdo->commit();

    echo json_encode([
        'success'   => true,
        'full_name' => $student['full_name'],
        'prize_id'  => (int) $won['id'],
        'prize'     => $won['name'],
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('spin error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
}