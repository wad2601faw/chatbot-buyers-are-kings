<?php
include "../config/koneksi.php";
include "../config/auth.php";

requireAdminLogin();

$admin = getCurrentAdmin();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    $valid_statuses = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $error = 'Invalid status';
    } else {
        $notes = mysqli_real_escape_string($koneksi, $notes);
        
        $update_query = "UPDATE orders SET status='$status' WHERE id=$order_id";
        if (mysqli_query($koneksi, $update_query)) {
            $insert_history = "INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                              VALUES ($order_id, '$status', '$notes', {$admin['id']})";
            if (mysqli_query($koneksi, $insert_history)) {
                $success = 'Order status updated successfully!';
                header('Location: orders.php?view=' . $order_id);
                exit();
            } else {
                $error = 'Failed to save status history: ' . mysqli_error($koneksi);
            }
        } else {
            $error = 'Failed to update order: ' . mysqli_error($koneksi);
        }
    }
}

$status_filter = $_GET['status'] ?? '';
$where_clause = '';
if ($status_filter && in_array($status_filter, ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'])) {
    $where_clause = "WHERE status = '$status_filter'";
}

$orders_query = "SELECT o.*, 
                      (SELECT COUNT(*) FROM order_status_history WHERE order_id = o.id) as history_count
                      FROM orders o 
                      $where_clause 
                      ORDER BY o.created_at DESC 
                      LIMIT 50";
$orders_result = mysqli_query($koneksi, $orders_query);

$status_colors = [
    'pending' => '#ffc107',
    'confirmed' => '#17a2b8',
    'shipped' => '#007bff',
    'completed' => '#28a745',
    'cancelled' => '#dc3545'
];

$status_icons = [
    'pending' => '⏳',
    'confirmed' => '✅',
    'shipped' => '🚚',
    'completed' => '✓',
    'cancelled' => '❌'
];

$view_order = null;
if ($action === 'view' && isset($_GET['view'])) {
    $order_id = intval($_GET['view']);
    $order_query = "SELECT * FROM orders WHERE id=$order_id";
    $order_result = mysqli_query($koneksi, $order_query);
    $view_order = mysqli_fetch_assoc($order_result);
    
    $items = json_decode($view_order['items_json'], true) ?? [];
    
    $history_query = "SELECT osh.*, a.username as admin_name 
                     FROM order_status_history osh 
                     LEFT JOIN admins a ON osh.updated_by = a.id 
                     WHERE osh.order_id=$order_id 
                     ORDER BY osh.created_at DESC";
    $history_result = mysqli_query($koneksi, $history_query);
    $history = [];
    while ($h = mysqli_fetch_assoc($history_result)) {
        $history[] = $h;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        body {
            background: #f0f2f5;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.2);
            padding-left: 25px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .top-bar h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .filter-tab:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            text-align: left;
            padding: 15px;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            background: #f9f9f9;
            border-bottom: 2px solid #eee;
        }
        
        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .orders-table tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .order-detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .order-items-table th {
            text-align: left;
            padding: 12px;
            background: #f9f9f9;
            font-weight: 600;
            font-size: 12px;
        }
        
        .order-items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -35px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -26px;
            top: 20px;
            width: 2px;
            height: calc(100% + 10px);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .timeline-content {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .timeline-status {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .timeline-time {
            color: #999;
            font-size: 11px;
        }
        
        .timeline-notes {
            margin-top: 8px;
            color: #666;
            font-size: 13px;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        @media (max-width: 1024px) {
            .order-detail-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>👑 Admin Panel</h2>
                <small>Buyers are KINGs</small>
            </div>
            <nav>
                <a href="index.php" class="nav-item">
                    📊 Dashboard
                </a>
                <a href="products.php" class="nav-item">
                    📦 Products
                </a>
                <a href="orders.php" class="nav-item active">
                    🛒 Orders
                </a>
                <a href="#" class="nav-item">
                    👥 Customers
                </a>
                <a href="#" class="nav-item">
                    ⭐ Reviews
                </a>
                <a href="#" class="nav-item">
                    📈 Analytics
                </a>
                <a href="#" class="nav-item">
                    ⚙️ Settings
                </a>
            </nav>
            <div style="margin-top: auto; padding: 20px;">
                <a href="../index.php" class="nav-item" style="background: rgba(255,255,255,0.2);">
                    🌐 View Website
                </a>
                <a href="logout.php" class="nav-item" style="margin-top: 10px;">
                    🚪 Logout
                </a>
            </div>
        </aside>
        
        <main class="main-content">
            <?php if ($view_order): ?>
                <div class="top-bar">
                    <a href="orders.php" class="btn-back">← Back to Orders</a>
                    <h1>Order #<?= $view_order['order_number'] ?></h1>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✓ <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <div class="order-detail-grid">
                    <div>
                        <div class="card" style="padding: 25px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 15px; color: #333;">Order Information</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label style="color: #666; font-size: 12px; display: block; margin-bottom: 5px;">Customer</label>
                                    <div style="font-weight: 600; color: #333;"><?= htmlspecialchars($view_order['customer_name']) ?></div>
                                    <div style="color: #999; font-size: 13px;"><?= htmlspecialchars($view_order['customer_email']) ?></div>
                                    <div style="color: #999; font-size: 13px;"><?= htmlspecialchars($view_order['customer_phone']) ?></div>
                                </div>
                                <div>
                                    <label style="color: #666; font-size: 12px; display: block; margin-bottom: 5px;">Order Details</label>
                                    <div><strong>Status:</strong> <span class="status-badge" style="background: <?= $status_colors[$view_order['status']] ?>; color: white;"><?= $status_icons[$view_order['status']] ?> <?= ucfirst($view_order['status']) ?></span></div>
                                    <div><strong>Order Date:</strong> <?= date('d M Y, H:i', strtotime($view_order['created_at'])) ?></div>
                                    <div style="margin-top: 5px;"><strong>Total:</strong> <span style="font-size: 18px; font-weight: 700; color: #667eea;">Rp <?= number_format($view_order['total_price'], 0, ',', '.') ?></span></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($view_order['customer_notes'])): ?>
                                <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <strong style="color: #856404;">Customer Notes:</strong>
                                    <p style="margin: 5px 0 0; color: #856404;"><?= htmlspecialchars($view_order['customer_notes']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card" style="padding: 25px;">
                            <h3 style="margin: 0 0 20px; color: #333;">Order Items</h3>
                            <table class="order-items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background: #f9f9f9; font-weight: 700;">
                                        <td colspan="3" style="text-align: right; padding-right: 20px;">Total:</td>
                                        <td style="color: #667eea;">Rp <?= number_format($view_order['total_price'], 0, ',', '.') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div>
                        <div class="card" style="padding: 25px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 20px; color: #333;">Update Status</h3>
                            <form method="POST" action="?action=update_status">
                                <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
                                
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label for="status" style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">Status</label>
                                    <select id="status" name="status" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                        <?php foreach ($status_icons as $status => $icon): ?>
                                            <option value="<?= $status ?>" 
                                                <?= $view_order['status'] === $status ? 'selected' : '' ?>>
                                                <?= $icon ?> <?= ucfirst($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="notes" style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">Notes</label>
                                    <textarea id="notes" name="notes" rows="4" placeholder="Add notes about this status update..." 
                                              style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; resize: vertical;"></textarea>
                                </div>
                                
                                <button type="submit" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    Update Status
                                </button>
                            </form>
                        </div>
                        
                        <div class="card" style="padding: 25px;">
                            <h3 style="margin: 0 0 20px; color: #333;">Status History</h3>
                            <div class="timeline">
                                <?php foreach ($history as $h): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <div class="timeline-status"><?= $status_icons[$h['status']] ?> <?= ucfirst($h['status']) ?></div>
                                            <div class="timeline-time"><?= date('d M Y, H:i', strtotime($h['created_at'])) ?></div>
                                            <?php if ($h['admin_name']): ?>
                                                <div style="color: #999; font-size: 11px;">by <?= htmlspecialchars($h['admin_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($h['notes']): ?>
                                                <div class="timeline-notes"><?= htmlspecialchars($h['notes']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="top-bar">
                    <h1>🛒 Orders Management</h1>
                </div>
                
                <div class="filter-tabs">
                    <a href="orders.php" class="filter-tab <?= empty($status_filter) ? 'active' : '' ?>">All</a>
                    <a href="?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">⏳ Pending</a>
                    <a href="?status=confirmed" class="filter-tab <?= $status_filter === 'confirmed' ? 'active' : '' ?>">✅ Confirmed</a>
                    <a href="?status=shipped" class="filter-tab <?= $status_filter === 'shipped' ? 'active' : '' ?>">🚚 Shipped</a>
                    <a href="?status=completed" class="filter-tab <?= $status_filter === 'completed' ? 'active' : '' ?>">✓ Completed</a>
                    <a href="?status=cancelled" class="filter-tab <?= $status_filter === 'cancelled' ? 'active' : '' ?>">❌ Cancelled</a>
                </div>
                
                <div class="card" style="overflow-x: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                <tr>
                                    <td><a href="?view=<?= $order['id'] ?>" style="color: #667eea; text-decoration: none; font-weight: 600;"><?= $order['order_number'] ?></a></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <div style="color: #999; font-size: 12px;"><?= htmlspecialchars($order['customer_email']) ?></div>
                                    </td>
                                    <td style="font-weight: 700;">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="status-badge" style="background: <?= $status_colors[$order['status']] ?>; color: white;">
                                            <?= $status_icons[$order['status']] ?> <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="?view=<?= $order['id'] ?>" 
                                           style="background: #007bff; color: white; padding: 6px 12px; border: none; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none;">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
