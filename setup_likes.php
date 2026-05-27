<?php
require_once 'components/connector.php';

// Vytvoření tabulky pro lajky
$sql = "CREATE TABLE IF NOT EXISTS `likes` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    post_id INT(6) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_like` (user_id, post_id)
)";

if ($conn->query($sql)) {
    echo "Tabulka 'likes' byla úspěšně vytvořena.<br>";
} else {
    echo "Chyba při vytváření tabulky: " . $conn->error . "<br>";
}

echo "Nastavení lajků dokončeno.";
?>
