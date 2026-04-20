<?php
// settings.php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

$user_id    = $_SESSION['user_id'] ?? '';
require_once 'connection.php';

$user_name  = htmlspecialchars($_SESSION['name'] ?? 'there');
$user_email = htmlspecialchars($_SESSION['user'] ?? '');
$initials   = strtoupper(substr($user_name, 0, 1));
$avatar_url = (!empty($_SESSION['avatar']) && file_exists(__DIR__ . '/' . $_SESSION['avatar']))
              ? htmlspecialchars($_SESSION['avatar']) : null;

$success_msg = '';
$error_msg   = '';

// ── AJAX: save theme to DB + session ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_theme') {
    $theme = ($_POST['theme'] === 'dark') ? 'dark' : 'light';
    $_SESSION['theme'] = $theme;
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE user_id = ?");
    $stmt->execute([$theme, $user_id]);
    echo json_encode(['status' => 'ok', 'theme' => $theme]);
    exit();
}

// ── Update profile ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $location = trim($_POST['location'] ?? 'Kampala');

    if (empty($name) || empty($email)) {
        $error_msg = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } else {
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $chk->execute([$email, $user_id]);
        if ($chk->fetch()) {
            $error_msg = 'That email is already used by another account.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, location=? WHERE user_id=?");
            $stmt->execute([$name, $email, $phone, $location, $user_id]);
            $_SESSION['name'] = $name;
            $user_name        = htmlspecialchars($name);
            $initials         = strtoupper(substr($name, 0, 1));
            $success_msg      = 'Profile updated successfully.';
        }
    }
}

// ── Change password ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password']  ?? '';
    $new     = $_POST['new_password']      ?? '';
    $confirm = $_POST['confirm_password']  ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password'])) {
        $error_msg = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error_msg = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error_msg = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([password_hash($new, PASSWORD_BCRYPT), $user_id]);
        $success_msg = 'Password changed successfully.';
    }
}

// ── Upload profile photo ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_photo') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = mime_content_type($_FILES['profile_photo']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $error_msg = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        } elseif ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {
            $error_msg = 'Image must be under 2 MB.';
        } else {
            $ext  = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $fname = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $dir   = __DIR__ . '/uploads/profiles/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dir . $fname)) {
                $path = 'uploads/profiles/' . $fname;
                $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
                $stmt->execute([$path, $user_id]);
                $_SESSION['avatar'] = $path;
                $avatar_url         = htmlspecialchars($path);
                $success_msg        = 'Profile photo updated.';
            } else {
                $error_msg = 'Upload failed. Please try again.';
            }
        }
    } else {
        $error_msg = 'No file selected.';
    }
}

