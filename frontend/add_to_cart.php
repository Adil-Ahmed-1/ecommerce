<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

 $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
 $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

 $quantity = max(1, min(10, $quantity));

include(__DIR__ . '/../backend/config/db.php');

 $stmt = mysqli_prepare($conn, "SELECT id, product_name, price, image FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false]);
    exit;
}

 $product = mysqli_fetch_assoc($result);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

 $found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_id'] == $product_id) {
        $item['quantity'] += $quantity;
        if ($item['quantity'] > 10) $item['quantity'] = 10;
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    $_SESSION['cart'][] = [
        'product_id' => $product['id'],
        'product_name' => $product['product_name'],
        'price' => $product['price'],
        'image' => $product['image'],
        'quantity' => $quantity
    ];
}

 $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

echo json_encode([
    'success' => true,
    'cart_count' => $cart_count
]);