<?php
session_start();
include("../backend/config/db.php");

/* ===== LOGIN CHECK ===== */
 $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
 $loginPage = "../login.php";
 $logoutPage = "../backend/logout.php";

if (!$isLoggedIn) {
    header("Location: " . $loginPage);
    exit;
}

 $user_id = intval($_SESSION['user_id']);
 $userName = '';
 $userEmail = '';
 $userInitial = 'U';
 $userImage = '';

/* ===== USER INFO ===== */
 $uStmt = mysqli_prepare($conn, "SELECT name, email, image FROM users WHERE id = ?");
mysqli_stmt_bind_param($uStmt, "i", $user_id);
mysqli_stmt_execute($uStmt);
 $uResult = mysqli_stmt_get_result($uStmt);
if ($uRow = mysqli_fetch_assoc($uResult)) {
    $userName = $uRow['name'];
    $userEmail = $uRow['email'];
    $userImage = $uRow['image'];
    $userInitial = strtoupper(mb_substr(trim($userName), 0, 1));
}
if (empty($userInitial)) { $userInitial = 'U'; $userName = 'User'; }

/* ===== CART ITEMS ===== */
 $stmt = mysqli_prepare($conn, "
    SELECT c.id AS cart_id, c.product_id, c.quantity, 
           p.product_name, p.price, p.image 
    FROM cart c 
    INNER JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

 $cart = [];
 $total = 0;
 $cart_count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $cart[] = $row;
    $total += $row['price'] * $row['quantity'];
    $cart_count += $row['quantity'];
}

