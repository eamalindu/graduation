<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

header('Location: ' . (empty($_SESSION['admin_id']) ? 'login.php' : 'dashboard.php'));
exit;
