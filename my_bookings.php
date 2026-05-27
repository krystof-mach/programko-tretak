<?php
$custom_title = "Moje rezervace - Photo Ahh";
$extra_css = "my_bookings.css";
include 'components/header.php';

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

// Načtení custom rezervací
if ($role === 'author') {
    // Pro fotografa: vidí rezervace u něj vytvořené
    $sql = "SELECT b.*, u.username as client_name, u.avatar as client_avatar 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.photographer_id = $uid 
            ORDER BY b.booking_date DESC";
} else {
    // Pro uživatele: vidí svoje rezervace u fotografů
    $sql = "SELECT b.*, u.username as photog_name, u.avatar as photog_avatar 
            FROM bookings b 
            JOIN users u ON b.photographer_id = u.id 
            WHERE b.user_id = $uid 
            ORDER BY b.booking_date DESC";
}
$result = $conn->query($sql);

function getStatusLabel($status) {
    switch ($status) {
        case 'odeslána': return 'Odeslána';
        case 'potvrzena': return 'Potvrzena';
        case 'zamítnuta': return 'Zamítnuta';
        case 'zamítnuta_s_duvodem': return 'Zamítnuta s důvodem';
        case 'protinabídka': return 'Protinabídka';
        case 'probíhající': return 'Probíhající';
        case 'hotova': return 'Hotova';
        default: return $status;
    }
}
?>

