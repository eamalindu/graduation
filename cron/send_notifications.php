<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }   // never runnable from the web

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/Notifier.php';

const MAX_ATTEMPTS = 5;
const TIME_BUDGET  = 50;   // seconds; stop before the next cron run starts

// Only one worker at a time
$lock = fopen(sys_get_temp_dir() . '/notif_worker.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit;

$start = time();
$pdo   = getDbConnection();

$rows = $pdo->query(
    "SELECT id, channel, recipient, subject, body
     FROM notification_queue
     WHERE status = 'pending' AND attempts < " . MAX_ATTEMPTS . "
     ORDER BY id
     LIMIT 200"
)->fetchAll();

if (!$rows) exit;

$ok = $pdo->prepare(
    "UPDATE notification_queue
     SET status = 'sent', sent_at = NOW(), attempts = attempts + 1, last_error = NULL
     WHERE id = :id"
);

// status is assigned BEFORE attempts on purpose: MySQL evaluates SET left to right,
// so this way it checks the old attempts value.
$bad = $pdo->prepare(
    "UPDATE notification_queue
     SET status = IF(attempts + 1 >= :max, 'failed', 'pending'),
         attempts = attempts + 1,
         last_error = :err
     WHERE id = :id"
);

$sent = 0;
$failed = 0;

foreach ($rows as $r) {
    if (time() - $start >= TIME_BUDGET) break;

    try {
        if ($r['channel'] === 'email') {
            Notifier::sendEmail($r['recipient'], (string)$r['subject'], $r['body']);
        } else {
            Notifier::sendSms($r['recipient'], $r['body']);
            usleep(150000);   // be gentle with the gateway
        }
        $ok->execute(['id' => $r['id']]);
        $sent++;
    } catch (Throwable $e) {
        $bad->execute([
            'max' => MAX_ATTEMPTS,
            'err' => substr($e->getMessage(), 0, 500),
            'id'  => $r['id'],
        ]);
        $failed++;
        error_log("notif #{$r['id']} ({$r['channel']}) failed: " . $e->getMessage());
    }
}

echo date('c') . " sent={$sent} failed={$failed}\n";