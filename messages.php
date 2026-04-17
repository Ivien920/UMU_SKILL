<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$current_user = $_SESSION['user_id'];

$upload_error   = "";
$upload_success = "";

/* ===============================
   PROFILE UPLOAD
================================= */
if (isset($_POST['upload'])) {

    if (!isset($_FILES['photo'])) {

        $upload_error = "No file selected.";

    } elseif ($_FILES['photo']['error'] !== 0) {

        switch ($_FILES['photo']['error']) {
            case 1:
            case 2:
                $upload_error = "File too large.";
                break;
            case 3:
                $upload_error = "Upload interrupted.";
                break;
            case 4:
                $upload_error = "No file selected.";
                break;
            default:
                $upload_error = "Upload failed.";
        }

    } else {

        $file = $_FILES['photo'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','gif','webp'];

        if (!in_array($ext, $allowed)) {

            $upload_error = "Only JPG, JPEG, PNG, GIF, WEBP allowed.";

        } else {

            if (!getimagesize($file['tmp_name'])) {

                $upload_error = "Invalid image file.";

            } else {

                $uploadDir = "../uploads/profile/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . "_" . rand(1000,9999) . "." . $ext;

                $target = $uploadDir . $fileName;

                $dbPath = "uploads/profile/" . $fileName;

                if (move_uploaded_file($file['tmp_name'], $target)) {

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET profile_photo=?
                        WHERE user_id=?
                    ");

                    $stmt->execute([$dbPath, $current_user]);

                    $upload_success = "Profile updated.";

                } else {
                    $upload_error = "Failed to save image.";
                }
            }
        }
    }
}

/* ===============================
   CURRENT USER PROFILE
================================= */
$stmt = $pdo->prepare("
    SELECT name, profile_photo
    FROM users
    WHERE user_id=?
");
$stmt->execute([$current_user]);
$me = $stmt->fetch();

$myName = $me['name'];
$myPic  = !empty($me['profile_photo']) ? "../".$me['profile_photo'] : null;

/* ===============================
   CHAT USER
================================= */
$chat_user = $_GET['user'] ?? null;

/* USERS LIST */
$stmt = $pdo->prepare("
SELECT user_id, name, profile_photo
FROM users
WHERE user_id != ?
ORDER BY name ASC
");
$stmt->execute([$current_user]);
$users = $stmt->fetchAll();

/* SELECTED CHAT */
$chatName = "Select a user";
$chatImg  = null;

if ($chat_user) {

    $stmt = $pdo->prepare("
        SELECT name, profile_photo
        FROM users
        WHERE user_id=?
    ");

    $stmt->execute([$chat_user]);
    $row = $stmt->fetch();

    if ($row) {
        $chatName = $row['name'];
        $chatImg  = !empty($row['profile_photo'])
            ? "../".$row['profile_photo']
            : null;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, Helvetica, sans-serif;
    background:#f4f4f4;
}

/* LAYOUT */
.container{
    display:flex;
    height:100vh;
}

/* SIDEBAR */
.sidebar{
    width:32%;
    background:#111;
    color:#fff;
    display:flex;
    flex-direction:column;
    border-right:4px solid #ffcc00;
}

.sidebar-top{
    background:#e60000;
    padding:16px;
    font-size:22px;
    font-weight:bold;
}

/* MY PROFILE */
.my-profile{
    padding:15px;
    background:#1a1a1a;
    text-align:center;
    border-bottom:1px solid #333;
}

.avatar-big{
    width:70px;
    height:70px;
    border-radius:50%;
    overflow:hidden;
    background:#333;
    margin:auto;
    display:flex;
    align-items:center;
    justify-content:center;
}

.avatar-big img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.my-name{
    margin-top:8px;
    font-weight:bold;
}

/* UPLOAD */
.upload-box{
    margin-top:10px;
}

.upload-box input{
    width:100%;
    padding:7px;
    background:#222;
    color:#fff;
    border:none;
    border-radius:6px;
}

.upload-box button{
    width:100%;
    margin-top:6px;
    padding:8px;
    border:none;
    border-radius:6px;
    background:#e60000;
    color:#fff;
    cursor:pointer;
}

.upload-box button:hover{
    background:#b80000;
}

.msg{
    margin-top:6px;
    font-size:12px;
}

.error{color:#ff7777;}
.success{color:#8cff8c;}

/* SEARCH */
.search-box{
    padding:12px;
    background:#1b1b1b;
    border-bottom:1px solid #333;
}

.search-box input{
    width:100%;
    padding:10px 14px;
    border:none;
    border-radius:25px;
    outline:none;
}

/* USERS */
.users{
    flex:1;
    overflow-y:auto;
}

.user{
    border-bottom:1px solid #222;
}

.user a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px;
    text-decoration:none;
    color:#fff;
}

.user a:hover{
    background:#222;
}

.user.active a{
    background:#2b2b2b;
    border-left:4px solid #ffcc00;
}

/* AVATAR */
.avatar{
    width:45px;
    height:45px;
    border-radius:50%;
    overflow:hidden;
    background:#333;
    display:flex;
    align-items:center;
    justify-content:center;
}

.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.user-name{
    font-weight:bold;
    font-size:15px;
}

/* CHAT AREA */
.chat-area{
    width:68%;
    display:flex;
    flex-direction:column;
    background:#fff;
}

/* HEADER */
.chat-header{
    background:#e60000;
    color:#fff;
    padding:15px;
    display:flex;
    align-items:center;
    gap:12px;
    border-bottom:4px solid #ffcc00;
}

.chat-title{
    font-size:18px;
    font-weight:bold;
}

/* CHAT BOX */
.chat-box{
    flex:1;
    overflow-y:auto;
    padding:20px;
    background:#f8f8f8;
}

/* FOOTER */
.chat-footer{
    display:flex;
    gap:10px;
    padding:12px;
    background:#111;
}

.chat-footer input[type=text]{
    flex:1;
    padding:12px;
    border:none;
    border-radius:25px;
}

.chat-footer button{
    padding:12px 20px;
    border:none;
    border-radius:25px;
    background:#e60000;
    color:#fff;
    cursor:pointer;
}

.chat-footer button:hover{
    background:#b80000;
}

.empty{
    text-align:center;
    margin-top:100px;
    color:#666;
}

/* MOBILE */
@media(max-width:800px){

    .container{
        flex-direction:column;
    }

    .sidebar,
    .chat-area{
        width:100%;
        height:50vh;
    }
}
</style>
</head>

<body>

<div class="container">

<!-- SIDEBAR -->
<div class="sidebar">

<div class="sidebar-top">Chats</div>

<!-- MY PROFILE -->
<div class="my-profile">

<div class="avatar-big">
<?php if($myPic): ?>
<img src="<?php echo $myPic.'?t='.time(); ?>">
<?php else: ?>
<svg width="32" height="32" viewBox="0 0 24 24" fill="#aaa">
<path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
</svg>
<?php endif; ?>
</div>

<div class="my-name">
<?php echo htmlspecialchars($myName); ?>
</div>

<div class="upload-box">

<form method="POST" enctype="multipart/form-data">

<input type="file"
       name="photo"
       accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
       required>

<button type="submit" name="upload">
Update Picture
</button>

</form>

<?php if($upload_error): ?>
<div class="msg error"><?php echo $upload_error; ?></div>
<?php endif; ?>

<?php if($upload_success): ?>
<div class="msg success"><?php echo $upload_success; ?></div>
<?php endif; ?>

</div>

</div>

<!-- SEARCH -->
<div class="search-box">
<input type="text"
       id="searchUser"
       placeholder="Search user...">
</div>

<!-- USERS -->
<div class="users" id="userList">

<?php foreach($users as $user):

$active = ($chat_user == $user['user_id']) ? 'active' : '';

$img = !empty($user['profile_photo'])
    ? "../".$user['profile_photo']
    : null;
?>

<div class="user <?php echo $active; ?> user-row">

<a href="?user=<?php echo $user['user_id']; ?>">

<div class="avatar">

<?php if($img): ?>
<img src="<?php echo $img.'?t='.time(); ?>">
<?php else: ?>
<svg width="22" height="22" viewBox="0 0 24 24" fill="#aaa">
<path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
</svg>
<?php endif; ?>

</div>

<div class="user-name">
<?php echo htmlspecialchars($user['name']); ?>
</div>

</a>

</div>

<?php endforeach; ?>

</div>

</div>

<!-- CHAT AREA -->
<div class="chat-area">

<!-- HEADER -->
<div class="chat-header">

<div class="avatar">

<?php if($chatImg): ?>
<img src="<?php echo $chatImg.'?t='.time(); ?>">
<?php else: ?>
<svg width="22" height="22" viewBox="0 0 24 24" fill="#fff">
<path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
</svg>
<?php endif; ?>

</div>

<div class="chat-title">
<?php echo htmlspecialchars($chatName); ?>
</div>

</div>

<!-- MESSAGES -->
<div class="chat-box">

<?php if($chat_user): ?>
<?php include __DIR__ . "/fetch_message.php"; ?>
<?php else: ?>
<div class="empty">
Select a user to start chatting
</div>
<?php endif; ?>

</div>

<!-- FOOTER -->
<?php if($chat_user): ?>

<form class="chat-footer"
      action="send_message.php"
      method="POST">

<input type="hidden"
       name="receiver_id"
       value="<?php echo $chat_user; ?>">

<input type="text"
       name="message"
       placeholder="Type a message...">

<button type="submit">Send</button>

</form>

<?php endif; ?>

</div>

</div>

<script>
document.getElementById("searchUser").addEventListener("keyup", function(){

    let value = this.value.toLowerCase();

    let users = document.querySelectorAll(".user-row");

    users.forEach(function(user){

        let text = user.innerText.toLowerCase();

        user.style.display =
            text.includes(value) ? "block" : "none";
    });
});
</script>

</body>
</html>