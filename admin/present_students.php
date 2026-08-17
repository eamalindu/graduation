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
    <link rel="stylesheet" href="https://cdn.datatables.net/3.0.0/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">

    <link rel="icon" type="image/ico" href="../favicon.ico"/>
</head>
<body>
<div class="admin-page">

    <header class="admin-topbar">
        <div class="admin-topbar__brand"><a href="dashboard.php">
                <span>Admin &middot; Attendance</span></a>
        </div>
        <div class="admin-topbar__user">
            <span><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES) ?></span>
            <a href="logout.php" class="admin-topbar__logout">Log out</a>
        </div>
    </header>

    <main class="admin-main" style="max-width: 90%;">

        <section class="panel-section">
            <h2 class="section-title" style="margin-bottom: 40px">Present Student Data</h2>
            <div class="table-container">
                <table class="students-table" id="students-table">
                    <thead>
                    <tr>
                        <th>Registration Number</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Faculty</th>
                        <th>Batch</th>
                        <th>Attendance Status</th>
                        <th>Attendance Time</th>
                        <th>Marked By</th>
                    </tr>
                    </thead>
                    <tbody id="students-table-body">
                    <!-- Student rows will be populated here by JavaScript -->
                    </tbody>
                </table>
        </section>

    </main>
</div>
<script>window.CSRF_TOKEN = "<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>";</script>
<script src="https://cdn.datatables.net/3.0.0/js/dataTables.js"></script>
<script src="assets/js/present_students.js"></script>
</body>
</html>
