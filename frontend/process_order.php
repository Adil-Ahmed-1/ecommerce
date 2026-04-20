<?php
session_start();
include('../backend/config/db.php');

header('Content-Type: application/json');

/* ===== LOGIN CHECK ===== */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

 $user_id = intval($_SESSION['user_id']);

/* ===== RECEIVE DATA ===== */
 $shipping_name   = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
 $shipping_phone  = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
 $shipping_address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
 $shipping_city   = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
 $pay_method      = mysqli_real_escape_string($conn, trim($_POST['payment_method'] ?? 'cod'));

/* ===== BASIC VALIDATION ===== */
if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address) || empty($shipping_city)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required shipping fields']);
    exit;
}

if (!in_array($pay_method, ['cod', 'jazzcash', 'easypaisa'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

/* ===== PAYMENT FIELDS (sirf JazzCash/EasyPaisa ke liye) ===== */
 $transaction_id = '';
 $sender_number  = '';
 $sender_name    = '';
 $notes          = '';
 $proof_image    = '';

if ($pay_method !== 'cod') {

    $transaction_id = mysqli_real_escape_string($conn, trim($_POST['transaction_id'] ?? ''));
    $sender_number  = mysqli_real_escape_string($conn, trim($_POST['sender_number'] ?? ''));
    $sender_name    = mysqli_real_escape_string($conn, trim($_POST['sender_name'] ?? ''));
    $notes          = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

    if (empty($transaction_id) || empty($sender_number) || empty($sender_name)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all payment details']);
        exit;
    }

    /* ===== PROOF IMAGE UPLOAD ===== */
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === 0) {

        $file    = $_FILES['proof_image'];
        $allowed = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG and PNG images allowed']);
            exit;
        }

        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Image size must be under 2MB']);
            exit;
        }

        $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $proof_image = 'proof_' . $user_id . '_' . time() . '_' . rand(100, 999) . '.' . $ext;

        $upload_dir  = '../backend/uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $proof_image)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload payment proof']);
            exit;
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Please upload payment screenshot']);
        exit;
    }
}

/* ===== FETCH CART ITEMS (IMAGE BHI LIYA) ===== */
 $cartRes = mysqli_query($conn, "
    SELECT c.product_id, c.quantity, p.price, p.product_name, p.image
    FROM cart c
    INNER JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $user_id
");

if (!$cartRes || mysqli_num_rows($cartRes) === 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit;
}

 $cart_items   = [];
 $total_amount = 0;

while ($row = mysqli_fetch_assoc($cartRes)) {
    $cart_items[]  = $row;
    $total_amount += floatval($row['price']) * intval($row['quantity']);
}

/* ===== GENERATE ORDER ID ===== */
 $order_id = 'BS-' . strtoupper(substr(md5($user_id . microtime(true)), 0, 8));

/* ===== START TRANSACTION ===== */
mysqli_begin_transaction($conn);

try {

    /* ──────────────────────────────
       1. INSERT INTO orders TABLE
    ────────────────────────────── */
    $orderSQL = "
        INSERT INTO orders (
            user_id, order_id, shipping_name, shipping_phone,
            shipping_address, shipping_city, payment_method,
            total_amount, status, created_at
        ) VALUES (
            $user_id,
            '$order_id',
            '$shipping_name',
            '$shipping_phone',
            '$shipping_address',
            '$shipping_city',
            '$pay_method',
            $total_amount,
            'pending',
            NOW()
        )
    ";

    if (!mysqli_query($conn, $orderSQL)) {
        throw new Exception('Order insert failed: ' . mysqli_error($conn));
    }

    $db_order_id = mysqli_insert_id($conn);

    /* ──────────────────────────────
       2. INSERT INTO payments TABLE
       (sirf JazzCash / EasyPaisa)
    ────────────────────────────── */
    if ($pay_method !== 'cod') {

        $paySQL = "
            INSERT INTO payments (
                user_id, order_id, amount, method,
                transaction_id, sender_number, sender_name,
                notes, proof_image, status, created_at
            ) VALUES (
                $user_id,
                '$order_id',
                $total_amount,
                '$pay_method',
                '$transaction_id',
                '$sender_number',
                '$sender_name',
                " . ($notes       ? "'$notes'"       : "NULL") . ",
                " . ($proof_image ? "'$proof_image'" : "NULL") . ",
                'pending',
                NOW()
            )
        ";

        if (!mysqli_query($conn, $paySQL)) {
            throw new Exception('Payment insert failed: ' . mysqli_error($conn));
        }
    }

    /* ──────────────────────────────
       3. INSERT ORDER ITEMS
       (TUMHARI EXACT TABLE KE MUTABIQ)
    ────────────────────────────── */
    foreach ($cart_items as $item) {

        $pid        = intval($item['product_id']);
        $pname      = mysqli_real_escape_string($conn, $item['product_name']);
        $qty        = intval($item['quantity']);
        $price      = floatval($item['price']);
        $item_image = mysqli_real_escape_string($conn, $item['image'] ?? '');

        $itemSQL = "
            INSERT INTO order_items (order_db_id, product_id, product_name, price, quantity, image)
            VALUES ($db_order_id, $pid, '$pname', $price, $qty, '$item_image')
        ";

        if (!mysqli_query($conn, $itemSQL)) {
            throw new Exception('Order item insert failed: ' . mysqli_error($conn));
        }

        /* UPDATE STOCK */
        mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE id = $pid AND stock >= $qty");
    }

    /* ──────────────────────────────
       4. CLEAR CART
    ────────────────────────────── */
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");

    /* ===== COMMIT ===== */
    mysqli_commit($conn);

    echo json_encode([
        'success'  => true,
        'order_id' => $order_id,
        'total'    => $total_amount,
        'payment'  => $pay_method,
        'message'  => 'Order placed successfully'
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        'success' => false,
        'message' => 'Order failed: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>