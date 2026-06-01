<?php
require_once 'components/connector.php';
require_once 'Parsedown.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$post_id = intval($_GET['id']);
$my_user_id = $_SESSION['user_id'] ?? 0;
$Parsedown = new Parsedown();

$stmt = $conn->prepare("SELECT p.*, u.username as author_name, u.avatar as author_avatar, u.id as author_id,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM `posts` p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?");
$stmt->bind_param("ii", $my_user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;

if (!$post) {
    echo "<div style='padding: 20px; color: var(--text);'>Příspěvek nebyl nalezen.</div>";
    exit();
}

$images = json_decode($post['urls'] ?? '[]');
if (!$images && isset($post['file_path'])) $images = [$post['file_path']];

$all_metadata = json_decode($post['metadata'] ?? '[]', true);

$stmt = $conn->prepare("SELECT c.*, u.username, u.avatar 
                             FROM comments c 
                             JOIN users u ON c.user_id = u.id 
                             WHERE c.post_id = ? 
                             ORDER BY c.created_at ASC");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments_res = $stmt->get_result();
?>

<!-- Media Section (Left) -->
<div style="flex: 1.5; background: #000; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; height: 100%;">
    <div id="slides-modal" style="display: flex; transition: transform 0.3s ease-out; width: 100%; height: 100%;">
        <?php foreach ($images as $img_url): ?>
            <img src="<?php echo htmlspecialchars($img_url); ?>" style="width: 100%; height: 100%; object-fit: contain; flex-shrink: 0;">
        <?php endforeach; ?>
    </div>
    
    <?php if (count($images) > 1): ?>
        <button onclick="moveSlideModal(-1)" style="position: absolute; left: 10px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="chevron-left"></i>
        </button>
        <button onclick="moveSlideModal(1)" style="position: absolute; right: 10px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="chevron-right"></i>
        </button>
    <?php endif; ?>

    <!-- EXIF Overlay -->
    <div id="exif-overlay" style="position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.6); color: white; padding: 10px 15px; border-radius: 8px; font-size: 11px; backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1); pointer-events: none;">
        <?php 
        $current_meta = $all_metadata[0] ?? null;
        if ($current_meta && !empty($current_meta['exif'])): 
            $e = $current_meta['exif'];
        ?>
            <div style="display: flex; gap: 15px;">
                <span><i data-lucide="camera" style="width: 12px; height: 12px; vertical-align: middle;"></i> <?php echo htmlspecialchars($e['make'] . ' ' . $e['model']); ?></span>
                <span><i data-lucide="aperture" style="width: 12px; height: 12px; vertical-align: middle;"></i> <?php echo htmlspecialchars($e['aperture']); ?></span>
                <span><i data-lucide="timer" style="width: 12px; height: 12px; vertical-align: middle;"></i> <?php echo htmlspecialchars($e['exposure']); ?>s</span>
                <span><i data-lucide="zap" style="width: 12px; height: 12px; vertical-align: middle;"></i> ISO <?php echo htmlspecialchars($e['iso']); ?></span>
            </div>
        <?php else: ?>
            <span style="opacity: 0.5;">EXIF data nedostupná</span>
        <?php endif; ?>
    </div>
</div>

<!-- Info Section (Right) -->
<div style="flex: 1; display: flex; flex-direction: column; background: var(--bg2); border-left: 1px solid var(--border); height: 100%; color: var(--text);">
    <!-- Header -->
    <div style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px;">
        <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);">
        <strong style="font-size: 15px; color: var(--text-h);"><?php echo htmlspecialchars($post['author_name']); ?></strong>
        <span onclick="closePostModal()" style="margin-left: auto; cursor: pointer; font-size: 24px; color: var(--text); opacity: 0.5;">&times;</span>
    </div>
    
    <!-- Body -->
    <div style="padding: 20px; flex: 1; overflow-y: auto;">
        <h2 style="margin: 0 0 12px 0; font-size: 22px; font-weight: 800; color: var(--text-h);"><?php echo htmlspecialchars($post['title']); ?></h2>
        <div class="markdown-content" style="font-size: 15px; line-height: 1.6; color: var(--text);">
            <?php echo $Parsedown->text($post['description']); ?>
        </div>
        <p style="font-size: 11px; color: var(--text); opacity: 0.4; margin-top: 25px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;"><?php echo htmlspecialchars(date('j. n. Y H:i', strtotime($post['created_at']))); ?></p>

        <!-- Comments Section -->
        <div style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 20px;">
            <h4 style="margin-bottom: 20px; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Komentáře (<?php echo htmlspecialchars($comments_res->num_rows); ?>)</h4>
            <div id="comments-list">
                <?php while($c = $comments_res->fetch_assoc()): ?>
                    <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                        <img src="<?php echo htmlspecialchars($c['avatar']); ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <strong style="font-size: 13px; color: var(--text-h);"><?php echo htmlspecialchars($c['username']); ?></strong>
                                <span style="font-size: 10px; opacity: 0.4;"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($c['created_at']))); ?></span>
                            </div>
                            <p style="font-size: 13px; margin: 0; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if($my_user_id): ?>
                <div style="margin-top: 25px; display: flex; gap: 12px;">
                    <textarea id="comment-input" placeholder="Napište komentář..." style="flex: 1; height: 60px; font-size: 13px;"></textarea>
                    <button onclick="submitComment(<?php echo htmlspecialchars($post_id); ?>)" class="btn btn-primary" style="height: 60px; padding: 0 20px;">
                        <i data-lucide="send"></i>
                    </button>
                </div>
            <?php else: ?>
                <p style="font-size: 12px; opacity: 0.5; font-style: italic; text-align: center;">Pro přidání komentáře se musíte přihlásit.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="padding: 20px; border-top: 1px solid var(--border);">
        <div style="display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="toggleLikeModal(<?php echo htmlspecialchars($post_id); ?>)">
            <i id="like-icon-modal" data-lucide="heart" 
               style="width: 28px; height: 28px; transition: all 0.2s; <?php echo $post['is_liked'] ? 'color: #ff4444; fill: #ff4444;' : 'color: var(--text-h); fill: none;'; ?>">
            </i>
            <span id="like-count-modal" style="font-weight: 800; font-size: 16px; color: var(--text-h);"><?php echo htmlspecialchars($post['likes_count']); ?></span>
        </div>
    </div>
