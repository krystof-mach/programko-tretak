<?php
$custom_title = "Můj Cloud - Photo Ahh";
$extra_css = "cloud.css";
include 'components/header.php';

if (!$is_logged_in) { header("Location: login.php"); exit(); }

$uid = $_SESSION['user_id'];
$abs_cloud_root = __DIR__ . "/cloud/";
$abs_user_dir = $abs_cloud_root . $uid . "/";
$rel_user_dir = "cloud/$uid/";

if (!file_exists($abs_user_dir)) { mkdir($abs_user_dir, 0777, true); }


$stmt = $conn->prepare("SELECT id, filename FROM cloud_files WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$all_files_in_db = $stmt->get_result();
while($fdb = $all_files_in_db->fetch_assoc()) {
    if (!file_exists($abs_user_dir . $fdb['filename'])) {
        $del_stmt = $conn->prepare("DELETE FROM cloud_files WHERE id = ?");
        $del_stmt->bind_param("i", $fdb['id']);
        $del_stmt->execute();
    }
}


$files_on_disk = glob($abs_user_dir . "*");
$db_filenames = [];
$res_filenames = $conn->query("SELECT filename FROM cloud_files WHERE user_id = $uid");
while($rf = $res_filenames->fetch_assoc()) { $db_filenames[] = $rf['filename']; }

foreach ($files_on_disk as $file_path) {
    if (is_dir($file_path)) continue;
    $fname = basename($file_path);
    if (!in_array($fname, $db_filenames)) {
        @unlink($file_path);
    }
}

$storage_info = getUserStorageInfo($uid, $conn);
$storage_limit = $storage_info['limit_bytes'];
$used_space = $storage_info['used_bytes'];
$percent = $storage_info['percent'];
$limit_mb = $storage_info['limit_mb'];
$used_mb = $storage_info['used_mb'];


$message = "";
$error = "";

if (isset($_FILES['cloud_files'])) {
    check_csrf();
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
                $errors[] = "Soubor " . htmlspecialchars($file_name) . " má nepovolený typ!";
            } elseif ($file_size > $max_file_size) {
                $errors[] = "Soubor " . htmlspecialchars($file_name) . " je příliš velký.";
            } elseif (($used_space + $file_size) > $storage_limit) {
                $errors[] = "Nedostatek místa pro soubor " . htmlspecialchars($file_name) . ".";
            } else {
                $unique_name = bin2hex(random_bytes(8)) . "." . $ext;
                $target_path = $abs_user_dir . $unique_name;
                $rel_target_path = $rel_user_dir . $unique_name;
                
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $thumb_url = "https://cdn-icons-png.flaticon.com/512/2965/2965335.png";
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $thumb_url = $rel_target_path;
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
    check_csrf();
    $fid = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT filename FROM cloud_files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $fid, $uid);
    $stmt->execute();
    if ($f = $stmt->get_result()->fetch_assoc()) {
        if (file_exists($abs_user_dir . $f['filename'])) unlink($abs_user_dir . $f['filename']);
        $del_stmt = $conn->prepare("DELETE FROM cloud_files WHERE id = ?");
        $del_stmt->bind_param("i", $fid);
        $del_stmt->execute();
    }
    header("Location: cloud.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM cloud_files WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$files_res = $stmt->get_result();
?>

<main class="cloud-container">
    <div class="content-inner">
        <div class="cloud-header">
            <div class="cloud-title-area">
                <h2>Můj Cloud</h2>
                <div class="quota-bar-container">
                    <div class="quota-info">Využito: <?php echo htmlspecialchars($used_mb); ?> MB z <?php echo htmlspecialchars($limit_mb); ?> MB (<?php echo htmlspecialchars($percent); ?>%)</div>
                    <div class="quota-progress-bg">
                        <div class="quota-progress-fill" style="width: <?php echo htmlspecialchars($percent); ?>%; background-color: <?php echo ($percent > 90) ? '#e74c3c' : 'var(--accent)'; ?>;"></div>
                    </div>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                        <div class="file-preview" style="cursor: pointer;" onclick="<?php echo in_array($ext_check = strtolower(pathinfo($f['filename'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? "openImageViewer('".htmlspecialchars($rel_user_dir . $f['filename'])."')" : ""; ?>">
                            <img src="<?php echo htmlspecialchars($f['thumbnail_url']); ?>" alt="">
                        </div>
                        <div class="file-info">
                            <span class="file-name" title="<?php echo htmlspecialchars($f['original_name']); ?>">
                                <?php echo (strlen($f['original_name']) > 15) ? substr(htmlspecialchars($f['original_name']), 0, 12)."..." : htmlspecialchars($f['original_name']); ?>
                            </span>
                            <div class="file-actions">
                                <a href="<?php echo htmlspecialchars($rel_user_dir . $f['filename']); ?>" download class="action-btn" title="Stáhnout"><i data-lucide="download"></i></a>
                                <a href="cloud.php?delete=<?php echo htmlspecialchars($f['id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn" title="Smazat" onclick="return confirm('Smazat tento soubor?')" style="color: #ef4444;"><i data-lucide="trash-2"></i></a>
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

<!-- Image Viewer Modal -->
<div id="imageViewerModal" class="modal-overlay" onclick="closeImageViewer()" style="display:none; cursor: zoom-out;">
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
</script>

<?php include 'components/footer.php'; ?>
