<div class="side-nav">
    <a href="photographers.php" class="nav-btn-link">
        <button class="nav-btn">
            <i data-lucide="camera" class="nav-icon"></i>
            <span class="nav-text">Discover</span>
        </button>
    </a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if (in_array($_SESSION['role'], ['author', 'admin'])): ?>
            <a href="dashboard.php" class="nav-btn-link">
                <button class="nav-btn">
                    <i data-lucide="layout-dashboard" class="nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </button>
            </a>
        <?php endif; ?>
        <a href="my_bookings.php" class="nav-btn-link">
            <button class="nav-btn">
                <i data-lucide="calendar" class="nav-icon"></i>
                <span class="nav-text">Rezervace</span>
            </button>
        </a>
        <a href="cloud.php" class="nav-btn-link">
            <button class="nav-btn">
                <i data-lucide="cloud" class="nav-icon"></i>
                <span class="nav-text">Cloud</span>
            </button>
        </a>
    <?php endif; ?>
    
    <a href="settings.php" class="nav-btn-link">
        <button class="nav-btn">
            <i data-lucide="settings" class="nav-icon"></i>
            <span class="nav-text">Nastavení</span>
        </button>
    </a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php?csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="nav-btn-link logout-nav" style="margin-top: auto;">
            <button class="nav-btn logout-btn-style">
                <i data-lucide="log-out" class="nav-icon"></i>
                <span class="nav-text">Odhlásit</span>
            </button>
        </a>
    <?php else: ?>
        <a href="login.php" class="nav-btn-link login-nav" style="margin-top: auto;">
            <button class="nav-btn">
                <i data-lucide="log-in" class="nav-icon"></i>
                <span class="nav-text">Přihlásit</span>
            </button>
        </a>
    <?php endif; ?>
</div>
