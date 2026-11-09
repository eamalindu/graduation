<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set('Asia/Colombo');

Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

return [
    'smtp' => [
        'host'      => $_ENV['MAIL_HOST'] ?? '',
        'port'      => (int)($_ENV['MAIL_PORT'] ?? 587),
        'user'      => $_ENV['MAIL_USERNAME'] ?? '',
        'pass'      => $_ENV['MAIL_PASSWORD'] ?? '',
        'secure'    => $_ENV['MAIL_SECURE'] ?? 'tls',
        'from'      => $_ENV['MAIL_FROM'] ?? ($_ENV['MAIL_USERNAME'] ?? ''),
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Metropolitan College',
    ],
    'sms' => [
        'key'    => $_ENV['SMS_API'] ?? '',
        'source' => $_ENV['SMS_SOURCE'] ?? '',
    ],
];