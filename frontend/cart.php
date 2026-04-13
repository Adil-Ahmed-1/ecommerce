<?php
session_start();

/* HANDLE CART ACTIONS */
if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if (isset($_SESSION['cart'][$id])) {

        if ($action === "plus") {
            $_SESSION['cart'][$id]['qty']++;
        }

        if ($action === "minus") {
            $_SESSION['cart'][$id]['qty']--;
            if ($_SESSION['cart'][$id]['qty'] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        }

        if ($action === "delete") {
            unset($_SESSION['cart'][$id]);
        }
    }

    header("Location: cart.php");
    exit();
}

 $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
 $total = 0;
 $item_count = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
    $item_count += $item['qty'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart — BeatsShop</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        gold: {
          50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',
          400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',
          800:'#92400e',900:'#78350f'
        },
        surface: {
          900:'#0a0a0f',800:'#101018',700:'#16161f',600:'#1c1c28',
          500:'#222230',400:'#2a2a3a',300:'#35354a'
        }
      }
    }
  }
}
</script>

<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Plus Jakarta Sans',sans-serif; background:#0a0a0f; color:#fff; }

  ::-webkit-scrollbar { width:8px; }
  ::-webkit-scrollbar-track { background:#0a0a0f; }
  ::-webkit-scrollbar-thumb { background:#2a2a3a; border-radius:99px; }

  .nav-blur {
    background:rgba(10,10,15,0.75);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(255,255,255,0.04);
  }

  /* Cart item */
  .cart-item {
    background:#101018;
    border:1px solid rgba(255,255,255,0.04);
    border-radius:20px;
    transition:all 0.3s ease;
  }
  .cart-item:hover {
    border-color:rgba(255,255,255,0.08);
    background:#12121a;
  }

  /* Remove animation */
  .cart-item.removing {
    animation:slideOut 0.35s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes slideOut {
    to { opacity:0;transform:translateX(40px) scale(0.96);height:0;margin:0;padding:0;overflow:hidden; }
  }

  /* Qty button */
  .qty-btn {
    width:36px;height:36px;border-radius:10px;
    background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);
    color:#fff;display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:all 0.2s;font-size:0.8rem;text-decoration:none;
  }
  .qty-btn:hover {
    background:rgba(251,191,36,0.1);border-color:rgba(251,191,36,0.3);
    color:#fbbf24;
  }

  /* Delete button */
  .del-btn {
    width:36px;height:36px;border-radius:10px;
    background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.1);
    color:rgba(239,68,68,0.5);display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:all 0.2s;text-decoration:none;
  }
  .del-btn:hover {
    background:rgba(239,68,68,0.15);border-color:rgba(239,68,68,0.3);
    color:#ef4444;
  }

  /* Checkout button */
  .btn-checkout {
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;font-weight:700;border:none;border-radius:16px;
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;
  }
  .btn-checkout::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);
    transition:left 0.5s ease;
  }
  .btn-checkout:hover::before { left:100%; }
  .btn-checkout:hover {
    box-shadow:0 8px 30px -4px rgba(251,191,36,0.5);
    transform:translateY(-2px);
  }

  /* Continue shopping */
  .btn-ghost {
    background:transparent;border:1px solid rgba(255,255,255,0.1);
    color:rgba(255,255,255,0.5);border-radius:16px;font-weight:600;
    transition:all 0.25s ease;text-decoration:none;
  }
  .btn-ghost:hover {
    background:rgba(255,255,255,0.04);
    border-color:rgba(255,255,255,0.15);
    color:#fff;
  }

  /* Clear all */
  .btn-clear {
    color:rgba(239,68,68,0.4);font-size:0.8rem;font-weight:600;
    text-decoration:none;transition:color 0.2s;
  }
  .btn-clear:hover { color:#ef4444; }

  /* Summary card */
  .summary-card {
    background:#101018;
    border:1px solid rgba(255,255,255,0.04);
    border-radius:24px;
    position:sticky;top:100px;
  }

  /* Text shimmer */
  .text-shimmer {
    background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24);
    background-size:200% auto;
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    animation:shimmer 3s linear infinite;
  }
  @keyframes shimmer { to { background-position:200% center; } }

  /* Fade in */
  .fade-up {
    opacity:0;transform:translateY(16px);
    animation:fadeUp 0.5s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  /* Empty bounce */
  .empty-bounce { animation:eBounce 2s ease-in-out infinite; }
  @keyframes eBounce {
    0%,100% { transform:translateY(0); }
    50% { transform:translateY(-10px); }
  }

  /* Divider */
  .divider {
    height:1px;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent);
  }

  /* Product image */
  .cart-img {
    width:90px;height:90px;border-radius:14px;object-fit:cover;
    border:2px solid rgba(255,255,255,0.04);
    transition:border-color 0.2s;
  }
  .cart-item:hover .cart-img {
    border-color:rgba(251,191,36,0.15);
  }

  /* Savings badge */
  .savings-badge {
    background:linear-gradient(135deg,rgba(34,197,94,0.1),rgba(34,197,94,0.05));
    border:1px solid rgba(34,197,94,0.15);
    color:#22c55e;
  }

  /* Confetti (for empty after clear) */
  @keyframes confettiFall {
    0% { transform:translateY(-20px) rotate(0deg);opacity:1; }
    100% { transform:translateY(60px) rotate(360deg);opacity:0; }
  }
