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
        #programs-stat {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }

        .program-stat-card {
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px;
            min-height: 130px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .program-stat-card.grayscale {
            filter: grayscale(100%);
            opacity: 0.6;
        }

        .program-stat-card.selected {
            filter: grayscale(0%);
            opacity: 1;
            scale: 1.1;
            z-index: 2;
        }

        .program-stat-name {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
            color: #343434
        }

        .program-stat-count {
            font-size: 32px;
            font-weight: 700;

            margin-top: 15px;
        }
        .bg-rangamal{

            color: #f63b3b !important;
            border-color: #f63b3b;
        }

        .bg-rangamal.selected {
            background: rgb(246 59 59 / 0.1);
        }

        .bg-nethmini{
            border-color: #6366F1;
            color: #6366F1!important;
        }

        .bg-nethmini.selected {
            background: rgba(99, 102, 241, 0.1)
        }

        .bg-divani{
            border-color: #F59E0B;
            color: #F59E0B!important;
        }

        .bg-divani.selected {
            background: rgba(245, 158, 11, 0.1);

        }

        .bg-dilrukshi{
            border-color: #EC4899;
            color: #EC4899!important;
        }

        .bg-dilrukshi.selected {
            background: rgba(236, 72, 153, 0.1);
        }

        .bg-chathurya{
            border-color: #10B981;
            color: #10B981!important;
        }

        .bg-chathurya.selected {
            background: rgba(16, 185, 129, 0.1)
        }
        .program-stat-label {
            font-size: 12px;
            color: #6b7280;
        }
        /* width */
        ::-webkit-scrollbar {
            width: 6px;
        }

        /* Track */
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        /* Handle */
        ::-webkit-scrollbar-thumb {
            background: #888;
        }

        /* Handle on hover */
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
<div class="admin-page">

    <header class="admin-topbar">
        <div class="admin-topbar__brand">
            <a href="dashboard.php" > <span>Admin &middot; Attendance</span></a>
        </div>
        <nav class="admin-topbar__nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="import.php" >Import Students</a>

            <div class="dropdown">
                <button type="button" class="dropdown-toggle is-active" data-dropdown-toggle>Charts</button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="charts.php" >Registered</a></li>
                    <li><a class="dropdown-item is-active" href="approved.php">Approved</a></li>
                </ul>
            </div>
        </nav>
        <div class="admin-topbar__user">
            <span><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES) ?></span>
            <a href="logout.php" class="admin-topbar__logout">Log out</a>
        </div>
    </header>

    <div class="row">
        <div style="width: 40%;padding-left: 20px;height: calc( 100vh - 50px)">

            <div id="programs-pie-chart" style="margin-top: 15px;height: 350px"></div>
            <div id="" style="width: 100%;" >
                <h1 style="font-size: 1.2em;text-align: left;">Stats Table | Approved List</h1>
                <table class="students-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Approved</th>
                    </tr>
                    </thead>
                    <tbody id="programs-table-body">
                    </tbody>
                </table>
            </div>



        </div>
        <div id="programs-stat" style="width: 60%;padding: 20px;overflow: auto;height: calc( 100vh - 50px)"></div>
    </div>



    <script src="highcharts-11.4.3/highcharts.js"></script>
    <script src="assets/js/approved.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggles = document.querySelectorAll('[data-dropdown-toggle]');

            function closeAll() {
                document.querySelectorAll('.dropdown-menu.is-open').forEach((menu) => {
                    menu.classList.remove('is-open');
                    menu.previousElementSibling?.classList.remove('is-open');
                });
            }

            toggles.forEach((toggle) => {
                const menu = toggle.nextElementSibling;
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = menu.classList.contains('is-open');
                    closeAll();
                    if (!isOpen) {
                        menu.classList.add('is-open');
                        toggle.classList.add('is-open');
                    }
                });
            });

            document.addEventListener('click', closeAll);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeAll();
            });
        });
    </script>
</div>
</body>
</html>