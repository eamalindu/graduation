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
    <title>Users | Graduation Attendance</title>
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
        <div class="admin-topbar__brand"><a href="dashboard.php">
                <span>Admin &middot; Users</span></a>
        </div>
        <div class="admin-topbar__user">
            <span><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES) ?></span>
            <a href="logout.php" class="admin-topbar__logout">Log out</a>
        </div>
    </header>

    <main class="admin-main" style="max-width: 90%;">
        <section class="panel-section">
            <div class="users-bar">
                <h2 class="section-title">Users</h2>
                <button type="button" class="btn btn--inline" id="add-btn">Add user</button>
            </div>

            <div class="table-container">
                <table class="students-table users-table">
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="users-table-body">
                    <tr><td colspan="5">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<dialog id="user-dialog" class="user-dialog">
    <form id="user-form" autocomplete="off">
        <h3 class="section-title" id="dialog-title">Add user</h3>
        <input type="hidden" id="user-id" value="0">

        <label class="form-row">Username
            <input type="text" class="field-input" id="user-username" required maxlength="50">
        </label>
        <label class="form-row">Full name
            <input type="text" class="field-input" id="user-fullname" maxlength="150">
        </label>
        <label class="form-row">Password <small id="pw-hint"></small>
            <input type="password" class="field-input" id="user-password" autocomplete="new-password">
        </label>
        <label class="form-check">
            <input type="checkbox" id="user-superadmin"> Superadmin
        </label>

        <p class="form-error" id="form-error"></p>

        <div class="dialog-actions">
            <button type="button" class="btn-toggle btn-toggle--undo" id="cancel-btn">Cancel</button>
            <button type="submit" class="btn btn--inline">Save</button>
        </div>
    </form>
</dialog>

<script>window.CSRF_TOKEN = "<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>";</script>
<script src="assets/js/users.js"></script>
</body>
</html>