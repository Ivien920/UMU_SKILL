<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die("Login required.");
}

$current_user = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $message_id = $_POST['message_id'] ?? null;
    $chat_user = $_POST['chat_user'] ?? null;

    if ($message_id) {
        // Soft delete - mark as deleted
        $stmt = $pdo->prepare("
            UPDATE messages
            SET deleted = 1
            WHERE id = ?
            AND sender_id = ?
        ");

        $stmt->execute([$message_id, $current_user]);
    }
}

/* safe redirect */
if (!empty($chat_user)) {
    header("Location: messages.php?user=" . $chat_user);
} else {
    header("Location: messages.php");
}

exit;
?>