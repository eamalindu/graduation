<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#7A1F2B">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard | Graduation Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="icon" type="image/ico" href="../favicon.ico"/>
</head>
<body>
<div class="admin-page">

    <header class="admin-topbar">
        <div class="admin-topbar__brand">
            <span>Admin &middot; Attendance</span>
        </div>
        <div class="admin-topbar__user">
            <span><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES) ?></span>
            <a href="logout.php" class="admin-topbar__logout">Log out</a>
        </div>
    </header>

    <main class="admin-main">

        <section class="stats-grid" aria-live="polite">
            <div class="stat-card"><a href="all_students.php">
                    <span class="stat-card__value" id="stat-total">—</span>
                    <span class="stat-card__label">Total</span>
                </a>
            </div>
            <div class="stat-card">
                <span class="stat-card__value" id="stat-present">—</span>
                <span class="stat-card__label">Present</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__value" id="stat-pending">—</span>
                <span class="stat-card__label">Pending</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__value" id="stat-percent">—</span>
                <span class="stat-card__label">Checked In</span>
            </div>
        </section>

        <section class="panel-section">
            <h2 class="section-title">Search &amp; Manual Check-in</h2>
            <form id="admin-search-form" class="admin-search-form" autocomplete="off">
                <input
                        type="text"
                        id="admin-search-input"
                        class="field-input field-input--text"
                        placeholder="Registration number or name"
                        autocomplete="off"
                >
                <button type="submit" class="btn btn--primary btn--inline">Search</button>
            </form>
            <div id="admin-message" class="message" hidden aria-live="polite"></div>
            <div id="search-results" class="search-results"></div>
        </section>

        <section class="panel-section">
            <h2 class="section-title">Recently Checked In</h2>
            <div id="recent-list" class="recent-list" aria-live="polite">
                <p class="empty-note">No check-ins yet.</p>
            </div>
        </section>

    </main>
</div>

<script>window.CSRF_TOKEN = "<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>";</script>
<script src="assets/js/admin.js"></script>
</body>
</html>
