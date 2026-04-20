<?php
session_start();
require_once 'connection.php';

// Create messages table if it doesn't exist
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT,
        file_path VARCHAR(255),
        file_type VARCHAR(100),
        deleted TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
    )');
} catch (Exception $e) {
    // Table creation failed - this is handled silently
    error_log('Messages table creation failed: ' . $e->getMessage());
}


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['name'] ?? 'User');
$user_email = htmlspecialchars($_SESSION['user'] ?? '');
$initials = strtoupper(substr($user_name, 0, 1));

// Get user profile photo
$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
$stmt->execute([$current_user]);
$user_data = $stmt->fetch();
$profile_photo = $user_data['profile_photo'] ?? null;
$profile_photo_path = $profile_photo ? 'uploads/avatars/' . $profile_photo : null;

// Get chat user
$chat_user = $_GET['user'] ?? null;

// Get only users who have previously exchanged messages with current user
$stmt = $pdo->prepare("
    SELECT DISTINCT u.user_id, u.name, u.profile_photo
    FROM users u
    WHERE u.user_id != ?
    AND (
        EXISTS (SELECT 1 FROM messages m WHERE m.sender_id = ? AND m.receiver_id = u.user_id)
        OR
        EXISTS (SELECT 1 FROM messages m WHERE m.receiver_id = ? AND m.sender_id = u.user_id)
    )
    ORDER BY u.name ASC
");
$stmt->execute([$current_user, $current_user, $current_user]);
$users = $stmt->fetchAll();

// Get selected chat user info
$chatName = "Select a user";
$chatImg = null;
$chatUserId = null;

if ($chat_user) {
    $stmt = $pdo->prepare("
        SELECT user_id, name, profile_photo
        FROM users
        WHERE user_id = ?
    ");
    $stmt->execute([$chat_user]);
    $row = $stmt->fetch();

    if ($row) {
        $chatName = $row['name'];
        $chatImg = $row['profile_photo'] ? 'uploads/avatars/' . $row['profile_photo'] : null;
        $chatUserId = $row['user_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Messages — Umu Skill Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root,[data-theme="dark"]{--bg:#0b0f1a;--surface:#141927;--surface2:#1c2438;--border:#252d42;--accent:#f5a623;--accent2:#e05c2a;--text:#e8eaf0;--muted:#6b7592;--green:#34c97a;--blue:#4a90e2;--radius:14px}
    [data-theme="light"]{--bg:#f0f2f5;--surface:#fff;--surface2:#f4f5f7;--border:#dde0e8;--accent:#e08c10;--accent2:#c8522a;--text:#111827;--muted:#6b7280;--green:#16a34a;--blue:#2563eb}
    html,body{transition:background .3s,color .3s}
    body{background:var(--bg);color:var(--text);font-family:'Instrument Sans',sans-serif;min-height:100vh;display:flex}

    /* Sidebar */
    .sidebar{width:300px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:background .3s,border-color .3s}
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
    .main{margin-left:300px;flex:1;min-height:100vh;display:flex;flex-direction:column}
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

    /* Messages Layout */
    .messages-container{display:flex;flex:1}
    .users-panel{width:280px;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column}
    .chat-panel{flex:1;display:flex;flex-direction:column;background:var(--bg)}

    /* Users List */
    .users-header{padding:1.2rem 1rem;border-bottom:1px solid var(--border);background:var(--surface)}
    .users-header h3{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin:0}
    .users-list{flex:1;overflow-y:auto}
    .user-item{display:flex;align-items:center;gap:.8rem;padding:1rem;border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;text-decoration:none;color:inherit}
    .user-item:hover{background:var(--surface2)}
    .user-item.active{background:rgba(245,166,35,.08);border-left:3px solid var(--accent)}
    .user-avatar{width:40px;height:40px;border-radius:50%;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-weight:600;color:var(--accent);flex-shrink:0;overflow:hidden}
    .user-avatar img{width:100%;height:100%;object-fit:cover}
    .user-info{flex:1;min-width:0}
    .user-name{font-weight:500;margin-bottom:.2rem}
    .user-status{font-size:.75rem;color:var(--muted)}

    /* Chat Area */
    .chat-header{padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);background:var(--surface);display:flex;align-items:center;gap:1rem}
    .chat-user-avatar{width:40px;height:40px;border-radius:50%;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-weight:600;color:var(--accent);overflow:hidden}
    .chat-user-avatar img{width:100%;height:100%;object-fit:cover}
    .chat-user-info{flex:1}
    .chat-user-name{font-weight:600;font-size:1.1rem}
    .chat-user-status{font-size:.8rem;color:var(--muted)}
    .chat-messages{flex:1;overflow-y:auto;padding:1.5rem;background:linear-gradient(to bottom,var(--bg),var(--surface))}
    .chat-input-area{border-top:1px solid var(--border);background:var(--surface);padding:1rem 1.5rem}

    /* Messages */
    .message{display:flex;margin-bottom:1rem;max-width:70%}
    .message.other{justify-content:flex-start}
    .message.mine{justify-content:flex-end;margin-left:auto}
    .message-avatar{width:32px;height:32px;border-radius:50%;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;color:var(--accent);margin-right:.8rem;flex-shrink:0;overflow:hidden}
    .message-avatar img{width:100%;height:100%;object-fit:cover}
    .message-content{flex:1}
    .message-sender{font-size:.75rem;color:var(--muted);margin-bottom:.3rem}
    .message-bubble{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;position:relative}
    .message.mine .message-bubble{background:var(--accent);color:#0b0f1a;border-color:var(--accent)}
    .message-text{line-height:1.5}
    .message-time{font-size:.7rem;color:var(--muted);margin-top:.5rem;text-align:right}
    .message.mine .message-time{color:rgba(11,15,26,.6)}
    .message-image{max-width:200px;border-radius:8px;margin-top:.5rem}
    .file-link{color:var(--accent);text-decoration:none;font-weight:500}
    .file-link:hover{text-decoration:underline}
    .delete-btn{background:none;border:none;color:#ff6b6b;cursor:pointer;font-size:1.2rem;padding:0;margin-left:.5rem;opacity:.7}
    .delete-btn:hover{opacity:1}

    /* Input Form */
    .message-form{display:flex;gap:.8rem}
    .message-input{flex:1;padding:.8rem 1rem;border:1px solid var(--border);border-radius:8px;background:var(--surface);color:var(--text);font-family:inherit}
    .message-input:focus{outline:none;border-color:var(--accent)}
    .file-input{display:none}
    .file-btn{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:.8rem;cursor:pointer;color:var(--muted)}
    .file-btn:hover{background:var(--surface);border-color:var(--muted)}
    .send-btn{background:var(--accent);color:#0b0f1a;border:none;border-radius:8px;padding:.8rem 1.5rem;font-weight:600;cursor:pointer}
    .send-btn:hover{filter:brightness(1.1)}

    /* Empty State */
    .empty{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;text-align:center;padding:3rem;color:var(--muted)}
    .empty svg{opacity:.4;margin-bottom:1rem}
    .empty h3{font-family:'Syne',sans-serif;font-size:1.2rem;margin-bottom:.5rem}
    .empty p{font-size:.9rem;max-width:300px}

    /* Responsive */
    @media(max-width:900px){
      .sidebar{display:none}
      .main{margin-left:0}
      .users-panel{display:none}
      .chat-panel{flex:1}
    }

    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
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
    <a href="dashboard.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard
    </a>
    <a href="skills.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>My Skills
    </a>
    <a href="bookings.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Bookings
    </a>
    <a href="browse.php" class="nav-item">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Browse Skills
    </a>
    <a href="messages.php" class="nav-item active">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages
    </a>

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
    <a href="profile.php" class="user-pill">
      <div class="sb-av">
        <?php if ($profile_photo_path): ?><img src="<?= htmlspecialchars($profile_photo_path) ?>" alt=""><?php else: ?><?= $initials ?><?php endif; ?>
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
      <h1>Messages</h1>
      <p>Chat with other users</p>
    </div>
    <div class="topbar-actions">
      <a href="dashboard.php" class="btn btn-ghost btn-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1 1h6a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4"/></svg>Back to Dashboard
      </a>
    </div>
  </header>

  <div class="messages-container">
    <!-- Users Panel -->
    <div class="users-panel">
      <div class="users-header">
        <h3>Conversations</h3>
        <div style="margin-top:.75rem;position:relative">
          <input type="text" id="userSearch" placeholder="Search all users…" 
            style="width:100%;padding:.55rem .9rem .55rem 2.2rem;border:1px solid var(--border);border-radius:8px;background:var(--surface2);color:var(--text);font-family:inherit;font-size:.83rem;outline:none;transition:border-color .18s"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
          <svg style="position:absolute;left:.65rem;top:50%;transform:translateY(-50%);opacity:.45;pointer-events:none" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>
      </div>
      <div class="users-list" id="usersList">
        <!-- Connected users (always shown) -->
        <?php if (empty($users)): ?>
        <div style="padding:1.5rem 1rem;color:var(--muted);font-size:.85rem;text-align:center">
          No conversations yet.<br>Search for a user above to start chatting.
        </div>
        <?php else: ?>
        <?php foreach ($users as $user): ?>
        <a href="?user=<?= $user['user_id'] ?>" class="user-item connected-user <?= ($chat_user == $user['user_id']) ? 'active' : '' ?>" data-name="<?= strtolower(htmlspecialchars($user['name'])) ?>">
          <div class="user-avatar">
            <?php if ($user['profile_photo']): ?>
              <img src="<?= htmlspecialchars('uploads/avatars/' . $user['profile_photo']) ?>" alt="">
            <?php else: ?>
              <?= strtoupper(substr($user['name'], 0, 1)) ?>
            <?php endif; ?>
          </div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="user-status">Previous conversation</div>
          </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Search results (hidden by default, shown when searching) -->
        <div id="searchResults" style="display:none;border-top:1px solid var(--border)">
          <div style="padding:.5rem 1rem;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)">All Users</div>
          <?php
          // Get ALL other users for search
          $stmtAll = $pdo->prepare("SELECT user_id, name, profile_photo FROM users WHERE user_id != ? ORDER BY name ASC");
          $stmtAll->execute([$current_user]);
          $allUsers = $stmtAll->fetchAll();
          foreach ($allUsers as $u): ?>
          <a href="?user=<?= $u['user_id'] ?>" class="user-item search-user" data-name="<?= strtolower(htmlspecialchars($u['name'])) ?>">
            <div class="user-avatar">
              <?php if ($u['profile_photo']): ?>
                <img src="<?= htmlspecialchars('uploads/avatars/' . $u['profile_photo']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($u['name'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <div class="user-info">
              <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
              <div class="user-status">Click to chat</div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Chat Panel -->
    <div class="chat-panel">
      <?php if ($chat_user && $chatUserId): ?>
      <div class="chat-header">
        <div class="chat-user-avatar">
          <?php if ($chatImg): ?>
            <img src="<?= htmlspecialchars($chatImg) ?>" alt="">
          <?php else: ?>
            <?= strtoupper(substr($chatName, 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div class="chat-user-info">
          <div class="chat-user-name"><?= htmlspecialchars($chatName) ?></div>
          <div class="chat-user-status">Active now</div>
        </div>
      </div>

      <div class="chat-messages" id="messages-container">
        <?php include 'fetch_message.php'; ?>
      </div>

      <div class="chat-input-area">
        <form method="POST" action="send_message.php" enctype="multipart/form-data" class="message-form">
          <input type="hidden" name="receiver_id" value="<?= $chatUserId ?>">
          <input type="text" name="message" class="message-input" placeholder="Type a message..." required>
          <input type="file" name="file" id="file-input" class="file-input" accept="image/*,.pdf,.doc,.docx">
          <label for="file-input" class="file-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          </label>
          <button type="submit" class="send-btn">Send</button>
        </form>
      </div>
      <?php else: ?>
      <div class="empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <h3>Select a user to start chatting</h3>
        <p>Choose someone from the sidebar to begin a conversation.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Auto-scroll to bottom of messages
function scrollToBottom() {
  const container = document.getElementById('messages-container');
  if (container) {
    container.scrollTop = container.scrollHeight;
  }
}

// Scroll on page load
scrollToBottom();

// Search: show all users filtered by query; hide connected list, show search results
const searchInput = document.getElementById('userSearch');
const searchResults = document.getElementById('searchResults');
const connectedUsers = document.querySelectorAll('.connected-user');

if (searchInput) {
  searchInput.addEventListener('input', function() {
    const query = this.value.trim().toLowerCase();

    if (query.length === 0) {
      // Back to default: show connected, hide search panel
      searchResults.style.display = 'none';
      connectedUsers.forEach(u => u.style.display = 'flex');
    } else {
      // Hide connected users, show search panel with filtered results
      connectedUsers.forEach(u => u.style.display = 'none');
      searchResults.style.display = 'block';

      document.querySelectorAll('.search-user').forEach(u => {
        const name = u.getAttribute('data-name') || '';
        u.style.display = name.includes(query) ? 'flex' : 'none';
      });
    }
  });
}

// Refresh messages every 5 seconds if chat is active
<?php if ($chat_user): ?>
setInterval(() => {
  fetch('fetch_message.php?user=<?= $chat_user ?>')
    .then(response => response.text())
    .then(html => {
      document.getElementById('messages-container').innerHTML = html;
      scrollToBottom();
    })
    .catch(error => console.log('Error refreshing messages:', error));
}, 5000);
<?php endif; ?>
</script>

</body>
</html>