<?php
$custom_title = "Nastavení profilu - Photo Ahh";
$extra_css = "settings.css";
include 'components/header.php';

if (!$is_logged_in) { header("Location: login.php"); exit(); }

$uid = $_SESSION['user_id'];
$message = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    check_csrf();
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $bio = $_POST['bio'];

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $full_name, $email, $phone, $bio, $uid);
    
    if ($stmt->execute()) {
        $message = "Profil byl úspěšně aktualizován.";
    } else {
        $error = "Chyba při ukládání dat.";
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_block'])) {
    check_csrf();
    $type = $_POST['block_type'];
    
    // Get last position
    $stmt = $conn->prepare("SELECT MAX(position) as max_p FROM profile_blocks WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $pos = $stmt->get_result()->fetch_assoc()['max_p'] + 1;

    $content = json_encode(['title' => 'Nová sekce', 'text' => 'Zde napište svůj text...']);
    if ($type === 'gallery') $content = json_encode(['limit' => 6]);
    if ($type === 'offers') $content = json_encode(['layout' => 'cards']);
    if ($type === 'reviews') $content = json_encode(['min_rating' => 4]);
    if ($type === 'contact') $content = json_encode(['show_email' => true]);

    $stmt = $conn->prepare("INSERT INTO profile_blocks (user_id, type, content, position) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $uid, $type, $content, $pos);
    $stmt->execute();
    header("Location: settings.php?msg=block_added"); exit();
}


if (isset($_GET['delete_block'])) {
    check_csrf();
    $bid = intval($_GET['delete_block']);
    $stmt = $conn->prepare("DELETE FROM profile_blocks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $bid, $uid);
    $stmt->execute();
    header("Location: settings.php?msg=block_deleted"); exit();
}


if (isset($_GET['move_up']) || isset($_GET['move_down'])) {
    check_csrf();
    $bid = intval($_GET['move_up'] ?? $_GET['move_down']);
    $dir = isset($_GET['move_up']) ? -1 : 1;
    
    $stmt = $conn->prepare("SELECT position FROM profile_blocks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $bid, $uid);
    $stmt->execute();
    $current_pos = $stmt->get_result()->fetch_assoc()['position'];
    $new_pos = $current_pos + $dir;
    
    if ($new_pos >= 0) {
        // Swap positions
        $conn->query("UPDATE profile_blocks SET position = $current_pos WHERE position = $new_pos AND user_id = $uid");
        $conn->query("UPDATE profile_blocks SET position = $new_pos WHERE id = $bid");
    }
    header("Location: settings.php"); exit();
}


$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();


$stmt = $conn->prepare("SELECT * FROM profile_blocks WHERE user_id = ? ORDER BY position ASC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$blocks = $stmt->get_result();
?>

<main class="settings-container">
    <div class="content-inner">
        <h1 style="font-size: 32px; font-weight: 800; color: var(--text-h); margin-bottom: 40px;">Nastavení</h1>

        <?php if($message) echo "<div class='badge' style='background: #d4edda; color: #155724; margin-bottom: 20px; padding: 15px;'>$message</div>"; ?>
        <?php if($error) echo "<div class='badge' style='background: #f8d7da; color: #721c24; margin-bottom: 20px; padding: 15px;'>$error</div>"; ?>

        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; align-items: start;">
            
            <!-- Profil Data -->
            <section class="card" style="padding: 35px;">
                <h3 style="margin-top: 0; margin-bottom: 25px; font-weight: 800;"><i data-lucide="user"></i> Osobní údaje</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div style="margin-bottom: 20px;">
                        <label>CELÉ JMÉNO</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label>E-MAIL</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label>TELEFON</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div style="margin-bottom: 30px;">
                        <label>BIO / POPIS</label>
                        <textarea name="bio" style="height: 120px;"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%; font-weight: 800;">ULOŽIT ZMĚNY</button>
                </form>
            </section>

            <!-- Profile Blocks (The New System) -->
            <?php if ($role !== 'user'): ?>
            <section class="card" style="padding: 35px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin: 0; font-weight: 800;"><i data-lucide="layout"></i> Uspořádání profilu</h3>
                    <button class="btn btn-primary" style="padding: 8px 15px; font-size: 12px;" onclick="document.getElementById('addBlockModal').style.display='flex'">+ PŘIDAT BLOK</button>
                </div>

                <div style="display: grid; gap: 12px;">
                    <?php while($b = $blocks->fetch_assoc()): ?>
                        <div style="background: var(--bg); border: 1px solid var(--border); padding: 15px 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="color: var(--accent);"><i data-lucide="grip-vertical" style="width: 18px; opacity: 0.3;"></i></div>
                                <span style="font-weight: 700; text-transform: uppercase; font-size: 13px;"><?php echo $b['type']; ?></span>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <?php if ($b['type'] === 'gallery'): ?>
                                    <a href="profile_editor.php?id=<?php echo $b['id']; ?>" class="action-btn-small" style="color: var(--accent); border-color: var(--accent); width: auto; padding: 0 10px; font-size: 10px; font-weight: 800;">EDITOR</a>
                                <?php elseif ($b['type'] === 'offers'): ?>
                                    <a href="offers_editor.php?id=<?php echo $b['id']; ?>" class="action-btn-small" style="color: var(--accent); border-color: var(--accent); width: auto; padding: 0 10px; font-size: 10px; font-weight: 800;">EDITOR</a>
                                <?php endif; ?>
                                <a href="settings.php?move_up=<?php echo $b['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-small"><i data-lucide="chevron-up"></i></a>
                                <a href="settings.php?move_down=<?php echo $b['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-small"><i data-lucide="chevron-down"></i></a>
                                <a href="settings.php?delete_block=<?php echo $b['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-small" style="color: #ef4444;" onclick="return confirm('Smazat tento blok?')"><i data-lucide="trash-2"></i></a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($blocks->num_rows === 0): ?>
                        <p style="text-align: center; opacity: 0.5; font-style: italic; padding: 20px;">Váš profil používá výchozí rozložení. Přidejte bloky pro vlastní uspořádání.</p>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Logout Section (Mainly for Mobile) -->
            <section class="card" style="padding: 35px; border-color: #feb2b2; background: #fff5f5; grid-column: 1 / -1;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <h3 style="margin: 0; color: #c53030; font-weight: 800;">Zabezpečení a účet</h3>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #c53030; opacity: 0.7;">Odhlášení z aktuální relace na tomto zařízení.</p>
                    </div>
                    <a href="logout.php?csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn" style="background: #ef4444; color: white; border: none; min-width: 150px;">
                        <i data-lucide="log-out"></i> Odhlásit se
                    </a>
                </div>
            </section>

        </div>
    </div>
</main>

<!-- Add Block Modal -->
<div id="addBlockModal" class="modal-overlay" onclick="if(event.target === this) this.style.display='none'">
    <div class="modal-container" style="max-width: 400px; padding: 30px; flex-direction: column;">
        <h3 style="margin-top: 0; font-weight: 800;">Přidat modul na profil</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div style="display: grid; gap: 10px; margin-top: 20px;">
                <button type="submit" name="add_block" value="1" onclick="this.form.block_type.value='bio'" class="btn btn-secondary" style="justify-content: flex-start; padding: 15px 20px;">
                    <i data-lucide="text-quote"></i> Bio / O mně
                </button>
                <button type="submit" name="add_block" value="1" onclick="this.form.block_type.value='gallery'" class="btn btn-secondary" style="justify-content: flex-start; padding: 15px 20px;">
                    <i data-lucide="image"></i> Portfolio (Galerie)
                </button>
                <button type="submit" name="add_block" value="1" onclick="this.form.block_type.value='offers'" class="btn btn-secondary" style="justify-content: flex-start; padding: 15px 20px;">
                    <i data-lucide="tag"></i> Ceník služeb
                </button>
                <button type="submit" name="add_block" value="1" onclick="this.form.block_type.value='reviews'" class="btn btn-secondary" style="justify-content: flex-start; padding: 15px 20px;">
                    <i data-lucide="star"></i> Recenze klientů
                </button>
                <button type="submit" name="add_block" value="1" onclick="this.form.block_type.value='contact'" class="btn btn-secondary" style="justify-content: flex-start; padding: 15px 20px;">
                    <i data-lucide="mail"></i> Kontaktní údaje
                </button>
            </div>
            <input type="hidden" name="block_type" id="block_type_val">
        </form>
    </div>
</div>

<style>
.action-btn-small {
    width: 32px;
    height: 32px;
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text);
    transition: all 0.2s;
}
.action-btn-small:hover {
    background: var(--accent);
    color: #000;
}
</style>

<?php include 'components/footer.php'; ?>
