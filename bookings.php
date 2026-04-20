<?php
$msg = '';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'connection.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name, email, theme, profile_photo FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
if (!$current_user) {
    header('Location: login.php');
    exit;
}

$user_name = htmlspecialchars($current_user['name'] ?? ($_SESSION['name'] ?? 'Learner'));
$user_email = htmlspecialchars($current_user['email'] ?? ($_SESSION['user'] ?? ''));
$user_theme = $current_user['theme'] ?? 'light';
$current_profile_photo = $current_user['profile_photo'] ?? '';
$initials = strtoupper(substr($user_name, 0, 1));

// Handle status update (accept / decline)
if (isset($_GET['accept'])) {
  $stmt = $pdo->prepare("
  UPDATE request SET status='accepted'
  WHERE request_id = ?
  AND service_id IN (SELECT service_id FROM service WHERE user_id = ?)");
  $stmt->execute([$_GET['accept'], $user_id]);
  $msg = 'Booking accepted!';
}

if (isset($_GET['decline'])) {
  $stmt = $pdo->prepare("
  UPDATE request SET status='rejected'
  WHERE request_id = ?
  AND service_id IN (SELECT service_id FROM service WHERE user_id = ?)");
  $stmt->execute([$_GET['decline'], $user_id]);
  $msg = 'Booking declined.';
}

// Fetch orders received (where user is the service provider)
$stmt = $pdo->prepare("
SELECT r.request_id AS id,
u.name AS client,
u.email AS client_email,
u.profile_photo,
s.title AS skill,
s.price,
s.unit,
r.status,
r.message,
r.created_at AS created
FROM request r
JOIN service s ON s.service_id = r.service_id
JOIN users u ON u.user_id = r.requester_id
WHERE s.user_id = ?
ORDER BY r.created_at DESC");
$stmt->execute([$user_id]);
$received = $stmt->fetchAll();

// Fetch orders placed (where user is the requester)
$stmt = $pdo->prepare("
SELECT r.request_id AS id,
u.name AS provider,
u.email AS provider_email,
u.profile_photo,
s.title AS skill,
s.price,
s.unit,
r.status,
r.message,
r.created_at AS created
FROM request r
JOIN service s ON s.service_id = r.service_id
JOIN users u ON u.user_id = s.user_id
WHERE r.requester_id = ?
ORDER BY r.created_at DESC");
$stmt->execute([$user_id]);
$placed = $stmt->fetchAll();

$bookings = $received;
 
// <-- Empty by default. Uncomment demo data below to preview UI.

/*
// DEMO DATA — remove in production
$bookings = [
    ['id'=>1,'client'=>'Amara Osei','client_email'=>'amara@mail.com','skill'=>'Web Development','price'=>'50,000','per'=>'hour','date'=>'2025-04-14','time'=>'2:00 PM','message'=>'I need a simple portfolio website built in 3 days.','status'=>'pending','created'=>'Apr 13, 2025'],
    ['id'=>2,'client'=>'Liam Nakato','client_email'=>'liam@mail.com','skill'=>'English Tutoring','price'=>'20,000','per'=>'hour','date'=>'2025-04-15','time'=>'10:00 AM','message'=>'Looking for help preparing for my IELTS exam next month.','status'=>'confirmed','created'=>'Apr 12, 2025'],
    ['id'=>3,'client'=>'Fatima Diallo','client_email'=>'fatima@mail.com','skill'=>'Graphic Design','price'=>'35,000','per'=>'project','date'=>'2025-04-15','time'=>'9:00 AM','message'=>'Need a logo and brand kit for my new business.','status'=>'confirmed','created'=>'Apr 11, 2025'],
    ['id'=>4,'client'=>'Samuel Eze','client_email'=>'samuel@mail.com','skill'=>'Web Development','price'=>'50,000','per'=>'hour','date'=>'2025-04-18','time'=>'3:00 PM','message'=>'Need help fixing bugs on an existing PHP project.','status'=>'declined','created'=>'Apr 10, 2025'],
];
*/

// Filter received bookings
$pending_received   = array_filter($received, fn($b) => $b['status'] === 'pending');
$confirmed_received = array_filter($received, fn($b) => $b['status'] === 'accepted');
$declined_received  = array_filter($received, fn($b) => $b['status'] === 'rejected');

// Filter placed bookings
$pending_placed   = array_filter($placed, fn($b) => $b['status'] === 'pending');
$confirmed_placed = array_filter($placed, fn($b) => $b['status'] === 'accepted');
$declined_placed  = array_filter($placed, fn($b) => $b['status'] === 'rejected');

$status_colors = [
    'pending'   => ['bg'=>'rgba(245,166,35,.12)', 'color'=>'#f5a623'],
    'accepted'  => ['bg'=>'rgba(52,201,122,.12)',  'color'=>'#34c97a'],
    'rejected'  => ['bg'=>'rgba(224,92,92,.12)',   'color'=>'#e05c5c'],
    'completed' => ['bg'=>'rgba(74,144,226,.12)',  'color'=>'#4a90e2'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($user_theme ?? 'light') ?>">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bookings — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root,[data-theme="dark"]{
      --bg:#0b0f1a;--surface:#141927;--surface2:#1c2438;--border:#252d42;
      --accent:#f5a623;--accent2:#e05c2a;--text:#e8eaf0;--muted:#6b7592;
      --green:#34c97a;--blue:#4a90e2;--red:#e05c5c;--radius:14px;
    }
    [data-theme="light"]{
      --bg:#f0f2f5;--surface:#ffffff;--surface2:#f4f5f7;--border:#dde0e8;
      --accent:#e08c10;--accent2:#c8522a;--text:#111827;--muted:#6b7280;
      --green:#16a34a;--blue:#2563eb;--red:#dc2626;--radius:14px;
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
    .topbar-actions{display:flex;gap:.75rem}

    .btn{padding:.6rem 1.2rem;border-radius:8px;font-family:'Instrument Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;border:none;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
    .btn-primary{background:var(--accent);color:#0b0f1a;font-weight:600}
    .btn-primary:hover{background:#f0b840}
    .btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}
    .btn-ghost:hover{border-color:var(--muted);color:var(--text)}
    .btn-green{background:rgba(52,201,122,.12);color:var(--green);border:1px solid rgba(52,201,122,.25)}
    .btn-green:hover{background:rgba(52,201,122,.22)}
    .btn-red{background:rgba(224,92,92,.12);color:var(--red);border:1px solid rgba(224,92,92,.2)}
    .btn-red:hover{background:rgba(224,92,92,.22)}
    .btn-sm{padding:.4rem .85rem;font-size:.78rem}

    .content{padding:2rem}

    /* Toast */
    .toast{background:rgba(52,201,122,.1);border:1px solid rgba(52,201,122,.25);color:var(--green);padding:.8rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem;animation:fadeUp .3s ease both}

    /* Tabs */
    .tab-bar{display:flex;gap:.4rem;margin-bottom:1.75rem;border-bottom:1px solid var(--border);padding-bottom:0;animation:fadeUp .35s ease both}
    .tab{padding:.65rem 1.2rem;font-size:.88rem;font-weight:500;color:var(--muted);cursor:pointer;border:none;background:none;border-bottom:2px solid transparent;margin-bottom:-1px;transition:all .18s;display:flex;align-items:center;gap:.5rem}
    .tab:hover{color:var(--text)}
    .tab.active{color:var(--accent);border-bottom-color:var(--accent)}
    .tab .count{background:var(--surface2);font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:20px}
    .tab.active .count{background:rgba(245,166,35,.15);color:var(--accent)}

    /* Tab panels */
    .tab-panel{display:none}
    .tab-panel.active{display:block}

    /* Empty state */
    .empty-state{text-align:center;padding:5rem 2rem;border:1px dashed var(--border);border-radius:var(--radius);animation:fadeUp .4s ease both}
    .empty-icon{width:72px;height:72px;background:var(--surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;color:var(--muted)}
    .empty-state h3{font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700;margin-bottom:.5rem}
    .empty-state p{font-size:.88rem;color:var(--muted);max-width:36ch;margin:0 auto 1.5rem;line-height:1.6}

    /* Booking cards */
    .bookings-list{display:flex;flex-direction:column;gap:1rem}

    .booking-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;animation:fadeUp .4s ease both;transition:border-color .2s}
    .booking-card:hover{border-color:rgba(245,166,35,.2)}

    .booking-card-head{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.4rem;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:.75rem}

    .client-info{display:flex;align-items:center;gap:.85rem}
    .client-avatar{width:38px;height:38px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.88rem;color:var(--accent);flex-shrink:0}
    .client-name{font-size:.92rem;font-weight:600;margin-bottom:.1rem}
    .client-email{font-size:.75rem;color:var(--muted)}

    .booking-badge{font-size:.7rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:.25rem .7rem;border-radius:20px}

    .booking-card-body{padding:1.2rem 1.4rem;display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem}
    @media(max-width:700px){.booking-card-body{grid-template-columns:1fr 1fr}}

    .detail-label{font-size:.7rem;font-weight:500;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.3rem}
    .detail-value{font-size:.9rem;font-weight:500}

    .booking-card-foot{padding:.9rem 1.4rem;border-top:1px solid var(--border);background:rgba(255,255,255,.015);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem}

    .message-bubble{font-size:.82rem;color:var(--muted);font-style:italic;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .foot-actions{display:flex;gap:.5rem;flex-shrink:0}

    /* Summary pills */
    .summary-strip{display:flex;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap;animation:fadeUp .3s ease both}
    .summary-pill{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:.75rem 1.3rem;display:flex;align-items:center;gap:.7rem}
    .summary-pill strong{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700}
    .summary-pill span{color:var(--muted);font-size:.8rem}

    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

    @media(max-width:800px){.sidebar{display:none}.main{margin-left:0}}
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
    <a href="bookings.php" class="nav-item active">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Bookings
      <?php if (count($pending_received) > 0): ?><span class="badge"><?= count($pending_received) ?></span><?php endif; ?>
    </a>
    <a href="browse.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse Skills
    </a>
     <a href="messages.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages</a>
    <div class="nav-section">Account</div>
    <a href="profile.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile
    </a>
    <a href="settings.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 21 12h-1"/></svg>Settings
    </a>
    <a href="logout.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Log Out
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="avatar">
        <?php if (!empty($current_profile_photo) && file_exists(__DIR__ . '/uploads/avatars/' . $current_profile_photo)): ?>
          <img src="<?= htmlspecialchars('uploads/avatars/' . $current_profile_photo) ?>" alt="<?= $user_name ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        <?php else: ?>
          <?= $initials ?>
        <?php endif; ?>
      </div>
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
      <h1>Bookings</h1>
      <p>Orders placed by clients for your skills</p>
    </div>
    <div class="topbar-actions">
      <a href="browse.php" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Browse Skills
      </a>
    </div>
  </header>

  <div class="content">

    <?php if ($msg): ?>
    <div class="toast">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($received) && empty($placed)): ?>
    <!-- Empty state -->
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <h3>No bookings yet</h3>
      <p>When other users book one of your skills or you book a service, your orders will appear here.</p>
      <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
        <a href="skills.php" class="btn btn-ghost">Manage My Skills</a>
        <a href="browse.php" class="btn btn-primary">Browse Available Skills</a>
      </div>
    </div>

    <?php else: ?>

    <!-- Tab navigation -->
    <div class="tab-bar">
      <button class="tab active" onclick="switchTab('received',this)">
        Orders Received <span class="count"><?= count($received) ?></span>
      </button>
      <button class="tab" onclick="switchTab('placed',this)">
        Orders Placed <span class="count"><?= count($placed) ?></span>
      </button>
    </div>

    <!-- ORDERS RECEIVED (as service provider) -->
    <div class="tab-panel active" id="panel-received">
      <?php if (!empty($received)): ?>
      <div class="summary-strip">
        <div class="summary-pill"><strong><?= count($received) ?></strong><span>Total</span></div>
        <div class="summary-pill"><strong style="color:var(--accent)"><?= count($pending_received) ?></strong><span>Pending</span></div>
        <div class="summary-pill"><strong style="color:var(--green)"><?= count($confirmed_received) ?></strong><span>Accepted</span></div>
        <div class="summary-pill"><strong style="color:var(--red)"><?= count($declined_received) ?></strong><span>Rejected</span></div>
      </div>
      <?php endif; ?>

      <div class="bookings-list">
        <?php if (empty($received)): ?>
        <div style="text-align:center;padding:3rem;color:var(--muted);font-size:.88rem">
          No orders received yet. Make sure your skills are active and attractive!
        </div>
        <?php else: ?>
        <?php foreach ($received as $i => $b):
          $sc = $status_colors[$b['status']];
          $name = htmlspecialchars($b['client']);
          $initial = strtoupper(substr($name, 0, 1));
          $date = date('F j, Y \a\t g:i A', strtotime($b['created']));
        ?>
        <div class="booking-card" style="animation-delay:<?= $i * .06 ?>s">
          <div class="booking-card-head">
            <div class="client-info">
              <div class="client-avatar">
                <?php if (!empty($b['profile_photo'])): ?>
                  <img src="<?= htmlspecialchars('uploads/avatars/' . $b['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="<?= $name ?>">
                <?php else: ?>
                  <?= $initial ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="client-name"><?= $name ?></div>
                <div class="client-email"><?= htmlspecialchars($b['client_email']) ?></div>
              </div>
            </div>
            <span class="booking-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>"><?= ucfirst($b['status']) ?></span>
          </div>

          <div class="booking-card-body">
            <div>
              <div class="detail-label">Skill</div>
              <div class="detail-value"><?= htmlspecialchars($b['skill']) ?></div>
            </div>
            <div>
              <div class="detail-label">Date Requested</div>
              <div class="detail-value"><?= $date ?></div>
            </div>
            <div>
              <div class="detail-label">Rate</div>
              <div class="detail-value" style="color:var(--accent)">UGX <?= number_format($b['price']) ?>/<?= htmlspecialchars($b['unit']) ?></div>
            </div>
          </div>

          <div class="booking-card-foot">
            <div class="message-bubble">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;margin-right:.3rem"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              "<?= htmlspecialchars(substr($b['message'], 0, 60)) ?><?= strlen($b['message']) > 60 ? '...' : '' ?>"
            </div>
            <div class="foot-actions">
              <?php if ($b['status'] === 'pending'): ?>
                <a href="?accept=<?= $b['id'] ?>" class="btn btn-green btn-sm">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Accept
                </a>
                <a href="?decline=<?= $b['id'] ?>" class="btn btn-red btn-sm" onclick="return confirm('Decline this booking?')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Decline
                </a>
              <?php elseif ($b['status'] === 'accepted'): ?>
                <span class="btn btn-ghost btn-sm" style="cursor:default;opacity:.6">✓ Accepted</span>
              <?php else: ?>
                <span class="btn btn-ghost btn-sm" style="cursor:default;opacity:.6">✗ Rejected</span>
              <?php endif; ?>
              <a href="messages.php?user=<?= urlencode($b['client_email']) ?>" class="btn btn-ghost btn-sm">Message</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ORDERS PLACED (as requester) -->
    <div class="tab-panel" id="panel-placed">
      <?php if (!empty($placed)): ?>
      <div class="summary-strip">
        <div class="summary-pill"><strong><?= count($placed) ?></strong><span>Total</span></div>
        <div class="summary-pill"><strong style="color:var(--accent)"><?= count($pending_placed) ?></strong><span>Pending</span></div>
        <div class="summary-pill"><strong style="color:var(--green)"><?= count($confirmed_placed) ?></strong><span>Accepted</span></div>
        <div class="summary-pill"><strong style="color:var(--red)"><?= count($declined_placed) ?></strong><span>Rejected</span></div>
      </div>
      <?php endif; ?>

      <div class="bookings-list">
        <?php if (empty($placed)): ?>
        <div style="text-align:center;padding:3rem;color:var(--muted);font-size:.88rem">
          You haven't placed any orders yet. <a href="browse.php" style="color:var(--accent)">Browse skills now</a>
        </div>
        <?php else: ?>
        <?php foreach ($placed as $i => $b):
          $sc = $status_colors[$b['status']];
          $name = htmlspecialchars($b['provider']);
          $initial = strtoupper(substr($name, 0, 1));
          $date = date('F j, Y \a\t g:i A', strtotime($b['created']));
        ?>
        <div class="booking-card" style="animation-delay:<?= $i * .06 ?>s">
          <div class="booking-card-head">
            <div class="client-info">
              <div class="client-avatar">
                <?php if (!empty($b['profile_photo'])): ?>
                  <img src="<?= htmlspecialchars('uploads/avatars/' . $b['profile_photo']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="<?= $name ?>">
                <?php else: ?>
                  <?= $initial ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="client-name"><?= $name ?></div>
                <div class="client-email"><?= htmlspecialchars($b['provider_email']) ?></div>
              </div>
            </div>
            <span class="booking-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>"><?= ucfirst($b['status']) ?></span>
          </div>

          <div class="booking-card-body">
            <div>
              <div class="detail-label">Skill</div>
              <div class="detail-value"><?= htmlspecialchars($b['skill']) ?></div>
            </div>
            <div>
              <div class="detail-label">Date Requested</div>
              <div class="detail-value"><?= $date ?></div>
            </div>
            <div>
              <div class="detail-label">Rate</div>
              <div class="detail-value" style="color:var(--accent)">UGX <?= number_format($b['price']) ?>/<?= htmlspecialchars($b['unit']) ?></div>
            </div>
          </div>

          <div class="booking-card-foot">
            <div class="message-bubble">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;margin-right:.3rem"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              "<?= htmlspecialchars(substr($b['message'], 0, 60)) ?><?= strlen($b['message']) > 60 ? '...' : '' ?>"
            </div>
            <div class="foot-actions">
              <?php if ($b['status'] === 'accepted'): ?>
                <span class="btn btn-ghost btn-sm" style="cursor:default;opacity:.6">✓ Accepted</span>
              <?php elseif ($b['status'] === 'rejected'): ?>
                <span class="btn btn-ghost btn-sm" style="cursor:default;opacity:.6">✗ Rejected</span>
              <?php else: ?>
                <span class="btn btn-ghost btn-sm" style="cursor:default;opacity:.6">⏳ Waiting for response</span>
              <?php endif; ?>
              <a href="messages.php?user=<?= urlencode($b['provider_email']) ?>" class="btn btn-ghost btn-sm">Message</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php endif; ?>
  </div>
</div>

<script>
function switchTab(name, btn) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-' + name).classList.add('active');
}
</script>
</body>
</html>