</div>

<script>
// EXIF switcher logic
var allMetadata = <?php echo json_encode($all_metadata); ?>;
function updateExifOverlay(index) {
    var meta = allMetadata[index];
    var overlay = document.getElementById('exif-overlay');
    if (meta && meta.exif && Object.keys(meta.exif).length > 0) {
        var e = meta.exif;
        overlay.innerHTML = '<div style="display: flex; gap: 15px;">' +
            '<span><i data-lucide="camera" style="width: 12px; height: 12px; vertical-align: middle;"></i> ' + e.make + ' ' + e.model + '</span>' +
            '<span><i data-lucide="aperture" style="width: 12px; height: 12px; vertical-align: middle;"></i> ' + e.aperture + '</span>' +
            '<span><i data-lucide="timer" style="width: 12px; height: 12px; vertical-align: middle;"></i> ' + e.exposure + 's</span>' +
            '<span><i data-lucide="zap" style="width: 12px; height: 12px; vertical-align: middle;"></i> ISO ' + e.iso + '</span>' +
            '</div>';
    } else {
        overlay.innerHTML = '<span style="opacity: 0.5;">EXIF data nedostupná</span>';
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Override moveSlideModal to update EXIF
var originalMoveSlideModal = window.moveSlideModal;
window.moveSlideModal = function(n) {
    if (typeof currentSlideModal !== 'undefined') {
        var slides = document.querySelectorAll('#slides-modal img');
        currentSlideModal += n;
        if (currentSlideModal >= slides.length) currentSlideModal = 0;
        if (currentSlideModal < 0) currentSlideModal = slides.length - 1;
        document.getElementById('slides-modal').style.transform = "translateX(" + (-currentSlideModal * 100) + "%)";
        updateExifOverlay(currentSlideModal);
    }
};

function submitComment(postId) {
    var text = document.getElementById('comment-input').value;
    if (!text.trim()) return;
    
    fetch('submit_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'post_id=' + postId + '&comment=' + encodeURIComponent(text) + '&csrf_token=' + CSRF_TOKEN
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Jednoduché obnovení pro zobrazení nového komentáře
        }
    });
}
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
