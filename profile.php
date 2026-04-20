<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

// Database connection
require_once 'connection.php';

$user_id = $_SESSION['user_id'] ?? 1;

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Handle case where user not found
    header('Location: login.php');
    exit;
}

$user_name = $user['name'];
$user_email = $user['email'];
$user_phone = $user['phone'];
$user_location = $user['location'];
$user_profile_photo = $user['profile_photo'];
$user_theme = $user['theme'];

$initials = strtoupper(substr($user_name, 0, 1));

/* ── Avatar upload ─────────────────────────────────────────────────────── */
$upload_error = $upload_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['avatar']['name'])) {
    $f       = $_FILES['avatar'];
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if ($f['error'] !== UPLOAD_ERR_OK)        $upload_error = 'Upload failed. Please try again.';
    elseif (!in_array($f['type'], $allowed))  $upload_error = 'Only JPG, PNG, WEBP or GIF allowed.';
    elseif ($f['size'] > 3 * 1024 * 1024)    $upload_error = 'Image must be under 3 MB.';
    else {
        $ext  = pathinfo($f['name'], PATHINFO_EXTENSION);
        $name = 'avatar_' . $user_id . '.' . $ext;
        $dest = __DIR__ . '/uploads/avatars/' . $name;
        @mkdir(dirname($dest), 0775, true);
        if (move_uploaded_file($f['tmp_name'], $dest)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
            $stmt->execute([$name, $user_id]);
            $_SESSION['profile_photo'] = $name;
            $user_profile_photo = $name;
            $upload_success = 'Profile picture updated!';
        } else {
            $upload_error = 'Could not save image — check folder permissions.';
        }
    }
}

$del_avatar_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_avatar'])) {
    $stmt = $pdo->prepare("UPDATE users SET profile_photo = '' WHERE user_id = ?");
    $stmt->execute([$user_id]);
    unset($_SESSION['profile_photo']);
    $user_profile_photo = '';
    $del_avatar_msg = 'Profile picture removed.';
}

/* ── Update profile details ────────────────────────────────────────────── */
$profile_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $new_location = trim($_POST['location'] ?? '');
    if ($new_name) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, location = ? WHERE user_id = ?");
        $stmt->execute([$new_name, $new_phone, $new_location, $user_id]);
        $_SESSION['name'] = $new_name;
        $user_name = $new_name;
        $user_phone = $new_phone;
        $user_location = $new_location;
        $initials = strtoupper(substr($user_name, 0, 1));
        $profile_success = 'Profile updated successfully.';
    }
}

/* ── Change password ───────────────────────────────────────────────────── */
$pw_error = $pw_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $new_pw  = $_POST['new_pw']     ?? '';
    $confirm = $_POST['confirm_pw'] ?? '';
    if (strlen($new_pw) < 8)        $pw_error = 'Password must be at least 8 characters.';
    elseif ($new_pw !== $confirm)   $pw_error = 'Passwords do not match.';
    else {
        $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed_pw, $user_id]);
        $pw_success = 'Password changed successfully.';
    }
}

