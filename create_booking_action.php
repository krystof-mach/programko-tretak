<?php
session_start();
include 'components/connector.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $user_id = $_SESSION['user_id'] ?? null;
    $photographer_id = intval($_POST['photographer_id']);
    $offer_id = !empty($_POST['offer_id']) ? intval($_POST['offer_id']) : null;
    $type = $_POST['type'];
    $date = $_POST['date'];
    $location = $_POST['location'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $description = $_POST['description'];
    
    $guest_name = $_POST['guest_name'] ?? null;
    $guest_email = $_POST['guest_email'] ?? null;
    $guest_phone = $_POST['guest_phone'] ?? null;

    if (!$user_id && empty($guest_name)) {
        die("Pro neregistrované uživatele je vyžadováno jméno a e-mail.");
    }
    
    // Always generate a hash so guests have a direct link right away
    $access_hash = bin2hex(random_bytes(16));

    if ($offer_id) {
        $stmt = $conn->prepare("INSERT INTO bookings (offer_id, user_id, photographer_id, type, date, location, budget, description, guest_name, guest_email, guest_phone, access_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) die("Prepare failed (with offer): " . $conn->error);
        $stmt->bind_param("iiisssssssss", $offer_id, $user_id, $photographer_id, $type, $date, $location, $budget, $description, $guest_name, $guest_email, $guest_phone, $access_hash);
    } else {
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, photographer_id, type, date, location, budget, description, guest_name, guest_email, guest_phone, access_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) die("Prepare failed (without offer): " . $conn->error);
        $stmt->bind_param("iisssssssss", $user_id, $photographer_id, $type, $date, $location, $budget, $description, $guest_name, $guest_email, $guest_phone, $access_hash);
    }

    if ($stmt->execute()) {
        if ($user_id) {
            header("Location: my_bookings.php?success=1");
        } else {
            // Neregistrovaný uživatel je přesměrován do svého prostředí objednávky
            header("Location: view_booking.php?hash=" . $access_hash);
        }
    } else {
        echo "Chyba při vytváření rezervace: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>
