<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

$user_name  = htmlspecialchars($_SESSION['name'] ?? 'Learner');
$user_email = htmlspecialchars($_SESSION['user'] ?? '');
$initials   = strtoupper(substr($user_name, 0, 1));

// Handle booking form submission
$book_success = false;
$book_error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_skill_id'])) {
    $skill_id = (int)$_POST['book_skill_id'];
    $date     = trim($_POST['date'] ?? '');
    $time     = trim($_POST['time'] ?? '');
    $message  = trim($_POST['message'] ?? '');

    if (!$date || !$time) {
        $book_error = 'Please choose a date and time.';
    } else {
        /*
         * INSERT INTO bookings (skill_id, client_id, date, time, message, status)
         * VALUES ($skill_id, $_SESSION['user_id'], $date, $time, $message, 'pending')
         */
        $book_success = true;
    }
}

// Active category / search filter
$q        = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? 'All');

/*
 * --- Replace $listings with a real DB query ---
 * SELECT s.*, u.name AS seller_name, u.id AS seller_id,
 *        AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
 * FROM skills s
 * JOIN users u ON u.id = s.user_id
 * LEFT JOIN reviews r ON r.skill_id = s.id
 * WHERE s.status = 'active'
 *   AND s.user_id != $_SESSION['user_id']   -- don't show own skills
 *   AND (s.title LIKE '%$q%' OR s.category = '$category')
 * GROUP BY s.id
 * ORDER BY avg_rating DESC
 */
$listings = [
    ['id'=>10,'seller'=>'Amara Osei',   'seller_id'=>5, 'title'=>'React & Next.js Development','category'=>'Technology','price'=>'75,000','per'=>'hour','rating'=>4.9,'reviews'=>12,'tags'=>['React','Next.js','TailwindCSS'],'description'=>'I build blazing-fast web apps using React and Next.js. Available for full projects or hourly consulting.','location'=>'Kampala'],
    ['id'=>11,'seller'=>'Liam Nakato',  'seller_id'=>6, 'title'=>'Digital Marketing & SEO',    'category'=>'Business',  'price'=>'60,000','per'=>'project','rating'=>4.7,'reviews'=>8,'tags'=>['SEO','Google Ads','Analytics'],'description'=>'Grow your online presence with data-driven SEO and digital marketing strategies.','location'=>'Entebbe'],
    ['id'=>12,'seller'=>'Fatima Diallo','seller_id'=>7, 'title'=>'Video Editing & Motion',     'category'=>'Creative',  'price'=>'45,000','per'=>'project','rating'=>4.8,'reviews'=>15,'tags'=>['Premiere','After Effects','Color Grading'],'description'=>'Professional video editing for YouTube, adverts and social media. Fast turnaround guaranteed.','location'=>'Kampala'],
    ['id'=>13,'seller'=>'Samuel Eze',   'seller_id'=>8, 'title'=>'Piano & Music Theory',       'category'=>'Music',     'price'=>'30,000','per'=>'hour','rating'=>5.0,'reviews'=>20,'tags'=>['Piano','Music Theory','Beginner-Friendly'],'description'=>'Learn piano from scratch or advance your existing skills. Online and in-person sessions available.','location'=>'Jinja'],
    ['id'=>14,'seller'=>'Grace Tumelo', 'seller_id'=>9, 'title'=>'Data Analysis with Python',  'category'=>'Technology','price'=>'80,000','per'=>'hour','rating'=>4.6,'reviews'=>7,'tags'=>['Python','Pandas','Matplotlib'],'description'=>'Turn your raw data into actionable insights. I specialise in data cleaning, visualisation and reporting.','location'=>'Kampala'],
    ['id'=>15,'seller'=>'Omar Hassan',  'seller_id'=>10,'title'=>'Arabic Language Tutoring',   'category'=>'Education', 'price'=>'25,000','per'=>'hour','rating'=>4.8,'reviews'=>11,'tags'=>['Arabic','Quran','Beginner'],'description'=>'Conversational and classical Arabic for all levels. Patient, structured, and fun lessons.','location'=>'Kampala'],
    ['id'=>16,'seller'=>'Joy Achieng',  'seller_id'=>11,'title'=>'Yoga & Mindfulness Coaching','category'=>'Health',    'price'=>'35,000','per'=>'session','rating'=>4.9,'reviews'=>18,'tags'=>['Yoga','Meditation','Wellness'],'description'=>'Guided yoga and mindfulness sessions to help you reduce stress and improve flexibility.','location'=>'Kampala'],
    ['id'=>17,'seller'=>'Kwame Asante', 'seller_id'=>12,'title'=>'Business Plan Writing',      'category'=>'Business',  'price'=>'120,000','per'=>'project','rating'=>4.7,'reviews'=>5,'tags'=>['Business Plan','Strategy','Pitch Deck'],'description'=>'Professional business plan writing and pitch deck creation for startups and SMEs seeking funding.','location'=>'Kampala'],
    ['id'=>18,'seller'=>'Nadia Bekele', 'seller_id'=>13,'title'=>'Fashion Design & Tailoring', 'category'=>'Creative',  'price'=>'50,000','per'=>'project','rating'=>4.5,'reviews'=>9,'tags'=>['Fashion','Tailoring','Design'],'description'=>'Custom clothing design and tailoring. Bring your style idea to life with quality handwork.','location'=>'Kampala'],
];

