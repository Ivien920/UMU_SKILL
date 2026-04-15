<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user_name  = htmlspecialchars($_SESSION['name']  ?? 'Learner');
$user_email = htmlspecialchars($_SESSION['user']  ?? '');
$initials   = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    /* 
── Dark Theme (default) ── */
    :root,
    [data-theme="dark"] {
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
      --red:       #e05c5c;
      --radius:    14px;
      --shadow:    0 4px 24px rgba(0,0,0,.4);
    }
    /* 
── Light Theme ── */
    [data-theme="light"] {
      --bg:        #f4f5f9;
      --surface:   #ffffff;
      --surface2:  #eef0f6;
      --border:    #dde0ea;
      --accent:    #d4890f;
      --accent2:   #c04c1c;
      --text:      #1a1f30;
      --muted:     #7a82a0;
      --green:     #1f9c5e;
      --blue:      #2d72c8;
      --red:       #c04040;
      --radius:    14px;
      --shadow:    0 4px 24px rgba(0,0,0,.08);
    }
    html { scroll-behavior: smooth; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Instrument Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      transition: background .25s, color .25s;
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
      transition: background .25s, border-color .25s;
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
    [data-theme="light"] .nav-item.active {
      background: rgba(212,137,15,.1);
    }
    .nav-item .badge {
      margin-left: auto;
      background: var(--accent);
      color: #fff;
      font-size: .65rem;
      font-weight: 700;
      padding: .15rem .45rem;
      border-radius: 20px;
    }
    [data-theme="dark"] .nav-item .badge { color: #000; }
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
      top: 0;
      z-index: 50;
      transition: background .25s, border-color .25s;
    }
    .topbar-title h1 {
      font-family: 'Syne', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
    }
      letter-spacing: -.02em;
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
    [data-theme="light"] .btn-primary { color: #fff; }
    .btn-primary:hover { opacity: .9; }
    .btn-danger {
      background: transparent;
      border: 1px solid rgba(224,92,92,.4);
      color: var(--red);
    }
    .btn-danger:hover { background: rgba(224,92,92,.08); border-color: var(--red); }
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
── Content layout ── */
    .content { padding: 2rem; display: flex; gap: 1.5rem; align-items: flex-start; }
    /* 
── Settings Nav ── */
    .settings-nav {
      width: 220px;
      flex-shrink: 0;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      position: sticky;
      top: 90px;
      animation: fadeUp .35s ease both;
      transition: background .25s, border-color .25s;
    }
    .settings-nav-item {
      display: flex;
      align-items: center;
      gap: .65rem;
      padding: .85rem 1.2rem;
      font-size: .86rem;
      color: var(--muted);
      cursor: pointer;
      transition: all .18s;
      border-bottom: 1px solid var(--border);
      text-decoration: none;
    }
    .settings-nav-item:last-child { border-bottom: none; }
    .settings-nav-item:hover { background: var(--surface2); color: var(--text); }
    .settings-nav-item.active {
      background: rgba(245,166,35,.08);
      color: var(--accent);
      font-weight: 500;
    }
    [data-theme="light"] .settings-nav-item.active { background: rgba(212,137,15,.08); }
    /* 
── Settings Panels ── */
    .settings-panels { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.5rem; }
    .settings-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      animation: fadeUp .4s ease both;
      transition: background .25s, border-color .25s;
    }
    .settings-card:nth-child(1) { animation-delay: .05s; }
    .settings-card:nth-child(2) { animation-delay: .1s; }
    .settings-card:nth-child(3) { animation-delay: .15s; }
    .settings-card:nth-child(4) { animation-delay: .2s; }
    .settings-card-head {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: .75rem;
    }
    .settings-card-head-icon {
      width: 34px; height: 34px;
      border-radius: 9px;
      background: rgba(245,166,35,.1);
      display: flex; align-items: center; justify-content: center;
      color: var(--accent);
      flex-shrink: 0;
    }
    [data-theme="light"] .settings-card-head-icon { background: rgba(212,137,15,.1); }
    .settings-card-head-text h3 {
      font-family: 'Syne', sans-serif;
      font-size: .95rem;
      font-weight: 700;
    }
    .settings-card-head-text p { font-size: .76rem; color: var(--muted); margin-top: .1rem; }
    .settings-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.2rem; }
    /* 
── Form elements ── */
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-group { display: flex; flex-direction: column; gap: .45rem; }
    .form-group.full { grid-column: 1 / -1; }
    label {
      font-size: .78rem;
      font-weight: 500;
      color: var(--muted);
      letter-spacing: .02em;
    }
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="url"],
    input[type="password"],
    textarea,
    select {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 9px;
      padding: .65rem .9rem;
      color: var(--text);
      font-family: 'Instrument Sans', sans-serif;
      font-size: .88rem;
      width: 100%;
    }
      outline: none;
      transition: border-color .18s, background .25s;
    input:focus, textarea:focus, select:focus {
      border-color: var(--accent);
    }
    input::placeholder, textarea::placeholder { color: var(--muted); }
    textarea { resize: vertical; min-height: 90px; line-height: 1.6; }
    select { cursor: pointer; }
    /* 
── Toggle switch ── */
    .toggle-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: .9rem 0;
      border-bottom: 1px solid var(--border);
    }
    .toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
    .toggle-row:first-child { padding-top: 0; }
    .toggle-info { flex: 1; }
    .toggle-label {
      font-size: .88rem;
      font-weight: 500;
      margin-bottom: .1rem;
    }
    .toggle-desc { font-size: .76rem; color: var(--muted); }
    .toggle {
      position: relative;
      width: 42px;
      height: 24px;
      flex-shrink: 0;
    }
    .toggle input { opacity: 0; width: 0; height: 0; }
    .toggle-track {
      position: absolute;
      inset: 0;
      border-radius: 24px;
      background: var(--surface2);
      border: 1px solid var(--border);
      cursor: pointer;
      transition: background .2s, border-color .2s;
    }
    .toggle-track::after {
      content: '';
      position: absolute;
      top: 3px; left: 3px;
      width: 16px; height: 16px;
      border-radius: 50%;
      background: var(--muted);
      transition: transform .2s, background.2s;
    }
    .toggle input:checked + .toggle-track {
      background: rgba(245,166,35,.2);
      border-color: var(--accent);
    }
    [data-theme="light"] .toggle input:checked + .toggle-track {
      background: rgba(212,137,15,.15);
    }
    .toggle input:checked + .toggle-track::after {
      transform: translateX(18px);
      background: var(--accent);
    }
    /* 
── Appearance / Theme picker ── */
    .theme-picker {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: .75rem;
      margin-top: .25rem;
    }
    .theme-option {
      border: 2px solid var(--border);
      border-radius: 10px;
      padding: 1rem;
      cursor: pointer;
      transition: all .2s;
      display: flex;
      flex-direction: column;
      gap: .6rem;
      position: relative;
    }
    .theme-option:hover { border-color: var(--muted); }
    .theme-option.selected { border-color: var(--accent); }
    .theme-option-check {
      position: absolute;
      top: .6rem; right: .6rem;
      width: 18px; height: 18px;
      border-radius: 50%;
      background: var(--accent);
      display: flex; align-items: center; justify-content: center;
      opacity: 0;
      transition: opacity .18s;
    }
    .theme-option.selected .theme-option-check { opacity: 1; }
    .theme-preview {
      border-radius: 7px;
      height: 60px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      gap: 4px;
      padding: 6px;
    }
    .theme-preview-dark { background: #0b0f1a; border: 1px solid #252d42; }
    .theme-preview-light { background: #f4f5f9; border: 1px solid #dde0ea; }
    .tp-bar {
      height: 6px;
      border-radius: 3px;
    }
    .theme-preview-dark .tp-bar.a { background: #252d42; width: 60%; }
    .theme-preview-dark .tp-bar.b { background: #f5a623; width: 40%; }
    .theme-preview-dark .tp-bar.c { background: #252d42; width: 80%; }
    .theme-preview-light .tp-bar.a { background: #dde0ea; width: 60%; }
    .theme-preview-light .tp-bar.b { background: #d4890f; width: 40%; }
    .theme-preview-light .tp-bar.c { background: #dde0ea; width: 80%; }
    .theme-option-label {
      font-size: .82rem;
      font-weight: 600;
      display: flex; align-items: center; gap: .4rem;
    }
    .theme-icon { font-size: 1rem; }
    /* 
── Danger zone ── */
    .danger-zone-card {
      background: var(--surface);
      border: 1px solid rgba(224,92,92,.25);
      border-radius: var(--radius);
      overflow: hidden;
      animation: fadeUp .4s ease both;
      animation-delay: .35s;
      transition: background .25s;
    }
    .danger-zone-head {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid rgba(224,92,92,.15);
      display: flex;
      align-items: center;
      gap: .75rem;
    }
    .danger-zone-head-icon {
      width: 34px; height: 34px;
      border-radius: 9px;
      background: rgba(224,92,92,.1);
      display: flex; align-items: center; justify-content: center;
      color: var(--red);
      flex-shrink: 0;
    }
    .danger-zone-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
    .danger-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }
    .danger-row-info h4 { font-size: .88rem; font-weight: 500; margin-bottom: .15rem; }
    .danger-row-info p  { font-size: .76rem; color: var(--muted); }
    /* 
── Toast ── */
    .toast {
      position: fixed;
      bottom: 1.5rem; right: 1.5rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: .9rem 1.2rem;
      display: flex;
      align-items: center;
      gap: .65rem;
      font-size: .86rem;
      font-weight: 500;
      box-shadow: var(--shadow);
      transform: translateY(20px);
      opacity: 0;
      transition: transform .3s, opacity .3s;
      z-index: 1000;
      pointer-events: none;
    }
    .toast.show { transform: none; opacity: 1; }
    .toast-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); flex-shrink: 0; }
    /* Animations */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: none; }
    }
    /* Responsive */
    @media (max-width: 960px) {
      .content { flex-direction: column; }
      .settings-nav { width: 100%; position: static; display: flex; flex-wrap: wrap; border-radius: var(--radius); }
      .settings-nav-item { border-bottom: none; border-right: 1px solid var(--border); }
    }
    @media (max-width: 800px) {
      .sidebar { display: none; }
      .main { margin-left: 0; }
    }
    @media (max-width: 600px) {
      .form-row { grid-template-columns: 1fr; }
      .theme-picker { grid-template-columns: 1fr; }
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
    <a href="profile.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </a>
    <a href="#" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Messages
    </a>
    <a href="settings.php" class="nav-item active">
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
      <h1>Settings</h1>
      <p>Manage your account preferences</p>
    </div>
    <div class="topbar-actions">
      <div class="notif-btn">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
      <button class="btn btn-primary" onclick="saveSettings()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Save Changes
      </button>
    </div>
  </header>
  <!-- Content -->
  <div class="content">
    <!-- Side Nav -->
    <nav class="settings-nav">
      <a href="#profile"      class="settings-nav-item active" onclick="setActive(this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Profile
      </a>
      <a href="#appearance"   class="settings-nav-item" onclick="setActive(this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        Appearance
      </a>
      <a href="#notifications" class="settings-nav-item" onclick="setActive(this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notifications
      </a>
      <a href="#security"     class="settings-nav-item" onclick="setActive(this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Security
      </a>
      <a href="#privacy"      class="settings-nav-item" onclick="setActive(this)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Privacy
      </a>
      <a href="#danger"       class="settings-nav-item" onclick="setActive(this)" style="color:var(--red)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="10.29 3.86 1.82 18 22.18 18 13.71 3.86 10.29 3.86"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Danger Zone
      </a>
    </nav>
    <!-- Panels -->
    <div class="settings-panels">
      <!-- Profile -->
      <div class="settings-card" id="profile">
        <div class="settings-card-head">
          <div class="settings-card-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div class="settings-card-head-text">
            <h3>Profile Information</h3>
            <p>Update your public profile details</p>
          </div>
        </div>
        <div class="settings-body">
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" value="<?= $user_name ?>" placeholder="Your full name">
            </div>
            <div class="form-group">
              <label>Username</label>
              <input type="text" value="learner" placeholder="@username">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" value="<?= $user_email ?>" placeholder="you@example.com">
            </div>
            <div class="form-group">
              <label>Phone Number</label>
              <input type="tel" value="+256 700 123 456" placeholder="+256 ...">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Location</label>
              <input type="text" value="Kampala, Uganda" placeholder="City, Country">
            </div>
            <div class="form-group">
              <label>Website / Portfolio</label>
              <input type="url" value="umuskills.ug/u/learner" placeholder="https://...">
            </div>
          </div>
          <div class="form-group full">
            <label>Bio</label>
            <textarea placeholder="Tell clients about yourself…">Passionate full-stack developer and creative designer based in Kampala, Uganda. I help businesses and individuals bring their ideas to life through clean code and bold visuals.</textarea>
          </div>
        </div>
      </div>
      <!-- Appearance -->
      <div class="settings-card" id="appearance">
        <div class="settings-card-head">
          <div class="settings-card-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          </div>
          <div class="settings-card-head-text">
            <h3>Appearance</h3>
            <p>Choose your preferred theme and display options</p>
          </div>
        </div>
        <div class="settings-body">
          <div class="form-group">
            <label>Theme</label>
            <div class="theme-picker">
              <div class="theme-option" id="opt-dark" onclick="setTheme('dark')">
                <div class="theme-option-check">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="theme-preview theme-preview-dark">
                  <div class="tp-bar a"></div>
                  <div class="tp-bar b"></div>
                  <div class="tp-bar c"></div>
                </div>
                <div class="theme-option-label">
                  <span class="theme-icon">
                </div>
              </div>
</span> Dark Mode
              <div class="theme-option" id="opt-light" onclick="setTheme('light')">
                <div class="theme-option-check">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="theme-preview theme-preview-light">
                  <div class="tp-bar a"></div>
                  <div class="tp-bar b"></div>
                  <div class="tp-bar c"></div>
                </div>
                <div class="theme-option-label">
                  <span class="theme-icon"> </span> Light Mode
                </div>
              </div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Language</label>
              <select>
                <option selected>English</option>
                <option>Luganda</option>
                <option>Swahili</option>
                <option>French</option>
              </select>
            </div>
            <div class="form-group">
              <label>Currency</label>
              <select>
                <option selected>UGX — Ugandan Shilling</option>
                <option>USD — US Dollar</option>
                <option>KES — Kenyan Shilling</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <!-- Notifications -->
      <div class="settings-card" id="notifications">
        <div class="settings-card-head">
          <div class="settings-card-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          </div>
          <div class="settings-card-head-text">
            <h3>Notifications</h3>
            <p>Control when and how you're notified</p>
          </div>
        </div>
        <div class="settings-body">
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">New booking requests</div>
              <div class="toggle-desc">Get notified when a client books one of your skills</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Booking reminders</div>
              <div class="toggle-desc">Reminder 1 hour before a scheduled session</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">New messages</div>
              <div class="toggle-desc">Notify me when I receive a message from a client</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">New reviews</div>
              <div class="toggle-desc">Notify when a client leaves a review</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Promotional emails</div>
              <div class="toggle-desc">Tips, platform updates, and marketplace news</div>
            </div>
            <label class="toggle">
              <input type="checkbox">
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">SMS notifications</div>
              <div class="toggle-desc">Receive text messages for critical alerts</div>
            </div>
            <label class="toggle">
              <input type="checkbox">
              <span class="toggle-track"></span>
            </label>
          </div>
        </div>
      </div>
      <!-- Security -->
      <div class="settings-card" id="security">
        <div class="settings-card-head">
          <div class="settings-card-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <div class="settings-card-head-text">
            <h3>Security</h3>
            <p>Manage your password and account access</p>
          </div>
        </div>
        <div class="settings-body">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" placeholder="Enter current password">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>New Password</label>
              <input type="password" placeholder="Min. 8 characters">
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" placeholder="Repeat new password">
            </div>
          </div>
          <div class="toggle-row" style="padding-top:0">
            <div class="toggle-info">
              <div class="toggle-label">Two-factor authentication</div>
              <div class="toggle-desc">Add a second layer of security with SMS or app verification</div>
            </div>
            <label class="toggle">
              <input type="checkbox">
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Login activity alerts</div>
              <div class="toggle-desc">Get notified about new sign-ins to your account</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
        </div>
      </div>
      <!-- Privacy -->
      <div class="settings-card" id="privacy">
        <div class="settings-card-head">
          <div class="settings-card-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div class="settings-card-head-text">
            <h3>Privacy</h3>
            <p>Control who can see your profile and activity</p>
          </div>
        </div>
        <div class="settings-body">
          <div class="toggle-row" style="padding-top:0">
            <div class="toggle-info">
              <div class="toggle-label">Public profile</div>
              <div class="toggle-desc">Allow anyone on Umu to view your profile page</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Show email address</div>
              <div class="toggle-desc">Display your email on your public profile</div>
            </div>
            <label class="toggle">
              <input type="checkbox">
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Show phone number</div>
              <div class="toggle-desc">Display your phone on your public profile</div>
            </div>
            <label class="toggle">
              <input type="checkbox">
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Allow direct messages</div>
              <div class="toggle-desc">Let any user on Umu send you a message</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
          <div class="toggle-row">
            <div class="toggle-info">
              <div class="toggle-label">Show online status</div>
              <div class="toggle-desc">Let clients see when you are active on Umu</div>
            </div>
            <label class="toggle">
              <input type="checkbox" checked>
              <span class="toggle-track"></span>
            </label>
          </div>
        </div>
      </div>
      <!-- Danger Zone -->
      <div class="danger-zone-card" id="danger">
        <div class="danger-zone-head">
          <div class="danger-zone-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="10.29 3.86 1.82 18 22.18 18 13.71 3.86 10.29 3.86"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <div class="settings-card-head-text">
            <h3 style="color:var(--red)">Danger Zone</h3>
            <p>Irreversible actions — proceed with caution</p>
          </div>
        </div>
        <div class="danger-zone-body">
          <div class="danger-row">
            <div class="danger-row-info">
              <h4>Deactivate Account</h4>
              <p>Temporarily hide your profile and listings from the marketplace</p>
            </div>
            <button class="btn btn-danger">Deactivate</button>
          </div>
          <div class="danger-row">
            <div class="danger-row-info">
              <h4>Delete Account</h4>
              <p>Permanently delete your account and all associated data. This cannot be undone.</p>
            </div>
            <button class="btn btn-danger">Delete Account</button>
          </div>
        </div>
      </div>
    </div><!-- /settings-panels -->
  </div><!-- /content -->
</div><!-- /main -->
<!-- Toast -->
<div class="toast" id="toast">
  <div class="toast-dot"></div>
  Settings saved successfully
</div>
<script>
  // ── Theme switching ──
  const html = document.documentElement;
  const savedTheme = localStorage.getItem('umu-theme') || 'dark';
  applyTheme(savedTheme);
  function setTheme(theme) {
    applyTheme(theme);
    localStorage.setItem('umu-theme', theme);
  }
  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    document.getElementById('opt-dark').classList.toggle('selected', theme === 'dark');
    document.getElementById('opt-light').classList.toggle('selected', theme === 'light');
  }
  // ── Settings nav active state ──
  function setActive(el) {
    document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
    el.classList.add('active');
  }
  // ── Save toast ──
  function saveSettings() {
    const toast = document.getElementById('toast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
  }
  // ── Smooth scroll nav highlighting ──
  const sections = document.querySelectorAll('[id]');
  const navItems = document.querySelectorAll('.settings-nav-item');
  window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(s => {
      if (window.scrollY >= s.offsetTop - 140) current = s.id;
    });
    navItems.forEach(n => {
      n.classList.toggle('active', n.getAttribute('href') === '#' + current);
    });
  }, { passive: true });
</script>
</body>
</html>