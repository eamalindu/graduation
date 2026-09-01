<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdminLogin();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#7A1F2B">
    <meta name="robots" content="noindex, nofollow">
    <title>Charts | Graduation Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="icon" type="image/ico" href="../favicon.ico"/>
    <style>
        .highcharts-root{
            font-family: inherit!important;
        }
        .row{
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="admin-page">

    <header class="admin-topbar">
        <div class="admin-topbar__brand">
            <span>Admin &middot; Attendance</span>
        </div>
        <nav class="admin-topbar__nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="import.php" >Import Students</a>
            <a href="charts.php" class="is-active">Charts</a>
        </nav>
        <div class="admin-topbar__user">
            <span><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES) ?></span>
            <a href="logout.php" class="admin-topbar__logout">Log out</a>
        </div>
    </header>

    <div class="row">
    <div id="programs-pie-chart" style="width: 50%"></div>
    <div id="" style="width: 50%">
        <table class="students-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Target</th>
                <th>Registered</th>
                <th>Pending</th>
                <th>%</th>
            </tr>
            </thead>
            <tbody id="programs-table-body">
            </tbody>
        </table>
    </div>

    </div>



    <script src="highcharts-11.4.3/highcharts.js"></script>
    <script src="assets/js/charts.js"></script>
</div>
</body>
</html>