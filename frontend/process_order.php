<?php
session_start();
include("../backend/config/db.php");

header('Content-Type: application/json');

/* ===== LOGIN CHECK ===== */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

 $user_id = intval($_SESSION['user_id']);

/* ===== GET POST DATA ===== */
 $name           = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
 $phone          = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
 $email          = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
 $address        = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
 $city           = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
 $payment_method = mysqli_real_escape_string($conn, trim($_POST['payment_method'] ?? 'cod'));

/* ===== BASIC VALIDATION ===== */
if (empty($name) || empty($phone) || empty($address) || empty($city)) {
    echo json_encode(['success' => false, 'message' => 'All shipping fields are required']);
    exit;
}

if (!in_array($payment_method, ['cod', 'jazzcash', 'easypaisa'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

/* ===== GET CART ITEMS ===== */
 $cartStmt = mysqli_prepare($conn, "
    SELECT c.product_id, c.quantity, p.product_name, p.price, p.image 
    FROM cart c 
    INNER JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
mysqli_stmt_bind_param($cartStmt, "i", $user_id);
mysqli_stmt_execute($cartStmt);
 $cartResult = mysqli_stmt_get_result($cartStmt);

 $cart_items = [];
 $total = 0;
while ($row = mysqli_fetch_assoc($cartResult)) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit;
}

/* ===== PAYMENT VARIABLES ===== */
 $transaction_id = '';
 $sender_number  = '';
 $sender_name    = '';
 $notes          = '';
 $proof_image    = '';
 $pay_status     = 'pending'; // Default for COD

/* ===== IF NOT COD — Handle Payment Fields ===== */
if ($payment_method !== 'cod') {
    
    $transaction_id = mysqli_real_escape_string($conn, trim($_POST['transaction_id'] ?? ''));
    $sender_number  = mysqli_real_escape_string($conn, trim($_POST['sender_number'] ?? ''));
    $sender_name    = mysqli_real_escape_string($conn, trim($_POST['sender_name'] ?? ''));
    $notes          = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

    if (empty($transaction_id) || empty($sender_number) || empty($sender_name)) {
        echo json_encode(['success' => false, 'message' => 'All payment fields are required']);
        exit;
    }

    /* ===== IMAGE UPLOAD ===== */
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
        
        $file_name = $_FILES['proof_image']['name'];
        $file_tmp  = $_FILES['proof_image']['tmp_name'];
        $file_size = $_FILES['proof_image']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG and PNG images allowed']);
            exit;
        }

        if ($file_size > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image size must be under 2MB']);
            exit;
        }

        $new_name = 'pay_' . $user_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $upload_path = '../backend/uploads/payments/';

        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $upload_path . $new_name)) {
            $proof_image = $new_name;
        } else {
            echo json_encode(['success' => false, 'message' => 'Image upload failed. Try again.']);
            exit;
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Payment proof image is required']);
        exit;
    }
}

/* ===== GENERATE ORDER ID ===== */
 $order_id = 'BS-' . strtoupper(substr(md5($user_id . time()), 0, 8));

/* ===== START TRANSACTION ===== */
mysqli_begin_transaction($conn);

try {

    /* 1. INSERT INTO ORDERS TABLE */
    $order_sql = "INSERT INTO orders 
        (order_id, user_id, name, phone, email, address, city, payment_method, total_amount, status, created_at, updated_at) 
        VALUES 
        ('$order_id', $user_id, '$name', '$phone', '$email', '$address', '$city', '$payment_method', $total, 'pending', NOW(), NOW())";
    
    mysqli_query($conn, $order_sql);
    $order_db_id = mysqli_insert_id($conn);

    /* 2. INSERT ORDER ITEMS */
    foreach ($cart_items as $item) {
        $pid = intval($item['product_id']);
        $pname = mysqli_real_escape_string($conn, $item['product_name']);
        $price = floatval($item['price']);
        $qty = intval($item['quantity']);
        $img = mysqli_real_escape_string($conn, $item['image']);

        $item_sql = "INSERT INTO order_items 
            (order_id, product_id, product_name, price, quantity, image) 
            VALUES 
            ($order_db_id, $pid, '$pname', $price, $qty, '$img')";
        mysqli_query($conn, $item_sql);

        /* 3. REDUCE PRODUCT STOCK (agar stock column hai) */
        mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE id = $pid AND stock >= $qty");
    }

    /* 4. INSERT INTO PAYMENTS TABLE */
    $pay_sql = "INSERT INTO payments 
        (user_id, amount, method, status, transaction_id, sender_number, sender_name, notes, proof_image, created_at, updated_at) 
        VALUES 
        ($user_id, $total, '$payment_method', '$pay_status', '$transaction_id', '$sender_number', '$sender_name', '$notes', '$proof_image', NOW(), NOW())";
    
    mysqli_query($conn, $pay_sql);

    /* 5. CLEAR USER CART */
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");

    /* COMMIT TRANSACTION */
    mysqli_commit($conn);

    /* SUCCESS RESPONSE */
    echo json_encode([
        'success'     => true,
        'order_id'    => $order_id,
        'total'       => $total,
        'payment'     => $payment_method,
        'message'     => 'Order placed successfully!'
    ]);

} catch (Exception $e) {
    
    /* ROLLBACK ON ERROR */
    mysqli_rollback($conn);
    
    /* Delete uploaded image if rollback */
    if (!empty($proof_image) && file_exists('../backend/uploads/payments/' . $proof_image)) {
        unlink('../backend/uploads/payments/' . $proof_image);
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

?>