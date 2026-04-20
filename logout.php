<?php
// logout.php - Handles session destruction and displays logout screen

session_start();
require_once 'connection.php';
$username = $_SESSION['name'] ?? 'User'; // Capture before destroying

// Destroy the session
$_SESSION = [];
session_destroy();

// Optional: clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Jost:wght@200;300;400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --cream: #c9c5beea;
            --ink: #1a1612;
            --gold: #b8924a;
            --gold-light: #d4aa6a;
            --muted: #8a7f72;
            --line: rgba(92, 62, 11, 0.88);
        }

        html, body {
            height: 100%;
        }

        body {
            background-color: var(--cream);
            color: var(--ink);
            font-family: 'Jost', sans-serif;
            font-weight: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Background grain texture */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }

        /* Decorative corner lines */
        .corner {
            position: fixed;
            width: 80px;
            height: 80px;
            opacity: 0.35;
        }
        .corner--tl { top: 32px; left: 32px; border-top: 1px solid var(--gold); border-left: 1px solid var(--gold); }
        .corner--tr { top: 32px; right: 32px; border-top: 1px solid var(--gold); border-right: 1px solid var(--gold); }
        .corner--bl { bottom: 32px; left: 32px; border-bottom: 1px solid var(--gold); border-left: 1px solid var(--gold); }
        .corner--br { bottom: 32px; right: 32px; border-bottom: 1px solid var(--gold); border-right: 1px solid var(--gold); }

        /* Card */
        .card {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 64px 72px;
            max-width: 480px;
            width: 90%;
            animation: fadeUp 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Icon */
        .icon-wrap {
            width: 64px;
            height: 64px;
            margin: 0 auto 32px;
            position: relative;
            animation: fadeUp 0.9s 0.1s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .icon-wrap svg {
            width: 100%;
            height: 100%;
        }

        /* Divider ornament */
        .ornament {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0;
            animation: fadeUp 0.9s 0.25s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .ornament::before,
        .ornament::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--gold), transparent);
        }

        .ornament-dot {
            width: 5px;
            height: 5px;
            border: 1px solid var(--gold);
            transform: rotate(45deg);
            flex-shrink: 0;
        }

        /* Text */
        .eyebrow {
            font-family: 'Jost', sans-serif;
            font-weight: 200;
            font-size: 10px;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
            animation: fadeUp 0.9s 0.15s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(2.2rem, 5vw, 3rem);
            line-height: 1.15;
            letter-spacing: 0.01em;
            color: var(--ink);
            margin-bottom: 6px;
            animation: fadeUp 0.9s 0.2s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        h1 em {
            font-style: italic;
            color: var(--gold);
        }

        .subtitle {
            font-size: 13px;
            font-weight: 300;
            color: var(--muted);
            line-height: 1.7;
            letter-spacing: 0.02em;
            animation: fadeUp 0.9s 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        /* Countdown */
        .countdown-wrap {
            margin: 32px 0 8px;
            animation: fadeUp 0.9s 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .countdown-label {
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .progress-track {
            width: 100%;
            height: 1px;
            background: var(--line);
            position: relative;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--gold), var(--gold-light));
            width: 0%;
            animation: fillBar 300s linear forwards;
        }

        @keyframes fillBar {
            from { width: 0%; }
            to   { width: 100%; }
        }

        /* Button */
        .btn-wrap {
            margin-top: 40px;
            animation: fadeUp 0.9s 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .btn {
            display: inline-block;
            padding: 13px 40px;
            border: 1px solid var(--gold);
            color: var(--ink);
            text-decoration: none;
            font-family: 'Jost', sans-serif;
            font-size: 11px;
            font-weight: 300;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            background: transparent;
            cursor: pointer;
            transition: background 0.35s ease, color 0.35s ease, border-color 0.35s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gold);
            transform: translateX(-101%);
            transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 0;
        }

        .btn:hover::before {
            transform: translateX(0);
        }

        .btn span {
            position: relative;
            z-index: 1;
            transition: color 0.38s ease;
        }

        .btn:hover span {
            color: var(--cream);
        }

        /* Footer note */
        .footer-note {
            margin-top: 28px;
            font-size: 11px;
            color: var(--muted);
            letter-spacing: 0.04em;
            animation: fadeUp 0.9s 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .footer-note a {
            color: var(--gold);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s;
        }

        .footer-note a:hover {
            border-color: var(--gold);
        }
    </style>
</head>
<body>

    <div class="corner corner--tl"></div>
    <div class="corner corner--tr"></div>
    <div class="corner corner--bl"></div>
    <div class="corner corner--br"></div>

    <div class="card">

        <div class="icon-wrap">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Outer circle -->
                <circle cx="32" cy="32" r="30" stroke="#b8924a" stroke-width="0.75" stroke-dasharray="3 3" opacity="0.5"/>
                <!-- Lock body -->
                <rect x="20" y="30" width="24" height="18" rx="2" stroke="#b8924a" stroke-width="1.2" fill="none"/>
                <!-- Lock shackle (open) -->
                <path d="M24 30V24a8 8 0 0 1 14.5-4.6" stroke="#b8924a" stroke-width="1.2" stroke-linecap="round" fill="none"/>
                <!-- Keyhole -->
                <circle cx="32" cy="38" r="2.5" fill="#b8924a"/>
                <rect x="31" y="39.5" width="2" height="4" rx="1" fill="#b8924a"/>
            </svg>
        </div>

        <p class="eyebrow">Session Ended</p>

        <h1>Thank You, <em><?= htmlspecialchars($username) ?></em></h1>

        <div class="ornament">
            <div class="ornament-dot"></div>
        </div>

        <p class="subtitle">
            You have been securely signed out.<br>
            Your session has been cleared.
        </p>

        <div class="countdown-wrap">
            <p class="countdown-label">Redirecting in <span id="timer">5:00</span></p>
            <div class="progress-track">
                <div class="progress-fill"></div>
            </div>
        </div>

        <div class="btn-wrap">
            <a href="login.php" class="btn"><span>Sign In Again</span></a>
        </div>

        <p class="footer-note">
            Not your account? <a href="login.php?new=1">Use a different account</a>
        </p>

    </div>

    <script>
        // Countdown timer (5 minutes)
        let seconds = 10;
        const timerEl = document.getElementById('timer');

        function formatTime(s) {
            const m = Math.floor(s / 60);
            const sec = s % 60;
            return m + ':' + String(sec).padStart(2, '0');
        }

        const countdown = setInterval(() => {
            seconds--;
            timerEl.textContent = formatTime(seconds);
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>

</body>
</html>