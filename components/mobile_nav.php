<div class="mobile-bottom-nav">
    <a href="index.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <i data-lucide="home"></i>
    </a>
    
    <a href="photographers.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'photographers.php') ? 'active' : ''; ?>">
        <i data-lucide="search"></i>
    </a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if (in_array($_SESSION['role'], ['author', 'admin'])): ?>
            <a href="dashboard.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i data-lucide="layout-dashboard"></i>
            </a>
        <?php endif; ?>
        
        <a href="my_bookings.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'my_bookings.php') ? 'active' : ''; ?>">
            <i data-lucide="calendar"></i>
        </a>
        
        <a href="cloud.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'cloud.php') ? 'active' : ''; ?>">
            <i data-lucide="cloud"></i>
        </a>
        
        <a href="profile.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
        </a>
    <?php else: ?>
        <a href="login.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>">
            <i data-lucide="log-in"></i>
        </a>
    <?php endif; ?>

    <a href="settings.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
        <i data-lucide="settings"></i>
    </a>
</div>
