<div class="side-nav">
    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'user' || $_SESSION['role'] === 'admin' || $_SESSION['role'] === 'author')): ?>
        <a href="photographers.php" class="nav-btn-link">
            <button class="nav-btn">
                <i data-lucide="camera" class="nav-icon"></i>
                <span class="nav-text">Discover</span>
            </button>
        </a>
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
        <a href="logout.php" class="nav-btn-link logout-nav" style="margin-top: auto;">
            <button class="nav-btn logout-btn-style">
                <i data-lucide="log-out" class="nav-icon"></i>
                <span class="nav-text">Odhlásit</span>
            </button>
        </a>
    <?php endif; ?>
</div>
