<?php
session_start();
include "../config/koneksi.php";

// Get cart from session OR dari JavaScript sessionStorage yang di-pass
$cart = $_SESSION['cart'] ?? [];

// Jika dari JavaScript fetch, get dari POST (prioritas lebih tinggi)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['cart']) || isset($_POST['cart_json']))) {
    $cart_data = $_POST['cart_json'] ?? $_POST['cart'];
    $cart = json_decode($cart_data, true) ?? [];
    $_SESSION['cart'] = $cart; // Sync to session
}

$cart_items = [];
$total_price = 0;

if (!empty($cart)) {
    foreach ($cart as $product_id => $quantity) {
        $product_id = intval($product_id);
        $query = "
            SELECT p.id, p.product_name, p.price, s.seller_name, p.image
            FROM products p
            JOIN sellers s ON p.seller_id = s.id
            WHERE p.id = $product_id
        ";
        
        $result = mysqli_query($koneksi, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            $item_total = $product['price'] * $quantity;
            $total_price += $item_total;
            
            // Process image path - add images/ prefix if not present
            $image = $product['image'];
            if (!empty($image) && strpos($image, 'images/') === false) {
                $image = "images/" . $image;
            }
            
            $cart_items[] = [
                'id' => $product_id,
                'name' => $product['product_name'],
                'seller' => $product['seller_name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'subtotal' => $item_total,
                'image' => $image
            ];
        }
    }
}

$tax = $total_price * 0.1;
$grand_total = $total_price + $tax;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Keranjang Belanja - Buyers Chatbot</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/checkout.css">
</head>
<body>

<div class="checkout-container">
    
    <div class="checkout-header">
        <a href="../index.php" class="back-btn">← Kembali</a>
        <h2>🛒 Keranjang Belanja</h2>
    </div>

    <?php if (!empty($cart_items)): ?>
    
    <div class="checkout-content">
        
        <div class="cart-items-section">
            <h3>Item Pesanan (<?= count($cart_items) ?> item)</h3>
            
            <div id="cart-items-list">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" data-product-id="<?= $item['id'] ?>">
                    <img src="../<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="item-img" onerror="this.src='../images/placeholder.jpg'">
                    
                    <div class="item-details">
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="item-seller"><?= htmlspecialchars($item['seller']) ?></div>
                        <div class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                    </div>
                    
                    <div class="item-quantity">
                        <button class="qty-btn minus" onclick="updateQty(<?= $item['id'] ?>, <?= $item['quantity'] ?> - 1)">−</button>
                        <input type="number" value="<?= $item['quantity'] ?>" readonly>
                        <button class="qty-btn plus" onclick="updateQty(<?= $item['id'] ?>, <?= $item['quantity'] ?> + 1)">+</button>
                    </div>
                    
                    <div class="item-subtotal">
                        <div>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                        <button class="remove-btn" onclick="removeItem(<?= $item['id'] ?>)">✕ Hapus</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="checkout-summary">
            <h3>Ringkasan Pesanan</h3>
            
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">Rp <?= number_format($total_price, 0, ',', '.') ?></span>
            </div>
            
            <div class="summary-row">
                <span>Pajak (10%):</span>
                <span id="tax">Rp <?= number_format($tax, 0, ',', '.') ?></span>
            </div>
            
            <div class="summary-divider"></div>
            
            <div class="summary-total">
                <span>Total:</span>
                <span id="total">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
            </div>
            
            <div class="order-form">
                <h4>Data Pemesan</h4>
                <input type="text" id="customer-name" placeholder="Nama Lengkap" required>
                <input type="email" id="customer-email" placeholder="Email" required>
                <input type="tel" id="customer-phone" placeholder="Nomor HP" required>
                <textarea id="customer-notes" placeholder="Catatan Pesanan (opsional)" rows="3"></textarea>
                
                <button id="order-btn" class="btn-order" onclick="submitOrder()">
                    ✓ Pesan Sekarang
                </button>
            </div>
        </div>

    </div>

    <?php else: ?>
    
    <div class="empty-cart">
        <div class="empty-icon">🛒</div>
        <h3>Keranjang Belanja Kosong</h3>
        <p>Anda belum memilih produk apapun</p>
        <a href="../index.php" class="btn-back">← Kembali ke Chat</a>
    </div>

    <?php endif; ?>

</div>

<script>
    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `notification-toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.5s ease forwards';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }
</script>
<script src="../assets/js/checkout.js"></script>

</body>
</html>
