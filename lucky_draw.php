<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#211C16">
    <meta name="robots" content="noindex, nofollow">
    <title>Graduation Lucky Draw</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/ico" href="favicon.ico"/>

    <style>
        html, body { height: 100%; margin: 0; }

        /* ---- same split layout as the attendance page ---- */
        .split-page { display: flex; min-height: 100vh; }

        .split-image {
            flex: 1 1 50%;
            background: url("images/backgrounds.jpg") center center / cover no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .split-image::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.45) 100%);
        }
        .split-image .split-caption {
            position: absolute;
            left: 40px;
            bottom: 40px;
            right: 40px;
            color: #fff;
            z-index: 1;
        }
        .split-image .split-caption h4 { font-weight: 600; margin-bottom: 4px; }
        .split-image .split-caption p { font-size: 13px !important; opacity: 0.85; margin: 0; }

        .split-form {
            flex: 1 1 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            padding: 2rem;
        }
        .split-form-inner { width: 100%; max-width: 440px; }

        /* ---- wheel ---- */
        .wheel-wrap {
            position: relative;
            z-index: 1;
            width: min(74vh, 44vw);
            aspect-ratio: 1;
            filter: drop-shadow(0 10px 24px rgba(0, 0, 0, 0.45));
        }
        .wheel-wrap {
            touch-action: none;            /* the wheel gets the touch, the page doesn't scroll */
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
            border-radius: 50%;            /* touches outside the circle are ignored */
        }
        .wheel-wrap.is-ready { cursor: grab; }
        #wheel { display: block; width: 100%; height: 100%; border-radius: 50%; will-change: transform; pointer-events: none; }
        .pointer {
            position: absolute;
            top: -1.5%;
            left: 50%;
            transform: translateX(-50%);
            width: 0; height: 0;
            border-left: 2.4vmin solid transparent;
            border-right: 2.4vmin solid transparent;
            border-top: 5vmin solid var(--red-text);
            z-index: 2;
        }
        .hub {
            position: absolute;
            top: 50%; left: 50%;
            width: 16%;
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 5px var(--white), 0 0 0 7px var(--accent);
            z-index: 1;
            display: grid;
            place-items: center;
            color: var(--white);
            font-family: var(--font-display);
            font-weight: 700;
            font-size: min(2.8vmin, 30px);
            user-select: none;
            -webkit-user-select: none;
            opacity: 0.85;
        }
        .hub.is-ready {
            cursor: pointer;
            opacity: 1;
            animation: hubPulse 1.6s ease-in-out infinite;
        }
        @keyframes hubPulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.06); }
        }
        .hub::after {
            content: '';
            position: absolute;
            inset: 14%;
            border-radius: 50%;
            border: 2.5px dashed var(--white);
        }

        /* ---- student card + spin button ---- */
        .record__name {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 26px;
            line-height: 1.15;
            margin: 0 0 6px;
        }
        .spin-hint { margin: 14px 0 0; text-align: center; color: #6b7280; font-size: 12px; font-weight: bold; }

        /* ---- result popup ---- */
        .result {
            position: fixed;
            inset: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: rgba(23, 44, 86, 0.72);
            z-index: 10;
        }
        .result .panel { width: min(34rem, 100%); text-align: center; animation: stampIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .result .panel__inner { padding: 44px 30px 34px; }
        .result__who { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: var(--ink-muted); }
        .result__title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: clamp(44px, 7vw, 64px);
            line-height: 1.05;
            margin: 0 0 10px;
            color: var(--accent);
        }
        .is-miss .result__title { color: var(--ink); }
        .result__note { margin: 0 0 12px; font-size: 15px; color: var(--ink-muted); }

        /* ---- small screens: wheel on top, form below ---- */
        @media (max-width: 767.98px) {
            .split-page { flex-direction: column; }
            .split-image { flex: 0 0 auto; padding: 32px 16px; }
            .split-image .split-caption { display: none; }
            .wheel-wrap { width: min(86vw, 30rem); }
            .split-form { padding: 1.5rem; }
        }
    </style>
</head>

<body>
<div class="split-page">
    <div class="split-image">
        <div class="wheel-wrap" aria-label="Prize wheel">
            <div class="pointer"></div>
            <canvas id="wheel" width="1000" height="1000"></canvas>
            <div class="hub" id="hub"><span>Spin</span></div>
        </div>

        <div class="split-caption">
            <h4>Metropolitan College</h4>
            <p>Graduation Management System</p>
        </div>
    </div>

    <div class="split-form">
        <div class="split-form-inner">
            <header class="header">
                <div class="seal seal--header" aria-hidden="true">
                    <a href>
                    <img src="images/logo.png" alt="Metropolitan College Seal" class="seal__img" style="width: 100%">
                    </a>
                </div>
                <p class="header__eyebrow">Graduation &middot; Lucky Draw</p>
                <h1 class="header__title">Lucky Draw</h1>
                <p class="header__subtitle">Enter your registration number, then spin the wheel.</p>
            </header>

            <main class="panel">
                <div class="panel__inner">

                    <form id="lookup-form" class="search-form" autocomplete="off">
                        <label for="reg-number" class="field-label">Registration Number</label>
                        <input
                            type="text"
                            id="reg-number"
                            name="registration_number"
                            class="field-input"
                            placeholder="2610454"
                            autocapitalize="characters"
                            autocomplete="off"
                            required
                        >
                        <button type="submit" id="lookup-btn" class="btn btn--primary">Find My Record</button>
                    </form>

                    <div id="message" class="message" hidden aria-live="polite"></div>

                    <div id="student-card" class="record" hidden>
                        <p class="record__name" style="text-align: center" id="s-name"></p>
                        <div class="record__row">
                            <span class="record__label">Reg. Number</span>
                            <span class="record__value" id="s-reg"></span>
                        </div>
                        <div class="record__row">
                            <span class="record__label">Program</span>
                            <span class="record__value" id="s-program"></span>
                        </div>
                        <div class="record__row">
                            <span class="record__label">Seat Number</span>
                            <span class="record__value" id="s-seat"></span>
                        </div>
                        <p class="spin-hint">Flick the wheel to spin it, or press the button.</p>
                        <button type="button" id="spin-btn" class="btn btn--confirm btn--spin" disabled>Spin the Wheel</button>
                    </div>

                </div>
            </main>
        </div>
    </div>
</div>

<div id="result" class="result" role="dialog" aria-modal="true" aria-labelledby="r-title" hidden>
    <div class="panel" id="r-card">
        <div class="panel__inner">
            <p class="result__who" id="r-who"></p>
            <h2 class="result__title" id="r-title"></h2>
            <p class="result__note" id="r-note"></p>
            <button type="button" id="next-btn" class="btn btn--primary">Look Up Another Record</button>
        </div>
    </div>
</div>

<script src="assets/js/lucky_draw.js"></script>
</body>
</html>