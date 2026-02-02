# Deployment Checklist: Buyers are KINGs Enhanced Chatbot

## Pre-Deployment Setup

### 1. Database Configuration
- [ ] MySQL server running
- [ ] Database `buyers_are_kings` created
- [ ] Run `config/database_setup.sql` to create tables
- [ ] Verify tables created:
  - [ ] `sellers` table exists
  - [ ] `products` table exists with `image` column
  - [ ] `chats` table exists (optional)
- [ ] Insert sample data:
  - [ ] 12 sample products inserted
  - [ ] 3 sellers created

### 2. Database Connection
- [ ] Open `config/koneksi.php`
- [ ] Verify credentials:
  - [ ] `$host` = "localhost" (or your DB host)
  - [ ] `$user` = "root" (or your DB user)
  - [ ] `$pass` = "" (or your password)
  - [ ] `$db` = "buyers_are_kings"
- [ ] Test connection by accessing `/api/chat.php`

### 3. File Structure
- [ ] All files in correct directories:
  ```
  chatbot1/
  ├── index.php                     ✓
  ├── README.md                     ✓ (new)
  ├── IMPLEMENTATION_GUIDE.md       ✓ (new)
  ├── DEPLOYMENT_CHECKLIST.md       ✓ (this file)
  ├── config/
  │   ├── koneksi.php              ✓
  │   └── database_setup.sql       ✓ (new)
  ├── api/
  │   ├── chat.php                 ✓ (updated)
  │   └── load_chat.php            ✓
  ├── assets/
  │   ├── css/
  │   │   └── style.css            ✓ (updated)
  │   └── js/
  │       └── chat.js              ✓ (updated)
  ├── images/                       (create if not exists)
  └── sound/
      └── mixkit-correct-answer-tone-2870.wav  ✓
  ```

### 4. Images Directory
- [ ] `/images/` folder exists
- [ ] Place product images in `/images/`:
  - [ ] `nasi_goreng.jpg`
  - [ ] `nasi_kuning.jpg`
  - [ ] `nasi_uduk.jpg`
  - [ ] `martabak.jpg`
  - [ ] `roti_bakar.jpg`
  - [ ] `donat.jpg`
  - [ ] `es_teh.jpg`
  - [ ] `jus_jeruk.jpg`
  - [ ] `kopi.jpg`
  - [ ] `keripik_pisang.jpg`
  - [ ] `batagor.jpg`
  - [ ] `lumpia.jpg`
  - [ ] `default.jpg` (fallback image, required)

### 5. Browser Compatibility
- [ ] Test in Chrome (latest)
- [ ] Test in Firefox (latest)
- [ ] Test in Edge (latest)
- [ ] Mobile browser responsive

---

## Testing Checklist

### 1. Chat Functionality
- [ ] Open `index.php` in browser
- [ ] Type "halo" → greeting message appears
- [ ] Type "nasi" → product cards display
- [ ] Type "murah" → cheapest items display
- [ ] Type "invalid" → default reply shown

### 2. Product Card Display
- [ ] Product images load correctly
- [ ] Product names visible
- [ ] Seller names visible (dari X)
- [ ] Prices formatted (Rp XXXXX)
- [ ] No broken image icons
- [ ] Default image shows for missing images

### 3. Click-to-Increment Behavior
- [ ] Click product card once → quantity badge shows "1"
- [ ] Click same card again → quantity badge updates to "2"
- [ ] Click again → quantity badge updates to "3"
- [ ] Badge animates (pulse effect) on each click
- [ ] Different products show separate badges

### 4. Visual Feedback
- [ ] Hover over card → card lifts and shadow increases
- [ ] Card border highlights on hover
- [ ] Badge appears with animation
- [ ] Badge has red gradient background
- [ ] Badge positioned at top-right of card

### 5. Multiple Products
- [ ] Browse 2-3 product categories
- [ ] Each category shows 3 items
- [ ] Can click items from different categories
- [ ] Cart tracks multiple products correctly

### 6. Browser Console
- [ ] Open DevTools (F12)
- [ ] No JavaScript errors
- [ ] Click product → console shows: "Added to cart: [name] x[qty]"
- [ ] Console doesn't show 404 errors for images

### 7. Database Integration
- [ ] Products load from database
- [ ] Images from database display
- [ ] Seller names from database show correctly
- [ ] Prices from database are accurate
- [ ] Category filtering works

### 8. Sound Notification
- [ ] Bot message received → notification sound plays
- [ ] Sound volume reasonable
- [ ] Sound plays on each bot reply

### 9. Responsive Design
- [ ] Chat container centered on desktop
- [ ] Chat readable on mobile (320px+)
- [ ] Input field functional on mobile
- [ ] Product cards stack properly
- [ ] Images scale responsively

### 10. Error Handling
- [ ] Server error message if DB disconnects
- [ ] Missing images show default gracefully
- [ ] Empty input doesn't send message
- [ ] Typing indicator shows while loading

