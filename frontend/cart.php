<?php
session_start();
include("../backend/config/db.php");

 $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
 $cart_count = array_sum(array_column($cart, 'quantity'));
 $total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart — BeatsShop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        gold: { 50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',800:'#92400e',900:'#78350f' },
        surface: { 900:'#0a0a0f',800:'#101018',700:'#16161f',600:'#1c1c28',500:'#222230',400:'#2a2a3a',300:'#35354a' }
      }
    }
  }
}
</script>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Plus Jakarta Sans',sans-serif; background:#0a0a0f; color:#fff; }
  .nav-blur { background:rgba(10,10,15,0.75); backdrop-filter:blur(20px); border-bottom:1px solid rgba(255,255,255,0.04); }
  .cart-card { background:#101018; border:1px solid rgba(255,255,255,0.04); border-radius:18px; transition:all 0.3s ease; }
  .cart-card:hover { border-color:rgba(251,191,36,0.1); }
  .text-shimmer { background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24); background-size:200% auto; -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; animation:shimmer 3s linear infinite; }
  @keyframes shimmer { to { background-position:200% center; } }
  .btn-cart { background:linear-gradient(135deg,#fbbf24,#f59e0b); color:#0a0a0f; font-weight:700; border:none; border-radius:14px; transition:all 0.25s ease; }
  .btn-cart:hover { box-shadow:0 8px 30px -4px rgba(251,191,36,0.5); transform:translateY(-2px); }
  .qty-btn { width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; font-size:0.8rem; }
  .qty-btn:hover { background:rgba(251,191,36,0.1); border-color:rgba(251,191,36,0.3); color:#fbbf24; }
  .remove-btn { color:rgba(255,255,255,0.2); transition:all 0.2s; cursor:pointer; }
  .remove-btn:hover { color:#ef4444; }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50 px-6 lg:px-10 py-4">
  <div class="max-w-7xl mx-auto flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-3 text-decoration-none">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center">
        <i class="fa-solid fa-headphones text-surface-900 text-sm"></i>
      </div>
      <span class="text-lg font-extrabold text-white tracking-tight">Beats<span class="text-gold-400">Shop</span></span>
    </a>
    <div class="flex items-center gap-3">
      <a href="index.php" class="hidden sm:flex items-center gap-2 text-sm font-medium text-white/50 hover:text-white transition px-3 py-2 rounded-xl hover:bg-white/5">
        <i class="fa-solid fa-house text-xs"></i> Home
      </a>
      <a href="cart.php" class="relative w-10 h-10 rounded-xl bg-gold-500/10 flex items-center justify-center transition text-gold-400">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($cart_count > 0) { ?>
          <span class="absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $cart_count ?></span>
        <?php } ?>
      </a>
    </div>
  </div>
</nav>

<main class="pt-24 pb-20">
  <div class="max-w-5xl mx-auto px-6 lg:px-10">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
      <a href="index.php" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/50 hover:text-white">
        <i class="fa-solid fa-arrow-left text-sm"></i>
      </a>
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Shopping Cart</h1>
        <p class="text-sm text-white/25 mt-0.5"><?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?> in your cart</p>
      </div>
    </div>

    <?php if (empty($cart)) { ?>

    <!-- Empty Cart -->
    <div class="flex flex-col items-center justify-center py-20">
      <div class="w-24 h-24 rounded-3xl bg-surface-800 border border-white/[0.04] flex items-center justify-center mb-6">
        <i class="fa-solid fa-bag-shopping text-3xl text-white/10"></i>
      </div>
      <h3 class="text-lg font-bold text-white/40">Your cart is empty</h3>
      <p class="text-sm text-white/15 mt-1 mb-6">Looks like you haven't added anything yet</p>
      <a href="index.php" class="btn-cart px-8 py-3 text-sm flex items-center gap-2">
        <i class="fa-solid fa-arrow-left text-xs"></i> Continue Shopping
      </a>
    </div>

    <?php } else { ?>

    <!-- Cart Items -->
    <div class="space-y-4 mb-8">

      <?php 
      $index = 0;
      foreach ($cart as $item) { 
        $subtotal = $item['price'] * $item['quantity'];
      ?>
      <div class="cart-card p-4 flex gap-4 sm:gap-6">
        
        <!-- Image -->
        <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="w-24 h-24 sm:w-32 sm:h-32 rounded-xl overflow-hidden shrink-0 bg-surface-700">
          <img src="../backend/uploads/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" 
               class="w-full h-full object-cover"
               onerror="this.src='https://picsum.photos/seed/<?= $item['product_id'] ?>/200/200.jpg'">
        </a>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-3">
            <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="text-sm sm:text-base font-bold text-white hover:text-gold-400 transition truncate">
              <?= htmlspecialchars($item['product_name']) ?>
            </a>
            <button onclick="removeFromCart(<?= $index ?>)" class="remove-btn p-1 shrink-0" title="Remove">
              <i class="fa-solid fa-trash-can text-xs"></i>
            </button>
          </div>
          
          <p class="text-lg font-extrabold text-gold-400 mt-1">Rs. <?= number_format($item['price'], 0) ?></p>
          
          <div class="flex items-center justify-between mt-3">
            <!-- Qty Controls -->
            <div class="flex items-center gap-2 bg-white/[0.03] border border-white/[0.06] rounded-xl px-2 py-1">
              <button class="qty-btn" onclick="updateQty(<?= $index ?>, -1)">
                <i class="fa-solid fa-minus text-[10px]"></i>
              </button>
              <span class="text-sm font-bold text-white w-6 text-center"><?= $item['quantity'] ?></span>
              <button class="qty-btn" onclick="updateQty(<?= $index ?>, 1)">
                <i class="fa-solid fa-plus text-[10px]"></i>
              </button>
            </div>
            
            <!-- Subtotal -->
            <span class="text-sm font-bold text-white/60">Rs. <?= number_format($subtotal, 0) ?></span>
          </div>
        </div>

      </div>
      <?php $index++; } ?>

    </div>

    <!-- Summary -->
    <div class="cart-card p-6 sm:p-8">
      <h3 class="text-base font-bold text-white mb-4">Order Summary</h3>
      
      <div class="space-y-3 mb-6">
        <div class="flex justify-between">
          <span class="text-sm text-white/30">Subtotal (<?= $cart_count ?> items)</span>
          <span class="text-sm font-semibold text-white/70">Rs. <?= number_format($total, 0) ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-sm text-white/30">Delivery</span>
          <span class="text-sm font-semibold text-green-400">FREE</span>
        </div>
        <div class="flex justify-between">
          <span class="text-sm text-white/30">Discount</span>
          <span class="text-sm font-semibold text-red-400">- Rs. <?= number_format($total * 0.3, 0) ?></span>
        </div>
        <div class="h-px bg-white/[0.04]"></div>
        <div class="flex justify-between">
          <span class="text-base font-bold text-white">Total</span>
          <span class="text-xl font-extrabold text-shimmer">Rs. <?= number_format($total, 0) ?></span>
        </div>
      </div>

      <button onclick="checkout()" class="btn-cart w-full py-4 text-sm flex items-center justify-center gap-2">
        <i class="fa-solid fa-lock text-xs"></i> Proceed to Checkout
      </button>

      <button onclick="clearCart()" class="w-full mt-3 py-3 text-sm font-semibold text-white/20 hover:text-red-400 transition">
        Clear Cart
      </button>
    </div>

    <?php } ?>

  </div>
</main>

<script>
function updateQty(index, delta) {
  const formData = new FormData();
  formData.append('index', index);
  formData.append('delta', delta);
  
  fetch('update_cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) location.reload();
    });
}

function removeFromCart(index) {
  if (!confirm('Remove this item from cart?')) return;
  const formData = new FormData();
  formData.append('index', index);
  
  fetch('remove_from_cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) location.reload();
    });
}

function clearCart() {
  if (!confirm('Clear all items from cart?')) return;
  fetch('clear_cart.php', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.success) location.reload();
    });
}

function checkout() {
  alert('Checkout feature coming soon!');
}
</script>

</body>
</html>