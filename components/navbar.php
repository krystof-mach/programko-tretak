<nav class="navbar">
    <div class="nav-left">
        <a href="index.php" class="logo-text">PHOTO AHH</a>
        <?php if ($is_admin): ?>
            <a href="admin.php" class="badge" style="margin-left: 10px; font-size: 9px; vertical-align: middle;">ADMIN</a>
        <?php endif; ?>
    </div>

    <form action="photographers.php" method="GET">
        <input type="text" name="search" placeholder="Hledat autory..." class="search-bar">
    </form>

    <div class="user-profile">
        <?php if ($is_logged_in): ?>
            <a href="profile.php">
                <img src="<?php echo $user_avatar; ?>" alt="Profil" class="profile-pic">
            </a>
        <?php else: ?>
            <a href="login.php" class="login-link">Přihlásit se</a>
        <?php endif; ?>
    </div>
</nav>