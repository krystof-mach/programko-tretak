<?php
$custom_title = "Profil - Photo Ahh";
$extra_css = "profile.css";
include 'components/header.php';

// Pokud je v URL id, zobrazíme daný profil, jinak vlastní
$view_user_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['user_id'] ?? null);

if (!$view_user_id) {
    header("Location: login.php");
    exit();
}

$my_id = $_SESSION['user_id'] ?? null;
$my_role = $_SESSION['role'] ?? '';

// Načtení dat uživatele
$stmt = $conn->prepare("SELECT id, username, avatar, bio, role FROM users WHERE id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    echo "<div style='text-align:center; margin-top:50px; color: var(--text);'>Uživatel nenalezen.</div>";
    include 'components/footer.php';
    exit();
}

$username = $userData['username'];
$user_handle = "@" . strtolower(str_replace(' ', '_', $username));
$user_avatar = $userData['avatar'];
$bio = $userData['bio'] ? $userData['bio'] : "Vítejte na mém profilu!";
$view_user_role = $userData['role'];

// Načtení všech příspěvků z 'posts' s počtem lajků
$sql = "SELECT p.*, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count 
        FROM `posts` p WHERE user_id = $view_user_id ORDER BY id DESC";
$result = $conn->query($sql);
$user_posts = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Zjištění stavu sledování
$is_following = false;
if ($my_id && $my_id !== $view_user_id) {
    $check = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
    $check->bind_param("ii", $my_id, $view_user_id);
    $check->execute();
    $is_following = $check->get_result()->num_rows > 0;
}

// Počty sledujících a sledovaných
$followers_res = $conn->query("SELECT COUNT(*) as count FROM follows WHERE followed_id = $view_user_id");
$followers_count = $followers_res->fetch_assoc()['count'];

$following_res = $conn->query("SELECT COUNT(*) as count FROM follows WHERE follower_id = $view_user_id");
$following_count = $following_res->fetch_assoc()['count'];
?>

