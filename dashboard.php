<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_name  = htmlspecialchars($_SESSION['name']  ?? 'Learner');
$user_email = htmlspecialchars($_SESSION['user']  ?? '');
$initials   = strtoupper(substr($user_name, 0, 1));

// --- Demo data (replace with DB queries) ---
$stats = [
    ['label' => 'Skills Listed',    'value' => '4',    'icon' => 'layers',   'delta' => '+1 this month'],
    ['label' => 'Active Bookings',  'value' => '3',    'icon' => 'calendar', 'delta' => '2 upcoming'],
    ['label' => 'Total Earned',     'value' => 'UGX 320,000', 'icon' => 'wallet',   'delta' => '+12% vs last month'],
    ['label' => 'Rating',           'value' => '4.8',  'icon' => 'star',     'delta' => 'from 24 reviews'],
];

$skills = [
    ['title' => 'Web Development',     'category' => 'Technology', 'price' => '50,000', 'bookings' => 8,  'status' => 'active'],
    ['title' => 'Graphic Design',      'category' => 'Creative',   'price' => '35,000', 'bookings' => 5,  'status' => 'active'],
    ['title' => 'English Tutoring',    'category' => 'Education',  'price' => '20,000', 'bookings' => 11, 'status' => 'active'],
    ['title' => 'Photography',         'category' => 'Creative',   'price' => '80,000', 'bookings' => 2,  'status' => 'draft'],
];

$bookings = [
    ['client' => 'Amara Osei',     'skill' => 'Web Development',  'date' => 'Today, 2:00 PM',    'status' => 'confirmed'],
    ['client' => 'Liam Nakato',    'skill' => 'English Tutoring', 'date' => 'Tomorrow, 10:00 AM', 'status' => 'pending'],
    ['client' => 'Fatima Diallo',  'skill' => 'Graphic Design',   'date' => 'Apr 15, 9:00 AM',   'status' => 'confirmed'],
];

