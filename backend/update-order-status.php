<?php
// process/update-order-status.php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

 $order_id = (int)($_POST['order_id'] ?? 0);
 $status = $_POST['status'] ?? '';
 $payment_status = $_POST['payment_status'] ?? null;
 $redirect = $_POST['redirect'] ?? 'view'; // 'view' or 'detail'

if ($order_id <= 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid order'];
    header("Location: ../orders/view.php");
    exit;
}

 $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
 $valid_payments = ['pending', 'paid', 'failed', 'refunded'];

if (!in_array($status, $valid_statuses)) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid status'];
    header("Location: ../orders/view.php");
    exit;
}

// If status changed to cancelled, restore stock
 $old_res = mysqli_query($conn, "SELECT status FROM orders WHERE id = $order_id");
 $old_order = mysqli_fetch_assoc($old_res);

if ($status === 'cancelled' && $old_order['status'] !== 'cancelled') {
    // Get items and restore stock
    $items_res = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($item = mysqli_fetch_assoc($items_res)) {
        mysqli_query($conn, "UPDATE products SET stock = stock + {$item['quantity']} WHERE id = {$item['product_id']}");
    }
}

// If status changed FROM cancelled back to something, deduct stock again
if ($old_order['status'] === 'cancelled' && $status !== 'cancelled') {
    $items_res = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($item = mysqli_fetch_assoc($items_res)) {
        // Check if enough stock
        $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock FROM products WHERE id = {$item['product_id']}"));
        if ($prod['stock'] >= $item['quantity']) {
            mysqli_query($conn, "UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
        }
    }
}

// Update order
if ($payment_status && in_array($payment_status, $valid_payments)) {
    mysqli_query($conn, "UPDATE orders SET status = '$status', payment_status = '$payment_status' WHERE id = $order_id");
} else {
    mysqli_query($conn, "UPDATE orders SET status = '$status' WHERE id = $order_id");
}

 $_SESSION['toast'] = ['type' => 'success', 'message' => 'Order status updated!'];

if ($redirect === 'detail') {
    header("Location: order-detail.php?id=$order_id");
} else {
    $status_filter = $_POST['status_filter'] ?? '';
    $search = $_POST['search'] ?? '';
    $url = "Order.php?page=" . ($_POST['page'] ?? 1);
    if ($status_filter) $url .= "&status=$status_filter";
    if ($search) $url .= "&search=" . urlencode($search);
    header("Location: $url");
}
exit;