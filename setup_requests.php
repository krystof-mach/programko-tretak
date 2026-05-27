<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "programko_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
$conn->query($sql);
$conn->select_db($dbname);

// Tabulka žádostí o roli fotografa
$sql = "CREATE TABLE IF NOT EXISTS `role_requests` (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT(6) UNSIGNED,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

$conn->close();
echo "Database updated with role_requests. <a href='register.php'>Go to Register</a>";
?>
