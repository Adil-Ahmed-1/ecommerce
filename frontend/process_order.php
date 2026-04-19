<?php
session_start();
include("../backend/config/db.php");

header('Content-Type: application/json');

/* ===== LOGIN CHECK ===== */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* ===== GET CART ITEMS ===== */
$stmt = mysqli_prepare($conn, "
    SELECT c.id AS cart_id, c.product_id, c.quantity, 
           p.product_name, p.price, p.image 
    FROM cart c 
    INNER JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cart_items = [];
$total = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit;
}

/* ===== GET FORM DATA (NO TRIM - AS PER YOUR REQUIREMENT) ===== */
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cod';

/* ===== VALIDATION ===== */
if (empty($name) || strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please enter your full name']);
    exit;
}

if (empty($phone) || !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
    exit;
}

if (empty($address) || strlen($address) < 5) {
    echo json_encode(['success' => false, 'message' => 'Please enter your complete address']);
    exit;
}

if (empty($city) || strlen($city) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please enter your city']);
    exit;
}

/* ===== PAYMENT METHOD VALIDATION ===== */
$allowed_methods = ['cod', 'jazzcash', 'easypaisa', 'bank_transfer'];

if (!in_array($payment_method, $allowed_methods)) {
    $payment_method = 'cod';
}

/* ===== GENERATE ORDER ID ===== */
$order_id = 'BS' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)) . rand(10, 99);

/* ===== INSERT ORDER ===== */
$orderStmt = mysqli_prepare($conn, "
    INSERT INTO orders 
    (order_id, user_id, total_amount, shipping_name, shipping_phone, shipping_address, shipping_city, payment_method, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");

mysqli_stmt_bind_param($orderStmt, "sidsssss",
    $order_id,
    $user_id,
    $total,
    $name,
    $phone,
    $address,
    $city,
    $payment_method
);

if (!mysqli_stmt_execute($orderStmt)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create order',
        'error' => mysqli_error($conn) // debug
    ]);
    exit;
}

$last_order_db_id = mysqli_insert_id($conn);

/* ===== INSERT ORDER ITEMS ===== */
$itemStmt = mysqli_prepare($conn, "
    INSERT INTO order_items 
    (order_db_id, product_id, product_name, price, quantity, image) 
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($cart_items as $item) {
    mysqli_stmt_bind_param($itemStmt, "iisdis",
        $last_order_db_id,
        $item['product_id'],
        $item['product_name'],
        $item['price'],
        $item['quantity'],
        $item['image']
    );

    mysqli_stmt_execute($itemStmt);
}

/* ===== CLEAR CART (SAFE) ===== */
$delStmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ?");
mysqli_stmt_bind_param($delStmt, "i", $user_id);
mysqli_stmt_execute($delStmt);

/* ===== SUCCESS RESPONSE ===== */
echo json_encode([
    'success' => true,
    'order_id' => $order_id,
    'total' => $total,
    'payment' => $payment_method,
    'items_count' => count($cart_items)
]);