<?php
include 'components/connector.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    check_csrf();
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id']);
    $photographer_id = intval($_POST['photographer_id']);
    $rating = intval($_POST['rating']);
    $comment = $_POST['comment'];

    // Access control: Check if user owns the booking
    $stmt_access = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
    $stmt_access->bind_param("ii", $booking_id, $user_id);
    $stmt_access->execute();
    if ($stmt_access->get_result()->num_rows === 0) {
        header("Location: my_bookings.php?error=unauthorized");
        exit();
    }

    $stmt_check = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ?");
    $stmt_check->bind_param("i", $booking_id);
    $stmt_check->execute();
    $check = $stmt_check->get_result();
    
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, photographer_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $booking_id, $photographer_id, $user_id, $rating, $comment);
        $stmt->execute();
    }

    header("Location: my_bookings.php?success=review_sent");
} else {
    header("Location: index.php");
}
?>
