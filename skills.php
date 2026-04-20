<?php
/**
 * skills.php — My Services / Skills
 * Fully wired to the umu_skill database.
 *
 * Tables used (from your schema):
 *   service  (service_id, user_id, skill_id, title, description, price, created_at)
 *   skill    (skill_id, skil_name)          ← note: skil_name (one l) in your DB
 *   request  (request_id, service_id, requester_id, status, message, created_at)
 *   review   (review_id, request_id, rating, comment, created_at)
 */

session_start();

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require_once 'connection.php';
require 'theme.php';
$me       = (int)$_SESSION['user_id'];


$me_name  = htmlspecialchars($_SESSION['name'] ?? 'User');
$me_email = htmlspecialchars($_SESSION['user'] ?? '');
$initials = strtoupper(substr($me_name, 0, 1));

/* ── ADD new service ─────────────────────────────────────────────────── */
$add_err = $add_ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $skill_id = (int)$_POST['skill_id'];
    $title    = trim($_POST['title']       ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $price    = floatval($_POST['price']   ?? 0);
    if (!$skill_id || !$title || !$desc || $price < 1) {
        $add_err = 'Please fill in all required fields (title, category, description, price).';
    } else {
        $pdo->prepare("INSERT INTO service (user_id,skill_id,title,description,price) VALUES (?,?,?,?,?)")
            ->execute([$me, $skill_id, $title, $desc, $price]);
        $add_ok = 'Service listed successfully!';
    }
}

/* ── EDIT service ────────────────────────────────────────────────────── */
$edit_err = $edit_ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    $svc_id   = (int)$_POST['service_id'];
    $skill_id = (int)$_POST['skill_id'];
    $title    = trim($_POST['title']       ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $price    = floatval($_POST['price']   ?? 0);
    $own = $pdo->prepare("SELECT service_id FROM service WHERE service_id=? AND user_id=?");
    $own->execute([$svc_id, $me]);
    if (!$own->fetch())              $edit_err = 'Service not found or access denied.';
    elseif (!$skill_id||!$title||!$desc||$price<1) $edit_err = 'Fill in all required fields.';
    else {
        $pdo->prepare("UPDATE service SET skill_id=?,title=?,description=?,price=? WHERE service_id=? AND user_id=?")
            ->execute([$skill_id, $title, $desc, $price, $svc_id, $me]);
        $edit_ok = 'Service updated.';
    }
}

/* ── DELETE service ──────────────────────────────────────────────────── */
$del_msg = '';
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $busy   = $pdo->prepare("SELECT COUNT(*) FROM request WHERE service_id=? AND status IN ('accepted','completed')");
    $busy->execute([$del_id]);
    if ($busy->fetchColumn() > 0) {
        $del_msg = 'err:Cannot delete — service has accepted/completed bookings.';
    } else {
        $pdo->prepare("DELETE FROM service WHERE service_id=? AND user_id=?")->execute([$del_id, $me]);
        $del_msg = 'ok:Service deleted successfully.';
    }
}

/* ── Skill categories list ───────────────────────────────────────────── */
$skills_list = $pdo->query("SELECT skill_id, skill_name FROM skill ORDER BY skill_name")->fetchAll();

/* ── Fetch MY services with aggregated stats ─────────────────────────── */
$filter_skill = (int)($_GET['skill'] ?? 0);
$q            = trim($_GET['q']      ?? '');

$sql  = "SELECT s.service_id, s.title, s.description, s.price, s.created_at,
                sk.skill_name, sk.skill_id,
                COUNT(DISTINCT r.request_id)        AS total_requests,
                COALESCE(SUM(r.status='pending'),0) AS pending_count,
                COALESCE(SUM(r.status='accepted'),0) AS accepted_count,
                COALESCE(SUM(r.status='completed'),0) AS completed_count,
                ROUND(AVG(rv.rating),1)             AS avg_rating,
                COUNT(DISTINCT rv.review_id)        AS review_count
         FROM service s
         JOIN skill sk ON sk.skill_id = s.skill_id
         LEFT JOIN request r  ON r.service_id  = s.service_id
         LEFT JOIN review  rv ON rv.request_id = r.request_id
         WHERE s.user_id = :me";
