const form = document.getElementById("chat-form");
const input = document.getElementById("message");
const chatBox = document.getElementById("chat-box");
const notifSound = document.getElementById("notifSound");

// ================= CART STATE MANAGEMENT =================
// Store cart in sessionStorage (persists during session)
let cart = {};

// Load cart from sessionStorage on page load
function loadCart() {
    const savedCart = sessionStorage.getItem('buyersCart');
    if (savedCart) {
        try {
            cart = JSON.parse(savedCart);
        } catch (e) {
            cart = {};
        }
    }
    // Update cart widget after loading
    setTimeout(updateCartSummary, 500);
}

// Save cart to sessionStorage
function saveCart() {
    sessionStorage.setItem('buyersCart', JSON.stringify(cart));
    updateCartSummary(); // Update widget setiap kali cart berubah
}

// Load cart on init
document.addEventListener('DOMContentLoaded', loadCart);
window.addEventListener('load', loadCart);

// ================= GET CURRENT TIME =================
function getTime() {
    const now = new Date();
    let h = now.getHours().toString().padStart(2, "0");
    let m = now.getMinutes().toString().padStart(2, "0");
    return h + ":" + m;
}

// ================= ESCAPE USER TEXT =================
function escapeHTML(text) {
    const div = document.createElement("div");
    div.innerText = text;
    return div.innerHTML;
}

// ================= PLAY CLICK SOUND =================
function playClickSound() {
    if (notifSound) {
        try {
            notifSound.currentTime = 0;
            notifSound.play();
        } catch (e) {
            console.log('Sound play error:', e);
        }
    }
}

