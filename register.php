<?php
session_start();
require_once 'components/connector.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $birth_date = $_POST['birth_date'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $requested_role = $_POST['role_choice'];

    if (!in_array($requested_role, ['user', 'author'])) {
        $requested_role = 'user';
    }

    if ($password !== $confirm_password) {
        $error = "Hesla se neshodují!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $error = "Uživatelské jméno nebo email již existuje!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, phone, birth_date, password, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
            $stmt->bind_param("ssssss", $username, $full_name, $email, $phone, $birth_date, $hashed_password);
            
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;

                if ($requested_role === 'author') {
                    $stmt_req = $conn->prepare("INSERT INTO role_requests (user_id) VALUES (?)");
                    $stmt_req->bind_param("i", $new_id);
                    $stmt_req->execute();
                    $success = "Registrace úspěšná! Žádost o roli fotografa byla odeslána.";
                } else {
                    $success = "Registrace se zdařila!";
                }
                
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                $_SESSION['avatar'] = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                $_SESSION['password'] = $hashed_password;

                header("refresh:2;url=index.php");
            } else {
                $error = "Chyba při registraci.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Registrace - Photo Ahh</title>
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="register.css">
</head>
<body style="background-color: #f4f4f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 0;">
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 400px;">
        <h1 style="color: #ffcc00; font-weight: 900; letter-spacing: -1px; text-align: center; margin-bottom: 20px;">PHOTO AHH</h1>
        <h2 style="text-align: center; margin-top: 0;">Vytvořit účet</h2>
        
        <?php if($error) echo "<p style='color:red; font-weight:bold; text-align:center;'>$error</p>"; ?>
        <?php if($success) echo "<p style='color:green; font-weight:bold; text-align:center;'>$success</p>"; ?>
        
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">CELÉ JMÉNO</label>
                <input type="text" name="full_name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">UŽIVATELSKÉ JMÉNO</label>
                <input type="text" name="username" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">EMAIL</label>
                <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">TELEFONNÍ ČÍSLO</label>
                <input type="text" name="phone" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">DATUM NAROZENÍ</label>
                <input type="date" name="birth_date" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">CHCI SE REGISTROVAT JAKO:</label>
                <select name="role_choice" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: white;">
                    <option value="user">Běžný uživatel (Klient)</option>
                    <option value="author">Fotograf (Vyžaduje schválení)</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">HESLO</label>
                <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #ffcc00; margin-bottom: 5px;">POTVRZENÍ HESLA</label>
                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            
            <button type="submit" style="width: 100%; padding: 12px; background: #ffcc00; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">REGISTROVAT SE</button>
        </form>
        
        <p style="margin-top: 20px; font-size: 14px; text-align: center;">
            Již máte účet? <a href="login.php" style="color: #ffcc00; text-decoration: none; font-weight: bold;">Přihlaste se</a>
        </p>
    </div>
</body>
</html>
