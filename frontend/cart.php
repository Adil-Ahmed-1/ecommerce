<?php
session_start();
include("../backend/config/db.php");

/* ===== LOGIN CHECK ===== */
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

$loginPage = "../user/login.php";
$logoutPage = "../user/logout.php";

$cart = [];
$total = 0;
$cart_count = 0;
$userName = $userEmail = $userInitial = "U";
$showLoginWarning = false;

if ($isLoggedIn) {

    $user_id = $_SESSION['user_id'];

    /* ===== USER ===== */
    $uStmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($uStmt, "i", $user_id);
    mysqli_stmt_execute($uStmt);
    $uResult = mysqli_stmt_get_result($uStmt);

    if ($uRow = mysqli_fetch_assoc($uResult)) {
        $userName = $uRow['name'];
        $userEmail = $uRow['email'];
        $userInitial = strtoupper(substr($userName, 0, 1));
    }

    /* ===== CART QUERY (FIXED) ===== */
    $stmt = mysqli_prepare($conn, "
        SELECT 
            c.id AS cart_id,
            p.id AS product_id,
            p.product_name,
            p.price,
            p.image,
            c.quantity
        FROM cart c
        INNER JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $cart[] = $row;
        $total += $row['price'] * $row['quantity'];
        $cart_count += $row['quantity'];
    }

} else {
    $showLoginWarning = true;
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
  ::-webkit-scrollbar { width:8px; }
  ::-webkit-scrollbar-track { background:#0a0a0f; }
  ::-webkit-scrollbar-thumb { background:#2a2a3a; border-radius:99px; }

  .nav-blur { background:rgba(10,10,15,0.75); backdrop-filter:blur(20px); border-bottom:1px solid rgba(255,255,255,0.04); }

  .cart-card { background:#101018; border:1px solid rgba(255,255,255,0.04); border-radius:18px; transition:all 0.3s ease; }
  .cart-card:hover { border-color:rgba(251,191,36,0.1); }

  .text-shimmer { background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24); background-size:200% auto; -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; animation:shimmer 3s linear infinite; }
  @keyframes shimmer { to { background-position:200% center; } }

  .btn-cart { background:linear-gradient(135deg,#fbbf24,#f59e0b); color:#0a0a0f; font-weight:700; border:none; border-radius:14px; transition:all 0.25s ease; position:relative; overflow:hidden; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
  .btn-cart::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent); transition:left 0.5s ease; }
  .btn-cart:hover::before { left:100%; }
  .btn-cart:hover { box-shadow:0 8px 30px -4px rgba(251,191,36,0.5); transform:translateY(-2px); }

  .btn-outline { background:transparent; border:1px solid rgba(255,255,255,0.1); color:rgba(255,255,255,0.6); border-radius:14px; font-weight:600; transition:all 0.25s ease; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
  .btn-outline:hover { background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.2); color:#fff; }

  .qty-btn { width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; font-size:0.8rem; }
  .qty-btn:hover { background:rgba(251,191,36,0.1); border-color:rgba(251,191,36,0.3); color:#fbbf24; }

  .remove-btn { color:rgba(255,255,255,0.2); transition:all 0.2s; cursor:pointer; background:none; border:none; padding:4px; }
  .remove-btn:hover { color:#ef4444; }

  /* Login warning page styles */
  .lock-icon-wrap {
    width:120px; height:120px; border-radius:32px;
    background:linear-gradient(135deg, rgba(251,146,60,0.08), rgba(251,146,60,0.02));
    border:1px solid rgba(251,146,60,0.12);
    display:flex; align-items:center; justify-content:center;
    position:relative;
  }
  .lock-icon-wrap::after {
    content:''; position:absolute; inset:-20px;
    border-radius:40px;
    background:radial-gradient(circle, rgba(251,146,60,0.06), transparent 70%);
    pointer-events:none;
  }
  @keyframes float {
    0%, 100% { transform:translateY(0); }
    50% { transform:translateY(-8px); }
  }
  .float-anim { animation:float 3s ease-in-out infinite; }

  @keyframes pulse-ring {
    0% { transform:scale(1); opacity:0.4; }
    100% { transform:scale(1.5); opacity:0; }
  }
  .pulse-ring {
    position:absolute; inset:0; border-radius:32px;
    border:1px solid rgba(251,146,60,0.2);
    animation:pulse-ring 2s ease-out infinite;
  }

  .fade-up { opacity:0; transform:translateY(20px); animation:fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes fadeUp { to { opacity:1; transform:translateY(0); } }

  /* ✅ PROFILE DROPDOWN STYLES */
  .profile-wrap { position:relative; }
  .profile-btn {
    width:40px;height:40px;border-radius:12px;
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:all 0.25s ease;border:none;
    font-weight:800;font-size:0.85rem;color:#0a0a0f;
    box-shadow:0 2px 10px -2px rgba(251,191,36,0.3);
  }
  .profile-btn:hover {
    transform:translateY(-1px);
    box-shadow:0 4px 16px -2px rgba(251,191,36,0.5);
  }
  .profile-btn.active {
    box-shadow:0 0 0 2px #0a0a0f, 0 0 0 4px rgba(251,191,36,0.4);
  }

  .profile-dropdown {
    position:absolute;top:calc(100% + 10px);right:0;
    width:260px;
    background:#16161f;
    border:1px solid rgba(255,255,255,0.06);
    border-radius:18px;
    box-shadow:0 20px 60px -12px rgba(0,0,0,0.7);
    opacity:0;visibility:hidden;
    transform:translateY(-8px) scale(0.97);
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    z-index:100;
    overflow:hidden;
  }
  .profile-dropdown.open {
    opacity:1;visibility:visible;
    transform:translateY(0) scale(1);
  }

  .dropdown-item {
    display:flex;align-items:center;gap:10px;
    padding:11px 16px;
    color:rgba(255,255,255,0.5);
    font-size:0.8rem;font-weight:500;
    text-decoration:none;
    transition:all 0.2s ease;
    cursor:pointer;border:none;background:none;width:100%;text-align:left;
  }
  .dropdown-item:hover {
    background:rgba(255,255,255,0.04);
    color:#fff;
  }
  .dropdown-item i { width:16px;text-align:center;font-size:0.75rem; }
  .dropdown-item.item-danger:hover {
    background:rgba(239,68,68,0.06);
    color:#ef4444;
  }
  .dropdown-divider {
    height:1px;background:rgba(255,255,255,0.04);margin:4px 12px;
  }

  .login-nav-btn {
    display:flex;align-items:center;gap:6px;
    padding:8px 16px;border-radius:12px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    color:rgba(255,255,255,0.6);
    font-size:0.8rem;font-weight:600;
    text-decoration:none;
    transition:all 0.2s ease;
  }
  .login-nav-btn:hover {
    background:rgba(251,191,36,0.08);
    border-color:rgba(251,191,36,0.2);
    color:#fbbf24;
  }

  .online-dot {
    position:absolute;bottom:-1px;right:-1px;
    width:12px;height:12px;border-radius:50%;
    background:#22c55e;
    border:2px solid #0a0a0f;
  }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50 px-6 lg:px-10 py-4">
  <div class="max-w-7xl mx-auto flex items-center justify-between">
    <a href="product_detail.php" class="flex items-center gap-3 text-decoration-none">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center">
        <i class="fa-solid fa-headphones text-surface-900 text-sm"></i>
      </div>
      <span class="text-lg font-extrabold text-white tracking-tight">Beats<span class="text-gold-400">Shop</span></span>
    </a>
    <div class="flex items-center gap-3">
      <a href="product_detail.php" class="hidden sm:flex items-center gap-2 text-sm font-medium text-white/50 hover:text-white transition px-3 py-2 rounded-xl hover:bg-white/5">
        <i class="fa-solid fa-house text-xs"></i> Home
      </a>
      
      <!-- Cart Icon -->
      <a href="cart.php" class="relative w-10 h-10 rounded-xl bg-gold-500/10 flex items-center justify-center transition text-gold-400">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if (!$showLoginWarning && $cart_count > 0) { ?>
          <span class="absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $cart_count ?></span>
        <?php } ?>
      </a>

      <!-- ✅ PROFILE / LOGIN BUTTON -->
      <?php if ($isLoggedIn) { ?>

      <div class="profile-wrap" id="profileWrap">
        <button class="profile-btn" id="profileBtn" onclick="toggleProfile()">
          <?= $userInitial ?>
          <span class="online-dot"></span>
        </button>

        <div class="profile-dropdown" id="profileDropdown">
          <div class="px-4 pt-4 pb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-surface-900 font-extrabold text-sm shrink-0">
                <?= $userInitial ?>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($userName) ?></p>
                <p class="text-[11px] text-white/25 truncate"><?= htmlspecialchars($userEmail) ?></p>
              </div>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item" onclick="closeProfile();">
            <i class="fa-solid fa-box"></i><span>My Orders</span>
          </button>
          <button class="dropdown-item" onclick="closeProfile();">
            <i class="fa-regular fa-heart"></i><span>Wishlist</span>
          </button>
          <button class="dropdown-item" onclick="closeProfile();">
            <i class="fa-solid fa-gear"></i><span>Settings</span>
          </button>
          <div class="dropdown-divider"></div>
          <a href="<?= $logoutPage ?>" class="dropdown-item item-danger">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
          </a>
        </div>
      </div>

      <?php } else { ?>

      <a href="<?= $loginPage ?>" class="login-nav-btn">
        <i class="fa-solid fa-right-to-bracket text-xs"></i>
        <span class="hidden sm:inline">Login</span>
      </a>

      <?php } ?>

    </div>
  </div>
</nav>

<main class="pt-24 pb-20">
  <div class="max-w-5xl mx-auto px-6 lg:px-10">

    <?php if ($showLoginWarning) { ?>

    <!-- ========== LOGIN REQUIRED — STYLED PAGE ========== -->
    <div class="flex flex-col items-center justify-center min-h-[70vh]">

      <div class="fade-up" style="animation-delay:0s">
        <div class="lock-icon-wrap float-anim mb-8">
          <div class="pulse-ring"></div>
          <i class="fa-solid fa-lock text-4xl text-orange-400/60"></i>
        </div>
      </div>

      <div class="fade-up text-center" style="animation-delay:0.1s">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Login Required</h1>
        <p class="text-sm text-white/30 mt-3 max-w-sm leading-relaxed">
          Please sign in to your account to view your cart and place orders. If you don't have an account, you can register for free.
        </p>
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-3 mt-8 fade-up" style="animation-delay:0.2s">
        <a href="<?= $loginPage ?>" class="btn-cart px-8 py-3.5 text-sm gap-2">
          <i class="fa-solid fa-right-to-bracket text-xs"></i> Login Now
        </a>
        <a href="product_detail.php" class="btn-outline px-8 py-3.5 text-sm gap-2">
          <i class="fa-solid fa-arrow-left text-xs"></i> Back to Shop
        </a>
      </div>

      <div class="flex items-center gap-6 mt-10 fade-up" style="animation-delay:0.3s">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-shield-halved text-xs text-orange-400/30"></i>
          <span class="text-xs text-white/15">Secure Login</span>
        </div>
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-bolt text-xs text-orange-400/30"></i>
          <span class="text-xs text-white/15">Quick Access</span>
        </div>
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-lock text-xs text-orange-400/30"></i>
          <span class="text-xs text-white/15">Data Protected</span>
        </div>
      </div>

    </div>

    <?php } elseif (empty($cart)) { ?>

    <!-- ========== EMPTY CART ========== -->
    <div class="flex flex-col items-center justify-center py-20">
      <a href="product_detail.php" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/50 hover:text-white mb-6">
        <i class="fa-solid fa-arrow-left text-sm"></i>
      </a>

      <div class="w-24 h-24 rounded-3xl bg-surface-800 border border-white/[0.04] flex items-center justify-center mb-6">
        <i class="fa-solid fa-bag-shopping text-3xl text-white/10"></i>
      </div>
      <h3 class="text-lg font-bold text-white/40">Your cart is empty</h3>
      <p class="text-sm text-white/15 mt-1 mb-6">Looks like you haven't added anything yet</p>
      <a href="product_detail.php" class="btn-cart px-8 py-3 text-sm gap-2">
        <i class="fa-solid fa-arrow-left text-xs"></i> Continue Shopping
      </a>
    </div>

    <?php } else { ?>

    <!-- ========== CART WITH ITEMS ========== -->

    <div class="flex items-center gap-4 mb-8">
      <a href="product_detail.php" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/50 hover:text-white">
        <i class="fa-solid fa-arrow-left text-sm"></i>
      </a>
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Shopping Cart</h1>
        <p class="text-sm text-white/25 mt-0.5"><?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?> in your cart</p>
      </div>
    </div>

    <div class="space-y-4 mb-8">

      <?php foreach ($cart as $item) { 
        $subtotal = $item['price'] * $item['quantity'];
      ?>
      <div class="cart-card p-4 flex gap-4 sm:gap-6">
        
        <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="w-24 h-24 sm:w-32 sm:h-32 rounded-xl overflow-hidden shrink-0 bg-surface-700">
          <img src="../backend/uploads/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" 
               class="w-full h-full object-cover"
               onerror="this.src='https://picsum.photos/seed/<?= $item['product_id'] ?>/200/200.jpg'">
        </a>

        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-3">
            <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="text-sm sm:text-base font-bold text-white hover:text-gold-400 transition truncate">
              <?= htmlspecialchars($item['product_name']) ?>
            </a>
            <button onclick="removeFromCart(<?= $item['cart_id'] ?>)" class="remove-btn shrink-0" title="Remove">
              <i class="fa-solid fa-trash-can text-xs"></i>
            </button>
          </div>
          
          <p class="text-lg font-extrabold text-gold-400 mt-1">Rs. <?= number_format($item['price'], 0) ?></p>
          
          <div class="flex items-center justify-between mt-3">
            <div class="flex items-center gap-2 bg-white/[0.03] border border-white/[0.06] rounded-xl px-2 py-1">
              <button class="qty-btn" onclick="updateQty(<?= $item['cart_id'] ?>, -1)">
                <i class="fa-solid fa-minus text-[10px]"></i>
              </button>
              <span class="text-sm font-bold text-white w-6 text-center"><?= $item['quantity'] ?></span>
              <button class="qty-btn" onclick="updateQty(<?= $item['cart_id'] ?>, 1)">
                <i class="fa-solid fa-plus text-[10px]"></i>
              </button>
            </div>
            
            <span class="text-sm font-bold text-white/60">Rs. <?= number_format($subtotal, 0) ?></span>
          </div>
        </div>

      </div>
      <?php } ?>

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

      <button onclick="checkout()" class="btn-cart w-full py-4 text-sm gap-2 cursor-pointer">
        <i class="fa-solid fa-lock text-xs"></i> Proceed to Checkout
      </button>

      <button onclick="clearCart()" class="w-full mt-3 py-3 text-sm font-semibold text-white/20 hover:text-red-400 transition bg-transparent border-none cursor-pointer">
        Clear Cart
      </button>
    </div>

    <?php } ?>

  </div>
</main>

<script>
/* ===== PROFILE DROPDOWN ===== */
let profileOpen = false;

function toggleProfile() {
  profileOpen = !profileOpen;
  const dropdown = document.getElementById('profileDropdown');
  const btn = document.getElementById('profileBtn');
  if (profileOpen) {
    dropdown.classList.add('open');
    btn.classList.add('active');
  } else {
    dropdown.classList.remove('open');
    btn.classList.remove('active');
  }
}

function closeProfile() {
  profileOpen = false;
  const dropdown = document.getElementById('profileDropdown');
  const btn = document.getElementById('profileBtn');
  if (dropdown) dropdown.classList.remove('open');
  if (btn) btn.classList.remove('active');
}

/* Bahar click karne se band ho */
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('profileWrap');
  if (wrap && !wrap.contains(e.target)) closeProfile();
});

/* ESC se band ho */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeProfile();
});

/* ===== CART FUNCTIONS ===== */
function updateQty(cart_id, delta) {
  const formData = new FormData();
  formData.append('cart_id', cart_id);
  formData.append('delta', delta);
  
  fetch('update_cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) location.reload();
    });
}

function removeFromCart(cart_id) {
  if (!confirm('Remove this item from cart?')) return;
  const formData = new FormData();
  formData.append('cart_id', cart_id);
  
  fetch('remove_from_cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) location.reload();
    });
}

// function clearCart() {
//   if (!confirm('Clear all items from cart?')) return;
//   fetch('clear_cart.php', { method: 'POST' })
//     .then(r => r.json())
//     .then(data => {
//       if (data.success) location.reload();
//     });
// }

function checkout() {
  window.location.href = 'checkout.php';
}

/* Navbar scroll effect */
window.addEventListener('scroll', function() {
  const nav = document.querySelector('.nav-blur');
  if (window.scrollY > 50) {
    nav.style.background = 'rgba(10,10,15,0.9)';
  } else {
    nav.style.background = 'rgba(10,10,15,0.75)';
  }
});
</script>

</body>
</html>