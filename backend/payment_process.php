<?php
// process/update-payment.php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard.php");
    exit;
}

 $payment_id = (int)($_POST['payment_id'] ?? 0);
 $status = $_POST['status'] ?? '';
 $admin_note = trim($_POST['admin_note'] ?? '');

if ($payment_id <= 0 || !in_array($status, ['approved', 'rejected'])) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid request'];
    header("Location: ../payments/view.php");
    exit;
}

// Get payment details
 $pay_res = mysqli_query($conn, "SELECT * FROM payments WHERE id = $payment_id");
if (mysqli_num_rows($pay_res) === 0) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Payment not found'];
    header("Location: ../payments/view.php");
    exit;
}
 $pay = mysqli_fetch_assoc($pay_res);

// Check if already processed
if ($pay['status'] !== 'pending') {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Payment already ' . $pay['status']];
    header("Location: ../payments/detail.php?id=$payment_id");
    exit;
}

mysqli_begin_autocommit($conn, false);

try {
    // Update payment status
    mysqli_query($conn, "UPDATE payments SET status = '$status', updated_at = NOW() WHERE id = $payment_id");

    // If linked to an order
    if ($pay['order_id']) {
        if ($status === 'approved') {
            // Mark order as paid
            mysqli_query($conn, "
                UPDATE orders 
                SET payment_status = 'paid', 
                    transaction_id = '" . mysqli_real_escape_string($conn, $pay['transaction_id'] ?? '') . "',
                    status = CASE 
                        WHEN status = 'pending' THEN 'confirmed' 
                        ELSE status 
                    END,
                    updated_at = NOW()
                WHERE id = {$pay['order_id']}
            ");
        } elseif ($status === 'rejected') {
            // Keep order payment_status as pending or failed
            mysqli_query($conn, "
                UPDATE orders 
                SET payment_status = 'failed',
                    updated_at = NOW()
                WHERE id = {$pay['order_id']}
            ");
        }
    }

    mysqli_commit($conn);
    mysqli_begin_autocommit($conn, true);

    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => $status === 'approved' 
            ? 'Payment approved! Order #' . ($pay['order_number'] ?? '') . ' marked as paid.' 
            : 'Payment rejected successfully.'
    ];

} catch (Exception $e) {
    mysqli_rollback($conn);
    mysqli_begin_autocommit($conn, true);
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()];
}

header("Location: ../payments/detail.php?id=$payment_id");
exit;