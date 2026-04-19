<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

include('../backend/config/db.php');

 $user_id = intval($_SESSION['user_id']);
 $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
 $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
 $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($product_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Select a valid rating.']);
    exit;
}
if (strlen($comment) < 3) {
    echo json_encode(['success' => false, 'message' => 'Write at least 3 characters.']);
    exit;
}

// Check product exists
 $p = mysqli_query($conn, "SELECT id FROM products WHERE id = $product_id LIMIT 1");
if (!$p || mysqli_num_rows($p) === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

// Check already reviewed (PHP side, no UNIQUE KEY needed)
 $check = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id = $user_id AND product_id = $product_id LIMIT 1");
if ($check && mysqli_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'message' => 'You already reviewed this product.']);
    exit;
}

// Insert
 $comment = mysqli_real_escape_string($conn, $comment);
 $insert = mysqli_query($conn, "INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES ($user_id, $product_id, $rating, '$comment', NOW())");

if (!$insert) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}

// New stats
 $stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ROUND(IFNULL(AVG(rating),0),1) as avg_r, COUNT(id) as total_r FROM reviews WHERE product_id = $product_id"));

echo json_encode([
    'success' => true,
    'new_avg' => floatval($stats['avg_r']),
    'new_total' => intval($stats['total_r'])
]);
exit;