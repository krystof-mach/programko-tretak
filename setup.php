<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "programko_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
$conn->query($sql);
$conn->select_db($dbname);

// 1. Tabulka USERS
$conn->query("CREATE TABLE IF NOT EXISTS `users` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    birth_date DATE,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    avatar VARCHAR(255) DEFAULT 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y',
    role ENUM('user', 'author', 'admin') DEFAULT 'user',
    storage_limit INT(10) DEFAULT 104857600,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Tabulka POSTS
$conn->query("CREATE TABLE IF NOT EXISTS `posts` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    title VARCHAR(100),
    description TEXT,
    file_path VARCHAR(255),
    urls TEXT,
    metadata TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// 3. Tabulka LIKES
$conn->query("CREATE TABLE IF NOT EXISTS `likes` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    post_id INT(6) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_like` (user_id, post_id)
)");

// 4. Tabulka FOLLOWS
$conn->query("CREATE TABLE IF NOT EXISTS `follows` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id INT(6) UNSIGNED NOT NULL,
    followed_id INT(6) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_follow` (follower_id, followed_id)
)");

// 5. Tabulka ROLE_REQUESTS
$conn->query("CREATE TABLE IF NOT EXISTS `role_requests` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// 6. Tabulka CLOUD_FILES
$conn->query("CREATE TABLE IF NOT EXISTS `cloud_files` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100),
    file_size INT(10),
    thumbnail_url VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// 7. Tabulka OFFERS
$conn->query("CREATE TABLE IF NOT EXISTS `offers` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id INT(6) UNSIGNED,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2),
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
)");

// 8. Tabulka BOOKINGS (Sjednocená)
$conn->query("CREATE TABLE IF NOT EXISTS `bookings` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offer_id INT(6) UNSIGNED,
    user_id INT(6) UNSIGNED,
    photographer_id INT(6) UNSIGNED,
    type VARCHAR(100),
    date DATE,
    location VARCHAR(255),
    budget VARCHAR(50),
    description TEXT,
    status ENUM('odeslána', 'potvrzena', 'zamítnuta', 'zamítnuta_s_duvodem', 'protinabídka', 'probíhající', 'hotova') DEFAULT 'odeslána',
    rejection_reason TEXT,
    counter_offer_note TEXT,
    completed_photos_json TEXT,
    is_paid TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// 9. Tabulka LOGIN_LOGS
$conn->query("CREATE TABLE IF NOT EXISTS `login_logs` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20),
    ip_address VARCHAR(45),
    user_agent TEXT
)");

$conn->close();
echo "Database setup completed successfully. <a href='index.php'>Go to Home</a>";
?>
