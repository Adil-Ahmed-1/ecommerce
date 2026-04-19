<?php
session_start();
include("../backend/config/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");

echo json_encode(['success' => true]);
?>