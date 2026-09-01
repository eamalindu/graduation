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
    <style>
        table {
            width: 100% !important;
            max-width: 100%;
            table-layout: auto;
        }

        table th,
        table td {
            white-space: normal !important;
            overflow-wrap: break-word;
            word-break: normal;
        }
    </style>
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
            <h2 class="section-title" style="margin-bottom: 40px">Student All Data</h2>
            <div class="table-container w-100">
                <table class="students-table w-100" id="students-table">
                    <thead>
                    <tr>
                        <th>Registration Number</th>
                        <th>Full Name</th>
                        <th>email</th>
                        <th>Programme</th>
                        <th>Registered Date</th>
                        <th>Attendance Status</th>
                        <th>Attendance Time</th>
                        <th>Marked By</th>
                    </tr>
                    </thead>
                    <tbody id="students-table-body">
                    <!-- Student rows will be populated here by JavaScript -->
                    </tbody>
                </table>
                <button id="download-btn"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>&nbsp;Export Data to Excel</button>

        </section>

    </main>
</div>
<script>window.CSRF_TOKEN = "<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>";</script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.datatables.net/3.0.0/js/dataTables.js"></script>
<script src="assets/js/students.js"></script>
</body>
</html>