/* ── Delete account ────────────────────────────────────────────────────── */
if (isset($_POST['delete_account'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    session_destroy();
    header('Location: register.php?deleted=1'); exit;
}

/* ── Fetch profile from session / DB ───────────────────────────────────── */
/*   $row = $pdo->query("SELECT * FROM users WHERE id=$user_id")->fetch();   */
$profile = [
    'name'     => $user_name,
    'email'    => $user_email,
    'phone'    => $user_phone,
    'location' => $user_location,
    'bio'      => $_POST['bio']      ?? '',
    'website'  => $_POST['website']  ?? '',
    'twitter'  => $_POST['twitter']  ?? '',
    'joined'   => date('F Y'),
    'avatar'   => $user_profile_photo,
    'skills'   =>$_post[""] ?? '',
    'bookings' => 1,
    'rating'   => 0,
    'earned'   => '0',
];

$avatar_url = (!empty($profile['avatar']) && file_exists(__DIR__ . '/uploads/avatars/' . $profile['avatar']))
    ? htmlspecialchars('uploads/avatars/' . $profile['avatar']) : null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($user_theme ?? 'dark') ?>">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root,[data-theme="dark"]{--bg:#0b0f1a;--surface:#141927;--surface2:#1c2438;--border:#252d42;--accent:#f5a623;--accent2:#e05c2a;--text:#e8eaf0;--muted:#6b7592;--green:#34c97a;--red:#e05c5c;--radius:14px}
    [data-theme="light"]{--bg:#f0f2f5;--surface:#fff;--surface2:#f4f5f7;--border:#dde0e8;--accent:#e08c10;--accent2:#c8522a;--text:#111827;--muted:#6b7280;--green:#16a34a;--red:#dc2626}
    html,body{transition:background .3s,color .3s}
    body{background:var(--bg);color:var(--text);font-family:'Instrument Sans',sans-serif;min-height:100vh;display:flex}

    /* Sidebar */
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
    .user-pill{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:9px;cursor:pointer;transition:background .18s}
    .user-pill:hover{background:var(--surface2)}
    .sb-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;color:#fff;flex-shrink:0;overflow:hidden}
    .sb-av img{width:100%;height:100%;object-fit:cover}
    .sb-name{font-size:.85rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sb-email{font-size:.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    /* Main */
    .main{margin-left:240px;flex:1}
    .topbar{display:flex;align-items:center;justify-content:space-between;padding:1.4rem 2rem;border-bottom:1px solid var(--border);background:var(--bg);position:sticky;top:0;z-index:50;transition:all .3s;gap:1rem;flex-wrap:wrap}
    .topbar-title h1{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;letter-spacing:-.02em}
    .topbar-title p{font-size:.8rem;color:var(--muted);margin-top:.1rem}

    /* Buttons */
    .btn{padding:.6rem 1.2rem;border-radius:8px;font-family:'Instrument Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;border:none;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
    .btn-primary{background:var(--accent);color:#0b0f1a;font-weight:600}
    .btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
    .btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}
    .btn-ghost:hover{border-color:var(--muted);color:var(--text)}
    .btn-danger{background:rgba(224,92,92,.1);color:var(--red);border:1px solid rgba(224,92,92,.25)}
    .btn-danger:hover{background:rgba(224,92,92,.2)}
    .btn-sm{padding:.42rem .88rem;font-size:.78rem}

    .content{padding:2rem;max-width:900px}

    /* Toasts */
    .toast{padding:.82rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem;animation:fadeUp .3s ease both}
    .toast.success{background:rgba(52,201,122,.1);border:1px solid rgba(52,201,122,.25);color:var(--green)}
    .toast.error{background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.25);color:var(--red)}

    /* ── Profile hero ── */
    .profile-hero{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1.5rem;animation:fadeUp .4s ease both;transition:background .3s,border-color .3s}
    .hero-cover{height:140px;background:linear-gradient(135deg,#0e1a30 0%,#1a2240 45%,#1c1a30 100%);position:relative;overflow:hidden}
    .hero-cover::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 20% 60%,rgba(245,166,35,.18) 0%,transparent 65%),radial-gradient(ellipse 40% 55% at 85% 25%,rgba(224,92,42,.12) 0%,transparent 60%)}
    .hero-cover::after{content:'';position:absolute;width:300px;height:300px;border-radius:50%;border:1px solid rgba(245,166,35,.09);top:-100px;right:-70px}
    .hero-body{padding:0 2rem 1.75rem;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:1.2rem}

    /* Avatar zone */
    .avatar-zone{position:relative;margin-top:-52px;flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:.55rem}
    .avatar-ring{width:100px;height:100px;border-radius:50%;border:4px solid var(--bg);background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:2.3rem;color:#fff;overflow:hidden;cursor:pointer;position:relative;transition:transform .2s,box-shadow .2s}
    .avatar-ring:hover{transform:scale(1.05);box-shadow:0 0 0 3px rgba(245,166,35,.35)}
    .avatar-ring img{width:100%;height:100%;object-fit:cover}
    .avatar-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.58);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.2rem;opacity:0;transition:opacity .22s}
    .avatar-ring:hover .avatar-overlay{opacity:1}
    .avatar-overlay span{font-size:.6rem;color:#fff;font-weight:600;letter-spacing:.04em;text-align:center;line-height:1.3}
    .avatar-btns{display:flex;gap:.35rem}
    #avatarInput{display:none}

    /* Hero info + stats */
    .hero-right{flex:1;padding-top:1.2rem;min-width:0}
    .hero-name{font-family:'Syne',sans-serif;font-size:1.55rem;font-weight:800;letter-spacing:-.03em;margin-bottom:.18rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .hero-email{font-size:.83rem;color:var(--muted);margin-bottom:.6rem}
    .hero-meta{display:flex;gap:1.1rem;flex-wrap:wrap;font-size:.76rem;color:var(--muted)}
    .hero-meta span{display:flex;align-items:center;gap:.28rem}
    .hero-stats{display:flex;gap:.75rem;flex-wrap:wrap;align-self:flex-end;padding-top:1.2rem}
    .stat-box{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:.65rem 1rem;text-align:center;min-width:76px;transition:all .2s}
    .stat-box:hover{border-color:rgba(245,166,35,.3);transform:translateY(-2px)}
    .stat-num{font-family:'Syne',sans-serif;font-weight:700;font-size:1.2rem;color:var(--accent)}
    .stat-lbl{font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-top:.1rem}

    /* ── Bio card ── */
    .bio-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1.25rem;overflow:hidden;animation:fadeUp .4s ease both;animation-delay:.06s;transition:background .3s,border-color .3s}
    .card-head{padding:1rem 1.4rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:.75rem}
    .card-head h3{font-family:'Syne',sans-serif;font-size:.92rem;font-weight:700;display:flex;align-items:center;gap:.55rem}
    .card-icon{width:30px;height:30px;border-radius:8px;background:rgba(245,166,35,.1);display:flex;align-items:center;justify-content:center;color:var(--accent)}
    .bio-view-body{padding:1.25rem 1.4rem}
    .bio-text{font-size:.9rem;line-height:1.75;color:var(--text);white-space:pre-wrap}
    .bio-placeholder{font-size:.88rem;color:var(--muted);font-style:italic;line-height:1.6}
    .bio-edit-body{padding:1.25rem 1.4rem;display:none;border-top:1px solid var(--border)}
    .bio-edit-body.open{display:block}
    .bio-textarea{width:100%;background:var(--surface2);border:1.5px solid var(--border);border-radius:9px;padding:.85rem 1rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.9rem;line-height:1.7;resize:vertical;min-height:120px;outline:none;transition:border-color .2s,background .3s}
    .bio-textarea:focus{border-color:var(--accent)}
    .char-row{display:flex;justify-content:space-between;align-items:center;margin-top:.35rem;font-size:.72rem;color:var(--muted)}
    .bio-edit-actions{display:flex;justify-content:flex-end;gap:.55rem;margin-top:.75rem}

    /* ── Form cards ── */
    .form-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1.25rem;overflow:hidden;animation:fadeUp .4s ease both;transition:background .3s,border-color .3s}
    .form-body{padding:1.4rem}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    .f-field{display:flex;flex-direction:column;gap:.38rem}
    .f-field.full{grid-column:1/-1}
    .f-field label{font-size:.7rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;color:var(--muted)}
    .input-wrap{position:relative}
    .input-wrap svg{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none}
    .f-field input{background:var(--surface2);border:1.5px solid var(--border);border-radius:9px;padding:.75rem 1rem .75rem 2.65rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s,background .3s;width:100%}
    .f-field input:focus{border-color:var(--accent)}
    .f-field input[readonly]{opacity:.5;cursor:not-allowed}
    .f-hint{font-size:.72rem;color:var(--muted);margin-top:.18rem}
    .form-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.1rem}

    /* Password strength */
    .pw-bar{display:flex;gap:4px;margin-top:.42rem}
    .pw-seg{height:3px;flex:1;border-radius:2px;background:var(--border);transition:background .3s}
    .pw-lbl{font-size:.72rem;margin-top:.25rem;color:var(--muted);min-height:1em}

    /* Achievements */
    .badges-body{padding:1.1rem 1.4rem;display:flex;flex-wrap:wrap;gap:.6rem}
    .badge{background:var(--surface2);border:1px solid var(--border);border-radius:9px;padding:.52rem .9rem;display:flex;align-items:center;gap:.42rem;font-size:.8rem;transition:border-color .2s,transform .2s;cursor:default}
    .badge:hover{border-color:rgba(245,166,35,.3);transform:translateY(-1px)}
    .badge.locked{opacity:.38;filter:grayscale(1)}

    /* Danger zone */
    .danger-zone{background:rgba(224,92,92,.04);border:1px solid rgba(224,92,92,.18);border-radius:var(--radius);padding:1.4rem;margin-bottom:1.25rem;animation:fadeUp .4s ease both;animation-delay:.38s}
    .danger-zone h3{font-family:'Syne',sans-serif;font-size:.92rem;font-weight:700;color:var(--red);margin-bottom:.38rem}
    .danger-zone p{font-size:.82rem;color:var(--muted);margin-bottom:1rem;line-height:1.6}

    /* Confirm modal */
    .confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.82);backdrop-filter:blur(6px);z-index:300;display:none;align-items:center;justify-content:center;padding:1rem}
    .confirm-overlay.open{display:flex}
    .confirm-box{background:var(--surface);border:1px solid rgba(224,92,92,.3);border-radius:16px;padding:2rem;max-width:420px;width:100%;animation:popIn .25s ease both;text-align:center}
    @keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:none}}
    .confirm-box h3{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:.6rem}
    .confirm-box p{font-size:.84rem;color:var(--muted);margin-bottom:1.3rem;line-height:1.65}
    .confirm-box input{width:100%;background:var(--surface2);border:1.5px solid var(--border);border-radius:9px;padding:.72rem 1rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.9rem;outline:none;margin-bottom:1rem;transition:border-color .2s}
    .confirm-box input:focus{border-color:var(--red)}
    .confirm-btns{display:flex;gap:.75rem;justify-content:center}

    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
    @media(max-width:860px){.form-grid{grid-template-columns:1fr}.hero-body{flex-direction:column;align-items:flex-start}}
    @media(max-width:800px){.sidebar{display:none}.main{margin-left:0}}
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">Umu <div class="dot"></div></div>
    <div class="logo-sub">Skill Marketplace</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="skills.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>My Skills</a>
    <a href="bookings.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Bookings</a>
    <a href="browse.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse Skills</a>
    <a href="messages.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages</a>
    
    <div class="nav-section">Account</div>
    <a href="profile.php" class="nav-item active"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile</a>
    <a href="settings.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 21 12h-1"/></svg>Settings</a>
    <a href="logout.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Log Out</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="sb-av">
        <?php if ($avatar_url): ?><img src="<?= $avatar_url ?>" alt=""><?php else: ?><?= $initials ?><?php endif; ?>
      </div>
      <div>
        <div class="sb-name"><?= htmlspecialchars($user_name) ?></div>
        <div class="sb-email"><?= htmlspecialchars($user_email) ?></div>
      </div>
    </div>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">
      <h1>My Profile</h1>
      <p>Your public identity on Umu Skill Marketplace</p>
    </div>
    <a href="settings.php" class="btn btn-ghost btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 21 12h-1"/></svg>Settings
    </a>
  </header>

  <div class="content">

    <!-- Toasts -->
    <?php foreach ([
      [$upload_success,'success'],[$upload_error,'error'],
      [$del_avatar_msg,'success'],[$profile_success,'success'],
      [$pw_success,'success'],[$pw_error,'error'],
    ] as [$msg,$type]): if ($msg): ?>
    <div class="toast <?= $type ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><?= $type==='success' ? '<polyline points="20 6 9 17 4 12"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' ?></svg>
      <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; endforeach; ?>

    <!-- ── HERO ── -->
    <div class="profile-hero">
      <div class="hero-cover"></div>
      <div class="hero-body">

        <!-- Avatar -->
        <div class="avatar-zone">
          <div class="avatar-ring" onclick="document.getElementById('avatarInput').click()" title="Click to change photo">
            <?php if ($avatar_url): ?>
              <img src="<?= $avatar_url ?>?t=<?= time() ?>" alt="Profile picture" id="avatarPreviewImg">
            <?php else: ?>
              <span id="avatarInitialSpan"><?= $initials ?></span>
              <img src="" alt="" id="avatarPreviewImg" style="display:none">
            <?php endif; ?>
            <div class="avatar-overlay">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              <span>Change Photo</span>
            </div>
          </div>

          <!-- Upload & delete buttons under avatar -->
          <div class="avatar-btns">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('avatarInput').click()" title="Upload photo">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
              Upload
            </button>
            <?php if ($avatar_url): ?>
            <form method="POST" style="display:inline">
              <button type="submit" name="delete_avatar" value="1" class="btn btn-danger btn-sm" title="Remove photo" onclick="return confirm('Remove your profile picture?')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                Remove
              </button>
            </form>
            <?php endif; ?>
          </div>

          <!-- Hidden file input, auto-submits on change -->
          <form method="POST" enctype="multipart/form-data" id="avatarForm">
            <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewAndUpload(this)">
          </form>
        </div>

        <!-- Info -->
        <div class="hero-right">
          <div class="hero-name"><?= htmlspecialchars($profile['name']) ?></div>
          <div class="hero-email"><?= htmlspecialchars($profile['email']) ?></div>
          <div class="hero-meta">
            <?php if ($profile['location']): ?>
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><?= htmlspecialchars($profile['location']) ?></span>
            <?php endif; ?>
            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Member since <?= $profile['joined'] ?></span>
          </div>
        </div>

        <!-- Stats -->
        <div class="hero-stats">
          <div class="stat-box"><div class="stat-num"><?= $profile['skills'] ?: '—' ?></div><div class="stat-lbl">Skills</div></div>
          <div class="stat-box"><div class="stat-num"><?= $profile['bookings'] ?: '—' ?></div><div class="stat-lbl">Bookings</div></div>
          <div class="stat-box"><div class="stat-num"><?= $profile['rating'] ? $profile['rating'].'★' : '—' ?></div><div class="stat-lbl">Rating</div></div>
          <div class="stat-box"><div class="stat-num" style="font-size:.9rem">UGX <?= $profile['earned'] ?: '0' ?></div><div class="stat-lbl">Earned</div></div>
        </div>

      </div>
    </div>

    <!-- ── BIO ── -->
    <div class="bio-card">
      <div class="card-head">
        <h3>
          <div class="card-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg></div>
          About Me
        </h3>
        <button type="button" class="btn btn-ghost btn-sm" id="bioToggleBtn" onclick="toggleBio()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit Bio
        </button>
      </div>

      <!-- View -->
      <div class="bio-view-body" id="bioViewBody">
        <?php if ($profile['bio']): ?>
          <p class="bio-text"><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
        <?php else: ?>
          <p class="bio-placeholder">You haven't written a bio yet. Click <strong>Edit Bio</strong> to introduce yourself — tell the community what you do, what you love, and what you offer.</p>
        <?php endif; ?>
      </div>

      <!-- Edit (inline, slides in) -->
      <div class="bio-edit-body" id="bioEditBody">
        <form method="POST">
          <input type="hidden" name="update_profile">
          <input type="hidden" name="name"     value="<?= htmlspecialchars($profile['name']) ?>">
          <input type="hidden" name="phone"    value="<?= htmlspecialchars($profile['phone']) ?>">
          <input type="hidden" name="location" value="<?= htmlspecialchars($profile['location']) ?>">
          <input type="hidden" name="website"  value="<?= htmlspecialchars($profile['website']) ?>">
          <input type="hidden" name="twitter"  value="<?= htmlspecialchars($profile['twitter']) ?>">
          <textarea name="bio" id="bioTa" class="bio-textarea" maxlength="500"
            placeholder="Write something about yourself — skills, experience, interests, what makes you unique on Umu…"
            oninput="updateCC()"><?= htmlspecialchars($profile['bio']) ?></textarea>
          <div class="char-row">
            <span style="color:var(--muted);font-size:.72rem">Max 500 characters</span>
            <span id="bioCC">0 / 500</span>
          </div>
          <div class="bio-edit-actions">
            <button type="button" class="btn btn-danger btn-sm" onclick="clearBio()" title="Delete bio">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
              Delete Bio
            </button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBio()">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              Save Bio
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── PERSONAL DETAILS ── -->
    <div class="form-card" style="animation-delay:.12s">
      <div class="card-head">
        <h3>
          <div class="card-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
          Personal Details
        </h3>
      </div>
      <div class="form-body">
        <form method="POST">
          <input type="hidden" name="update_profile">
          <input type="hidden" name="bio" value="<?= htmlspecialchars($profile['bio']) ?>">
          <div class="form-grid">
            <div class="f-field">
              <label>Full Name</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" placeholder="Your full name" required>
              </div>
            </div>
            <div class="f-field">
              <label>Email Address</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                <input type="email" value="<?= htmlspecialchars($profile['email']) ?>" readonly>
              </div>
              <span class="f-hint">Email cannot be changed here.</span>
            </div>
            <div class="f-field">
              <label>Phone Number</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.15 12a19.79 19.79 0 0 1-3.07-8.63A2 2 0 0 1 3.12 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21 16.92z"/></svg>
                <input type="tel" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>" placeholder="+256 700 000 000">
              </div>
            </div>
            <div class="f-field">
              <label>Location</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" name="location" value="<?= htmlspecialchars($profile['location']) ?>" placeholder="City, Country">
              </div>
            </div>
            <div class="f-field">
              <label>Website</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <input type="url" name="website" value="<?= htmlspecialchars($profile['website']) ?>" placeholder="https://yoursite.com">
              </div>
            </div>
            <div class="f-field">
              <label>Twitter / X</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53A4.48 4.48 0 0 0 22.43.36a9 9 0 0 1-2.88 1.1A4.52 4.52 0 0 0 16.11 0c-2.5 0-4.52 2.02-4.52 4.52 0 .35.04.7.11 1.03C7.69 5.37 4.07 3.58 1.64.9a4.52 4.52 0 0 0 1.4 6.04A4.47 4.47 0 0 1 .96 6.4v.06a4.52 4.52 0 0 0 3.62 4.43 4.56 4.56 0 0 1-2.04.08 4.52 4.52 0 0 0 4.22 3.13A9.07 9.07 0 0 1 0 15.54 12.8 12.8 0 0 0 6.92 17.5c8.3 0 12.85-6.88 12.85-12.85 0-.2 0-.39-.01-.58A9.18 9.18 0 0 0 22 2.26"/></svg>
                <input type="text" name="twitter" value="<?= htmlspecialchars($profile['twitter']) ?>" placeholder="@yourhandle">
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button type="reset" class="btn btn-ghost btn-sm">Discard</button>
            <button type="submit" class="btn btn-primary btn-sm">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Save Details
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── CHANGE PASSWORD ── -->
    <div class="form-card" style="animation-delay:.2s">
      <div class="card-head">
        <h3>
          <div class="card-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
          Change Password
        </h3>
      </div>
      <div class="form-body">
        <form method="POST">
          <input type="hidden" name="change_password">
          <div class="form-grid">
            <div class="f-field full">
              <label>Current Password</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="current_pw" placeholder="Enter current password" required>
              </div>
            </div>
            <div class="f-field">
              <label>New Password</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="new_pw" id="newPwInput" placeholder="Min. 8 characters" oninput="strengthCheck(this.value)" required>
              </div>
              <div class="pw-bar"><div class="pw-seg" id="s1"></div><div class="pw-seg" id="s2"></div><div class="pw-seg" id="s3"></div><div class="pw-seg" id="s4"></div></div>
              <div class="pw-lbl" id="pwLbl"></div>
            </div>
            <div class="f-field">
              <label>Confirm New Password</label>
              <div class="input-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="confirm_pw" placeholder="Repeat new password" required>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── ACHIEVEMENTS ── -->
    <div class="form-card" style="animation-delay:.27s">
      <div class="card-head">
        <h3>
          <div class="card-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none" style="color:var(--accent)"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" opacity=".8"/></svg></div>
          Achievements
        </h3>
        <span style="font-size:.75rem;color:var(--muted)"><?= ($profile['skills'] && $profile['bookings']) > 0 ? 'Keep going!' : 'Start listing skills to earn badges' ?></span>
      </div>
      <div class="badges-body">
        <?php
        $badges = [
          ['🎉','Early Adopter',     false],
          ['✅','Verified Member',   false],
          ['🎯','First Skill Listed', $profile['skills'] === 0],
          ['📅','First Booking',     $profile['bookings'] === 0],
          ['⭐','Received a Review', $profile['rating']  === 0],
          ['🌟','Top Rated (4.5+)',  $profile['rating']  < 4.5],
          ['📦','5+ Bookings',       $profile['bookings'] < 5],
          ['🏆','10+ Bookings',      $profile['bookings'] < 10],
          ['💰','First Earnings',    $profile['earned']  === '0'],
        ];
        foreach ($badges as [$icon, $label, $locked]): ?>
        <div class="badge <?= $locked ? 'locked' : '' ?>" title="<?= $locked ? '🔒 Locked — keep going!' : '✓ Earned' ?>">
          <?= $icon ?> <?= $label ?>
          <?php if (!$locked): ?><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── DANGER ZONE ── -->
    <div class="danger-zone">
      <h3>⚠️ Danger Zone</h3>
      <p>Permanently delete your account and all associated data — skills, bookings, messages, and reviews. This action is irreversible. You'll need to type your email to confirm.</p>
      <button type="button" class="btn btn-danger" onclick="openDeleteModal()">Delete My Account</button>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ── Delete confirm modal ── -->
