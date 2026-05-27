<?php
require_once 'components/connector.php';

// Přidání sloupce storage_limit do tabulky users
$check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'storage_limit'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN storage_limit BIGINT DEFAULT 52428800"); // 50MB default
    echo "Sloupec storage_limit byl přidán.<br>";
    
    // Nastavení 100MB pro autory/fotografy
    $conn->query("UPDATE `users` SET storage_limit = 104857600 WHERE role = 'author'");
    echo "Limity pro autory byly nastaveny na 100MB.<br>";
} else {
    echo "Sloupec storage_limit již existuje.<br>";
}

// Ujistíme se, že cloud_files má sloupec file_size
$check_size = $conn->query("SHOW COLUMNS FROM `cloud_files` LIKE 'file_size'");
if ($check_size->num_rows == 0) {
    $conn->query("ALTER TABLE `cloud_files` ADD COLUMN file_size INT(11) DEFAULT 0");
    echo "Sloupec file_size byl přidán do cloud_files.<br>";
}

echo "Migrace dokončena. <a href='admin.php'>Zpět do Adminu</a>";
?>
