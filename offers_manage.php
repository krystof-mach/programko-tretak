<?php
$custom_title = "Moje nabídky - Photo Ahh";
$extra_css = "offers_manage.css";
include 'components/header.php';

if ($role !== 'author') { header("Location: index.php"); exit(); }

$author_id = $_SESSION['user_id'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $conn->prepare("INSERT INTO offers (author_id, title, description, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $author_id, $_POST['title'], $_POST['description'], $_POST['price']);
    $stmt->execute();
}
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM offers WHERE id = ? AND author_id = ?");
    $stmt->bind_param("ii", $del_id, $author_id);
    $stmt->execute();
}
$stmt = $conn->prepare("SELECT * FROM offers WHERE author_id = ?");
$stmt->bind_param("i", $author_id);
$stmt->execute();
$offers = $stmt->get_result();
?>

<main class="manage-container">
    <div class="card">
        <h3>Nová nabídka</h3>
        <form method="POST">
            <input type="text" name="title" placeholder="Název" required>
            <textarea name="description" placeholder="Popis"></textarea>
            <input type="number" name="price" placeholder="Cena" required>
            <button type="submit" class="nav-btn">PŘIDAT</button>
        </form>
    </div>
    <div class="card">
        <h3>Vaše nabídky</h3>
        <?php while($o = $offers->fetch_assoc()): ?>
            <div class="offer-item">
                <div><strong><?php echo htmlspecialchars($o['title']); ?></strong> (<?php echo $o['price']; ?> Kč)</div>
                <a href="offers_manage.php?delete=<?php echo $o['id']; ?>" style="color:red;">Smazat</a>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<?php include 'components/footer.php'; ?>
