# Implementation Guide: Click-to-Increment Shopping System

## How Click-to-Increment Works

### 1. Initial State
When bot sends products, each product card looks like:
```
┌─────────────────────────────────────────┐
│  [Image]  Product Name                  │
│           dari Seller Name               │
│           Rp 20000                       │
└─────────────────────────────────────────┘
   (No quantity badge - data-qty="0")
```

### 2. First Click (User clicks card)
```javascript
// JavaScript cart state BEFORE
cart = {}

// User clicks product card (data-product-id="3")
→ Event listener triggers handleProductCardClick()

// JavaScript increments quantity
cart[3] = 1

// Update DOM attribute
productCard.setAttribute("data-qty", "1")
```

**Visual Result:**
```
┌─────────────────────────────────────────┐
│  [Image]  Product Name          ┌────┐ │
│           dari Seller Name      │ 1️⃣ │ │  ← Red badge appears & pulses
│           Rp 20000              └────┘ │
└─────────────────────────────────────────┘
      (data-qty="1" shows badge)
```

### 3. Second Click (User clicks same card again)
```javascript
// JavaScript cart state BEFORE
cart = { "3": 1 }

// User clicks same product card again
→ handleProductCardClick() triggers again

// Quantity increments
cart[3] = 2  // Now 2 items in cart

// DOM updates
productCard.setAttribute("data-qty", "2")
badge.textContent = "2"
```

**Visual Result:**
```
┌─────────────────────────────────────────┐
│  [Image]  Product Name          ┌────┐ │
│           dari Seller Name      │ 2️⃣ │ │  ← Badge updates to 2
│           Rp 20000              └────┘ │
└─────────────────────────────────────────┘
      (data-qty="2")
```

### 4. Third Click (Still same card)
```javascript
cart = { "3": 2 }
→ Click again
cart[3] = 3
badge shows "3"
```

### 5. Click Different Product
```javascript
cart = { "3": 3 }

// User clicks different product (data-product-id="5")
→ handleProductCardClick() triggers on new card

cart[5] = 1  // New product added

// Multiple badges now showing
Product 3: [3️⃣] badge
Product 5: [1️⃣] badge
```

---

## Code Flow Diagram

```
┌─────────────────────────────────────────────────────┐
│ User Types "nasi murah"                             │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ chat.js: form.submit → fetch("api/chat.php")        │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ chat.php:                                           │
│ - Analyze keyword → category = "rice"               │
│ - Query DB: SELECT products WHERE category = rice   │
│ - generateProductCard() for each product            │
│ - Return HTML with product cards                    │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ chat.js: addMessage(html, "bot")                    │
│ - Add HTML to chat box                              │
│ - Find all .product-card elements                   │
│ - Attach click event listener:                      │
│   card.addEventListener("click", 
│     handleProductCardClick)                         │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ User sees interactive product cards                 │
│ Ready to click                                      │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ User Clicks Product Card                            │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ handleProductCardClick(e)                           │
│ - Get productId from data-product-id attribute      │
│ - If not in cart: cart[productId] = 0               │
│ - Increment: cart[productId]++                      │
│ - Update DOM: data-qty attribute                    │
│ - Update badge text                                 │
│ - Add pulse animation                               │
└────────────────┬────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────┐
│ CSS Animation:                                      │
│ - Badge scales: 0 → 1.15 → 1 (pulse effect)        │
│ - Duration: 0.5 seconds                             │
└─────────────────────────────────────────────────────┘
```

---

## Key Code Sections

### JavaScript Cart Initialization
```javascript
// Location: chat.js (line 8)
const cart = {};  // Empty cart on page load

// Example after user clicks:
// cart = { "3": 2, "5": 1, "7": 3 }
```

### Attaching Click Handlers
```javascript
// Location: chat.js addMessage() function (lines 51-54)
if (sender === "bot") {
    const productCards = div.querySelectorAll(".product-card");
    productCards.forEach(card => {
        card.addEventListener("click", handleProductCardClick);
    });
}
```

