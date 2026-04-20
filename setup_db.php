<?php
require 'connection.php';

try {
    // Create users table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        role_id INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        location VARCHAR(255),
        profile_photo VARCHAR(255),
        theme ENUM('light', 'dark') DEFAULT 'dark'
    )");

    echo "Users table created successfully!<br>";

    // Check if there's already a test user
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->execute(['test@umu.ac.ug']);
    $result = $stmt->fetch();

    if ($result['count'] == 0) {
        // Create a test user
        $hashedPassword = password_hash('Test1234', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Test User', 'test@umu.ac.ug', $hashedPassword, '0700123456', 'Kampala']);

        echo "Test user created!<br>";
        echo "Email: test@umu.ac.ug<br>";
        echo "Password: Test1234<br>";
    } else {
        echo "Test user already exists!<br>";
    }

    // Show all users
    $stmt = $pdo->query("SELECT user_id, name, email FROM users");
    $users = $stmt->fetchAll();

    echo "<br>Current users:<br>";
    foreach ($users as $user) {
        echo "- " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?></content>
<parameter name="filePath">c:\wamp64\www\umu_skill_marketplace\umu_skill\setup_db.php