<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/components/connector.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $bio = $_POST['bio'];

        // Avatar upload logic
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['avatar']['tmp_name'];
            $file_name = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed_ext)) {
                $new_name = bin2hex(random_bytes(10)) . "." . $ext;
                $abs_target_dir = __DIR__ . "/uploads/avatars/";
                if (!is_dir($abs_target_dir)) mkdir($abs_target_dir, 0777, true);
                
                $abs_target_file = $abs_target_dir . $new_name;
                $db_target_path = "uploads/avatars/" . $new_name;
                
                if (move_uploaded_file($file_tmp, $abs_target_file)) {
                    $stmt_av = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt_av->bind_param("si", $db_target_path, $user_id);
                    if ($stmt_av->execute()) {
                        $_SESSION['avatar'] = $db_target_path;
                    }
                } else {
                    $error = "Chyba při nahrávání souboru na server.";
                }
            } else {
                $error = "Nepodporovaný formát obrázku.";
            }
        } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = "Soubor je příliš velký nebo nastala jiná chyba.";
        }

        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, bio = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $bio, $user_id);
            if ($stmt->execute()) {
                header("Location: settings.php?msg=success");
                exit();
            } else {
                $error = "Chyba při aktualizaci profilu.";
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $db_pass = $stmt->get_result()->fetch_assoc()['password'];

        if (!password_verify($old_pass, $db_pass)) {
            $error = "Staré heslo není správné.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "Nová hesla se neshodují.";
        } else {
            $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_new_pass, $user_id);
            if ($stmt->execute()) {
                session_destroy();
                header("Location: login.php?msg=pass_changed");
                exit();
            } else {
                $error = "Chyba při změně hesla.";
            }
        }
    }
}

// Získání aktuálních dat
$stmt = $conn->prepare("SELECT full_name, email, bio, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $message = "Profil byl úspěšně aktualizován.";
}

$custom_title = "Nastavení - Photo Ahh";
$extra_css = "settings.css";
include 'components/header.php';
?>

<main class="settings-container">
    <h2>Nastavení</h2>

    <?php if($message) echo "<div class='card' style='background: #f0fff4; border-color: #c6f6d5; color: #22543d; text-align: center; margin-bottom: 25px; font-weight: 700; padding: 15px; border-radius: 16px;'>$message</div>"; ?>
    <?php if($error) echo "<div class='card' style='background: #fff5f5; border-color: #fed7d7; color: #c53030; text-align: center; margin-bottom: 25px; font-weight: 700; padding: 15px; border-radius: 16px;'>$error</div>"; ?>

    <div class="settings-card">
        <h3>Upravit profil</h3>
        <form method="POST" enctype="multipart/form-data">
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px; background: var(--bg); padding: 20px; border-radius: 20px; border: 1px solid var(--border);">
                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent); flex-shrink: 0;">
                <div style="flex: 1;">
                    <label style="margin-bottom: 8px; font-size: 11px; color: var(--accent);">PROFILOVÁ FOTKA</label>
                    <input type="file" name="avatar" id="avatar_input" accept="image/*" style="display: none;" onchange="updateAvatarLabel()">
                    <label for="avatar_input" class="upload-btn" style="display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer; padding: 10px 15px; font-size: 13px; width: fit-content;">
                        <i data-lucide="image"></i>
                        <span id="avatar-label-text">Změnit fotografii</span>
                    </label>
                </div>
            </div>

            <label>CELÉ JMÉNO</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required placeholder="Vaše jméno">
            
            <label>EMAIL</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required placeholder="Váš email">
            
            <label>BIO (O MNĚ)</label>
            <textarea name="bio" rows="4" placeholder="Napište něco o sobě..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
            
            <button type="submit" name="update_profile" class="btn btn-primary">ULOŽIT ZMĚNY</button>
        </form>
    </div>

    <div class="settings-card">
        <h3>Změnit heslo</h3>
        <form method="POST">
            <label>STARÉ HESLO</label>
            <input type="password" name="old_password" required placeholder="Současné heslo">
            
            <label>NOVÉ HESLO</label>
            <input type="password" name="new_password" required placeholder="Nové heslo">
            
            <label>POTVRZENÍ NOVÉHO HESLA</label>
            <input type="password" name="confirm_password" required placeholder="Nové heslo znovu">
            
            <button type="submit" name="change_password" class="btn" style="background-color: #ef4444 !important; color: white !important;">ZMĚNIT HESLO A ODHLÁSIT</button>
        </form>
    </div>
</main>

<script>
function updateAvatarLabel() {
    const input = document.getElementById('avatar_input');
    const labelText = document.getElementById('avatar-label-text');
    if (input.files && input.files[0]) {
        labelText.innerText = input.files[0].name;
    }
}
</script>

<?php include 'components/footer.php'; ?>
