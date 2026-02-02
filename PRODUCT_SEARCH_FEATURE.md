# Fitur Pencarian Produk Spesifik - Documentation

## Deskripsi
Fitur ini memungkinkan user untuk mencari produk makanan spesifik hanya dengan mengetik nama produk tanpa perlu menggunakan keyword "cari" atau "search".

## Cara Kerja

### 1. **Direct Product Name Search** (Pencarian Langsung)
Ketika user mengetik nama produk spesifik yang ada di database, bot akan:
- Mencari produk berdasarkan nama lengkap atau sebagian nama
- Menampilkan hasil produk dari database
- Memprioritaskan hasil yang cocok dengan nama produk dibandingkan nama seller

**Contoh penggunaan:**
```
User: "Donat Gula"
Bot: [Menampilkan kartu produk Donat Gula dari database]

User: "Kue Lapis"
Bot: [Menampilkan kartu produk Kue Lapis dari database]

User: "Nasi Goreng"
Bot: [Menampilkan kartu produk Nasi Goreng dari database]
```

### 2. **Fitur Lain yang Tetap Berfungsi**
Fitur-fitur lainnya masih tetap berfungsi dengan baik:
- **Kategori Filter**: `nasi`, `drink`, `sweet`, `snack`
- **Harga Filter**: `murah`, `expensive`
- **Search dengan keyword**: `cari [nama produk]`, `search [kata kunci]`
- **Full List**: `all menu`, `semua daftar`
- **Pagination**: `next`, `prev`

## Implementasi Teknis

### File yang Dimodifikasi:
1. **`/api/chat.php`** - File utama yang menangani logika chat

### Perubahan Kode:

#### A. Deteksi Pencarian Langsung (Lines 65-95)
```php
// DIRECT PRODUCT NAME SEARCH (EXACT & PARTIAL MATCH)
$is_direct_product_search = false;
$direct_search_term = trim($msg);

// Check if message contains potential product name (not a known keyword)
if (strlen($direct_search_term) > 0 && !detectIntent(...) && ...) {
    $is_direct_product_search = true;
}
```

Logika ini:
- Mengecek apakah input user adalah nama produk (bukan keyword yang dikenal)
- Mendeteksi jika user tidak menggunakan keyword kategori, harga, atau search
- Jika valid, lanjut ke pencarian di database

#### B. Query Database (Lines 95-130)
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
    
    // Execute dan return hasil
    ...
}
```

Fitur pencarian:
- Menggunakan LIKE query untuk flexible search
- Memprioritaskan hasil yang cocok dengan nama produk (CASE statement)
- Mengurutkan hasil berdasarkan harga termurah
- Maksimal 8 hasil (limit)

## User Interface

### Default Reply
Bot akan menampilkan:
```
Aku bisa bantu cariin makanan & minuman termurah atau termahal buat kamu 😊

✨ Coba fitur ini:
• Ketik nama produk: Donat Gula, Kue Lapis, Nasi Goreng, dll
• Ketik kategori: nasi, drink, sweet, snack
• Filter harga: murah, expensive
• Search: cari [kata kunci]
• Lihat semua: all menu
```

### Greeting Response
```
Halo! 👋

Aku assistant pembelian kamu.
Sistem ini fokus ke penawaran terbaik (Buyers are KINGs 👑).

Fitur Pencarian:
✨ Cukup ketik nama produk spesifik, contoh:
• Donat Gula
• Kue Lapis
• Nasi Goreng
• Kopi Susu

Atau gunakan filter kategori:
• nasi murah / rice cheap
• minuman termahal / expensive drink
• all menu / semua daftar menu
```

## Kondisi Pencarian Langsung

Pencarian langsung akan **TIDAK** aktif jika user mengetik:
- `halo`, `hai`, `hello`, `hi` → Greeting
- `cari`, `search`, `find`, etc. → Search dengan keyword
- `nasi`, `rice`, `drink`, `sweet`, `snack` → Kategori filter
- `murah`, `cheap`, `expensive`, `mahal` → Harga filter
- `all`, `menu`, `semua`, `daftar` → Full list
- `next`, `lanjut`, `prev`, `sebelumnya` → Pagination

Pencarian langsung akan **AKTIF** jika user mengetik nama produk spesifik yang tidak termasuk dalam kategori di atas.

## Contoh Skenario

### Scenario 1: Pencarian Donat Gula
```
User Input: "Donat Gula"
Bot Output:
🔍 Hasil pencarian: "Donat Gula"

Ditemukan 1 produk:

[Product Card]
Donat Gula
dari [Seller Name]
Rp [Price]
[Add to Cart Button]

Gunakan filter (murah/expensive) atau cari produk lain
```

### Scenario 2: Pencarian Partial
```
User Input: "Nasi"
Bot Output:
🔍 Hasil pencarian: "Nasi"

Ditemukan 3 produk:

[Nasi Goreng Card]
[Nasi Kuning Card]
[Nasi Kuning Istimewa Card]

Gunakan filter (murah/expensive) atau cari produk lain
```

### Scenario 3: Produk Tidak Ditemukan
```
User Input: "Pizza Hawaii"
Bot Output:
❌ Tidak ada produk dengan nama yang spesifik

[Default reply ditampilkan]
```

## Database Requirements

Pastikan tabel `products` memiliki kolom-kolom berikut:
- `id` - Product ID
- `product_name` - Nama produk (VARCHAR)
- `seller_id` - FK ke tabel sellers
- `price` - Harga produk
- `image` - Path gambar
- `category` - Kategori produk
- `sellers.id` - Seller ID
- `sellers.seller_name` - Nama seller

## Testing Checklist

- [ ] Mengetik "Donat Gula" menampilkan produk
- [ ] Mengetik "Kue Lapis" menampilkan produk  
- [ ] Mengetik nama produk partial (misal "Nasi") menampilkan hasil
- [ ] Kategori filter masih berfungsi (nasi, drink, dll)
- [ ] Harga filter masih berfungsi (murah, expensive)
- [ ] Search dengan keyword masih berfungsi
- [ ] Greeting masih merespon dengan benar
- [ ] Produk tidak ada menampilkan pesan error

## Maintenance

Jika ingin menambah/mengurangi keyword yang mengaktifkan pencarian langsung, edit bagian:

```php
// Lines 69-81 di chat.php
if (strlen($direct_search_term) > 0 && 
    !detectIntent($msg_lower, 'search', $synonyms) && 
    !detectIntent($msg_lower, 'rice', $synonyms) &&
    // ... tambah/kurangi kondisi di sini
```

Untuk mengubah limit hasil, modifikasi:
```php
// Line 102
LIMIT 8  // Ubah angka ini
```

Untuk mengubah urutan hasil, modifikasi bagian `ORDER BY`:
```php
// Lines 98-102
ORDER BY 
    CASE 
        WHEN p.product_name LIKE ? THEN 0
        ELSE 1
    END,
    p.price ASC
```
