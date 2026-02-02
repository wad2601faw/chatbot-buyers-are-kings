<?php
session_start();
include "../config/koneksi.php";

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

if ($action === 'create_order') {
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_notes = $_POST['customer_notes'] ?? '';
    
    // Validation
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone)) {
        $response['message'] = 'Data pelanggan tidak lengkap';
        echo json_encode($response);
        exit;
    }

    // Get cart
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        $response['message'] = 'Keranjang belanja kosong';
        echo json_encode($response);
        exit;
    }

    // Calculate total
    $total_price = 0;
    $order_items = [];

    foreach ($cart as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);
        
        $query = "
            SELECT p.id, p.product_name, p.price
            FROM products p
            WHERE p.id = $product_id
        ";
        
        $result = mysqli_query($koneksi, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            $item_total = $product['price'] * $quantity;
            $total_price += $item_total;
            
            $order_items[] = [
                'product_id' => $product_id,
                'product_name' => $product['product_name'],
                'quantity' => $quantity,
                'price' => $product['price'],
                'subtotal' => $item_total
            ];
        }
    }

    if (empty($order_items)) {
        $response['message'] = 'Produk tidak ditemukan';
        echo json_encode($response);
        exit;
    }

    // Create orders table jika belum ada
    $create_table = "
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            customer_email VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20) NOT NULL,
            customer_notes TEXT,
            items_json JSON NOT NULL,
            total_price INT NOT NULL,
            status ENUM('pending', 'confirmed', 'shipped', 'completed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    mysqli_query($koneksi, $create_table);

    // Generate order number
    $order_number = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);

    // Escape data
    $name_esc = mysqli_real_escape_string($koneksi, $customer_name);
    $email_esc = mysqli_real_escape_string($koneksi, $customer_email);
    $phone_esc = mysqli_real_escape_string($koneksi, $customer_phone);
    $notes_esc = mysqli_real_escape_string($koneksi, $customer_notes);
    $items_json = mysqli_real_escape_string($koneksi, json_encode($order_items));

    // Insert order
    $insert_query = "
        INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_notes, items_json, total_price)
        VALUES ('$order_number', '$name_esc', '$email_esc', '$phone_esc', '$notes_esc', '$items_json', '$total_price')
    ";

    if (mysqli_query($koneksi, $insert_query)) {
        // Clear cart
        $_SESSION['cart'] = [];
        
        $response['success'] = true;
        $response['message'] = 'Order berhasil dibuat';
        $response['order_id'] = $order_number;
        $response['total'] = $total_price;
    } else {
        $response['message'] = 'Gagal menyimpan order: ' . mysqli_error($koneksi);
    }
}

echo json_encode($response);
?>
