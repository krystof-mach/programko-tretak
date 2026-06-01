<?php
session_start();
require_once 'components/connector.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0 && ($userData = $result->fetch_assoc()) && password_verify($pass, $userData['password'])) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['avatar'] = $userData['avatar'];
        $_SESSION['password'] = $userData['password'];

        header("Location: index.php");
        exit();
    } else {
        $error = "Nesprávné jméno nebo heslo!";
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přihlášení - Photo Ahh</title>
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h1 style="color: #ffcc00; font-weight: 900; letter-spacing: -1px; margin-bottom: 30px;">PHOTO AHH</h1>
        
        <?php if($error): ?>
            <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label>UŽIVATELSKÉ JMÉNO</label>
                <input type="text" name="username" required class="form-control">
            </div>
            <div class="form-group">
                <label>HESLO</label>
                <input type="password" name="password" required class="form-control">
            </div>
            <button type="submit" class="btn-login">PŘIHLÁSIT SE</button>
        </form>
        <p style="margin-top: 20px; font-size: 14px;">
            Nemáte účet? <a href="register.php" style="color: #ffcc00; text-decoration: none; font-weight: bold;">Registrovat se</a>
        </p>
    </div>
</body>
</html>
