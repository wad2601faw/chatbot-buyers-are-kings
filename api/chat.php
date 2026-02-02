<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../config/koneksi.php";

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid('user_');
}

$msg = $_POST['message'] ?? '';
$msg_lower = strtolower(trim($msg));


// ================= INTENT DETECTION CONFIG (ENHANCED) =================
$synonyms = [
    'rice' => ['nasi', 'rice', 'beras', 'makan siang', 'lauk'],
    'sweet' => ['manis', 'sweet', 'dessert', 'pencuci mulut', 'coklat', 'gula', 'martabak'],
    'drink' => ['minum', 'drink', 'beverage', 'jus', 'kopi', 'teh', 'boba'],
    'snack' => ['snack', 'cemilan', 'keripik', 'jajanan', 'ringan'],
    'cheap' => ['murah', 'cheap', 'cheapest', 'termurah', 'terjangkau', 'hemat', 'low price'],
    'expensive' => ['mahal', 'expensive', 'premium', 'termahal', 'sultan', 'high end'],
    'full_list' => ['all', 'full', 'list', 'semua', 'menu', 'daftar', 'everything'],
    'next' => ['next', 'lanjut', 'berikutnya', 'page selanjutnya'],
    'prev' => ['prev', 'previous', 'sebelumnya', 'bali', 'back'],
    'search' => ['cari', 'search', 'find', 'look for', 'mencari', 'pencarian']
];

