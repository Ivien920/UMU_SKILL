<?php
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
require 'connection.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = $user['email'];
    $_SESSION['name']    = $user['name'];
    header('Location: dashboard.php');
    exit;
} else {
    $error = 'Invalid email or password. Please try again.';
}
    // --- Replace this block with your real DB lookup ---

    // --- End block ---
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign In — UMU SKILLS MARKET PLACE </title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --ink:      #0e0e0e;
      --paper:    #f5f2ec;
      --accent:   #c8522a;
      --muted:    #8a8070;
      --border:   #d9d4cb;
      --surface:  #ffffff;
      --error:    #b8332a;
    }

    html, body {
      height: 100%;
      background: var(--paper);
      font-family: 'DM Sans', sans-serif;
      color: var(--ink);
    }

    body {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 100vh;
    }

    /* ── Left panel ── */
    .panel-left {
      position: relative;
      background: var(--ink);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 3rem;
      overflow: hidden;
    }

    .panel-left::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 60% 50% at 20% 80%, rgba(200,82,42,.35) 0%, transparent 70%),
        radial-gradient(ellipse 40% 40% at 80% 20%, rgba(200,82,42,.15) 0%, transparent 60%);
    }

    .brand {
      position: relative;
      font-family: 'DM Serif Display', serif;
      font-size: 1.6rem;
      color: var(--paper);
      letter-spacing: -.02em;
    }

    .brand span { color: var(--accent); }

    .hero-text {
      position: relative;
      z-index: 1;
    }

    .hero-text h1 {
      font-family: 'DM Serif Display', serif;
      font-size: clamp(2.4rem, 4vw, 3.6rem);
      color: var(--paper);
      line-height: 1.1;
      margin-bottom: 1.2rem;
    }

    .hero-text h1 em {
      font-style: italic;
      color: var(--accent);
    }

    .hero-text p {
      font-size: .95rem;
      color: var(--muted);
      line-height: 1.65;
      max-width: 30ch;
      font-weight: 300;
    }

    .decorative-ring {
      position: absolute;
      width: 420px;
      height: 420px;
      border-radius: 50%;
      border: 1px solid rgba(200,82,42,.2);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      animation: spin 30s linear infinite;
    }

    .decorative-ring::after {
      content: '';
      position: absolute;
      width: 260px;
      height: 260px;
      border-radius: 50%;
      border: 1px solid rgba(200,82,42,.12);
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    @keyframes spin { to { transform: translate(-50%,-50%) rotate(360deg); } }

    .left-footer {
      position: relative;
      font-size: .78rem;
      color: var(--muted);
      letter-spacing: .04em;
    }

    /* ── Right panel ── */
    .panel-right {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      background: var(--paper);
    }

    .form-card {
      width: 100%;
      max-width: 420px;
      animation: fadeUp .5s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: none; }
    }

    .form-header {
      margin-bottom: 2.5rem;
    }

    .form-header h2 {
      font-family: 'DM Serif Display', serif;
      font-size: 2rem;
      letter-spacing: -.03em;
      color: var(--ink);
      margin-bottom: .4rem;
    }

    .form-header p {
      font-size: .88rem;
      color: var(--muted);
    }

    .form-header p a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }

    .form-header p a:hover { text-decoration: underline; }
.form-header{
  min-height: 120px;
}
    /* error banner */
    .error-banner {
      background: #fdf0ef;
      border: 1px solid #f2c4c0;
      color: var(--error);
      border-radius: 8px;
      padding: .75rem 1rem;
      font-size: .85rem;
      margin-bottom: 1.5rem;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    /* field */
    .field { margin-bottom: 1.25rem; }

    .field label {
      display: block;
      font-size: .78rem;
      font-weight: 500;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: .5rem;
    }

    .input-wrap { position: relative; }

    .input-wrap svg {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      pointer-events: none;
    }

    .field input {
      width: 100%;
      padding: .85rem 1rem .85rem 2.8rem;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: .95rem;
      background: var(--surface);
      color: var(--ink);
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }

    .field input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(200,82,42,.12);
    }

    .field-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: .5rem;
    }

    .field-row label {
      margin-bottom: 0;
    }

    .forgot {
      font-size: .78rem;
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
    }

    .forgot:hover { text-decoration: underline; }

    /* remember */
    .remember {
      display: flex;
      align-items: center;
      gap: .6rem;
      margin-bottom: 1.75rem;
      font-size: .85rem;
      color: var(--muted);
      cursor: pointer;
    }

    .remember input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--accent);
      cursor: pointer;
    }

    /* submit */
    .btn-submit {
      width: 100%;
      padding: .95rem;
      background: var(--ink);
      color: var(--paper);
      border: none;
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: .95rem;
      font-weight: 500;
      letter-spacing: .02em;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: background .2s, transform .1s;
    }

    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(255,255,255,.07);
      opacity: 0;
      transition: opacity .2s;
    }

    .btn-submit:hover { background: #1e1e1e; }
    .btn-submit:hover::after { opacity: 1; }
    .btn-submit:active { transform: scale(.99); }

    .divider {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1.5rem 0;
      color: var(--border);
      font-size: .78rem;
      color: var(--muted);
    }

    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

.main-logo {
height: 50px;
width:auto;
display:block;
margin:o auto;

}
    .btn-oauth {
      width: 100%;
      padding: .85rem;
      background: var(--surface);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: .9rem;
      color: var(--ink);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .6rem;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      text-decoration: none;
    }

    .btn-oauth:hover { background: #f9f7f3; border-color: var(--muted); }

    /* responsive */
    @media (max-width: 700px) {
      body { grid-template-columns: 1fr; }
      .panel-left { display: none; }
    }
  </style>
</head>
<body>
  
<!-- Left decorative panel -->
<div class="panel-left">
  <div class="brand">UMU SKILLS MARKETPLACE<span>.</span></div>
  
  <div class="decorative-ring"></div>
  <div class="hero-text">
    <h1>Welcome<br><em>back.</em></h1>
    <p>Sign in to access your workspace and pick up right where you left off.</p>
  </div>
  <div class="left-footer">© <?= date('Y') ?> Techsphere systems Inc. All rights reserved.</div>
</div>

<!-- Right form panel -->
<div class="panel-right">
  <div class="form-card">
    <div class="form-header">
      <h2>Sign in</h2>
      <p>Don't have an account? <a href="register.php">Create one</a></p>
    </div>

    <?php if ($error): ?>
    <div class="error-banner">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="field">
        <label>Email address</label>
        <div class="input-wrap">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
          <input
            type="email"
            name="email"
            placeholder="you@stud.umu.ac.ug"
            value=""
            required
            autocomplete="email"
          />
        </div>
      </div>

      <div class="field">
        <div class="field-row">
          <label>Password</label>
          <a href="forgot-password.php" class="forgot">Forgot password?</a>
        </div>
        <div class="input-wrap">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input
            type="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
          />
        </div>
      </div>

      <label class="remember">
        <input type="checkbox" name="remember" value="1"> Keep me signed in
      </label>

      <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <div class="divider">or continue with</div>

    <a href="#" class="btn-oauth">
      <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      Sign in with Google
    </a>
  </div>
</div>

</body>
</html>