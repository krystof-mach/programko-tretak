<?php
$custom_title = "Discover - Photo Ahh";
$extra_css = "photographers.css";
include 'components/header.php';

if (isset($_GET['book'])) {
    $offer_id = intval($_GET['book']);
    $user_id = $_SESSION['user_id'];
    
    // Získání informací o nabídce pro vytvoření rezervace
    $stmt_off = $conn->prepare("SELECT author_id, title FROM offers WHERE id = ?");
    $stmt_off->bind_param("i", $offer_id);
    $stmt_off->execute();
    $offer = $stmt_off->get_result()->fetch_assoc();
    
    if ($offer) {
        $photog_id = $offer['author_id'];
        $type = $offer['title'];
        $desc = "Objednávka balíčku: " . $type;
        $date = date('Y-m-d', strtotime('+7 days')); // Výchozí datum za týden
        
        $stmt = $conn->prepare("INSERT INTO bookings (offer_id, user_id, photographer_id, type, description, date, status) VALUES (?, ?, ?, ?, ?, ?, 'odeslána')");
        $stmt->bind_param("iiisss", $offer_id, $user_id, $photog_id, $type, $desc, $date);
        $stmt->execute();
    }
    header("Location: my_bookings.php");
}

$search = isset($_GET['search']) ? $_GET['search'] : "";
$query = "SELECT u.id as user_id, u.username, u.avatar, u.bio, o.id as offer_id, o.title, o.price 
          FROM users u 
          LEFT JOIN offers o ON u.id = o.author_id 
          WHERE u.role = 'author'";

if (!empty($search)) {
    $query .= " AND u.username LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$query .= " GROUP BY u.id";
$result = $conn->query($query);
?>

<main class="grid">
    <div class="discover-header">
        <h2>Objevte autory</h2>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="card photographer-card">
                <a href="profile.php?id=<?php echo $row['user_id']; ?>" class="photog-link">
                    <img src="<?php echo htmlspecialchars($row['avatar']); ?>" class="photographer-avatar">
                    <div class="photog-info-text">
                        <h4 class="photog-name"><?php echo htmlspecialchars($row['username']); ?></h4>
                        <p class="photog-bio-short"><?php echo (strlen($row['bio'] ?? '') > 60) ? substr(htmlspecialchars($row['bio']), 0, 57)."..." : htmlspecialchars($row['bio'] ?? 'Bez bia'); ?></p>
                    </div>
                </a>
                
                <div class="photog-offer-section">
                    <?php if ($row['offer_id']): ?>
                        <div class="offer-info">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="offer-price"><?php echo number_format($row['price'], 0, ',', ' '); ?> Kč</div>
                        </div>
                        <a href="photographers.php?book=<?php echo $row['offer_id']; ?>" class="btn btn-primary offer-btn" onclick="return confirm('Objednat?')">OBJEDNAT</a>
                    <?php else: ?>
                        <div class="offer-info">
                            <p style="color: #888; font-style: italic; font-size: 11px; margin: 0;">Žádná nabídka</p>
                        </div>
                        <a href="profile.php?id=<?php echo $row['user_id']; ?>" class="btn btn-secondary offer-btn" style="background: #eee !important;">PROFIL</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center; margin-top: 50px; color: #888;">Žádní autoři nebyli nalezeni.</p>
    <?php endif; ?>
</main>

<?php include 'components/footer.php'; ?>
