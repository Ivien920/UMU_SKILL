<?php
session_start();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
    $terms    = $_POST['terms']         ?? '';

    // Validation
    if (strlen($name) < 2) {
        $errors['name'] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Include at least one uppercase letter and one number.';
    }
    if ($password !== $confirm) {
        $errors['confirm'] = 'Passwords do not match.';
    }
    if (!$terms) {
        $errors['terms'] = 'You must accept the terms to continue.';
    }

    if (empty($errors)) {
        require 'connection.php';

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes"/>
  <meta name="description" content="UMU Skills Marketplace Register"/>
  <title>Create Account — UMU SKILLS MARKETPLACE</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --ink:     #0e0e0e;
      --paper:   #f5f2ec;
      --accent:  #c8522a;
      --muted:   #8a8070;
      --border:  #d9d4cb;
      --surface: #ffffff;
      --ok:      #2a7a4b;
      --ok-bg:   #edf7f1;
      --ok-bd:   #b8dfc9;
    }

    html, body {
      min-height: 100%;
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
      background: white url('un.jpg') no-repeat center;
      background-size: 100% 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 3rem;
      overflow: hidden;
      min-height: 100vh;
      width: 100%;
    }

    .panel-left::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      background: rgba(0, 0, 0, 0.4);
      z-index: 1;
    }

    .brand {
      position: relative;
      font-family: 'DM Serif Display', serif;
      font-size: 1.6rem;
      color: #000000;
      letter-spacing: -.02em;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
      z-index: 2;
    }

    .brand span { 
      color: #ff0000;
      font-weight: 800;
    }

    .features {
      position: relative;
      z-index: 2;
    }

    .features h2 {
      font-family: 'DM Serif Display', serif;
      font-size: clamp(2rem, 3.5vw, 3.2rem);
      color: #000000;
      line-height: 1.1;
      margin-bottom: 2rem;
      font-weight: 800;
      text-shadow: 0 2px 6px rgba(255, 255, 255, 0.9);
    }

    .features h2 em { 
      font-style: italic; 
      color: #cc0000;
      font-weight: 900;
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .feature-list { list-style: none; }

    .feature-list li {
      display: flex;
      align-items: flex-start;
      gap: .8rem;
      margin-bottom: 1.1rem;
      color: #1a1a1a;
      font-size: .88rem;
      font-weight: 500;
      line-height: 1.5;
      text-shadow: 0 1px 2px rgba(255, 255, 255, 0.7);
    }

    .feature-list li .icon {
      flex-shrink: 0;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: rgba(255, 0, 0, 0.1);
      border: 1px solid rgba(255, 0, 0, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: .1rem;
    }

    .feature-list li .icon svg { color: #ff0000; }

    .decorative-dots {
      position: absolute;
      right: -40px;
      top: 50%;
      transform: translateY(-50%);
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 12px;
      opacity: .15;
      z-index: 1;
    }

    .dot {
      width: 4px;
      height: 4px;
      border-radius: 50%;
      background: #ff0000;
    }

    .left-footer {
      position: relative;
      font-size: .78rem;
      color: #000000;
      letter-spacing: .04em;
      font-weight: 600;
      text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
      z-index: 2;
    }

    /* ── Right panel ── */
    .panel-right {
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 3rem 2rem;
      background: var(--paper);
      overflow-y: auto;
    }

    .form-card {
      width: 100%;
      max-width: 440px;
      animation: fadeUp .5s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: none; }
    }

    .form-header {
      margin-bottom: 2.2rem;
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

    /* success */
    .success-banner {
      background: var(--ok-bg);
      border: 1px solid var(--ok-bd);
      color: var(--ok);
      border-radius: 10px;
      padding: 1rem 1.2rem;
      font-size: .9rem;
      display: flex;
      align-items: center;
      gap: .6rem;
      margin-bottom: 1.5rem;
    }

    /* two-column row */
    .field-row-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    /* field */
    .field { margin-bottom: 1.15rem; }

    .field label {
      display: block;
      font-size: .75rem;
      font-weight: 500;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: .45rem;
    }

    .input-wrap { position: relative; }

    .input-wrap svg.icon-prefix {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      pointer-events: none;
    }

    .field input[type="text"],
    .field input[type="email"],
    .field input[type="password"] {
      width: 100%;
      padding: .82rem 1rem .82rem 2.75rem;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: .93rem;
      background: var(--surface);
      color: var(--ink);
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }

    .field input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(200,82,42,.12);
    }

    .field input.has-error {
      border-color: #d95c50;
      box-shadow: 0 0 0 3px rgba(200,82,42,.1);
    }

    .field-error {
      font-size: .78rem;
      color: #c0392b;
      margin-top: .3rem;
    }

    /* password strength */
    .strength-bar {
      display: flex;
      gap: 4px;
      margin-top: .5rem;
    }

    .strength-bar .seg {
      height: 3px;
      flex: 1;
      border-radius: 2px;
      background: var(--border);
      transition: background .3s;
    }

    .strength-label {
      font-size: .72rem;
      color: var(--muted);
      margin-top: .3rem;
    }

    /* terms */
    .terms-row {
      display: flex;
      align-items: flex-start;
      gap: .6rem;
      margin-bottom: 1.5rem;
      font-size: .83rem;
      color: var(--muted);
      line-height: 1.5;
    }

    .terms-row input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--accent);
      margin-top: .15rem;
      flex-shrink: 0;
      cursor: pointer;
    }

    .terms-row a { color: var(--accent); text-decoration: none; }
    .terms-row a:hover { text-decoration: underline; }

    /* submit */
    .btn-submit {
      width: 100%;
      padding: .95rem;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: 'DM Sans', sans-serif;
      font-size: .95rem;
      font-weight: 500;
      letter-spacing: .02em;
      cursor: pointer;
      transition: background .2s, transform .1s, box-shadow .2s;
      box-shadow: 0 4px 14px rgba(200,82,42,.35);
    }

    .btn-submit:hover {
      background: #b34825;
      box-shadow: 0 6px 18px rgba(200,82,42,.4);
    }

    .btn-submit:active { transform: scale(.99); }

    .divider {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1.4rem 0;
      font-size: .78rem;
      color: var(--muted);
    }

    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .btn-oauth {
      width: 100%;
      padding: .82rem;
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
    @media (max-width: 900px) {
      body { 
        grid-template-columns: 1fr; 
      }
      .panel-left { 
        display: none; 
      }
      .panel-right { 
        padding: 1.5rem;
        min-height: auto;
      }
      .form-card {
        max-width: 100%;
      }
      .field-row-grid { 
        grid-template-columns: 1fr; 
      }
    }

    @media (max-width: 480px) {
      body {
        min-height: auto;
      }
      .panel-right {
        padding: 1rem;
        align-items: flex-start;
        justify-content: flex-start;
        padding-top: 2rem;
      }
      .form-card {
        max-width: 100%;
      }
      .form-header {
        margin-bottom: 1.5rem;
      }
      .form-header h2 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>

<!-- Left panel -->
<div class="panel-left">
  <div class="brand" style="display:flex; flex-direction:row; align-items: center; gap: 12px;">
    <img src="umu.jpg" alt="UMU Logo" style="width: 60px; height: 60px;">
    <div>UMU SKILLS MARKETPLACE<span>.</span></div>
  </div>

  <div class="decorative-dots">
    <?php for ($i = 0; $i < 48; $i++): ?><div class="dot"></div><?php endfor; ?>
  </div>

  <div class="features">
    <h2>Start your<br><em>journey.</em></h2>
    <ul class="feature-list">
      <li>
        <span class="icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></span>
        Collaborate with your team in real time
      </li>
      <li>
        <span class="icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></span>
        Secure, encrypted data storage by default
      </li>
      <li>
        <span class="icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></span>
        Powerful analytics and reporting tools
      </li>
      <li>
        <span class="icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></span>
        1 month free trial — no credit card required
      </li>
    </ul>
  </div>

  <div class="left-footer">© <?= date('Y') ?> Techsphere Systems Inc. All rights reserved.</div>
</div>

<!-- Right panel -->
<div class="panel-right">
  <div class="form-card">
    <div class="form-header">
      <h2>Create account</h2>
      <p>Already have one? <a href="login.php">Sign in</a></p>
    </div>

    <?php if ($success): ?>
    <div class="success-banner">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Account created! <a href="login.php" style="color:var(--ok);font-weight:500;margin-left:.2rem">Sign in now →</a>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="reg-form">

      <div class="field">
        <label>Full name</label>
        <div class="input-wrap">
          <svg class="icon-prefix" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input
            type="text"
            name="name"
            placeholder="BABIRYE LUCY"
            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
            class="<?= isset($errors['name']) ? 'has-error' : '' ?>"
            autocomplete="name"
          />
        </div>
        <?php if (isset($errors['name'])): ?>
          <div class="field-error"><?= htmlspecialchars($errors['name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Phone number</label>
        <div class="input-wrap">
          <svg class="icon-prefix" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6 15.27 15.27 0 0 0 2.11-.45 2 2 0 0 1 2.81.7l2.81.7a2 2 0 0 1 1.72 2z"/></svg>
          <input
            type="tel"
            name="phone"
            placeholder="0700123456"
            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
            class="<?= isset($errors['phone']) ? 'has-error' : '' ?>"
            autocomplete="tel"
          />
        </div>
        <?php if (isset($errors['phone'])): ?>
          <div class="field-error"><?= htmlspecialchars($errors['phone']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Email address</label>
        <div class="input-wrap">
          <svg class="icon-prefix" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
          <input
            type="email"
            name="email"
            placeholder="you@stud.umu.ac.ug"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            class="<?= isset($errors['email']) ? 'has-error' : '' ?>"
            autocomplete="email"
          />
        </div>
        <?php if (isset($errors['email'])): ?>
          <div class="field-error"><?= htmlspecialchars($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="input-wrap">
          <svg class="icon-prefix" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input
            type="password"
            name="password"
            id="password"
            placeholder="Min. 8 characters"
            class="<?= isset($errors['password']) ? 'has-error' : '' ?>"
            autocomplete="new-password"
            oninput="updateStrength(this.value)"
          />
        </div>
        <div class="strength-bar">
          <div class="seg" id="s1"></div>
          <div class="seg" id="s2"></div>
          <div class="seg" id="s3"></div>
          <div class="seg" id="s4"></div>
        </div>
        <div class="strength-label" id="strength-label"></div>
        <?php if (isset($errors['password'])): ?>
          <div class="field-error"><?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Confirm password</label>
        <div class="input-wrap">
          <svg class="icon-prefix" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input
            type="password"
            name="confirm"
            placeholder="Repeat password"
            class="<?= isset($errors['confirm']) ? 'has-error' : '' ?>"
            autocomplete="new-password"
          />
        </div>
        <?php if (isset($errors['confirm'])): ?>
          <div class="field-error"><?= htmlspecialchars($errors['confirm']) ?></div>
        <?php endif; ?>
      </div>

      <label class="terms-row">
        <input type="checkbox" name="terms" value="1" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
        I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
      </label>
      <?php if (isset($errors['terms'])): ?>
        <div class="field-error" style="margin-top:-.9rem;margin-bottom:.9rem;"><?= htmlspecialchars($errors['terms']) ?></div>
      <?php endif; ?>

      <button type="submit" class="btn-submit">Create Account</button>
    </form>

    <div class="divider">or sign up with</div>

    <a href="#" class="btn-oauth">
      <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      Continue with Google
    </a>
  </div>
</div>

<script>
function updateStrength(val) {
  const segs   = [1,2,3,4].map(i => document.getElementById('s'+i));
  const label  = document.getElementById('strength-label');
  const colors = ['#d95c50','#e08c3b','#d4a017','#2a7a4b'];
  const labels = ['Weak','Fair','Good','Strong'];

  let score = 0;
  if (val.length >= 8)                       score++;
  if (/[A-Z]/.test(val))                     score++;
  if (/[0-9]/.test(val))                     score++;
  if (/[^A-Za-z0-9]/.test(val))             score++;

  segs.forEach((s, i) => {
    s.style.background = i < score ? colors[score-1] : 'var(--border)';
  });

  label.textContent = val.length ? labels[score-1] ?? '' : '';
  label.style.color = score > 0 ? colors[score-1] : 'var(--muted)';
}
</script>

</body>
</html>