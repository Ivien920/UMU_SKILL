<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user_name  = htmlspecialchars($_SESSION['name']  ?? 'Learner');
$user_email = htmlspecialchars($_SESSION['user']  ?? '');
$initials   = strtoupper(substr($user_name, 0, 1));
// --- Demo data (replace with DB queries) --
$profile = [
    'name'       => $user_name,
    'email'      => $user_email,
    'bio'        => $user_bio,
    'location'   => $user_location,
    'phone'      => $user_phone,
    'member_since' => $user_created_at,
    'website'    => $user_website,
    'languages'  => ['English', 'Luganda', 'Swahili'],
];
$stats = [
    ['label' => 'Skills Listed',  'value' => '4'],
    ['label' => 'Total Bookings', 'value' => '26'],
    ['label' => 'Reviews',        'value' => '24'],
    ['label' => 'Rating',         'value' => '4.8 ★'],
];
$skills = [
    ['title' => 'Web Development',  'category' => 'Technology', 'price' => '50,000', 'bookings' => 8,  'status' => 'active'],
    ['title' => 'Graphic Design',   'category' => 'Creative',   'price' => '35,000', 'bookings' => 5,  'status' => 'active'],
    ['title' => 'English Tutoring', 'category' => 'Education',  'price' => '20,000', 'bookings' => 11, 'status' => 'active'],
    ['title' => 'Photography',      'category' => 'Creative',   'price' => '80,000', 'bookings' => 2,  'status' => 'draft'],
];
$reviews = [
    ['author' => 'Amara Osei',    'rating' => 5, 'skill' => 'Web Development',  'text' => 'Absolutely incredible work. Delivered the project ahead of schedule with great attention to detail.', 'date' => '2 days ago'],
    ['author' => 'Liam Nakato',   'rating' => 5, 'skill' => 'English Tutoring', 'text' => 'Very patient and knowledgeable teacher. My English improved significantly after just three sessions.', 'date' => '1 week ago'],
    ['author' => 'Fatima Diallo', 'rating' => 4, 'skill' => 'Graphic Design',   'text' => 'Great design sense and quick turnaround. Would definitely book again for future projects.', 'date' => '2 weeks ago'],
];
$category_colors = [
    'Technology' => '#3b6ef0',
    'Creative'   => '#c8522a',
    'Education'  => '#2a7a4b',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile — Umu Skill Marketplace</title>
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
    /* 
── Sidebar ── */
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
    .sidebar-nav { flex: 1; padding: 1.2rem .75rem; }
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
    /* 
── Main ── */
    .main { margin-left: 240px; flex: 1; min-height: 100vh; }
    /* 
── Topbar ── */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.4rem 2rem;
      border-bottom: 1px solid var(--border);
      background: var(--bg);
      position: sticky;
    }
      top: 0;
      z-index: 50;
    .topbar-title h1 {
      font-family: 'Syne', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      letter-spacing: -.02em;
    }
    .topbar-title p { font-size: .8rem; color: var(--muted); margin-top: .1rem; }
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
    /* 
── Content ── */
    .content { padding: 2rem; }
    /* 
── Profile Hero ── */
    .profile-hero {
      background: linear-gradient(135deg, #1a2240 0%, #1c1a30 100%);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2rem;
      margin-bottom: 1.5rem;
      position: relative;
      overflow: hidden;
      animation: fadeUp .4s ease both;
    }
    .profile-hero::before {
      content: '';
      position: absolute;
      right: -80px; top: -80px;
      width: 300px; height: 300px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(245,166,35,.1) 0%, transparent 70%);
    }
    .profile-hero::after {
      content: '';
      position: absolute;
      right: 100px; bottom: -100px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(224,92,42,.07) 0%, transparent 70%);
    }
    .profile-hero-inner {
      display: flex;
      align-items: flex-start;
      gap: 1.8rem;
      position: relative;
      z-index: 1;
    }
    .profile-avatar-large {
      width: 88px; height: 88px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 2rem;
      color: #fff;
      flex-shrink: 0;
      border: 3px solid rgba(245,166,35,.3);
      position: relative;
    }
    .online-dot {
      position: absolute;
      bottom: 4px; right: 4px;
      width: 14px; height: 14px;
      background: var(--green);
      border-radius: 50%;
      border: 2px solid #1a2240;
    }
    .profile-meta { flex: 1; }
    .profile-name {
      font-family: 'Syne', sans-serif;
      font-size: 1.6rem;
      font-weight: 800;
    }
      letter-spacing: -.03em;
      margin-bottom: .3rem;
    .profile-badge {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      background: rgba(245,166,35,.12);
      color: var(--accent);
      font-size: .72rem;
      font-weight: 600;
      letter-spacing: .05em;
      text-transform: uppercase;
      padding: .25rem .65rem;
      border-radius: 20px;
      border: 1px solid rgba(245,166,35,.25);
      margin-bottom: .75rem;
    }
    .profile-bio {
      font-size: .9rem;
      color: var(--muted);
      line-height: 1.65;
      max-width: 560px;
      margin-bottom: 1rem;
    }
    .profile-meta-row {
      display: flex;
      flex-wrap: wrap;
      gap: 1.2rem;
    }
    .profile-meta-item {
      display: flex;
      align-items: center;
      gap: .4rem;
      font-size: .8rem;
      color: var(--muted);
    }
    .profile-meta-item svg { color: var(--accent); flex-shrink: 0; }
    .profile-actions {
      display: flex;
      flex-direction: column;
    }
      gap: .6rem;
      flex-shrink: 0;
    /* 
── Profile Stats ── */
    .profile-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .pstat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.3rem 1.4rem;
      text-align: center;
      animation: fadeUp .4s ease both;
      transition: border-color .2s, transform .2s;
    }
    .pstat-card:hover { border-color: rgba(245,166,35,.3); transform: translateY(-2px); }
    .pstat-card:nth-child(1) { animation-delay: .05s; }
    .pstat-card:nth-child(2) { animation-delay: .1s; }
    .pstat-card:nth-child(3) { animation-delay: .15s; }
    .pstat-card:nth-child(4) { animation-delay: .2s; }
    .pstat-value {
      font-family: 'Syne', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -.03em;
      color: var(--accent);
      margin-bottom: .25rem;
    }
    .pstat-label { font-size: .76rem; color: var(--muted); }
    /* 
── Two column layout ── */
    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }
    /* 
── Section card ── */
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
    .section-head a { font-size: .78rem; color: var(--accent); text-decoration: none; }
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
    .skill-cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .skill-info { flex: 1; min-width: 0; }
    .skill-title { font-size: .88rem; font-weight: 500; margin-bottom: .15rem; }
    .skill-meta {
      font-size: .74rem;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .skill-price { font-size: .82rem; font-weight: 600; color: var(--accent); white-space: nowrap; }
    .skill-status {
      font-size: .68rem;
      font-weight: 600;
      letter-spacing: .05em;
      text-transform: uppercase;
      padding: .2rem .55rem;
      border-radius: 20px;
    }
    .status-active   { background: rgba(52,201,122,.12);  color: var(--green); }
    .status-draft    { background: rgba(107,117,146,.12); color: var(--muted); }
    .status-pending  { background: rgba(245,166,35,.12);  color: var(--accent); }
    .status-confirmed { background: rgba(74,144,226,.12); color: var(--blue); }
    /* 
── About card ── */
    .about-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      animation: fadeUp .4s ease both;
      animation-delay: .2s;
    }
    .about-card h3 {
      font-family: 'Syne', sans-serif;
      font-size: .95rem;
      font-weight: 700;
      margin-bottom: 1rem;
      padding-bottom: .75rem;
      border-bottom: 1px solid var(--border);
    }
    .about-row {
      display: flex;
      align-items: flex-start;
      gap: .75rem;
      padding: .65rem 0;
      border-bottom: 1px solid var(--border);
    }
    .about-row:last-child { border-bottom: none; padding-bottom: 0; }
    .about-row-icon {
      width: 32px; height: 32px;
      border-radius: 8px;
      background: rgba(245,166,35,.1);
      display: flex; align-items: center; justify-content: center;
      color: var(--accent);
      flex-shrink: 0;
      margin-top: .1rem;
    }
    .about-row-label { font-size: .72rem; color: var(--muted); margin-bottom: .15rem; }
    .about-row-value { font-size: .87rem; font-weight: 500; }
    .lang-tags { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .3rem; }
    .lang-tag {
      font-size: .72rem;
      padding: .2rem .55rem;
      border-radius: 20px;
      background: rgba(74,144,226,.12);
      color: var(--blue);
      font-weight: 500;
    }
    /* 
── Reviews ── */
    .reviews-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      animation: fadeUp .4s ease both;
      animation-delay: .3s;
    }
    .review-row {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid var(--border);
    }
    .review-row:last-child { border-bottom: none; }
    .review-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: .6rem;
    }
    .review-author-wrap { display: flex; align-items: center; gap: .65rem; }
    .review-avatar {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--surface2);
      display: flex; align-items: center; justify-content: center;
      font-size: .72rem;
      font-weight: 700;
      color: var(--accent);
      flex-shrink: 0;
      border: 1px solid var(--border);
    }
    .review-author { font-size: .86rem; font-weight: 500; }
    .review-skill-tag { font-size: .72rem; color: var(--muted); }
    .review-meta { text-align: right; }
    .review-stars { color: var(--accent); font-size: .85rem; letter-spacing: .05em; }
    .review-date  { font-size: .7rem; color: var(--muted); margin-top: .1rem; }
    .review-text { font-size: .85rem; color: var(--muted); line-height: 1.6; }
    /* Animations */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: none; }
    }
    /* Responsive */
    @media (max-width: 1100px) {
      .profile-stats { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 900px) {
      .profile-hero-inner { flex-direction: column; }
      .profile-actions { flex-direction: row; }
      .two-col { grid-template-columns: 1fr; }
    }
    @media (max-width: 800px) {
    }
      .sidebar { display: none; }
      .main { margin-left: 0; }
    @media (max-width: 520px) {
      .profile-stats { grid-template-columns: repeat(2, 1fr); }
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
    <a href="dashboard.php" class="nav-item">
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
    <a href="profile.php" class="nav-item active">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
    <a href="#" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Messages
    </a>
    <a href="settings.php" class="nav-item">
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
      <h1>Profile</h1>
      <p>Your public profile on Umu</p>
    </div>
    <div class="topbar-actions">
      <div class="notif-btn">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
      <a href="settings.php" class="btn btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 21 12h-1"/></svg>
        Edit Profile
      </a>
      <a href="#" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        List a Skill
      </a>
    </div>
  </header>
  <!-- Content -->
  <div class="content">
    <!-- Profile Hero -->
    <div class="profile-hero">
      <div class="profile-hero-inner">
        <div class="profile-avatar-large">
          <?= $initials ?>
          <div class="online-dot"></div>
        </div>
        <div class="profile-meta">
          <div class="profile-name"><?= $user_name ?></div>
          <div class="profile-badge">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Verified Seller
          </div>
          <div class="profile-bio"><?= htmlspecialchars($profile['bio']) ?></div>
          <div class="profile-meta-row">
            <div class="profile-meta-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= htmlspecialchars($profile['location']) ?>
            </div>
            <div class="profile-meta-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              Member since <?= htmlspecialchars($profile['member_since']) ?>
            </div>
            <div class="profile-meta-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              <?= htmlspecialchars($profile['website']) ?>
            </div>
          </div>
        </div>
        <div class="profile-actions">
          <a href="settings.php" class="btn btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Profile
          </a>
          <a href="#" class="btn btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            Share Profile
          </a>
        </div>
      </div>
    </div>
    <!-- Profile Stats -->
    <div class="profile-stats">
      <?php foreach ($stats as $s): ?>
      <div class="pstat-card">
        <div class="pstat-value"><?= $s['value'] ?></div>
        <div class="pstat-label"><?= $s['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Skills + About -->
    <div class="two-col">
      <!-- My Skills -->
      <div class="section-card">
        <div class="section-head">
          <h3>My Skills</h3>
          <a href="skills.php">Manage all →</a>
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
      <!-- About -->
      <div class="about-card">
        <h3>About</h3>
        <div class="about-row">
          <div class="about-row-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div>
            <div class="about-row-label">Email</div>
            <div class="about-row-value"><?= $user_email ?></div>
          </div>
        </div>
        <div class="about-row">
          <div class="about-row-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.92 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </div>
          <div>
            <div class="about-row-label">Phone</div>
            <div class="about-row-value"><?= htmlspecialchars($profile['phone']) ?></div>
          </div>
        </div>
        <div class="about-row">
          <div class="about-row-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div>
            <div class="about-row-label">Location</div>
            <div class="about-row-value"><?= htmlspecialchars($profile['location']) ?></div>
          </div>
        </div>
        <div class="about-row">
          <div class="about-row-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          </div>
          <div>
            <div class="about-row-label">Website</div>
            <div class="about-row-value"><?= htmlspecialchars($profile['website']) ?></div>
          </div>
        </div>
        <div class="about-row">
          <div class="about-row-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div>
            <div class="about-row-label">Languages</div>
            <div class="lang-tags">
              <?php foreach ($profile['languages'] as $lang): ?>
                <span class="lang-tag"><?= htmlspecialchars($lang) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Reviews -->
    <div class="reviews-card">
      <div class="section-head">
        <h3>Recent Reviews</h3>
        <a href="#">View all 24 →</a>
      </div>
      <?php foreach ($reviews as $review): ?>
      <div class="review-row">
        <div class="review-header">
          <div class="review-author-wrap">
            <div class="review-avatar"><?= strtoupper(substr($review['author'], 0, 1)) ?></div>
            <div>
              <div class="review-author"><?= htmlspecialchars($review['author']) ?></div>
              <div class="review-skill-tag">on <?= htmlspecialchars($review['skill']) ?></div>
            </div>
          </div>
          <div class="review-meta">
            <div class="review-stars"><?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5 - $review['rating']) ?></div>
            <div class="review-date"><?= $review['date'] ?></div>
          </div>
        </div>
        <div class="review-text"><?= htmlspecialchars($review['text']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div><!-- /content -->
</div><!-- /main -->
</body>
</html>