<main class="profile-container">
    <div class="profile-header">
        <div class="profile-main-info">
            <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="profile-large-avatar">
            <div class="profile-text-info">
                <div class="profile-top-row">
                    <h1><?php echo htmlspecialchars($username); ?></h1>
                    <?php if ($my_id === $view_user_id): ?>
                        <a href="settings.php" class="edit-profile-btn">Upravit profil</a>
                        <?php if ($role === 'author' || $role === 'admin'): ?>
                            <button class="edit-profile-btn" onclick="document.getElementById('uploadModal').style.display='flex'" style="background: var(--accent) !important; border-color: var(--accent) !important; color: #000 !important;">+ Nový post</button>
                        <?php endif; ?>
                    <?php elseif ($my_id): ?>
                        <?php if ($my_role === 'user' && $view_user_role === 'author'): ?>
                            <button class="edit-profile-btn" style="background: var(--accent) !important; border-color: var(--accent) !important; color: #000 !important; margin-right: 10px;" onclick="document.getElementById('bookingModal').style.display='flex'">Rezervovat</button>
                        <?php endif; ?>
                        <form action="toggle_follow.php" method="POST" style="display: inline;">
                            <input type="hidden" name="followed_id" value="<?php echo $view_user_id; ?>">
                            <button type="submit" class="edit-profile-btn" style="<?php echo $is_following ? '' : 'background: #0095f6; color: white; border-color: #0095f6;'; ?>">
                                <?php echo $is_following ? 'Sledováno' : 'Sledovat'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="user-handle"><?php echo htmlspecialchars($user_handle); ?></div>
                <div class="profile-stats">
                    <span><strong><?php echo count($user_posts); ?></strong> příspěvků</span>
                    <span><strong><?php echo $followers_count; ?></strong> sledujících</span>
                    <span><strong><?php echo $following_count; ?></strong> sledování</span>
                </div>
                <div class="profile-bio"><?php echo nl2br(htmlspecialchars($bio)); ?></div>
            </div>
        </div>
    </div>

    <div class="profile-grid">
        <?php foreach ($user_posts as $post): 
            $display_url = $post['file_path'];
            if (empty($display_url)) {
                $urls = json_decode($post['urls'] ?? '[]');
                $display_url = $urls[0] ?? '';
            }
        ?>
            <div class="grid-item" onclick="openPostModal(<?php echo $post['id']; ?>)">
                <img src="<?php echo htmlspecialchars($display_url); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                <div class="grid-overlay">
                    <div class="overlay-info">
                        <span><i data-lucide="heart" style="fill: white;"></i> <?php echo $post['likes_count']; ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($user_posts)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: var(--text); opacity: 0.5;">
                Zatím žádné příspěvky.
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modals for Actions -->
<div id="uploadModal" class="modal-overlay" onclick="if(event.target === this) this.style.display='none'">
    <div class="modal-container" style="max-width: 450px; height: auto; padding: 35px; flex-direction: column;">
        <span onclick="this.parentElement.parentElement.style.display='none'" style="position: absolute; top: 15px; right: 20px; cursor: pointer; font-size: 28px; color: var(--text); opacity: 0.5;">&times;</span>
        <h3 style="color: var(--text-h); margin-top: 0; margin-bottom: 25px; font-weight: 800;">Nahrát novou fotku</h3>
        <form action="upload_post_action.php" method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 15px;">
                <label>NÁZEV</label>
                <input type="text" name="title" required placeholder="Jak se jmenuje tento příspěvek?">
            </div>
            <div style="margin-bottom: 15px;">
                <label>POPIS</label>
                <textarea name="description" placeholder="Napište něco o fotkách..." style="height: 100px;"></textarea>
            </div>
            <div style="margin-bottom: 25px;">
                <label>FOTOGRAFIE (1-4)</label>
                <input type="file" name="post_files[]" accept="image/*" multiple required style="border: none; padding: 0; background: transparent;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 800;">ZVEŘEJNIT</button>
        </form>
    </div>
</div>

<div id="bookingModal" class="modal-overlay" onclick="if(event.target === this) this.style.display='none'">
    <div class="modal-container" style="max-width: 500px; height: auto; max-height: 90vh; overflow-y: auto; padding: 35px; flex-direction: column;">
        <span onclick="this.parentElement.parentElement.style.display='none'" style="position: absolute; top: 15px; right: 20px; cursor: pointer; font-size: 28px; color: var(--text); opacity: 0.5;">&times;</span>
        <h3 style="color: var(--text-h); margin-top: 0; margin-bottom: 25px; font-weight: 800;">Rezervovat focení</h3>
        <form action="create_booking_action.php" method="POST">
            <input type="hidden" name="photographer_id" value="<?php echo $view_user_id; ?>">
            <div style="margin-bottom: 18px;">
                <label>TYP FOCENÍ</label>
                <input type="text" name="type" placeholder="např. Portrét, Svatba, Produktové..." required>
            </div>
            <div style="margin-bottom: 18px;">
                <label>POŽADOVANÉ DATUM</label>
                <input type="date" name="date" required>
            </div>
            <div style="margin-bottom: 18px;">
                <label>MÍSTO</label>
                <input type="text" name="location" placeholder="Kde se bude fotit?">
            </div>
            <div style="margin-bottom: 18px;">
                <label>ROZPOČET (Kč)</label>
                <input type="text" name="budget" placeholder="Váš odhadovaný rozpočet">
            </div>
            <div style="margin-bottom: 25px;">
                <label>DETAILY ŽÁDOSTI</label>
                <textarea name="description" maxlength="2000" required style="height: 120px;" placeholder="Popište vaši představu..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 800;">ODESLAT ŽÁDOST</button>
        </form>
    </div>
</div>

<?php include 'components/footer.php'; ?>
