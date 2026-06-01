<?php
require_once 'components/connector.php';

// Detekce přetečení limitu post_max_size (prázdný $_POST u POST requestu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $max_size = ini_get('post_max_size');
    die("❌ Chyba: Nahrávaná data jsou příliš velká! Váš limit na serveru je $max_size. Zkuste menší fotky nebo zvyšte 'post_max_size' v php.ini.");
}

$block_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$uid = $_SESSION['user_id'] ?? 0;

if (!$uid) {
    header("Location: login.php"); exit();
}

$stmt = $conn->prepare("SELECT * FROM profile_blocks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $block_id, $uid);
$stmt->execute();
$block = $stmt->get_result()->fetch_assoc();

if (!$block || $block['type'] !== 'gallery') {
    die("Blok nebyl nalezen.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mosaic'])) {
    check_csrf();
    
    $new_title = $_POST['title'] ?? 'Portfolio';
    $new_height = intval($_POST['block_height'] ?? 600) . "px";
    
    $new_items = [];
    if (isset($_POST['item_row'])) {
        foreach ($_POST['item_row'] as $i => $row) {
            $url = $_POST['item_url'][$i];
            
            if (strpos($url, 'data:image/') === 0) {
                list($type, $data) = explode(';', $url);
                list(, $data)      = explode(',', $data);
                $data = base64_decode($data);
                
                $ext = 'jpg';
                if (strpos($type, 'png') !== false) $ext = 'png';
                if (strpos($type, 'webp') !== false) $ext = 'webp';
                
                $new_name = uniqid('mosaic_') . "." . $ext;
                $target_dir = __DIR__ . "/posts/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                
                if (file_put_contents($target_dir . $new_name, $data)) {
                    $url = "posts/" . $new_name;
                }
            }
            
            $new_items[] = [
                'url' => $url,
                'row' => intval($row),
                'col' => intval($_POST['item_col'][$i]),
                'row_span' => intval($_POST['item_row_span'][$i]),
                'col_span' => intval($_POST['item_col_span'][$i])
            ];
        }
    }

    $new_content = json_encode([
        'title' => $new_title,
        'block_height' => $new_height,
        'items' => $new_items
    ]);
    
    $update = $conn->prepare("UPDATE profile_blocks SET content = ? WHERE id = ?");
    $update->bind_param("si", $new_content, $block_id);
    if ($update->execute()) {
        header("Location: profile_editor.php?id=$block_id&saved=1");
        exit();
    } else {
        die("Chyba při ukládání: " . $conn->error);
    }
}

$data = json_decode($block['content'], true);
$items = $data['items'] ?? [];
$block_height = intval($data['block_height'] ?? 600);
$title = $data['title'] ?? 'Portfolio';

$custom_title = "Mosaic Editor Portfolia - Photo Ahh";
include 'components/header.php';
?>

<main class="editor-container" style="background: #0a0a0a; height: calc(100vh - 70px); display: flex; overflow: hidden; color: #eee; width: 100vw; max-width: 100vw; margin: 0; padding: 0;">
    
    <!-- Left Sidebar: Controls -->
    <aside style="width: 400px; background: #16171d; border-right: 1px solid #2e303a; padding: 25px; overflow-y: auto; z-index: 10;">
        <h2 style="margin-top: 0; font-size: 22px; font-weight: 800; color: var(--accent);">Mosaic Builder</h2>
        <p style="opacity: 0.5; font-size: 13px; margin-bottom: 25px;">Mřížka 4x3 (12 polí). Každá fotka může zabírat více polí.</p>
        
        <form method="POST" enctype="multipart/form-data" id="mosaicForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="color: #888;">TITULEK SEKCE</label>
                <input type="text" name="title" id="in_title" value="<?php echo htmlspecialchars($title); ?>" oninput="updatePreview()">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="color: #888;">CELKOVÁ VÝŠKA BLOKU: <span id="val_height" style="color: var(--accent);"><?php echo $block_height; ?>px</span></label>
                <input type="range" name="block_height" id="in_height" min="300" max="1200" step="50" value="<?php echo $block_height; ?>" oninput="updatePreview()">
            </div>
            
            <hr style="border: none; border-top: 1px solid #2e303a; margin: 25px 0;">
            
            <label style="color: #888; margin-bottom: 15px; display: block;">FOTOGRAFIE V MŘÍŽCE</label>
            <div id="item_list" style="display: grid; gap: 15px;">
                <?php foreach($items as $idx => $item): ?>
                    <div class="mosaic-item-ctrl" style="background: #1f2028; padding: 15px; border-radius: 12px; border: 1px solid #2e303a;">
                        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
                            <img src="<?php echo htmlspecialchars($item['url']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                            <input type="hidden" name="item_url[]" value="<?php echo htmlspecialchars($item['url']); ?>">
                            <div style="flex: 1; font-size: 11px; opacity: 0.5; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo basename($item['url']); ?>
                            </div>
                            <button type="button" onclick="this.parentElement.parentElement.remove(); updatePreview();" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i data-lucide="trash-2" style="width: 18px;"></i></button>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="font-size: 9px; opacity: 0.5;">POZICE (Řádek / Sloupec)</label>
                                <div style="display: flex; gap: 5px;">
                                    <select name="item_row[]" onchange="updatePreview()"><?php for($r=1;$r<=3;$r++) echo "<option value='$r'".($item['row']==$r?' selected':'').">$r</option>"; ?></select>
                                    <select name="item_col[]" onchange="updatePreview()"><?php for($c=1;$c<=4;$c++) echo "<option value='$c'".($item['col']==$c?' selected':'').">$c</option>"; ?></select>
                                </div>
                            </div>
                            <div>
                                <label style="font-size: 9px; opacity: 0.5;">ROZSAH (V / Š)</label>
                                <div style="display: flex; gap: 5px;">
                                    <select name="item_row_span[]" onchange="updatePreview()"><?php for($rs=1;$rs<=3;$rs++) echo "<option value='$rs'".($item['row_span']==$rs?' selected':'').">$rs</option>"; ?></select>
                                    <select name="item_col_span[]" onchange="updatePreview()"><?php for($cs=1;$cs<=4;$cs++) echo "<option value='$cs'".($item['col_span']==$cs?' selected':'').">$cs</option>"; ?></select>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin: 25px 0;">
                <input type="file" name="new_images[]" multiple accept="image/*" id="file_in" style="display: none;" onchange="handleNewUploads(this)">
                <label for="file_in" class="btn" style="width: 100%; box-sizing: border-box; display: flex; justify-content: center; align-items: center; font-weight: 700; background: #374151; color: #ffffff; border: none; cursor: pointer;">+ Nahrát další fotky</label>
            </div>
            
            <div style="position: sticky; bottom: 0; background: #16171d; padding-top: 20px; padding-bottom: 20px; display: flex; flex-direction: column; gap: 10px;">
                <button type="submit" name="save_mosaic" class="btn btn-primary" style="width: 100%; box-sizing: border-box; font-weight: 800;">ULOŽIT MOZAIKU</button>
                <a href="settings.php" class="btn" style="width: 100%; box-sizing: border-box; display: flex; justify-content: center; align-items: center; text-decoration: none; font-weight: 700; background: #374151; color: #ffffff; border: none;">Zrušit</a>
            </div>
        </form>
    </aside>
    
    <!-- Right Section: Real-time Live Preview -->
    <section style="flex: 1; padding: 0; overflow-y: auto; display: flex; flex-direction: column; background: #0a0a0a; position: relative;">
        <div style="width: 100%; padding: 60px; box-sizing: border-box;">
            <h2 id="prev_title" style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 25px; margin-top: 0;">Portfolio</h2>
            
            <!-- The Actual Mosaic Grid -->
            <div id="mosaic_grid_wrapper" style="position: relative; width: 100%; transition: height 0.2s ease-out; background: #111; border-radius: 24px; overflow: hidden; transform: translateZ(0);">
                <!-- Ghost Grid (The Background structure) -->
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: grid; grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(3, 1fr); gap: 0; padding: 0; pointer-events: none;">
                    <?php for($i=0; $i<12; $i++): ?>
                        <div style="border-right: 1px dashed rgba(255,255,255,0.05); border-bottom: 1px dashed rgba(255,255,255,0.05); background: rgba(255,255,255,0.01);"></div>
                    <?php endfor; ?>
                </div>

                <!-- Active Images Grid -->
                <div id="mosaic_grid" style="position: relative; z-index: 2; display: grid; grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(3, 1fr); gap: 0; width: 100%; height: 100%;">
                    <!-- Dynamically filled -->
                </div>
            </div>
            
            <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; opacity: 0.3; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                <span>Vlevo: 0px</span>
                <span>Šířka bloku: 100% (Celý prostor)</span>
                <span>Vpravo: 0px</span>
            </div>
        </div>
    </section>
</main>

<script>
function updatePreview() {
    const title = document.getElementById('in_title').value;
    const height = document.getElementById('in_height').value;
    
    document.getElementById('val_height').innerText = height + 'px';
    document.getElementById('prev_title').innerText = title;
    
    // Ensure the wrapper sets the height so the absolute ghost grid fits
    const wrapper = document.getElementById('mosaic_grid_wrapper');
    wrapper.style.height = height + 'px';
    
    const grid = document.getElementById('mosaic_grid');
    grid.innerHTML = '';
    
    // Get all items from the sidebar controls
    const ctrlItems = document.querySelectorAll('.mosaic-item-ctrl');
    ctrlItems.forEach(ctrl => {
        const url = ctrl.querySelector('input[name="item_url[]"]').value;
        const row = ctrl.querySelector('select[name="item_row[]"]').value;
        const col = ctrl.querySelector('select[name="item_col[]"]').value;
        const r_span = ctrl.querySelector('select[name="item_row_span[]"]').value;
        const c_span = ctrl.querySelector('select[name="item_col_span[]"]').value;
        
        const div = document.createElement('div');
        div.style.gridArea = `${row} / ${col} / span ${r_span} / span ${c_span}`;
        div.style.overflow = 'hidden';
        div.style.position = 'relative';
        
        const img = document.createElement('img');
        img.src = url;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.display = 'block';
        
        div.appendChild(img);
        grid.appendChild(div);
    });
    
    if (ctrlItems.length === 0) {
        grid.innerHTML = '<div style="grid-area: 1/1/span 3/span 4; display:flex; align-items:center; justify-content:center; color:#333; font-style:italic;">Mřížka je prázdná</div>';
    }
}

function handleNewUploads(input) {
    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    // Client-side compression
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    const max_size = 1600; // Rozumné maximum pro profil
                    
                    if (width > height) {
                        if (width > max_size) {
                            height *= max_size / width;
                            width = max_size;
                        }
                    } else {
                        if (height > max_size) {
                            width *= max_size / height;
                            height = max_size;
                        }
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    const compressedData = canvas.toDataURL('image/jpeg', 0.8); // 80% kvalita
                    
                    const list = document.getElementById('item_list');
                    const div = document.createElement('div');
                    div.className = 'mosaic-item-ctrl';
                    div.style.cssText = 'background: #1f2028; padding: 15px; border-radius: 12px; border: 1px solid #2e303a;';
                    
                    div.innerHTML = `
                        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
                            <img src="${compressedData}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                            <input type="hidden" name="item_url[]" value="${compressedData}">
                            <div style="flex: 1; font-size: 11px; opacity: 0.5;">Optimalizovaná fotka</div>
                            <button type="button" onclick="this.parentElement.parentElement.remove(); updatePreview();" style="background: none; border: none; color: #ef4444; cursor: pointer;">&times;</button>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="font-size: 9px; opacity: 0.5;">POZICE</label>
                                <div style="display: flex; gap: 5px;">
                                    <select name="item_row[]" onchange="updatePreview()"><option value="1">1</option><option value="2">2</option><option value="3">3</option></select>
                                    <select name="item_col[]" onchange="updatePreview()"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select>
                                </div>
                            </div>
                            <div>
                                <label style="font-size: 9px; opacity: 0.5;">ROZSAH</label>
                                <div style="display: flex; gap: 5px;">
                                    <select name="item_row_span[]" onchange="updatePreview()"><option value="1">1</option><option value="2">2</option><option value="3">3</option></select>
                                    <select name="item_col_span[]" onchange="updatePreview()"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select>
                                </div>
                            </div>
                        </div>
                    `;
                    list.appendChild(div);
                    updatePreview();
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                };
                img.src = e.target.result;
            }
            reader.readAsDataURL(file);
        });
    }
}

document.addEventListener('DOMContentLoaded', updatePreview);
</script>

<?php include 'components/footer.php'; ?>
