document.addEventListener('DOMContentLoaded', () => {
    // ---------- config ----------
    const CURRENCY = 'Rs. ';          // shown before cash prizes, set to '' to hide
    const DEFAULT_SPEED = 1.0;        // start speed (degrees per ms) when spinning with the button or hub
    const MIN_FLICK = 0.25;           // slowest flick (deg/ms) that still counts as a spin
    const MAX_SPEED = 2.2;            // fastest start speed the wheel will use
    const MIN_SPIN_MS = 4500;         // the wheel always takes at least this long to stop
    const MAX_SPIN_MS = 8000;         // ...and never longer than this
    const AUTO_RESET_MS = 30000;      // result screen returns to start after this idle time (0 = off)

    // Wheel layout. "name" must match prizes.name in the database exactly.
    // Cash prizes are brass, merchandise is teal, "Try Again" is slate.
    const SEGMENTS = [
        '5000', 'Metro Key Tags', '10000', 'Try Again', '15000',
        'Metro T Shirt', '20000', 'Metro Note Books', '25000', 'Try Again',
    ];

    // ---------- elements ----------
    const $ = (id) => document.getElementById(id);
    const lookupForm = $('lookup-form');
    const regInput = $('reg-number');
    const lookupBtn = $('lookup-btn');
    const messageBox = $('message');
    const studentCard = $('student-card');
    const spinBtn = $('spin-btn');
    const hub = $('hub');
    const wheel = $('wheel');
    const wrap = document.querySelector('.wheel-wrap');
    const result = $('result');
    const resultCard = $('r-card');
    const nextBtn = $('next-btn');

    let currentReg = null;
    let rotation = 0;
    let busy = false;
    let resetTimer = null;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ---------- helpers ----------
    const isCash = (name) => /^\d+$/.test(name);
    const prizeLabel = (name) => (isCash(name) ? CURRENCY + Number(name).toLocaleString('en-US') : name);

    // One colour per slice, in wheel order (repeats if you add more than 10 slices)
    const PALETTE = [
        { bg: '#203D79', fg: '#ffffff' }, // navy
        { bg: '#C2255C', fg: '#ffffff' }, // magenta
        { bg: '#E8A317', fg: '#1a1a1a' }, // amber
        { bg: '#1F8A70', fg: '#ffffff' }, // green
        { bg: '#7B3FA0', fg: '#ffffff' }, // purple
        { bg: '#E8590C', fg: '#ffffff' }, // orange
        { bg: '#1C7DB5', fg: '#ffffff' }, // blue
        { bg: '#B6213A', fg: '#ffffff' }, // crimson
        { bg: '#4C8A1A', fg: '#ffffff' }, // lime green
        { bg: '#0B7285', fg: '#ffffff' }, // teal
    ];
    const segmentColors = (i) => PALETTE[i % PALETTE.length];

    // Spin button and the "Spin" hub switch on and off together
    function setSpinEnabled(on) {
        spinBtn.disabled = !on;
        hub.classList.toggle('is-ready', on);
        wrap.classList.toggle('is-ready', on);
    }

    function showMessage(text, type = 'error') {
        messageBox.textContent = text;
        messageBox.className = `message message--${type}`;
        messageBox.hidden = false;
    }
    const hideMessage = () => { messageBox.hidden = true; };

    async function post(url, reg) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({registration_number: reg}),
        });
        return res.json();
    }

    // ---------- wheel drawing ----------
    function drawWheel() {
        const ctx = wheel.getContext('2d');
        const size = wheel.width;
        const c = size / 2;
        const rim = 36;
        const r = c - rim;
        const seg = (Math.PI * 2) / SEGMENTS.length;
        const labelRight = r - 36;
        const maxLabelW = labelRight - size * 0.1;

        ctx.clearRect(0, 0, size, size);

        // rim: navy ring with the same dotted line as the seal
        ctx.beginPath();
        ctx.arc(c, c, c - 2, 0, Math.PI * 2);
        ctx.fillStyle = '#203D79';
        ctx.fill();

        ctx.beginPath();
        ctx.arc(c, c, c - rim / 2, 0, Math.PI * 2);
        ctx.setLineDash([2, 17]);
        ctx.lineCap = 'round';
        ctx.lineWidth = 6;
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();
        ctx.setLineDash([]);

        SEGMENTS.forEach((name, i) => {
            const a0 = -Math.PI / 2 + i * seg;
            const a1 = a0 + seg;
            const {bg, fg} = segmentColors(i);

            ctx.beginPath();
            ctx.moveTo(c, c);
            ctx.arc(c, c, r, a0, a1);
            ctx.closePath();
            ctx.fillStyle = bg;
            ctx.fill();
            ctx.lineWidth = 4;
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#ffffff';
            ctx.stroke();

            ctx.save();
            ctx.translate(c, c);
            ctx.rotate(a0 + seg / 2);
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = fg;

            // shrink long labels until they fit inside the segment
            const label = prizeLabel(name);
            let px = isCash(name) ? 68 : 52;
            do {
                ctx.font = `700 ${px}px "Cormorant Garamond", Georgia, serif`;
                px -= 2;
            } while (ctx.measureText(label).width > maxLabelW && px > 24);

            ctx.fillText(label, labelRight, 2);
            ctx.restore();
        });
    }

    // ---------- wheel motion ----------
    const mod360 = (n) => ((n % 360) + 360) % 360;
    const clamp = (n, lo, hi) => Math.min(hi, Math.max(lo, n));

    function setRotation(deg) {
        rotation = deg;
        wheel.style.transform = `rotate(${deg}deg)`;
    }

    // Free spin: keeps the wheel turning at the flick speed while we wait for the server
    let free = null;
    function freeSpinStart(dir, speed) {
        free = { dir, speed, last: performance.now(), raf: 0 };
        const tick = (now) => {
            if (!free) return;
            setRotation(rotation + free.dir * free.speed * (now - free.last));
            free.last = now;
            free.raf = requestAnimationFrame(tick);
        };
        free.raf = requestAnimationFrame(tick);
    }
    function freeSpinStop() {
        if (!free) return null;
        cancelAnimationFrame(free.raf);
        const state = free;
        free = null;
        return state;
    }

    // Slows the wheel from its current speed and stops it on the prize the server chose
    function landOn(prizeName, dir, speed) {
        const segDeg = 360 / SEGMENTS.length;

        // pick one of the slices that carry this prize
        const matches = SEGMENTS.map((n, i) => (n === prizeName ? i : -1)).filter((i) => i >= 0);
        const idx = matches.length ? matches[Math.floor(Math.random() * matches.length)] : 0;

        // stop somewhere inside the slice, not always dead centre
        const jitter = (Math.random() - 0.5) * segDeg * 0.6;
        const targetMod = mod360(360 - ((idx + 0.5) * segDeg + jitter));
        const curMod = mod360(rotation);

        // distance to the target in the direction of travel
        const base = dir > 0 ? mod360(targetMod - curMod) : mod360(curMod - targetMod);

        let turns = 0;
        let minMs = MIN_SPIN_MS;
        let maxMs = MAX_SPIN_MS;
        if (reduceMotion) {
            minMs = maxMs = 600;
        } else {
            // enough full turns that an ease-out from this speed lasts at least MIN_SPIN_MS
            turns = Math.max(2, Math.ceil((speed * minMs / 3 - base) / 360));
        }

        const delta = base + 360 * turns;
        const duration = clamp((3 * delta) / speed, minMs, maxMs);
        const start = rotation;
        const t0 = performance.now();

        return new Promise((resolve) => {
            const step = (now) => {
                const s = Math.min(1, (now - t0) / duration);
                const eased = 1 - Math.pow(1 - s, 3); // cubic ease-out: starts at the flick speed, ends at 0
                setRotation(start + dir * delta * eased);
                if (s < 1) requestAnimationFrame(step);
                else resolve();
            };
            requestAnimationFrame(step);
        });
    }

    function snapWheelHome() {
        freeSpinStop();
        setRotation(mod360(rotation));
    }

    // ---------- screens ----------
    function resetAll() {
        clearTimeout(resetTimer);
        result.hidden = true;
        studentCard.hidden = true;
        hideMessage();
        setSpinEnabled(false);
        lookupBtn.disabled = false;
        regInput.value = '';
        currentReg = null;
        busy = false;
        drag = null;
        snapWheelHome();
        regInput.focus();
    }

    function showResult({fullName, prize, already}) {
        const miss = prize === 'Try Again';
        resultCard.classList.toggle('is-miss', miss);
        $('r-who').textContent = fullName || '';

        if (already) {
            $('r-title').textContent = miss ? 'No prize' : prizeLabel(prize);
            $('r-note').textContent = 'You have already spun the wheel. This was your result.';
        } else if (miss) {
            $('r-title').textContent = 'Not this time';
            $('r-note').textContent = 'No prize on this spin. Thank you for taking part.';
        } else {
            $('r-title').textContent = prizeLabel(prize);
            $('r-note').textContent = 'Congratulations, you won a prize.';
        }

        result.hidden = false;
        nextBtn.focus();
        if (AUTO_RESET_MS > 0) resetTimer = setTimeout(resetAll, AUTO_RESET_MS);
    }

    // ---------- 1. look up the student ----------
    lookupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (busy) return;

        hideMessage();
        studentCard.hidden = true;
        setSpinEnabled(false);
        currentReg = null;

        const reg = regInput.value.trim();
        if (!reg) {
            showMessage('Enter your registration number to continue.');
            return;
        }

        lookupBtn.disabled = true;
        lookupBtn.textContent = 'Searching…';

        try {
            const found = await post('api/search_student.php', reg);
            if (!found.success) {
                showMessage(found.message || 'No record found for that registration number.');
                return;
            }

            const student = found.data;

            // Only students in the registration table may spin
            const check = await post('api/check_registration.php', student.registration_number);
            if (!check.success) {
                showMessage(check.message || 'Could not verify registration. Try again.');
                return;
            }
            if (!check.reg_timestamp) {
                showMessage('You have not registered yet. Please register at the registration desk first.');
                return;
            }

            currentReg = student.registration_number;
            $('s-name').textContent = student.full_name;
            $('s-reg').textContent = student.registration_number;
            $('s-program').textContent = student.program || '—';
            $('s-seat').textContent = student.seatNo || '—';

            studentCard.hidden = false;
            setSpinEnabled(true);
            spinBtn.focus();
        } catch (err) {
            console.error(err);
            showMessage('Could not reach the server. Check the connection and try again.');
        } finally {
            lookupBtn.disabled = false;
            lookupBtn.textContent = 'Find My Record';
        }
    });

    // ---------- 2. spin ----------
    // dir: +1 clockwise, -1 anticlockwise. speed: degrees per ms at the start.
    async function startSpin(dir = 1, speed = DEFAULT_SPEED) {
        if (!currentReg || busy) return;

        busy = true;
        setSpinEnabled(false);
        lookupBtn.disabled = true;
        hideMessage();
        freeSpinStart(dir, speed); // wheel is already turning while the server picks the prize

        try {
            // The server picks the prize; the wheel only animates to it.
            const res = await post('api/spin.php', currentReg);
            freeSpinStop();

            if (!res.success && res.already_spun) {
                showResult({fullName: $('s-name').textContent, prize: res.prize, already: true});
                return;
            }
            if (!res.success) {
                showMessage(res.message || 'Could not spin. Try again.');
                setSpinEnabled(true);
                lookupBtn.disabled = false;
                busy = false;
                return;
            }

            await landOn(res.prize, dir, speed);
            setTimeout(() => showResult({fullName: res.full_name, prize: res.prize, already: false}), 250);
        } catch (err) {
            freeSpinStop();
            console.error(err);
            showMessage('Could not reach the server. Try again.');
            setSpinEnabled(true);
            lookupBtn.disabled = false;
            busy = false;
        }
    }

    spinBtn.addEventListener('click', () => startSpin(1, DEFAULT_SPEED));

    // ---------- 3. touch / mouse / pen: drag and flick the wheel ----------
    let drag = null;

    function pointerAngle(e) {
        const r = wrap.getBoundingClientRect();
        return Math.atan2(e.clientY - (r.top + r.height / 2), e.clientX - (r.left + r.width / 2)) * 180 / Math.PI;
    }

    wrap.addEventListener('pointerdown', (e) => {
        if (busy || drag || !result.hidden) return;
        if (!currentReg) {
            showMessage('Find your record first, then spin the wheel.', 'info');
            return;
        }
        wrap.setPointerCapture(e.pointerId);
        drag = {
            id: e.pointerId,
            lastAngle: pointerAngle(e),
            total: 0,
            moved: false,
            onHub: !!e.target.closest('#hub'),
            x: e.clientX,
            y: e.clientY,
            samples: [{t: e.timeStamp, a: 0}],
        };
    });

    wrap.addEventListener('pointermove', (e) => {
        if (!drag || e.pointerId !== drag.id) return;

        const angle = pointerAngle(e);
        let delta = angle - drag.lastAngle;
        if (delta > 180) delta -= 360;
        if (delta < -180) delta += 360;
        drag.lastAngle = angle;
        drag.total += delta;

        if (!drag.moved && Math.hypot(e.clientX - drag.x, e.clientY - drag.y) > 10) drag.moved = true;
        if (drag.moved) setRotation(rotation + delta); // the wheel follows the finger

        drag.samples.push({t: e.timeStamp, a: drag.total});
        while (drag.samples.length > 2 && e.timeStamp - drag.samples[0].t > 120) drag.samples.shift();
    });

    function endDrag(e) {
        if (!drag || e.pointerId !== drag.id) return;
        const d = drag;
        drag = null;

        // a tap on the "Spin" hub spins like the button
        if (!d.moved) {
            if (d.onHub && e.type === 'pointerup') startSpin(1, DEFAULT_SPEED);
            return;
        }
        if (e.type === 'pointercancel') return;

        // flick speed over the last ~120 ms; a finger held still before lifting counts as no flick
        const first = d.samples[0];
        const last = d.samples[d.samples.length - 1];
        const dt = last.t - first.t;
        let v = dt > 0 ? (last.a - first.a) / dt : 0;
        if (e.timeStamp - last.t > 100) v = 0;

        if (Math.abs(v) >= MIN_FLICK) startSpin(Math.sign(v), Math.min(Math.abs(v), MAX_SPEED));
    }
    wrap.addEventListener('pointerup', endDrag);
    wrap.addEventListener('pointercancel', endDrag);

    nextBtn.addEventListener('click', resetAll);

    // ---------- init ----------
    drawWheel();
    // redraw once the web fonts are ready so the labels use Cormorant Garamond
    if (document.fonts && document.fonts.load) {
        document.fonts.load('700 40px "Cormorant Garamond"').then(drawWheel).catch(() => {});
    }
    regInput.focus();
});