/* ===== EMPTY CART REDIRECT ===== */
if (empty($cart)) {
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — BeatsShop</title>
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
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Plus Jakarta Sans',sans-serif;background:#0a0a0f;color:#fff}
  ::-webkit-scrollbar{width:8px}
  ::-webkit-scrollbar-track{background:#0a0a0f}
  ::-webkit-scrollbar-thumb{background:#2a2a3a;border-radius:99px}
  .nav-blur{background:rgba(10,10,15,0.75);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,0.04)}
  .text-shimmer{background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite}
  @keyframes shimmer{to{background-position:200% center}}

  .section-card{background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:20px;padding:28px}
  .section-title{font-size:0.95rem;font-weight:700;color:#fff;display:flex;align-items:center;gap:10px;margin-bottom:20px}
  .section-title i{color:#fbbf24;font-size:0.8rem;width:28px;height:28px;border-radius:8px;background:rgba(251,191,36,0.08);display:flex;align-items:center;justify-content:center}

  .form-group{margin-bottom:18px}
  .form-label{display:block;font-size:0.75rem;font-weight:600;color:rgba(255,255,255,0.35);margin-bottom:7px;letter-spacing:0.02em}
  .form-input{width:100%;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 16px;font-size:0.85rem;color:#fff;font-family:'Plus Jakarta Sans',sans-serif;transition:all 0.2s ease;outline:none}
  .form-input::placeholder{color:rgba(255,255,255,0.12)}
  .form-input:focus{border-color:rgba(251,191,36,0.4);box-shadow:0 0 0 3px rgba(251,191,36,0.06);background:rgba(255,255,255,0.04)}
  .form-input.input-error{border-color:rgba(239,68,68,0.5);box-shadow:0 0 0 3px rgba(239,68,68,0.06)}
  .form-input:read-only{opacity:0.5;cursor:default}
  textarea.form-input{resize:vertical;min-height:80px;max-height:160px;line-height:1.6}
  .field-error{font-size:0.72rem;color:#ef4444;margin-top:5px;display:none}
  .field-error.show{display:block}

  .payment-option{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:14px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);cursor:pointer;transition:all 0.25s ease;position:relative}
  .payment-option:hover{background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.1)}
  .payment-option.selected{background:rgba(251,191,36,0.04);border-color:rgba(251,191,36,0.25)}
  .payment-option.selected .pay-icon{background:rgba(251,191,36,0.15);color:#fbbf24}
  .payment-option.selected .pay-name{color:#fbbf24}
  .payment-option input[type="radio"]{position:absolute;opacity:0;pointer-events:none}
  .pay-icon{width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.3);font-size:0.9rem;transition:all 0.25s ease;flex-shrink:0}
  .pay-name{font-size:0.85rem;font-weight:600;color:rgba(255,255,255,0.6);transition:color 0.25s ease}
  .pay-desc{font-size:0.7rem;color:rgba(255,255,255,0.2);margin-top:2px}
  .pay-check{margin-left:auto;width:20px;height:20px;border-radius:50%;border:2px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;transition:all 0.25s ease;flex-shrink:0}
  .payment-option.selected .pay-check{background:#fbbf24;border-color:#fbbf24}
  .pay-check i{font-size:0.5rem;color:#0a0a0f;opacity:0;transform:scale(0);transition:all 0.2s ease}
  .payment-option.selected .pay-check i{opacity:1;transform:scale(1)}

  .btn-place{width:100%;padding:16px;border-radius:16px;font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#0a0a0f;border:none;cursor:pointer;transition:all 0.25s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;gap:10px}
  .btn-place::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);transition:left 0.5s ease}
  .btn-place:hover::before{left:100%}
  .btn-place:hover{box-shadow:0 8px 30px -4px rgba(251,191,36,0.5);transform:translateY(-2px)}
  .btn-place:disabled{opacity:0.4;cursor:not-allowed;transform:none!important;box-shadow:none!important}
  .btn-place:disabled::before{display:none}

  .summary-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.03)}
  .summary-item:last-child{border-bottom:none}
  .summary-img{width:56px;height:56px;border-radius:10px;overflow:hidden;background:#16161f;flex-shrink:0}
  .summary-img img{width:100%;height:100%;object-fit:cover}

  /* SUCCESS */
  @keyframes scaleIn{0%{opacity:0;transform:scale(0.5)}60%{transform:scale(1.05)}100%{opacity:1;transform:scale(1)}}
  @keyframes checkDraw{0%{stroke-dashoffset:48}100%{stroke-dashoffset:0}}
  @keyframes circleFill{0%{transform:scale(0);opacity:0}50%{transform:scale(1.1);opacity:1}100%{transform:scale(1);opacity:1}}
  .success-wrap{animation:scaleIn 0.5s cubic-bezier(.4,0,.2,1) forwards}
  .success-circle{width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;animation:circleFill 0.4s 0.2s cubic-bezier(.4,0,.2,1) both;box-shadow:0 8px 30px -4px rgba(34,197,94,0.4)}
  .success-circle i{font-size:2.2rem;color:#fff}
  .success-id-box{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
  .copy-btn{background:none;border:none;color:rgba(255,255,255,0.3);cursor:pointer;padding:4px;transition:color 0.2s;font-size:0.8rem}
  .copy-btn:hover{color:#fbbf24}
  .btn-continue{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:12px;font-size:0.82rem;font-weight:700;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#0a0a0f;border:none;cursor:pointer;transition:all 0.25s ease;text-decoration:none}
  .btn-continue:hover{box-shadow:0 6px 24px -4px rgba(251,191,36,0.5);transform:translateY(-1px)}
  .btn-secondary{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:12px;font-size:0.82rem;font-weight:600;background:transparent;border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.5);cursor:pointer;transition:all 0.25s ease;text-decoration:none}
  .btn-secondary:hover{background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.2);color:#fff}

  .fade-up{opacity:0;transform:translateY(20px);animation:fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes fadeUp{to{opacity:1;transform:translateY(0)}}

  .bc-link{color:rgba(255,255,255,0.3);font-size:0.8rem;text-decoration:none;transition:color 0.2s}
  .bc-link:hover{color:#fbbf24}

  .profile-wrap{position:relative}
  .profile-btn{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#fbbf24,#f59e0b);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.25s ease;border:none;font-weight:800;font-size:0.85rem;color:#0a0a0f;box-shadow:0 2px 10px -2px rgba(251,191,36,0.3);overflow:hidden;position:relative}
  .profile-btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px -2px rgba(251,191,36,0.5)}
  .profile-btn.active{box-shadow:0 0 0 2px #0a0a0f,0 0 0 4px rgba(251,191,36,0.4)}
  .profile-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:260px;background:#16161f;border:1px solid rgba(255,255,255,0.06);border-radius:18px;box-shadow:0 20px 60px -12px rgba(0,0,0,0.7);opacity:0;visibility:hidden;transform:translateY(-8px) scale(0.97);transition:all 0.25s cubic-bezier(.4,0,.2,1);z-index:100;overflow:hidden}
  .profile-dropdown.open{opacity:1;visibility:visible;transform:translateY(0) scale(1)}
  .dropdown-item{display:flex;align-items:center;gap:10px;padding:11px 16px;color:rgba(255,255,255,0.5);font-size:0.8rem;font-weight:500;text-decoration:none;transition:all 0.2s ease;cursor:pointer;border:none;background:none;width:100%;text-align:left}
  .dropdown-item:hover{background:rgba(255,255,255,0.04);color:#fff}
  .dropdown-item i{width:16px;text-align:center;font-size:0.75rem}
  .dropdown-item.item-danger:hover{background:rgba(239,68,68,0.06);color:#ef4444}
  .dropdown-divider{height:1px;background:rgba(255,255,255,0.04);margin:4px 12px}
  .online-dot{position:absolute;bottom:-1px;right:-1px;width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid #0a0a0f}

  .toast-msg{position:fixed;top:24px;right:24px;z-index:9999;background:#101018;border-radius:16px;padding:16px 20px;box-shadow:0 20px 50px -10px rgba(0,0,0,0.6);opacity:0;transform:translateY(-12px) scale(0.96);transition:all 0.35s cubic-bezier(.4,0,.2,1);pointer-events:none;max-width:360px;display:flex;align-items:center;gap:12px}
  .toast-msg.show{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
  .toast-msg.toast-err{border:1px solid rgba(239,68,68,0.25)}
</style>
</head>
<body>

<!-- TOAST -->
<div id="toastErr" class="toast-msg toast-err">
  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shrink-0">
    <i class="fa-solid fa-xmark text-surface-900 text-sm"></i>
  </div>
  <div>
    <p class="text-sm font-semibold text-white" id="toastErrMsg">Error</p>
    <p class="text-xs text-white/30 mt-0.5">Please check the form</p>
  </div>
</div>

<!-- NAVBAR -->
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
      <a href="cart.php" class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60 hover:text-white">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($cart_count > 0): ?>
          <span class="absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $cart_count ?></span>
        <?php endif; ?>
      </a>
      <div class="profile-wrap" id="profileWrap">
        <button class="profile-btn" id="profileBtn" onclick="toggleProfile()">
          <?php if (!empty($userImage) && file_exists("../backend/uploads/" . $userImage)): ?>
            <img src="../backend/uploads/<?= $userImage ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px" alt="">
          <?php else: ?>
            <?= $userInitial ?>
          <?php endif; ?>
          <span class="online-dot"></span>
        </button>
        <div class="profile-dropdown" id="profileDropdown">
          <div class="px-4 pt-4 pb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-surface-900 font-extrabold text-sm shrink-0 overflow-hidden">
                <?php if (!empty($userImage) && file_exists("../backend/uploads/" . $userImage)): ?>
                  <img src="../backend/uploads/<?= $userImage ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px" alt="">
                <?php else: ?>
                  <?= $userInitial ?>
                <?php endif; ?>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($userName) ?></p>
                <p class="text-[11px] text-white/25 truncate"><?= htmlspecialchars($userEmail) ?></p>
              </div>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item" onclick="closeProfile()"><i class="fa-solid fa-box"></i><span>My Orders</span></button>
          <button class="dropdown-item" onclick="closeProfile()"><i class="fa-regular fa-heart"></i><span>Wishlist</span></button>
          <div class="dropdown-divider"></div>
          <a href="<?= $logoutPage ?>" class="dropdown-item item-danger"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- MAIN -->
<main class="pt-24 pb-20">
  <div class="max-w-6xl mx-auto px-6 lg:px-10">

    <!-- BREADCRUMB -->
    <div class="flex items-center gap-2 mb-8 fade-up">
      <a href="index.php" class="bc-link"><i class="fa-solid fa-house text-[10px]"></i> Home</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-white/10"></i>
      <a href="cart.php" class="bc-link">Cart</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-white/10"></i>
      <span class="text-xs text-white/60 font-medium">Checkout</span>
    </div>

    <!-- ===== CHECKOUT FORM ===== -->
    <div id="checkoutForm">

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- LEFT: FORM -->
        <div class="lg:col-span-2 space-y-6">

          <!-- SHIPPING -->
          <div class="section-card fade-up" style="animation-delay:0s">
            <div class="section-title">
              <i class="fa-solid fa-location-dot"></i>
              Shipping Details
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
              <div class="form-group sm:col-span-2">
                <label class="form-label">Full Name *</label>
                <input type="text" id="shipName" class="form-input" value="<?= htmlspecialchars($userName) ?>" placeholder="Enter your full name">
                <p class="field-error" id="errName">Please enter your full name</p>
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="tel" id="shipPhone" class="form-input" placeholder="03XX XXXXXXX">
                <p class="field-error" id="errPhone">Please enter a valid phone number</p>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" id="shipEmail" class="form-input" value="<?= htmlspecialchars($userEmail) ?>" readonly>
              </div>
              <div class="form-group sm:col-span-2">
                <label class="form-label">Address *</label>
                <textarea id="shipAddress" class="form-input" placeholder="House/Flat No, Street, Area..."></textarea>
                <p class="field-error" id="errAddress">Please enter your complete address</p>
              </div>
              <div class="form-group">
                <label class="form-label">City *</label>
                <input type="text" id="shipCity" class="form-input" placeholder="e.g. Lahore">
                <p class="field-error" id="errCity">Please enter your city</p>
              </div>
            </div>
          </div>

          <!-- PAYMENT -->
          <div class="section-card fade-up" style="animation-delay:0.08s">
            <div class="section-title">
              <i class="fa-solid fa-wallet"></i>
              Payment Method
            </div>

            <div class="space-y-3" id="paymentOptions">
              <label class="payment-option selected" onclick="selectPayment(this, 'cod')">
                <input type="radio" name="payment" value="cod" checked>
                <div class="pay-icon"><i class="fa-solid fa-truck"></i></div>
                <div>
                  <p class="pay-name">Cash on Delivery</p>
                  <p class="pay-desc">Pay when your order arrives</p>
                </div>
                <div class="pay-check"><i class="fa-solid fa-check"></i></div>
              </label>

              <label class="payment-option" onclick="selectPayment(this, 'jazzcash')">
                <input type="radio" name="payment" value="jazzcash">
                <div class="pay-icon"><i class="fa-solid fa-mobile-screen"></i></div>
                <div>
                  <p class="pay-name">JazzCash</p>
                  <p class="pay-desc">Pay via JazzCash account</p>
                </div>
                <div class="pay-check"><i class="fa-solid fa-check"></i></div>
              </label>

              <label class="payment-option" onclick="selectPayment(this, 'easypaisa')">
                <input type="radio" name="payment" value="easypaisa">
                <div class="pay-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                  <p class="pay-name">EasyPaisa</p>
                  <p class="pay-desc">Pay via EasyPaisa account</p>
                </div>
                <div class="pay-check"><i class="fa-solid fa-check"></i></div>
              </label>
            </div>
          </div>

          <!-- PLACE ORDER (mobile) -->
          <div class="lg:hidden fade-up" style="animation-delay:0.15s">
            <button type="button" id="btnPlaceMobile" class="btn-place" onclick="placeOrder()">
              <i class="fa-solid fa-lock text-sm"></i>
              <span>Place Order — Rs. <?= number_format($total, 0) ?></span>
            </button>
          </div>

        </div>

        <!-- RIGHT: SUMMARY -->
        <div class="lg:col-span-1">
          <div class="section-card lg:sticky lg:top-28 fade-up" style="animation-delay:0.1s">
            <div class="section-title">
              <i class="fa-solid fa-receipt"></i>
              Order Summary
            </div>

            <div class="space-y-0 mb-5 max-h-[320px] overflow-y-auto pr-1" style="scrollbar-width:thin;scrollbar-color:#2a2a3a transparent">
              <?php foreach ($cart as $item): ?>
              <div class="summary-item">
                <div class="summary-img">
                  <img src="../backend/uploads/<?= $item['image'] ?>" alt="" onerror="this.src='https://picsum.photos/seed/<?= $item['product_id'] ?>/100/100.jpg'">
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-semibold text-white/70 truncate"><?= htmlspecialchars($item['product_name']) ?></p>
                  <p class="text-xs text-white/25 mt-0.5">Qty: <?= $item['quantity'] ?></p>
                </div>
                <p class="text-xs font-bold text-white/50 shrink-0">Rs. <?= number_format($item['price'] * $item['quantity'], 0) ?></p>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="space-y-3 pt-4 border-t border-white/[0.04]">
              <div class="flex justify-between">
                <span class="text-xs text-white/25">Subtotal (<?= $cart_count ?> items)</span>
                <span class="text-xs font-semibold text-white/50">Rs. <?= number_format($total, 0) ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-xs text-white/25">Delivery</span>
                <span class="text-xs font-semibold text-green-400">FREE</span>
              </div>
              <div class="h-px bg-white/[0.04]"></div>
              <div class="flex justify-between items-end">
                <span class="text-sm font-bold text-white">Total</span>
                <span class="text-xl font-extrabold text-shimmer">Rs. <?= number_format($total, 0) ?></span>
              </div>
            </div>

            <!-- PLACE ORDER (desktop) -->
            <button type="button" id="btnPlaceDesktop" class="btn-place mt-6 hidden lg:flex" onclick="placeOrder()">
              <i class="fa-solid fa-lock text-sm"></i>
              <span>Place Order</span>
            </button>

            <div class="flex items-center justify-center gap-4 mt-4">
              <div class="flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-[10px] text-white/10"></i><span class="text-[10px] text-white/10">Secure</span></div>
              <div class="flex items-center gap-1.5"><i class="fa-solid fa-lock text-[10px] text-white/10"></i><span class="text-[10px] text-white/10">Encrypted</span></div>
              <div class="flex items-center gap-1.5"><i class="fa-solid fa-certificate text-[10px] text-white/10"></i><span class="text-[10px] text-white/10">Trusted</span></div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ===== SUCCESS STATE ===== -->
    <div id="successState" class="hidden">
      <div class="flex flex-col items-center justify-center py-16 success-wrap">
        <div class="success-circle mb-8">
          <i class="fa-solid fa-check"></i>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight text-center">Order Placed Successfully!</h1>
        <p class="text-sm text-white/30 mt-3 text-center max-w-md">Thank you for your order. We'll send you a confirmation with tracking details soon.</p>

        <div class="success-id-box mt-8 w-full max-w-sm">
          <div>
            <p class="text-[10px] text-white/20 font-semibold uppercase tracking-wider">Order ID</p>
            <p class="text-base font-extrabold text-white mt-1" id="successOrderId">—</p>
          </div>
          <button class="copy-btn" onclick="copyOrderId()" title="Copy">
            <i class="fa-regular fa-copy"></i>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-6 w-full max-w-sm">
          <div class="bg-white/[0.02] border border-white/[0.04] rounded-xl p-4 text-center">
            <p class="text-[10px] text-white/20 font-semibold uppercase tracking-wider">Total Paid</p>
            <p class="text-lg font-extrabold text-gold-400 mt-1" id="successTotal">—</p>
          </div>
          <div class="bg-white/[0.02] border border-white/[0.04] rounded-xl p-4 text-center">
            <p class="text-[10px] text-white/20 font-semibold uppercase tracking-wider">Payment</p>
            <p class="text-sm font-bold text-white/60 mt-1.5 capitalize" id="successPayment">—</p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-3 mt-10">
          <a href="index.php" class="btn-continue">
            <i class="fa-solid fa-arrow-left text-xs"></i> Continue Shopping
          </a>
          <a href="cart.php" class="btn-secondary">
            <i class="fa-solid fa-box text-xs"></i> View Orders
          </a>
        </div>
      </div>
    </div>

  </div>
</main>

<script>
/* ===== PROFILE DROPDOWN ===== */
var profileOpen = false;
function toggleProfile() {
  profileOpen = !profileOpen;
  document.getElementById('profileDropdown').classList.toggle('open', profileOpen);
  document.getElementById('profileBtn').classList.toggle('active', profileOpen);
}
function closeProfile() {
  profileOpen = false;
  var dd = document.getElementById('profileDropdown');
  var btn = document.getElementById('profileBtn');
  if (dd) dd.classList.remove('open');
  if (btn) btn.classList.remove('active');
}
document.addEventListener('click', function(e) {
  var w = document.getElementById('profileWrap');
  if (w && !w.contains(e.target)) closeProfile();
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeProfile(); });

/* ===== PAYMENT SELECTION ===== */
var selectedPayment = 'cod';

function selectPayment(el, method) {
  selectedPayment = method;
  document.querySelectorAll('.payment-option').forEach(function(opt) {
    opt.classList.remove('selected');
  });
  el.classList.add('selected');
  el.querySelector('input[type="radio"]').checked = true;
}

/* ===== TOAST ===== */
function showToast(msg) {
  document.getElementById('toastErrMsg').textContent = msg;
  var t = document.getElementById('toastErr');
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 4000);
}

/* ===== VALIDATION ===== */
function clearErrors() {
  document.querySelectorAll('.field-error').forEach(function(e) { e.classList.remove('show'); });
  document.querySelectorAll('.input-error').forEach(function(e) { e.classList.remove('input-error'); });
}

function showFieldError(inputId, errorId) {
  document.getElementById(inputId).classList.add('input-error');
  document.getElementById(errorId).classList.add('show');
}

function validateForm() {
  clearErrors();
  var valid = true;
  var name = document.getElementById('shipName').value.trim();
  var phone = document.getElementById('shipPhone').value.trim();
  var address = document.getElementById('shipAddress').value.trim();
  var city = document.getElementById('shipCity').value.trim();

  if (!name || name.length < 2) { showFieldError('shipName', 'errName'); valid = false; }
  if (!phone || !/^[0-9+\-\s]{7,15}$/.test(phone)) { showFieldError('shipPhone', 'errPhone'); valid = false; }
  if (!address || address.length < 5) { showFieldError('shipAddress', 'errAddress'); valid = false; }
  if (!city || city.length < 2) { showFieldError('shipCity', 'errCity'); valid = false; }

  return valid;
}

/* ===== PLACE ORDER ===== */
var isPlacing = false;

function placeOrder() {
  if (isPlacing) return;

  if (!validateForm()) {
    showToast('Please fill in all required fields correctly');
    return;
  }

  isPlacing = true;
  var btnM = document.getElementById('btnPlaceMobile');
  var btnD = document.getElementById('btnPlaceDesktop');
  var mobileText = '<i class="fa-solid fa-lock text-sm"></i><span>Place Order — Rs. <?= number_format($total, 0) ?></span>';
  var desktopText = '<i class="fa-solid fa-lock text-sm"></i><span>Place Order</span>';
  var loadingText = '<i class="fa-solid fa-spinner fa-spin text-sm"></i><span>Placing Order...</span>';

  if (btnM) { btnM.disabled = true; btnM.innerHTML = loadingText; }
  if (btnD) { btnD.disabled = true; btnD.innerHTML = loadingText; }

  var fd = new FormData();
  fd.append('name', document.getElementById('shipName').value.trim());
  fd.append('phone', document.getElementById('shipPhone').value.trim());
  fd.append('email', document.getElementById('shipEmail').value.trim());
  fd.append('address', document.getElementById('shipAddress').value.trim());
  fd.append('city', document.getElementById('shipCity').value.trim());
  fd.append('payment_method', selectedPayment);

  fetch('process_order.php', { method: 'POST', body: fd })
  .then(function(r) {
    if (!r.ok) {
      return r.text().then(function(txt) {
        throw new Error('Server returned ' + r.status + ': ' + txt.substring(0, 200));
      });
    }
    return r.text().then(function(txt) {
      try {
        return JSON.parse(txt);
      } catch(e) {
        throw new Error('Invalid response: ' + txt.substring(0, 200));
      }
    });
  })
  .then(function(data) {
    if (data.success) {
      document.getElementById('successOrderId').textContent = data.order_id;
      document.getElementById('successTotal').textContent = 'Rs. ' + Number(data.total).toLocaleString();
      document.getElementById('successPayment').textContent = data.payment.replace('_', ' ');
      document.getElementById('checkoutForm').classList.add('hidden');
      document.getElementById('successState').classList.remove('hidden');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      showToast(data.message || 'Something went wrong. Please try again.');
      resetButtons(btnM, btnD, mobileText, desktopText);
      isPlacing = false;
    }
  })
  .catch(function(err) {
    console.error('Order error:', err);
    showToast('Error: ' + err.message);
    resetButtons(btnM, btnD, mobileText, desktopText);
    isPlacing = false;
  });
}

function resetButtons(btnM, btnD, mobileText, desktopText) {
  if (btnM) { btnM.disabled = false; btnM.innerHTML = mobileText; }
  if (btnD) { btnD.disabled = false; btnD.innerHTML = desktopText; }
}
/* ===== COPY ORDER ID ===== */
function copyOrderId() {
  var text = document.getElementById('successOrderId').textContent;
  navigator.clipboard.writeText(text).then(function() {
    var btn = document.querySelector('.copy-btn');
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    btn.style.color = '#22c55e';
    setTimeout(function() {
      btn.innerHTML = '<i class="fa-regular fa-copy"></i>';
      btn.style.color = '';
    }, 2000);
  });
}

/* ===== INPUT FOCUS CLEAR ERROR ===== */
['shipName','shipPhone','shipAddress','shipCity'].forEach(function(id) {
  var el = document.getElementById(id);
  if (el) {
    el.addEventListener('input', function() {
      this.classList.remove('input-error');
      var errEl = this.parentNode.querySelector('.field-error');
      if (errEl) errEl.classList.remove('show');
    });
  }
});

/* ===== NAV SCROLL ===== */
window.addEventListener('scroll', function() {
  var nav = document.querySelector('.nav-blur');
  nav.style.background = window.scrollY > 50 ? 'rgba(10,10,15,0.9)' : 'rgba(10,10,15,0.75)';
});

/* ===== FADE UP OBSERVER ===== */
var obs = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) { if (entry.isIntersecting) entry.target.style.animationPlayState = 'running'; });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(function(el) { el.style.animationPlayState = 'paused'; obs.observe(el); });
</script>

</body>
</html>