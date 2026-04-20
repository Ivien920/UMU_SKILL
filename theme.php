<?php
// theme.php — Include at the TOP of every page (after session_start)
// Usage: require_once 'theme.php';
// This reads the user's saved theme from the DB (or session fallback)
// and outputs the correct <body> class.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default theme
$current_theme = 'light';

// If user is logged in, get their theme from session (set during login or settings save)
if (isset($_SESSION['user_id'])) {
    $current_theme = $_SESSION['theme'] ?? 'light';
}

// If not logged in but they've set a preference in the session
if (!isset($_SESSION['user_id']) && isset($_SESSION['theme'])) {
    $current_theme = $_SESSION['theme'];
}

// Sanitize
$current_theme = ($current_theme === 'dark') ? 'dark' : 'light';

// Output the CSS variables and body class
// Include this function call inside your <head> tag: echo themeStyles();
function themeStyles() {
    return '
    <style>
        /* ===== CSS Variables for Light / Dark Theme ===== */
        :root {
            --bg-primary:    #ffffff;
            --bg-secondary:  #f4f6f9;
            --bg-card:       #ffffff;
            --text-primary:  #212529;
            --text-secondary:#6c757d;
            --border-color:  #dee2e6;
            --nav-bg:        #343a40;
            --nav-text:      #ffffff;
            --btn-primary:   #0d6efd;
            --shadow:        rgba(0,0,0,0.08);
        }
        body.dark-mode {
            --bg-primary:    #121212;
            --bg-secondary:  #1e1e1e;
            --bg-card:       #2c2c2c;
            --text-primary:  #e0e0e0;
            --text-secondary:#aaaaaa;
            --border-color:  #444444;
            --nav-bg:        #1a1a2e;
            --nav-text:      #e0e0e0;
            --btn-primary:   #4d8bf5;
            --shadow:        rgba(0,0,0,0.4);
        }
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            font-family: "Segoe UI", sans-serif;
        }
        .card, .modal-content, .dropdown-menu {
            background-color: var(--bg-card) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }
        .navbar {
            background-color: var(--nav-bg) !important;
        }
        .navbar a, .navbar-brand, .nav-link {
            color: var(--nav-text) !important;
        }
        .form-control, .form-select {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        .form-control:focus, .form-select:focus {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }
        .table {
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }
        .list-group-item {
            background-color: var(--bg-card);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        .text-muted { color: var(--text-secondary) !important; }
        .border     { border-color: var(--border-color) !important; }
        .bg-light   { background-color: var(--bg-secondary) !important; }
        hr          { border-color: var(--border-color); }
    </style>';
}

// Return current theme for use in <body class="">
function getThemeClass() {
    global $current_theme;
    return ($current_theme === 'dark') ? 'dark-mode' : '';
}
?>