$categories = ['All', 'Technology', 'Creative', 'Business', 'Education', 'Music', 'Health'];

// Filter listings by search and category
$filtered = array_filter($listings, function($l) use ($q, $category) {
    $matchCat   = $category === 'All' || $l['category'] === $category;
    $matchQuery = !$q || stripos($l['title'], $q) !== false || stripos($l['seller'], $q) !== false || stripos($l['description'], $q) !== false;
    return $matchCat && $matchQuery;
});

$category_colors = [
    'Technology' => '#3b6ef0','Creative'=>'#c8522a','Education'=>'#34c97a',
    'Business'   => '#f5a623','Music'=>'#9b59b6','Health'=>'#1abc9c',
];

// Skill being booked (from modal)
$booking_skill = null;
if (isset($_GET['book'])) {
    foreach ($listings as $l) {
        if ($l['id'] == $_GET['book']) { $booking_skill = $l; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Browse Skills — Umu Skill Marketplace</title>
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
    .topbar{display:flex;align-items:center;justify-content:space-between;padding:1.4rem 2rem;border-bottom:1px solid var(--border);background:var(--bg);position:sticky;top:0;z-index:50;gap:1rem;flex-wrap:wrap}
    .topbar-title h1{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;letter-spacing:-.02em}
    .topbar-title p{font-size:.8rem;color:var(--muted);margin-top:.1rem}

    /* Hero search */
    .hero-search{padding:2rem 2rem 0}
    .search-box{background:var(--surface);border:1.5px solid var(--border);border-radius:12px;display:flex;align-items:center;gap:.75rem;padding:.8rem 1.2rem;transition:border-color .2s;max-width:680px}
    .search-box:focus-within{border-color:var(--accent)}
    .search-box svg{color:var(--muted);flex-shrink:0}
    .search-box input{flex:1;background:none;border:none;outline:none;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.98rem}
    .search-box input::placeholder{color:var(--muted)}
    .search-box button{background:var(--accent);color:#000;border:none;border-radius:8px;padding:.55rem 1.1rem;font-family:'Instrument Sans',sans-serif;font-weight:600;font-size:.85rem;cursor:pointer;transition:background .18s;white-space:nowrap}
    .search-box button:hover{background:#f0b840}

    /* Category pills */
    .cat-bar{display:flex;gap:.5rem;padding:1.25rem 2rem 0;flex-wrap:wrap}
    .cat-pill{padding:.45rem 1rem;border-radius:20px;font-size:.82rem;font-weight:500;cursor:pointer;border:1px solid var(--border);background:var(--surface);color:var(--muted);transition:all .18s;text-decoration:none}
    .cat-pill:hover{color:var(--text);border-color:var(--muted)}
    .cat-pill.active{background:rgba(245,166,35,.12);color:var(--accent);border-color:rgba(245,166,35,.35)}

    .content{padding:1.5rem 2rem 2rem}

    /* Results header */
    .results-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem}
    .results-count{font-size:.85rem;color:var(--muted)}
    .results-count strong{color:var(--text)}

    /* Sort */
    .sort-select{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.45rem .9rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.82rem;outline:none;cursor:pointer}
    .sort-select option{background:var(--surface2)}

    /* Toast */
    .toast{background:rgba(52,201,122,.1);border:1px solid rgba(52,201,122,.25);color:var(--green);padding:.8rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;animation:fadeUp .3s ease both}
    .toast-error{background:rgba(224,92,92,.1);border-color:rgba(224,92,92,.25);color:var(--red)}

    /* Skills grid */
    .skills-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem}

    /* Skill card */
    .skill-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;display:flex;flex-direction:column;transition:border-color .2s,transform .2s,box-shadow .2s;animation:fadeUp .4s ease both}
    .skill-card:hover{border-color:rgba(245,166,35,.3);transform:translateY(-3px);box-shadow:0 14px 40px rgba(0,0,0,.35)}
    .card-stripe{height:4px}
    .card-body{padding:1.3rem;flex:1;display:flex;flex-direction:column;gap:.7rem}
    .card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem}
    .seller-row{display:flex;align-items:center;gap:.6rem;margin-bottom:.15rem}
    .seller-avatar{width:28px;height:28px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:var(--accent);flex-shrink:0}
    .seller-name{font-size:.78rem;color:var(--muted)}
    .seller-location{font-size:.72rem;color:var(--muted);display:flex;align-items:center;gap:.2rem}
    .card-title{font-family:'Syne',sans-serif;font-size:.98rem;font-weight:700;letter-spacing:-.01em;line-height:1.3}
    .card-category{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:.2rem .55rem;border-radius:20px;white-space:nowrap}
    .card-desc{font-size:.81rem;color:var(--muted);line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .card-tags{display:flex;gap:.35rem;flex-wrap:wrap}
    .tag{background:var(--surface2);border:1px solid var(--border);color:var(--muted);font-size:.68rem;padding:.18rem .5rem;border-radius:5px}
    .card-rating{display:flex;align-items:center;gap:.35rem;font-size:.78rem}
    .stars{color:var(--accent);letter-spacing:-.04em;font-size:.8rem}
    .rating-count{color:var(--muted)}
    .card-footer{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.3rem;border-top:1px solid var(--border);background:rgba(255,255,255,.015)}
    .price-block .price-amt{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--accent)}
    .price-block .price-per{font-size:.7rem;color:var(--muted)}
    .btn-book{background:var(--accent);color:#0b0f1a;border:none;border-radius:8px;padding:.55rem 1.1rem;font-family:'Instrument Sans',sans-serif;font-weight:600;font-size:.82rem;cursor:pointer;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem}
    .btn-book:hover{background:#f0b840;transform:scale(1.03)}

    /* Empty */
    .empty-state{text-align:center;padding:5rem 2rem;border:1px dashed var(--border);border-radius:var(--radius);color:var(--muted)}
    .empty-state h3{font-family:'Syne',sans-serif;font-size:1.1rem;color:var(--text);margin:1rem 0 .5rem}
    .empty-state p{font-size:.85rem;max-width:32ch;margin:0 auto}

    /* Booking Modal */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(5px);z-index:200;display:none;align-items:center;justify-content:center;padding:1rem}
    .modal-overlay.open{display:flex}
    .modal{background:var(--surface);border:1px solid var(--border);border-radius:18px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;animation:modalIn .25s ease both}
    @keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(12px)}to{opacity:1;transform:none}}
    .modal-head{padding:1.5rem 1.75rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .modal-head h2{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700}
    .modal-close{width:30px;height:30px;border-radius:7px;background:var(--surface2);border:none;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:all .15s}
    .modal-close:hover{color:var(--text);background:var(--border)}
    .modal-skill-info{padding:1.1rem 1.75rem;background:var(--surface2);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:1rem}
    .modal-skill-info .skill-badge{padding:.35rem .9rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
    .modal-skill-title{font-family:'Syne',sans-serif;font-weight:700;font-size:.98rem;margin-bottom:.2rem}
    .modal-skill-seller{font-size:.78rem;color:var(--muted)}
    .modal-body{padding:1.5rem 1.75rem;display:flex;flex-direction:column;gap:1.1rem}
    .form-field label{display:block;font-size:.72rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem}
    .form-field input,.form-field textarea,.form-field select{width:100%;background:var(--surface2);border:1px solid var(--border);border-radius:9px;padding:.72rem 1rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s}
    .form-field textarea{resize:vertical;min-height:80px}
    .form-field input:focus,.form-field textarea:focus{border-color:var(--accent)}
    .field-row-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    .price-summary{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:1rem 1.2rem;display:flex;justify-content:space-between;align-items:center}
    .price-summary .label{font-size:.78rem;color:var(--muted)}
    .price-summary .amount{font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);font-size:1.05rem}
    .modal-foot{padding:1.25rem 1.75rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.75rem}
    .btn-cancel{background:transparent;border:1px solid var(--border);color:var(--muted);border-radius:8px;padding:.6rem 1.1rem;font-family:'Instrument Sans',sans-serif;font-size:.88rem;cursor:pointer;transition:all .18s}
    .btn-cancel:hover{color:var(--text);border-color:var(--muted)}
    .btn-confirm{background:var(--accent);color:#0b0f1a;border:none;border-radius:8px;padding:.6rem 1.3rem;font-family:'Instrument Sans',sans-serif;font-weight:600;font-size:.88rem;cursor:pointer;transition:all .18s}
    .btn-confirm:hover{background:#f0b840}

    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
    @media(max-width:800px){.sidebar{display:none}.main{margin-left:0}}
    @media(max-width:520px){.skills-grid{grid-template-columns:1fr}.field-row-2{grid-template-columns:1fr}}
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
    <a href="skills.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>My Skills
    </a>
    <a href="bookings.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Bookings
    </a>
    <a href="browse.php" class="nav-item active">
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
      <h1>Browse Skills</h1>
      <p>Discover and book talents from the Umu community</p>
    </div>
  </header>

  <!-- Search bar -->
  <div class="hero-search">
    <form method="GET" action="">
      <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
      <div class="search-box">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search skills, people, categories…">
        <button type="submit">Search</button>
      </div>
    </form>
  </div>

  <!-- Category pills -->
  <div class="cat-bar">
    <?php foreach ($categories as $cat): ?>
      <a href="?q=<?= urlencode($q) ?>&category=<?= urlencode($cat) ?>" class="cat-pill <?= $category === $cat ? 'active' : '' ?>"><?= $cat ?></a>
    <?php endforeach; ?>
  </div>

  <div class="content">

    <?php if ($book_success): ?>
    <div class="toast">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      Booking request sent! The seller will confirm shortly. Check your <a href="bookings.php" style="color:var(--green);font-weight:600;margin-left:.2rem">Bookings</a>.
    </div>
    <?php endif; ?>

    <?php if ($book_error): ?>
    <div class="toast toast-error">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($book_error) ?>
    </div>
    <?php endif; ?>

    <div class="results-head">
      <div class="results-count">Showing <strong><?= count($filtered) ?></strong> skill<?= count($filtered) !== 1 ? 's' : '' ?><?= $q ? ' for "<strong>'.htmlspecialchars($q).'</strong>"' : '' ?></div>
      <select class="sort-select" onchange="sortCards(this.value)">
        <option value="rating">Sort: Top Rated</option>
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="reviews">Most Reviewed</option>
      </select>
    </div>

    <?php if (empty($filtered)): ?>
    <div class="empty-state">
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--muted);margin:0 auto"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <h3>No skills found</h3>
      <p>Try a different search or browse all categories.</p>
    </div>
    <?php else: ?>

    <div class="skills-grid" id="skillsGrid">
      <?php foreach ($filtered as $i => $s):
        $cc  = $category_colors[$s['category']] ?? '#8a8070';
        $cbg = $cc . '1a';
        $si  = strtoupper(substr($s['seller'], 0, 1));
        $full  = floor($s['rating']);
        $stars = str_repeat('★', $full) . str_repeat('☆', 5 - $full);
      ?>
      <div class="skill-card"
           data-rating="<?= $s['rating'] ?>"
           data-reviews="<?= $s['reviews'] ?>"
           data-price="<?= str_replace(',','',$s['price']) ?>"
           style="animation-delay:<?= $i * .06 ?>s">
        <div class="card-stripe" style="background:<?= $cc ?>"></div>
        <div class="card-body">
          <div class="card-top">
            <div>
              <div class="seller-row">
                <div class="seller-avatar"><?= $si ?></div>
                <div>
                  <div class="seller-name"><?= htmlspecialchars($s['seller']) ?></div>
                </div>
              </div>
              <div class="seller-location">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= htmlspecialchars($s['location']) ?>
              </div>
            </div>
            <span class="card-category" style="background:<?= $cbg ?>;color:<?= $cc ?>"><?= $s['category'] ?></span>
          </div>
          <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
          <p class="card-desc"><?= htmlspecialchars($s['description']) ?></p>
          <div class="card-tags">
            <?php foreach ($s['tags'] as $tag): ?>
              <span class="tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="card-rating">
            <span class="stars"><?= $stars ?></span>
            <strong><?= number_format($s['rating'],1) ?></strong>
            <span class="rating-count">(<?= $s['reviews'] ?> reviews)</span>
          </div>
        </div>
        <div class="card-footer">
          <div class="price-block">
            <div class="price-amt">UGX <?= $s['price'] ?></div>
            <div class="price-per">per <?= $s['per'] ?></div>
          </div>
          <button class="btn-book" onclick="openBooking(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Book Now
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </div>
</div>

<!-- Booking Modal -->
<div class="modal-overlay" id="bookingModal" onclick="if(event.target===this)closeBooking()">
  <div class="modal">
    <div class="modal-head">
      <h2>Book a Session</h2>
      <button class="modal-close" onclick="closeBooking()">✕</button>
    </div>
    <div class="modal-skill-info" id="modalSkillInfo"></div>
    <form method="POST" action="">
      <input type="hidden" name="book_skill_id" id="bookSkillId">
      <div class="modal-body">
        <div class="field-row-2">
          <div class="form-field">
            <label>Preferred Date</label>
            <input type="date" name="date" id="bookDate" min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-field">
            <label>Preferred Time</label>
            <input type="time" name="time" id="bookTime" required>
          </div>
        </div>
        <div class="form-field">
          <label>Message to Seller</label>
          <textarea name="message" placeholder="Describe what you need, your goals, or any specific requirements…"></textarea>
        </div>
        <div class="price-summary">
          <div class="label">Session Rate</div>
          <div class="amount" id="modalPrice">UGX —</div>
        </div>
        <p style="font-size:.75rem;color:var(--muted);line-height:1.5">
          Your booking request will be sent to the seller. Payment is handled directly between you and the seller once confirmed.
        </p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeBooking()">Cancel</button>
        <button type="submit" class="btn-confirm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline;margin-right:.3rem"><polyline points="20 6 9 17 4 12"/></svg>
          Send Booking Request
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const catColors = <?= json_encode($category_colors) ?>;

function openBooking(id, skill) {
  document.getElementById('bookSkillId').value = id;
  document.getElementById('modalPrice').textContent = 'UGX ' + skill.price + ' / ' + skill.per;
  const cc = catColors[skill.category] || '#8a8070';
  document.getElementById('modalSkillInfo').innerHTML = `
    <div>
      <div class="modal-skill-title">${skill.title}</div>
      <div class="modal-skill-seller">by ${skill.seller} &nbsp;·&nbsp; ${skill.location}</div>
    </div>
    <span class="skill-badge" style="background:${cc}1a;color:${cc};margin-left:auto">${skill.category}</span>
  `;
  document.getElementById('bookingModal').classList.add('open');
}

function closeBooking() {
  document.getElementById('bookingModal').classList.remove('open');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeBooking(); });

function sortCards(by) {
  const grid  = document.getElementById('skillsGrid');
  const cards = [...grid.querySelectorAll('.skill-card')];
  cards.sort((a, b) => {
    if (by === 'rating')     return +b.dataset.rating  - +a.dataset.rating;
    if (by === 'reviews')    return +b.dataset.reviews - +a.dataset.reviews;
    if (by === 'price_asc')  return +a.dataset.price   - +b.dataset.price;
    if (by === 'price_desc') return +b.dataset.price   - +a.dataset.price;
  });
  cards.forEach(c => grid.appendChild(c));
}
</script>
</body>
</html>