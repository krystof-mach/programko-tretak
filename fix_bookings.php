<?php
require_once 'components/connector.php';

$res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'guest_name'");
if ($res->num_rows > 0) {
    echo "Columns exist.";
} else {
    echo "Columns missing. Adding them...";
    $conn->query("ALTER TABLE bookings ADD COLUMN guest_name VARCHAR(100) AFTER description");
    $conn->query("ALTER TABLE bookings ADD COLUMN guest_email VARCHAR(100) AFTER guest_name");
    $conn->query("ALTER TABLE bookings ADD COLUMN guest_phone VARCHAR(50) AFTER guest_email");
    echo " Done.";
}
?>