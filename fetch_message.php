<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$current_user = $_SESSION['user_id'];
$chat_user = $_GET['user'] ?? null;

if (!$chat_user) {
    echo '<div class="empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <h3>Select a user to start chatting</h3>
        <p>Choose someone from the sidebar to begin a conversation.</p>
    </div>';
    exit;
}

// Get messages between current user and chat user
$stmt = $pdo->prepare("
    SELECT m.id, m.sender_id, m.receiver_id, m.message, m.file_path, m.file_type, m.created_at,
           u.name as sender_name, u.profile_photo as sender_photo
    FROM messages m
    JOIN users u ON u.user_id = m.sender_id
    WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
    AND m.deleted = 0
    ORDER BY m.created_at ASC
");

$stmt->execute([$current_user, $chat_user, $chat_user, $current_user]);
$messages = $stmt->fetchAll();

foreach ($messages as $msg) {
    $isMine = $msg['sender_id'] == $current_user;
    $time = date('H:i', strtotime($msg['created_at']));

    echo '<div class="message ' . ($isMine ? 'mine' : 'other') . '">';

    if (!$isMine) {
        echo '<div class="message-avatar">';
        if ($msg['sender_photo']) {
            echo '<img src="' . htmlspecialchars('uploads/avatars/' . $msg['sender_photo']) . '" alt="">';
        } else {
            echo strtoupper(substr($msg['sender_name'], 0, 1));
        }
        echo '</div>';
    }

    echo '<div class="message-content">';

    if (!$isMine) {
        echo '<div class="message-sender">' . htmlspecialchars($msg['sender_name']) . '</div>';
    }

    echo '<div class="message-bubble">';

    if ($msg['file_path']) {
        if (strpos($msg['file_type'], 'image/') === 0) {
            echo '<img src="' . htmlspecialchars($msg['file_path']) . '" class="message-image" alt="Shared image">';
        } else {
            echo '<a href="' . htmlspecialchars($msg['file_path']) . '" target="_blank" class="file-link">📎 ' . basename($msg['file_path']) . '</a>';
        }
    }

    if ($msg['message']) {
        echo '<div class="message-text">' . nl2br(htmlspecialchars($msg['message'])) . '</div>';
    }

    echo '<div class="message-time">' . $time . '</div>';

    if ($isMine) {
        echo '<form method="POST" action="delete_messages.php" class="delete-form" style="display:inline;">
            <input type="hidden" name="message_id" value="' . $msg['id'] . '">
            <input type="hidden" name="chat_user" value="' . $chat_user . '">
            <button type="submit" class="delete-btn" title="Delete message">×</button>
        </form>';
    }

    echo '</div></div></div>';
}
?>