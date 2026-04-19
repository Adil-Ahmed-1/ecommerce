// includes/functions.php

<?php

// ============ SESSION START ============
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============ REDIRECT ============
function redirect($url)
{
    header("Location: $url");
    exit();
}

// ============ FLASH MESSAGE ============
function setFlash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type)
{
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function showFlash()
{
    $success = getFlash('success');
    $error   = getFlash('error');

    if ($success) {
        echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
    }
    if ($error) {
        echo '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>';
    }
}

// ============ CHECK LOGIN ============
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        setFlash('error', 'Pehle login karein!');
        redirect('/login.php');
    }
}

function userId()
{
    return $_SESSION['user_id'] ?? null;
}

function userName()
{
    return $_SESSION['user_name'] ?? 'Guest';
}

// ============ FORMAT PRICE ============
function formatPrice($amount)
{
    return 'Rs. ' . number_format($amount, 2);
}

// ============ ESCAPE OUTPUT ============
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ============ STATUS COLORS ============
function statusColor($status)
{
    $colors = [
        'pending'    => '#f59e0b',
        'confirmed'  => '#3b82f6',
        'processing' => '#8b5cf6',
        'shipped'    => '#6366f1',
        'delivered'  => '#10b981',
        'cancelled'  => '#ef4444',
        'returned'   => '#f97316',
    ];
    return $colors[$status] ?? '#6b7280';
}

function paymentStatusColor($status)
{
    $colors = [
        'pending'  => '#f59e0b',
        'paid'     => '#10b981',
        'failed'   => '#ef4444',
        'refunded' => '#f97316',
    ];
    return $colors[$status] ?? '#6b7280';
}

// ============ PAYMENT METHOD LABEL ============
function paymentMethodLabel($method)
{
    $labels = [
        'cod'           => 'Cash on Delivery',
        'card'          => 'Credit/Debit Card',
        'jazzcash'      => 'JazzCash',
        'easypaisa'     => 'EasyPaisa',
        'bank_transfer' => 'Bank Transfer',
    ];
    return $labels[$method] ?? $method;
}