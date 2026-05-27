<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "programko_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->select_db($dbname);

// Změna hesla pro admina
$new_pass = "12345";
if ($conn->query("UPDATE users SET password = '$new_pass' WHERE username = 'admin'")) {
    echo "Heslo pro admina bylo úspěšně změněno na: $new_pass<br>";
} else {
    echo "Chyba při změně hesla: " . $conn->error . "<br>";
}

$conn->close();
?>
