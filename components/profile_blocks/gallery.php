<?php
// Mosaic Gallery Block - Auto-shrinking Grid
$items = $data['items'] ?? [];
$base_height = intval($data['block_height'] ?? 600);
$title = $data['title'] ?? 'Portfolio';

// 1. Zjistit, které řádky jsou reálně obsazené
$used_rows = [1 => false, 2 => false, 3 => false];
foreach ($items as $item) {
    $r_start = intval($item['row']);
    $r_span = intval($item['row_span']);
    for ($i = 0; $i < $r_span; $i++) {
        if (($r_start + $i) <= 3) {
            $used_rows[$r_start + $i] = true;
        }
    }
}

// 2. Vytvořit mapování starých řádků na nové (komprese prázdných)
$row_map = [];
$new_row_idx = 1;
for ($r = 1; $r <= 3; $r++) {
    if ($used_rows[$r]) {
        $row_map[$r] = $new_row_idx;
        $new_row_idx++;
    } else {
        $row_map[$r] = -1; // Prázdný řádek
    }
}

$total_active_rows = $new_row_idx - 1;
if ($total_active_rows === 0) {
    $total_active_rows = 3; // Pokud je prázdno, ukážeme default 3
}

// 3. Přepočet výšky podle počtu využitých řádků
$actual_height = ($base_height / 3) * $total_active_rows;
?>
<section class="profile-section block-gallery" style="margin: 40px 0 0; width: 100%;">
    <h2 style="font-size: 24px; font-weight: 800; color: var(--text-h); margin-bottom: 25px;"><?php echo htmlspecialchars($title); ?></h2>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(<?php echo $total_active_rows; ?>, 1fr); gap: 0; height: <?php echo $actual_height; ?>px; width: 100%; border-radius: 24px; overflow: hidden; transform: translateZ(0);">
        <?php foreach ($items as $item): 
            $old_row = intval($item['row']);
            $mapped_row = $row_map[$old_row] > 0 ? $row_map[$old_row] : 1;
            $grid_area = "{$mapped_row} / {$item['col']} / span {$item['row_span']} / span {$item['col_span']}";
        ?>
            <div class="gallery-item" style="grid-area: <?php echo $grid_area; ?>; overflow: hidden; cursor: pointer; position: relative;" onclick="openImageViewer('<?php echo htmlspecialchars($item['url']); ?>')">
                <img src="<?php echo htmlspecialchars($item['url']); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($items)): ?>
            <div style="grid-area: 1 / 1 / span 3 / span 4; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; background: var(--bg2); border: 2px dashed var(--border); opacity: 0.5;">
                <i data-lucide="layout-grid" style="width: 48px; height: 48px; margin-bottom: 15px;"></i>
                <p>Mřížka portfolia (4x3) je prázdná.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
