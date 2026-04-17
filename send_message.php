<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$sender_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    $file_path = null;
    $file_type = null;

    /* =========================
       FILE UPLOAD HANDLING
    ========================== */
    if (!empty($_FILES['file']['name'])) {

        $upload_dir = "uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $file_name;

        $file_type = $_FILES['file']['type'];

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        }
    }

    /* =========================
       INSERT MESSAGE
    ========================== */
    $stmt = $pdo->prepare("
        INSERT INTO messages 
        (sender_id, receiver_id, message, file_path, file_type)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $sender_id,
        $receiver_id,
        $message,
        $file_path,
        $file_type
    ]);

    header("Location: messages.php?user=" . $receiver_id);
    exit;
}
?>