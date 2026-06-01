<?php
$custom_title = "Dashboard - Photo Ahh";
include 'components/header.php';

if (!in_array($role, ['author', 'admin'])) {
    header("Location: index.php");
    exit();
}

$uid = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE photographer_id = ? AND status NOT IN ('hotova', 'zamítnuta')");
$stmt->bind_param("i", $uid);
$stmt->execute();
$active_bookings_res = $stmt->get_result();
$active_bookings = $active_bookings_res->fetch_assoc()['count'];


$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE photographer_id = ? AND approved = 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$rating_res = $stmt->get_result();
$rating_data = $rating_res->fetch_assoc();
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$total_reviews = $rating_data['count'];


$stmt = $conn->prepare("SELECT SUM(CAST(budget AS DECIMAL(10,2))) as total FROM bookings WHERE photographer_id = ? AND is_paid = 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
if (!$stmt) {
    
    $stmt = $conn->prepare("SELECT SUM(budget) as total FROM bookings WHERE photographer_id = ? AND is_paid = 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}
$income_res = $stmt->get_result();
$total_income = $income_res->fetch_assoc()['total'] ?? 0;


$storage_info = getUserStorageInfo($uid, $conn);
$limit_bytes = $storage_info['limit_bytes'];
$used_bytes = $storage_info['used_bytes'];
$usage_percent = $storage_info['percent'];


$stmt = $conn->prepare("SELECT b.*, u.username as client_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.photographer_id = ? AND b.date >= CURDATE() AND b.status = 'potvrzena' ORDER BY b.date ASC LIMIT 5");
$stmt->bind_param("i", $uid);
$stmt->execute();
$upcoming_res = $stmt->get_result();
?>

<main class="dashboard-container" style="padding: 40px;">
    <div class="content-inner">
        <div style="margin-bottom: 40px;">
            <h1 style="font-size: 32px; font-weight: 800; color: var(--text-h);">Můj Dashboard</h1>
            <p style="opacity: 0.6;">Přehled vašich aktivit a statistik.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div class="card" style="padding: 25px; display: flex; flex-direction: column; gap: 10px; border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px; font-weight: 700; opacity: 0.6; text-transform: uppercase;">Aktivní zakázky</span>
                    <i data-lucide="calendar" style="color: var(--accent);"></i>
                </div>
                <span style="font-size: 32px; font-weight: 800; color: var(--text-h);"><?php echo htmlspecialchars($active_bookings); ?></span>
            </div>

            <div class="card" style="padding: 25px; display: flex; flex-direction: column; gap: 10px; border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px; font-weight: 700; opacity: 0.6; text-transform: uppercase;">Průměrné hodnocení</span>
                    <i data-lucide="star" style="color: #f1c40f;"></i>
                </div>
                <span style="font-size: 32px; font-weight: 800; color: var(--text-h);"><?php echo htmlspecialchars($avg_rating); ?> <small style="font-size: 14px; opacity: 0.5;">/ 5</small></span>
                <span style="font-size: 12px; opacity: 0.5;">Z <?php echo htmlspecialchars($total_reviews); ?> recenzí</span>
            </div>

            <div class="card" style="padding: 25px; display: flex; flex-direction: column; gap: 10px; border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px; font-weight: 700; opacity: 0.6; text-transform: uppercase;">Celkové příjmy</span>
                    <i data-lucide="dollar-sign" style="color: #2ecc71;"></i>
                </div>
                <span style="font-size: 32px; font-weight: 800; color: var(--text-h);"><?php echo htmlspecialchars(number_format($total_income, 0, ',', ' ')); ?> <small style="font-size: 14px; opacity: 0.5;">Kč</small></span>
            </div>

            <div class="card" style="padding: 25px; display: flex; flex-direction: column; gap: 10px; border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px; font-weight: 700; opacity: 0.6; text-transform: uppercase;">Kapacita cloudu</span>
                    <i data-lucide="hard-drive" style="color: var(--accent);"></i>
                </div>
                <span style="font-size: 32px; font-weight: 800; color: var(--text-h);"><?php echo htmlspecialchars($usage_percent); ?> <small style="font-size: 14px; opacity: 0.5;">%</small></span>
                <div style="width: 100%; height: 6px; background: var(--bg); border-radius: 3px; overflow: hidden; margin-top: 5px;">
                    <div style="width: <?php echo htmlspecialchars($usage_percent); ?>%; height: 100%; background: <?php echo $usage_percent > 90 ? '#ef4444' : 'var(--accent)'; ?>;"></div>
                </div>
                <?php if ($usage_percent > 90): ?>
                    <span style="font-size: 11px; color: #ef4444; font-weight: 700;">VAROVÁNÍ: Kapacita téměř plná!</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
            <section>
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 20px; color: var(--text-h);">Nejbližší termíny</h3>
                <div class="card" style="padding: 0; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: var(--bg); border-bottom: 1px solid var(--border);">
                            <tr>
                                <th style="padding: 15px 20px; text-align: left; font-size: 12px; text-transform: uppercase; opacity: 0.6;">Klient</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 12px; text-transform: uppercase; opacity: 0.6;">Datum</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 12px; text-transform: uppercase; opacity: 0.6;">Typ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($ut = $upcoming_res->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 15px 20px; font-weight: 700;"><?php echo htmlspecialchars($ut['client_name']); ?></td>
                                    <td style="padding: 15px 20px;"><?php echo htmlspecialchars(date('d. m. Y', strtotime($ut['date']))); ?></td>
                                    <td style="padding: 15px 20px;"><span class="badge"><?php echo htmlspecialchars($ut['type']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($upcoming_res->num_rows == 0): ?>
                                <tr><td colspan="3" style="padding: 30px; text-align: center; opacity: 0.5; font-style: italic;">Žádné potvrzené termíny v blízké době.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 20px; color: var(--text-h);">Rychlé akce</h3>
                <div style="display: grid; gap: 15px;">
                    <a href="profile.php" class="btn btn-primary" style="justify-content: center; font-weight: 800;">Zobrazit profil</a>
                    <a href="my_bookings.php" class="btn btn-primary" style="justify-content: center; font-weight: 800;">Spravovat rezervace</a>
                    <a href="cloud.php" class="btn btn-primary" style="justify-content: center; font-weight: 800;">Moje soubory</a>
                    <a href="offers_manage.php" class="btn btn-primary" style="justify-content: center; font-weight: 800;">Ceník služeb</a>
                </div>
            </section>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>
