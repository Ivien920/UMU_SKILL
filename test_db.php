<?php
require 'connection.php';

try {
    // Test connection
    echo "Database connection successful!<br>";

    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "Users table exists!<br>";

        // Check if there are any users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "Number of users: " . $result['count'] . "<br>";

        // Show sample user if exists
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT user_id, name, email FROM users LIMIT 1");
            $user = $stmt->fetch();
            echo "Sample user: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")<br>";
        }
    } else {
        echo "Users table does not exist!<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?></content>
<parameter name="filePath">c:\wamp64\www\umu_skill_marketplace\umu_skill\test_db.php