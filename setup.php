<?php


require_once 'components/connector.php';

echo "<h3>Initializing System Setup...</h3>";


$sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "✔ Database `" . DB_NAME . "` ready.<br>";
} else {
    
    echo "⚠ Could not create database (might already exist or insufficient permissions): " . $conn->error . "<br>";
}

if (!$conn->select_db(DB_NAME)) {
    die("❌ Database selection failed: " . $conn->error);
}

$conn->set_charset("utf8mb4");


$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS `users` (
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
        storage_limit BIGINT DEFAULT 104857600,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "posts" => "CREATE TABLE IF NOT EXISTS `posts` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
        title VARCHAR(100),
        description TEXT,
        file_path VARCHAR(255),
        urls TEXT,
        metadata TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "likes" => "CREATE TABLE IF NOT EXISTS `likes` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED NOT NULL,
        post_id INT(6) UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_like` (user_id, post_id)
    )",
    "follows" => "CREATE TABLE IF NOT EXISTS `follows` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        follower_id INT(6) UNSIGNED NOT NULL,
        followed_id INT(6) UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_follow` (follower_id, followed_id)
    )",
    "role_requests" => "CREATE TABLE IF NOT EXISTS `role_requests` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "cloud_files" => "CREATE TABLE IF NOT EXISTS `cloud_files` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100),
        file_size BIGINT DEFAULT 0,
        thumbnail_url VARCHAR(255),
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "offers" => "CREATE TABLE IF NOT EXISTS `offers` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        author_id INT(6) UNSIGNED,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2),
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "bookings" => "CREATE TABLE IF NOT EXISTS `bookings` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        offer_id INT(6) UNSIGNED,
        user_id INT(6) UNSIGNED,
        photographer_id INT(6) UNSIGNED,
        type VARCHAR(100),
        date DATE,
        location VARCHAR(255),
        budget VARCHAR(50),
        description TEXT,
        guest_name VARCHAR(100),
        guest_email VARCHAR(100),
        guest_phone VARCHAR(50),
        status ENUM('odeslána', 'potvrzena', 'zamítnuta', 'zamítnuta_s_duvodem', 'protinabídka', 'probíhající', 'hotova') DEFAULT 'odeslána',
        rejection_reason TEXT,
        counter_offer_note TEXT,
        completed_photos_json TEXT,
        access_hash VARCHAR(64) UNIQUE,
        is_paid TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "login_logs" => "CREATE TABLE IF NOT EXISTS `login_logs` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20),
        ip_address VARCHAR(45),
        user_agent TEXT
    )",
    "reviews" => "CREATE TABLE IF NOT EXISTS `reviews` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        booking_id INT(6) UNSIGNED,
        photographer_id INT(6) UNSIGNED,
        user_id INT(6) UNSIGNED,
        rating TINYINT(1) NOT NULL,
        comment TEXT,
        approved TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (photographer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "comments" => "CREATE TABLE IF NOT EXISTS `comments` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT(6) UNSIGNED NOT NULL,
        user_id INT(6) UNSIGNED NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "profile_blocks" => "CREATE TABLE IF NOT EXISTS `profile_blocks` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED NOT NULL,
        type ENUM('bio', 'gallery', 'offers', 'reviews', 'contact') NOT NULL,
        content LONGTEXT,
        position INT(3) DEFAULT 0,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $name => $query) {
    if ($conn->query($query)) {
        echo "✔ Table `$name` ready.<br>";
    } else {
        echo "❌ Error creating table `$name`: " . $conn->error . "<br>";
    }
}


echo "<h4>Running Migrations...</h4>";


$check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'storage_limit'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN storage_limit BIGINT DEFAULT 104857600");
    echo "✔ Added `storage_limit` column to `users`.<br>";
} else {
    
    $conn->query("ALTER TABLE `users` MODIFY COLUMN storage_limit BIGINT DEFAULT 104857600");
    echo "✔ Checked `storage_limit` type in `users`.<br>";
}


