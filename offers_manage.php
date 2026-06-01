<?php
$custom_title = "Správa ceníku - Photo Ahh";
$extra_css = "offers_manage.css";
include 'components/header.php';

if (!in_array($role, ['author', 'admin'])) { 
    header("Location: index.php"); 
    exit(); 
}

$author_id = $_SESSION['user_id'];


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_offer'])) {
    check_csrf();
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    $stmt = $conn->prepare("INSERT INTO offers (author_id, title, description, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $author_id, $title, $description, $price);
    $stmt->execute();
    header("Location: offers_manage.php?success=1"); exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_offer'])) {
    check_csrf();
    $offer_id = $_POST['offer_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    $stmt = $conn->prepare("UPDATE offers SET title = ?, description = ?, price = ? WHERE id = ? AND author_id = ?");
    $stmt->bind_param("ssdii", $title, $description, $price, $offer_id, $author_id);
    $stmt->execute();
    header("Location: offers_manage.php?success=2"); exit();
}


if (isset($_GET['delete'])) {
    check_csrf();
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM offers WHERE id = ? AND author_id = ?");
    $stmt->bind_param("ii", $del_id, $author_id);
    $stmt->execute();
    header("Location: offers_manage.php?success=3"); exit();
}

$stmt = $conn->prepare("SELECT * FROM offers WHERE author_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $author_id);
$stmt->execute();
$offers = $stmt->get_result();
?>

<main class="manage-container">
    <div class="content-inner">
        <div style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1 style="font-size: 32px; font-weight: 800; color: var(--text-h);">Správa ceníku</h1>
                <p style="opacity: 0.6;">Vytvořte balíčky služeb, které si klienti mohou objednat přímo z vašeho profilu.</p>
            </div>
            <button class="btn btn-primary" onclick="openOfferModal()" style="font-weight: 800;">+ NOVÝ BALÍČEK</button>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="badge" style="margin-bottom: 25px; display: inline-block; padding: 10px 20px;">
                <?php 
                    if($_GET['success'] == 1) echo "Balíček byl vytvořen.";
                    if($_GET['success'] == 2) echo "Balíček byl upraven.";
                    if($_GET['success'] == 3) echo "Balíček byl smazán.";
                ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; gap: 20px;">
            <?php while($o = $offers->fetch_assoc()): ?>
                <div class="offer-card">
                    <div class="offer-info">
                        <h3><?php echo htmlspecialchars($o['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($o['description'])); ?></p>
                        <div class="offer-price"><?php echo number_format($o['price'], 0, ',', ' '); ?> Kč</div>
                    </div>
                    <div class="offer-actions">
                        <button class="btn btn-secondary" onclick='openOfferModal(<?php echo htmlspecialchars(json_encode($o), ENT_QUOTES, "UTF-8"); ?>)'>Upravit</button>
                        <a href="offers_manage.php?delete=<?php echo $o['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-secondary" style="color: #ef4444; border-color: #ef4444; background: transparent !important;" onclick="return confirm('Opravdu smazat tento balíček?')">Smazat</a>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if ($offers->num_rows == 0): ?>
                <div class="offer-empty">
                    <i data-lucide="package-plus" style="width: 64px; height: 64px;"></i>
                    <p style="opacity: 0.5; font-style: italic; margin-bottom: 25px;">Zatím nemáte vytvořené žádné nabídky služeb.</p>
                    <button class="btn btn-primary" onclick="openOfferModal()">VYTVOŘIT PRVNÍ NABÍDKU</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Offer Modal -->
<div id="offerModal" class="modal-overlay" onclick="if(event.target === this) closeOfferModal()">
    <div class="modal-container" style="max-width: 450px; height: auto; padding: 35px; flex-direction: column;">
        <h3 id="modalTitle" style="margin-top: 0; color: var(--text-h); font-weight: 800;">Nový balíček</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="offer_id" id="modalOfferId">
            <div style="margin-bottom: 20px;">
                <label>NÁZEV SLUŽBY</label>
                <input type="text" name="title" id="modalOfferTitle" required placeholder="např. Portrétní focení Standard">
            </div>
            <div style="margin-bottom: 20px;">
                <label>POPIS (CO BALÍČEK OBSAHUJE)</label>
                <textarea name="description" id="modalOfferDesc" style="height: 120px;" placeholder="Např. 10 upravených fotek, 2 hodiny focení..."></textarea>
            </div>
            <div style="margin-bottom: 25px;">
                <label>CENA (Kč)</label>
                <input type="number" name="price" id="modalOfferPrice" required placeholder="0">
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeOfferModal()" style="flex: 1;">Zrušit</button>
                <button type="submit" name="add_offer" id="modalSubmitAdd" class="btn btn-primary" style="flex: 1; font-weight: 800;">VYTVOŘIT</button>
                <button type="submit" name="update_offer" id="modalSubmitUpdate" class="btn btn-primary" style="flex: 1; font-weight: 800; display: none;">ULOŽIT ZMĚNY</button>
            </div>
        </form>
    </div>
</div>

<script>
function openOfferModal(offer = null) {
    if (offer) {
        document.getElementById('modalTitle').innerText = 'Upravit balíček';
        document.getElementById('modalOfferId').value = offer.id;
        document.getElementById('modalOfferTitle').value = offer.title;
        document.getElementById('modalOfferDesc').value = offer.description;
        document.getElementById('modalOfferPrice').value = offer.price;
        document.getElementById('modalSubmitAdd').style.display = 'none';
        document.getElementById('modalSubmitUpdate').style.display = 'block';
    } else {
        document.getElementById('modalTitle').innerText = 'Nový balíček';
        document.getElementById('modalOfferId').value = '';
        document.getElementById('modalOfferTitle').value = '';
        document.getElementById('modalOfferDesc').value = '';
        document.getElementById('modalOfferPrice').value = '';
        document.getElementById('modalSubmitAdd').style.display = 'block';
        document.getElementById('modalSubmitUpdate').style.display = 'none';
    }
    document.getElementById('offerModal').style.display = 'flex';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeOfferModal() {
    document.getElementById('offerModal').style.display = 'none';
}
</script>

<?php include 'components/footer.php'; ?>