// ================= NOTIFICATION SYSTEM =================
function showNotification(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `notification-toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.5s ease forwards';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// ================= QUICK FILTERS =================
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            let message = '';
            
            switch(category) {
                case 'rice':
                    message = 'nasi murah';
                    break;
                case 'drink':
                    message = 'minuman termurah';
                    break;
                case 'sweet':
                    message = 'makanan manis';
                    break;
                case 'snack':
                    message = 'cemilan';
                    break;
                case 'cheap':
                    message = 'murah';
                    break;
                case 'expensive':
                    message = 'expensive';
                    break;
                case 'all':
                    message = 'all menu';
                    break;
            }
            
            if (message) {
                input.value = message;
                form.dispatchEvent(new Event('submit'));
                showNotification(`Filter applied: ${this.textContent}`, 'info');
            }
        });
    });
});

// ================= ADD MESSAGE =================
function addMessage(text, sender = "user") {

    const time = getTime();
    const div = document.createElement("div");
    div.className = "chat " + sender;

    // User = plain text | Bot = HTML allowed
    const content = sender === "user"
        ? escapeHTML(text)
        : text;

    div.innerHTML = `
        <div class="chat-bubble">
            ${content}
            <div class="chat-time">${time}</div>
        </div>
    `;

    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;

    // ================= ATTACH PRODUCT CARD CLICK HANDLERS =================
    // Find all product cards in the newly added bot message
    if (sender === "bot") {
        const productCards = div.querySelectorAll(".product-card");
        productCards.forEach(card => {
            card.addEventListener("click", handleProductCardClick);
        });
    }
}

// ================= SHOW TYPING =================
function showTyping() {

    const div = document.createElement("div");
    div.className = "chat bot";
    div.id = "typing";

    div.innerHTML = `
        <div class="chat-bubble typing">
            Bot sedang mengetik
            <span>.</span>
            <span>.</span>
            <span>.</span>
        </div>
    `;

    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// ================= REMOVE TYPING =================
function removeTyping() {
    const typing = document.getElementById("typing");
    if (typing) typing.remove();
}

// ================= HANDLE PRODUCT CARD CLICK =================
// User clicks on a product card to increment quantity (add to cart)
function handleProductCardClick(e) {
    // Check if clicking on button
    if (e.target.classList.contains('qty-btn')) {
        const productId = e.target.getAttribute('data-product-id');
        const productCard = document.querySelector(`[data-product-id="${productId}"]`);

        if (e.target.classList.contains('qty-plus')) {
            // Increment
            cart[productId] = (cart[productId] || 0) + 1;
        } else if (e.target.classList.contains('qty-minus')) {
            // Decrement
            cart[productId] = Math.max(0, (cart[productId] || 0) - 1);
            if (cart[productId] === 0) {
                delete cart[productId];
                // Hide controls if quantity is 0
                const controls = productCard.querySelector('.quantity-controls');
                if (controls) controls.style.display = 'none';
            }
        }

        updateProductDisplay(productCard, productId);
        saveCart();
        return;
    }

    // Prevent triggering if clicking on the image or controls
    if (e.target.classList.contains("product-img") ||
        e.target.classList.contains("qty-display")) {
        return;
    }

    const productCard = this;
    const productId = productCard.getAttribute("data-product-id");
    const productName = productCard.getAttribute("data-product-name");
    const price = productCard.getAttribute("data-price");

    // Initialize quantity to 0 if not in cart yet
    if (!cart.hasOwnProperty(productId)) {
        cart[productId] = 0;
    }

    // Increment quantity
    cart[productId]++;

    // Show quantity controls on first click
    const controls = productCard.querySelector('.quantity-controls');
    if (controls && cart[productId] >= 1) {
        controls.style.display = 'flex';
    }

    updateProductDisplay(productCard, productId);
    playClickSound();
    saveCart();
    showNotification(`✓ ${productName} ditambahkan ke keranjang`, 'success');

    // Add visual feedback (optional console log for debugging)
    console.log(`Added to cart: ${productName} x${cart[productId]} (ID: ${productId})`);
}

// Helper function to update product card display
function updateProductDisplay(productCard, productId) {
    const qty = cart[productId] || 0;

    // Update data attribute
    productCard.setAttribute("data-qty", qty);

    // Update badge
    const badge = productCard.querySelector(".product-quantity-badge");
    if (badge) {
        badge.textContent = qty;
        badge.setAttribute('data-qty', qty);
    }

    // Update controls display
    const qtyDisplay = productCard.querySelector('.qty-display');
    if (qtyDisplay) {
        qtyDisplay.textContent = qty;
    }

    // Add animation class
    productCard.classList.add("qty-updated");
    setTimeout(() => {
        productCard.classList.remove("qty-updated");
    }, 500);
}

// ================= SEND CHAT =================
form.addEventListener("submit", function (e) {

    e.preventDefault();

    let message = input.value.trim();
    if (message === "") return;

    addMessage(message, "user");

    input.value = "";
    input.focus();

    showTyping();

    fetch("api/chat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "message=" + encodeURIComponent(message)
    })
        .then(res => res.text())
        .then(data => {

            setTimeout(() => {

                removeTyping();

                addMessage(data, "bot");

                // PLAY SOUND
                if (notifSound) {
                    notifSound.currentTime = 0;
                    notifSound.play().catch(e => console.log('Sound play blocked:', e));
                }

            }, 700);

        })
        .catch(() => {
            removeTyping();
            addMessage("Server error. Coba lagi ya 🙏", "bot");
        });

});

// ================= ADD CHECKOUT BUTTON =================
function addCheckoutButton() {
    if (Object.keys(cart).length > 0) {
        // Create cart button
        let cartBtn = document.getElementById("cart-btn");
        if (!cartBtn) {
            const form = document.getElementById("chat-form");
            cartBtn = document.createElement("button");
            cartBtn.id = "cart-btn";
            cartBtn.type = "button";
            cartBtn.innerHTML = "🛒 (" + Object.keys(cart).length + ")";
            cartBtn.style.cssText = "background:#ff6b6b;color:white;border:none;padding:0 15px;cursor:pointer;font-weight:600;transition:background 0.3s;border-radius:8px;padding:10px 15px;";
            cartBtn.onmouseover = () => cartBtn.style.background = "#ff5252";
            cartBtn.onmouseout = () => cartBtn.style.background = "#ff6b6b";
            cartBtn.onclick = () => {
                // Validate cart is not empty
                if (Object.keys(cart).length === 0) {
                    alert('Keranjang masih kosong! Pilih produk terlebih dahulu.');
                    return;
                }

                // Pass cart data ke checkout via form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'pages/checkout.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'cart_json'; // Changed from 'cart' to 'cart_json'
                input.value = JSON.stringify(cart);

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            };
            form.parentNode.insertBefore(cartBtn, form.nextSibling);
        } else {
            cartBtn.innerHTML = "🛒 (" + Object.keys(cart).length + ")";
        }
    }
}

// ================= UPDATE CART SUMMARY WIDGET =================
function updateCartSummary() {
    let totalItems = 0;
    let totalPrice = 0;

    // Calculate totals
    for (let productId in cart) {
        const qty = cart[productId];
        const productCard = document.querySelector(`[data-product-id="${productId}"]`);

        // If product card is visible, we can get price from it. 
        // If not (e.g. after reload), we might need to rely on stored data or just show item count.
        // For this prototype, we'll try to get price from DOM or data attributes if available.
        if (productCard) {
            const price = parseInt(productCard.getAttribute('data-price'));
            totalItems += qty;
            totalPrice += price * qty;
        } else {
            // Fallback: we count items but might miss price if card not loaded
            // In a full app, we'd have a product database in JS or fetch from server.
            totalItems += qty;
            // For now, we won't add to totalPrice if card not found to avoid wrong calculation
        }
    }

    let widget = document.getElementById('cart-summary');

    if (totalItems === 0) {
        if (widget) widget.style.display = 'none';
        return;
    }

    if (!widget) {
        widget = document.createElement('div');
        widget.id = 'cart-summary';
        widget.className = 'cart-summary-widget';
        document.body.appendChild(widget);
    }

    widget.innerHTML = `
        <div class="cart-summary-header">
            <span>🛒 Ringkasan</span>
            <span style="font-size:12px;color:#888;cursor:pointer" onclick="document.getElementById('cart-summary').style.display='none'">✕</span>
        </div>
        <div class="cart-summary-body">
            <div class="cart-row">
                <span>Total Item</span>
                <span>${totalItems}</span>
            </div>
            <div class="cart-total">
                Rp ${totalPrice.toLocaleString()}
            </div>
        </div>
        <button class="checkout-btn" onclick="goToCheckout()">Checkout</button>
    `;

    widget.style.display = 'block';
}

function goToCheckout() {
    // Validate cart is not empty
    if (Object.keys(cart).length === 0) {
        alert('Keranjang masih kosong! Pilih produk terlebih dahulu.');
        return;
    }

    // Pass cart data ke checkout via form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'pages/checkout.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'cart_json';
    input.value = JSON.stringify(cart);

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// Check cart on page load
// addCheckoutButton(); // Replaced by widget
updateCartSummary();

// ================= ENTER SEND =================
input.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
        e.preventDefault();
        form.dispatchEvent(new Event("submit"));
    }
});
