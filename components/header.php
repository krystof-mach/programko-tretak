<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/connector.php';

$is_logged_in = isset($_SESSION['user_id']);

// VALIDACE RELACE (pokud se změní heslo v DB, odhlásíme uživatele)
if ($is_logged_in) {
    $uid = $_SESSION['user_id'];
    $session_pass = isset($_SESSION['password']) ? $_SESSION['password'] : '';
    
    $val_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $val_stmt->bind_param("i", $uid);
    $val_stmt->execute();
    $res = $val_stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        if ($row['password'] !== $session_pass) {
            session_destroy();
            header("Location: login.php?msg=session_invalid");
            exit();
        }
    } else {
        // Uživatel už neexistuje
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$user_avatar = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : "https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y";
$is_admin = ($role === 'admin');

// Možnost nastavit vlastní titulek stránky
$page_title = isset($custom_title) ? $custom_title : "photo ahh stránka";
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="global.css">
    <?php if (isset($extra_css)) echo '<link rel="stylesheet" href="'.$extra_css.'">'; ?>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>
    <div class="main-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