### Increment Logic
```javascript
// Location: chat.js handleProductCardClick() (lines 93-119)
function handleProductCardClick(e) {
    const productId = productCard.getAttribute("data-product-id");
    
    // Initialize if new
    if (!cart.hasOwnProperty(productId)) {
        cart[productId] = 0;
    }
    
    // INCREMENT ⬇️ (THIS IS THE MAGIC)
    cart[productId]++;  // 0→1, 1→2, 2→3, ...
    
    // Update UI to show new quantity
    productCard.setAttribute("data-qty", cart[productId]);
    badge.textContent = cart[productId];
}
```

### CSS Badge Control
```css
/* Location: style.css */

/* Hide badge when quantity is 0 */
.product-card[data-qty="0"] .product-quantity-badge {
    display: none;
}

/* Show badge when quantity > 0 */
.product-card[data-qty]:not([data-qty="0"]) .product-quantity-badge {
    transform: scale(1);  /* Make visible */
}

/* Pulse animation on update */
@keyframes badgePulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }  /* Zoom in */
    100% { transform: scale(1); }
}

.product-card.qty-updated .product-quantity-badge {
    animation: badgePulse 0.5s ease;
}
```

---

## Data Attributes Explanation

### Product Card Element
```html
<div class="product-card" 
     data-product-id="3"          <!-- Unique product ID -->
     data-product-name="Nasi Uduk"    <!-- For logging -->
     data-price="20000"           <!-- For reference -->
     data-qty="0">                <!-- Current quantity (updated on click) -->
    
    <!-- Card content -->
</div>
```

**How `data-qty` works:**
- `data-qty="0"` → Badge hidden (CSS: `display:none`)
- `data-qty="1"` → Badge shows "1"
- `data-qty="2"` → Badge shows "2"
- CSS updates instantly when JavaScript sets attribute

---

## Step-by-Step Example

### Scenario: User orders 2 Nasi Uduk + 1 Jus Jeruk

**Step 1: Chat loads, user types "nasi"**
```
User Input: "nasi"
↓
Bot Response: Shows 3 rice products with cards
- Nasi Goreng (Rp 25000)
- Nasi Kuning (Rp 22000)  
- Nasi Uduk (Rp 20000)     ← Click this

JavaScript cart = {}  (empty)
```

**Step 2: User clicks Nasi Uduk (first time)**
```
Click Nasi Uduk card
↓
handleProductCardClick() executes:
- productId = "3"
- cart doesn't have "3" yet
- Initialize: cart["3"] = 0
- Increment: cart["3"] = 1
- Update DOM: data-qty="1"
- Badge shows: 1️⃣

cart = { "3": 1 }
```

**Step 3: User clicks Nasi Uduk again**
```
Click same Nasi Uduk card
↓
handleProductCardClick() executes:
- productId = "3"
- cart already has "3"
- Increment: cart["3"] = 2
- Update DOM: data-qty="2"
- Badge shows: 2️⃣

cart = { "3": 2 }
```

**Step 4: User types "minum"**
```
User Input: "minum"
↓
Bot Response: Shows 3 drink products with cards
- Es Teh Manis (Rp 5000)
- Jus Jeruk (Rp 8000)      ← Click this
- Kopi Hitam (Rp 7000)

cart = { "3": 2 }  (unchanged)
```

**Step 5: User clicks Jus Jeruk (first time)**
```
Click Jus Jeruk card
↓
handleProductCardClick() executes:
- productId = "8"
- cart doesn't have "8" yet
- Initialize: cart["8"] = 0
- Increment: cart["8"] = 1
- Update DOM: data-qty="1"
- Badge shows: 1️⃣

cart = { "3": 2, "8": 1 }
```

