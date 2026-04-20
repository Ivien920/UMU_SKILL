<?php
require 'connection.php';

try {
    echo "Database connection successful!<br><br>";

    // Check if users table exists
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "✅ Users table exists<br>";

        // Check users count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "✅ Users count: $count<br>";

        if ($count > 0) {
            // Show users
            $stmt = $pdo->query("SELECT user_id, name, email FROM users LIMIT 5");
            $users = $stmt->fetchAll();
            echo "<br>Sample users:<br>";
            foreach ($users as $user) {
                echo "- {$user['name']} ({$user['email']})<br>";
            }
        }
    } else {
        echo "❌ Users table does not exist<br>";
    }

    // Check if messages table exists
    $result = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($result->rowCount() > 0) {
        echo "✅ Messages table exists<br>";

        // Check messages count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
        $count = $stmt->fetch()['count'];
        echo "✅ Messages count: $count<br>";
    } else {
        echo "❌ Messages table does not exist<br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?></content>
<parameter name="filePath">c:\wamp64\www\umu_skill_marketplace\umu_skill\check_db.php