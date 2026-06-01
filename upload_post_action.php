<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'components/connector.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['author', 'admin'])) {
    die("Access denied. Role: " . ($_SESSION['role'] ?? 'none'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['post_files'])) {
    check_csrf();
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $files = $_FILES['post_files'];

    $uploaded_urls = [];
    $all_metadata = [];
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $max_files = 4;
    $total_files = count($files['name']);
    
    if ($total_files > $max_files) {
        die("Maximálně lze nahrát $max_files fotky.");
    }

    for ($i = 0; $i < $total_files; $i++) {
        if ($files['error'][$i] === 0) {
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_filename = bin2hex(random_bytes(10)) . "." . $ext;
                $target_dir = __DIR__ . "/posts/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                
                $disk_path = $target_dir . $new_filename;
                $web_path = "posts/" . $new_filename;

                if (move_uploaded_file($files['tmp_name'][$i], $disk_path)) {
                    $uploaded_urls[] = $web_path;
                    
                    $image_info = getimagesize($disk_path);
                    $exif_data = [];
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $exif = @exif_read_data($disk_path);
                        if ($exif) {
                            $exif_data = [
                                'make' => $exif['Make'] ?? '',
                                'model' => $exif['Model'] ?? '',
                                'exposure' => $exif['ExposureTime'] ?? '',
                                'aperture' => $exif['COMPUTED']['ApertureFNumber'] ?? '',
                                'iso' => $exif['ISOSpeedRatings'] ?? '',
                                'date' => $exif['DateTimeOriginal'] ?? ''
                            ];
                        }
                    }
                    
                    $all_metadata[] = [
                        'original_name' => $files['name'][$i],
                        'mime' => $files['type'][$i],
                        'size' => $files['size'][$i],
                        'width' => $image_info[0] ?? 0,
                        'height' => $image_info[1] ?? 0,
                        'exif' => $exif_data
                    ];
                }
            }
        }
    }

    if (!empty($uploaded_urls)) {
        $first_url = $uploaded_urls[0];
        $urls_json = json_encode($uploaded_urls);
        $metadata_json = json_encode($all_metadata);

        
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, description, file_path, urls, metadata) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $title, $description, $first_url, $urls_json, $metadata_json);
        
        if ($stmt->execute()) {
            header("Location: profile.php?success=1");
            exit();
        } else {
            die("Database error: " . $stmt->error);
        }
    } else {
        die("Nebyla nahrána žádná platná fotka.");
    }
} else {
    header("Location: profile.php");
    exit();
}
?>
