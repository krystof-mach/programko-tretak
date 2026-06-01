<?php
require_once 'components/connector.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    http_response_code(403);
    exit();
}

check_csrf();

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);


$check = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$check->bind_param("ii", $user_id, $post_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    
    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $status = 'unliked';
} else {
    
    $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $post_id);
    $status = 'liked';
}

$stmt->execute();


$stmt_count = $conn->prepare("SELECT COUNT(*) as cnt FROM likes WHERE post_id = ?");
$stmt_count->bind_param("i", $post_id);
$stmt_count->execute();
$count = $stmt_count->get_result()->fetch_assoc()['cnt'];

echo json_encode(['status' => $status, 'likes' => $count]);
?>
