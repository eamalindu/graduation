<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#211C16">
    <meta name="robots" content="noindex, nofollow">
    <title>Graduation Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/ico" href="favicon.ico"/>
</head>
<body>
<div class="page">

    <header class="header">
        <div class="seal seal--header" aria-hidden="true"><a href="">
                <img src="images/MC.png" alt="Metropolitan College Seal" class="seal__img" width="100" height="100"></a>
        </div>
        <p class="header__eyebrow">Convocation &middot; Attendance</p>
        <h1 class="header__title">Metropolitan College</h1>
        <p class="header__subtitle">Confirm your attendance below</p>
    </header>

    <main class="panel">
        <div class="panel__inner">

            <form id="search-form" class="search-form" autocomplete="off">
                <label for="reg-number" class="field-label">Registration Number</label>
                <input
                        type="number"
                        id="reg-number"
                        name="registration_number"
                        class="field-input"
                        placeholder="2610454"
                        autocapitalize="characters"
                        autocomplete="off"
                        required
                        inputmode="numeric"
                        pattern="[0-9]*"
                >
                <button type="submit" id="search-btn" class="btn btn--primary">Find My Record</button>
            </form>

            <div id="message" class="message" hidden aria-live="polite"></div>

            <div id="student-details" class="record" hidden>
                <div class="record__row">
                    <span class="record__label">Reg. Number</span>
                    <span class="record__value record__value--mono" id="detail-reg"></span>
                </div>
                <div class="record__row">
                    <span class="record__label">Name</span>
                    <span class="record__value" id="detail-name"></span>
                </div>
                <div class="record__row">
                    <span class="record__label">Program</span>
                    <span class="record__value" id="detail-course"></span>
                </div>
                <div class="record__row">
                    <span class="record__label">email</span>
                    <span class="record__value" id="detail-faculty"></span>
                </div>
                <div class="record__row">
                    <span class="record__label">Registered Date</span>
                    <span class="record__value" id="detail-batch"></span>
                </div>

                <button type="button" id="confirm-btn" class="btn btn--confirm" disabled>Confirm Attendance</button>
            </div>

            <div id="success-state" class="success" hidden aria-live="polite">
                <div class="seal seal--stamp" aria-hidden="true">
                    <img src="images/Success.gif" alt="Metropolitan College Seal" class="seal__img"
                         style="margin: 0 auto; display: block" width="100"
                         height="100">
                </div>
                <h2 class="success__title">Attendance Recorded</h2>
                <p class="success__name" id="success-name"></p>
                <p class="success__time" id="success-time"></p>
                <button type="button" id="reset-btn" class="btn btn--ghost">Look Up Another Record</button>
            </div>

        </div>
    </main>

    <footer class="footer">
        <p>Powered by <a href="https://pixelbros.online/" target="_blank">Pixelbros</a></p>
    </footer>

</div>

<script src="assets/js/student.js"></script>
</body>
</html>