**Final Cart State:**
```
{
    "3": 2,    // Nasi Uduk × 2
    "8": 1     // Jus Jeruk × 1
}

Visual representation in chat:
- Nasi Uduk card shows badge: [2️⃣]
- Jus Jeruk card shows badge: [1️⃣]
```

---

## How Images Load from Database

### Database Setup
```sql
products table:
- id: 3
- product_name: "Nasi Uduk"
- category: "rice"
- price: 20000
- image: "images/nasi_uduk.jpg"  ← Image path stored
```

### PHP Generates HTML
```php
// In generateProductCard() function
$image = htmlspecialchars($product['image']);

// If image file doesn't exist, use default
if (!file_exists("../".$image)) {
    $image = "images/default.jpg";
}

// Generate img tag with src from database
echo "<img src='{$image}' alt='...' class='product-img'
      onerror=\"this.src='images/default.jpg'\">";
```

### Browser Renders Image
1. Browser receives: `<img src="images/nasi_uduk.jpg">`
2. Looks for file in `/images/` directory
3. If found: displays product image
4. If not found: `onerror` attribute triggers, shows default image
5. CSS styles it: 80×80px, rounded corners, cover fit

### Image Result
```
Chat Message:
┌─────────────────────────────────────────┐
│  [Image from DB]  Nasi Uduk              │
│                   Rp 20000               │
└─────────────────────────────────────────┘

Image path: images/nasi_uduk.jpg
Source: database products.image column
```

---

## HTML Structure in Chat

### What User Sees
```
Chat Box
├─ User Message: "nasi murah"
│
└─ Bot Response:
   ├─ Text: "👑 Best offer sekarang:"
   │
   ├─ Product Card 1
   │  └─ [Image] Nasi Goreng | Rp 25000
   │
   ├─ Product Card 2
   │  └─ [Image] Nasi Kuning | Rp 22000
   │
   └─ Product Card 3
      └─ [Image] Nasi Uduk | Rp 20000 [2️⃣]
```

### Actual HTML Structure
```html
<div class="chat bot">
    <div class="chat-bubble">
        <b>👑 Best offer sekarang:</b><br><br>
        
        <div class="product-card" 
             data-product-id="1" 
             data-product-name="Nasi Goreng" 
             data-price="25000"
             data-qty="0">
            <img src="images/nasi_goreng.jpg" class="product-img">
            <div class="product-info">
                <div class="product-name">Nasi Goreng</div>
                <div class="product-seller">dari Warung Mak Siti</div>
                <div class="product-price">Rp 25000</div>
            </div>
            <div class="product-quantity-badge">0</div>
        </div>
        
        <!-- More cards... -->
        
        <div class="chat-time">14:30</div>
    </div>
</div>
```

---

## UX Improvements Summary

| Feature | Before | After |
|---------|--------|-------|
| **Display** | Plain text list | Interactive cards with images |
| **Interaction** | Not clickable | Click to add to cart |
| **Feedback** | None | Quantity badge with animation |
| **Visual Info** | Just name & price | Image + name + seller + price |
| **State Tracking** | Manual counting | Automatic quantity display |
| **User Experience** | Basic chatbot | Modern food ordering app |

---

## Browser Console Debug

When user clicks, open browser console (F12) to see:
```javascript
// Console output
Added to cart: Nasi Uduk x1 (ID: 3)
Added to cart: Nasi Uduk x2 (ID: 3)
Added to cart: Jus Jeruk x1 (ID: 8)

// Access cart in console
console.log(cart)
// Output: { "3": 2, "8": 1 }
```

This helps debug if clicking isn't working!

---

## Next Steps for Enhancement

1. **Show Cart Total**: Display sum of quantities
2. **Remove Items**: Button to decrease quantity
3. **Persist Cart**: Use localStorage to save on refresh
4. **Checkout**: Send cart data to backend for order
5. **Search**: Filter products by name/category
6. **Sort**: Price, rating, distance from location

All maintaining the simple, beginner-friendly approach! 👑
