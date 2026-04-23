<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}
include("../backend/config/db.php");

 $uid = $_SESSION['user_id'];
 $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, email, image, phone, address, created_at FROM users WHERE id = $uid LIMIT 1"));

 $userStats = array('total_orders' => 0, 'total_spent' => 0, 'member_since' => '');
 $ord_res = mysqli_query($conn, "SELECT COUNT(*) as total_orders, IFNULL(SUM(total_amount),0) as total_spent FROM orders WHERE user_id = $uid AND status != 'cancelled'");
if ($ord_res) {
    $ord_row = mysqli_fetch_assoc($ord_res);
    $userStats['total_orders'] = $ord_row['total_orders'];
    $userStats['total_spent'] = $ord_row['total_spent'];
}
if (!empty($user['created_at'])) $userStats['member_since'] = date('M Y', strtotime($user['created_at']));

 $recent_res = mysqli_query($conn, "
    SELECT o.order_id, o.total_amount, o.status, o.created_at, o.payment_method,
           o.shipping_city, MIN(oi.product_name) as product_name, MIN(oi.image) as product_image
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_db_id
    WHERE o.user_id = $uid
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
    LIMIT 5
");
 $recentOrders = array();
while ($ro = mysqli_fetch_assoc($recent_res)) $recentOrders[] = $ro;

 $cart_res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
 $cart_row = mysqli_fetch_assoc($cart_res);
 $count = $cart_row['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — AHMUS Shop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;overflow-x:hidden;color:#fff;min-height:100vh}
.glass-bg{position:fixed;inset:0;z-index:-1;background:linear-gradient(135deg,#0f0c29 0%,#1a1a3e 25%,#24243e 50%,#0f0c29 100%)}
.blob{position:fixed;border-radius:50%;filter:blur(80px);opacity:.35;pointer-events:none;z-index:-1}
.blob-1{width:500px;height:500px;background:radial-gradient(circle,#f59e0b,transparent 70%);top:-10%;left:-5%;animation:bf1 18s ease-in-out infinite}
.blob-2{width:450px;height:450px;background:radial-gradient(circle,#8b5cf6,transparent 70%);top:30%;right:-10%;animation:bf2 22s ease-in-out infinite}
.blob-3{width:400px;height:400px;background:radial-gradient(circle,#06b6d4,transparent 70%);bottom:-5%;left:20%;animation:bf3 20s ease-in-out infinite}
.blob-4{width:350px;height:350px;background:radial-gradient(circle,#ec4899,transparent 70%);top:60%;left:-8%;animation:bf4 24s ease-in-out infinite}
@keyframes bf1{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(60px,40px) scale(1.1)}66%{transform:translate(-30px,70px) scale(.95)}}
@keyframes bf2{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(-50px,-30px) scale(1.08)}66%{transform:translate(40px,50px) scale(.92)}}
@keyframes bf3{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(40px,-60px) scale(1.05)}66%{transform:translate(-60px,20px) scale(.97)}}
@keyframes bf4{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(70px,30px) scale(.93)}66%{transform:translate(-40px,-40px) scale(1.1)}}

.glass{background:rgba(255,255,255,.06);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);box-shadow:0 8px 32px rgba(0,0,0,.15),inset 0 1px 0 rgba(255,255,255,.08)}
.glass-light{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.07);box-shadow:0 4px 16px rgba(0,0,0,.1)}
.glass-input{background:rgba(255,255,255,.06);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.12);color:#fff;font-family:'Inter',sans-serif;transition:all .25s}
.glass-input::placeholder{color:rgba(255,255,255,.3)}
.glass-input:focus{outline:none;border-color:rgba(245,158,11,.5);box-shadow:0 0 0 3px rgba(245,158,11,.15);background:rgba(255,255,255,.09)}
.glass-btn{background:linear-gradient(135deg,rgba(245,158,11,.85),rgba(234,88,12,.85));backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);color:#fff;font-family:'Inter',sans-serif;box-shadow:0 4px 20px rgba(245,158,11,.25);transition:all .3s}
.glass-btn:hover{box-shadow:0 6px 28px rgba(245,158,11,.4);transform:translateY(-1px)}
.glass-btn-dark{background:rgba(255,255,255,.08);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);color:#fff;font-family:'Inter',sans-serif;transition:all .25s}
.glass-btn-dark:hover{background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.25)}

.rv{opacity:0;transform:translateY(30px);transition:all .8s cubic-bezier(.22,1,.36,1);will-change:opacity,transform}
.rv.on{opacity:1;transform:none}
.d1{transition-delay:.05s}.d2{transition-delay:.1s}.d3{transition-delay:.15s}.d4{transition-delay:.2s}.d5{transition-delay:.25s}

.nav-glass{background:rgba(15,12,41,.5);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.06);transition:all .3s}
.nav-glass.scrolled{background:rgba(15,12,41,.75);border-bottom-color:rgba(255,255,255,.1);box-shadow:0 4px 30px rgba(0,0,0,.3)}

.glow-text{background:linear-gradient(135deg,#fbbf24,#f97316,#ef4444,#f472b6,#a78bfa,#38bdf8,#fbbf24);background-size:300% 300%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:gs 6s ease infinite}
@keyframes gs{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

.sec-label{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#fbbf24}
.glass-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent)}

.profile-glow{position:absolute;inset:-2px;border-radius:28px;background:linear-gradient(135deg,rgba(245,158,11,.3),rgba(139,92,246,.3),rgba(6,182,212,.3),rgba(245,158,11,.3));background-size:300% 300%;animation:pglow 8s ease infinite;z-index:-1;filter:blur(1px)}
@keyframes pglow{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
.profile-avatar-ring{position:relative;width:100px;height:100px;border-radius:50%;padding:3px;background:linear-gradient(135deg,#f59e0b,#8b5cf6,#06b6d4);background-size:200% 200%;animation:pglow 4s ease infinite}
.profile-avatar-ring img,.profile-avatar-ring .avatar-fallback{width:100%;height:100%;border-radius:50%;object-fit:cover;border:3px solid rgba(15,12,41,.9)}
.avatar-fallback{display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fbbf24;background:rgba(245,158,11,.1)}
.profile-stat{display:flex;flex-direction:column;align-items:center;padding:16px 12px;border-radius:16px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);transition:all .25s}
.profile-stat:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.12);transform:translateY(-2px)}

.order-chip{display:flex;align-items:center;gap:14px;padding:16px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);transition:all .25s}
.order-chip:hover{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.12)}
.status-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.status-pending{background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.2)}
.status-processing{background:rgba(59,130,246,.12);color:#60a5fa;border:1px solid rgba(59,130,246,.2)}
.status-shipped{background:rgba(139,92,246,.12);color:#a78bfa;border:1px solid rgba(139,92,246,.2)}
.status-delivered{background:rgba(34,197,94,.12);color:#4ade80;border:1px solid rgba(34,197,94,.2)}
.status-cancelled{background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.2)}
.pay-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;font-size:.6rem;font-weight:600;color:rgba(255,255,255,.4);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)}

.info-row{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);transition:all .2s}
.info-row:hover{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.info-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.9rem}
.info-label{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:rgba(255,255,255,.3);margin-bottom:2px}
.info-value{font-size:.88rem;font-weight:600;color:#fff}

.edit-field{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:12px 16px;font-size:.88rem;color:#fff;font-family:'Inter',sans-serif;outline:none;transition:all .2s;width:100%}
.edit-field:focus{border-color:rgba(245,158,11,.5);box-shadow:0 0 0 3px rgba(245,158,11,.15)}
.edit-field:disabled{opacity:.6;cursor:not-allowed}

.tab-btn{padding:10px 20px;border-radius:12px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .25s;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.04);color:rgba(255,255,255,.45);font-family:'Inter',sans-serif}
.tab-btn:hover{background:rgba(255,255,255,.08);color:rgba(255,255,255,.7)}
.tab-btn.active{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.3);color:#fbbf24;box-shadow:0 4px 16px rgba(245,158,11,.1)}
.tab-panel{display:none}
.tab-panel.active{display:block}

.online-dot{width:8px;height:8px;border-radius:50%;background:#4ade80;animation:pulse 2s ease infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.4)}50%{box-shadow:0 0 0 6px rgba(74,222,128,0)}}

.toast-box{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column-reverse;gap:8px;pointer-events:none}
.toast{display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:14px;font-size:.84rem;font-weight:500;pointer-events:auto;transform:translateX(120%);opacity:0;transition:all .35s cubic-bezier(.22,1,.36,1);max-width:360px;backdrop-filter:blur(16px);box-shadow:0 8px 32px rgba(0,0,0,.3)}
.toast.show{transform:translateX(0);opacity:1}
.toast.exit{transform:translateX(120%);opacity:0}
.toast-success{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.25)}
.toast-error{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.25)}
</style>
</head>
<body>

<div class="glass-bg"></div>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>
<div class="blob blob-4"></div>

<div class="toast-box" id="toastContainer"></div>

<!-- NAV -->
<nav class="nav-glass fixed top-0 left-0 right-0 z-50" id="mainNav">
  <div class="max-w-[900px] mx-auto px-5 flex items-center h-16 gap-4">
    <a href="index.php" class="flex items-center gap-2.5 flex-shrink-0 no-underline">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,rgba(245,158,11,.8),rgba(234,88,12,.8));border:1px solid rgba(255,255,255,.15)"><i class="fa-solid fa-headphones text-white text-sm"></i></div>
      <span class="text-xl font-extrabold tracking-tight text-white">ahmus<span class="text-amber-400">Shop</span></span>
    </a>
    <div class="flex-1"></div>
    <a href="index.php" class="glass-btn-dark inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold no-underline"><i class="fa-solid fa-arrow-left text-xs"></i>Back to Shop</a>
    <a href="../user/logout.php" class="glass-btn-dark inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold no-underline" style="color:#f87171;border-color:rgba(239,68,68,.2)"><i class="fa-solid fa-right-from-bracket text-xs"></i>Logout</a>
  </div>
</nav>

<!-- MAIN -->
<main class="pt-24 pb-12">
  <div class="max-w-[900px] mx-auto px-5">

    <!-- Header -->
    <div class="text-center mb-10 rv on">
      <div class="sec-label mb-3"><i class="fa-solid fa-user-circle text-amber-400" style="font-size:.6rem"></i>My Account</div>
      <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-white">My <span class="glow-text">Profile</span></h1>
    </div>

    <!-- Profile Card -->
    <div class="glass rounded-[24px] p-8 relative overflow-hidden mb-6 rv d1">
      <div class="profile-glow"></div>
      <div class="absolute top-0 left-0 right-0 h-28 rounded-t-[24px]" style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(139,92,246,.15),rgba(6,182,212,.1));border-bottom:1px solid rgba(255,255,255,.06)"></div>
      <div class="relative flex flex-col items-center pt-8">
        <div class="profile-avatar-ring">
          <?php if (!empty($user['image']) && file_exists("../backend/uploads/" . $user['image'])): ?>
          <img src="../backend/uploads/<?= $user['image'] ?>" alt="<?= htmlspecialchars($user['name']) ?>">
          <?php else: ?>
          <div class="avatar-fallback"><?= strtoupper(mb_substr($user['name'], 0, 1)) ?></div>
          <?php endif; ?>
        </div>
        <h2 class="text-2xl font-extrabold text-white mt-5"><?= htmlspecialchars($user['name']) ?></h2>
        <p class="text-sm mt-1" style="color:rgba(255,255,255,.4)"><?= htmlspecialchars($user['email']) ?></p>
        <?php if ($userStats['member_since']): ?>
        <div class="flex items-center gap-1.5 mt-3 px-4 py-1.5 rounded-full" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.15)">
          <i class="fa-solid fa-crown text-xs" style="color:#fbbf24"></i>
          <span class="text-xs font-semibold" style="color:#fbbf24">Member since <?= $userStats['member_since'] ?></span>
        </div>
        <?php endif; ?>

        <!-- Info Rows -->
        <div class="w-full mt-6 flex flex-col gap-3">
          <div class="info-row">
            <div class="info-icon" style="background:rgba(245,158,11,.1);color:#fbbf24"><i class="fa-solid fa-envelope"></i></div>
            <div class="flex-1 min-w-0"><div class="info-label">Email</div><div class="info-value truncate"><?= htmlspecialchars($user['email']) ?></div></div>
          </div>
          <?php if (!empty($user['phone'])): ?>
          <div class="info-row">
            <div class="info-icon" style="background:rgba(34,197,94,.1);color:#4ade80"><i class="fa-solid fa-phone"></i></div>
            <div class="flex-1 min-w-0"><div class="info-label">Phone</div><div class="info-value"><?= htmlspecialchars($user['phone']) ?></div></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($user['address'])): ?>
          <div class="info-row">
            <div class="info-icon" style="background:rgba(59,130,246,.1);color:#60a5fa"><i class="fa-solid fa-location-dot"></i></div>
            <div class="flex-1 min-w-0"><div class="info-label">Address</div><div class="info-value"><?= htmlspecialchars($user['address']) ?></div></div>
          </div>
          <?php endif; ?>
          <div class="info-row">
            <div class="info-icon" style="background:rgba(139,92,246,.1);color:#a78bfa"><i class="fa-solid fa-fingerprint"></i></div>
            <div class="flex-1 min-w-0"><div class="info-label">Account ID</div><div class="info-value">#<?= str_pad($user['id'], 5, '0') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 rv d2">
      <div class="profile-stat">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2" style="background:rgba(245,158,11,.1);color:#fbbf24"><i class="fa-solid fa-box text-sm"></i></div>
        <span class="text-2xl font-extrabold text-white"><?= $userStats['total_orders'] ?></span>
        <span class="text-[11px] mt-0.5" style="color:rgba(255,255,255,.35)">Orders</span>
      </div>
      <div class="profile-stat">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2" style="background:rgba(34,197,94,.1);color:#4ade80"><i class="fa-solid fa-bag-shopping text-sm"></i></div>
        <span class="text-2xl font-extrabold text-white"><?= $count ?></span>
        <span class="text-[11px] mt-0.5" style="color:rgba(255,255,255,.35)">Cart Items</span>
      </div>
      <div class="profile-stat">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2" style="background:rgba(139,92,246,.1);color:#a78bfa"><i class="fa-solid fa-wallet text-sm"></i></div>
        <span class="text-xl font-extrabold text-white">Rs.<?= number_format($userStats['total_spent'], 0) ?></span>
        <span class="text-[11px] mt-0.5" style="color:rgba(255,255,255,.35)">Spent</span>
      </div>
      <div class="profile-stat">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-2" style="background:rgba(6,182,212,.1);color:#22d3ee"><i class="fa-solid fa-circle-check text-sm"></i></div>
        <span class="text-2xl font-extrabold text-white">Active</span>
        <span class="text-[11px] mt-0.5" style="color:rgba(255,255,255,.35)">Status</span>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6 rv d3 overflow-x-auto pb-1">
      <button class="tab-btn active" onclick="switchTab('orders',this)"><i class="fa-solid fa-clock-rotate-left mr-2 text-xs"></i>Orders</button>
      <button class="tab-btn" onclick="switchTab('edit',this)"><i class="fa-solid fa-pen-to-square mr-2 text-xs"></i>Edit Profile</button>
      <a href="cart.php" class="tab-btn no-underline inline-flex items-center"><i class="fa-solid fa-bag-shopping mr-2 text-xs"></i>Cart <?= $count > 0 ? '<span class="ml-1 text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:rgba(245,158,11,.15);color:#fbbf24">' . $count . '</span>' : '' ?></a>
    </div>

    <!-- TAB: Orders -->
    <div class="tab-panel active" id="tab-orders">
      <div class="glass rounded-[20px] p-6 rv d4">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-base font-bold text-white flex items-center gap-2"><i class="fa-solid fa-clock-rotate-left text-amber-400 text-sm"></i>Order History</h3>
          <span class="text-xs font-semibold px-3 py-1 rounded-lg" style="background:rgba(255,255,255,.04);color:rgba(255,255,255,.3)"><?= count($recentOrders) ?> recent</span>
        </div>

        <?php if (!empty($recentOrders)): ?>
        <div class="flex flex-col gap-3">
          <?php foreach ($recentOrders as $ro):
              $st = strtolower($ro['status']);
              $statusClass = 'status-' . $st;
              $statusIcon = 'fa-clock';
              if ($st == 'processing') $statusIcon = 'fa-gear';
              elseif ($st == 'shipped') $statusIcon = 'fa-truck';
              elseif ($st == 'delivered') $statusIcon = 'fa-circle-check';
              elseif ($st == 'cancelled') $statusIcon = 'fa-circle-xmark';

              $payIcon = 'fa-money-bill';
              $pm = strtolower($ro['payment_method'] ?? '');
              if (strpos($pm, 'jazz') !== false) $payIcon = 'fa-mobile-screen';
              elseif (strpos($pm, 'easypaisa') !== false) $payIcon = 'fa-mobile-screen';
              elseif (strpos($pm, 'bank') !== false) $payIcon = 'fa-building-columns';
              elseif (strpos($pm, 'card') !== false) $payIcon = 'fa-credit-card';
              elseif (strpos($pm, 'cod') !== false || strpos($pm, 'cash') !== false) $payIcon = 'fa-money-bill-wave';
          ?>
          <div class="order-chip">
            <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0" style="border:1px solid rgba(255,255,255,.06)">
              <?php if (!empty($ro['product_image']) && file_exists("../backend/uploads/" . $ro['product_image'])): ?>
              <img src="../backend/uploads/<?= $ro['product_image'] ?>" alt="" class="w-full h-full object-cover">
              <?php else: ?>
              <div class="w-full h-full flex items-center justify-center" style="background:rgba(255,255,255,.04)"><i class="fa-solid fa-box text-sm" style="color:rgba(255,255,255,.15)"></i></div>
              <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($ro['product_name'] ?? 'Order #' . $ro['order_id']) ?></div>
              <div class="flex flex-wrap items-center gap-2 mt-1">
                <span class="text-xs font-bold text-amber-400">Rs.<?= number_format($ro['total_amount'], 0) ?></span>
                <span class="text-[11px]" style="color:rgba(255,255,255,.25)"><?= date('d M Y', strtotime($ro['created_at'])) ?></span>
                <?php if (!empty($ro['payment_method'])): ?>
                <span class="pay-badge"><i class="fa-solid <?= $payIcon ?>"></i><?= htmlspecialchars(ucfirst($ro['payment_method'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($ro['shipping_city'])): ?>
                <span class="pay-badge"><i class="fa-solid fa-location-dot"></i><?= htmlspecialchars(ucfirst($ro['shipping_city'])) ?></span>
                <?php endif; ?>
              </div>
              <div class="text-[11px] mt-1" style="color:rgba(255,255,255,.2)">Order #<?= str_pad($ro['order_id'], 5, '0') ?></div>
            </div>
            <span class="status-badge <?= $statusClass ?>"><i class="fa-solid <?= $statusIcon ?>"></i><?= htmlspecialchars($ro['status']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-16">
          <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:rgba(255,255,255,.04)"><i class="fa-solid fa-box-open text-3xl" style="color:rgba(255,255,255,.1)"></i></div>
          <p class="text-base font-semibold mb-2" style="color:rgba(255,255,255,.4)">No orders yet</p>
          <p class="text-sm mb-6" style="color:rgba(255,255,255,.25)">Start shopping to see your orders here.</p>
          <a href="index.php" class="glass-btn inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold no-underline"><i class="fa-solid fa-bag-shopping text-xs"></i>Start Shopping</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- TAB: Edit Profile -->
    <div class="tab-panel" id="tab-edit">
      <div class="glass rounded-[20px] p-8 rv d4">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.2)"><i class="fa-solid fa-pen-to-square text-amber-400"></i></div>
          <div><h3 class="text-base font-bold text-white">Edit Profile</h3><p class="text-xs" style="color:rgba(255,255,255,.3)">Update your personal information</p></div>
        </div>
        <form method="POST" action="" id="editForm" novalidate>
          <input type="hidden" name="action" value="update_profile">
          <div class="grid sm:grid-cols-2 gap-5 mb-5">
            <div>
              <label class="block text-xs font-semibold mb-2" style="color:rgba(255,255,255,.5)">Full Name</label>
              <input type="text" name="name" class="edit-field" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div>
              <label class="block text-xs font-semibold mb-2" style="color:rgba(255,255,255,.5)">Email</label>
              <input type="email" name="email" class="edit-field" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div>
              <label class="block text-xs font-semibold mb-2" style="color:rgba(255,255,255,.5)">Phone</label>
              <input type="text" name="phone" class="edit-field" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="03XX-XXXXXXX">
            </div>
            <div>
              <label class="block text-xs font-semibold mb-2" style="color:rgba(255,255,255,.5)">City</label>
              <input type="text" name="city" class="edit-field" placeholder="e.g. Karachi" value="">
            </div>
          </div>
          <div class="mb-5">
            <label class="block text-xs font-semibold mb-2" style="color:rgba(255,255,255,.5)">Address</label>
            <textarea name="address" class="edit-field" rows="3" placeholder="Your full address..." style="resize:vertical;min-height:80px"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
          </div>
          <div class="mb-5">
            <label class="block text-xs font-semibold mb-2" style="color:rgba(255,255,255,.5)">Profile Picture</label>
            <div class="flex items-center gap-4">
              <?php if (!empty($user['image']) && file_exists("../backend/uploads/" . $user['image'])): ?>
              <img src="../backend/uploads/<?= $user['image'] ?>" alt="" class="w-16 h-16 rounded-xl object-cover" style="border:1px solid rgba(255,255,255,.1)">
              <?php else: ?>
              <div class="w-16 h-16 rounded-xl flex items-center justify-center" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)"><i class="fa-solid fa-user text-xl" style="color:rgba(255,255,255,.15)"></i></div>
              <?php endif; ?>
              <label class="glass-btn-dark inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold cursor-pointer no-underline transition-all hover:bg-white/[.14]">
                <i class="fa-solid fa-cloud-arrow-up"></i>Choose File
                <input type="file" name="image" accept="image/*" class="hidden" onchange="this.parentElement.querySelector('span').textContent=this.files[0].name">
                <span class="max-w-[120px] truncate" style="color:rgba(255,255,255,.35)">No file chosen</span>
              </label>
            </div>
          </div>
          <button type="submit" name="update_profile" class="glass-btn w-full flex items-center justify-center gap-2 py-3.5 rounded-xl text-sm font-bold no-underline cursor-pointer border-none">
            <i class="fa-solid fa-floppy-disk text-xs"></i>Save Changes
          </button>
        </form>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 rv d5">
      <a href="index.php" class="glass rounded-2xl p-5 flex flex-col items-center gap-2.5 no-underline transition-all hover:bg-white/[.08] group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all group-hover:scale-110" style="background:rgba(245,158,11,.1);color:#fbbf24"><i class="fa-solid fa-house text-lg"></i></div>
        <span class="text-xs font-semibold text-white">Home</span>
      </a>
      <a href="cart.php" class="glass rounded-2xl p-5 flex flex-col items-center gap-2.5 no-underline transition-all hover:bg-white/[.08] group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all group-hover:scale-110" style="background:rgba(34,197,94,.1);color:#4ade80"><i class="fa-solid fa-bag-shopping text-lg"></i></div>
        <span class="text-xs font-semibold text-white">Cart</span>
        <?php if ($count > 0): ?><span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:rgba(245,158,11,.15);color:#fbbf24"><?= $count ?></span><?php endif; ?>
      </a>
      <a href="#contact" class="glass rounded-2xl p-5 flex flex-col items-center gap-2.5 no-underline transition-all hover:bg-white/[.08] group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all group-hover:scale-110" style="background:rgba(59,130,246,.1);color:#60a5fa"><i class="fa-solid fa-headset text-lg"></i></div>
        <span class="text-xs font-semibold text-white">Support</span>
      </a>
      <a href="../user/logout.php" class="glass rounded-2xl p-5 flex flex-col items-center gap-2.5 no-underline transition-all hover:bg-white/[.08] group" style="border:1px solid rgba(239,68,68,.1)">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all group-hover:scale-110" style="background:rgba(239,68,68,.1);color:#f87171"><i class="fa-solid fa-right-from-bracket text-lg"></i></div>
        <span class="text-xs font-semibold" style="color:#f87171">Logout</span>
      </a>
    </div>

    <!-- Online Widget -->
    <div class="fixed bottom-6 right-5 z-40 glass rounded-xl px-3.5 py-2 flex items-center gap-2.5">
      <div class="online-dot"></div>
      <span class="text-xs font-medium" style="color:rgba(255,255,255,.5)"><span id="onlineNum">0</span> visiting</span>
    </div>

  </div>
</main>

<script>
// Reveal
(function(){var o=new IntersectionObserver(function(e,obs){e.forEach(function(el){if(el.isIntersecting){el.target.classList.add('on');obs.unobserve(el.target)}})},{threshold:.08});document.querySelectorAll('.rv').forEach(function(el){o.observe(el)})})();

// Nav scroll
window.addEventListener('scroll',function(){var n=document.getElementById('mainNav');if(n)n.classList.toggle('scrolled',window.scrollY>20)});

// Tabs
function switchTab(name,btn){
  document.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('active')});
  btn.classList.add('active');
  document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('active')});
  var panel=document.getElementById('tab-'+name);
  if(panel){panel.classList.add('active');panel.querySelectorAll('.rv').forEach(function(el){el.classList.remove('on')});var obs=new IntersectionObserver(function(e,o){e.forEach(function(el){if(el.isIntersecting){el.classList.add('on');o.unobserve(el.target)}})},{threshold:.08});panel.querySelectorAll('.rv').forEach(function(el){obs.observe(el)})}
}

