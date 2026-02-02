# Quick Reference Guide

## File Changes Summary

### 1. PHP Backend Changes

**File: `api/chat.php`**

**Added Function:**
```php
function generateProductCard($product) {
    // Returns HTML for interactive product card
    // Includes: image, product name, seller, price, quantity badge
    // Data attributes for JavaScript: data-product-id, data-price
}
```

**Updated Queries:**
```php
// BEFORE:
SELECT p.product_name, s.seller_name, p.price

// AFTER:
SELECT p.id, p.product_name, s.seller_name, p.price, p.image
```

**Result:** Returns product cards with interactive elements

---

### 2. JavaScript Frontend Changes

**File: `assets/js/chat.js`**

**New Cart State:**
```javascript
const cart = {};  // Line 8
// Stores: { product_id: quantity, ... }
```

**New Function:**
```javascript
function handleProductCardClick(e) {  // Lines 93-119
    // Get product ID from data attribute
    // Increment quantity in cart
    // Update DOM (badge)
    // Add animation
}
```

**Updated Function:**
```javascript
// addMessage() - Lines 51-54 (NEW SECTION)
if (sender === "bot") {
    const productCards = div.querySelectorAll(".product-card");
    productCards.forEach(card => {
        card.addEventListener("click", handleProductCardClick);
    });
}
```

**Result:** Handles all user interactions and cart management

---

### 3. CSS Styling Changes

**File: `assets/css/style.css`**

**New Classes:**
```css
.product-card { }
.product-img { }
.product-info { }
.product-name { }
.product-seller { }
.product-price { }
.product-quantity-badge { }
```

**New Animation:**
```css
@keyframes badgePulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}
```

**Result:** Beautiful, interactive product cards with animations

---

### 4. Database Setup

**File: `config/database_setup.sql`** (NEW)

Contains:
- `sellers` table creation
- `products` table with `image` column
- `chats` table
- 12 sample products
- 3 sample sellers

**Result:** Complete DB schema ready to import

---

### 5. Documentation Files

**New Files Created:**
- `README.md` - Complete project documentation
- `IMPLEMENTATION_GUIDE.md` - Technical deep dive
- `DEPLOYMENT_CHECKLIST.md` - Step-by-step deployment
- `PROJECT_SUMMARY.md` - Project completion report
- `ARCHITECTURE_DIAGRAMS.md` - Visual system diagrams
- `QUICK_REFERENCE.md` - This file

---

## Quick Code Reference

### How to Add to Cart (JavaScript)

```javascript
// When user clicks product card:
const productId = "3";
const quantity = 1;

// Initialize if new
if (!cart[productId]) {
    cart[productId] = 0;
}

// Add quantity
cart[productId] += quantity;

// View cart
console.log(cart);  // { "3": 1 }
```

### How to Create Product Card (PHP)

```php
$product = [
    "id" => 3,
    "product_name" => "Nasi Uduk",
    "seller_name" => "Toko Manis",
    "price" => 20000,
    "image" => "images/nasi_uduk.jpg"
];

echo generateProductCard($product);
// Outputs: <div class="product-card" ...>
```

### How to Style Badge (CSS)

```css
/* Show badge only when quantity > 0 */
.product-card[data-qty="0"] .product-quantity-badge {
    display: none;
}

.product-card[data-qty]:not([data-qty="0"]) .product-quantity-badge {
    transform: scale(1);
}
```

---

## Data Flow Cheat Sheet

```
User Types Message
    ↓
JavaScript: fetch() POST to chat.php
    ↓
PHP: Receives message, analyzes keywords
    ↓
PHP: SELECT products from MySQL
    ↓
PHP: generateProductCard() for each product
    ↓
PHP: Return HTML response
    ↓
JavaScript: addMessage(html, "bot")
    ↓
JavaScript: Find .product-card elements
    ↓
JavaScript: Add click listeners
    ↓
User Clicks Card
    ↓
JavaScript: handleProductCardClick()
    ↓
JavaScript: cart[productId]++
    ↓
JavaScript: Update DOM (badge)
    ↓
CSS: Animation triggers
    ↓
User sees quantity badge update
```

---

## Key Variables Explained

### JavaScript

| Variable | Type | Value | Purpose |
|----------|------|-------|---------|
| `cart` | Object | `{ "3": 2, "5": 1 }` | Tracks quantities |
| `productId` | String | `"3"` | Product ID from DB |
| `data-qty` | Attribute | `"2"` | Current quantity in DOM |

### Database

| Column | Table | Type | Example |
|--------|-------|------|---------|
| `id` | products | INT | 3 |
| `product_name` | products | VARCHAR | "Nasi Uduk" |
| `image` | products | VARCHAR | "images/nasi_uduk.jpg" |
| `price` | products | INT | 20000 |
| `seller_id` | products | INT | 1 |

---

## Common Tasks

### Task: View Current Cart State
```javascript
// In browser console (F12):
console.log(cart);
// Output: { "3": 2, "5": 1 }
```

### Task: Add Product Image to Database
```sql
UPDATE products 
SET image = "images/product_name.jpg" 
WHERE id = 3;
```

