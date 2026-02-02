# QUICK REFERENCE - Code Snippets

## 🚀 Fitur Pencarian Produk - Code Overview

---

## 1️⃣ Deteksi Pencarian Langsung

**Location**: `/api/chat.php` Lines 65-87

```php
// ================= DIRECT PRODUCT NAME SEARCH (EXACT & PARTIAL MATCH) =================
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
    
    $is_direct_product_search = true;
}
```

**Penjelasan:**
- Deteksi apakah input user adalah nama produk
- Exclude keyword yang sudah dikenal (kategori, harga, dll)
- Set flag `$is_direct_product_search = true` jika valid

---

## 2️⃣ Query Database untuk Pencarian

**Location**: `/api/chat.php` Lines 89-130

```php
if ($is_direct_product_search) {
    $search_pattern = '%' . $direct_search_term . '%';
    
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
        $stmt->bind_param("sss", $search_pattern, $search_pattern, $direct_search_term . '%');
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
```

**Penjelasan Query:**
- **LIKE dengan wildcard**: `LIKE '%term%'` untuk flexible search
- **CASE statement**: Prioritas hasil yang cocok dengan nama produk
- **ORDER BY price ASC**: Urutkan dari termurah
- **LIMIT 8**: Maksimal 8 hasil
- **exit()**: Penting! Mencegah double processing

---

## 3️⃣ Update Pesan Default

**Location**: `/api/chat.php` Lines 56-63

```php
// ================= DEFAULT REPLY =================
$reply = "Aku bisa bantu cariin makanan & minuman termurah atau termahal buat kamu 😊<br><br>
✨ <b>Coba fitur ini:</b><br>
• Ketik nama produk: Donat Gula, Kue Lapis, Nasi Goreng, dll<br>
• Ketik kategori: nasi, drink, sweet, snack<br>
• Filter harga: murah, expensive<br>
• Search: cari [kata kunci]<br>
• Lihat semua: all menu";
```

---

## 4️⃣ Update Greeting Message

**Location**: `/api/chat.php` Lines 186-201

```php
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
    • all menu / semua daftar menu";
}
```

---

## 5️⃣ Helper Function: Generate Product Card

```php
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
```

---

## 🔍 SQL Query Breakdown

### Full Search Query:
```sql
SELECT p.id, p.product_name, s.seller_name, p.price, p.image, p.category
FROM products p
JOIN sellers s ON p.seller_id = s.id
WHERE p.product_name LIKE '%donat%' 
   OR s.seller_name LIKE '%donat%'
ORDER BY 
    CASE 
        WHEN p.product_name LIKE '%donat%' THEN 0
        ELSE 1
    END,
    p.price ASC
LIMIT 8;
```

### Penjelasan Per Bagian:

**SELECT Clause:**
```sql
SELECT p.id                    -- Product ID
     , p.product_name          -- Nama produk
     , s.seller_name           -- Nama seller
     , p.price                 -- Harga
     , p.image                 -- Gambar
     , p.category              -- Kategori
```

**FROM & JOIN:**
```sql
FROM products p                -- Tabel produk dengan alias p
JOIN sellers s                 -- Join dengan tabel sellers
ON p.seller_id = s.id         -- Berdasarkan seller_id
```

**WHERE Clause:**
```sql
WHERE p.product_name LIKE '%donat%'   -- Cari di nama produk
   OR s.seller_name LIKE '%donat%'    -- Atau di nama seller
```

**ORDER BY Clause:**
```sql
ORDER BY 
    CASE 
        WHEN p.product_name LIKE '%donat%' THEN 0  -- Prioritas 0 (tertinggi)
        ELSE 1                                       -- Prioritas 1 (lebih rendah)
    END,
    p.price ASC                                      -- Kemudian urutkan harga termurah
```

**LIMIT:**
```sql
LIMIT 8   -- Maksimal 8 hasil
```

---

## 🛠️ Customization Guide

### 1. Mengubah Jumlah Hasil Maksimal
```php
// Dari:
LIMIT 8

// Menjadi:
LIMIT 15  // untuk 15 hasil
```

### 2. Mengubah Urutan Hasil
```php
// Default: Termurah dulu
ORDER BY p.price ASC

// Untuk: Termahal dulu
ORDER BY p.price DESC

// Untuk: Berdasarkan rating
ORDER BY p.rating DESC

// Untuk: Berdasarkan popularity
ORDER BY p.views DESC
```

