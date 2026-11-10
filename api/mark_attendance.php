<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/phone.php';
require_once __DIR__ . '/../services/Notifier.php';

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

    $stmt = $pdo->prepare(
        'SELECT id, full_name, attendance_status, attendance_time, roster_status,contact,email,program,seatNo,registered_date
         FROM students
         WHERE UPPER(registration_number) = UPPER(:reg_number)
         LIMIT 1'
    );
    $stmt->execute(['reg_number' => $regNumber]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'No record found for that registration number.']);
        exit;
    }

    if ($student['roster_status'] !== 'registered') {
        echo json_encode(['success' => false, 'message' => "Registration isn't complete yet."]);
        exit;
    }

    if ($student['attendance_status'] === 'present') {
        echo json_encode([
            'success'         => false,
            'already_marked'  => true,
            'message'         => 'Already recorded.',
            'attendance_time' => $student['attendance_time'],
        ]);
        exit;
    }

    // The status='pending' guard means only one of two overlapping requests
    // (e.g. a double-tap) can ever win this update.
    $update = $pdo->prepare(
        "UPDATE students
         SET attendance_status = 'present', attendance_time = NOW(), marked_by = 'self'
         WHERE id = :id AND attendance_status = 'pending'"
    );
    $update->execute(['id' => $student['id']]);

    if ($update->rowCount() === 0) {
        echo json_encode([
            'success'        => false,
            'already_marked' => true,
            'message'        => 'Already recorded.',
        ]);
        exit;
    }

    $confirm = $pdo->prepare('SELECT attendance_time FROM students WHERE id = :id');
    $confirm->execute(['id' => $student['id']]);
    $attendanceTime = $confirm->fetchColumn();

    // Queue notifications (a failure here must never break the response)
    try {
        $q = $pdo->prepare(
            'INSERT IGNORE INTO notification_queue (student_id, channel, recipient, subject, body)
             VALUES (:sid, :ch, :rcpt, :subj, :body)'
        );

        // SMS first (plain text, local mobiles only)
        $sms = lkMobile($student['contact'] ?? null);
        if ($sms !== null) {
            $q->execute([
                'sid'  => $student['id'],
                'ch'   => 'sms',
                'rcpt' => $sms,
                'subj' => null,
                'body' => "Hi {$student['full_name']}, your attendance was recorded at {$attendanceTime}.",
            ]);
        }

        // Email: everyone, rendered from the HTML template
        if (filter_var($student['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $html = Notifier::renderTemplate('graduation_email.html', [
                'name'          => $student['full_name'],
                'programme'     => $student['program'] ?? '-',
                'seat_no'       => $student['seatNo'] ?? '-',
                'registered_on' => !empty($attendanceTime)
                    ? date('Y-m-d, h:i A', strtotime($attendanceTime))
                    : '-',
            ]);

            $q->execute([
                'sid'  => $student['id'],
                'ch'   => 'email',
                'rcpt' => $student['email'],
                'subj' => 'Graduation Ceremony 2026 - Registration Confirmed',
                'body' => $html,
            ]);
        }
    } catch (Throwable $e) {
        error_log('queue error: ' . $e->getMessage());
    }

    echo json_encode([
        'success'         => true,
        'message'         => 'Attendance recorded.',
        'full_name'       => $student['full_name'],
        'attendance_time' => $attendanceTime,
    ]);
} catch (Throwable $e) {
    error_log('mark_attendance error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
}