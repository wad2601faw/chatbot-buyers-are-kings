<?php
include "../config/koneksi.php";
include "../config/auth.php";

requireAdminLogin();

$admin = getCurrentAdmin();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

$sellers_query = "SELECT * FROM sellers ORDER BY seller_name";
$sellers_result = mysqli_query($koneksi, $sellers_query);
$sellers = [];
while ($seller = mysqli_fetch_assoc($sellers_result)) {
    $sellers[$seller['id']] = $seller['seller_name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $product_name = trim($_POST['product_name']);
        $category = $_POST['category'];
        $price = intval($_POST['price']);
        $seller_id = intval($_POST['seller_id']);
        $stock = intval($_POST['stock']);
        $serving_size = intval($_POST['serving_size']);
        $preparation_time = intval($_POST['preparation_time']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $image = $_POST['existing_image'] ?? '';
        
        if (empty($product_name)) {
            $error = 'Product name is required';
        } elseif ($price <= 0) {
            $error = 'Price must be greater than 0';
        } else {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../images/';
                $filename = time() . '_' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($_FILES['image']['type'], $allowed_types)) {
                    $error = 'Invalid image type. Only JPG, PNG, WEBP, and GIF are allowed.';
                } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $error = 'Failed to upload image';
                } else {
                    $image = 'images/' . $filename;
                }
            }
            
            if (empty($error)) {
                $image = mysqli_real_escape_string($koneksi, $image);
                $product_name = mysqli_real_escape_string($koneksi, $product_name);
                
                if ($action === 'add') {
                    $insert_query = "INSERT INTO products (product_name, category, price, seller_id, is_available, image, stock, serving_size, preparation_time) 
                                   VALUES ('$product_name', '$category', $price, $seller_id, $is_available, '$image', $stock, $serving_size, $preparation_time)";
                    if (mysqli_query($koneksi, $insert_query)) {
                        $success = 'Product added successfully!';
                        header('Location: products.php');
                        exit();
                    } else {
                        $error = 'Failed to add product: ' . mysqli_error($koneksi);
                    }
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $update_query = "UPDATE products SET 
                                     product_name='$product_name', 
                                     category='$category', 
                                     price=$price, 
                                     seller_id=$seller_id, 
                                     is_available=$is_available, 
                                     image='$image',
                                     stock=$stock,
                                     serving_size=$serving_size,
                                     preparation_time=$preparation_time
                                     WHERE id=$id";
                    if (mysqli_query($koneksi, $update_query)) {
                        $success = 'Product updated successfully!';
                        header('Location: products.php');
                        exit();
                    } else {
                        $error = 'Failed to update product: ' . mysqli_error($koneksi);
                    }
                }
            }
        }
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete_query = "DELETE FROM products WHERE id=$id";
    if (mysqli_query($koneksi, $delete_query)) {
        $success = 'Product deleted successfully!';
        header('Location: products.php');
        exit();
    } else {
        $error = 'Failed to delete product: ' . mysqli_error($koneksi);
    }
}

$edit_product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $product_query = "SELECT * FROM products WHERE id=$id";
    $result = mysqli_query($koneksi, $product_query);
    $edit_product = mysqli_fetch_assoc($result);
}