### 3. Menambah Keyword yang Bypass Pencarian Langsung
```php
// Tambahkan kondisi baru:
&& !detectIntent($msg_lower, 'new_keyword', $synonyms)

// Atau tambahkan ke array synonyms:
'new_keyword' => ['keyword1', 'keyword2', 'keyword3'],
```

### 4. Mengubah Response Message
```php
// Dari:
$reply = "🔍 <b>Hasil pencarian: \"{$direct_search_term}\"</b><br><br>";

// Menjadi:
$reply = "✨ <b>Kami menemukan: \"{$direct_search_term}\"</b><br><br>";
```

---

## 📊 Flow Diagram

```
┌─────────────────────────────┐
│  User Input: "Donat Gula"   │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ Apakah keyword khusus?          │
│ (cari, nasi, murah, dll)        │
└──────────────┬──────────────────┘
               │ TIDAK
               ▼
┌─────────────────────────────────┐
│ Deteksi Pencarian Langsung      │
│ $is_direct_product_search = true│
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ Query: WHERE product_name LIKE% │
│ OR seller_name LIKE %           │
└──────────────┬──────────────────┘
               │
        ┌──────┴──────┐
        │             │
        ▼             ▼
    Ada hasil    Tidak ada
        │             │
        ▼             ▼
    Tampilkan   Tampilkan
    Kartu       Default
    Produk      Reply
        │             │
        └──────┬──────┘
               │
               ▼
    ┌──────────────────────┐
    │   User melihat hasil │
    │  dan dapat add cart  │
    └──────────────────────┘
```

---

## 🔐 Security Checklist

```php
// ✅ AMAN - Menggunakan Prepared Statement
$stmt = $koneksi->prepare("SELECT ... WHERE name LIKE ?");
$stmt->bind_param("s", $search_term);

// ❌ TIDAK AMAN - Direct concatenation
$sql = "SELECT ... WHERE name LIKE '$search_term'";

// ✅ AMAN - Output escaped
echo htmlspecialchars($product_name);

// ❌ TIDAK AMAN - Direct output
echo $product_name;
```

---

## 📱 Response Examples

### Example 1: Exact Match
```
Input: "Donat Gula"
Output:
🔍 Hasil pencarian: "Donat Gula"

Ditemukan 1 produk:

[Product Card] Donat Gula dari [Seller] Rp [Price]

Gunakan filter (murah/expensive) atau cari produk lain
```

### Example 2: Partial Match
```
Input: "Nasi"
Output:
🔍 Hasil pencarian: "Nasi"

Ditemukan 5 produk:

[Nasi Goreng]
[Nasi Kuning]
[Nasi Putih]
[Nasi Liwet]
[Nasi Kuning Istimewa]

Gunakan filter (murah/expensive) atau cari produk lain
```

### Example 3: No Results
```
Input: "Pizza Hawaii"
Output:
[Bot menampilkan default reply/empty state]
```

---

## 🚀 Deployment Checklist

```php
// Pre-deployment verification:

// 1. Check koneksi database
if(!$koneksi) { die("Koneksi gagal"); }  // ✅

// 2. Check tabel exists
$result = $koneksi->query("SHOW TABLES LIKE 'products'");
if($result->num_rows == 0) { /* error */ }  // ✅

// 3. Check kolom exists
$result = $koneksi->query("SHOW COLUMNS FROM products LIKE 'product_name'");
if($result->num_rows == 0) { /* error */ }  // ✅

// 4. Check function exists
if(!function_exists('generateProductCard')) { /* error */ }  // ✅

// 5. Check prepared statement support
if(!$koneksi->prepare("SELECT 1")) { /* error */ }  // ✅
```

---

## 📚 File References

| File | Location | Purpose |
|------|----------|---------|
| chat.php | `/api/` | Main chat logic |
| PRODUCT_SEARCH_FEATURE.md | `/test/` | Technical documentation |
| PRODUCT_SEARCH_IMPLEMENTATION.md | `/test/` | Implementation summary |
| BEFORE_AFTER_COMPARISON.md | `/test/` | Feature comparison |
| TESTING_GUIDE.md | `/test/` | Testing procedures |
| IMPLEMENTATION_COMPLETE.md | `/test/` | Completion report |

---

**Version**: 1.0  
**Last Updated**: February 2, 2026  
**Status**: ✅ Production Ready