$check = $conn->query("SHOW COLUMNS FROM `cloud_files` LIKE 'file_size'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE `cloud_files` ADD COLUMN file_size BIGINT DEFAULT 0");
    echo "✔ Added `file_size` column to `cloud_files`.<br>";
} else {
    $conn->query("ALTER TABLE `cloud_files` MODIFY COLUMN file_size BIGINT DEFAULT 0");
    echo "✔ Checked `file_size` type in `cloud_files`.<br>";
}


if (!file_exists('cloud')) {
    if (mkdir('cloud', 0777, true)) {
        echo "✔ Directory `cloud` created.<br>";
    } else {
        echo "❌ Failed to create directory `cloud`.<br>";
    }
} else {
    echo "✔ Directory `cloud` already exists.<br>";
}



echo "<h4>Applying User Role Fixes...</h4>";
$conn->query("UPDATE users SET role = 'admin' WHERE username = 'admin'");
$conn->query("UPDATE users SET role = 'author' WHERE username = 'fotograf'");
$conn->query("UPDATE users SET role = 'user' WHERE username = 'klient'");
echo "✔ Attempted to update roles for 'admin', 'fotograf', and 'klient'.<br>";


echo "<h4>Securing Passwords...</h4>";
$result = $conn->query("SELECT id, password FROM users");
$hashed_count = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $pass = $row['password'];
        if (strpos($pass, '$2y$') !== 0) {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $id);
            $stmt->execute();
            $hashed_count++;
        }
    }
}
echo "✔ Hashed $hashed_count unhashed passwords.<br>";


echo "<h4>Populating Sample Data...</h4>";
$users_to_seed = [
    [
        'username' => 'admin',
        'full_name' => 'System Administrator',
        'email' => 'admin@photoahh.cz',
        'password' => password_hash('12345', PASSWORD_DEFAULT),
        'role' => 'admin',
        'bio' => 'Hlavní správce systému Photo Ahh.'
    ],
    [
        'username' => 'fotograf',
        'full_name' => 'Tomáš Blesk',
        'email' => 'fotograf@photoahh.cz',
        'password' => password_hash('12345', PASSWORD_DEFAULT),
        'role' => 'author',
        'bio' => 'Profesionální fotograf se zaměřením na portréty a krajinu.'
    ],
    [
        'username' => 'klient',
        'full_name' => 'Jan Novák',
        'email' => 'klient@photoahh.cz',
        'password' => password_hash('12345', PASSWORD_DEFAULT),
        'role' => 'user',
        'bio' => 'Milovník kvalitní fotografie.'
    ]
];

foreach ($users_to_seed as $u) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $u['username']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO users (username, full_name, email, password, role, bio) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->bind_param("ssssss", $u['username'], $u['full_name'], $u['email'], $u['password'], $u['role'], $u['bio']);
        $insert->execute();
        echo "✔ User `{$u['username']}` created.<br>";
    }
}


$res = $conn->query("SELECT id FROM users WHERE username = 'fotograf'");
if ($row = $res->fetch_assoc()) {
    $author_id = $row['id'];
    $check_posts = $conn->query("SELECT id FROM posts WHERE user_id = $author_id");
    if ($check_posts->num_rows == 0) {
        $title = "Vítejte ve Photo Ahh!";
        $desc = "Tohle je ukázkový příspěvek vytvořený během instalace.";
        $img = "https://images.unsplash.com/photo-1542038784456-1ea8e935640e?w=800";
        $urls = json_encode([$img]);
        $conn->query("INSERT INTO posts (user_id, title, description, urls) VALUES ($author_id, '$title', '$desc', '$urls')");
        echo "✔ Sample post created.<br>";
    }
}

$conn->close();

echo "<h3>Setup completed successfully!</h3>";
echo "<p><a href='index.php'>Go to Home Page</a> | <a href='login.php'>Login</a></p>";
?>
