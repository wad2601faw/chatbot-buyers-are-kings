<?php
include "../config/koneksi.php";
include "../config/auth.php";

requireAdminLogin();

$admin = getCurrentAdmin();

$today = date('Y-m-d');
$month = date('Y-m');

$stats = [
    'total_orders' => 0,
    'today_orders' => 0,
    'month_orders' => 0,
    'total_revenue' => 0,
    'today_revenue' => 0,
    'month_revenue' => 0,
    'total_products' => 0,
    'total_customers' => 0
];

$total_orders_query = "SELECT COUNT(*) as count, COALESCE(SUM(total_price), 0) as revenue FROM orders";
$result = mysqli_query($koneksi, $total_orders_query);
$row = mysqli_fetch_assoc($result);
$stats['total_orders'] = $row['count'];
$stats['total_revenue'] = $row['revenue'];

$today_orders_query = "SELECT COUNT(*) as count, COALESCE(SUM(total_price), 0) as revenue FROM orders WHERE DATE(created_at) = '$today'";
$result = mysqli_query($koneksi, $today_orders_query);
$row = mysqli_fetch_assoc($result);
$stats['today_orders'] = $row['count'];
$stats['today_revenue'] = $row['revenue'];

$month_orders_query = "SELECT COUNT(*) as count, COALESCE(SUM(total_price), 0) as revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'";
$result = mysqli_query($koneksi, $month_orders_query);
$row = mysqli_fetch_assoc($result);
$stats['month_orders'] = $row['count'];
$stats['month_revenue'] = $row['revenue'];

$products_query = "SELECT COUNT(*) as count FROM products";
$result = mysqli_query($koneksi, $products_query);
$row = mysqli_fetch_assoc($result);
$stats['total_products'] = $row['count'];

$customers_query = "SELECT COUNT(DISTINCT customer_email) as count FROM orders";
$result = mysqli_query($koneksi, $customers_query);
$row = mysqli_fetch_assoc($result);
$stats['total_customers'] = $row['count'];

$recent_orders_query = "SELECT o.*, COUNT(osh.id) as status_changes 
                      FROM orders o 
                      LEFT JOIN order_status_history osh ON o.id = osh.order_id 
                      GROUP BY o.id 
                      ORDER BY o.created_at DESC 
                      LIMIT 5";
$recent_orders = mysqli_query($koneksi, $recent_orders_query);

$top_products_query = "SELECT p.product_name, p.price, 
                      (SELECT COUNT(*) FROM orders WHERE items_json LIKE CONCAT('\"', p.id, '\"')) as order_count 
                      FROM products p 
                      ORDER BY order_count DESC, p.price DESC 
                      LIMIT 5";
$top_products = mysqli_query($koneksi, $top_products_query);

$orders_by_status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$orders_by_status = mysqli_query($koneksi, $orders_by_status_query);

$status_colors = [
    'pending' => '#ffc107',
    'confirmed' => '#17a2b8',
    'shipped' => '#007bff',
    'completed' => '#28a745',
    'cancelled' => '#dc3545'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
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
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        
        .sidebar-header small {
            font-size: 12px;
            opacity: 0.8;
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
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
        }
        
        .admin-info span {
            color: #666;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 15px;
            color: #667eea;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-sub {
            font-size: 12px;
            color: #666;
        }
        
        .stat-sub.highlight {
            color: #28a745;
            font-weight: 600;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            text-align: left;
            padding: 12px;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            background: #f9f9f9;
        }
        
        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .orders-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .top-product-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .top-product-item:last-child {
            border-bottom: none;
        }
        
        .product-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-info h4 {
            margin: 0 0 5px;
            color: #333;
            font-size: 14px;
        }
        
        .product-info p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        
        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c82333;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                <a href="index.php" class="nav-item active">
                    📊 Dashboard
                </a>
                <a href="products.php" class="nav-item">
                    📦 Products
                </a>
                <a href="orders.php" class="nav-item">
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
                <div style="margin-bottom: 10px; font-size: 12px; opacity: 0.8;">
                    <?= htmlspecialchars($admin['full_name']) ?>
                </div>
                <a href="logout.php" class="btn-logout" style="text-decoration: none; display: block; text-align: center;">
                    🚪 Logout
                </a>
            </div>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>📊 Dashboard Overview</h1>
                <div class="admin-info">
                    <span>👤 <?= htmlspecialchars($admin['full_name']) ?></span>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-sub">
                        Today: <span class="highlight"><?= $stats['today_orders'] ?></span> | 
                        Month: <span class="highlight"><?= $stats['month_orders'] ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="stat-value">Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></div>
                    <div class="stat-sub">
                        Today: <span class="highlight">Rp <?= number_format($stats['today_revenue'], 0, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
                    <div class="stat-sub">
                        Available in catalog
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Customers</h3>
                    <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
                    <div class="stat-sub">
                        Unique customers
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>📦 Recent Orders</h3>
                    </div>
                    <div class="card-body">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td><a href="orders.php?view=<?= $order['id'] ?>" style="color: #667eea; text-decoration: none;"><?= $order['order_number'] ?></a></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                        <td><span class="status-badge" style="background: <?= $status_colors[$order['status']] ?>; color: white;"><?= $order['status'] ?></span></td>
                                        <td><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>🏆 Top Products</h3>
                    </div>
                    <div class="card-body">
                        <?php $rank = 1; ?>
                        <?php while ($product = mysqli_fetch_assoc($top_products)): ?>
                            <div class="top-product-item">
                                <div class="product-rank"><?= $rank++ ?></div>
                                <div class="product-info">
                                    <h4><?= htmlspecialchars($product['product_name']) ?></h4>
                                    <p>Rp <?= number_format($product['price'], 0, ',', '.') ?> | <?= $product['order_count'] ?> orders</p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
