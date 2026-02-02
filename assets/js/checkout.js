// ================= GET CART FROM PAGE =================
let pageCart = {};

function getCartFromPage() {
    // Parse cart dari product cards
    const items = document.querySelectorAll('.cart-item');
    items.forEach(item => {
        const productId = item.getAttribute('data-product-id');
        const input = item.querySelector('.item-quantity input');
        if (input) {
            pageCart[productId] = parseInt(input.value);
        }
    });
    return pageCart;
}

// Function untuk recalculate total
function recalculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.cart-item').forEach(item => {
        const priceText = item.querySelector('.item-price').textContent;
        const price = parseInt(priceText.replace(/[^0-9]/g, ''));
        const qty = parseInt(item.querySelector('.item-quantity input').value);
        subtotal += price * qty;
    });
    
    const tax = Math.floor(subtotal * 0.1);
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('tax').textContent = 'Rp ' + tax.toLocaleString('id-ID');
    document.getElementById('total').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', function() {
    getCartFromPage();
    recalculateTotal();
});

// ================= UPDATE QUANTITY =================
function updateQty(productId, newQty) {
    if (newQty <= 0) {
        removeItem(productId);
        return;
    }

    // Update cart locally
    pageCart[productId] = newQty;
    
    // Update input field
    const item = document.querySelector(`[data-product-id="${productId}"]`);
    if (item) {
        item.querySelector('.item-quantity input').value = newQty;
    }
    
    // Recalculate total
    recalculateTotal();

    // Submit updated cart
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = location.href;
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'cart';
    input.value = JSON.stringify(pageCart);
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// ================= REMOVE ITEM =================
function removeItem(productId) {
    if (confirm('Yakin hapus item ini dari keranjang?')) {
        delete pageCart[productId];
        
        // Submit updated cart
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = location.href;
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cart';
        input.value = JSON.stringify(pageCart);
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// ================= SUBMIT ORDER =================
function submitOrder() {
    const name = document.getElementById('customer-name').value.trim();
    const email = document.getElementById('customer-email').value.trim();
    const phone = document.getElementById('customer-phone').value.trim();
    const notes = document.getElementById('customer-notes').value.trim();

    // Validation
    if (!name) {
        alert('Nama lengkap harus diisi');
        return;
    }
    if (!email) {
        alert('Email harus diisi');
        return;
    }
    if (!phone) {
        alert('Nomor HP harus diisi');
        return;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Email tidak valid');
        return;
    }

    const btn = document.getElementById('order-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Memproses...';

    // Submit order
    fetch('../api/checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=create_order&customer_name=' + encodeURIComponent(name) + 
              '&customer_email=' + encodeURIComponent(email) + 
              '&customer_phone=' + encodeURIComponent(phone) + 
              '&customer_notes=' + encodeURIComponent(notes)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('✓ Pesanan berhasil dibuat! No: ' + data.order_id, 'success');
            sessionStorage.removeItem('buyersCart');
            setTimeout(() => {
                window.location.href = '../index.php';
            }, 2000);
        } else {
            showNotification('❌ Gagal membuat pesanan: ' + data.message, 'error');
            btn.disabled = false;
            btn.textContent = '✓ Pesan Sekarang';
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('❌ Error: ' + err);
        btn.disabled = false;
        btn.textContent = '✓ Pesan Sekarang';
    });
}
