<?php
session_start();
include("../backend/config/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

 $user_id = $_SESSION['user_id'];
 $product_id = intval($_POST['product_id'] ?? 0);
 $quantity = intval($_POST['quantity'] ?? 1);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

/* ===== CHECK IF PRODUCT ALREADY IN CART ===== */
 $stmt = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
mysqli_stmt_execute($stmt);
 $res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) > 0) {
    /* ===== ALREADY EXISTS — UPDATE QUANTITY ===== */
    $row = mysqli_fetch_assoc($res);
    $new_qty = $row['quantity'] + $quantity;
    
    $upd = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($upd, "iii", $new_qty, $row['id'], $user_id);
    mysqli_stmt_execute($upd);
} else {
    /* ===== NEW ITEM — INSERT INTO CART ===== */
    $ins = mysqli_prepare($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($ins, "iii", $user_id, $product_id, $quantity);
    mysqli_stmt_execute($ins);
}

/* ===== GET TOTAL CART COUNT ===== */
 $countStmt = mysqli_prepare($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
mysqli_stmt_bind_param($countStmt, "i", $user_id);
mysqli_stmt_execute($countStmt);
 $countRes = mysqli_stmt_get_result($countStmt);
 $countRow = mysqli_fetch_assoc($countRes);
 $cart_count = $countRow['total'] ?? 0;

echo json_encode(['success' => true, 'cart_count' => (int)$cart_count]);