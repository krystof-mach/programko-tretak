<?php
include 'components/connector.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    check_csrf();
    $user_id = $_SESSION['user_id'];
    $post_id = intval($_POST['post_id']);
    $comment = $_POST['comment'];

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $user_id, $comment);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
    }
}
echo json_encode(['success' => false]);
?>
