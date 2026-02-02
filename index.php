<!DOCTYPE html>
<html>
<head>
    <title>Buyers Chatbot</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="chat-container">

    <!-- BOT PROFILE HEADER -->
    <div class="bot-profile-header">
        <img src="images/pprobot.webp" class="bot-avatar">
        <div class="bot-info">
            <div class="bot-name">Buyers Assistant Bot</div>
            <div class="bot-status">Online</div>
        </div>
        <a href="pages/order-history.php" class="my-orders-btn" title="Lihat pesanan saya">
            📦 Order Saya
        </a>
    </div>

    <!-- CHAT BOX -->
    <div id="chat-box" class="chat-box"></div>

    <!-- FORM -->
    <form id="chat-form" class="chat-form">
        <input type="text" id="message" placeholder="Ketik pesan... (contoh: nasi murah, cari ayam)" autocomplete="off">
        <button type="submit">Kirim</button>
    </form>

    <!-- QUICK FILTERS -->
    <div class="quick-filters">
        <button type="button" class="filter-btn" data-category="rice">🍚 Nasi</button>
        <button type="button" class="filter-btn" data-category="drink">🥤 Minum</button>
        <button type="button" class="filter-btn" data-category="sweet">🍰 Manis</button>
        <button type="button" class="filter-btn" data-category="snack">🍿 Snack</button>
        <button type="button" class="filter-btn" data-category="cheap">💰 Termurah</button>
        <button type="button" class="filter-btn" data-category="expensive">💎 Termahal</button>
        <button type="button" class="filter-btn" data-category="all">📋 Semua Menu</button>
    </div>

    <audio id="notifSound">
        <source src="sound/mixkit-correct-answer-tone-2870.wav" type="audio/wav">
    </audio>

</div>

<script src="assets/js/chat.js"></script>

</body>
</html>
