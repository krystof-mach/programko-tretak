<?php
require_once 'components/connector.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    http_response_code(403);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);

// Zjistit, zda již lajknuto
$check = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$check->bind_param("ii", $user_id, $post_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Un-like
    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $status = 'unliked';
} else {
    // Like
    $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $post_id);
    $status = 'liked';
}

$stmt->execute();

// Získat aktuální počet lajků
$res_count = $conn->query("SELECT COUNT(*) as cnt FROM likes WHERE post_id = $post_id");
$count = $res_count->fetch_assoc()['cnt'];

echo json_encode(['status' => $status, 'likes' => $count]);
?>
