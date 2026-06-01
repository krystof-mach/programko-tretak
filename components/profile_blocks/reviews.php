<?php
// Reviews Block
$stmt = $conn->prepare("SELECT r.*, u.username, u.avatar 
                        FROM reviews r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.photographer_id = ? AND r.approved = 1 
                        ORDER BY r.created_at DESC");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$rev_res = $stmt->get_result();

$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count 
                        FROM reviews 
                        WHERE photographer_id = ? AND approved = 1");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$avg_data = $stmt->get_result()->fetch_assoc();
?>
<section class="profile-section block-reviews" style="margin-top: 60px; border-top: 1px solid var(--border); padding-top: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="font-size: 24px; font-weight: 800; color: var(--text-h);">Recenze od klientů</h2>
        <?php if ($avg_data['count'] > 0): ?>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="display: flex; color: #f1c40f;">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i data-lucide="star" style="width: 18px; height: 18px; fill: <?php echo $i <= round($avg_data['avg_rating']) ? '#f1c40f' : 'none'; ?>; color: <?php echo $i <= round($avg_data['avg_rating']) ? '#f1c40f' : '#ddd'; ?>;"></i>
                    <?php endfor; ?>
                </div>
                <strong style="font-size: 18px; color: var(--text-h);"><?php echo number_format($avg_data['avg_rating'], 1); ?></strong>
                <span style="opacity: 0.5; font-size: 14px;">(<?php echo $avg_data['count']; ?>)</span>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php while($r = $rev_res->fetch_assoc()): ?>
            <div class="card" style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="<?php echo htmlspecialchars($r['avatar']); ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                        <strong style="font-size: 14px;"><?php echo htmlspecialchars($r['username']); ?></strong>
                    </div>
                    <div style="display: flex; color: #f1c40f;">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i data-lucide="star" style="width: 12px; height: 12px; fill: <?php echo $i <= $r['rating'] ? '#f1c40f' : 'none'; ?>; color: <?php echo $i <= $r['rating'] ? '#f1c40f' : '#ddd'; ?>;"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <p style="font-size: 13px; color: var(--text); line-height: 1.5; margin: 0;"><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
                <div style="font-size: 11px; opacity: 0.4; margin-top: 10px;"><?php echo date('d. m. Y', strtotime($r['created_at'])); ?></div>
            </div>
        <?php endwhile; ?>
        
        <?php if ($rev_res->num_rows == 0): ?>
            <p style="grid-column: 1 / -1; text-align: center; opacity: 0.5; font-style: italic; padding: 20px;">Zatím žádné recenze k zobrazení.</p>
        <?php endif; ?>
    </div>
</section>