<div class="confirm-overlay" id="deleteOverlay" onclick="if(event.target===this)closeDeleteModal()">
  <div class="confirm-box">
    <div style="font-size:2.5rem;margin-bottom:.9rem">🗑️</div>
    <h3>Delete Your Account?</h3>
    <p>All your skills, bookings, messages, and profile data will be permanently erased.<br><br>Type your email address below to confirm:</p>
    <input type="text" id="confirmInput" placeholder="<?= htmlspecialchars($user_email) ?>" oninput="checkDelete()">
    <div class="confirm-btns">
      <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
      <form method="POST" style="display:inline">
        <button type="submit" name="delete_account" value="1" id="deleteBtn" class="btn btn-danger" disabled style="opacity:.5;cursor:not-allowed">
          Yes, Delete Everything
        </button>
      </form>
    </div>
  </div>
</div>

<script>
  // Theme - Load from database
  const currentTheme = '<?= htmlspecialchars($user_theme ?? 'light') ?>';
  document.documentElement.setAttribute('data-theme', currentTheme);

  // Avatar preview → auto-submit
  function previewAndUpload(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img  = document.getElementById('avatarPreviewImg');
      const init = document.getElementById('avatarInitialSpan');
      img.src = e.target.result;
      img.style.display = 'block';
      if (init) init.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
    setTimeout(() => document.getElementById('avatarForm').submit(), 220);
  }

  // Bio toggle
  let bioOpen = false;
  function toggleBio() {
    bioOpen = !bioOpen;
    document.getElementById('bioEditBody').classList.toggle('open', bioOpen);
    document.getElementById('bioViewBody').style.display = bioOpen ? 'none' : '';
    const btn = document.getElementById('bioToggleBtn');
    btn.innerHTML = bioOpen
      ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancel'
      : '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit Bio';
    if (bioOpen) { updateCC(); document.getElementById('bioTa').focus(); }
  }

  function updateCC() {
    const len = document.getElementById('bioTa').value.length;
    const el  = document.getElementById('bioCC');
    el.textContent = len + ' / 500';
    el.style.color = len > 450 ? 'var(--red)' : 'var(--muted)';
  }

  function clearBio() {
    if (!confirm('Delete your bio? This will save an empty bio.')) return;
    document.getElementById('bioTa').value = '';
    updateCC();
    document.getElementById('bioTa').closest('form').submit();
  }

  // Password strength
  function strengthCheck(val) {
    const colors = ['#e05c5c','#f5a623','#d4a017','#34c97a'];
    const labels = ['Weak','Fair','Good','Strong'];
    let score = 0;
    if (val.length >= 8)            score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    [1,2,3,4].forEach(i => {
      document.getElementById('s'+i).style.background = i <= score ? colors[score-1] : 'var(--border)';
    });
    const lbl = document.getElementById('pwLbl');
    lbl.textContent = val.length ? labels[score-1] : '';
    lbl.style.color = score > 0 ? colors[score-1] : 'var(--muted)';
  }

  // Delete modal
  function openDeleteModal()  { document.getElementById('deleteOverlay').classList.add('open'); }
  function closeDeleteModal() { document.getElementById('deleteOverlay').classList.remove('open'); }
  function checkDelete() {
    const ok = document.getElementById('confirmInput').value.trim() === '<?= addslashes($user_email) ?>';
    const btn = document.getElementById('deleteBtn');
    btn.disabled = !ok;
    btn.style.opacity = ok ? '1' : '.5';
    btn.style.cursor  = ok ? 'pointer' : 'not-allowed';
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDeleteModal(); });
</script>
</body>
</html>