$category_colors = [
    'Technology' => '#3b6ef0',
    'Creative'   => '#c8522a',
    'Education'  => '#2a7a4b',
    'Business'   => '#7a4b2a',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0b0f1a;
      --surface:   #141927;
      --surface2:  #1c2438;
      --border:    #252d42;
      --accent:    #f5a623;
      --accent2:   #e05c2a;
      --text:      #e8eaf0;
      --muted:     #6b7592;
      --green:     #34c97a;
      --blue:      #4a90e2;
      --radius:    14px;
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Instrument Sans', sans-serif;
      min-height: 100vh;
      display: flex;
    }

    /* ── Sidebar ── */
    .sidebar {
      width: 240px;
      min-height: 100vh;
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 100;
    }

    .sidebar-logo {
      padding: 1.8rem 1.5rem 1.4rem;
      border-bottom: 1px solid var(--border);
    }

    .logo-mark {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.4rem;
      letter-spacing: -.03em;
      display: flex;
      align-items: center;
      gap: .4rem;
    }

    .logo-mark .dot {
      width: 8px; height: 8px;
      background: var(--accent);
      border-radius: 50%;
      display: inline-block;
    }

    .logo-sub {
      font-size: .68rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--muted);
      margin-top: .2rem;
    }

    .sidebar-nav {
      flex: 1;
      padding: 1.2rem .75rem;
    }

    .nav-section {
      font-size: .65rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--muted);
      padding: .8rem .75rem .4rem;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .65rem .75rem;
      border-radius: 9px;
      color: var(--muted);
      font-size: .88rem;
      font-weight: 400;
      cursor: pointer;
      transition: all .18s;
      text-decoration: none;
      margin-bottom: .1rem;
    }

    .nav-item:hover { background: var(--surface2); color: var(--text); }

    .nav-item.active {
      background: rgba(245,166,35,.1);
      color: var(--accent);
      font-weight: 500;
    }

    .nav-item .badge {
      margin-left: auto;
      background: var(--accent);
      color: #000;
      font-size: .65rem;
      font-weight: 700;
      padding: .15rem .45rem;
      border-radius: 20px;
    }

    .sidebar-footer {
      border-top: 1px solid var(--border);
      padding: 1rem .75rem;
    }

    .user-pill {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .6rem .75rem;
      border-radius: 9px;
      cursor: pointer;
      transition: background .18s;
    }

    .user-pill:hover { background: var(--surface2); }

    .avatar {
      width: 34px; height: 34px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: .85rem;
      color: #fff;
      flex-shrink: 0;
    }

    .user-info { overflow: hidden; }

    .user-name {
      font-size: .85rem;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .user-email {
      font-size: .72rem;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ── Main ── */
    .main {
      margin-left: 240px;
      flex: 1;
      min-height: 100vh;
    }

    /* ── Topbar ── */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.4rem 2rem;
      border-bottom: 1px solid var(--border);
      background: var(--bg);
      position: sticky;
      top: 0;
      z-index: 50;
    }

    .topbar-title h1 {
      font-family: 'Syne', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      letter-spacing: -.02em;
    }

    .topbar-title p {
      font-size: .8rem;
      color: var(--muted);
      margin-top: .1rem;
    }

    .topbar-actions { display: flex; align-items: center; gap: .75rem; }

    .btn {
      padding: .6rem 1.2rem;
      border-radius: 8px;
      font-family: 'Instrument Sans', sans-serif;
      font-size: .85rem;
      font-weight: 500;
      cursor: pointer;
      border: none;
      transition: all .18s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
    }

    .btn-ghost {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--muted);
    }

    .btn-ghost:hover { border-color: var(--muted); color: var(--text); }

    .btn-primary {
      background: var(--accent);
      color: #0b0f1a;
      font-weight: 600;
    }

    .btn-primary:hover { background: #f0b840; }

    .notif-btn {
      width: 36px; height: 36px;
      border-radius: 8px;
      background: var(--surface);
      border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      position: relative;
      color: var(--muted);
      transition: all .18s;
    }

    .notif-btn:hover { color: var(--text); border-color: var(--muted); }

    .notif-dot {
      position: absolute;
      top: 6px; right: 6px;
      width: 7px; height: 7px;
      background: var(--accent);
      border-radius: 50%;
      border: 2px solid var(--bg);
    }

    /* ── Content ── */
    .content { padding: 2rem; }

    /* Welcome Banner */
    .welcome-banner {
      background: linear-gradient(135deg, #1a2240 0%, #1c1a30 100%);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.8rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
      animation: fadeUp .4s ease both;
    }

    .welcome-banner::before {
      content: '';
      position: absolute;
      right: -60px; top: -60px;
      width: 250px; height: 250px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(245,166,35,.12) 0%, transparent 70%);
    }

    .welcome-banner::after {
      content: '';
      position: absolute;
      right: 80px; bottom: -80px;
      width: 180px; height: 180px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(224,92,42,.08) 0%, transparent 70%);
    }

    .welcome-text h2 {
      font-family: 'Syne', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: .3rem;
    }

    .welcome-text h2 span { color: var(--accent); }

    .welcome-text p { font-size: .88rem; color: var(--muted); }

    .welcome-cta { position: relative; z-index: 1; }

    /* Stats grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.4rem;
      animation: fadeUp .4s ease both;
      transition: border-color .2s, transform .2s;
    }

    .stat-card:hover { border-color: rgba(245,166,35,.3); transform: translateY(-2px); }

    .stat-card:nth-child(1) { animation-delay: .05s; }
    .stat-card:nth-child(2) { animation-delay: .1s;  }
    .stat-card:nth-child(3) { animation-delay: .15s; }
    .stat-card:nth-child(4) { animation-delay: .2s;  }

    .stat-icon {
      width: 36px; height: 36px;
      border-radius: 9px;
      background: rgba(245,166,35,.12);
      display: flex; align-items: center; justify-content: center;
      color: var(--accent);
      margin-bottom: 1rem;
    }

    .stat-value {
      font-family: 'Syne', sans-serif;
      font-size: 1.6rem;
      font-weight: 700;
      letter-spacing: -.03em;
      margin-bottom: .2rem;
    }

    .stat-label { font-size: .78rem; color: var(--muted); margin-bottom: .4rem; }

    .stat-delta {
      font-size: .72rem;
      color: var(--green);
      display: flex;
      align-items: center;
      gap: .25rem;
    }

    /* Two column layout */
    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    /* Section card */
    .section-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      animation: fadeUp .4s ease both;
      animation-delay: .25s;
    }

    .section-head {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .section-head h3 {
      font-family: 'Syne', sans-serif;
      font-size: .95rem;
      font-weight: 700;
    }

    .section-head a {
      font-size: .78rem;
      color: var(--accent);
      text-decoration: none;
    }

    .section-head a:hover { text-decoration: underline; }

    /* Skill rows */
    .skill-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.5rem;
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }

    .skill-row:last-child { border-bottom: none; }
    .skill-row:hover { background: var(--surface2); }

    .skill-cat-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .skill-info { flex: 1; min-width: 0; }

    .skill-title {
      font-size: .88rem;
      font-weight: 500;
      margin-bottom: .15rem;
    }

    .skill-meta {
      font-size: .74rem;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    .skill-price {
      font-size: .82rem;
      font-weight: 600;
      color: var(--accent);
      white-space: nowrap;
    }

    .skill-status {
      font-size: .68rem;
      font-weight: 600;
      letter-spacing: .05em;
      text-transform: uppercase;
      padding: .2rem .55rem;
      border-radius: 20px;
    }

    .status-active  { background: rgba(52,201,122,.12); color: var(--green); }
    .status-draft   { background: rgba(107,117,146,.12); color: var(--muted); }
    .status-pending { background: rgba(245,166,35,.12);  color: var(--accent); }
    .status-confirmed { background: rgba(74,144,226,.12); color: var(--blue); }

    /* Booking rows */
    .booking-row {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.5rem;
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }

    .booking-row:last-child { border-bottom: none; }
    .booking-row:hover { background: var(--surface2); }

    .booking-avatar {
      width: 32px; height: 32px;
      border-radius: 50%;
      background: var(--surface2);
      display: flex; align-items: center; justify-content: center;
      font-size: .75rem;
      font-weight: 700;
      color: var(--accent);
      flex-shrink: 0;
      border: 1px solid var(--border);
    }

    .booking-info { flex: 1; min-width: 0; }

    .booking-client {
      font-size: .88rem;
      font-weight: 500;
      margin-bottom: .12rem;
    }

    .booking-detail {
      font-size: .74rem;
      color: var(--muted);
    }

    .booking-date {
      font-size: .74rem;
      color: var(--muted);
      white-space: nowrap;
      text-align: right;
    }

    /* Quick actions */
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
      animation: fadeUp .4s ease both;
      animation-delay: .3s;
    }

    .action-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.2rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .6rem;
      cursor: pointer;
      text-align: center;
      transition: all .2s;
      text-decoration: none;
      color: inherit;
    }

    .action-card:hover {
      border-color: rgba(245,166,35,.35);
      background: var(--surface2);
      transform: translateY(-2px);
    }

    .action-icon {
      width: 44px; height: 44px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem;
    }

    .action-label {
      font-size: .8rem;
      font-weight: 500;
      color: var(--muted);
    }

    /* Animations */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: none; }
    }

    /* Responsive */
    @media (max-width: 1100px) {
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
      .quick-actions { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 800px) {
      .sidebar { display: none; }
      .main { margin-left: 0; }
      .two-col { grid-template-columns: 1fr; }
      .welcome-banner { flex-direction: column; align-items: flex-start; gap: 1rem; }
    }

    @media (max-width: 520px) {
      .stats-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">Umu <div class="dot"></div></div>
    <div class="logo-sub">Skill Marketplace</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>

    <a href="dashboard.php" class="nav-item active">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="skills.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      My Skills
      <span class="badge">4</span>
    </a>
    <a href="booking.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Bookings
      <span class="badge">3</span>
    </a>
    <a href="browse.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Browse Skills
    </a>

    <div class="nav-section">Account</div>

    <a href="#" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
    <a href="#" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Messages
    </a>
    <a href="#" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 21 12h-1"/></svg>
      Settings
    </a>
    <a href="login.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Log Out
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="avatar"><?= $initials ?></div>
      <div class="user-info">
        <div class="user-name"><?= $user_name ?></div>
        <div class="user-email"><?= $user_email ?></div>
      </div>
    </div>
  </div>
</aside>

<!-- ── Main ── -->
<div class="main">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-title">
      <h1>Dashboard</h1>
      <p><?= date('l, F j, Y') ?></p>
    </div>
    <div class="topbar-actions">
      <div class="notif-btn">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
      <a href="#" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        List a Skill
      </a>
    </div>
  </header>

  <!-- Content -->
  <div class="content">

    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div class="welcome-text">
        <h2>Good <?= (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening') ?>, <span><?= $user_name ?> 👋</span></h2>
        <p>Here's what's happening with your skills on Umu today.</p>
      </div>
      <div class="welcome-cta">
        <a href="#" class="btn btn-ghost">View Marketplace →</a>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <?php foreach ($stats as $stat): ?>
      <div class="stat-card">
        <div class="stat-icon">
          <?php if ($stat['icon'] === 'layers'): ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
          <?php elseif ($stat['icon'] === 'calendar'): ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <?php elseif ($stat['icon'] === 'wallet'): ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 13a1 1 0 1 0 2 0 1 1 0 0 0-2 0z" fill="currentColor"/></svg>
          <?php else: ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <?php endif; ?>
        </div>
        <div class="stat-value"><?= $stat['value'] ?></div>
        <div class="stat-label"><?= $stat['label'] ?></div>
        <div class="stat-delta">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
          <?= $stat['delta'] ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <a href="#" class="action-card">
        <div class="action-icon" style="background:rgba(245,166,35,.1);">🎯</div>
        <div class="action-label">List New Skill</div>
      </a>
      <a href="#" class="action-card">
        <div class="action-icon" style="background:rgba(74,144,226,.1);">🔍</div>
        <div class="action-label">Browse Skills</div>
      </a>
      <a href="#" class="action-card">
        <div class="action-icon" style="background:rgba(52,201,122,.1);">💬</div>
        <div class="action-label">Messages</div>
      </a>
      <a href="#" class="action-card">
        <div class="action-icon" style="background:rgba(224,92,42,.1);">📊</div>
        <div class="action-label">Earnings Report</div>
      </a>
    </div>

    <!-- Skills + Bookings -->
    <div class="two-col">

      <!-- My Skills -->
      <div class="section-card">
        <div class="section-head">
          <h3>My Skills</h3>
          <a href="#">Manage all →</a>
        </div>
        <?php foreach ($skills as $skill):
          $cat_color = $category_colors[$skill['category']] ?? '#8a8070';
        ?>
        <div class="skill-row">
          <div class="skill-cat-dot" style="background:<?= $cat_color ?>"></div>
          <div class="skill-info">
            <div class="skill-title"><?= htmlspecialchars($skill['title']) ?></div>
            <div class="skill-meta">
              <span><?= htmlspecialchars($skill['category']) ?></span>
              <span>·</span>
              <span><?= $skill['bookings'] ?> bookings</span>
            </div>
          </div>
          <div class="skill-price">UGX <?= $skill['price'] ?></div>
          <div class="skill-status status-<?= $skill['status'] ?>"><?= ucfirst($skill['status']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Upcoming Bookings -->
      <div class="section-card">
        <div class="section-head">
          <h3>Upcoming Bookings</h3>
          <a href="#">View all →</a>
        </div>
        <?php foreach ($bookings as $booking):
          $bi = strtoupper(substr($booking['client'], 0, 1));
        ?>
        <div class="booking-row">
          <div class="booking-avatar"><?= $bi ?></div>
          <div class="booking-info">
            <div class="booking-client"><?= htmlspecialchars($booking['client']) ?></div>
            <div class="booking-detail"><?= htmlspecialchars($booking['skill']) ?></div>
          </div>
          <div>
            <div class="booking-date"><?= $booking['date'] ?></div>
            <div style="text-align:right;margin-top:.3rem;">
              <span class="skill-status status-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>

  </div><!-- /content -->
</div><!-- /main -->

</body>
</html>