<?php
session_start();
include 'components/connector.php';

function addWatermarkAndResize($source, $dest, $text) {
    $info = getimagesize($source);
    if (!$info) return false;
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($source); break;
        case 'image/png': $img = imagecreatefrompng($source); break;
        case 'image/gif': $img = imagecreatefromgif($source); break;
        case 'image/webp': $img = imagecreatefromwebp($source); break;
        default: return false;
    }
    $width = imagesx($img); 
    $height = imagesy($img);

    // Resize to max 800px width for preview
    $max_width = 800;
    if ($width > $max_width) {
        $ratio = $max_width / $width;
        $new_width = $max_width;
        $new_height = $height * $ratio;
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Handle transparency for PNG/WEBP
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($img);
        $img = $resized;
        $width = $new_width;
        $height = $new_height;
    }

    $color = imagecolorallocatealpha($img, 255, 255, 255, 80);
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    
    // Add grid watermark
    for ($x = 20; $x < $width; $x += $textWidth + 100) {
        for ($y = 20; $y < $height; $y += $textHeight + 100) {
            imagestring($img, $fontSize, $x, $y, $text, $color);
        }
    }
    
    // Always output as JPEG for the preview to save space
    $result = imagejpeg($img, $dest, 60); // 60% quality = low quality preview
    imagedestroy($img);
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $booking_id = intval($_POST['booking_id']);
    $status = $_POST['status'];
    $note = $_POST['note'] ?? null;
    $uid = $_SESSION['user_id'] ?? null;
    $is_guest = isset($_POST['is_guest']) && $_POST['is_guest'] == '1';
    $hash = $_POST['hash'] ?? '';

    $check = $conn->prepare("SELECT b.photographer_id, b.user_id, b.status as current_status, b.access_hash, u.username as photog_name 
                            FROM bookings b 
                            JOIN users u ON b.photographer_id = u.id 
                            WHERE b.id = ?");
    $check->bind_param("i", $booking_id);
    $check->execute();
    $booking = $check->get_result()->fetch_assoc();

    if (!$booking) die("Rezervace nenalezena.");

    $is_photographer = ($uid && $booking['photographer_id'] == $uid);
    $is_client = ($uid && $booking['user_id'] == $uid);
    $is_valid_guest = ($is_guest && $booking['access_hash'] === $hash && !empty($hash));

    if ($is_photographer) {
        if ($status === 'zamítnuta_s_duvodem') {
            $stmt = $conn->prepare("UPDATE bookings SET status = ?, rejection_reason = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $note, $booking_id);
        } elseif ($status === 'protinabídka') {
            $stmt = $conn->prepare("UPDATE bookings SET status = ?, counter_offer_note = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $note, $booking_id);
        } elseif ($status === 'hotova' || $status === 'add_more_photos') {
            
            $uploaded_files = [];
            
            if ($status === 'add_more_photos') {
                $get_old = $conn->prepare("SELECT completed_photos_json FROM bookings WHERE id = ?");
                $get_old->bind_param("i", $booking_id);
                $get_old->execute();
                $old_photos_raw = $get_old->get_result()->fetch_assoc()['completed_photos_json'] ?? '[]';
                $uploaded_files = json_decode($old_photos_raw, true);
            }

            $upload_dir_rel = 'uploads/bookings/' . $booking_id . '/';
            $upload_dir_abs = __DIR__ . '/' . $upload_dir_rel;
            if (!is_dir($upload_dir_abs)) mkdir($upload_dir_abs, 0777, true);

            if (isset($_FILES['completed_photos'])) {
                foreach ($_FILES['completed_photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['completed_photos']['error'][$key] === 0) {
                        $original_name = $_FILES['completed_photos']['name'][$key];
                        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                        $new_name = uniqid() . '.' . $ext;
                        
                        $target_abs = $upload_dir_abs . $new_name;
                        $target_rel = $upload_dir_rel . $new_name;
                        
                        $wm_target_abs = $upload_dir_abs . 'wm_' . $new_name;
                        $wm_target_rel = $upload_dir_rel . 'wm_' . $new_name;
                        
                        if (move_uploaded_file($tmp_name, $target_abs)) {
                            // Zkusit přidat vodoznak a zmenšit rozlišení
                            $has_wm = addWatermarkAndResize($target_abs, $wm_target_abs, $booking['photog_name']);
                            
                            $uploaded_files[] = [
                                'path' => $target_rel, 
                                'wm_path' => $has_wm ? $wm_target_rel : $target_rel,
                                'name' => $original_name
                            ];
                        }
                    }
                }
            }
            
            $json_photos = json_encode($uploaded_files);
            if ($status === 'hotova') {
                $new_hash = empty($booking['access_hash']) ? bin2hex(random_bytes(16)) : $booking['access_hash'];
                $stmt = $conn->prepare("UPDATE bookings SET status = 'hotova', completed_photos_json = ?, access_hash = ? WHERE id = ?");
                $stmt->bind_param("ssi", $json_photos, $new_hash, $booking_id);
            } else {
                $stmt = $conn->prepare("UPDATE bookings SET completed_photos_json = ? WHERE id = ?");
                $stmt->bind_param("si", $json_photos, $booking_id);
            }
        } elseif ($status === 'mark_paid') {
            $stmt = $conn->prepare("UPDATE bookings SET is_paid = 1 WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $booking_id);
        }
    } elseif (($is_client || $is_valid_guest) && $booking['current_status'] === 'protinabídka') {
        if ($status === 'potvrzena' || $status === 'zamítnuta') {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $booking_id);
        }
    }

    if (isset($stmt) && $stmt->execute()) {
        if ($is_valid_guest) {
            header("Location: view_booking.php?hash=" . $hash . "&success=1");
        } else {
            header("Location: my_bookings.php?success=1");
        }
    } else {
        if ($is_valid_guest) {
            header("Location: view_booking.php?hash=" . $hash . "&error=1");
        } else {
            header("Location: my_bookings.php?error=1");
        }
    }
}
?>