</style>

</head>

<body>

<!-- ========== NAVBAR ========== -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50 px-6 lg:px-10 py-4">
  <div class="max-w-7xl mx-auto flex items-center justify-between">

    <a href="index.php" class="flex items-center gap-3 text-decoration-none">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-lg shadow-gold-500/20">
        <i class="fa-solid fa-headphones text-surface-900 text-sm"></i>
      </div>
      <span class="text-lg font-extrabold text-white tracking-tight">Beats<span class="text-gold-400">Shop</span></span>
    </a>

    <div class="flex items-center gap-3">
      <a href="index.php" class="hidden sm:flex items-center gap-2 text-sm font-medium text-white/50 hover:text-white transition px-3 py-2 rounded-xl hover:bg-white/5">
        <i class="fa-solid fa-house text-xs"></i> Home
      </a>
      <a href="cart.php" class="relative w-10 h-10 rounded-xl bg-gold-500/10 border border-gold-500/20 flex items-center justify-center transition text-gold-400">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($item_count > 0) { ?>
          <span class="absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $item_count ?></span>
        <?php } ?>
      </a>
    </div>

  </div>
</nav>


<!-- ========== MAIN ========== -->
<main class="pt-24 pb-20 min-h-screen">

  <div class="max-w-7xl mx-auto px-6 lg:px-10">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8 fade-up">

      <div>
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-gold-500/10 flex items-center justify-center">
            <i class="fa-solid fa-bag-shopping text-gold-400"></i>
          </div>
          <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Shopping Cart</h1>
            <p class="text-xs text-white/30 mt-0.5"><?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?> in your cart</p>
          </div>
        </div>
      </div>

      <?php if (!empty($cart)) { ?>
        <a href="cart.php?action=delete&clear=1" class="btn-clear flex items-center gap-1.5" onclick="return confirmClear(event)">
          <i class="fa-solid fa-trash-can text-[10px]"></i> Clear All
        </a>
      <?php } ?>

    </div>


    <?php if (!empty($cart)) { ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- LEFT — Cart Items -->
      <div class="lg:col-span-2 space-y-4">

        <?php
          $delay = 0;
          foreach ($cart as $id => $item) {
            $delay += 0.05;
            $subtotal = $item['price'] * $item['qty'];
            $imgPath = "../backend/uploads/" . $item['image'];
        ?>

        <div class="cart-item fade-up p-5" style="animation-delay:<?= $delay ?>s" id="item-<?= $id ?>">

          <div class="flex items-center gap-5">

            <!-- Image -->
            <a href="product_detail.php?id=<?= $id ?>" class="shrink-0">
              <img
                src="<?= $imgPath ?>"
                alt="<?= htmlspecialchars($item['name']) ?>"
                class="cart-img"
                onerror="this.src='https://picsum.photos/seed/<?= $id ?>/100/100.jpg'"
              >
            </a>

            <!-- Info -->
            <div class="flex-1 min-w-0">

              <a href="product_detail.php?id=<?= $id ?>" class="text-sm font-bold text-white hover:text-gold-400 transition truncate block">
                <?= htmlspecialchars($item['name']) ?>
              </a>

              <p class="text-xs text-white/25 mt-0.5">Rs. <?= number_format($item['price'], 0) ?> each</p>

              <!-- Qty Controls -->
              <div class="flex items-center gap-2 mt-3">

                <a href="cart.php?id=<?= $id ?>&action=minus" class="qty-btn">
                  <i class="fa-solid fa-minus text-[10px]"></i>
                </a>

                <span class="w-10 text-center text-sm font-bold text-white"><?= $item['qty'] ?></span>

                <a href="cart.php?id=<?= $id ?>&action=plus" class="qty-btn">
                  <i class="fa-solid fa-plus text-[10px]"></i>
                </a>

                <!-- Subtotal (mobile: show here) -->
                <span class="sm:hidden ml-auto text-sm font-extrabold text-gold-400">Rs. <?= number_format($subtotal, 0) ?></span>

              </div>

            </div>

            <!-- Right side (desktop) -->
            <div class="hidden sm:flex flex-col items-end gap-3 shrink-0">

              <a href="cart.php?id=<?= $id ?>&action=delete" class="del-btn" title="Remove">
                <i class="fa-solid fa-xmark text-xs"></i>
              </a>

              <span class="text-base font-extrabold text-gold-400">Rs. <?= number_format($subtotal, 0) ?></span>

            </div>

            <!-- Delete (mobile) -->
            <a href="cart.php?id=<?= $id ?>&action=delete" class="sm:hidden del-btn shrink-0" title="Remove">
              <i class="fa-solid fa-xmark text-xs"></i>
            </a>

          </div>

        </div>

        <?php } ?>

        <!-- Continue Shopping -->
        <div class="pt-2">
          <a href="index.php" class="btn-ghost inline-flex items-center gap-2 px-6 py-3 text-sm">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Continue Shopping
          </a>
        </div>

      </div>

      <!-- RIGHT — Summary -->
      <div class="lg:col-span-1">

        <div class="summary-card p-7 fade-up" style="animation-delay:0.2s">

          <h3 class="text-base font-bold text-white mb-6 flex items-center gap-2">
            <i class="fa-solid fa-receipt text-gold-400 text-sm"></i>
            Order Summary
          </h3>

          <div class="space-y-4">

            <div class="flex items-center justify-between">
              <span class="text-sm text-white/35">Subtotal (<?= $item_count ?> items)</span>
              <span class="text-sm font-semibold text-white/70">Rs. <?= number_format($total, 0) ?></span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-sm text-white/35">Shipping</span>
              <span class="text-sm font-semibold text-green-400">Free</span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-sm text-white/35">Tax (GST)</span>
              <span class="text-sm font-semibold text-white/70">Rs. <?= number_format($total * 0.03, 0) ?></span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-sm text-white/35">Discount</span>
              <span class="savings-badge text-xs font-bold px-2.5 py-1 rounded-lg">- Rs. <?= number_format($total * 0.05, 0) ?></span>
            </div>

          </div>

          <div class="divider my-5"></div>

          <!-- Total -->
          <div class="flex items-end justify-between mb-6">
            <span class="text-sm text-white/50 font-medium">Total</span>
            <div class="text-right">
              <span class="text-3xl font-extrabold text-shimmer">Rs. <?= number_format($total + ($total * 0.03) - ($total * 0.05), 0) ?></span>
              <p class="text-[10px] text-white/20 mt-0.5">Including all taxes</p>
            </div>
          </div>

          <!-- Checkout Button -->
          <a href="checkout.php" class="btn-checkout w-full py-4 text-sm flex items-center justify-center gap-2 text-center">
            <i class="fa-solid fa-lock text-xs"></i>
            Proceed to Checkout
          </a>

          <!-- Trust -->
          <div class="flex items-center justify-center gap-4 mt-5 pt-5 border-t border-white/[0.04]">
            <div class="flex items-center gap-1.5">
              <i class="fa-solid fa-shield-halved text-[10px] text-gold-400/40"></i>
              <span class="text-[10px] text-white/20">Secure</span>
            </div>
            <div class="flex items-center gap-1.5">
              <i class="fa-solid fa-truck-fast text-[10px] text-gold-400/40"></i>
              <span class="text-[10px] text-white/20">Free Ship</span>
            </div>
            <div class="flex items-center gap-1.5">
              <i class="fa-solid fa-rotate-left text-[10px] text-gold-400/40"></i>
              <span class="text-[10px] text-white/20">Returns</span>
            </div>
          </div>

          <!-- Savings callout -->
          <div class="mt-5 bg-green-500/5 border border-green-500/10 rounded-xl px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-piggy-bank text-green-400 text-xs"></i>
            </div>
            <div>
              <p class="text-xs font-semibold text-green-400">You're saving Rs. <?= number_format($total * 0.05, 0) ?></p>
              <p class="text-[10px] text-white/20 mt-0.5">5% discount applied automatically</p>
            </div>
          </div>

        </div>

      </div>

    </div>

    <?php } else { ?>

    <!-- EMPTY STATE -->
    <div class="text-center py-20 fade-up">

      <div class="empty-bounce inline-block mb-6">
        <div class="w-24 h-24 rounded-3xl bg-surface-700 flex items-center justify-center mx-auto">
          <i class="fa-solid fa-bag-shopping text-white/[0.06] text-4xl"></i>
        </div>
      </div>

      <h2 class="text-xl font-bold text-white/30">Your Cart is Empty</h2>
      <p class="text-sm text-white/15 mt-2 max-w-sm mx-auto">Looks like you haven't added anything to your cart yet. Browse our collection and find something you love.</p>

      <div class="flex items-center justify-center gap-3 mt-8">
        <a href="index.php" class="btn-checkout px-8 py-3.5 text-sm inline-flex items-center gap-2">
          <i class="fa-solid fa-headphones text-xs"></i>
          Start Shopping
        </a>
      </div>

    </div>

    <?php } ?>

  </div>

