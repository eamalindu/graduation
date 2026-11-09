<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

final class Notifier
{
    private static function cfg(): array
    {
        static $cfg = null;
        return $cfg ??= require __DIR__ . '/../config/notify.php';
    }

    /** Throws on failure. */
    /** Throws on failure. $body is plain text; it's wrapped in an HTML layout. */
    /** Throws on failure. $html is a complete HTML document. */
    public static function sendEmail(string $to, string $subject, string $html): void
    {
        $c = self::cfg()['smtp'];

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $c['host'];
        $mail->Port       = $c['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $c['user'];
        $mail->Password   = $c['pass'];
        $mail->SMTPSecure = $c['secure'];
        $mail->Timeout    = 10;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($c['from'], $c['from_name']);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->msgHTML($html);   // sets HTML body + auto-generates plain-text AltBody

        $mail->send();
    }

    /** Fills {{placeholders}} in templates/$file. Values are HTML-escaped. */
    public static function renderTemplate(string $file, array $vars): string
    {
        $html = file_get_contents(__DIR__ . '/../templates/' . $file);
        if ($html === false) {
            throw new RuntimeException("Email template not found: {$file}");
        }

        $map = [];
        foreach ($vars as $k => $v) {
            $map['{{' . $k . '}}'] = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        }
        return strtr($html, $map);
    }



    /** Throws on failure. $number is already 94XXXXXXXXX. */
    public static function sendSms(string $number, string $body): void
    {
        $c = self::cfg()['sms'];
        if ($c['key'] === '') {
            throw new RuntimeException('SMS config missing (SMS_API / SMS_SOURCE)');
        }

        $url = 'https://e-sms.dialog.lk/api/v1/message-via-url/create/url-campaign?' . http_build_query([
                'esmsqk'         => $c['key'],
                'list'           => $number,
                'source_address' => $c['source'],
                'message'        => $body,
            ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new RuntimeException('SMS cURL error: ' . $err);
        }

        $errors = [
            '2001' => 'Error during campaign creation',
            '2002' => 'Bad request',
            '2003' => 'Empty number list',
            '2004' => 'Empty message body',
            '2005' => 'Invalid number list format',
            '2006' => 'Not eligible for GET requests (access level)',
            '2007' => 'Invalid key',
            '2008' => 'Insufficient balance / package exhausted',
            '2009' => 'No valid numbers after mask-blocked removal',
            '2010' => 'Not eligible to consume package',
            '2011' => 'Transactional error',
        ];

        $code = trim((string)$resp);
        if ($code !== '1') {
            throw new RuntimeException('SMS failed [' . $code . ']: ' . ($errors[$code] ?? 'Unknown response'));
        }
    }

    /** Returns the raw balance string, throws on failure. */
    public static function smsBalance(): string
    {
        $c   = self::cfg()['sms'];
        $url = 'https://e-sms.dialog.lk/api/v1/message-via-url/check/balance?' . http_build_query(['esmsqk' => $c['key']]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $resp = curl_exec($ch);
        curl_close($ch);

        if ($resp === false || !str_contains($resp, '|')) {
            throw new RuntimeException('Balance check failed: ' . var_export($resp, true));
        }
        [$status, $balance] = explode('|', $resp, 2);
        if (trim($status) !== '1') {
            throw new RuntimeException('Balance check error code ' . trim($status));
        }
        return trim($balance);
    }
}