<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "programko_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->select_db($dbname);

// Tabulka pro soubory v cloudu
$sql = "CREATE TABLE IF NOT EXISTS `cloud_files` (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT(6) UNSIGNED,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_type VARCHAR(100),
  file_size INT(10),
  thumbnail_url VARCHAR(255),
  upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Vytvoření hlavní složky pro cloud
if (!file_exists('cloud')) {
    mkdir('cloud', 0777, true);
}

$conn->close();
echo "Cloud system database ready. <a href='cloud.php'>Go to Cloud</a>";
?>