</main>


<!-- ========== FOOTER ========== -->
<footer class="bg-surface-800 border-t border-white/[0.03] py-8">
  <div class="max-w-7xl mx-auto px-6 lg:px-10 flex flex-col sm:flex-row items-center justify-between gap-3">
    <a href="index.php" class="flex items-center gap-2 text-decoration-none">
      <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center">
        <i class="fa-solid fa-headphones text-surface-900 text-[10px]"></i>
      </div>
      <span class="text-sm font-extrabold text-white">Beats<span class="text-gold-400">Shop</span></span>
    </a>
    <p class="text-xs text-white/15">&copy; <?= date('Y') ?> BeatsShop. All rights reserved.</p>
  </div>
</footer>


<!-- ========== SCRIPTS ========== -->
<script>

/* Clear All confirmation */
function confirmClear(e) {
  e.preventDefault();
  const link = e.currentTarget.href;

  /* Custom inline confirm */
  if (e.currentTarget.dataset.confirmed === 'true') {
    window.location.href = link;
    return;
  }

  const btn = e.currentTarget;
  const original = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Click again to confirm';
  btn.style.color = '#ef4444';
  btn.dataset.confirmed = 'true';

  setTimeout(() => {
    btn.innerHTML = original;
    btn.style.color = '';
    btn.dataset.confirmed = 'false';
  }, 3000);

  return false;
}

/* Intersection Observer */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) entry.target.style.animationPlayState = 'running';
  });
}, { threshold: 0.1 });

document.querySelectorAll('.fade-up').forEach(el => {
  el.style.animationPlayState = 'paused';
  observer.observe(el);
});

/* Navbar scroll */
window.addEventListener('scroll', function() {
  const nav = document.querySelector('.nav-blur');
  nav.style.background = window.scrollY > 50 ? 'rgba(10,10,15,0.9)' : 'rgba(10,10,15,0.75)';
});
</script>

</body>
</html>