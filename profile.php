<?php
$custom_title = "Profil - Photo Ahh";
$extra_css = "profile.css";
include 'components/header.php';
require_once 'components/profile_engine.php';
require_once 'Parsedown.php';


$view_user_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['user_id'] ?? null);

if (!$view_user_id) {
    header("Location: login.php");
    exit();
}

$my_id = $_SESSION['user_id'] ?? null;
$my_role = $_SESSION['role'] ?? '';


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
$view_user_role = $userData['role'];


$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE followed_id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$followers_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$following_count = $stmt->get_result()->fetch_assoc()['count'];


$is_following = false;
if ($my_id && $my_id !== $view_user_id) {
    $check = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
    $check->bind_param("ii", $my_id, $view_user_id);
    $check->execute();
    $is_following = $check->get_result()->num_rows > 0;
}


$stmt = $conn->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$posts_count = $stmt->get_result()->fetch_assoc()['count'];


$stmt = $conn->prepare("SELECT * FROM profile_blocks WHERE user_id = ? AND is_visible = 1 ORDER BY position ASC");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$blocks_res = $stmt->get_result();

$tab = $_GET['tab'] ?? '';
if (empty($tab)) {
    if ($blocks_res->num_rows > 0) {
        $tab = 'main';
    } else {
        $tab = 'gallery';
    }
}
?>

<main class="profile-container">
    <div class="content-inner">
        <div class="profile-header" style="margin-bottom: 50px;">
            <div class="profile-main-info">
                <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="profile-large-avatar">
                <div class="profile-text-info">
                    <div class="profile-top-row">
                        <h1><?php echo htmlspecialchars($username); ?></h1>
                        <?php if ($my_id === $view_user_id): ?>
                            <div style="display: flex; gap: 10px;">
                                <a href="settings.php" class="edit-profile-btn">Upravit profil</a>
                                <?php if (in_array($my_role, ['author', 'admin'])): ?>
                                    <a href="offers_manage.php" class="edit-profile-btn" style="background: var(--accent) !important; color: #000 !important; font-weight: 800; border-color: var(--accent) !important;">Ceník služeb</a>
                                    <button class="edit-profile-btn" onclick="document.getElementById('uploadModal').style.display='flex'" style="background: var(--accent) !important; border-color: var(--accent) !important; color: #000 !important; font-weight: 800;">+ Nový post</button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php if ($view_user_role === 'author' && (!$my_id || $my_role === 'user')): ?>
                                <button class="edit-profile-btn" style="background: var(--accent) !important; border-color: var(--accent) !important; color: #000 !important; margin-right: 10px;" onclick="document.getElementById('bookingModal').style.display='flex'">Rezervovat</button>
                            <?php endif; ?>
                            <?php if ($my_id): ?>
                                <form action="toggle_follow.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="followed_id" value="<?php echo $view_user_id; ?>">
                                    <button type="submit" class="edit-profile-btn" style="<?php echo $is_following ? '' : 'background: #0095f6; color: white; border-color: #0095f6;'; ?>">
                                        <?php echo $is_following ? 'Sledováno' : 'Sledovat'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-handle"><?php echo htmlspecialchars($user_handle); ?></div>
                    <div class="profile-stats">
                        <span><strong><?php echo $posts_count; ?></strong> příspěvků</span>
                        <span><strong><?php echo $followers_count; ?></strong> sledujících</span>
                        <span><strong><?php echo $following_count; ?></strong> sledování</span>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="profile-tabs" style="display: flex; gap: 20px; border-bottom: 1px solid var(--border); margin-bottom: 40px;">
            <a href="profile.php?id=<?php echo $view_user_id; ?>&tab=main" style="padding: 15px 0; font-weight: 800; color: <?php echo $tab === 'main' ? 'var(--text-h)' : 'var(--text)'; ?>; border-bottom: 2px solid <?php echo $tab === 'main' ? 'var(--accent)' : 'transparent'; ?>;">Hlavní stránka</a>
            <a href="profile.php?id=<?php echo $view_user_id; ?>&tab=gallery" style="padding: 15px 0; font-weight: 800; color: <?php echo $tab === 'gallery' ? 'var(--text-h)' : 'var(--text)'; ?>; border-bottom: 2px solid <?php echo $tab === 'gallery' ? 'var(--accent)' : 'transparent'; ?>;">Galerie</a>
        </div>

        <div class="profile-dynamic-content">
            <?php if ($tab === 'main'): ?>
                <?php if ($blocks_res->num_rows > 0): ?>
                    <?php while ($block = $blocks_res->fetch_assoc()): ?>
                        <?php renderProfileBlock($block, $conn, ($my_id === $view_user_id)); ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <?php 
                    // Default modules if no blocks defined (Only Bio as fallback)
                    include 'components/profile_blocks/bio.php'; 
                    ?>
                    <?php if ($my_id === $view_user_id && in_array($my_role, ['author', 'admin'])): ?>
                        <div style="text-align: center; padding: 40px; background: var(--bg2); border: 2px dashed var(--border); border-radius: 20px; margin-top: 30px;">
                            <p style="opacity: 0.6; margin-bottom: 20px;">Váš profil je zatím prázdný. Můžete si ho poskládat z modulů (Portfolio, Ceník, Recenze) v nastavení.</p>
                            <a href="settings.php" class="btn btn-primary" style="font-weight: 800;">Nastavit rozložení profilu</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php elseif ($tab === 'gallery'): ?>
                <div class="profile-grid">
                    <?php
                    $stmt_posts = $conn->prepare("SELECT p.*, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count FROM `posts` p WHERE user_id = ? ORDER BY id DESC");
                    $stmt_posts->bind_param("i", $view_user_id);
                    $stmt_posts->execute();
                    $posts_res = $stmt_posts->get_result();
                    
                    while ($post = $posts_res->fetch_assoc()): 
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
                    <?php endwhile; ?>
                    
                    <?php if ($posts_res->num_rows === 0): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: var(--text); opacity: 0.5;">
                            Zatím žádné příspěvky.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modals for Actions (Keeping existing modals) -->
