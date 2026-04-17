<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$current_user = $_SESSION['user_id'];
$chat_user = $_GET['user'] ?? 0;

/* =========================
   GET MESSAGES BETWEEN USERS
========================= */
$stmt = $pdo->prepare("
    SELECT * FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY sent_at ASC
");

$stmt->execute([
    $current_user,
    $chat_user,
    $chat_user,
    $current_user
]);

$messages = $stmt->fetchAll();

/* =========================
   DISPLAY MESSAGES
========================= */
foreach ($messages as $msg) {

    $is_me = $msg['sender_id'] == $current_user;

    echo "<div style='
        max-width:60%;
        margin:10px;
        padding:10px;
        border-radius:10px;
        clear:both;
        ";

    if ($is_me) {
        echo "background:#e60000;color:white;float:right;";
    } else {
        echo "background:#111;color:white;float:left;";
    }

    echo "'>";

    /* ================= TEXT MESSAGE ================= */
    if (!empty($msg['message'])) {
        echo "<p style='margin:0 0 5px 0;'>" . htmlspecialchars($msg['message']) . "</p>";
    }

    /* ================= FILE DISPLAY ================= */
    if (!empty($msg['file_path'])) {

        $file = $msg['file_path'];
        $type = $msg['file_type'];

        if (strpos($type, 'image') !== false) {
            echo "<img src='$file' style='max-width:100%; border-radius:8px;'>";
        }
        elseif (strpos($type, 'video') !== false) {
            echo "<video controls style='max-width:100%; border-radius:8px;'>
                    <source src='$file'>
                  </video>";
        }
        else {
            echo "<a href='$file' style='color:#ffcc00;' target='_blank'>Download file</a>";
        }
    }

    echo "<br><small style='font-size:10px;opacity:0.7;'>
            " . $msg['sent_at'] . "
          </small>";

    echo "</div>";
}
?>

