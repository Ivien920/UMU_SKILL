<?php
include '../middleware/auth_check.php';
include '../connection.php';

$user_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'];

// Fetch messages
$query = "SELECT * FROM message 
WHERE (sender_id='$user_id' AND receiver_id='$receiver_id')
   OR (sender_id='$receiver_id' AND receiver_id='$user_id')
ORDER BY sent_at ASC";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    echo "<p><b>{$row['sender_id']}:</b> {$row['message_text']}</p>";
}
?>

<form method="POST" action="send_message.php">
    <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
    <input type="text" name="message">
    <button>Send</button>
</form>