<?php
session_start();
include 'components/connector.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $photographer_id = intval($_POST['photographer_id']);
    $type = $_POST['type'];
    $date = $_POST['date'];
    $location = $_POST['location'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $description = $_POST['description'];

    if (!$user_id) {
        header("Location: login.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO bookings (user_id, photographer_id, type, date, location, budget, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $user_id, $photographer_id, $type, $date, $location, $budget, $description);

    if ($stmt->execute()) {
        header("Location: my_bookings.php?success=1");
    } else {
        echo "Chyba při vytváření rezervace: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>
