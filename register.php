<?php
session_start();
require_once 'components/connector.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();
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
<body>
    <div class="register-container">
        <h1 class="auth-logo">PHOTO AHH</h1>
        <h2 class="auth-title">Vytvořit účet</h2>
        
        <?php if($error) echo "<p class='error-msg'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>"; ?>
        <?php if($success) echo "<p class='success-msg'>" . htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . "</p>"; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label>CELÉ JMÉNO</label>
                <input type="text" name="full_name" required class="form-control">
            </div>
            <div class="form-group">
                <label>UŽIVATELSKÉ JMÉNO</label>
                <input type="text" name="username" required class="form-control">
            </div>
            <div class="form-group">
                <label>EMAIL</label>
                <input type="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label>TELEFONNÍ ČÍSLO</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label>DATUM NAROZENÍ</label>
                <input type="date" name="birth_date" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>CHCI SE REGISTROVAT JAKO:</label>
                <select name="role_choice" required class="form-control">
                    <option value="user">Běžný uživatel (Klient)</option>
                    <option value="author">Fotograf (Vyžaduje schválení)</option>
                </select>
            </div>

            <div class="form-group">
                <label>HESLO</label>
                <input type="password" name="password" required class="form-control">
            </div>
            <div class="form-group">
                <label>POTVRZENÍ HESLA</label>
                <input type="password" name="confirm_password" required class="form-control">
            </div>
            
            <button type="submit" class="btn-login">REGISTROVAT SE</button>
        </form>
        
        <p class="auth-footer">
            Již máte účet? <a href="login.php">Přihlaste se</a>
        </p>
    </div>
</body>
</html>
