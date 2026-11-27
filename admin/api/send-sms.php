<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../services/phone.php';
$cfg = require __DIR__ . '/../../config/notify.php';   // also loads vendor/autoload.php
$c   = $cfg['sms'];
$m   = $cfg['smtp'];

const TEST_MODE   = true;            // flip to false for the real send
const TEST_NUMBER = '0781011144';
const TEST_EMAIL  = 'eamalindu@gmail.com';   // your own address

requireAdminLoginApi();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'POST only']));
}

if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
}

// release the session so other requests aren't blocked while sending
session_write_close();

// stops two overlapping runs (double click / second tab)
$lock = fopen(sys_get_temp_dir() . '/gold_sms.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    http_response_code(409);
    exit(json_encode(['success' => false, 'message' => 'Already sending, please wait.']));
}

function sendSms(array $c, string $to, string $message): bool
{
    $url = 'https://e-sms.dialog.lk/api/v1/message-via-url/create/url-campaign?' . http_build_query([
            'esmsqk'         => $c['key'],
            'list'           => $to,
            'source_address' => $c['source'],
            'message'        => $message,
        ]);

    for ($i = 0; $i < 2; $i++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res !== false && $code < 400) return true;
        usleep(300000);
    }
    return false;
}

function makeMailer(array $m): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host          = $m['host'];
    $mail->Port          = $m['port'];
    $mail->SMTPAuth      = true;
    $mail->Username      = $m['user'];
    $mail->Password      = $m['pass'];
    $mail->SMTPSecure    = strtolower($m['secure']) === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPKeepAlive = true;
    $mail->Timeout       = 10;
    $mail->CharSet       = 'UTF-8';
    $mail->setFrom($m['from'], $m['from_name']);
    $mail->isHTML(true);
    $mail->Subject = 'Congratulations, Gold Medalist - Graduation Ceremony 2026';
    return $mail;
}

function sendEmail(PHPMailer $mail, string $to): bool
{
    try {
        $mail->clearAddresses();
        $mail->addAddress($to);

        $mail->Body = '<p>Dear Graduate,</p>'
            . '<p>On behalf of the Board of Management, Metropolitan College is delighted to officially confirm '
            . 'that you have been selected as one of the Gold Medalists of the Graduation Ceremony 2026.</p>'
            . '<p>Congratulations on your outstanding academic achievement!</p>'
            . '<p>Board of Management<br>Metropolitan College</p>';

        $mail->AltBody = "Dear Graduate,\n\n"
            . "On behalf of the Board of Management, Metropolitan College is delighted to officially confirm "
            . "that you have been selected as one of the Gold Medalists of the Graduation Ceremony 2026.\n\n"
            . "Congratulations on your outstanding academic achievement!\n\n"
            . "Board of Management\nMetropolitan College";

        return $mail->send();
    } catch (MailException $e) {
        error_log("Gold email failed for $to: " . $e->getMessage());
        return false;
    }
}

$mail = null;

try {
    set_time_limit(180);
    $pdo = getDbConnection();

    $students = $pdo->query(
        'SELECT id, contact, email FROM students
         WHERE isGold = 1'
    )->fetchAll(PDO::FETCH_ASSOC);

    $message = "Congratulations!\n\nYou have been officially selected as a Gold Medalist of Metropolitan College Graduation 2026.\n\nWell done!\n\n- Board of Management, Metropolitan College";

    $mail  = makeMailer($m);
    $sms   = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
    $email = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

    foreach ($students as $s) {
        // SMS
        $number = lkMobile($s['contact'] ?? null);
        if ($number === null) {
            $sms['skipped']++;
        } elseif (sendSms($c, TEST_MODE ? TEST_NUMBER : $number, $message)) {
            $sms['sent']++;
        } else {
            $sms['failed']++;
            error_log("Gold SMS failed for student {$s['id']}");
        }

        // Email
        $addr = trim((string)($s['email'] ?? ''));
        if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            $email['skipped']++;
        } elseif (sendEmail($mail, TEST_MODE ? TEST_EMAIL : $addr)) {
            $email['sent']++;
        } else {
            $email['failed']++;
            error_log("Gold email failed for student {$s['id']}");
        }

        if (TEST_MODE) break;   // one test SMS + one test email is enough
    }

    echo json_encode([
        'success' => true,
        'test'    => TEST_MODE,
        'sms'     => $sms,
        'email'   => $email,
    ]);
} catch (Throwable $e) {
    error_log('Gold notify error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again in a moment.']);
} finally {
    if ($mail instanceof PHPMailer) {
        try { $mail->smtpClose(); } catch (Throwable $e) {}
    }
    flock($lock, LOCK_UN);
    fclose($lock);
}