$p = [':me' => $me];
if ($filter_skill) { $sql .= " AND s.skill_id = :sk"; $p[':sk'] = $filter_skill; }
if ($q) {
    $sql .= " AND (s.title LIKE :q OR s.description LIKE :q2 OR sk.skil_name LIKE :q3)";
    $p[':q'] = $p[':q2'] = $p[':q3'] = "%$q%";
}
$sql .= " GROUP BY s.service_id ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($p);
$services = $stmt->fetchAll();

/* ── Overall stats ───────────────────────────────────────────────────── */
$st = $pdo->prepare("SELECT COUNT(DISTINCT s.service_id) AS svcs,
                            COUNT(DISTINCT r.request_id) AS reqs,
                            COALESCE(SUM(r.status='completed'),0) AS done,
                            ROUND(AVG(rv.rating),1) AS rating
                     FROM service s
                     LEFT JOIN request r  ON r.service_id  = s.service_id
                     LEFT JOIN review  rv ON rv.request_id = r.request_id
                     WHERE s.user_id = ?");
$st->execute([$me]); $stats = $st->fetch();

/* ── Pre-fill for edit modal ─────────────────────────────────────────── */
$editing = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $e = $pdo->prepare("SELECT s.*, sk.skil_name FROM service s JOIN skill sk ON sk.skill_id=s.skill_id WHERE s.service_id=? AND s.user_id=?");
    $e->execute([(int)$_GET['edit'], $me]); $editing = $e->fetch();
}

