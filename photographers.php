<?php
$custom_title = "Discover - Photo Ahh";
$extra_css = "photographers.css";
include 'components/header.php';

if (isset($_GET['book'])) {
    check_csrf();
    $offer_id = intval($_GET['book']);
    $user_id = $_SESSION['user_id'];
    
    
    $stmt_off = $conn->prepare("SELECT author_id, title FROM offers WHERE id = ?");
    $stmt_off->bind_param("i", $offer_id);
    $stmt_off->execute();
    $offer = $stmt_off->get_result()->fetch_assoc();
    
    if ($offer) {
        $photog_id = $offer['author_id'];
        $type = $offer['title'];
        $desc = "Objednávka balíčku: " . $type;
        $date = date('Y-m-d', strtotime('+7 days')); 
        
        $stmt = $conn->prepare("INSERT INTO bookings (offer_id, user_id, photographer_id, type, description, date, status) VALUES (?, ?, ?, ?, ?, ?, 'odeslána')");
        $stmt->bind_param("iiisss", $offer_id, $user_id, $photog_id, $type, $desc, $date);
        $stmt->execute();
    }
    header("Location: my_bookings.php");
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : "";
$location = isset($_GET['location']) ? $_GET['location'] : "";
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 999999;


$query = "SELECT u.id as user_id, u.username, u.avatar, u.bio, 
          (SELECT AVG(rating) FROM reviews WHERE photographer_id = u.id AND approved = 1) as avg_rating,
          (SELECT COUNT(id) FROM reviews WHERE photographer_id = u.id AND approved = 1) as review_count,
          (SELECT MIN(price) FROM offers WHERE author_id = u.id) as min_offer_price,
          (SELECT MAX(price) FROM offers WHERE author_id = u.id) as max_offer_price
          FROM users u 
          WHERE u.role = 'author'";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND u.username LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($location)) {
    $query .= " AND EXISTS (SELECT 1 FROM bookings WHERE photographer_id = u.id AND location LIKE ?)";
    $params[] = "%$location%";
    $types .= "s";
}


$query .= " HAVING 1=1";

if ($min_price > 0) {
    $query .= " AND (SELECT MIN(price) FROM offers WHERE author_id = u.id) >= ?";
    $params[] = $min_price;
    $types .= "i";
}
if ($max_price < 999999) {
    $query .= " AND (SELECT MIN(price) FROM offers WHERE author_id = u.id) <= ?";
    $params[] = $max_price;
    $types .= "i";
}

$query .= " ORDER BY avg_rating DESC, review_count DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="discover-container">
    <div class="content-inner">
        <div class="discover-header" style="margin-bottom: 40px;">
            <h2 style="font-size: 32px; font-weight: 800; color: var(--text-h);">Objevte autory</h2>
            
            <form action="photographers.php" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 25px; background: var(--bg2); padding: 20px; border-radius: 16px; border: 1px solid var(--border);">
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-size: 11px; font-weight: 800; opacity: 0.5; margin-bottom: 5px; display: block;">JMÉNO</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Hledat jméno..." style="width: 100%;">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="font-size: 11px; font-weight: 800; opacity: 0.5; margin-bottom: 5px; display: block;">LOKALITA</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="Kde fotí? (např. Praha)" style="width: 100%;">
                </div>
                <div style="flex: 0.5; min-width: 100px;">
                    <label style="font-size: 11px; font-weight: 800; opacity: 0.5; margin-bottom: 5px; display: block;">CENA OD</label>
                    <input type="number" name="min_price" value="<?php echo $min_price; ?>" style="width: 100%;">
                </div>
                <div style="flex: 0.5; min-width: 100px;">
                    <label style="font-size: 11px; font-weight: 800; opacity: 0.5; margin-bottom: 5px; display: block;">CENA DO</label>
                    <input type="number" name="max_price" value="<?php echo $max_price > 0 && $max_price < 999999 ? $max_price : ''; ?>" placeholder="Max" style="width: 100%;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="height: 45px; padding: 0 30px; font-weight: 800;">FILTROVAT</button>
                </div>
            </form>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="card photographer-card" style="padding: 25px; border: 1px solid var(--border); position: relative;">
                        <a href="profile.php?id=<?php echo $row['user_id']; ?>" class="photog-link" style="text-decoration: none; color: inherit;">
                            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                                <img src="<?php echo htmlspecialchars($row['avatar']); ?>" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent);">
                                <div>
                                    <h4 style="margin: 0; font-size: 18px; color: var(--text-h); font-weight: 800;"><?php echo htmlspecialchars($row['username']); ?></h4>
                                    <div style="display: flex; align-items: center; gap: 5px; margin-top: 4px;">
                                        <i data-lucide="star" style="width: 14px; height: 14px; fill: #f1c40f; color: #f1c40f;"></i>
                                        <span style="font-size: 13px; font-weight: 800;"><?php echo $row['avg_rating'] ? round($row['avg_rating'], 1) : 'N/A'; ?></span>
                                        <span style="opacity: 0.5; font-size: 12px;">(<?php echo $row['review_count']; ?>)</span>
                                    </div>
                                </div>
                            </div>
                            <p style="font-size: 13px; color: var(--text); opacity: 0.7; line-height: 1.6; height: 4.8em; overflow: hidden; margin-bottom: 20px;">
                                <?php echo htmlspecialchars($row['bio'] ?? 'Bez popisu'); ?>
                            </p>
                        </a>
                        
                        <div style="border-top: 1px solid var(--border); padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="font-size: 10px; font-weight: 800; opacity: 0.5; text-transform: uppercase;">Cena od</span>
                                <div style="font-size: 18px; font-weight: 800; color: var(--accent);"><?php echo $row['min_offer_price'] ? number_format($row['min_offer_price'], 0, ',', ' ') . ' Kč' : 'Na dotaz'; ?></div>
                            </div>
                            <a href="profile.php?id=<?php echo $row['user_id']; ?>" class="btn btn-primary" style="padding: 10px 20px; font-size: 12px; font-weight: 800;">DETAIL</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; margin-top: 50px; color: #888; font-style: italic;">Žádní autoři nevyhovují vašim filtrům.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'components/footer.php'; ?>
