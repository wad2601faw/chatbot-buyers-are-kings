<?php
session_start();
include "../config/koneksi.php";

$user_id = $_SESSION['user_id'] ?? '';

$orders_query = "SELECT * FROM orders WHERE customer_email LIKE CONCAT('%', ?, '%') ORDER BY created_at DESC LIMIT 20";
$orders = [];

if (!empty($user_id)) {
    $stmt = $koneksi->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT 20");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($order = $result->fetch_assoc()) {
            $orders[] = $order;
        }
        $stmt->close();
    }
}

$status_colors = [
    'pending' => '#ffc107',
    'confirmed' => '#17a2b8',
    'shipped' => '#007bff',
    'completed' => '#28a745',
    'cancelled' => '#dc3545'
];

$status_labels = [
    'pending' => '⏳ Pending',
    'confirmed' => '✅ Dikonfirmasi',
    'shipped' => '🚚 Dikirim',
    'completed' => '✓ Selesai',
    'cancelled' => '❌ Dibatalkan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Buyers Chatbot</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/checkout.css">
    <style>
        body {
            background: linear-gradient(120deg, #4facfe, #00f2fe);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .history-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .history-header {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .history-header h1 {
            margin: 0;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .history-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .orders-list {
            padding: 30px;
        }
        
        .order-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #4facfe;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-number {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .order-date {
            color: #999;
            font-size: 12px;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .order-info {
            font-size: 14px;
            color: #666;
        }
        
        .order-info strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }
        
        .order-total {
            font-size: 24px;
            font-weight: 700;
            color: #4facfe;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #333;
            margin: 0 0 10px;
        }
        
        .empty-state p {
            font-size: 14px;
            margin: 0 0 20px;
        }
        
        @media (max-width: 600px) {
            .history-container {
                border-radius: 0;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="history-container">
        <div class="history-header">
            <h1>📦 Order Saya</h1>
            <p>Lihat riwayat dan status pesanan Anda</p>
            <a href="../index.php" class="back-link">← Kembali ke Chat</a>
        </div>
        
        <div class="orders-list">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>Belum ada pesanan</h3>
                    <p>Anda belum melakukan pesanan apapun.</p>
                    <a href="../index.php" style="display: inline-block; padding: 12px 30px; background: #4facfe; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                        Mulai Belanja
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card" onclick="showOrderDetail('<?= $order['order_number'] ?>')">
                        <div class="order-header">
                            <div class="order-number">#<?= htmlspecialchars($order['order_number']) ?></div>
                            <div class="order-status" style="background: <?= $status_colors[$order['status']] ?>">
                                <?= $status_labels[$order['status']] ?>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="order-info">
                                <strong>Tanggal:</strong>
                                <?= date('d F Y, H:i', strtotime($order['created_at'])) ?>
                            </div>
                            <div class="order-info">
                                <strong>Pemesan:</strong>
                                <?= htmlspecialchars($order['customer_name']) ?>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #e0e0e0;">
                            <strong>Total:</strong>
                            <span class="order-total">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showOrderDetail(orderNumber) {
            const items = JSON.parse('<?= json_encode($orders) ?>').find(o => o.order_number === orderNumber);
            if (items) {
                const orderItems = JSON.parse(items.items_json);
                let detailHTML = `
                    <div style="max-width: 600px; margin: auto; padding: 20px;">
                        <h2 style="color: #333; margin-top: 0;">Order #${orderNumber}</h2>
                        <p style="color: #666; margin-bottom: 20px;">Status: <strong style="color: #4facfe;">${items.status.toUpperCase()}</strong></p>
                        <div style="background: #f9f9f9; padding: 20px; border-radius: 10px;">
                `;
                
                orderItems.forEach((item, index) => {
                    detailHTML += `
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e0e0e0; ${index === orderItems.length - 1 ? 'border-bottom: none;' : ''}">
                            <span style="font-weight: 600; color: #333;">${item.product_name}</span>
                            <div style="text-align: right;">
                                <div style="color: #666;">${item.quantity} x Rp ${item.price.toLocaleString('id-ID')}</div>
                                <div style="font-weight: 700; color: #4facfe;">Rp ${item.subtotal.toLocaleString('id-ID')}</div>
                            </div>
                        </div>
                    `;
                });
                
                detailHTML += `
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #4facfe;">
                                <strong>Total:</strong> <span style="font-size: 24px; font-weight: 700; color: #4facfe;">Rp ${items.total_price.toLocaleString('id-ID')}</span>
                            </div>
                        </div>
                    </div>
                `;
                
                alert(detailHTML);
            }
        }
    </script>
</body>
</html>
