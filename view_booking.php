<?php
include 'components/connector.php';
$hash = isset($_GET['hash']) ? $_GET['hash'] : '';

if (empty($hash)) die("Chybějící odkaz.");

$stmt = $conn->prepare("SELECT b.*, u.username as photog_name, u.avatar as photog_avatar 
                        FROM bookings b 
                        JOIN users u ON b.photographer_id = u.id 
                        WHERE b.access_hash = ?");
$stmt->bind_param("s", $hash);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) die("Zakázka nenalezena.");

$photos = json_decode($booking['completed_photos_json'] ?? '[]', true);
$custom_title = "Sledování zakázky - Photo Ahh";

function getStatusLabelGuest($status) {
    switch ($status) {
        case 'odeslána': return ['Čeká na vyjádření fotografa', '#f39c12'];
        case 'potvrzena': return ['Potvrzena (Čeká na realizaci)', '#2ecc71'];
        case 'zamítnuta': return ['Zamítnuta fotografem', '#e74c3c'];
        case 'zamítnuta_s_duvodem': return ['Zamítnuta s důvodem', '#e74c3c'];
        case 'protinabídka': return ['Fotograf navrhl změnu', '#9b59b6'];
        case 'probíhající': return ['Fotograf pracuje na fotkách', '#3498db'];
        case 'hotova': return ['Hotovo', '#2ecc71'];
        default: return [$status, '#95a5a6'];
    }
}