<div id="uploadModal" class="modal-overlay" onclick="if(event.target === this) this.style.display='none'">
    <div class="modal-container" style="max-width: 450px; height: auto; padding: 35px; flex-direction: column;">
        <span onclick="this.parentElement.parentElement.style.display='none'" style="position: absolute; top: 15px; right: 20px; cursor: pointer; font-size: 28px; color: var(--text); opacity: 0.5;">&times;</span>
        <h3 style="color: var(--text-h); margin-top: 0; margin-bottom: 25px; font-weight: 800;">Nahrát novou fotku</h3>
        <form action="upload_post_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
        <form action="create_booking_action.php" method="POST" id="bookingForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="photographer_id" value="<?php echo $view_user_id; ?>">
            <input type="hidden" name="offer_id" id="booking_offer_id" value="">
            
            <?php if (!$is_logged_in): ?>
            <!-- Guest Step 1 -->
            <div id="booking_step_1">
                <p style="font-size: 13px; opacity: 0.7; margin-bottom: 20px;">Jelikož nejste přihlášeni, prosím zadejte nejprve své kontaktní údaje.</p>
                <div style="margin-bottom: 18px;">
                    <label>CELÉ JMÉNO</label>
                    <input type="text" name="guest_name" id="guest_name" placeholder="Jan Novák">
                </div>
                <div style="margin-bottom: 18px;">
                    <label>E-MAIL</label>
                    <input type="email" name="guest_email" id="guest_email" placeholder="jan@example.com">
                </div>
                <div style="margin-bottom: 25px;">
                    <label>TELEFONNÍ ČÍSLO</label>
                    <input type="text" name="guest_phone" id="guest_phone" placeholder="+420 123 456 789">
                </div>
                <button type="button" class="btn btn-primary" style="width: 100%; font-weight: 800;" onclick="nextBookingStep()">POKRAČOVAT</button>
            </div>
            <?php endif; ?>

            <!-- Booking Details Step -->
            <div id="booking_step_2" style="<?php echo !$is_logged_in ? 'display: none;' : ''; ?>">
                <?php if (!$is_logged_in): ?>
                    <button type="button" onclick="prevBookingStep()" style="background: none; border: none; color: var(--accent); cursor: pointer; font-size: 12px; font-weight: 800; padding: 0; margin-bottom: 15px;"><i data-lucide="arrow-left" style="width: 12px; height: 12px;"></i> ZPĚT NA ÚDAJE</button>
                <?php endif; ?>
                <div style="margin-bottom: 18px;">
                    <label>TYP FOCENÍ / SLUŽBA</label>
                    <input type="text" name="type" id="booking_type" placeholder="např. Portrét, Svatba, Produktové..." required>
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
                    <input type="text" name="budget" id="booking_budget" placeholder="Váš odhadovaný rozpočet">
                </div>
                <div style="margin-bottom: 25px;">
                    <label>DETAILY ŽÁDOSTI</label>
                    <textarea name="description" maxlength="2000" required style="height: 120px;" placeholder="Popište vaši představu..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 800;">ODESLAT ŽÁDOST</button>
            </div>
        </form>
    </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageViewerModal" class="modal-overlay" onclick="closeImageViewer()" style="display:none; cursor: zoom-out; z-index: 10000;">
    <div style="width: 90%; height: 90%; display: flex; align-items: center; justify-content: center; position: relative;">
        <img id="viewerImage" src="" style="max-width: 100%; max-height: 100%; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); object-fit: contain;">
        <button onclick="closeImageViewer()" style="position: absolute; top: 20px; right: 20px; background: rgba(0,0,0,0.5); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="x"></i>
        </button>
    </div>
</div>

<script>
function openImageViewer(src) {
    document.getElementById('viewerImage').src = src;
    document.getElementById('imageViewerModal').style.display = 'flex';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeImageViewer() {
    document.getElementById('imageViewerModal').style.display = 'none';
}

function nextBookingStep() {
    const name = document.getElementById('guest_name').value.trim();
    const email = document.getElementById('guest_email').value.trim();
    if (!name || !email) {
        alert('Prosím vyplňte jméno a e-mail.');
        return;
    }
    document.getElementById('booking_step_1').style.display = 'none';
    document.getElementById('booking_step_2').style.display = 'block';
}

function prevBookingStep() {
    document.getElementById('booking_step_2').style.display = 'none';
    document.getElementById('booking_step_1').style.display = 'block';
}

function openBookingModal(offerId = '', offerTitle = '', offerPrice = '') {
    document.getElementById('booking_offer_id').value = offerId;
    document.getElementById('booking_type').value = offerTitle;
    document.getElementById('booking_budget').value = offerPrice;
    document.getElementById('bookingModal').style.display = 'flex';
}
</script>

<?php include 'components/footer.php'; ?>
