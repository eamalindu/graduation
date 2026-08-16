<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['login_error'] = 'Session expired. Please try again.';
        header('Location: login.php');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = 'Enter your username and password.';
        header('Location: login.php');
        exit;
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'] ?: $admin['username'];
            header('Location: dashboard.php');
            exit;
        }

        $_SESSION['login_error'] = 'Invalid username or password.';
        header('Location: login.php');
        exit;
    } catch (Throwable $e) {
        error_log('admin login error: ' . $e->getMessage());
        $_SESSION['login_error'] = 'Something went wrong. Try again in a moment.';
        header('Location: login.php');
        exit;
    }
}

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#7A1F2B">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | Graduation Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/ico" href="../favicon.ico"/>
</head>
<body>
<div class="page">

    <header class="header">
        <div class="seal seal--header" aria-hidden="true">
            <a href="">
                <img src="../images/MC.png" alt="Metropolitan College Seal" class="seal__img" width="100" height="100"></a>
        </div>
        <p class="header__eyebrow">Convocation &middot; Admin</p>
        <h1 class="header__title">Metropolitan College</h1>
        <p class="header__subtitle">Sign in to manage attendance</p>
    </header>

    <main class="panel">
        <div class="panel__inner">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

                <div class="field-group">
                    <label for="username" class="field-label">Username</label>
                    <input type="text" id="username" name="username" class="field-input field-input--text"
                           autocomplete="username" required>
                </div>

                <div class="field-group" style="margin-top: 10px">
                    <label for="password" class="field-label">Password</label>
                    <input type="password" id="password" name="password" class="field-input field-input--text"
                           autocomplete="current-password" required>
                </div>

                <?php if ($loginError): ?>
                    <div class="message"><?= htmlspecialchars($loginError, ENT_QUOTES) ?></div>
                <?php endif; ?>

                <button type="submit" class="btn btn--primary">Sign In</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>Metropolitan College</p>
    </footer>

</div>
</body>
</html>