// Online widget
(function(){var n=Math.floor(Math.random()*30)+12,el=document.getElementById('onlineNum');if(el)el.textContent=n;setInterval(function(){n+=Math.floor(Math.random()*5)-2;n=Math.max(5,Math.min(60,n));if(el)el.textContent=n},4000)})();

// Toast
function showToast(msg,type){var c=document.getElementById('toastContainer');if(!c)return;var el=document.createElement('div');el.className='toast toast-'+(type||'info');var icons={success:'fa-circle-check',error:'fa-circle-xmark'};el.innerHTML='<i class="fa-solid '+(icons[type]||'fa-circle-info')+'"></i><span>'+msg+'</span>';c.appendChild(el);requestAnimationFrame(function(){requestAnimationFrame(function(){el.classList.add('show')})});setTimeout(function(){el.classList.remove('show');el.classList.add('exit');setTimeout(function(){el.remove()},400)},3500)}

// Edit form
var ef=document.getElementById('editForm');
if(ef){ef.addEventListener('submit',function(e){
  var valid=true;
  this.querySelectorAll('.edit-field[required]').forEach(function(i){
    i.style.borderColor='rgba(255,255,255,.12)';
    if(!i.value.trim()){i.style.borderColor='rgba(239,68,68,.6)';valid=false}
  });
  var em=this.querySelector('input[type="email"]');
  if(em&&em.value.trim()&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim())){em.style.borderColor='rgba(239,68,68,.6)';valid=false}
  if(!valid)e.preventDefault();
})}
</script>
</body>
</html>