<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'] ?? "";  

require_once 'connection.php'; 

$user_name  = htmlspecialchars($_SESSION['name'] ?? 'there');
$user_email = htmlspecialchars($_SESSION['user'] ?? '');
$initials   = strtoupper(substr($user_name, 0, 1));

$avatar_url = (!empty($_SESSION['avatar']) && file_exists(__DIR__ . '/' . $_SESSION['avatar']))
              ? htmlspecialchars($_SESSION['avatar']) : null;

// Skills count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM service WHERE user_id = ?");
$stmt->execute([$user_id]);
$skills_count = $stmt->fetchColumn();

$stmt_users = $pdo -> prepare("SELECT profile_photo FROM users WHERE user_id = ?");
$stmt_users ->execute([$user_id]);
$user_data = $stmt_users->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT name, email, theme, profile_photo FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

$profile_photo = !
empty($user_data ['profile_photo']) ?
$user_data['profile_photo'] :'default.png';

// Bookings count (requests on your services)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM request r
    JOIN service s ON s.service_id = r.service_id
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$bookings_count = $stmt->fetchColumn();

// Average rating
$stmt = $pdo->prepare("
    SELECT ROUND(AVG(rv.rating), 1) FROM review rv
    JOIN request r ON r.request_id = rv.request_id
    JOIN service s ON s.service_id = r.service_id
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$avg_rating = $stmt->fetchColumn() ?? 0;

$stats = [
    'skills'   => (int)$skills_count,
    'bookings' => (int)$bookings_count,
    'earned'   => 0,       // add when payments are tracked
    'rating'   => (float)$avg_rating,
];

// Recent bookings
$stmt = $pdo->prepare("
    SELECT r.request_id, u.name AS client, s.title AS skill,
           s.price, r.status, r.created_at
    FROM request r
    JOIN service s ON s.service_id = r.service_id
    JOIN users u ON u.user_id = r.requester_id
    WHERE s.user_id = ?
    ORDER BY r.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll();

// My skills
$stmt = $pdo->prepare("
    SELECT s.service_id, s.title, sk.skill_name AS category,
           s.price,
           COUNT(r.request_id) AS bookings,
           'active' AS status
    FROM service s
    JOIN skill sk ON sk.skill_id = s.skill_id
    LEFT JOIN request r ON r.service_id = s.service_id
    WHERE s.user_id = ?
    GROUP BY s.service_id
    ORDER BY s.created_at DESC LIMIT 4
");
$stmt->execute([$user_id]);
$my_skills = $stmt->fetchAll();

$hour     = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$is_new   = $stats['skills'] === 0 && $stats['bookings'] === 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root,[data-theme="dark"]{--bg:#0b0f1a;--surface:#141927;--surface2:#1c2438;--border:#252d42;--accent:#f5a623;--accent2:#e05c2a;--text:#e8eaf0;--muted:#6b7592;--green:#34c97a;--blue:#4a90e2;--radius:14px}
    [data-theme="light"]{--bg:#f0f2f5;--surface:#fff;--surface2:#f4f5f7;--border:#dde0e8;--accent:#e08c10;--accent2:#c8522a;--text:#111827;--muted:#6b7280;--green:#16a34a;--blue:#2563eb}
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
    .topbar-actions{display:flex;gap:.75rem}

    .btn{padding:.6rem 1.2rem;border-radius:8px;font-family:'Instrument Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;border:none;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
    .btn-primary{background:var(--accent);color:#0b0f1a;font-weight:600}
    .btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
    .btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}
    .btn-ghost:hover{border-color:var(--muted);color:var(--text)}
    .btn-sm{padding:.42rem .88rem;font-size:.78rem}

    .content{padding:2rem}

    /* Welcome banner */
    .welcome-banner{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.75rem 2rem;margin-bottom:2rem;position:relative;overflow:hidden;animation:fadeUp .4s ease both;transition:background .3s,border-color .3s}
    .welcome-banner::before{content:'';position:absolute;right:-60px;top:-60px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(245,166,35,.12) 0%,transparent 70%)}
    .welcome-banner::after{content:'';position:absolute;right:80px;bottom:-80px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(224,92,42,.07) 0%,transparent 70%)}
    .wb-inner{display:flex;align-items:center;justify-content:space-between;gap:1.5rem;position:relative;z-index:1;flex-wrap:wrap}
    .wb-text h2{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;letter-spacing:-.02em;margin-bottom:.3rem}
    .wb-text h2 span{color:var(--accent)}
    .wb-text p{font-size:.88rem;color:var(--muted);max-width:44ch;line-height:1.6}
    .wb-cta{display:flex;gap:.65rem;flex-wrap:wrap}

    /* Stats grid */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem}
    .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.3rem;animation:fadeUp .4s ease both;transition:border-color .2s,transform .2s,background .3s}
    .stat-card:hover{border-color:rgba(245,166,35,.3);transform:translateY(-2px)}
    .stat-icon{width:34px;height:34px;border-radius:9px;background:rgba(245,166,35,.1);display:flex;align-items:center;justify-content:center;color:var(--accent);margin-bottom:.85rem}
    .stat-value{font-family:'Syne',sans-serif;font-size:1.55rem;font-weight:700;letter-spacing:-.03em;margin-bottom:.15rem}
    .stat-label{font-size:.75rem;color:var(--muted)}
    .stat-empty{font-family:'Syne',sans-serif;font-size:1.55rem;font-weight:700;color:var(--border)}

    /* Quick actions */
    .quick-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;animation:fadeUp .4s ease both;animation-delay:.18s}
    .qa-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;display:flex;flex-direction:column;align-items:center;gap:.55rem;cursor:pointer;text-align:center;transition:all .2s;text-decoration:none;color:inherit}
    .qa-card:hover{border-color:rgba(245,166,35,.35);background:var(--surface2);transform:translateY(-2px)}
    .qa-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem}
    .qa-label{font-size:.8rem;font-weight:500;color:var(--muted)}

    /* Two-col */
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem}
    .section-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;animation:fadeUp .4s ease both;animation-delay:.24s;transition:background .3s,border-color .3s}
    .section-head{padding:1.1rem 1.4rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .section-head h3{font-family:'Syne',sans-serif;font-size:.92rem;font-weight:700}
    .section-head a{font-size:.78rem;color:var(--accent);text-decoration:none}
    .section-head a:hover{text-decoration:underline}

    /* Section empty state */
    .section-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2.5rem 1.5rem;text-align:center;gap:.65rem;color:var(--muted)}
    .section-empty svg{opacity:.4}
    .section-empty p{font-size:.82rem;max-width:22ch;line-height:1.6}
    .section-empty a{font-size:.82rem;color:var(--accent);text-decoration:none;font-weight:500}
    .section-empty a:hover{text-decoration:underline}

    /* Skill/booking rows */
    .skill-row,.booking-row{display:flex;align-items:center;gap:.85rem;padding:.95rem 1.4rem;border-bottom:1px solid var(--border);transition:background .15s}
    .skill-row:last-child,.booking-row:last-child{border-bottom:none}
    .skill-row:hover,.booking-row:hover{background:var(--surface2)}
    .skill-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .skill-info,.booking-info{flex:1;min-width:0}
    .skill-title,.booking-client{font-size:.88rem;font-weight:500;margin-bottom:.1rem}
    .skill-meta,.booking-detail{font-size:.74rem;color:var(--muted)}
    .skill-price{font-size:.82rem;font-weight:600;color:var(--accent);white-space:nowrap}
    .status-badge{font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:.2rem .55rem;border-radius:20px}
    .status-active{background:rgba(52,201,122,.12);color:var(--green)}
    .status-draft{background:rgba(107,117,146,.12);color:var(--muted)}
    .status-pending{background:rgba(245,166,35,.12);color:var(--accent)}
    .status-confirmed{background:rgba(74,144,226,.12);color:var(--blue)}
    .booking-av{width:30px;height:30px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--accent);flex-shrink:0}

    /* Profile completion nudge */
    .nudge{background:linear-gradient(135deg,rgba(245,166,35,.08) 0%,rgba(224,92,42,.05) 100%);border:1px solid rgba(245,166,35,.2);border-radius:var(--radius);padding:1.2rem 1.4rem;margin-bottom:2rem;display:flex;align-items:center;gap:1rem;animation:fadeUp .4s ease both;animation-delay:.1s;flex-wrap:wrap}
    .nudge-icon{font-size:1.6rem;flex-shrink:0}
    .nudge-text{flex:1}
    .nudge-text strong{font-family:'Syne',sans-serif;font-size:.9rem;font-weight:700;display:block;margin-bottom:.2rem}
    .nudge-text p{font-size:.8rem;color:var(--muted)}

    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
    @media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}.quick-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:800px){.sidebar{display:none}.main{margin-left:0}.two-col{grid-template-columns:1fr}}
    @media(max-width:480px){.stats-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<script>document.documentElement.setAttribute('data-theme',localStorage.getItem('umu_theme')||'dark');</script>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">Umu <div class="dot"></div></div>
    <div class="logo-sub">Skill Marketplace</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item active"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="skills.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>My Skills</a>
    <a href="bookings.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Bookings</a>
    <a href="browse.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse Skills</a>
    <a href="messages.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages</a>
  
    <div class="nav-section">Account</div>
    <a href="profile.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile</a>
    <a href="settings.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 21 12h-1"/></svg>Settings</a>
    <a href="logout.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Log Out</a>
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

<div class="main">
  <header class="topbar">
    <div class="topbar-title">
      <h1>Dashboard</h1>
      <p><?= date('l, F j, Y') ?></p>
    </div>
    <div class="topbar-actions">
      <a href="browse.php" class="btn btn-ghost btn-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse
      </a>
      <a href="skills.php" class="btn btn-primary btn-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        List a Skill
      </a>
    </div>
  </header>

  <div class="content">

    <!-- Welcome banner -->
    <div class="welcome-banner">
      <div class="wb-inner">
        <div class="wb-text">
          <h2><?= $greeting ?>, <span><?= $user_name ?> <?= $is_new ? '👋' : '⚡' ?></span></h2>
          <?php if ($is_new): ?>
          <p>Welcome to Umu! Your dashboard is ready — start by listing your first skill or browsing what others offer.</p>
          <?php else: ?>
          <p>Here's a snapshot of your activity on Umu today.</p>
          <?php endif; ?>
        </div>
        <div class="wb-cta">
          <?php if ($is_new): ?>
          <a href="skills.php" class="btn btn-primary btn-sm">List My First Skill</a>
          <a href="browse.php" class="btn btn-ghost btn-sm">Explore Marketplace</a>
          <?php else: ?>
          <a href="browse.php" class="btn btn-ghost btn-sm">View Marketplace →</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Profile completion nudge (only for new users) -->
    <?php if ($is_new): ?>
    <div class="nudge">
      <div class="nudge-icon">🪪</div>
      <div class="nudge-text">
        <strong>Complete your profile</strong>
        <p>Add a photo, bio and location so clients can trust and find you faster.</p>
      </div>
      <a href="profile.php" class="btn btn-ghost btn-sm">Complete Profile →</a>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <?php
      $stat_defs = [
        ['Skills Listed', $stats['skills'],   'layers',   'List your first skill'],
        ['Bookings',       $stats['bookings'], 'calendar', 'No bookings yet'],
        ['UGX Earned',     $stats['earned'],   'wallet',   'Start earning today'],
        ['Avg Rating',     $stats['rating'],   'star',     'Ratings appear after reviews'],
      ];
      foreach ($stat_defs as $i => [$label, $val, $icon, $empty_hint]): ?>
      <div class="stat-card" style="animation-delay:<?= $i*.06 ?>s">
        <div class="stat-icon">
          <?php if ($icon === 'layers'): ?><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
          <?php elseif ($icon === 'calendar'): ?><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <?php elseif ($icon === 'wallet'): ?><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 13a1 1 0 1 0 2 0 1 1 0 0 0-2 0z" fill="currentColor"/></svg>
          <?php else: ?><svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" style="color:var(--accent)"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" opacity=".85"/></svg>
          <?php endif; ?>
        </div>
        <?php if ($val > 0): ?>
          <div class="stat-value"><?= $label === 'UGX Earned' ? number_format($val) : ($label === 'Avg Rating' ? number_format($val,1).'★' : $val) ?></div>
        <?php else: ?>
          <div class="stat-empty">—</div>
        <?php endif; ?>
        <div class="stat-label"><?= $val > 0 ? $label : $empty_hint ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Quick actions -->
    <div class="quick-grid">
      <a href="skills.php" class="qa-card">
        <div class="qa-icon" style="background:rgba(245,166,35,.1)">🎯</div>
        <div class="qa-label">List a Skill</div>
      </a>
      <a href="browse.php" class="qa-card">
        <div class="qa-icon" style="background:rgba(74,144,226,.1)">🔍</div>
        <div class="qa-label">Browse Skills</div>
      </a>
      <a href="messages.php" class="qa-card">
        <div class="qa-icon" style="background:rgba(52,201,122,.1)">💬</div>
        <div class="qa-label">Messages</div>
      </a>
      
    </div>

    <!-- My skills + Recent bookings -->
    <div class="two-col">

      <!-- My Skills -->
      <div class="section-card">
        <div class="section-head">
          <h3>My Skills</h3>
          <a href="skills.php">Manage →</a>
        </div>
        <?php if (empty($my_skills)): ?>
        <div class="section-empty">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          <p>You haven't listed any skills yet.</p>
          <a href="skills.php">+ Add your first skill</a>
        </div>
        <?php else: ?>
          <?php
          $cat_colors = ['Technology'=>'#3b6ef0','Creative'=>'#c8522a','Education'=>'#34c97a','Business'=>'#f5a623'];
          foreach ($my_skills as $s):
            $cc = $cat_colors[$s['category']] ?? '#8a8070';
          ?>
          <div class="skill-row">
            <div class="skill-dot" style="background:<?= $cc ?>"></div>
            <div class="skill-info">
              <div class="skill-title"><?= htmlspecialchars($s['title']) ?></div>
              <div class="skill-meta"><?= $s['category'] ?> · <?= $s['bookings'] ?> bookings</div>
            </div>
            <div class="skill-price">UGX <?= $s['price'] ?></div>
            <span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Recent bookings -->
      <div class="section-card">
        <div class="section-head">
          <h3>Recent Bookings</h3>
          <a href="bookings.php">View all →</a>
        </div>
        <?php if (empty($recent_bookings)): ?>
        <div class="section-empty">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <p>No bookings yet. Once someone books you, they'll appear here.</p>
          <a href="browse.php">Explore the marketplace</a>
        </div>
        <?php else: ?>
          <?php foreach ($recent_bookings as $b):
            $bi = strtoupper(substr($b['client'], 0, 1));
          ?>
          <div class="booking-row">
            <div class="booking-av"><?= $bi ?></div>
            <div class="booking-info">
              <div class="booking-client"><?= htmlspecialchars($b['client']) ?></div>
              <div class="booking-detail"><?= htmlspecialchars($b['skill']) ?></div>
            </div>
            <span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>

  </div><!-- /content -->
</div><!-- /main -->
</body>
</html>