list($status_text, $status_color) = getStatusLabelGuest($booking['status']);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title><?php echo $custom_title; ?></title>
    <link rel="stylesheet" href="global.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .guest-container { max-width: 1000px; margin: 60px auto; padding: 20px; }
        .guest-header { text-align: center; margin-bottom: 50px; }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .photo-item { position: relative; aspect-ratio: 1; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); cursor: pointer; transition: transform 0.2s; }
        .photo-item:hover { transform: scale(1.02); }
        .photo-item img { width: 100%; height: 100%; object-fit: cover; }
        .overlay-pay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 14px; opacity: 0; transition: opacity 0.2s; }
        .photo-item:hover .overlay-pay { opacity: 1; }
        .paid-badge { background: #2ecc71; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; }
        .unpaid-badge { background: #ef4444; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; }
        .status-badge-large { display: inline-block; padding: 8px 16px; border-radius: 8px; font-weight: 800; color: #fff; margin-top: 15px; font-size: 14px; }
    </style>
</head>
<body>

<div class="guest-container">
    <div class="guest-header">
        <img src="<?php echo htmlspecialchars($booking['photog_avatar']); ?>" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--accent); margin-bottom: 15px;">
        <h1 style="margin: 0; font-size: 32px; font-weight: 800;">Vaše zakázka od: <?php echo htmlspecialchars($booking['photog_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p style="opacity: 0.6; margin-top: 10px;">Typ: <?php echo htmlspecialchars($booking['type'], ENT_QUOTES, 'UTF-8'); ?> | Datum: <?php echo date('d. m. Y', strtotime($booking['date'])); ?></p>
        
        <div class="status-badge-large" style="background: <?php echo $status_color; ?>;">
            Stav: <?php echo $status_text; ?>
        </div>

        <?php if ($booking['status'] === 'hotova'): ?>
            <div style="margin-top: 20px;">
                <?php if ($booking['is_paid']): ?>
                    <span class="paid-badge"><i data-lucide="check-circle" style="width: 14px; height: 14px; vertical-align: middle;"></i> ZAPLACENO</span>
                    <a href="download_booking_zip.php?hash=<?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="margin-left: 15px; display: inline-flex; align-items: center; gap: 8px;">
                        <i data-lucide="download-cloud"></i> Stáhnout originály (ZIP)
                    </a>
                <?php else: ?>
                    <span class="unpaid-badge"><i data-lucide="alert-circle" style="width: 14px; height: 14px; vertical-align: middle;"></i> ČEKÁ NA PLATBU</span>
                    <p style="font-size: 12px; opacity: 0.5; margin-top: 10px;">Fotografie jsou opatřeny vodoznakem. Plná kvalita bude dostupná ke stažení po zaplacení.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="background: var(--bg2); padding: 30px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 40px;">
        <h3 style="margin-top: 0; color: var(--text-h); font-weight: 800;">Detaily žádosti</h3>
        <?php if($booking['location']): ?>
            <p><strong style="color: var(--text-h);">Místo:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
        <?php endif; ?>
        <?php if($booking['budget']): ?>
            <p><strong style="color: var(--text-h);">Rozpočet:</strong> <span style="color: var(--accent); font-weight: 800;"><?php echo htmlspecialchars($booking['budget']); ?> Kč</span></p>
        <?php endif; ?>
        <p style="margin-top: 15px; line-height: 1.6; opacity: 0.8;"><?php echo nl2br(htmlspecialchars($booking['description'])); ?></p>
        
        <?php if($booking['rejection_reason']): ?>
            <div style="margin-top: 20px; padding: 15px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 12px; font-size: 14px; color: #c53030;">
                <strong style="display: block; margin-bottom: 5px;">Zpráva od fotografa (Důvod zamítnutí):</strong>
                <?php echo nl2br(htmlspecialchars($booking['rejection_reason'])); ?>
            </div>
        <?php endif; ?>

        <?php if($booking['counter_offer_note']): ?>
            <div style="margin-top: 20px; padding: 15px; background: #fffaf0; border: 1px solid #feebc8; border-radius: 12px; font-size: 14px; color: #9c4221;">
                <strong style="display: block; margin-bottom: 5px;">Zpráva od fotografa (Protinabídka):</strong>
                <?php echo nl2br(htmlspecialchars($booking['counter_offer_note'])); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($booking['status'] === 'protinabídka' && empty($booking['user_id'])): ?>
            <div style="margin-top: 20px; text-align: center;">
                <p style="font-size: 14px; color: var(--text-h); font-weight: 800; margin-bottom: 15px;">Chcete tuto nabídku přijmout?</p>
                <form action="update_booking_status.php" method="POST" style="display: inline-block;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                    <input type="hidden" name="status" value="potvrzena">
                    <input type="hidden" name="is_guest" value="1">
                    <input type="hidden" name="hash" value="<?php echo htmlspecialchars($hash); ?>">
                    <button type="submit" class="btn" style="background: #2ecc71; color: white; border: none; padding: 10px 20px; font-weight: 800; border-radius: 8px; cursor: pointer; margin-right: 10px;">Přijmout nabídku</button>
                </form>
                <form action="update_booking_status.php" method="POST" style="display: inline-block;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                    <input type="hidden" name="status" value="zamítnuta">
                    <input type="hidden" name="is_guest" value="1">
                    <input type="hidden" name="hash" value="<?php echo htmlspecialchars($hash); ?>">
                    <button type="submit" class="btn" style="background: #e74c3c; color: white; border: none; padding: 10px 20px; font-weight: 800; border-radius: 8px; cursor: pointer;">Odmítnout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($booking['status'] === 'hotova'): ?>
        <h3 style="margin-top: 0; color: var(--text-h); font-weight: 800; margin-bottom: 20px; text-align: center;">Odevzdané fotografie</h3>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): 
                $orig_path = is_array($photo) ? $photo['path'] : $photo;
                $wm_path = (is_array($photo) && isset($photo['wm_path'])) ? $photo['wm_path'] : $orig_path;
                $display_path = ($booking['is_paid']) ? $orig_path : $wm_path;
            ?>
                <div class="photo-item" onclick="openImageViewer('<?php echo htmlspecialchars($display_path); ?>')">
                    <img src="<?php echo htmlspecialchars($display_path); ?>">
                    <div class="overlay-pay">Zvětšit</div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($photos)): ?>
                <p style="grid-column: 1 / -1; text-align: center; opacity: 0.5;">Zatím nebyly nahrány žádné fotografie.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Image Viewer -->
<div id="imageViewerModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 10000; align-items: center; justify-content: center; cursor: zoom-out;" onclick="this.style.display='none'">
    <img id="viewerImage" src="" style="max-width: 95vw; max-height: 95vh; object-fit: contain; border-radius: 8px; box-shadow: 0 0 50px rgba(0,0,0,1);">
</div>

<script>
    lucide.createIcons();
    function openImageViewer(src) {
        document.getElementById('viewerImage').src = src;
        document.getElementById('imageViewerModal').style.display = 'flex';
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                alert("Odkaz zkopírován do schránky.");
            }).catch(err => {
                console.error("Kopírování selhalo:", err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            var successful = document.execCommand('copy');
            if (successful) alert("Odkaz zkopírován do schránky.");
            else alert("Nepodařilo se zkopírovat odkaz.");
        } catch (err) {
            console.error('Fallback: Kopírování selhalo', err);
        }
        document.body.removeChild(textArea);
    }
</script>

</body>
</html>