### Task: Add New Product
```sql
INSERT INTO products 
(seller_id, product_name, category, price, image)
VALUES (1, "Burger", "snack", 15000, "images/burger.jpg");
```

### Task: Check Click Handler
```javascript
// In chat.js:
// Add console.log in handleProductCardClick():
console.log(`Clicked: ${productName}, Qty: ${cart[productId]}`);
```

### Task: Change Badge Color
```css
/* In style.css, modify: */
.product-quantity-badge {
    background: linear-gradient(135deg, #YOUR_COLOR_1, #YOUR_COLOR_2);
}
```

---

## Troubleshooting Quick Fixes

| Problem | Solution |
|---------|----------|
| **Images not showing** | Check `/images/` folder exists, verify image paths in database |
| **Click not working** | Check browser console (F12) for errors, ensure chat.js loaded |
| **Badge not appearing** | Check CSS - `data-qty` attribute must be > 0 |
| **Quantity not incrementing** | Check `cart[productId]++` logic, verify productId is correct |
| **Database connection fails** | Verify credentials in `koneksi.php`, check MySQL running |
| **Product cards not displaying** | Check `database_setup.sql` imported, products table has data |

---

## Performance Tips

**For Better Speed:**
- Compress product images to <100KB
- Use WebP format if browser supports
- Lazy load images if many products
- Cache database queries

**For Better UX:**
- Reduce animation duration for mobile
- Add haptic feedback for mobile clicks
- Show loading indicator while fetching
- Prefetch product images

---

## Security Checklist

- ✅ Use `htmlspecialchars()` for output
- ✅ Escape user input before display
- ✅ Use parameterized queries (if upgrading to prepared statements)
- ✅ Validate image paths
- ✅ Don't expose database credentials
- ✅ Validate user inputs server-side

---

## Keyboard Shortcuts (Development)

| Key | Action |
|-----|--------|
| **F12** | Open browser DevTools |
| **Console Tab** | View JavaScript errors |
| **Elements Tab** | Inspect HTML structure |
| **Network Tab** | Monitor API calls |
| **F5** | Refresh page |
| **Ctrl+Shift+Delete** | Clear cache |

---

## API Endpoints

### Frontend Calls

**Send Message:**
```javascript
fetch("api/chat.php", {
    method: "POST",
    headers: {"Content-Type": "application/x-www-form-urlencoded"},
    body: "message=" + encodeURIComponent(message)
})
```

**Returns:**
- HTML with product cards
- Bot reply text
- Product image src paths

---

## Database Queries Quick Reference

### Get All Products
```sql
SELECT * FROM products;
```

### Get Products by Category
```sql
SELECT * FROM products WHERE category = 'rice';
```

### Get Cheapest Products
```sql
SELECT * FROM products ORDER BY price ASC LIMIT 3;
```

### Get Product with Seller Info
```sql
SELECT p.*, s.seller_name 
FROM products p 
JOIN sellers s ON p.seller_id = s.id 
WHERE p.category = 'rice' 
ORDER BY p.price ASC;
```

---

## File Size Reference

| File | Lines | Size |
|------|-------|------|
| `index.php` | 40 | ~1.5KB |
| `chat.php` | 112 | ~3.5KB |
| `chat.js` | 173 | ~5KB |
| `style.css` | 220+ | ~8KB |
| `database_setup.sql` | 100+ | ~3KB |

Total: **~600 lines of clean, readable code**

---

## Browser Support

✅ Chrome 60+
✅ Firefox 55+
✅ Safari 12+
✅ Edge 79+
✅ Mobile Safari (iOS 12+)
✅ Chrome Mobile (Android 6+)

---

## Dependencies

### Zero External Dependencies! 🎉

- No jQuery
- No React
- No Bootstrap
- No npm packages
- Pure vanilla JS, HTML, CSS
- Only requires: PHP 5.6+, MySQL 5.7+

---

## Environment Setup (Local)

```
1. Install XAMPP / WAMP
2. Copy project to htdocs/
3. Import database_setup.sql
4. Access: localhost/chatbot1/
5. Test: Type "nasi" to see products
```

---

## Going Live (Production)

```
1. Update koneksi.php with production DB
2. Upload files to server
3. Set permissions: 755 folders, 644 files
4. Import database schema
5. Add product images
6. Test all features
7. Monitor error logs
```

---

## Future Enhancements

```
[ ] Add - Remove quantity buttons
[ ] Save cart to localStorage
[ ] Implement checkout page
[ ] Add user login
[ ] Create admin panel
[ ] Add payment gateway
[ ] Implement order tracking
[ ] Add customer reviews
```

---

## Contact & Support

📖 Read: `README.md` (overview)
🔧 Technical: `IMPLEMENTATION_GUIDE.md`
📋 Deployment: `DEPLOYMENT_CHECKLIST.md`
🎨 Architecture: `ARCHITECTURE_DIAGRAMS.md`
📊 Summary: `PROJECT_SUMMARY.md`

---

**Last Updated:** January 31, 2026
**Version:** 1.0
**Status:** Production Ready ✅