function detectIntent($message, $intent_key, $synonym_map) {
    if (!isset($synonym_map[$intent_key])) return false;
    foreach ($synonym_map[$intent_key] as $keyword) {
        if (strpos($message, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// ================= HANDLE PAGINATION =================
$saved_category = $_SESSION['current_category'] ?? '';
$saved_sort = $_SESSION['current_sort'] ?? 'ASC';
$saved_full_list = $_SESSION['current_full_list'] ?? false;

// Check if user matches pagination intent
if (detectIntent($msg_lower, 'next', $synonyms) && !empty($saved_category)) {
    $_SESSION['current_page'] = ($_SESSION['current_page'] ?? 1) + 1;
    $msg_lower = $saved_category . " full list";
} elseif (detectIntent($msg_lower, 'prev', $synonyms) && !empty($saved_category)) {
    $page_num = max(1, ($_SESSION['current_page'] ?? 2) - 1);
    $_SESSION['current_page'] = $page_num;
    $msg_lower = $saved_category . " full list";
} else {
    $_SESSION['current_page'] = 1; // Reset page untuk query baru
}

// ================= DEFAULT REPLY =================
$reply = "Aku bisa bantu cariin makanan & minuman termurah atau termahal buat kamu 😊<br><br>
✨ <b>Coba fitur ini:</b><br>
• Ketik nama produk: Donat Gula, Kue Lapis, Nasi Goreng, dll<br>
• Ketik kategori: nasi, drink, sweet, snack<br>
• Filter harga: murah, expensive<br>
• Search: cari [kata kunci]<br>
• Lihat semua: all menu";

// ================= DIRECT PRODUCT NAME SEARCH (EXACT & PARTIAL MATCH) =================
// Try to search for exact or partial product name without "cari" keyword
$is_direct_product_search = false;
$direct_search_term = trim($msg);

// Check if message contains potential product name (not a known keyword)
if (strlen($direct_search_term) > 0 && !detectIntent($msg_lower, 'search', $synonyms) && 
    !detectIntent($msg_lower, 'rice', $synonyms) &&
    !detectIntent($msg_lower, 'sweet', $synonyms) &&
    !detectIntent($msg_lower, 'drink', $synonyms) &&
    !detectIntent($msg_lower, 'snack', $synonyms) &&
    !detectIntent($msg_lower, 'cheap', $synonyms) &&
    !detectIntent($msg_lower, 'expensive', $synonyms) &&
    !detectIntent($msg_lower, 'full_list', $synonyms) &&
    !detectIntent($msg_lower, 'next', $synonyms) &&
    !detectIntent($msg_lower, 'prev', $synonyms) &&
    strpos($msg_lower, 'halo') === false &&
    strpos($msg_lower, 'hai') === false &&
    strpos($msg_lower, 'hello') === false &&
    strpos($msg_lower, 'hi') === false) {
    
    // This could be a direct product name search
    $is_direct_product_search = true;
}

if ($is_direct_product_search) {
    $search_pattern = '%' . $direct_search_term . '%';
    $search_pattern_start = $direct_search_term . '%';
    
    $stmt = $koneksi->prepare("
        SELECT p.id, p.product_name, s.seller_name, p.price, p.image, p.category
        FROM products p
        JOIN sellers s ON p.seller_id = s.id
        WHERE p.product_name LIKE ? 
           OR s.seller_name LIKE ?
        ORDER BY 
            CASE 
                WHEN p.product_name LIKE ? THEN 0
                ELSE 1
            END,
            p.price ASC
        LIMIT 8
    ");
    
    if ($stmt) {
        $stmt->bind_param("sss", $search_pattern, $search_pattern, $search_pattern_start);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $reply = "🔍 <b>Hasil pencarian: \"{$direct_search_term}\"</b><br><br>";
            $reply .= "Ditemukan <b>{$result->num_rows}</b> produk:<br><br>";
            
            while ($p = $result->fetch_assoc()) {
                $reply .= generateProductCard($p);
            }
            
            $reply .= "<div style='text-align:center;margin-top:10px;font-size:11px;color:#888;'>";
            $reply .= "Gunakan filter (murah/expensive) atau cari produk lain</div>";
            
            echo $reply;
            exit();
        }
    }
}

// ================= SEARCH FUNCTIONALITY =================
if (detectIntent($msg_lower, 'search', $synonyms)) {
    $search_term = trim(str_replace(['cari', 'search', 'find', 'look for', 'mencari', 'pencarian'], '', $msg_lower));
    $search_term = trim($search_term);
    
    if (empty($search_term)) {
        $reply = "🔍 <b>Mode Pencarian Aktif</b><br><br>
        Ketik kata kunci produk yang ingin dicari.<br>
        Contoh: <br>
        • cari ayam<br>
        • search kopi<br>
        • find goreng<br><br>
        Atau gunakan filter kategori: <br>
        • nasi, minum, manis, snack<br>
        • murah / expensive<br>
        • all menu";
    } else {
        $search_pattern = '%' . $search_term . '%';
        
        $stmt = $koneksi->prepare("
            SELECT p.id, p.product_name, s.seller_name, p.price, p.image
            FROM products p
            JOIN sellers s ON p.seller_id = s.id
            WHERE p.product_name LIKE ? 
               OR s.seller_name LIKE ?
               OR p.category LIKE ?
            ORDER BY p.price ASC
            LIMIT 8
        ");
        
        if ($stmt) {
            $stmt->bind_param("sss", $search_pattern, $search_pattern, $search_pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $reply = "🔍 <b>Hasil pencarian \"{$search_term}\"</b><br><br>";
                $reply .= "Ditemukan <b>{$result->num_rows}</b> produk:<br><br>";
                
                while ($p = $result->fetch_assoc()) {
                    $reply .= generateProductCard($p);
                }
                
                $reply .= "<div style='text-align:center;margin-top:10px;font-size:11px;color:#888;'>";
                $reply .= "Ketik kategori untuk filter lebih spesifik (nasi, minum, manis, snack)</div>";
            } else {
                $reply = "🔍 <b>Hasil pencarian \"{$search_term}\"</b><br><br>";
                $reply .= "<div class='empty-state'>❌ Tidak ditemukan produk dengan kata kunci ini.</div><br><br>";
                $reply .= "Coba kata kunci lain atau ketik: <br>";
                $reply .= "• nasi, minum, manis, snack<br>";
                $reply .= "• murah / expensive<br>";
                $reply .= "• all menu";
            }
            $stmt->close();
        }
        echo $reply;
        exit();
    }
}

// ================= GREETING =================
if (
    strpos($msg_lower,"halo") !== false ||
    strpos($msg_lower,"hai") !== false ||
    strpos($msg_lower,"hello") !== false ||
    strpos($msg_lower,"hi") !== false
){
    $reply = "Halo! 👋<br><br>
    Aku assistant pembelian kamu.<br>
    Sistem ini fokus ke <b>penawaran terbaik</b> (Buyers are KINGs 👑).<br><br>
    <b>Fitur Pencarian:</b><br>
    ✨ Cukup ketik nama produk spesifik, contoh:<br>
    • Donat Gula<br>
    • Kue Lapis<br>
    • Nasi Goreng<br>
    • Kopi Susu<br><br>
    <b>Atau gunakan filter kategori:</b><br>
    • nasi murah / rice cheap<br>
    • minuman termahal / expensive drink<br>
    • all menu / semua daftar menu<br>
    • cari [kata kunci] / search [kata kunci]";
}

// ================= HELPER: Generate Product Card HTML =================
function generateProductCard($product, $reason = "") {
    $product_id = htmlspecialchars($product['id']);
    $product_name = htmlspecialchars($product['product_name']);
    $seller_name = htmlspecialchars($product['seller_name']);
    $price = htmlspecialchars($product['price']);
    $image = htmlspecialchars($product['image']);
    
    // Add images/ prefix if not already there
    if (!empty($image) && strpos($image, 'images/') === false) {
        $image = "images/" . $image;
    }
    
    // Use default image if missing
    if (empty($image) || !file_exists("../".$image)) {
        $image = "images/placeholder.jpg";
    }
    
    $reason_badge = "";
    if (!empty($reason)) {
        $reason_badge = "<div class='recommendation-reason'>{$reason}</div>";
    }
    
    return "
    <div class='product-card' data-product-id='{$product_id}' data-product-name='{$product_name}' data-price='{$price}' data-qty='0'>
        {$reason_badge}
        <img src='{$image}' alt='{$product_name}' class='product-img' onerror=\"this.src='images/placeholder.jpg'\">
        <div class='product-info'>
            <div class='product-name'>{$product_name}</div>
            <div class='product-seller'>dari {$seller_name}</div>
            <div class='product-price'>Rp {$price}</div>
        </div>
        <div class='quantity-controls' style='display:none;'>
            <button class='qty-btn qty-minus' data-product-id='{$product_id}'>−</button>
            <span class='qty-display'>0</span>
            <button class='qty-btn qty-plus' data-product-id='{$product_id}'>+</button>
        </div>
        <div class='product-quantity-badge' data-qty='0'>0</div>
    </div>
    ";
}

// ================= TENTUKAN KATEGORI (Support English & Indonesia) =================
$category = "";
$sort_order = "ASC"; // ASC untuk murah (default), DESC untuk mahal

// Whitelist for sort order (SECURITY FIX)
$allowed_sort_orders = ['ASC', 'DESC'];

$show_full_list = false;
$page = $_SESSION['current_page'] ?? 1;
$limit_per_page = 5;
$limit_offset = ($page - 1) * $limit_per_page;

// Check for full list request
if (detectIntent($msg_lower, 'full_list', $synonyms)) {
    $show_full_list = true;
}

// Check for price filtering
if (detectIntent($msg_lower, 'cheap', $synonyms)) {
    $sort_order = "ASC";
} elseif (detectIntent($msg_lower, 'expensive', $synonyms)) {
    $sort_order = "DESC";
}

// Validate sort order against whitelist (SECURITY FIX)
if (!in_array($sort_order, $allowed_sort_orders)) {
    $sort_order = "ASC"; // Default to safe value
}

// Determine category - Support both Indonesian and English via synonyms
if (detectIntent($msg_lower, 'rice', $synonyms)) {
    $category = "rice";
    $_SESSION['current_category'] = "rice";
} elseif (detectIntent($msg_lower, 'sweet', $synonyms)) {
    $category = "sweet";
    $_SESSION['current_category'] = "sweet";
} elseif (detectIntent($msg_lower, 'drink', $synonyms)) {
    $category = "drink";
    $_SESSION['current_category'] = "drink";
} elseif (detectIntent($msg_lower, 'snack', $synonyms)) {
    $category = "snack";
    $_SESSION['current_category'] = "snack";
} else {
    $_SESSION['current_category'] = "";
}

// Simpan sort order
$_SESSION['current_sort'] = $sort_order;
$_SESSION['current_full_list'] = $show_full_list;

// ================= QUERY PRODUCTS (USING PREPARED STATEMENTS - SECURITY FIX) =================
if ($category != "") {
    // Calculate limit and offset
    $limit = $show_full_list ? $limit_per_page : 3;
    
    // Get total count first
    $count_stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM products WHERE category = ?");
    if ($count_stmt) {
        $count_stmt->bind_param("s", $category);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $total_products = $count_row['total'];
        $total_pages = ceil($total_products / $limit_per_page);
        $count_stmt->close();
    } else {
        $total_products = 0;
        $total_pages = 1;
    }
    
    // Build query with validated sort order
    $sql = "SELECT p.id, p.product_name, s.seller_name, p.price, p.image
            FROM products p
            JOIN sellers s ON p.seller_id = s.id
            WHERE p.category = ?
            ORDER BY p.price $sort_order
            LIMIT ? OFFSET ?";
    
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sii", $category, $limit, $limit_offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $price_label = ($sort_order == "ASC") ? "👑 Termurah" : "💎 Termahal";
            $reply = "<b>$price_label sekarang:</b>";
            
            if ($show_full_list) {
                $reply .= " (Halaman $page dari $total_pages)<br><br>";
            } else {
                $reply .= "<br><br>";
            }

            $counter = 0;
            while ($p = $result->fetch_assoc()) {
                $reason = "";
                // Assign reason to first item if not full list
                if ($counter === 0 && !$show_full_list) {
                    if ($sort_order == "ASC") $reason = "👑 Termurah";
                    elseif ($sort_order == "DESC") $reason = "💎 Paling Premium";
                }
                $reply .= generateProductCard($p, $reason);
                $counter++;
            }
            
            // Add pagination info if showing full list
            if ($show_full_list && $total_pages > 1) {
                $reply .= "<div style='text-align:center;margin-top:15px;font-size:11px;color:#888;'>";
                if ($page < $total_pages) {
                    $reply .= "⬅️ Ketik 'next' untuk halaman berikutnya<br>";
                }
                if ($page > 1) {
                    $reply .= "➡️ Ketik 'prev' untuk halaman sebelumnya";
                }
                $reply .= "</div>";
            }
        } else {
            // No results found
            $reply = "<div class='empty-state'>🔍 Tidak ada produk di kategori ini.</div>";
        }
        $stmt->close();
    } else {
        error_log("Database prepare error: " . $koneksi->error);
        $reply = "Maaf, terjadi kesalahan sistem. Coba lagi ya! 😔";
    }
}

// ================= FALLBACK TERMURAH/TERMAHAL =================
elseif (detectIntent($msg_lower, 'cheap', $synonyms)) {
    
    $stmt = $koneksi->prepare("
        SELECT p.id, p.product_name, s.seller_name, p.price, p.image
        FROM products p
        JOIN sellers s ON p.seller_id = s.id
        ORDER BY p.price ASC
        LIMIT 3
    ");
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $reply = "<b>💰 Penawaran termurah hari ini:</b><br><br>";
            $counter = 0;
            while ($p = $result->fetch_assoc()) {
                $reason = ($counter === 0) ? "👑 Termurah" : "";
                $reply .= generateProductCard($p, $reason);
                $counter++;
            }
        }
        $stmt->close();
    }
}

// ================= PALING MAHAL/EXPENSIVE =================
elseif (detectIntent($msg_lower, 'expensive', $synonyms)) {
    
    $stmt = $koneksi->prepare("
        SELECT p.id, p.product_name, s.seller_name, p.price, p.image
        FROM products p
        JOIN sellers s ON p.seller_id = s.id
        ORDER BY p.price DESC
        LIMIT 3
    ");
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $reply = "<b>💎 Penawaran termahal hari ini:</b><br><br>";
            $counter = 0;
            while ($p = $result->fetch_assoc()) {
                $reason = ($counter === 0) ? "💎 Paling Premium" : "";
                $reply .= generateProductCard($p, $reason);
                $counter++;
            }
        }
        $stmt->close();
    }
}

// ================= SAVE TO DATABASE =================
// Insert message dan reply ke tabel chats untuk history
$msg_escaped = mysqli_real_escape_string($koneksi, $msg);
$reply_escaped = mysqli_real_escape_string($koneksi, $reply);

$insert_sql = "INSERT INTO chats (message, reply) VALUES ('$msg_escaped', '$reply_escaped')";
mysqli_query($koneksi, $insert_sql);

// ================= OUTPUT =================
echo $reply;
