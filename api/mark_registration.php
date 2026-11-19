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

    // 1. Make sure the student exists
    $stmt = $pdo->prepare(
        'SELECT registration_number, full_name
         FROM students
         WHERE registration_number = :reg
         LIMIT 1'
    );
    $stmt->execute(['reg' => $regNumber]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'No student found for that registration number.']);
        exit;
    }

    // Use the canonical value from the students table
    $regNumber = $student['registration_number'];

    // 2. Already registered?
    $check = $pdo->prepare('SELECT reg_timestamp FROM registration WHERE reg_number = :reg LIMIT 1');
    $check->execute(['reg' => $regNumber]);
    $existing = $check->fetch();

    if ($existing) {
        echo json_encode([
            'success'        => false,
            'already_marked' => true,
            'message'        => 'Already registered.',
            'full_name'      => $student['full_name'],
            'reg_timestamp'  => $existing['reg_timestamp'],
        ]);
        exit;
    }

    // 3. Register
    try {
        $insert = $pdo->prepare('INSERT INTO registration (reg_number) VALUES (:reg)');
        $insert->execute(['reg' => $regNumber]);
    } catch (PDOException $e) {
        // 1062 = duplicate key: another scan won the race between check and insert
        if (($e->errorInfo[1] ?? null) === 1062) {
            $check->execute(['reg' => $regNumber]);
            $existing = $check->fetch();
            echo json_encode([
                'success'        => false,
                'already_marked' => true,
                'message'        => 'Already registered.',
                'full_name'      => $student['full_name'],
                'reg_timestamp'  => $existing['reg_timestamp'] ?? null,
            ]);
            exit;
        }
        throw $e;
    }

    $ts = $pdo->prepare('SELECT reg_timestamp FROM registration WHERE id = :id');
    $ts->execute(['id' => (int) $pdo->lastInsertId()]);

    echo json_encode([
        'success'       => true,
        'message'       => 'Registration recorded.',
        'full_name'     => $student['full_name'],
        'reg_timestamp' => $ts->fetchColumn(),
    ]);
} catch (Throwable $e) {
    error_log('mark_registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
}