<?php
session_start();
include("../backend/config/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'];
$delta = $_POST['delta'];

/* GET CURRENT QTY */
$stmt = mysqli_prepare($conn, "SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {

    $newQty = $row['quantity'] + $delta;

    if ($newQty <= 0) {
        mysqli_query($conn, "DELETE FROM cart WHERE id = $cart_id");
    } else {
        $upd = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "ii", $newQty, $cart_id);
        mysqli_stmt_execute($upd);
    }

    echo json_encode(['success' => true]);
}
?>