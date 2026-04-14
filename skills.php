<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

$user_name  = htmlspecialchars($_SESSION['name'] ?? 'Learner');
$user_email = htmlspecialchars($_SESSION['user'] ?? '');
$initials   = strtoupper(substr($user_name, 0, 1));

$deleted_msg = isset($_GET['delete']) ? 'Skill removed successfully.' : '';
$toggled_msg = isset($_GET['toggle']) ? 'Skill status updated.' : '';

// --- Replace with: SELECT * FROM skills WHERE user_id = $_SESSION['user_id'] ORDER BY created_at DESC ---
$skills = [
    [
        'id'          => 1,
        'title'       => 'Web Development',
        'category'    => 'Technology',
        'description' => 'Full-stack web development using HTML, CSS, JavaScript, PHP and MySQL. I build responsive, modern websites tailored to your needs.',
        'price'       => '50,000',
        'per'         => 'hour',
        'bookings'    => 8,
        'rating'      => 4.9,
        'reviews'     => 6,
        'status'      => 'active',
        'created'     => 'Mar 10, 2025',
        'tags'        => ['PHP', 'MySQL', 'JavaScript'],
    ],
    [
        'id'          => 2,
        'title'       => 'Graphic Design',
        'category'    => 'Creative',
        'description' => 'Logo design, branding, flyers, social media graphics and more. I use Illustrator and Photoshop to deliver professional creative work.',
        'price'       => '35,000',
        'per'         => 'project',
        'bookings'    => 5,
        'rating'      => 4.7,
        'reviews'     => 4,
        'status'      => 'active',
        'created'     => 'Feb 22, 2025',
        'tags'        => ['Illustrator', 'Photoshop', 'Branding'],
    ],
    [
        'id'          => 3,
        'title'       => 'English Tutoring',
        'category'    => 'Education',
        'description' => 'Private English lessons for beginners to advanced learners. Focus on speaking, writing, grammar and exam preparation.',
        'price'       => '20,000',
        'per'         => 'hour',
        'bookings'    => 11,
        'rating'      => 5.0,
        'reviews'     => 9,
        'status'      => 'active',
        'created'     => 'Jan 5, 2025',
        'tags'        => ['Grammar', 'Speaking', 'IELTS'],
    ],
    [
        'id'          => 4,
        'title'       => 'Photography',
        'category'    => 'Creative',
        'description' => 'Portrait, event and product photography. High-quality edited photos delivered within 48 hours of the shoot.',
        'price'       => '80,000',
        'per'         => 'session',
        'bookings'    => 2,
        'rating'      => 0,
        'reviews'     => 0,
        'status'      => 'draft',
        'created'     => 'Apr 1, 2025',
        'tags'        => ['Portrait', 'Events', 'Editing'],
    ],
];

$category_colors = [
    'Technology' => '#3b6ef0',
    'Creative'   => '#c8522a',
    'Education'  => '#34c97a',
    'Business'   => '#f5a623',
];

