<?php
session_start();
include('../backend/config/db.php');

header('Content-Type: application/json');

/* ===== ADMIN CHECK ===== */
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

 $action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ──────────────────────────
       APPROVE ORDER
    ────────────────────────── */
    case 'approve':
        $id = intval($_POST['id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        mysqli_query($conn, "UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = $id");

        /* payments table bhi update karo agar record hai */
        mysqli_query($conn, "UPDATE payments SET status = 'approved', updated_at = NOW() WHERE order_id = (SELECT order_id FROM orders WHERE id = $id)");

        if (mysqli_affected_rows($conn) > 0) {
            echo json_encode(['success' => true, 'message' => 'Order approved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found or already updated']);
        }
        break;

    /* ──────────────────────────
       REJECT / CANCEL ORDER
    ────────────────────────── */
    case 'reject':
    case 'cancel':
        $id = intval($_POST['id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        mysqli_query($conn, "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = $id");
        mysqli_query($conn, "UPDATE payments SET status = 'rejected', updated_at = NOW() WHERE order_id = (SELECT order_id FROM orders WHERE id = $id)");

        echo json_encode(['success' => true, 'message' => 'Order cancelled']);
        break;

    /* ──────────────────────────
       MARK AS PROCESSING
    ────────────────────────── */
    case 'processing':
        $id = intval($_POST['id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        mysqli_query($conn, "UPDATE orders SET status = 'processing', updated_at = NOW() WHERE id = $id");

        echo json_encode(['success' => true, 'message' => 'Order marked as processing']);
        break;

    /* ──────────────────────────
       MARK AS SHIPPED
    ────────────────────────── */
    case 'shipped':
        $id = intval($_POST['id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        mysqli_query($conn, "UPDATE orders SET status = 'shipped', updated_at = NOW() WHERE id = $id");

        echo json_encode(['success' => true, 'message' => 'Order marked as shipped']);
        break;

    /* ──────────────────────────
       MARK AS DELIVERED
    ────────────────────────── */
    case 'delivered':
        $id = intval($_POST['id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        mysqli_query($conn, "UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = $id");
        mysqli_query($conn, "UPDATE payments SET status = 'completed', updated_at = NOW() WHERE order_id = (SELECT order_id FROM orders WHERE id = $id)");

        echo json_encode(['success' => true, 'message' => 'Order marked as delivered']);
        break;

    /* ──────────────────────────
       DELETE ORDER
    ────────────────────────── */
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        /* Pehle order_id nikalo */
        $oidRes = mysqli_query($conn, "SELECT order_id FROM orders WHERE id = $id");
        if ($oidRow = mysqli_fetch_assoc($oidRes)) {
            $oid = $oidRow['order_id'];

            /* Payment proof image delete karo */
            $payRes = mysqli_query($conn, "SELECT proof_image FROM payments WHERE order_id = '$oid'");
            if ($payRow = mysqli_fetch_assoc($payRes)) {
                $img = $payRow['proof_image'];
                if (!empty($img) && file_exists('../backend/uploads/payments/' . $img)) {
                    unlink('../backend/uploads/payments/' . $img);
                }
            }

            mysqli_query($conn, "DELETE FROM payments WHERE order_id = '$oid'");
            mysqli_query($conn, "DELETE FROM order_items WHERE order_db_id = $id");
            mysqli_query($conn, "DELETE FROM orders WHERE id = $id");

            echo json_encode(['success' => true, 'message' => 'Order deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
        }
        break;

    /* ──────────────────────────
       DEFAULT — UNKNOWN ACTION
    ────────────────────────── */
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

mysqli_close($conn);
?>