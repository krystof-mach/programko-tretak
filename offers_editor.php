<?php
require_once 'components/connector.php';

$block_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$uid = $_SESSION['user_id'] ?? 0;

if (!$uid) {
    header("Location: login.php"); exit();
}

$stmt = $conn->prepare("SELECT * FROM profile_blocks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $block_id, $uid);
$stmt->execute();
$block = $stmt->get_result()->fetch_assoc();

if (!$block || $block['type'] !== 'offers') {
    die("Blok nebyl nalezen.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_offers'])) {
    check_csrf();
    
    $new_title = $_POST['title'] ?? 'Nabídka služeb';
    $new_selected = [];
    if (isset($_POST['offer_ids'])) {
        foreach ($_POST['offer_ids'] as $oid) {
            $new_selected[] = intval($oid);
        }
    }
    
    $new_content = json_encode([
        'title' => $new_title,
        'selected_offers' => $new_selected,
        'layout' => 'cards'
    ]);
    
    $update = $conn->prepare("UPDATE profile_blocks SET content = ? WHERE id = ?");
    $update->bind_param("si", $new_content, $block_id);
    if ($update->execute()) {
        header("Location: offers_editor.php?id=$block_id&saved=1");
        exit();
    } else {
        die("Chyba při ukládání: " . $conn->error);
    }
}

$data = json_decode($block['content'], true);
$selected_offers = $data['selected_offers'] ?? null;
$title = $data['title'] ?? 'Nabídka služeb';

$all_offers = [];
$stmt = $conn->prepare("SELECT * FROM offers WHERE author_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $all_offers[$row['id']] = $row;
}

$current_items = [];
if ($selected_offers !== null && is_array($selected_offers)) {
    foreach ($selected_offers as $oid) {
        if (isset($all_offers[$oid])) {
            $current_items[] = $all_offers[$oid];
        }
    }
} else {
    foreach ($all_offers as $o) {
        $current_items[] = $o;
    }
}

$custom_title = "Editor Ceníku - Photo Ahh";
include 'components/header.php';
?>

<main class="editor-container" style="background: #0a0a0a; height: calc(100vh - 70px); display: flex; overflow: hidden; color: #eee; width: 100vw; max-width: 100vw; margin: 0; padding: 0;">
    
    <!-- Left Sidebar: Controls -->
    <aside style="width: 400px; background: #16171d; border-right: 1px solid #2e303a; padding: 25px; display: flex; flex-direction: column; z-index: 10;">
        <h2 style="margin-top: 0; font-size: 22px; font-weight: 800; color: var(--accent);">Editor Ceníku</h2>
        <p style="opacity: 0.5; font-size: 13px; margin-bottom: 25px;">Vyberte a seřaďte služby, které chcete na profilu ukázat.</p>
        
        <form method="POST" id="offersForm" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="color: #888;">TITULEK SEKCE</label>
                <input type="text" name="title" id="in_title" value="<?php echo htmlspecialchars($title); ?>" oninput="updatePreview()">
            </div>
            
            <hr style="border: none; border-top: 1px solid #2e303a; margin: 10px 0 20px;">
            
            <label style="color: #888; margin-bottom: 10px; display: block;">ZOBRAZENÉ SLUŽBY (Tažením nebo šipkami změňte pořadí)</label>
            
            <div id="selected_list" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-bottom: 20px;">
                <?php foreach($current_items as $item): ?>
                    <div class="offer-ctrl-item" data-id="<?php echo $item['id']; ?>" data-title="<?php echo htmlspecialchars($item['title']); ?>" data-desc="<?php echo htmlspecialchars($item['description']); ?>" data-price="<?php echo $item['price']; ?>" style="background: #1f2028; padding: 15px; border-radius: 12px; border: 1px solid #2e303a; display: flex; align-items: center; justify-content: space-between;">
                        <input type="hidden" name="offer_ids[]" value="<?php echo $item['id']; ?>">
                        <div>
                            <div style="font-weight: 800; font-size: 14px;"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div style="font-size: 12px; color: var(--accent);"><?php echo number_format($item['price'], 0, ',', ' '); ?> Kč</div>
                        </div>
                        <div style="display: flex; gap: 5px;">
                            <button type="button" onclick="moveUp(this)" style="background: #2e303a; border: none; color: #fff; width: 28px; height: 28px; border-radius: 6px; cursor: pointer;"><i data-lucide="chevron-up" style="width: 16px;"></i></button>
                            <button type="button" onclick="moveDown(this)" style="background: #2e303a; border: none; color: #fff; width: 28px; height: 28px; border-radius: 6px; cursor: pointer;"><i data-lucide="chevron-down" style="width: 16px;"></i></button>
                            <button type="button" onclick="removeItem(this)" style="background: #ef4444; border: none; color: #fff; width: 28px; height: 28px; border-radius: 6px; cursor: pointer;"><i data-lucide="x" style="width: 16px;"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin: 15px 0;">
                <select id="add_offer_select" style="margin-bottom: 10px;">
                    <option value="">-- Přidat existující službu --</option>
                    <?php foreach($all_offers as $o): ?>
                        <option value="<?php echo $o['id']; ?>" data-title="<?php echo htmlspecialchars($o['title']); ?>" data-desc="<?php echo htmlspecialchars($o['description']); ?>" data-price="<?php echo $o['price']; ?>">
                            <?php echo htmlspecialchars($o['title']); ?> (<?php echo number_format($o['price'], 0, ',', ' '); ?> Kč)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-secondary" style="width: 100%; border: 1px dashed #444; background: transparent; color: #888;" onclick="addOffer()">+ Přidat do výběru</button>
            </div>
            
            <div style="background: #16171d; padding-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                <button type="submit" name="save_offers" class="btn btn-primary" style="width: 100%; box-sizing: border-box; font-weight: 800;">ULOŽIT CENÍK</button>
                <a href="settings.php" class="btn" style="width: 100%; box-sizing: border-box; display: flex; justify-content: center; align-items: center; text-decoration: none; font-weight: 700; background: #374151; color: #ffffff; border: none;">Zrušit</a>
            </div>
        </form>
    </aside>
    
    <!-- Right Section: Real-time Live Preview -->
    <section style="flex: 1; padding: 0; overflow-y: auto; display: flex; flex-direction: column; background: #0a0a0a; position: relative;">
        <div style="width: 100%; padding: 60px; box-sizing: border-box;">
            
            <div style="width: 100%; transition: all 0.2s ease-out; background: var(--bg); border-radius: 30px; border: 1px solid var(--border); overflow: hidden; padding: 50px;">
                <h2 id="prev_title" style="font-size: 24px; font-weight: 800; color: var(--text-h); margin-bottom: 30px; margin-top: 0;">Nabídka služeb</h2>
                
                <div id="prev_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    <!-- Dynamically filled -->
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center; opacity: 0.3; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                Vizuální reprezentace na vašem profilu
            </div>
        </div>
    </section>
</main>

<script>
function formatPrice(price) {
    return new Intl.NumberFormat('cs-CZ').format(price) + ' Kč';
}

function updatePreview() {
    const title = document.getElementById('in_title').value;
    document.getElementById('prev_title').innerText = title;
    
    const grid = document.getElementById('prev_grid');
    grid.innerHTML = '';
    
    const items = document.querySelectorAll('.offer-ctrl-item');
    items.forEach(ctrl => {
        const title = ctrl.getAttribute('data-title');
        const desc = ctrl.getAttribute('data-desc');
        const price = ctrl.getAttribute('data-price');
        
        const div = document.createElement('div');
        div.className = 'card';
        div.style.cssText = 'padding: 25px; display: flex; flex-direction: column; background: var(--bg2); border: 1px solid var(--border); border-radius: 20px;';
        
        div.innerHTML = `
            <h3 style="margin-top: 0; font-size: 18px; font-weight: 800; color: var(--accent);">${title}</h3>
            <p style="font-size: 14px; opacity: 0.7; flex: 1; margin: 15px 0;">${desc.replace(/\\n/g, '<br>')}</p>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                <span style="font-size: 20px; font-weight: 800; color: var(--text-h);">${formatPrice(price)}</span>
                <button class="btn btn-primary" style="padding: 8px 16px; font-size: 12px; pointer-events: none;">Rezervovat</button>
            </div>
        `;
        grid.appendChild(div);
    });
    
    if (items.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; opacity: 0.5; font-style: italic;">Žádné služby k zobrazení.</p>';
    }
}

function addOffer() {
    const select = document.getElementById('add_offer_select');
    if (!select.value) return;
    
    const option = select.options[select.selectedIndex];
    const id = select.value;
    const title = option.getAttribute('data-title');
    const desc = option.getAttribute('data-desc');
    const price = option.getAttribute('data-price');
    
    const list = document.getElementById('selected_list');
    
    // Check if already added
    if (list.querySelector(`input[value="${id}"]`)) {
        alert("Tato služba už ve výběru je.");
        return;
    }
    
    const div = document.createElement('div');
    div.className = 'offer-ctrl-item';
    div.setAttribute('data-id', id);
    div.setAttribute('data-title', title);
    div.setAttribute('data-desc', desc);
    div.setAttribute('data-price', price);
    div.style.cssText = 'background: #1f2028; padding: 15px; border-radius: 12px; border: 1px solid #2e303a; display: flex; align-items: center; justify-content: space-between; animation: modalAppear 0.2s;';
    
    div.innerHTML = `
        <input type="hidden" name="offer_ids[]" value="${id}">
        <div>
            <div style="font-weight: 800; font-size: 14px;">${title}</div>
            <div style="font-size: 12px; color: var(--accent);">${formatPrice(price)}</div>
        </div>
        <div style="display: flex; gap: 5px;">
            <button type="button" onclick="moveUp(this)" style="background: #2e303a; border: none; color: #fff; width: 28px; height: 28px; border-radius: 6px; cursor: pointer;">^</button>
            <button type="button" onclick="moveDown(this)" style="background: #2e303a; border: none; color: #fff; width: 28px; height: 28px; border-radius: 6px; cursor: pointer;">v</button>
            <button type="button" onclick="removeItem(this)" style="background: #ef4444; border: none; color: #fff; width: 28px; height: 28px; border-radius: 6px; cursor: pointer;">x</button>
        </div>
    `;
    
    list.appendChild(div);
    select.value = '';
    updatePreview();
}

function removeItem(btn) {
    btn.closest('.offer-ctrl-item').remove();
    updatePreview();
}

function moveUp(btn) {
    const item = btn.closest('.offer-ctrl-item');
    const prev = item.previousElementSibling;
    if (prev) {
        item.parentNode.insertBefore(item, prev);
        updatePreview();
    }
}

function moveDown(btn) {
    const item = btn.closest('.offer-ctrl-item');
    const next = item.nextElementSibling;
    if (next) {
        item.parentNode.insertBefore(next, item);
        updatePreview();
    }
}

document.addEventListener('DOMContentLoaded', updatePreview);
</script>

<?php include 'components/footer.php'; ?>