$active_count   = count(array_filter($skills, fn($s) => $s['status'] === 'active'));
$draft_count    = count(array_filter($skills, fn($s) => $s['status'] === 'draft'));
$total_bookings = array_sum(array_column($skills, 'bookings'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Skills — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:#0b0f1a;--surface:#141927;--surface2:#1c2438;--border:#252d42;
      --accent:#f5a623;--accent2:#e05c2a;--text:#e8eaf0;--muted:#6b7592;
      --green:#34c97a;--blue:#4a90e2;--red:#e05c5c;--radius:14px;
    }
    body{background:var(--bg);color:var(--text);font-family:'Instrument Sans',sans-serif;min-height:100vh;display:flex}

    /* Sidebar */
    .sidebar{width:240px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
    .sidebar-logo{padding:1.8rem 1.5rem 1.4rem;border-bottom:1px solid var(--border)}
    .logo-mark{font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;letter-spacing:-.03em;display:flex;align-items:center;gap:.4rem}
    .logo-mark .dot{width:8px;height:8px;background:var(--accent);border-radius:50%}
    .logo-sub{font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:.2rem}
    .sidebar-nav{flex:1;padding:1.2rem .75rem}
    .nav-section{font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:.8rem .75rem .4rem}
    .nav-item{display:flex;align-items:center;gap:.75rem;padding:.65rem .75rem;border-radius:9px;color:var(--muted);font-size:.88rem;cursor:pointer;transition:all .18s;text-decoration:none;margin-bottom:.1rem}
    .nav-item:hover{background:var(--surface2);color:var(--text)}
    .nav-item.active{background:rgba(245,166,35,.1);color:var(--accent);font-weight:500}
    .nav-item .badge{margin-left:auto;background:var(--accent);color:#000;font-size:.65rem;font-weight:700;padding:.15rem .45rem;border-radius:20px}
    .sidebar-footer{border-top:1px solid var(--border);padding:1rem .75rem}
    .user-pill{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:9px;cursor:pointer;transition:background .18s}
    .user-pill:hover{background:var(--surface2)}
    .avatar{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;color:#fff;flex-shrink:0}
    .user-info{overflow:hidden}
    .user-name{font-size:.85rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .user-email{font-size:.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    /* Main */
    .main{margin-left:240px;flex:1;min-height:100vh}
    .topbar{display:flex;align-items:center;justify-content:space-between;padding:1.4rem 2rem;border-bottom:1px solid var(--border);background:var(--bg);position:sticky;top:0;z-index:50}
    .topbar-title h1{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;letter-spacing:-.02em}
    .topbar-title p{font-size:.8rem;color:var(--muted);margin-top:.1rem}
    .topbar-actions{display:flex;align-items:center;gap:.75rem}

    /* Buttons */
    .btn{padding:.6rem 1.2rem;border-radius:8px;font-family:'Instrument Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;border:none;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
    .btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}
    .btn-ghost:hover{border-color:var(--muted);color:var(--text)}
    .btn-primary{background:var(--accent);color:#0b0f1a;font-weight:600}
    .btn-primary:hover{background:#f0b840}
    .btn-danger{background:rgba(224,92,92,.12);color:var(--red);border:1px solid rgba(224,92,92,.2)}
    .btn-danger:hover{background:rgba(224,92,92,.22)}
    .btn-sm{padding:.4rem .9rem;font-size:.78rem}

    .content{padding:2rem}

    /* Toast */
    .toast{background:rgba(52,201,122,.1);border:1px solid rgba(52,201,122,.25);color:var(--green);padding:.8rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem;animation:fadeUp .3s ease both}

    /* Summary */
    .summary-strip{display:flex;gap:1rem;margin-bottom:2rem;flex-wrap:wrap;animation:fadeUp .35s ease both}
    .summary-pill{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:.8rem 1.4rem;display:flex;align-items:center;gap:.7rem}
    .summary-pill strong{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700}
    .summary-pill span{color:var(--muted);font-size:.8rem}

    /* Filter bar */
    .filter-bar{display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;animation:fadeUp .4s ease both}
    .search-wrap{position:relative;flex:1;min-width:200px}
    .search-wrap svg{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none}
    .search-input{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:9px;padding:.65rem .9rem .65rem 2.4rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.88rem;outline:none;transition:border-color .2s}
    .search-input::placeholder{color:var(--muted)}
    .search-input:focus{border-color:var(--accent)}
    .filter-tabs{display:flex;gap:.4rem}
    .filter-tab{padding:.55rem 1rem;border-radius:8px;font-size:.82rem;font-weight:500;cursor:pointer;border:1px solid var(--border);background:var(--surface);color:var(--muted);transition:all .18s}
    .filter-tab:hover{color:var(--text);border-color:var(--muted)}
    .filter-tab.active{background:rgba(245,166,35,.1);color:var(--accent);border-color:rgba(245,166,35,.35)}
    .view-toggle{display:flex;gap:.3rem}
    .view-btn{width:34px;height:34px;border-radius:7px;background:var(--surface);border:1px solid var(--border);color:var(--muted);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .18s}
    .view-btn.active{background:rgba(245,166,35,.1);color:var(--accent);border-color:rgba(245,166,35,.35)}
    .view-btn:hover{color:var(--text)}

    /* Skills grid */
    .skills-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem}
    .skills-grid.list-view{grid-template-columns:1fr}

    /* Skill card */
    .skill-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:border-color .2s,transform .2s,box-shadow .2s;animation:fadeUp .4s ease both;display:flex;flex-direction:column}
    .skill-card:hover{border-color:rgba(245,166,35,.3);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.3)}
    .skills-grid.list-view .skill-card{flex-direction:row;align-items:stretch}
    .card-stripe{height:4px;width:100%}
    .skills-grid.list-view .card-stripe{width:4px;height:auto;flex-shrink:0}
    .card-body{padding:1.4rem;flex:1;display:flex;flex-direction:column;gap:.75rem}
    .skills-grid.list-view .card-body{flex-direction:row;align-items:center;flex-wrap:wrap;gap:1rem}
    .card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem}
    .skills-grid.list-view .card-head{flex:1;min-width:200px}
    .card-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;letter-spacing:-.01em;margin-bottom:.25rem}
    .card-category{font-size:.72rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;padding:.2rem .6rem;border-radius:20px}
    .card-desc{font-size:.83rem;color:var(--muted);line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .skills-grid.list-view .card-desc{flex:2;min-width:200px;-webkit-line-clamp:1}
    .card-tags{display:flex;gap:.4rem;flex-wrap:wrap}
    .tag{background:var(--surface2);border:1px solid var(--border);color:var(--muted);font-size:.7rem;padding:.2rem .55rem;border-radius:6px}
    .card-meta{display:flex;align-items:center;gap:1.2rem;font-size:.78rem;color:var(--muted);flex-wrap:wrap}
    .meta-item{display:flex;align-items:center;gap:.3rem}
    .card-footer{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.4rem;border-top:1px solid var(--border);background:rgba(255,255,255,.015);gap:.5rem;flex-wrap:wrap}
    .skills-grid.list-view .card-footer{border-top:none;border-left:1px solid var(--border);padding:1rem 1.2rem;flex-direction:column;align-items:flex-end;justify-content:center;min-width:180px}
    .price-amount{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:var(--accent)}
    .price-per{font-size:.72rem;color:var(--muted)}
    .card-actions{display:flex;gap:.5rem}
    .status-badge{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:.22rem .6rem;border-radius:20px}
    .status-active{background:rgba(52,201,122,.12);color:var(--green)}
    .status-draft{background:rgba(107,117,146,.12);color:var(--muted)}
    .stars{color:var(--accent);font-size:.8rem;letter-spacing:-.05em}

    /* Empty state */
    .empty-state{text-align:center;padding:5rem 2rem;border:1px dashed var(--border);border-radius:var(--radius);color:var(--muted);display:none}
    .empty-state h3{font-family:'Syne',sans-serif;font-size:1.1rem;color:var(--text);margin:1rem 0 .5rem}
    .empty-state p{font-size:.85rem;margin-bottom:1.5rem}

    /* Modal */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);z-index:200;display:none;align-items:center;justify-content:center;padding:1rem}
    .modal-overlay.open{display:flex}
    .modal{background:var(--surface);border:1px solid var(--border);border-radius:18px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;animation:modalIn .25s ease both}
    @keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(10px)}to{opacity:1;transform:none}}
    .modal-head{padding:1.5rem 1.75rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .modal-head h2{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700}
    .modal-close{width:30px;height:30px;border-radius:7px;background:var(--surface2);border:none;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:all .15s}
    .modal-close:hover{color:var(--text);background:var(--border)}
    .modal-body{padding:1.75rem;display:flex;flex-direction:column;gap:1.2rem}
    .form-field label{display:block;font-size:.75rem;font-weight:500;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.45rem}
    .form-field input,.form-field textarea,.form-field select{width:100%;background:var(--surface2);border:1px solid var(--border);border-radius:9px;padding:.75rem 1rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s}
    .form-field textarea{resize:vertical;min-height:90px}
    .form-field input:focus,.form-field textarea:focus,.form-field select:focus{border-color:var(--accent)}
    .form-field select option{background:var(--surface2)}
    .field-row-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    .modal-foot{padding:1.25rem 1.75rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.75rem}

    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

    @media(max-width:800px){
      .sidebar{display:none}.main{margin-left:0}
      .skills-grid.list-view .skill-card{flex-direction:column}
      .skills-grid.list-view .card-stripe{width:100%;height:4px}
      .skills-grid.list-view .card-footer{border-left:none;border-top:1px solid var(--border);flex-direction:row}
    }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">Umu <div class="dot"></div></div>
    <div class="logo-sub">Skill Marketplace</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard
    </a>
    <a href="skills.php" class="nav-item active">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>My Skills
      <span class="badge"><?= count($skills) ?></span>
    </a>
    <a href="booking.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Bookings
      <span class="badge">3</span>
    </a>
    <a href="browse.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse Skills
    </a>
    <div class="nav-section">Account</div>
    <a href="profile.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile
    </a>
    <a href="#" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages
    </a>
    <a href="logout.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Log Out
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

<div class="main">
  <header class="topbar">
    <div class="topbar-title">
      <h1>My Skills</h1>
      <p>Manage and track all your listed skills</p>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-primary" onclick="openModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New Skill
      </button>
    </div>
  </header>

  <div class="content">

    <?php if ($deleted_msg || $toggled_msg): ?>
    <div class="toast">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?= $deleted_msg ?: $toggled_msg ?>
    </div>
    <?php endif; ?>

    <!-- Summary strip -->
    <div class="summary-strip">
      <div class="summary-pill"><strong><?= count($skills) ?></strong><span>Total Skills</span></div>
      <div class="summary-pill"><strong style="color:var(--green)"><?= $active_count ?></strong><span>Active</span></div>
      <div class="summary-pill"><strong style="color:var(--muted)"><?= $draft_count ?></strong><span>Drafts</span></div>
      <div class="summary-pill"><strong style="color:var(--accent)"><?= $total_bookings ?></strong><span>Total Bookings</span></div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="search-input" id="searchInput" placeholder="Search skills..." oninput="filterSkills()">
      </div>
      <div class="filter-tabs">
        <button class="filter-tab active" onclick="setFilter('all',this)">All</button>
        <button class="filter-tab" onclick="setFilter('active',this)">Active</button>
        <button class="filter-tab" onclick="setFilter('draft',this)">Drafts</button>
      </div>
      <div class="view-toggle">
        <button class="view-btn active" title="Grid view" onclick="setView('grid',this)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
        <button class="view-btn" title="List view" onclick="setView('list',this)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
      </div>
    </div>

    <!-- Skills grid -->
    <div class="skills-grid" id="skillsGrid">
      <?php foreach ($skills as $i => $skill):
        $cat_color = $category_colors[$skill['category']] ?? '#8a8070';
        $cat_bg    = $cat_color . '1a';
        $rating    = $skill['rating'];
        $full  = floor($rating);
        $empty = 5 - $full;
        $stars = str_repeat('★', $full) . str_repeat('☆', $empty);
      ?>
      <div class="skill-card" data-status="<?= $skill['status'] ?>" data-title="<?= strtolower($skill['title']) ?>" style="animation-delay:<?= $i * .07 ?>s">
        <div class="card-stripe" style="background:<?= $cat_color ?>"></div>
        <div class="card-body">
          <div class="card-head">
            <div>
              <div class="card-title"><?= htmlspecialchars($skill['title']) ?></div>
              <span class="card-category" style="background:<?= $cat_bg ?>;color:<?= $cat_color ?>"><?= $skill['category'] ?></span>
            </div>
            <span class="status-badge status-<?= $skill['status'] ?>"><?= ucfirst($skill['status']) ?></span>
          </div>
          <p class="card-desc"><?= htmlspecialchars($skill['description']) ?></p>
          <div class="card-tags">
            <?php foreach ($skill['tags'] as $tag): ?>
              <span class="tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="card-meta">
            <span class="meta-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?= $skill['bookings'] ?> bookings
            </span>
            <?php if ($rating > 0): ?>
            <span class="meta-item">
              <span class="stars"><?= $stars ?></span>&nbsp;<?= number_format($rating,1) ?> (<?= $skill['reviews'] ?>)
            </span>
            <?php else: ?>
            <span class="meta-item" style="font-style:italic">No reviews yet</span>
            <?php endif; ?>
            <span class="meta-item">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?= $skill['created'] ?>
            </span>
          </div>
        </div>
        <div class="card-footer">
          <div>
            <div class="price-amount">UGX <?= $skill['price'] ?></div>
            <div class="price-per">per <?= $skill['per'] ?></div>
          </div>
          <div class="card-actions">
            <a href="edit-skill.php?id=<?= $skill['id'] ?>" class="btn btn-ghost btn-sm">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit
            </a>
            <?php if ($skill['status'] === 'active'): ?>
            <a href="?toggle=<?= $skill['id'] ?>" class="btn btn-ghost btn-sm">Pause</a>
            <?php else: ?>
            <a href="?toggle=<?= $skill['id'] ?>" class="btn btn-ghost btn-sm" style="color:var(--green)">Publish</a>
            <?php endif; ?>
            <a href="?delete=<?= $skill['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this skill?')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="empty-state" id="emptyState">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--muted);margin:0 auto"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      <h3>No skills found</h3>
      <p>Try adjusting your filters or add a new skill.</p>
      <button class="btn btn-primary" onclick="openModal()">Add Your First Skill</button>
    </div>

  </div>
</div>

<!-- Add Skill Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-head">
      <h2>List a New Skill</h2>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" action="add-skill.php">
      <div class="modal-body">
        <div class="form-field">
          <label>Skill Title</label>
          <input type="text" name="title" placeholder="e.g. Web Development, Photography..." required>
        </div>
        <div class="form-field">
          <label>Category</label>
          <select name="category" required>
            <option value="">Select a category</option>
            <option>Technology</option>
            <option>Creative</option>
            <option>Education</option>
            <option>Business</option>
            <option>Health &amp; Wellness</option>
            <option>Music &amp; Arts</option>
            <option>Language</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-field">
          <label>Description</label>
          <textarea name="description" placeholder="Describe what you offer, your experience, and what clients can expect..." required></textarea>
        </div>
        <div class="field-row-2">
          <div class="form-field">
            <label>Price (UGX)</label>
            <input type="number" name="price" placeholder="e.g. 50000" min="0" required>
          </div>
          <div class="form-field">
            <label>Per</label>
            <select name="per">
              <option>hour</option>
              <option>session</option>
              <option>project</option>
              <option>day</option>
            </select>
          </div>
        </div>
        <div class="form-field">
          <label>Tags (comma separated)</label>
          <input type="text" name="tags" placeholder="e.g. PHP, MySQL, JavaScript">
        </div>
        <div class="form-field">
          <label>Publish Status</label>
          <select name="status">
            <option value="active">Publish immediately</option>
            <option value="draft">Save as draft</option>
          </select>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">List Skill</button>
      </div>
    </form>
  </div>
</div>

<script>
  let currentFilter = 'all';

  function setFilter(filter, btn) {
    currentFilter = filter;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterSkills();
  }

  function filterSkills() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.skill-card');
    let visible = 0;
    cards.forEach(card => {
      const matchFilter = currentFilter === 'all' || card.dataset.status === currentFilter;
      const matchSearch = card.dataset.title.includes(q);
      const show = matchFilter && matchSearch;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    document.getElementById('emptyState').style.display = visible === 0 ? 'block' : 'none';
  }

  function setView(view, btn) {
    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('skillsGrid').classList.toggle('list-view', view === 'list');
  }

  function openModal()  { document.getElementById('modalOverlay').classList.add('open'); }
  function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>