<main class="bookings-container">
    <div class="bookings-content-wrapper">
    <div class="cloud-header" style="background: none; padding: 0; box-shadow: none; border: none; margin-bottom: 30px;">
        <div class="cloud-title-area">
            <h2 style="font-size: 28px; font-weight: 800; color: var(--text-h); margin: 0;">Moje Rezervace</h2>
            <?php if(isset($_GET['success'])): ?>
                <span class="badge" style="margin-top: 10px;">Změna uložena</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="bookings-list">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <div class="flex items-center gap-10">
                            <img src="<?php echo htmlspecialchars($role === 'author' ? $row['client_avatar'] : $row['photog_avatar']); ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);">
                            <div>
                                <strong style="font-size: 16px; color: var(--text-h);"><?php echo htmlspecialchars($role === 'author' ? $row['client_name'] : $row['photog_name']); ?></strong>
                                <div style="font-size: 12px; color: var(--text); opacity: 0.6;"><?php echo date('d. m. Y H:i', strtotime($row['booking_date'])); ?></div>
                            </div>
                        </div>
                        <div class="status-tag">
                            <?php echo getStatusLabel($row['status']); ?>
                        </div>
                    </div>

                    <div style="font-size: 14px; line-height: 1.8; color: var(--text);">
                        <p style="margin: 8px 0;"><strong style="color: var(--text-h);">Typ:</strong> <?php echo htmlspecialchars($row['type']); ?></p>
                        <p style="margin: 8px 0;"><strong style="color: var(--text-h);">Datum:</strong> <?php echo date('d. m. Y', strtotime($row['date'])); ?></p>
                        <?php if($row['location']): ?>
                            <p style="margin: 8px 0;"><strong style="color: var(--text-h);">Místo:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                        <?php endif; ?>
                        <?php if($row['budget']): ?>
                            <p style="margin: 8px 0;"><strong style="color: var(--text-h);">Rozpočet:</strong> <span style="color: var(--accent); font-weight: 800;"><?php echo htmlspecialchars($row['budget']); ?> Kč</span></p>
                        <?php endif; ?>
                        
                        <div style="margin-top: 15px; padding: 15px; background: var(--bg); border: 1px solid var(--border); border-radius: 12px; font-size: 13px;">
                            <strong style="color: var(--text-h); display: block; margin-bottom: 5px;">Popis žádosti:</strong>
                            <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                        </div>

                        <?php if($row['rejection_reason']): ?>
                            <div style="margin-top: 10px; padding: 15px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 12px; font-size: 13px; color: #c53030;">
                                <strong style="display: block; margin-bottom: 5px;">Důvod zamítnutí:</strong>
                                <?php echo nl2br(htmlspecialchars($row['rejection_reason'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if($row['counter_offer_note']): ?>
                            <div style="margin-top: 10px; padding: 15px; background: #fffaf0; border: 1px solid #feebc8; border-radius: 12px; font-size: 13px; color: #9c4221;">
                                <strong style="display: block; margin-bottom: 5px;">Poznámka k protinabídce:</strong>
                                <?php echo nl2br(htmlspecialchars($row['counter_offer_note'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php 
                        $photos = json_decode($row['completed_photos_json'] ?? '[]', true);
                        if (!empty($photos)): 
                        ?>
                            <div style="margin-top: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <strong style="color: var(--text-h); font-size: 14px;">Odevzdané fotografie:</strong>
                                    <?php if ($role === 'author' || $row['is_paid']): ?>
                                        <a href="download_booking_zip.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 5px 12px; font-size: 11px;">
                                            <i data-lucide="download-cloud" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 5px;"></i> Stáhnout vše (ZIP)
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px;">
                                    <?php foreach ($photos as $photo): 
                                        $path = is_array($photo) ? $photo['path'] : $photo;
                                        $name = is_array($photo) ? $photo['name'] : basename($photo);
                                    ?>
                                        <div style="position: relative; aspect-ratio: 1; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); cursor: pointer;" onclick="openImageViewer('<?php echo htmlspecialchars($path); ?>')">
                                            <img src="<?php echo htmlspecialchars($path); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <a href="<?php echo htmlspecialchars($path); ?>" download="<?php echo htmlspecialchars($name); ?>" 
                                               style="position: absolute; bottom: 5px; right: 5px; background: rgba(0,0,0,0.5); color: white; padding: 4px; border-radius: 4px; line-height: 0;" title="Stáhnout" onclick="event.stopPropagation();">
                                                <i data-lucide="download" style="width: 12px; height: 12px;"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="booking-actions">
                        <?php if ($role === 'author'): ?>
                            <?php if ($row['status'] === 'odeslána' || $row['status'] === 'protinabídka'): ?>
                                <button class="btn-status btn-confirm" onclick="updateStatus(<?php echo $row['id']; ?>, 'potvrzena')">Potvrdit</button>
                                <button class="btn-status btn-secondary" onclick="showReasonModal(<?php echo $row['id']; ?>, 'zamítnuta_s_duvodem')">Zamítnout s důvodem</button>
                                <button class="btn-status btn-secondary" onclick="showCounterModal(<?php echo $row['id']; ?>)">Protinabídka</button>
                                <button class="btn-status btn-reject" onclick="updateStatus(<?php echo $row['id']; ?>, 'zamítnuta')">Zamítnout</button>
                            <?php endif; ?>

                            <?php if ($row['status'] === 'potvrzena'): ?>
                                <button class="btn-status btn-progress" onclick="updateStatus(<?php echo $row['id']; ?>, 'probíhající')">Začít pracovat</button>
                            <?php endif; ?>

                            <?php if ($row['status'] === 'probíhající'): ?>
                                <button class="btn-status btn-finish" onclick="showUploadModal(<?php echo $row['id']; ?>)">Dokončit a nahrát fotky</button>
                            <?php endif; ?>

                            <?php if ($row['status'] === 'hotova' && !$row['is_paid']): ?>
                                <button class="btn-status btn-confirm" onclick="updateStatus(<?php echo $row['id']; ?>, 'mark_paid')">Označit jako zaplaceno</button>
                            <?php endif; ?>

                            <?php if ($row['status'] === 'hotova'): ?>
                                <button class="btn-status btn-secondary" onclick="showUploadModal(<?php echo $row['id']; ?>, true)">Přidat další fotky</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($row['status'] === 'protinabídka'): ?>
                                <button class="btn-status btn-confirm" onclick="updateStatusUser(<?php echo $row['id']; ?>, 'potvrzena')">Přijmout protinabídku</button>
                                <button class="btn-status btn-reject" onclick="updateStatusUser(<?php echo $row['id']; ?>, 'zamítnuta')">Odmítnout</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 60px;">
                <p style="color: var(--text); opacity: 0.5;">Zatím nemáte žádné rezervace.</p>
            </div>
        <?php endif; ?>
    </div>
    </div>
</main>

<!-- Action Modal -->
<div id="actionModal" class="modal-overlay" onclick="if(event.target === this) closeModal()">
    <div class="modal-container" style="max-width: 450px; height: auto; padding: 35px; flex-direction: column;">
        <h3 id="modalTitle" style="margin-top: 0; color: var(--text-h); font-weight: 800;">Akce</h3>
        <form action="update_booking_status.php" method="POST" enctype="multipart/form-data" id="actionForm">
            <input type="hidden" name="booking_id" id="modalBookingId">
            <input type="hidden" name="status" id="modalStatus">
            
            <div id="reasonField" style="display:none; margin-bottom: 20px;">
                <label>Důvod / Poznámka:</label>
                <textarea name="note" style="height: 120px;"></textarea>
            </div>

            <div id="uploadField" style="display:none; margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 700;">Nahrát fotografie (plná kvalita):</label>
                <input type="file" name="completed_photos[]" id="completed_photos" multiple accept="image/*" style="display: none;" onchange="updateFileLabel()">
                <label for="completed_photos" class="upload-btn" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; box-sizing: border-box; cursor: pointer;">
                    <i data-lucide="upload"></i>
                    <span id="file-label-text">Vybrat soubory</span>
                </label>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()" style="flex: 1; background: var(--bg2) !important;">Zrušit</button>
                <button type="submit" class="btn btn-primary" style="flex: 1; font-weight: 800;">Potvrdit</button>
            </div>
        </form>
    </div>
</div>

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

function updateStatus(id, status) {
    if (confirm('Opravdu chcete změnit stav na ' + status + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_booking_status.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'booking_id';
        idInput.value = id;
        form.appendChild(idInput);
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function updateStatusUser(id, status) {
    const msg = status === 'potvrzena' ? 'Opravdu chcete přijmout tuto nabídku?' : 'Opravdu chcete odmítnout tuto nabídku?';
    if (confirm(msg)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_booking_status.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'booking_id';
        idInput.value = id;
        form.appendChild(idInput);
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function showReasonModal(id, status) {
    document.getElementById('modalBookingId').value = id;
    document.getElementById('modalStatus').value = status;
    document.getElementById('modalTitle').innerText = 'Zamítnout s důvodem';
    document.getElementById('reasonField').style.display = 'block';
    document.getElementById('uploadField').style.display = 'none';
    document.getElementById('actionModal').style.display = 'flex';
}

function showCounterModal(id) {
    document.getElementById('modalBookingId').value = id;
    document.getElementById('modalStatus').value = 'protinabídka';
    document.getElementById('modalTitle').innerText = 'Poslat protinabídku';
    document.getElementById('reasonField').style.display = 'block';
    document.getElementById('uploadField').style.display = 'none';
    document.getElementById('actionModal').style.display = 'flex';
}

function showUploadModal(id, isExtra = false) {
    document.getElementById('modalBookingId').value = id;
    document.getElementById('modalStatus').value = isExtra ? 'add_more_photos' : 'hotova';
    document.getElementById('modalTitle').innerText = isExtra ? 'Přidat další fotografie' : 'Dokončit a nahrát fotografie';
    document.getElementById('reasonField').style.display = 'none';
    document.getElementById('uploadField').style.display = 'block';
    document.getElementById('actionModal').style.display = 'flex';
    
    // Reset file input and label
    document.getElementById('completed_photos').value = '';
    updateFileLabel();
}

function closeModal() {
    document.getElementById('actionModal').style.display = 'none';
}

function updateFileLabel() {
    const input = document.getElementById('completed_photos');
    const labelText = document.getElementById('file-label-text');
    if (input.files.length > 0) {
        labelText.innerText = input.files.length + ' souborů vybráno';
    } else {
        labelText.innerText = 'Vybrat soubory';
    }
}
</script>

<?php include 'components/footer.php'; ?>
