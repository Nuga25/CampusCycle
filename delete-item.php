<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['item_id'])) {
    header("Location: /CampusCycle/index.php");
    exit();
}

$item_id = intval($_POST['item_id']);
$user_id = $_SESSION['user_id'];

// Make sure the item belongs to this user
$stmt = $conn->prepare("SELECT id, image FROM items WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: /CampusCycle/index.php");
    exit();
}

// Delete images from disk
$img_stmt = $conn->prepare("SELECT filename FROM item_images WHERE item_id = ?");
$img_stmt->bind_param("i", $item_id);
$img_stmt->execute();
$images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$img_stmt->close();

foreach ($images as $img) {
    $path = __DIR__ . '/uploads/' . $img['filename'];
    if (file_exists($path)) unlink($path);
}

// Delete item (claims + images cascade automatically)
$stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: /CampusCycle/dashboard.php");
exit();
?>