$products_query = "SELECT p.*, s.seller_name FROM products p JOIN sellers s ON p.seller_id = s.id ORDER BY p.id DESC";
$products_result = mysqli_query($koneksi, $products_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Admin Panel</title>
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: transform 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 20px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th {
            text-align: left;
            padding: 15px;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            background: #f9f9f9;
            border-bottom: 2px solid #eee;
        }
        
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            vertical-align: middle;
        }
        
        .products-table tr:hover {
            background: #f9f9f9;
        }
        
        .product-thumb {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #eee;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 5px;
            transition: all 0.2s ease;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-available {
            background: #28a745;
            color: white;
        }
        
        .status-unavailable {
            background: #dc3545;
            color: white;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .form-row {
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
                <a href="index.php" class="nav-item">
                    📊 Dashboard
                </a>
                <a href="products.php" class="nav-item active">
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
                <a href="../index.php" class="nav-item" style="background: rgba(255,255,255,0.2);">
                    🌐 View Website
                </a>
                <a href="logout.php" class="nav-item" style="margin-top: 10px;">
                    🚪 Logout
                </a>
            </div>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>📦 Products Management</h1>
                <a href="?action=add" class="btn-primary">➕ Add Product</a>
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
            
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <div class="card">
                    <h2 style="margin: 0 0 25px; color: #333;">
                        <?= $action === 'edit' ? 'Edit Product' : 'Add New Product' ?>
                    </h2>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_name">Product Name *</label>
                                <input type="text" id="product_name" name="product_name" 
                                       value="<?= htmlspecialchars($edit_product['product_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category *</label>
                                <select id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="rice" <?= (isset($edit_product) && $edit_product['category'] === 'rice') ? 'selected' : '' ?>>Rice</option>
                                    <option value="sweet" <?= (isset($edit_product) && $edit_product['category'] === 'sweet') ? 'selected' : '' ?>>Sweet</option>
                                    <option value="drink" <?= (isset($edit_product) && $edit_product['category'] === 'drink') ? 'selected' : '' ?>>Drink</option>
                                    <option value="snack" <?= (isset($edit_product) && $edit_product['category'] === 'snack') ? 'selected' : '' ?>>Snack</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price (Rp) *</label>
                                <input type="number" id="price" name="price" 
                                       value="<?= $edit_product['price'] ?? '' ?>" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="seller_id">Seller *</label>
                                <select id="seller_id" name="seller_id" required>
                                    <option value="">Select Seller</option>
                                    <?php foreach ($sellers as $id => $name): ?>
                                        <option value="<?= $id ?>" 
                                            <?= (isset($edit_product) && $edit_product['seller_id'] == $id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock">Stock</label>
                                <input type="number" id="stock" name="stock" 
                                       value="<?= $edit_product['stock'] ?? 10 ?>" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="serving_size">Serving Size</label>
                                <input type="number" id="serving_size" name="serving_size" 
                                       value="<?= $edit_product['serving_size'] ?? 1 ?>" min="1">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="preparation_time">Preparation Time (minutes)</label>
                            <input type="number" id="preparation_time" name="preparation_time" 
                                   value="<?= $edit_product['preparation_time'] ?? 15 ?>" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Product Image</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <?php if (isset($edit_product) && !empty($edit_product['image'])): ?>
                                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_product['image']) ?>">
                                <small>Current: <img src="../<?= htmlspecialchars($edit_product['image']) ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; vertical-align: middle;"></small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_available" name="is_available" 
                                       <?= (isset($edit_product) && $edit_product['is_available']) ? 'checked' : '' ?>>
                                <label for="is_available">Product Available</label>
                            </div>
                        </div>
                        
                        <div style="text-align: right;">
                            <a href="products.php" style="padding: 12px 25px; border: none; border-radius: 8px; background: #6c757d; color: white; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block;">Cancel</a>
                            <button type="submit" class="btn-primary">
                                <?= $action === 'edit' ? 'Update Product' : 'Add Product' ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                <tr>
                                    <td>
                                        <img src="../<?= htmlspecialchars($product['image'] ?: 'images/placeholder.jpg') ?>" 
                                             class="product-thumb" 
                                             onerror="this.src='../images/placeholder.jpg'">
                                    </td>
                                    <td><strong><?= htmlspecialchars($product['product_name']) ?></strong></td>
                                    <td>
                                        <span style="background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; font-weight: 600;">
                                            <?= htmlspecialchars($product['category']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($product['seller_name']) ?></td>
                                    <td>Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                                    <td><?= number_format($product['stock']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $product['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                                            <?= $product['is_available'] ? 'Available' : 'Unavailable' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?action=edit&id=<?= $product['id'] ?>" class="action-btn btn-edit">Edit</a>
                                        <a href="?action=delete&id=<?= $product['id'] ?>" 
                                           class="action-btn btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
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