/* helper */
function esc($s){ return htmlspecialchars((string)($s??''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>My Skills — Umu Skill Marketplace</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root,[data-theme="dark"]{--bg:#0b0f1a;--surface:#141927;--surface2:#1c2438;--border:#252d42;--accent:#f5a623;--accent2:#e05c2a;--text:#e8eaf0;--muted:#6b7592;--green:#34c97a;--blue:#4a90e2;--red:#e05c5c;--radius:14px}
[data-theme="light"]{--bg:#f0f2f5;--surface:#fff;--surface2:#f4f5f7;--border:#dde0e8;--accent:#e08c10;--accent2:#c8522a;--text:#111827;--muted:#6b7280;--green:#16a34a;--blue:#2563eb;--red:#dc2626}
html,body{transition:background .3s,color .3s}
body{background:var(--bg);color:var(--text);font-family:'Instrument Sans',sans-serif;min-height:100vh;display:flex}
.sidebar{width:240px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:background .3s,border-color .3s}
.sidebar-logo{padding:1.8rem 1.5rem 1.4rem;border-bottom:1px solid var(--border)}
.logo-mark{font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;letter-spacing:-.03em;display:flex;align-items:center;gap:.4rem}
.logo-mark .dot{width:8px;height:8px;background:var(--accent);border-radius:50%}
.logo-sub{font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:.2rem}
.sidebar-nav{flex:1;padding:1.2rem .75rem;overflow-y:auto}
.nav-section{font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:.8rem .75rem .4rem}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.65rem .75rem;border-radius:9px;color:var(--muted);font-size:.88rem;cursor:pointer;transition:all .18s;text-decoration:none;margin-bottom:.1rem}
.nav-item:hover{background:var(--surface2);color:var(--text)}.nav-item.active{background:rgba(245,166,35,.1);color:var(--accent);font-weight:500}
.sidebar-footer{border-top:1px solid var(--border);padding:1rem .75rem}
.user-pill{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border-radius:9px;cursor:pointer;transition:background .18s;text-decoration:none;color:inherit}
.user-pill:hover{background:var(--surface2)}
.sb-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;color:#fff;flex-shrink:0}
.sb-name{font-size:.85rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-email{font-size:.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.main{margin-left:240px;flex:1}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:1.4rem 2rem;border-bottom:1px solid var(--border);background:var(--bg);position:sticky;top:0;z-index:50;transition:all .3s;gap:1rem;flex-wrap:wrap}
.topbar-title h1{font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;letter-spacing:-.02em}
.topbar-title p{font-size:.8rem;color:var(--muted);margin-top:.1rem}
.btn{padding:.6rem 1.2rem;border-radius:8px;font-family:'Instrument Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;border:none;transition:all .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
.btn-primary{background:var(--accent);color:#0b0f1a;font-weight:600}.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted)}.btn-ghost:hover{border-color:var(--muted);color:var(--text)}
.btn-danger{background:rgba(224,92,92,.1);color:var(--red);border:1px solid rgba(224,92,92,.22)}.btn-danger:hover{background:rgba(224,92,92,.2)}
.btn-edit{background:rgba(74,144,226,.1);color:var(--blue);border:1px solid rgba(74,144,226,.22)}.btn-edit:hover{background:rgba(74,144,226,.2)}
.btn-sm{padding:.4rem .88rem;font-size:.78rem}
.content{padding:2rem}
.toast{padding:.82rem 1.2rem;border-radius:10px;font-size:.85rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem;animation:fadeUp .3s ease both}
.toast.ok{background:rgba(52,201,122,.1);border:1px solid rgba(52,201,122,.25);color:var(--green)}
.toast.err{background:rgba(224,92,92,.1);border:1px solid rgba(224,92,92,.25);color:var(--red)}
.toast.info{background:rgba(107,117,146,.1);border:1px solid var(--border);color:var(--muted)}
.stats-strip{display:flex;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap;animation:fadeUp .35s ease both}
.stat-pill{background:var(--surface);border:1px solid var(--border);border-radius:11px;padding:.78rem 1.35rem;display:flex;align-items:center;gap:.7rem;transition:border-color .2s,background .3s}
.stat-pill:hover{border-color:rgba(245,166,35,.3)}
.stat-pill strong{font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700}
.stat-pill span{font-size:.78rem;color:var(--muted)}
.filter-bar{display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;animation:fadeUp .4s ease both}
.search-wrap{position:relative;flex:1;min-width:200px}
.search-wrap svg{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none}
.search-input{width:100%;background:var(--surface);border:1.5px solid var(--border);border-radius:9px;padding:.62rem .9rem .62rem 2.4rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.88rem;outline:none;transition:border-color .2s,background .3s}
.search-input::placeholder{color:var(--muted)}.search-input:focus{border-color:var(--accent)}
.skill-filter{background:var(--surface);border:1.5px solid var(--border);border-radius:9px;padding:.6rem .9rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.85rem;outline:none;cursor:pointer;transition:border-color .2s}.skill-filter:focus{border-color:var(--accent)}.skill-filter option{background:var(--surface2)}
.view-toggle{display:flex;gap:.3rem}
.view-btn{width:34px;height:34px;border-radius:7px;background:var(--surface);border:1px solid var(--border);color:var(--muted);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .18s}
.view-btn.active{background:rgba(245,166,35,.1);color:var(--accent);border-color:rgba(245,166,35,.35)}
.services-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem}
.services-grid.list-view{grid-template-columns:1fr}
.svc-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;display:flex;flex-direction:column;transition:border-color .2s,transform .2s,box-shadow .2s,background .3s;animation:fadeUp .4s ease both}
.svc-card:hover{border-color:rgba(245,166,35,.3);transform:translateY(-2px);box-shadow:0 10px 36px rgba(0,0,0,.25)}
.services-grid.list-view .svc-card{flex-direction:row}
.card-stripe{height:4px;background:linear-gradient(90deg,var(--accent),var(--accent2));flex-shrink:0}
.services-grid.list-view .card-stripe{width:4px;height:auto}
.card-body{padding:1.3rem;flex:1;display:flex;flex-direction:column;gap:.7rem}
.services-grid.list-view .card-body{flex-direction:row;align-items:center;flex-wrap:wrap;gap:1rem}
.card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem}
.services-grid.list-view .card-top{flex:1.5;min-width:180px}
.card-skill-badge{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:.2rem .6rem;border-radius:20px;background:rgba(245,166,35,.12);color:var(--accent);flex-shrink:0}
.card-title{font-family:'Syne',sans-serif;font-size:.98rem;font-weight:700;letter-spacing:-.01em;line-height:1.3;margin-bottom:.2rem}
.card-desc{font-size:.82rem;color:var(--muted);line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.services-grid.list-view .card-desc{flex:2;min-width:160px;-webkit-line-clamp:1}
.card-meta{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;font-size:.76rem;color:var(--muted)}
.meta-item{display:flex;align-items:center;gap:.3rem}
.stars{color:var(--accent);letter-spacing:-.03em;font-size:.78rem}
.card-footer{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.3rem;border-top:1px solid var(--border);background:rgba(255,255,255,.015);gap:.5rem;flex-wrap:wrap}
.services-grid.list-view .card-footer{border-top:none;border-left:1px solid var(--border);padding:1rem 1.2rem;flex-direction:column;align-items:flex-end;justify-content:center;min-width:170px}
.price-amt{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--accent)}
.price-per{font-size:.7rem;color:var(--muted)}
.card-actions{display:flex;gap:.45rem}
.req-count{font-size:.72rem;padding:.18rem .52rem;border-radius:6px;background:rgba(107,117,146,.12);color:var(--muted);font-weight:600}
.req-count.has{background:rgba(245,166,35,.12);color:var(--accent)}
.empty-state{text-align:center;padding:5rem 2rem;border:1px dashed var(--border);border-radius:var(--radius);animation:fadeUp .4s ease both}
.empty-icon{width:72px;height:72px;background:var(--surface);border:1px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;color:var(--muted)}
.empty-state h3{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:.5rem}
.empty-state p{font-size:.87rem;color:var(--muted);max-width:38ch;margin:0 auto 1.5rem;line-height:1.65}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(5px);z-index:200;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:18px;width:100%;max-width:540px;max-height:92vh;overflow-y:auto;animation:modalIn .25s ease both;transition:background .3s}
@keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(12px)}to{opacity:1;transform:none}}
.modal-head{padding:1.4rem 1.75rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-head h2{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700}
.modal-close{width:30px;height:30px;border-radius:7px;background:var(--surface2);border:none;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:all .15s}
.modal-close:hover{color:var(--text);background:var(--border)}
.modal-body{padding:1.5rem 1.75rem;display:flex;flex-direction:column;gap:1.1rem}
.form-field{display:flex;flex-direction:column;gap:.38rem}
.form-field label{font-size:.72rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;color:var(--muted)}
.form-field input,.form-field textarea,.form-field select{background:var(--surface2);border:1.5px solid var(--border);border-radius:9px;padding:.75rem 1rem;color:var(--text);font-family:'Instrument Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s,background .3s;width:100%}
.form-field textarea{resize:vertical;min-height:90px;line-height:1.6}
.form-field input:focus,.form-field textarea:focus,.form-field select:focus{border-color:var(--accent)}
.form-field select option{background:var(--surface2)}
.f-hint{font-size:.72rem;color:var(--muted);margin-top:.15rem}
.price-wrap{position:relative}.currency-pfx{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.82rem;font-weight:600;pointer-events:none}.price-wrap input{padding-left:3.5rem}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.modal-foot{padding:1.2rem 1.75rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.7rem}
.btn-cancel{background:transparent;border:1px solid var(--border);color:var(--muted);border-radius:8px;padding:.6rem 1.1rem;font-family:'Instrument Sans',sans-serif;font-size:.88rem;cursor:pointer;transition:all .18s}
.btn-cancel:hover{color:var(--text)}
.btn-submit{background:var(--accent);color:#0b0f1a;border:none;border-radius:8px;padding:.6rem 1.3rem;font-family:'Instrument Sans',sans-serif;font-weight:600;font-size:.88rem;cursor:pointer;transition:all .18s}
.btn-submit:hover{filter:brightness(1.1)}
.no-skills-warn{background:rgba(224,92,92,.06);border:1px solid rgba(224,92,92,.2);border-radius:10px;padding:1rem;font-size:.84rem;color:var(--red);line-height:1.6}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
@media(max-width:800px){.sidebar{display:none}.main{margin-left:0}.services-grid.list-view .svc-card{flex-direction:column}.services-grid.list-view .card-stripe{width:100%;height:4px}.services-grid.list-view .card-footer{border-left:none;border-top:1px solid var(--border);flex-direction:row}.grid-2{grid-template-columns:1fr}}
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
    <a href="dashboard.php" class="nav-item"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="skills.php" class="nav-item active"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>My Skills<?php if(count($services)>0):?><span style="margin-left:auto;background:var(--accent);color:#000;font-size:.65rem;font-weight:700;padding:.12rem .42rem;border-radius:20px"><?=count($services)?></span><?php endif;?></a>
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
      <div class="sb-av"><?php echo $initials;?></div>
      <div><div class="sb-name"><?php echo $me_name;?></div><div class="sb-email"><?php echo $me_email;?></div></div>
    </a>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">
      <h1>My Skills</h1>
      <p>Your services listed on Umu — connected to umu_skill database</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('add')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add New Skill
    </button>
  </header>

  <div class="content">

<?php
// Toasts
$toasts = [
  [$add_ok,'ok'],[$add_err,'err'],
  [$edit_ok,'ok'],[$edit_err,'err'],
];
if($del_msg){
  $parts = explode(':',$del_msg,2);
  $toasts[] = [$parts[1]??$del_msg, $parts[0]==='err'?'err':'info'];
}
foreach($toasts as [$msg,$type]){ if(!$msg) continue;
  echo "<div class='toast $type'>";
  echo $type==='ok'
    ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
    : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
  echo esc($msg).'</div>';
}
?>

    <!-- Stats -->
    <div class="stats-strip">
      <div class="stat-pill"><strong><?php echo (int)$stats['svcs'];?></strong><span>Services</span></div>
      <div class="stat-pill"><strong><?php echo (int)$stats['reqs'];?></strong><span>Total Requests</span></div>
      <div class="stat-pill"><strong><?php echo (int)$stats['done'];?></strong><span>Completed</span></div>
      <div class="stat-pill"><strong style="color:var(--accent)"><?php echo $stats['rating']?number_format($stats['rating'],1).'★':'—';?></strong><span>Avg Rating</span></div>
      <div class="stat-pill"><strong><?php echo count($skills_list);?></strong><span>Skill Categories</span></div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <form method="GET" style="display:contents">
          <input type="text" name="q" value="<?php echo esc($q);?>" class="search-input" placeholder="Search services…" oninput="dbs(this)">
          <input type="hidden" name="skill" value="<?php echo $filter_skill;?>">
        </form>
      </div>
      <form method="GET" style="display:contents">
        <input type="hidden" name="q" value="<?php echo esc($q);?>">
        <select name="skill" class="skill-filter" onchange="this.form.submit()">
          <option value="0">All Categories</option>
          prrint_r($skills_list[0]); die (); ?>
          <?php foreach($skills_list as $sk):?>
          <option value="<?php echo $sk['skill_id'];?>" <?php echo $filter_skill==$sk['skill_id']?'selected':'';?>><?php echo esc($sk['skill_name']);?></option>
          <?php endforeach;?>
        </select>
      </form>
      <div class="view-toggle">
        <div class="view-btn active" id="vg" onclick="setView('grid',this)" title="Grid view">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </div>
        <div class="view-btn" id="vl" onclick="setView('list',this)" title="List view">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </div>
      </div>
    </div>

<?php if(empty($services)):?>
    <div class="empty-state">
      <div class="empty-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
      <h3><?php echo ($q||$filter_skill)?'No services match your filter':'No services yet';?></h3>
      <p><?php echo ($q||$filter_skill)?'Try different keywords or clear the filter.':'List your first skill so others in the community can find and book you.';?></p>
      <?php if(!$q&&!$filter_skill):?>
      <button class="btn btn-primary" onclick="openModal('add')">List My First Skill</button>
      <?php else:?>
      <a href="skills.php" class="btn btn-ghost">Clear Filters</a>
      <?php endif;?>
    </div>
<?php else:?>

    <div class="services-grid" id="sGrid">
<?php foreach($services as $i=>$s):
  $reqs = (int)$s['total_requests'];
  $full = $s['avg_rating'] ? round($s['avg_rating']) : 0;
  $stars = str_repeat('★',$full).str_repeat('☆',5-$full);
?>
      <div class="svc-card" style="animation-delay:<?php echo $i*.07;?>s">
        <div class="card-stripe"></div>
        <div class="card-body">
          <div class="card-top">
            <div>
              <div class="card-title"><?php echo esc($s['title']);?></div>
              <span class="card-skill-badge"><?php echo esc($s['skill_name']);?></span>
            </div>
            <span class="req-count <?php echo $reqs>0?'has':'';?>"><?php echo $reqs;?> req<?php echo $reqs!==1?'s':'';?></span>
          </div>
          <p class="card-desc"><?php echo esc($s['description']);?></p>
          <div class="card-meta">
            <?php if($s['avg_rating']):?>
            <span class="meta-item"><span class="stars"><?php echo $stars;?></span>&nbsp;<?php echo $s['avg_rating'];?> (<?php echo $s['review_count'];?>)</span>
            <?php endif;?>
            <span class="meta-item">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?php echo date('M j, Y',strtotime($s['created_at']));?>
            </span>
            <?php if($s['pending_count']>0):?>
            <span class="meta-item" style="color:var(--accent)">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?php echo (int)$s['pending_count'];?> pending
            </span>
            <?php endif;?>
            <?php if($s['completed_count']>0):?>
            <span class="meta-item" style="color:var(--green)">✓ <?php echo (int)$s['completed_count'];?> done</span>
            <?php endif;?>
          </div>
        </div>
        <div class="card-footer">
          <div>
            <div class="price-amt">UGX <?php echo number_format($s['price'],0);?></div>
            <div class="price-per">per service</div>
          </div>
          <div class="card-actions">
            <a href="skills.php?edit=<?php echo $s['service_id'];?>" class="btn btn-edit btn-sm">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit
            </a>
            <a href="bookings.php?service=<?php echo $s['service_id'];?>" class="btn btn-ghost btn-sm">Requests</a>
            <a href="skills.php?delete=<?php echo $s['service_id'];?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this service?')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
            </a>
          </div>
        </div>
      </div>
<?php endforeach;?>
    </div>
<?php endif;?>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ADD Modal -->
<div class="modal-overlay" id="addModal" onclick="if(event.target===this)closeModal('add')">
  <div class="modal">
    <div class="modal-head">
      <h2>List a New Skill / Service</h2>
      <button class="modal-close" onclick="closeModal('add')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="add_service">
      <div class="modal-body">

        <?php if(empty($skills_list)):?>
        <div class="no-skills-warn">
          ⚠️ No skill categories exist in the <code>skill</code> table yet.<br>
          Ask your admin to INSERT rows into the <code>skill</code> table first, e.g.:<br>
          <code style="font-size:.78rem">INSERT INTO skill (skil_name) VALUES ('Web Development'),('Graphic Design');</code>
        </div>
        <?php endif;?>

        <div class="form-field">
          <label>Skill Category *</label>
          <select name="skill_id" required <?php echo empty($skills_list)?'disabled':'';?>>
            <option value="">— Select —</option>
            <?php foreach($skills_list as $sk):?>
            <option value="<?php echo $sk['skill_id'];?>"><?php echo esc($sk['skill_name']);?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-field">
          <label>Service Title *</label>
          <input type="text" name="title" placeholder="e.g. Logo Design, PHP Web App, English Tuition…" maxlength="150" required>
        </div>
        <div class="form-field">
          <label>Description *</label>
          <textarea name="description" placeholder="Describe what you offer, your experience, delivery time…" required></textarea>
        </div>
        <div class="grid-2">
          <div class="form-field">
            <label>Price (UGX) *</label>
            <div class="price-wrap">
              <span class="currency-pfx">UGX</span>
              <input type="number" name="price" placeholder="50000" min="1000" step="500" required>
            </div>
          </div>
          <div class="form-field">
            <label>Status</label>
            <select name="pub_status">
              <option value="active">Publish now</option>
              <option value="draft">Save as draft</option>
            </select>
            <span class="f-hint">Drafts won't appear in Browse yet.</span>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeModal('add')">Cancel</button>
        <button type="submit" class="btn-submit" <?php echo empty($skills_list)?'disabled':'';?>>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          List Service
        </button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT Modal -->
<div class="modal-overlay" id="editModal" onclick="if(event.target===this)closeModal('edit')">
  <div class="modal">
    <div class="modal-head">
      <h2>Edit Service</h2>
      <button class="modal-close" onclick="closeModal('edit')">✕</button>
    </div>
    <?php if($editing):?>
    <form method="POST">
      <input type="hidden" name="edit_service">
      <input type="hidden" name="service_id" value="<?php echo $editing['service_id'];?>">
      <div class="modal-body">
        <div class="form-field">
          <label>Skill Category *</label>
          <select name="skill_id" required>
            <option value="">— Select —</option>
            <?php foreach($skills_list as $sk):?>
            <option value="<?php echo $sk['skill_id'];?>" <?php echo $sk['skill_id']==$editing['skill_id']?'selected':'';?>><?php echo esc($sk['skil_name']);?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-field">
          <label>Service Title *</label>
          <input type="text" name="title" value="<?php echo esc($editing['title']);?>" maxlength="150" required>
        </div>
        <div class="form-field">
          <label>Description *</label>
          <textarea name="description" required><?php echo esc($editing['description']);?></textarea>
        </div>
        <div class="form-field">
          <label>Price (UGX) *</label>
          <div class="price-wrap">
            <span class="currency-pfx">UGX</span>
            <input type="number" name="price" value="<?php echo number_format($editing['price'],0,'','');?>" min="1000" step="500" required>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <a href="skills.php" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-submit">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>
    </form>
    <?php else:?>
    <div class="modal-body"><p style="color:var(--muted)">No service loaded. <a href="skills.php" style="color:var(--accent)">Go back</a></p></div>
    <?php endif;?>
  </div>
</div>

<script>
document.documentElement.setAttribute('data-theme',localStorage.getItem('umu_theme')||'dark');
<?php if($editing):?>openModal('edit');<?php elseif($add_err):?>openModal('add');<?php endif;?>
function openModal(t){document.getElementById(t+'Modal').classList.add('open')}
function closeModal(t){document.getElementById(t+'Modal').classList.remove('open')}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal('add');closeModal('edit')}});
function setView(v,btn){
  document.querySelectorAll('.view-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('sGrid')?.classList.toggle('list-view',v==='list');
}
let _sd;
function dbs(el){clearTimeout(_sd);_sd=setTimeout(()=>el.closest('form').submit(),420)}
</script>
</body>
</html>