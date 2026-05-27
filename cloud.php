<?php
$custom_title = "Můj Cloud - Photo Ahh";
$extra_css = "cloud.css";
include 'components/header.php';

if (!$is_logged_in) { header("Location: login.php"); exit(); }

$uid = $_SESSION['user_id'];

// Funkce pro výpočet reálné velikosti složky na disku
function getDirSize($dir) {
    $size = 0;
    if (!is_dir($dir)) return 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

$user_cloud_dir = "cloud/$uid/";
if (!file_exists($user_cloud_dir)) { mkdir($user_cloud_dir, 0777, true); }

// Automatický úklid DB
$all_files_in_db = $conn->query("SELECT id, filename FROM cloud_files WHERE user_id = $uid");
while($fdb = $all_files_in_db->fetch_assoc()) {
    if (!file_exists($user_cloud_dir . $fdb['filename'])) {
        $conn->query("DELETE FROM cloud_files WHERE id = " . $fdb['id']);
    }
}

$user_info = $conn->query("SELECT storage_limit FROM users WHERE id = $uid")->fetch_assoc();
$storage_limit = $user_info['storage_limit'] ? $user_info['storage_limit'] : 52428800;
$used_space = getDirSize($user_cloud_dir);

$message = "";
$error = "";

if (isset($_FILES['cloud_files'])) {
    $files = $_FILES['cloud_files'];
    $count = count($files['name']);
    
    if ($count > 50) {
        $error = "Můžete nahrát maximálně 50 souborů najednou.";
    } else {
        $uploaded_count = 0;
        $errors = [];
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'docx', 'txt', 'zip'];
            $max_file_size = 50 * 1024 * 1024;
            $file_name = $files['name'][$i];
            $tmp_name = $files['tmp_name'][$i];
            $file_size = $files['size'][$i];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed_extensions)) {
                $errors[] = "Soubor $file_name má nepovolený typ!";
            } elseif ($file_size > $max_file_size) {
                $errors[] = "Soubor $file_name je příliš velký.";
            } elseif (($used_space + $file_size) > $storage_limit) {
                $errors[] = "Nedostatek místa pro soubor $file_name.";
            } else {
                $unique_name = bin2hex(random_bytes(8)) . "." . $ext;
                $target_path = $user_cloud_dir . $unique_name;
                
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $thumb_url = "https://cdn-icons-png.flaticon.com/512/2965/2965335.png";
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $thumb_url = $target_path;
                    }

                    $stmt = $conn->prepare("INSERT INTO cloud_files (user_id, filename, original_name, file_type, file_size, thumbnail_url) VALUES (?, ?, ?, ?, ?, ?)");
                    $file_type = $files['type'][$i];
                    $stmt->bind_param("isssis", $uid, $unique_name, $file_name, $file_type, $file_size, $thumb_url);
                    $stmt->execute();
                    $used_space += $file_size;
                    $uploaded_count++;
                }
            }
        }
        if ($uploaded_count > 0) header("Location: cloud.php?msg=ok&count=$uploaded_count");
        if (!empty($errors)) $error = implode("<br>", $errors);
    }
}

if (isset($_GET['delete'])) {
    $fid = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT filename FROM cloud_files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $fid, $uid);
    $stmt->execute();
    if ($f = $stmt->get_result()->fetch_assoc()) {
        if (file_exists($user_cloud_dir . $f['filename'])) unlink($user_cloud_dir . $f['filename']);
        $conn->query("DELETE FROM cloud_files WHERE id = $fid");
    }
    header("Location: cloud.php");
    exit();
}

$files_res = $conn->query("SELECT * FROM cloud_files WHERE user_id = $uid ORDER BY upload_date DESC");
$percent = min(100, round(($used_space / $storage_limit) * 100));
$limit_mb = round($storage_limit / (1024*1024), 0);
$used_mb = round($used_space / (1024*1024), 2);
?>

<main class="cloud-container">
    <div class="cloud-header">
        <div class="cloud-title-area">
            <h2>Můj Cloud</h2>
            <div class="quota-bar-container">
                <div class="quota-info">Využito: <?php echo $used_mb; ?> MB z <?php echo $limit_mb; ?> MB (<?php echo $percent; ?>%)</div>
                <div class="quota-progress-bg">
                    <div class="quota-progress-fill" style="width: <?php echo $percent; ?>%; background-color: <?php echo ($percent > 90) ? '#e74c3c' : 'var(--accent)'; ?>;"></div>
                </div>
            </div>
        </div>
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="file" name="cloud_files[]" id="file_input" multiple style="display:none;" onchange="this.form.submit()">
            <label for="file_input" class="upload-btn"><i data-lucide="plus"></i> Nahrát soubory</label>
        </form>
    </div>

    <div id="toastNotification" class="toast-popup" style="display:none;">
        <div class="toast-content">
            <i data-lucide="check-circle" style="color: #2ecc71; width: 20px; height: 20px;"></i>
            <span>Soubory byly úspěšně nahrány!</span>
            <button onclick="closeToast()" class="toast-close">&times;</button>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="card" style="background: #fff5f5; border-color: #fed7d7; color: #c53030; text-align: center; margin-bottom: 20px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="file-grid">
        <?php if ($files_res->num_rows > 0): ?>
            <?php while($f = $files_res->fetch_assoc()): ?>
                <div class="file-item">
                    <div class="file-preview">
                        <img src="<?php echo htmlspecialchars($f['thumbnail_url']); ?>" alt="">
                    </div>
                    <div class="file-info">
                        <span class="file-name" title="<?php echo htmlspecialchars($f['original_name']); ?>">
                            <?php echo (strlen($f['original_name']) > 15) ? substr(htmlspecialchars($f['original_name']), 0, 12)."..." : htmlspecialchars($f['original_name']); ?>
                        </span>
                        <div class="file-actions">
                            <a href="<?php echo htmlspecialchars($user_cloud_dir . $f['filename']); ?>" download class="action-btn" title="Stáhnout"><i data-lucide="download"></i></a>
                            <a href="cloud.php?delete=<?php echo $f['id']; ?>" class="action-btn" title="Smazat" onclick="return confirm('Smazat tento soubor?')" style="color: #ef4444;"><i data-lucide="trash-2"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card empty-cloud" style="text-align: center; padding: 80px; grid-column: 1 / -1; background: none; border: 2px dashed var(--border); box-shadow: none;">
                <i data-lucide="folder-open" style="width: 48px; height: 48px; opacity: 0.2; margin-bottom: 15px;"></i>
                <p style="opacity: 0.5;">Váš cloud je zatím prázdný.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function closeToast() {
    const toast = document.getElementById('toastNotification');
    if (!toast) return;
    toast.style.animation = 'slideOut 0.3s ease-in forwards';
    setTimeout(() => {
        toast.style.display = 'none';
        // Vyčištění URL parametrů bez reloadu
        const url = new URL(window.location);
        url.searchParams.delete('msg');
        url.searchParams.delete('count');
        window.history.replaceState({}, '', url);
    }, 300);
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg') && urlParams.get('msg') === 'ok') {
        const toast = document.getElementById('toastNotification');
        if (toast) {
            toast.style.display = 'block';
            
            // Automatické skrytí po 1.5s
            setTimeout(() => {
                if (toast.style.display !== 'none') {
                    closeToast();
                }
            }, 1500);
        }
    }
});
</script>

<?php include 'components/footer.php'; ?>
