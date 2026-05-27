<?php
require_once 'components/connector.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$post_id = intval($_GET['id']);
$my_user_id = $_SESSION['user_id'] ?? 0;

// Načtení detailu příspěvku
$sql = "SELECT p.*, u.username as author_name, u.avatar as author_avatar, u.id as author_id,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = $my_user_id) as is_liked
        FROM `posts` p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = $post_id";

$result = $conn->query($sql);
$post = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;

if (!$post) {
    echo "<div style='padding: 20px; color: var(--text);'>Příspěvek nebyl nalezen.</div>";
    exit();
}

$images = json_decode($post['urls'] ?? '[]');
if (!$images && isset($post['file_path'])) $images = [$post['file_path']];
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
        <p style="font-size: 15px; line-height: 1.6; color: var(--text);"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
        <p style="font-size: 11px; color: var(--text); opacity: 0.4; margin-top: 25px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;"><?php echo date('j. n. Y H:i', strtotime($post['created_at'])); ?></p>
    </div>
    
    <!-- Footer -->
    <div style="padding: 20px; border-top: 1px solid var(--border);">
        <div style="display: flex; align-items: center; gap: 8px; cursor: pointer;" onclick="toggleLikeModal(<?php echo $post_id; ?>)">
            <i id="like-icon-modal" data-lucide="heart" 
               style="width: 28px; height: 28px; transition: all 0.2s; <?php echo $post['is_liked'] ? 'color: #ff4444; fill: #ff4444;' : 'color: var(--text-h); fill: none;'; ?>">
            </i>
            <span id="like-count-modal" style="font-weight: 800; font-size: 16px; color: var(--text-h);"><?php echo $post['likes_count']; ?></span>
        </div>
    </div>
</div>
