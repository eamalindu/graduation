<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdminLoginApi();


try {
    $pdo = getDbConnection();

    $totals = $pdo->query("select sum(channel='sms' and status='pending') as SMS_PENDING, sum(channel='sms' and status='sent') as SMS_SENT, sum(channel='email' and status='pending') as EMAIL_PENDING, sum(channel='email' and status='sent') as EMAIL_SENT from notification_queue")->fetch();

    $smsPending = (int)$totals['SMS_PENDING'];
    $smsSent = (int)$totals['SMS_SENT'];
    $emailPending = (int)$totals['EMAIL_PENDING'];
    $emailSent = (int)$totals['EMAIL_SENT'];


    echo json_encode(['success' => true, 'sms_pending' => $smsPending, 'sms_sent' => $smsSent, 'email_pending' => $emailPending, 'email_sent' => $emailSent,]);
} catch (Throwable $e) {
    error_log('admin stats error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load stats.']);
}