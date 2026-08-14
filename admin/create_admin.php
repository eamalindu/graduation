<?php
declare(strict_types=1);

// Run from the command line only:  php create_admin.php
// Creates a new admin account, or resets the password of an existing one
// (same username = update). Never expose this over the web — the SAPI
// check below blocks it, and admin/.htaccess denies it too as a second layer.

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

function prompt(string $label, bool $hidden = false): string
{
    echo $label;
    if ($hidden && stripos(PHP_OS, 'WIN') === false) {
        system('stty -echo');
        $value = trim((string) fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $value = trim((string) fgets(STDIN));
    }
    return $value;
}

$username = prompt('Username: ');
$fullName = prompt('Full name (optional): ');
$password = prompt('Password: ', true);
$confirm  = prompt('Confirm password: ', true);

if ($username === '' || $password === '') {
    exit("Username and password are required.\n");
}

if ($password !== $confirm) {
    exit("Passwords do not match.\n");
}

$pdo = getDbConnection();

$stmt = $pdo->prepare(
    'INSERT INTO admins (username, password_hash, full_name)
     VALUES (:username, :password_hash, :full_name)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name)'
);
$stmt->execute([
    'username'      => $username,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'full_name'     => $fullName !== '' ? $fullName : null,
]);

echo "Admin account '{$username}' saved.\n";
