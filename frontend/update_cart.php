<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

 $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
 $delta = isset($_POST['delta']) ? intval($_POST['delta']) : 0;

if ($index < 0 || !isset($_SESSION['cart'][$index])) {
    echo json_encode(['success' => false]);
    exit;
}

 $_SESSION['cart'][$index]['quantity'] += $delta;

if ($_SESSION['cart'][$index]['quantity'] <= 0) {
    array_splice($_SESSION['cart'], $index, 1);
} elseif ($_SESSION['cart'][$index]['quantity'] > 10) {
    $_SESSION['cart'][$index]['quantity'] = 10;
}

echo json_encode(['success' => true]);