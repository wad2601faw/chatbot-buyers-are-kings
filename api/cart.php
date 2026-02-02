<?php
session_start();
include "../config/koneksi.php";

$action = $_POST['action'] ?? 'get';
$product_id = $_POST['product_id'] ?? '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = ['success' => false, 'cart' => $_SESSION['cart']];

if ($action === 'add') {
    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = 0;
    }
    $_SESSION['cart'][$product_id] += $quantity;
    $response['success'] = true;
    $response['message'] = 'Added to cart';
}

elseif ($action === 'remove') {
    unset($_SESSION['cart'][$product_id]);
    $response['success'] = true;
    $response['message'] = 'Removed from cart';
}

elseif ($action === 'update') {
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    $response['success'] = true;
    $response['message'] = 'Cart updated';
}

elseif ($action === 'get') {
    $response['success'] = true;
    $response['cart'] = $_SESSION['cart'];
}

elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
    $response['success'] = true;
    $response['message'] = 'Cart cleared';
}

$response['cart'] = $_SESSION['cart'];
header('Content-Type: application/json');
echo json_encode($response);
?>
