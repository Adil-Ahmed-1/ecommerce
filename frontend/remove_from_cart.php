<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

 $index = isset($_POST['index']) ? intval($_POST['index']) : -1;

if ($index >= 0 && isset($_SESSION['cart'][$index])) {
    array_splice($_SESSION['cart'], $index, 1);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}