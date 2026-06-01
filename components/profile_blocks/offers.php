<?php
// Offers Block
$title = $data['title'] ?? 'Nabídka služeb';
$selected_offers = $data['selected_offers'] ?? null;

$offers = [];
if ($selected_offers !== null && is_array($selected_offers) && count($selected_offers) > 0) {
    // Fetch specific offers in specified order
    $placeholders = implode(',', array_fill(0, count($selected_offers), '?'));
    $types = str_repeat('i', count($selected_offers));
    // Provide author_id to prevent showing other people's offers if manipulated
    $types = 'i' . $types; 
    
    // Order by FIELD to maintain the user's custom order
    $order_clause = "ORDER BY FIELD(id, " . implode(',', $selected_offers) . ")";
    
    $query = "SELECT * FROM offers WHERE author_id = ? AND id IN ($placeholders) $order_clause";
    $stmt = $conn->prepare($query);
    
    $params = array_merge([$view_user_id], $selected_offers);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $offers[] = $row;
    }
} elseif ($selected_offers === null) {
    // Legacy / Default: Fetch all
    $stmt = $conn->prepare("SELECT * FROM offers WHERE author_id = ? ORDER BY price ASC");
    $stmt->bind_param("i", $view_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $offers[] = $row;
    }
}
?>
<section class="profile-section block-offers" style="margin-top: 60px; padding-top: 40px; border-top: 1px solid var(--border);">
    <h2 style="font-size: 24px; font-weight: 800; color: var(--text-h); margin-bottom: 30px;"><?php echo htmlspecialchars($title); ?></h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        <?php foreach ($offers as $o): ?>
            <div class="card" style="padding: 25px; display: flex; flex-direction: column;">
                <h3 style="margin-top: 0; font-size: 18px; font-weight: 800; color: var(--accent);"><?php echo htmlspecialchars($o['title']); ?></h3>
                <p style="font-size: 14px; opacity: 0.7; flex: 1; margin: 15px 0;"><?php echo nl2br(htmlspecialchars($o['description'])); ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <span style="font-size: 20px; font-weight: 800; color: var(--text-h);"><?php echo number_format($o['price'], 0, ',', ' '); ?> Kč</span>
                    <?php if (!$my_id || ($my_id !== $view_user_id && $my_role === 'user')): ?>
                        <button class="btn btn-primary" style="padding: 8px 16px; font-size: 12px;" onclick="document.getElementById('bookingModal').style.display='flex'">Rezervovat</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($offers)): ?>
            <p style="grid-column: 1 / -1; text-align: center; opacity: 0.5; font-style: italic;">Žádné služby k zobrazení.</p>
        <?php endif; ?>
    </div>
</section>
