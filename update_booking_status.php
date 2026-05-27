<?php
session_start();
include 'components/connector.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $status = $_POST['status'];
    $note = $_POST['note'] ?? null;
    $uid = $_SESSION['user_id'];

    $check = $conn->prepare("SELECT photographer_id, user_id, status as current_status FROM bookings WHERE id = ?");
    $check->bind_param("i", $booking_id);
    $check->execute();
    $booking = $check->get_result()->fetch_assoc();

    if (!$booking) die("Rezervace nenalezena.");

    $is_photographer = ($booking['photographer_id'] == $uid);
    $is_client = ($booking['user_id'] == $uid);

    if ($is_photographer) {
        if ($status === 'zamítnuta_s_duvodem') {
            $stmt = $conn->prepare("UPDATE bookings SET status = ?, rejection_reason = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $note, $booking_id);
        } elseif ($status === 'protinabídka') {
            $stmt = $conn->prepare("UPDATE bookings SET status = ?, counter_offer_note = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $note, $booking_id);
        } elseif ($status === 'hotova' || $status === 'add_more_photos') {
            // Logika pro nahrávání fotek (nové i dodatečné)
            $uploaded_files = [];
            
            if ($status === 'add_more_photos') {
                $get_old = $conn->prepare("SELECT completed_photos_json FROM bookings WHERE id = ?");
                $get_old->bind_param("i", $booking_id);
                $get_old->execute();
                $old_photos_raw = $get_old->get_result()->fetch_assoc()['completed_photos_json'] ?? '[]';
                $uploaded_files = json_decode($old_photos_raw, true);
            }

            $upload_dir = 'cloud/' . $uid . '/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (isset($_FILES['completed_photos'])) {
                foreach ($_FILES['completed_photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['completed_photos']['error'][$key] === 0) {
                        $original_name = $_FILES['completed_photos']['name'][$key];
                        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
                        $new_name = uniqid() . '.' . $ext;
                        $target = $upload_dir . $new_name;
                        if (move_uploaded_file($tmp_name, $target)) {
                            // TADY UKLÁDÁME OBĚ INFORMACE
                            $uploaded_files[] = ['path' => $target, 'name' => $original_name];
                        }
                    }
                }
            }
            
            $json_photos = json_encode($uploaded_files);
            if ($status === 'hotova') {
                $stmt = $conn->prepare("UPDATE bookings SET status = 'hotova', completed_photos_json = ? WHERE id = ?");
                $stmt->bind_param("si", $json_photos, $booking_id);
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
    } elseif ($is_client && $booking['current_status'] === 'protinabídka') {
        if ($status === 'potvrzena' || $status === 'zamítnuta') {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $booking_id);
        }
    }

    if (isset($stmt) && $stmt->execute()) {
        header("Location: my_bookings.php?success=1");
    } else {
        header("Location: my_bookings.php?error=1");
    }
}
?>
