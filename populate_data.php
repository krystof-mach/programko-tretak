<?php
/**
 * Data Population Script for programko-tretak
 * Adds default users and sample content for testing.
 */

require_once 'components/connector.php';

echo "<h3>Populating Sample Data...</h3>";

// 1. Create Default Users
$users = [
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

foreach ($users as $user) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $user['username']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO users (username, full_name, email, password, role, bio) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->bind_param("ssssss", $user['username'], $user['full_name'], $user['email'], $user['password'], $user['role'], $user['bio']);
        if ($insert->execute()) {
            echo "✔ User `{$user['username']}` created.<br>";
        }
    } else {
        echo "ℹ User `{$user['username']}` already exists.<br>";
    }
}

// 2. Add sample post for 'fotograf'
$res = $conn->query("SELECT id FROM users WHERE username = 'fotograf'");
if ($row = $res->fetch_assoc()) {
    $author_id = $row['id'];
    
    $check_posts = $conn->query("SELECT id FROM posts WHERE user_id = $author_id");
    if ($check_posts->num_rows == 0) {
        $title = "Vítejte ve Photo Ahh!";
        $desc = "Tohle je ukázkový příspěvek vytvořený během instalace.";
        // Použijeme nějaký placeholder obrázek
        $img = "https://images.unsplash.com/photo-1542038784456-1ea8e935640e?w=800";
        $urls = json_encode([$img]);
        
        $conn->query("INSERT INTO posts (user_id, title, description, urls) VALUES ($author_id, '$title', '$desc', '$urls')");
        echo "✔ Sample post created.<br>";
    }
}

echo "<h4>Data population finished.</h4>";
?>
