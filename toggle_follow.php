<?php
require_once 'components/connector.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['followed_id'])) {
    header("Location: login.php");
    exit();
}

check_csrf();

$follower_id = $_SESSION['user_id'];
$followed_id = intval($_POST['followed_id']);

if ($follower_id === $followed_id) {
    header("Location: profile.php?id=$followed_id");
    exit();
}


$check = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
$check->bind_param("ii", $follower_id, $followed_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    
    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->bind_param("ii", $follower_id, $followed_id);
} else {
    
    $stmt = $conn->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $follower_id, $followed_id);
}

$stmt->execute();

header("Location: profile.php?id=$followed_id");
exit();
?>
