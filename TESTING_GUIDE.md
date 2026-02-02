# TESTING GUIDE - Fitur Pencarian Produk Spesifik

## 🎯 Panduan Testing Cepat

### Prerequisites
- [ ] Database `buyers_are_kings_2` aktif
- [ ] Apache/PHP berjalan
- [ ] Chat application dapat diakses
- [ ] Produk sudah ada di database (misal: Donat Gula, Kue Lapis, dll)

---

## 📝 Test Cases

### ✅ TEST 1: Pencarian Produk Exact Match

**Test Name:** Pencarian Produk Donat Gula

**Steps:**
1. Buka aplikasi chat
2. Ketik: `Donat Gula`
3. Tunggu response

**Expected Result:**
```
🔍 Hasil pencarian: "Donat Gula"

Ditemukan 1 produk:

[Kartu Produk]
Donat Gula
dari [Nama Seller]
Rp [Harga]

Gunakan filter (murah/expensive) atau cari produk lain
```

**Status:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 2: Pencarian Produk Partial Match

**Test Name:** Pencarian dengan sebagian nama (Nasi)

**Steps:**
1. Buka aplikasi chat
2. Ketik: `Nasi`
3. Tunggu response

**Expected Result:**
```
🔍 Hasil pencarian: "Nasi"

Ditemukan X produk:

[Nasi Goreng]
[Nasi Kuning]
[Nasi Putih]
...

Gunakan filter (murah/expensive) atau cari produk lain
```

**Status:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 3: Pencarian Produk Tidak Ada

**Test Name:** Pencarian produk yang tidak ada di database

**Steps:**
1. Buka aplikasi chat
2. Ketik: `Pizza Hawaii`
3. Tunggu response

**Expected Result:**
- Bot menampilkan pesan default atau empty state
- Tidak error

**Status:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 4: Tetap Support Keyword Search

**Test Name:** Pencarian dengan keyword "cari"

**Steps:**
1. Buka aplikasi chat
2. Ketik: `cari Donat`
3. Tunggu response

**Expected Result:**
```
🔍 Hasil pencarian "Donat"

Ditemukan X produk:
[Hasil pencarian]

Gunakan filter (murah/expensive) atau cari produk lain
```

**Backward Compatibility:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 5: Tetap Support Filter Kategori

**Test Name:** Filter kategori "nasi" masih berfungsi

**Steps:**
1. Buka aplikasi chat
2. Ketik: `nasi`
3. Tunggu response

**Expected Result:**
```
👑 Termurah sekarang:

[Produk nasi termurah]
...

Atau filter dengan murah/expensive, ketik 'nasi all menu' untuk full list
```

**Backward Compatibility:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 6: Tetap Support Filter Harga

**Test Name:** Filter harga "murah" masih berfungsi

**Steps:**
1. Buka aplikasi chat
2. Ketik: `murah`
3. Tunggu response

**Expected Result:**
```
💰 Penawaran termurah hari ini:

[Produk 1 - Rp X]
[Produk 2 - Rp Y]
[Produk 3 - Rp Z]
```

**Backward Compatibility:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 7: Greeting Message

**Test Name:** Greeting menampilkan informasi fitur pencarian baru

**Steps:**
1. Buka aplikasi chat
2. Ketik: `halo` atau `hello`
3. Tunggu response

**Expected Result:**
Harus ada informasi tentang fitur pencarian produk spesifik:
```
Fitur Pencarian:
✨ Cukup ketik nama produk spesifik, contoh:
• Donat Gula
• Kue Lapis
• Nasi Goreng
• Kopi Susu
```

**Documentation Update:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 8: Default Reply Update

**Test Name:** Default reply menampilkan fitur pencarian baru

**Steps:**
1. Buka aplikasi chat
2. Ketik: `apa aja` (random input yang bukan keyword)
3. Tunggu response

**Expected Result:**
Pesan default harus memuat:
```
✨ Coba fitur ini:
• Ketik nama produk: Donat Gula, Kue Lapis, Nasi Goreng, dll
```

**Documentation Update:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 9: Add to Cart dari Pencarian

**Test Name:** User bisa tambah produk ke cart dari hasil pencarian

**Steps:**
1. Ketik: `Donat Gula`
2. Klik button [+] pada kartu produk
3. Periksa cart summary

