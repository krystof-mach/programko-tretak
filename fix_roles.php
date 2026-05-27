<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "programko_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$conn->select_db($dbname);

// Explicitně vynutíme roli admina pro uživatele 'admin'
$conn->query("UPDATE users SET role = 'admin' WHERE username = 'admin'");
$conn->query("UPDATE users SET role = 'author' WHERE username = 'fotograf'");
$conn->query("UPDATE users SET role = 'user' WHERE username = 'klient'");

echo "Role byly v databázi explicitně opraveny.<br>";

// Kontrola současného stavu admina v DB
$res = $conn->query("SELECT username, role FROM users WHERE username = 'admin'");
$admin = $res->fetch_assoc();
echo "Uživatel: " . $admin['username'] . " | Role v DB: " . $admin['role'] . "<br>";

$conn->close();
echo "<br>Zkuste se nyní <a href='logout.php'>ODHLÁSIT</a> a znovu přihlásit.";
?>
