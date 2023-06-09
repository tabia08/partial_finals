<?php
$servername = "";
$username = "root";
$password = "";
$dbname = "spogify";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the users table
    $usersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($usersTable);

    // Create the user_songs table
    $userSongsTable = "CREATE TABLE IF NOT EXISTS user_songs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        song_title VARCHAR(100) NOT NULL,
        play_count INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($userSongsTable);

    echo "Tables created successfully!";
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
}
?>
