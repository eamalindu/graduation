<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/phone.php';
require_once __DIR__ . '/../services/Notifier.php';

/**
 * Builds the graduation email HTML (your design, inline — no Notifier/template file needed).
 */
function buildGraduationEmail(string $name, string $registeredAt): string
{
    $name         = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $registeredAt = htmlspecialchars($registeredAt, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background: #f5f5f3;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper {
            max-width: 580px;
            margin: 40px auto;
            padding: 0 16px 40px;
        }

        .card {
            background: #ffffff;
            border: 0.5px solid #e8e8e8;
            border-radius: 12px;
            overflow: hidden;
        }

        .card-top {
            background: #333333;
            padding: 32px;
            text-align: center;
        }

        .card-top h4 {
            font-size: 16px;
            font-weight: 500;
            margin-top: 10px;
            color: #e7e7e7;
        }

        .card-top img {
            max-width: 200px;
        }

        .card-body {
            padding: 32px;
        }

        .greeting {
            font-size: 15px;
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 12px;
        }

        .text {
            font-size: 14px;
            color: #555;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f5f5f3;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: 500;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 12px;
        }

        .btn {
            display: block;
            text-align: center;
            background: #333;
            color: #ffffff !important;
            font-size: 13px;
            font-weight: 500;
            padding: 13px 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            width: max-content;
            text-decoration: none;
        }

        .steps-box {
            background: #f5f5f3;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .steps-box ol {
            margin: 0;
            padding-left: 18px;
        }

        .steps-box li {
            font-size: 13px;
            color: #555;
            line-height: 1.8;
        }

        .divider {
            height: 0.5px;
            background: #f0f0f0;
            margin: 24px 0;
        }

        .note {
            font-size: 12px;
            color: #aaa;
            line-height: 1.6;
            text-align: center;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px 0;
            border-bottom: 0.5px solid #efefef;
            font-size: 13px;
            vertical-align: top;
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        .info-label {
            width: 38%;
            color: #aaa;
            padding-right: 12px;
        }

        .info-value {
            font-weight: 500;
            color: #1a1a1a;
            word-break: break-word;
        }

        @media (max-width: 480px) {
            .wrapper {
                margin: 16px auto;
            }

            .card-top {
                padding: 24px 20px;
            }

            .card-body {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>
<div class="wrapper">
    <div class="card">
        <div class="card-top">
            <img src="https://graduation.metropolitancollegeedu.com/images/MC.png" alt="Metropolitan College Logo" width="75px">
            <h4>Graduation Ceremony - 2026</h4>
        </div>
        <div class="card-body">
            <div class="greeting">Hi {$name},</div>
            <p class="text">
                Your attendance was recorded at {$registeredAt}.
            </p>
            <p class="text">
                Please proceed to collect your Sash and Ticket.
            </p>
            <p class="text">
                Participate in our Graduation Lucky Draw and make your graduation even more exciting with fantastic prizes!
            </p>
            <p class="text">
                - Graduation Team
            </p>

            <div class="divider"></div>
            <p class="note">
                This is an automated notification — do not reply to this email.
            </p>
        </div>
    </div>
</div>
</body>

</html>
HTML;
}

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
    // NOTE: id is needed for notification_queue.student_id.
    $stmt = $pdo->prepare(
        'SELECT id, registration_number, full_name, contact, email, seatNo
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

    // Fetch the timestamp ONCE into a plain value (was a PDOStatement before)
    $ts = $pdo->prepare('SELECT reg_timestamp FROM registration WHERE id = :id');
    $ts->execute(['id' => (int) $pdo->lastInsertId()]);
    $regTimestamp = $ts->fetchColumn();

    // 4. Queue SMS + HTML email
    try {
        $q = $pdo->prepare(
            'INSERT IGNORE INTO notification_queue (student_id, channel, recipient, subject, body)
             VALUES (:sid, :ch, :rcpt, :subj, :body)'
        );

        // SMS text
        $messageText = "Hi {$student['full_name']},\n\n"
            . "Your attendance was recorded at {$regTimestamp}.\n\n"
            . "Please proceed to collect your Sash and Ticket.\n\n"
            . "Participate in our Graduation Lucky Draw and make your graduation even more exciting with fantastic prizes!\n\n"
            . "- Graduation Team";

        // SMS
        $sms = lkMobile($student['contact'] ?? null);
        if ($sms !== null) {
            $q->execute([
                'sid'  => $student['id'],
                'ch'   => 'sms',
                'rcpt' => $sms,
                'subj' => null,
                'body' => $messageText,
            ]);
        }

        // Email (HTML)
        if (filter_var($student['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $html = buildGraduationEmail(
                (string) $student['full_name'],
                (string) $regTimestamp
            );

            $q->execute([
                'sid'  => $student['id'],
                'ch'   => 'email',
                'rcpt' => $student['email'],
                'subj' => 'Graduation Ceremony 2026 - Slash and Ticket Collection',
                'body' => $html,
            ]);
        }
    } catch (Throwable $e) {
        // Queue problems must never block the scan at the gate
        error_log('queue error: ' . $e->getMessage());
    }

    echo json_encode([
        'success'       => true,
        'message'       => 'Registration recorded.',
        'full_name'     => $student['full_name'],
        'reg_timestamp' => $regTimestamp,
    ]);
} catch (Throwable $e) {
    error_log('mark_registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
}