<?php
require_once 'components/connector.php';

$result = $conn->query("SELECT id, password FROM users");

$count = 0;
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $pass = $row['password'];

    // Zkontrolujeme, zda heslo už není zahashované (bcrypt hashe obvykle začínají $2y$)
    if (strpos($pass, '$2y$') !== 0) {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $id);
        $stmt->execute();
        $count++;
    }
}

echo "Migrace hesel dokončena. Zahashováno $count účtů. <a href='login.php'>Zpět na přihlášení</a>";
?>