// ── Fetch current user ────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT user_id, name, email, phone, location, profile_photo, theme FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$saved_theme = $user['theme'] ?? $_SESSION['theme'] ?? 'dark';
$_SESSION['theme'] = $saved_theme;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($saved_theme) ?>">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root,[data-theme="dark"]{--bg:#0b0f1a;--surface:#141927;--surface2:#1c2438;--border:#252d42;--accent:#f5a623;--accent2:#e05c2a;--text:#e8eaf0;--muted:#6b7592;--green:#34c97a;--blue:#4a90e2;--red:#e05c2a;--radius:14px}
    [data-theme="light"]{--bg:#f0f2f5;--surface:#fff;--surface2:#f4f5f7;--border:#dde0e8;--accent:#e08c10;--accent2:#c8522a;--text:#111827;--muted:#6b7280;--green:#16a34a;--blue:#2563eb;--red:#dc2626}
    html,body{transition:background .3s,color .3s}
    body{background:var(--bg);color:var(--text);font-family:'Instrument Sans',sans-serif;min-height:100vh;display:flex}

    /* Sidebar — identical to dashboard.php */
    .sidebar{width:240px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:background .3s,border-color .3s}
    .sidebar-logo{padding:1.8rem 1.5rem 1.4rem;border-bottom:1px solid var(--border)}
    .logo-mark{font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;letter-spacing:-.03em;display:flex;align-items:center;gap:.4rem}
    .logo-mark .dot{width:8px;height:8px;background:var(--accent);border-radius:50%}
    .logo-sub{font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:.2rem}
    .sidebar-nav{flex:1;padding:1.2rem .75rem;overflow-y:auto}
    .nav-section{font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:.8rem .75rem .4rem}
    .nav-item{display:flex;align-items:center;gap:.75rem;padding:.65rem .75rem;border-radius:9px;color:var(--muted);font-size:.88rem;cursor:pointer;transition:all .18s;text-decoration:none;margin-bottom:.1rem}
    .nav-item:hover{background:var(--surface2);color:var(--text)}
    .nav-item.active{background:rgba(245,166,35,.1);color:var(--accent);font-weight:500}
    .sidebar-footer{border-top:1px solid var(--border);padding:1rem .75rem}
    .user-pill{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:9px;cursor:pointer;transition:background .18s;text-decoration:none;color:inherit}
    .user-pill:hover{background:var(--surface2)}
    .sb-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;color:#fff;flex-shrink:0;overflow:hidden}
    .sb-av img{width:100%;height:100%;object-fit:cover}
    .sb-name{font-size:.85rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sb-email{font-size:.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    /* Main */
    .main{margin-left:240px;flex:1;min-height:100vh}
    .topbar{display:flex;align-items:center;justify-content:space-between;padding:1.4rem 2rem;border-bottom:1px solid var(--border);background:var(--bg);position:sticky;top:0;z-index:50;transition:all .3s;gap:1rem;flex-wrap:wrap}
    .topbar-title h1{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;letter-spacing:-.02em}
    .topbar-title p{font-size:.8rem;color:var(--muted);margin-top:.1rem}
    .topbar-actions{display:flex;gap:.75rem;align-items:center}

    .btn{padding:.6rem 1.2rem;border-radius:8px;font-family:'Instrument Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;border:none;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
    .btn-primary{background:var(--accent);color:#0b0f1a;font-weight:600}
    .btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
    .btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}
    .btn-ghost:hover{border-color:var(--muted);color:var(--text)}
    .btn-sm{padding:.42rem .88rem;font-size:.78rem}

    .content{padding:2rem}

    /* Settings two-col wrap */
    .settings-wrap{display:grid;grid-template-columns:210px 1fr;gap:1.5rem;align-items:start}

    /* Left nav — same card style as .section-card */
    .settings-nav{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;position:sticky;top:90px;transition:background .3s,border-color .3s}
    .sn-item{display:flex;align-items:center;gap:.7rem;padding:.72rem 1.1rem;font-size:.85rem;color:var(--muted);cursor:pointer;transition:all .18s;border-bottom:1px solid var(--border);text-decoration:none}
    .sn-item:last-child{border-bottom:none}
    .sn-item:hover{background:var(--surface2);color:var(--text)}
    .sn-item.active{color:var(--accent);background:rgba(245,166,35,.07);font-weight:500}
    .sn-item svg{flex-shrink:0;opacity:.6}
    .sn-item.active svg{opacity:1}

    /* Section card — matches dashboard .section-card exactly */
    .s-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1.25rem;animation:fadeUp .35s ease both;transition:background .3s,border-color .3s}
    .s-card-head{padding:1.1rem 1.4rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .s-card-head h3{font-family:'Syne',sans-serif;font-size:.92rem;font-weight:700}
    .s-card-head span{font-size:.75rem;color:var(--muted)}
    .s-card-body{padding:1.4rem}

    /* Form controls */
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
    .form-row.single{grid-template-columns:1fr}
    .form-group{display:flex;flex-direction:column;gap:.4rem}
    .form-group label{font-size:.78rem;color:var(--muted);font-weight:500;letter-spacing:.02em}
    .form-group input,.form-group select{background:var(--surface2);border:1px solid var(--border);border-radius:9px;padding:.62rem .9rem;font-size:.88rem;color:var(--text);font-family:'Instrument Sans',sans-serif;outline:none;transition:border-color .18s,box-shadow .18s;width:100%}
    .form-group input:focus,.form-group select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(245,166,35,.12)}
    .form-group input::placeholder{color:var(--muted)}

    /* Save row */
    .save-row{display:flex;align-items:center;justify-content:flex-end;gap:.75rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)}

    /* Alerts */
    .alert{padding:.85rem 1.1rem;border-radius:10px;font-size:.84rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.65rem;animation:fadeUp .3s ease both}
    .alert-success{background:rgba(52,201,122,.1);border:1px solid rgba(52,201,122,.2);color:var(--green)}
    .alert-error{background:rgba(224,92,42,.1);border:1px solid rgba(224,92,42,.2);color:var(--red)}

    /* Theme options */
    .theme-options{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.1rem}
    .theme-opt{border:2px solid var(--border);border-radius:12px;padding:1rem;cursor:pointer;transition:all .2s;position:relative;overflow:hidden}
    .theme-opt:hover{border-color:rgba(245,166,35,.4);transform:translateY(-1px)}
    .theme-opt.selected{border-color:var(--accent)}
    .theme-opt::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent);opacity:0;transition:opacity .2s}
    .theme-opt.selected::after{opacity:1}
    .tp-preview{height:64px;border-radius:8px;margin-bottom:.7rem;overflow:hidden;display:grid;grid-template-rows:16px 1fr;gap:3px;padding:5px}
    .tp-bar{border-radius:3px;height:100%}
    .tp-body{border-radius:3px;display:grid;grid-template-columns:2fr 3fr;gap:3px}
    .tp-side,.tp-main{border-radius:3px}
    /* dark preview swatches */
    .tp-dark{background:#0b0f1a}
    .tp-dark .tp-bar{background:#1c2438}
    .tp-dark .tp-side{background:#141927}
    .tp-dark .tp-main{background:#1c2438}
    /* light preview swatches */
    .tp-light{background:#f0f2f5}
    .tp-light .tp-bar{background:#dde0e8}
    .tp-light .tp-side{background:#fff}
    .tp-light .tp-main{background:#f4f5f7}
    .theme-opt-label{font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;display:flex;align-items:center;justify-content:space-between}
    .check-circle{width:16px;height:16px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;font-size:.6rem;color:transparent}
    .theme-opt.selected .check-circle{background:var(--accent);border-color:var(--accent);color:#0b0f1a;font-weight:700}

    /* Topbar theme pill */
    .theme-pill{display:flex;align-items:center;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:.28rem;gap:.15rem}
    .tp-btn{width:28px;height:24px;border-radius:14px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;background:transparent;color:var(--muted);transition:all .18s;font-size:.82rem;line-height:1}
    .tp-btn.on{background:var(--surface2);color:var(--text)}

    /* Password strength */
    .pw-bar-wrap{height:3px;border-radius:2px;background:var(--border);margin-top:.4rem;overflow:hidden}
    .pw-bar-fill{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s}

    /* Avatar */
    .avatar-row{display:flex;align-items:center;gap:1.25rem;margin-bottom:1.25rem}
    .avatar-lg{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:1.5rem;color:#fff;flex-shrink:0;overflow:hidden;border:2px solid var(--border)}
    .avatar-lg img{width:100%;height:100%;object-fit:cover}
    .avatar-meta p{font-size:.8rem;color:var(--muted);margin-top:.25rem;line-height:1.5;max-width:30ch}

    @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
    @media(max-width:900px){.settings-wrap{grid-template-columns:1fr}.settings-nav{position:static;display:flex;overflow-x:auto;border-radius:var(--radius)}.sn-item{border-bottom:none;border-right:1px solid var(--border);white-space:nowrap;flex-shrink:0}.sn-item:last-child{border-right:none}}
    @media(max-width:800px){.sidebar{display:none}.main{margin-left:0}.form-row{grid-template-columns:1fr}}
    @media(max-width:600px){.theme-options{grid-template-columns:1fr}}
  </style>
</head>
<body>
<script>(function(){var t=localStorage.getItem('umu_theme')||'<?= htmlspecialchars($saved_theme) ?>';document.documentElement.setAttribute('data-theme',t);})();</script>

<!-- ── Sidebar ───────────────────────────────────────────────────────────── -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">Umu <div class="dot"></div></div>
    <div class="logo-sub">Skill Marketplace</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="skills.php"    class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>My Skills</a>
    <a href="bookings.php"  class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Bookings</a>
    <a href="browse.php"    class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse Skills</a>
    <a href="messages.php"  class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages</a>
    <div class="nav-section">Account</div>
    <a href="profile.php"   class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile</a>
    <a href="settings.php"  class="nav-item active"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 21 12h-1"/></svg>Settings</a>
    <a href="logout.php"    class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Log Out</a>
  </nav>
  <div class="sidebar-footer">
    <a href="profile.php" class="user-pill">
      <div class="sb-av">
        <?php if ($avatar_url): ?><img src="<?= $avatar_url ?>" alt=""><?php else: ?><?= $initials ?><?php endif; ?>
      </div>
      <div>
        <div class="sb-name"><?= $user_name ?></div>
        <div class="sb-email"><?= $user_email ?></div>
      </div>
    </a>
  </div>
</aside>

<!-- ── Main ──────────────────────────────────────────────────────────────── -->
<div class="main">
  <header class="topbar">
    <div class="topbar-title">
      <h1>Settings</h1>
      <p>Account, security &amp; appearance</p>
    </div>
    <div class="topbar-actions">
      <div class="theme-pill">
        <button class="tp-btn" id="tpLight" onclick="applyTheme('light')" title="Light mode">☀️</button>
        <button class="tp-btn" id="tpDark"  onclick="applyTheme('dark')"  title="Dark mode">🌙</button>
      </div>
      <a href="dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>
  </header>

  <div class="content">

    <?php if ($success_msg): ?>
    <div class="alert alert-success">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($success_msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
    <div class="alert alert-error">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error_msg) ?>
    </div>
    <?php endif; ?>

    <div class="settings-wrap">

      <!-- Left nav -->
      <div class="settings-nav">
        <a href="#profile"    class="sn-item active" data-tab="profile">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile
        </a>
        <a href="#appearance" class="sn-item" data-tab="appearance">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>Appearance
        </a>
        <a href="#security"   class="sn-item" data-tab="security">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Security
        </a>
        <a href="#photo"      class="sn-item" data-tab="photo">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Photo
        </a>
      </div>

      <!-- Panels -->
      <div>

        <!-- PROFILE -->
        <div id="panel-profile" class="panel">
          <div class="s-card">
            <div class="s-card-head">
              <h3>Profile Information</h3>
              <span>Visible to other users</span>
            </div>
            <div class="s-card-body">
              <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-row">
                  <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Your full name" required>
                  </div>
                  <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="you@example.com" required>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="0700 123 456">
                  </div>
                  <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($user['location'] ?? 'Kampala') ?>" placeholder="City, District">
                  </div>
                </div>
                <div class="save-row">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- APPEARANCE -->
        <div id="panel-appearance" class="panel" style="display:none">
          <div class="s-card">
            <div class="s-card-head">
              <h3>Appearance</h3>
              <span>Saved to your account · applies everywhere</span>
            </div>
            <div class="s-card-body">
              <p style="font-size:.82rem;color:var(--muted);margin-bottom:1.1rem;line-height:1.7">
                Pick a colour scheme. Your choice is stored in your account so it follows you across every device.
              </p>
              <div class="theme-options">
                <div class="theme-opt" id="opt-dark" onclick="applyTheme('dark')">
                  <div class="tp-preview tp-dark">
                    <div class="tp-bar"></div>
                    <div class="tp-body"><div class="tp-side"></div><div class="tp-main"></div></div>
                  </div>
                  <div class="theme-opt-label">🌙 Dark <div class="check-circle">✓</div></div>
                </div>
                <div class="theme-opt" id="opt-light" onclick="applyTheme('light')">
                  <div class="tp-preview tp-light">
                    <div class="tp-bar"></div>
                    <div class="tp-body"><div class="tp-side"></div><div class="tp-main"></div></div>
                  </div>
                  <div class="theme-opt-label">☀️ Light <div class="check-circle">✓</div></div>
                </div>
              </div>
              <div id="theme-saved-msg" style="font-size:.8rem;color:var(--green);display:none;align-items:center;gap:.45rem;margin-top:.5rem">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Theme saved to your account
              </div>
            </div>
          </div>
        </div>

        <!-- SECURITY -->
        <div id="panel-security" class="panel" style="display:none">
          <div class="s-card">
            <div class="s-card-head">
              <h3>Change Password</h3>
              <span>Use a strong, unique password</span>
            </div>
            <div class="s-card-body">
              <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-row single">
                  <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter your current password" required>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" id="newPw" placeholder="Min. 6 characters" minlength="6" required>
                    <div class="pw-bar-wrap"><div class="pw-bar-fill" id="pwBar"></div></div>
                  </div>
                  <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                  </div>
                </div>
                <div class="save-row">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Update Password
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- PHOTO -->
        <div id="panel-photo" class="panel" style="display:none">
          <div class="s-card">
            <div class="s-card-head">
              <h3>Profile Photo</h3>
              <span>JPG, PNG or WEBP · Max 2 MB</span>
            </div>
            <div class="s-card-body">
              <div class="avatar-row">
                <div class="avatar-lg" id="avatarWrap">
                  <?php if ($avatar_url): ?>
                  <img src="<?= $avatar_url ?>" alt="Photo">
                  <?php else: ?>
                  <?= $initials ?>
                  <?php endif; ?>
                </div>
                <div class="avatar-meta">
                  <strong style="font-size:.9rem"><?= $user_name ?></strong>
                  <p>Your photo shows on your profile card and next to services in the marketplace.</p>
                </div>
              </div>
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_photo">
                <div class="form-row single" style="max-width:360px">
                  <div class="form-group">
                    <label>Choose new photo</label>
                    <input type="file" name="profile_photo" id="photoInput" accept="image/*" required>
                  </div>
                </div>
                <div class="save-row" style="justify-content:flex-start;border:none;padding-top:.5rem;margin-top:0">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
                    Upload Photo
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div><!-- /panels -->
    </div><!-- /settings-wrap -->
  </div><!-- /content -->
</div><!-- /main -->

<script>
// ── Tab switching ─────────────────────────────────────────────────────────────
document.querySelectorAll('.sn-item[data-tab]').forEach(function(link){
  link.addEventListener('click', function(e){
    e.preventDefault();
    document.querySelectorAll('.sn-item').forEach(function(l){ l.classList.remove('active'); });
    document.querySelectorAll('.panel').forEach(function(p){ p.style.display = 'none'; });
    link.classList.add('active');
    document.getElementById('panel-' + link.dataset.tab).style.display = 'block';
    history.replaceState(null, '', '#' + link.dataset.tab);
  });
});
// Restore from hash
(function(){
  var h = location.hash.replace('#','');
  var l = document.querySelector('.sn-item[data-tab="'+h+'"]');
  if (l) l.click();
})();

// ── Theme ─────────────────────────────────────────────────────────────────────
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('umu_theme', theme);
  // cards
  var od = document.getElementById('opt-dark'),  ol = document.getElementById('opt-light');
  if (od) od.classList.toggle('selected', theme === 'dark');
  if (ol) ol.classList.toggle('selected', theme === 'light');
  // topbar pill
  document.getElementById('tpDark').classList.toggle('on',  theme === 'dark');
  document.getElementById('tpLight').classList.toggle('on', theme === 'light');
  // AJAX save
  fetch('settings.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=set_theme&theme='+encodeURIComponent(theme)
  }).then(function(r){ return r.json(); }).then(function(d){
    if (d.status === 'ok') {
      var m = document.getElementById('theme-saved-msg');
      if (m) { m.style.display='flex'; setTimeout(function(){ m.style.display='none'; }, 2800); }
    }
  });
}
// Init on load
(function(){
  var t = localStorage.getItem('umu_theme') || '<?= htmlspecialchars($saved_theme) ?>';
  applyTheme(t);
})();

// ── Password strength ─────────────────────────────────────────────────────────
var newPw = document.getElementById('newPw');
if (newPw) newPw.addEventListener('input', function(){
  var v = this.value, s = 0;
  if (v.length >= 6)           s++;
  if (v.length >= 10)          s++;
  if (/[A-Z]/.test(v))         s++;
  if (/[0-9]/.test(v))         s++;
  if (/[^A-Za-z0-9]/.test(v)) s++;
  var bar = document.getElementById('pwBar');
  bar.style.width      = (s/5*100) + '%';
  bar.style.background = s <= 1 ? '#e05c2a' : s <= 3 ? '#f5a623' : '#34c97a';
});

// ── Photo preview ─────────────────────────────────────────────────────────────
var pi = document.getElementById('photoInput');
if (pi) pi.addEventListener('change', function(){
  var f = this.files[0]; if (!f) return;
  var r = new FileReader();
  r.onload = function(e){
    var w = document.getElementById('avatarWrap');
    w.innerHTML = '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover">';
  };
  r.readAsDataURL(f);
});
</script>
</body>
</html>