**Expected Result:**
- Produk ditambah ke cart
- Cart summary update
- Qty badge muncul di kartu produk

**Status:** ✅ PASS / ❌ FAIL

---

### ✅ TEST 10: Case Insensitive Search

**Test Name:** Pencarian tidak case-sensitive

**Steps:**
1. Ketik: `donat gula` (huruf kecil)
2. Tunggu response
3. Ketik: `DONAT GULA` (huruf besar)
4. Tunggu response

**Expected Result:**
Kedua input harus menampilkan hasil yang sama

**Status:** ✅ PASS / ❌ FAIL

---

## 🔍 Database Verification

Sebelum testing, pastikan data ini ada di database:

```sql
-- Check produk di database
SELECT product_name, seller_name, price, category 
FROM products 
WHERE product_name LIKE '%Donat%' 
   OR product_name LIKE '%Kue Lapis%'
   OR product_name LIKE '%Nasi%';

-- Hasil harus menampilkan minimal:
-- Donat Gula | Seller Name | Price | sweet
-- Kue Lapis | Seller Name | Price | sweet
-- Nasi Goreng | Seller Name | Price | rice
-- etc.
```

---

## 🎬 Testing Flow

### Quick Test (5 menit)
1. Test #1: Exact match search (Donat Gula)
2. Test #4: Keyword search (cari Donat)
3. Test #5: Category filter (nasi)
4. Test #9: Add to cart

### Full Test (15 menit)
Jalankan semua test cases #1-#10

### Regression Test (10 menit)
Test #4, #5, #6 untuk memastikan backward compatibility

---

## 📊 Test Report Template

```
TEST SESSION: _______________
DATE: _______________
TESTER: _______________
BROWSER: _______________

TEST RESULTS:
─────────────────────────────────────────
Test #1: Exact Match         [ ] PASS [ ] FAIL
Test #2: Partial Match       [ ] PASS [ ] FAIL
Test #3: No Results          [ ] PASS [ ] FAIL
Test #4: Keyword Search      [ ] PASS [ ] FAIL
Test #5: Category Filter     [ ] PASS [ ] FAIL
Test #6: Price Filter        [ ] PASS [ ] FAIL
Test #7: Greeting            [ ] PASS [ ] FAIL
Test #8: Default Reply       [ ] PASS [ ] FAIL
Test #9: Add to Cart         [ ] PASS [ ] FAIL
Test #10: Case Insensitive   [ ] PASS [ ] FAIL
─────────────────────────────────────────

OVERALL: [ ] ALL PASS [ ] SOME FAIL

ISSUES FOUND:
1. _______________________________
2. _______________________________
3. _______________________________

NOTES:
_________________________________
_________________________________
```

---

## ✅ Sign-Off Checklist

- [ ] Semua test cases passed
- [ ] No database errors di console
- [ ] No PHP errors di error log
- [ ] Backward compatibility maintained
- [ ] Documentation updated
- [ ] Code reviewed
- [ ] Performance acceptable

---

## 🚨 Troubleshooting

### Issue: Pencarian tidak menampilkan hasil meskipun produk ada

**Solution:**
1. Check database connection: `test di /config/koneksi.php`
2. Check tabel products: `SELECT COUNT(*) FROM products;`
3. Check product_name field ada data: `SELECT DISTINCT product_name FROM products LIMIT 5;`
4. Check syntax error di chat.php: Lihat error log PHP

---

### Issue: Error "Database prepare error"

**Solution:**
1. Check query syntax di lines 89-102 di chat.php
2. Cek kolom di tabel: id, product_name, seller_id, price, image, category
3. Cek table sellers ada: `SHOW TABLES LIKE 'sellers';`

---

### Issue: Pencarian return hasil tapi tidak ditampilkan

**Solution:**
1. Check function `generateProductCard()` ada
2. Check image path correct
3. Check no PHP notices/warnings di output

---

## 📞 Support & Contact

Jika ada issue:
1. Check error log di browser console (F12)
2. Check PHP error log di `/xampp/logs/`
3. Check file path di code (absolute vs relative)
4. Check database connection string di koneksi.php

---

**Testing Document Version: 1.0**
**Last Updated: February 2, 2026**
**Status: Ready for QA**
