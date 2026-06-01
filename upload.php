<?php
$custom_title = "Vytvořit příspěvek - Photo Ahh";
$extra_css = "upload.css";
include 'components/header.php';

if ($role !== 'author' && $role !== 'admin') { header("Location: index.php"); exit(); }

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['photo_files'])) {
    check_csrf();
    $files = $_FILES['photo_files'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    
    $uploaded_paths = [];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $max_size = 5 * 1024 * 1024; 

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === 0) {
            $name = $files['name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_ext) && $files['size'][$i] <= $max_size) {
                $new_name = bin2hex(random_bytes(10)) . "." . $ext;
                $target = "posts/" . $new_name;
                if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                    $uploaded_urls[] = $target;
                }
            }
        }
    }

    if (!empty($uploaded_urls)) {
        $first_url = $uploaded_urls[0];
        $urls_json = json_encode($uploaded_urls);
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, description, file_path, urls) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $title, $description, $first_url, $urls_json);
        if ($stmt->execute()) {
            $message = "Příspěvek byl úspěšně zveřejněn!";
        } else {
            $error = "Chyba při ukládání do databáze.";
        }
    } else {
        $error = "Nebyla nahrána žádná platná fotka.";
    }
}
?>

<main class="upload-container" style="max-width: 600px; margin: 50px auto;">
    <div class="card">
        <h2 style="margin-top: 0; text-align: center; color: #ffcc00; text-transform: uppercase;">Nový příspěvek</h2>
        
        <?php if($message) echo "<p style='color:green; font-weight:bold; text-align:center;'>$message</p>"; ?>
        <?php if($error) echo "<p style='color:red; font-weight:bold; text-align:center;'>$error</p>"; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Název příspěvku</label>
                <input type="text" name="title" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Popis</label>
                <textarea name="description" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; height: 100px; box-sizing: border-box;"></textarea>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Vybrat fotky (1-4, max 5MB/ks)</label>
                <input type="file" name="photo_files[]" accept="image/*" multiple required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
            </div>
            
            <button type="submit" style="width: 100%; padding: 15px; background: #ffcc00; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px;">ZVEŘEJNIT PŘÍSPĚVEK</button>
        </form>
    </div>
</main>

<?php include 'components/footer.php'; ?>