---

## Performance Checklist

- [ ] Chat loads in < 2 seconds
- [ ] Product cards render smoothly
- [ ] Click response instant (< 100ms)
- [ ] Badge animation smooth (60fps)
- [ ] No memory leaks (DevTools Memory)
- [ ] No excessive DOM repaints
- [ ] Images lazy load properly

---

## Security Checklist

- [ ] SQL injection prevention:
  - [ ] `htmlspecialchars()` used in PHP
  - [ ] User input escaped
- [ ] XSS prevention:
  - [ ] User messages escaped before display
  - [ ] Bot HTML allowed (intentional for cards)
- [ ] No sensitive data logged
- [ ] Database credentials not exposed
- [ ] No direct database access from frontend

---

## Code Review Checklist

### PHP (chat.php)
- [ ] `generateProductCard()` function creates valid HTML
- [ ] All database columns selected (id, product_name, seller_name, price, image)
- [ ] Image fallback works if file missing
- [ ] Product cards formatted correctly
- [ ] No syntax errors

### JavaScript (chat.js)
- [ ] `const cart = {}` initializes empty
- [ ] Click handler attaches to product cards
- [ ] `handleProductCardClick()` increments correctly
- [ ] Data attributes read correctly
- [ ] Badge updates in DOM
- [ ] Console logging works for debugging
- [ ] No infinite loops or memory leaks

### CSS (style.css)
- [ ] `.product-card` styles applied
- [ ] `.product-quantity-badge` positioned correctly
- [ ] Badge hides when data-qty="0"
- [ ] Badge shows when data-qty > 0
- [ ] `@keyframes badgePulse` animation smooth
- [ ] Hover effects work
- [ ] No conflicting styles

---

## Documentation Checklist

- [ ] README.md complete and accurate
- [ ] IMPLEMENTATION_GUIDE.md explains flow
- [ ] Code comments explain logic
- [ ] Database schema documented
- [ ] API endpoints documented
- [ ] User flow documented

---

## Deployment Instructions

### Local Testing
1. Copy project to web server (XAMPP/WAMP)
2. Import `database_setup.sql` into MySQL
3. Update `koneksi.php` if needed
4. Place product images in `/images/`
5. Open `http://localhost/chatbot1/` in browser
6. Run through testing checklist

### Production Deployment
1. Copy files to production server
2. Update database credentials in `koneksi.php`
3. Import database schema
4. Add production product images
5. Set proper file permissions (755 for folders, 644 for files)
6. Test all functionality
7. Monitor error logs

### Environment Variables (Optional Enhancement)
Consider moving DB credentials to `.env` file:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=buyers_are_kings
```

---

## Post-Deployment

- [ ] Monitor application logs
- [ ] Check database performance
- [ ] Get user feedback
- [ ] Measure conversion rate (clicks to purchase)
- [ ] Track common user flows
- [ ] Optimize slow queries if needed

---

## Known Limitations & Future Work

### Current Limitations
- ⚠️ Cart stored only in memory (resets on refresh)
- ⚠️ No checkout functionality
- ⚠️ No user authentication
- ⚠️ No order history
- ⚠️ No payment integration

### Future Enhancements
- [ ] Add localStorage to persist cart
- [ ] Implement checkout page
- [ ] User login system
- [ ] Order tracking
- [ ] Payment gateway
- [ ] Admin dashboard
- [ ] Product search/filter
- [ ] Wishlist feature
- [ ] Customer reviews
- [ ] Promotional codes

---

## Support & Troubleshooting

### Issue: "Koneksi gagal" Error
**Solution:** Check database credentials in `koneksi.php`
```php
$host = "localhost";
$user = "root";
$pass = "";
$db = "buyers_are_kings";
```

### Issue: Products Show as Text, Not Cards
**Solution:** Verify `database_setup.sql` ran successfully
- Check `products` table has `image` column
- Verify sample data inserted

### Issue: Images Not Loading
**Solution:** Check file paths and permissions
- Verify images exist in `/images/` folder
- File names match database entries
- Check file permissions (644)
- Verify relative paths work

### Issue: Click Not Incrementing Quantity
**Solution:** Debug with browser console
- Press F12 → Console
- Click product
- Should see: "Added to cart: [name] x[qty]"
- Check for JavaScript errors

### Issue: Notification Sound Not Playing
**Solution:** Check audio file and permissions
- Verify `mixkit-correct-answer-tone-2870.wav` exists
- Check browser volume
- Check browser permissions for audio
- Test with different browser

---

## Rollback Plan

If deployment fails:
1. Keep backup of old files
2. Revert code changes from Git
3. Restore database backup
4. Test previous version
5. Identify issue before re-deploying

---

## Sign-Off

- [ ] Developer: _____________ Date: _______
- [ ] QA Tester: ____________ Date: _______
- [ ] Project Manager: _______ Date: _______

Ready for deployment! 🚀👑
