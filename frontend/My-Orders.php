<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}
include("../backend/config/db.php");

 $uid = $_SESSION['user_id'];
 $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, email, image FROM users WHERE id = $uid LIMIT 1"));

/* ── Pagination ── */
 $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
 $per_page = 5;
 $offset = ($page - 1) * $per_page;

/* ── Total orders (from orders table directly) ── */
 $total_orders = 0;
 $tot_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE user_id = $uid");
if ($tot_q) {
    $tot_row = mysqli_fetch_assoc($tot_q);
    $total_orders = intval($tot_row['cnt']);
}
 $total_pages = max(1, ceil($total_orders / $per_page));

/* ── Fetch orders with items ── */
 $orders_query = "
    SELECT o.order_id, o.total_amount, o.status, o.created_at, o.updated_at,
           o.shipping_name, o.shipping_phone, o.shipping_address, o.shipping_city,
           o.payment_method,
           oi.id as item_id, oi.product_id, oi.product_name as item_name,
           oi.price as item_price, oi.quantity as item_qty, oi.image as item_image
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_db_id
    WHERE o.user_id = $uid
    ORDER BY o.order_id DESC
    LIMIT $offset, $per_page
";
 $orders_result = mysqli_query($conn, $orders_query);

/* ── Group items by order ── */
 $orders = array();
if ($orders_result) {
    while ($row = mysqli_fetch_assoc($orders_result)) {
        $oid = $row['order_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = array(
                'order_id' => $oid,
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'shipping_name' => $row['shipping_name'],
                'shipping_phone' => $row['shipping_phone'],
                'shipping_address' => $row['shipping_address'],
                'shipping_city' => $row['shipping_city'],
                'payment_method' => $row['payment_method'],
                'items' => array()
            );
        }
        $orders[$oid]['items'][] = array(
            'item_id' => $row['item_id'],
            'product_id' => $row['product_id'],
            'name' => $row['item_name'],
            'price' => $row['item_price'],
            'qty' => $row['item_qty'],
            'image' => $row['item_image']
        );
    }
}

/* ── Order stats ── */
 $stats = array('total' => $total_orders, 'pending' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0, 'spent' => 0);
 $st_res = mysqli_query($conn, "
    SELECT status, COUNT(*) as cnt, IFNULL(SUM(total_amount),0) as amt
    FROM orders WHERE user_id = $uid
    GROUP BY status
");
if ($st_res) {
    while ($sr = mysqli_fetch_assoc($st_res)) {
        $stats['spent'] += floatval($sr['amt']);
        $st = strtolower($sr['status']);
        if (isset($stats[$st])) $stats[$st] = intval($sr['cnt']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — AHMUS Shop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;overflow-x:hidden;color:#fff;min-height:100vh}
.glass-bg{position:fixed;inset:0;z-index:-1;background:linear-gradient(135deg,#0f0c29 0%,#1a1a3e 25%,#24243e 50%,#0f0c29 100%)}
.blob{position:fixed;border-radius:50%;filter:blur(80px);opacity:.3;pointer-events:none;z-index:-1}
.blob-1{width:500px;height:500px;background:radial-gradient(circle,#f59e0b,transparent 70%);top:-10%;left:-5%;animation:bf1 18s ease-in-out infinite}
.blob-2{width:450px;height:450px;background:radial-gradient(circle,#8b5cf6,transparent 70%);top:30%;right:-10%;animation:bf2 22s ease-in-out infinite}
.blob-3{width:400px;height:400px;background:radial-gradient(circle,#06b6d4,transparent 70%);bottom:-5%;left:20%;animation:bf3 20s ease-in-out infinite}
@keyframes bf1{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(60px,40px) scale(1.1)}66%{transform:translate(-30px,70px) scale(.95)}}
@keyframes bf2{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(-50px,-30px) scale(1.08)}66%{transform:translate(40px,50px) scale(.92)}}
@keyframes bf3{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(40px,-60px) scale(1.05)}66%{transform:translate(-60px,20px) scale(.97)}}

.glass{background:rgba(255,255,255,.06);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);box-shadow:0 8px 32px rgba(0,0,0,.15),inset 0 1px 0 rgba(255,255,255,.08)}
.glass-btn{background:linear-gradient(135deg,rgba(245,158,11,.85),rgba(234,88,12,.85));backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);color:#fff;font-family:'Inter',sans-serif;box-shadow:0 4px 20px rgba(245,158,11,.25);transition:all .3s;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
.glass-btn:hover{box-shadow:0 6px 28px rgba(245,158,11,.4);transform:translateY(-1px)}
.glass-btn-dark{background:rgba(255,255,255,.08);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);color:#fff;font-family:'Inter',sans-serif;transition:all .25s;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
.glass-btn-dark:hover{background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.25)}

.nav-glass{background:rgba(15,12,41,.5);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.06);transition:all .3s}
.nav-glass.scrolled{background:rgba(15,12,41,.75);border-bottom-color:rgba(255,255,255,.1);box-shadow:0 4px 30px rgba(0,0,0,.3)}

.rv{opacity:0;transform:translateY(30px);transition:all .8s cubic-bezier(.22,1,.36,1);will-change:opacity,transform}
.rv.on{opacity:1;transform:none}
.d1{transition-delay:.05s}.d2{transition-delay:.1s}.d3{transition-delay:.15s}.d4{transition-delay:.2s}.d5{transition-delay:.25s}

.glow-text{background:linear-gradient(135deg,#fbbf24,#f97316,#ef4444,#f472b6,#a78bfa,#38bdf8,#fbbf24);background-size:300% 300%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:gs 6s ease infinite}
@keyframes gs{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
.sec-label{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#fbbf24}

.status-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:99px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.status-pending{background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.2)}
.status-processing{background:rgba(59,130,246,.12);color:#60a5fa;border:1px solid rgba(59,130,246,.2)}
.status-shipped{background:rgba(139,92,246,.12);color:#a78bfa;border:1px solid rgba(139,92,246,.2)}
.status-delivered{background:rgba(34,197,94,.12);color:#4ade80;border:1px solid rgba(34,197,94,.2)}
.status-cancelled{background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.2)}

.pay-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:.62rem;font-weight:600;color:rgba(255,255,255,.4);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)}

.order-card{border-radius:20px;overflow:hidden;background:rgba(255,255,255,.05);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.08);box-shadow:0 8px 32px rgba(0,0,0,.12);transition:all .3s}
.order-card:hover{border-color:rgba(255,255,255,.15);box-shadow:0 12px 40px rgba(0,0,0,.2);background:rgba(255,255,255,.07)}

.product-row{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04);transition:background .2s}
.product-row:last-child{border-bottom:none;padding-bottom:0}
.product-row:hover{background:rgba(255,255,255,.03);border-radius:12px}
.product-thumb{width:56px;height:56px;border-radius:12px;overflow:hidden;flex-shrink:0;border:1px solid rgba(255,255,255,.06)}

.stat-card{display:flex;flex-direction:column;align-items:center;padding:16px 12px;border-radius:16px;background:rgba(255,255,255,.04);border:1px;solid rgba(255,255,255,.06);transition:all .25s}
.stat-card:hover{background:rgba(255,255,255,.08);transform:translateY(-2px)}

.page-btn{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.4);font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;font-family:'Inter',sans-serif}
.page-btn:hover{background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.2)}
.page-btn.active{background:linear-gradient(135deg,rgba(245,158,11,.7),rgba(234,88,12,.7));border-color:rgba(245,158,11,.4);color:#fff;box-shadow:0 4px 16px rgba(245,158,11,.2)}
.page-btn:disabled{opacity:.3;cursor:not-allowed;pointer-events:none}

.online-dot{width:8px;height:8px;border-radius:50%;background:#4ade80;animation:pulse 2s ease infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.4)}50%{box-shadow:0 0 0 6px rgba(74,222,128,0)}}

.toast-box{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column-reverse;gap:8px;pointer-events:none}
.toast{display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:14px;font-size:.84rem;font-weight:500;pointer-events:auto;transform:translateX(120%);opacity:0;transition:all .35s cubic-bezier(.22,1,.36,1);max-width:360px;backdrop-filter:blur(16px);box-shadow:0 8px 32px rgba(0,0,0,.3)}
.toast.show{transform:translateX(0);opacity:1}
.toast.exit{transform:translateX(120%);opacity:0}
.toast-success{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.25)}
.toast-error{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.25)}
.toast-info{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.25)}
</style>
</head>
<body>

<div class="glass-bg"></div>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<div class="toast-box" id="toastContainer"></div>

<!-- NAV -->
<nav class="nav-glass fixed top-0 left-0 right-0 z-50" id="mainNav">
  <div class="max-w-[1000px] mx-auto px-5 flex items-center h-16 gap-4">
    <a href="index.php" class="flex items-center gap-2.5 flex-shrink-0 no-underline">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,rgba(245,158,11,.8),rgba(234,88,12,.8));border:1px solid rgba(255,255,255,.15)"><i class="fa-solid fa-headphones text-white text-sm"></i></div>
      <span class="text-xl font-extrabold tracking-tight text-white">ahmus<span class="text-amber-400">Shop</span></span>
    </a>
    <div class="flex-1"></div>
    <a href="My-profile.php" class="glass-btn-dark px-4 py-2 rounded-xl text-sm font-semibold no-underline"><i class="fa-solid fa-user text-xs mr-1.5"></i>Profile</a>
    <a href="cart.php" class="glass-btn-dark px-4 py-2 rounded-xl text-sm font-semibold no-underline"><i class="fa-solid fa-bag-shopping text-xs mr-1.5"></i>Cart</a>
  </div>
</nav>

<main class="pt-24 pb-16">
  <div class="max-w-[1000px] mx-auto px-5">

    <!-- Header -->
    <div class="text-center mb-10 rv on">
      <div class="sec-label mb-3"><i class="fa-solid fa-box text-amber-400" style="font-size:.6rem"></i>Order History</div>
      <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-white">My <span class="glow-text">Orders</span></h1>
      <p class="text-sm mt-2" style="color:rgba(255,255,255,.35)"><?= $total_orders ?> total orders placed</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-8 rv d1">
      <div class="stat-card">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-1.5" style="background:rgba(245,158,11,.1);color:#fbbf24"><i class="fa-solid fa-box text-xs"></i></div>
        <span class="text-lg font-extrabold text-white"><?= $stats['total'] ?></span>
        <span class="text-[10px]" style="color:rgba(255,255,255,.3)">Total</span>
      </div>
      <div class="stat-card">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-1.5" style="background:rgba(245,158,11,.1);color:#fbbf24"><i class="fa-solid fa-clock text-xs"></i></div>
        <span class="text-lg font-extrabold text-white"><?= $stats['pending'] ?></span>
        <span class="text-[10px]" style="color:rgba(255,255,255,.3)">Pending</span>
      </div>
      <div class="stat-card">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-1.5" style="background:rgba(59,130,246,.1);color:#60a5fa"><i class="fa-solid fa-gear text-xs"></i></div>
        <span class="text-lg font-extrabold text-white"><?= $stats['processing'] ?></span>
        <span class="text-[10px]" style="color:rgba(255,255,255,.3)">Processing</span>
      </div>
      <div class="stat-card">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-1.5" style="background:rgba(139,92,246,.1);color:#a78bfa"><i class="fa-solid fa-truck text-xs"></i></div>
        <span class="text-lg font-extrabold text-white"><?= $stats['shipped'] ?></span>
        <span class="text-[10px]" style="color:rgba(255,255,255,.3)">Shipped</span>
      </div>
      <div class="stat-card">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-1.5" style="background:rgba(34,197,94,.1);color:#4ade80"><i class="fa-solid fa-circle-check text-xs"></i></div>
        <span class="text-lg font-extrabold text-white"><?= $stats['delivered'] ?></span>
        <span class="text-[10px]" style="color:rgba(255,255,255,.3)">Delivered</span>
      </div>
      <div class="stat-card">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-1.5" style="background:rgba(139,92,246,.1);color:#a78bfa"><i class="fa-solid fa-wallet text-xs"></i></div>
        <span class="text-sm font-extrabold text-white">Rs.<?= number_format($stats['spent'], 0) ?></span>
        <span class="text-[10px]" style="color:rgba(255,255,255,.3)">Spent</span>
      </div>
    </div>

    <!-- Orders -->
    <div class="flex flex-col gap-5">
    <?php if (!empty($orders)): ?>
    <?php $oi = 0; foreach ($orders as $oid => $order):
        $oi++;
        $dc = 'd' . (1 + (($oi - 1) % 3));
        $st = strtolower($order['status']);
        $statusClass = 'status-' . $st;
        $statusIcon = 'fa-clock';
        if ($st == 'processing') $statusIcon = 'fa-gear';
        elseif ($st == 'shipped') $statusIcon = 'fa-truck';
        elseif ($st == 'delivered') $statusIcon = 'fa-circle-check';
        elseif ($st == 'cancelled') $statusIcon = 'fa-circle-xmark';

        $payIcon = 'fa-money-bill';
        $pm = strtolower($order['payment_method'] ?? '');
        if (strpos($pm, 'jazz') !== false) $payIcon = 'fa-mobile-screen';
        elseif (strpos($pm, 'easypaisa') !== false) $payIcon = 'fa-mobile-screen';
        elseif (strpos($pm, 'bank') !== false) $payIcon = 'fa-building-columns';
        elseif (strpos($pm, 'card') !== false) $payIcon = 'fa-credit-card';
        elseif (strpos($pm, 'cod') !== false || strpos($pm, 'cash') !== false) $payIcon = 'fa-money-bill-wave';

        $item_count = count($order['items']);
        $isCancelled = ($st === 'cancelled');
    ?>
    <div class="order-card rv <?= $dc ?>" style="<?= $isCancelled ? 'opacity:.6' : '' ?>">
      <div class="p-5 pb-3" style="border-bottom:1px solid rgba(255,255,255,.05)">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.4)">#<?= str_pad($oid, 5, '0') ?></span>
              <span class="status-badge <?= $statusClass ?>"><i class="fa-solid <?= $statusIcon ?>"></i><?= htmlspecialchars(ucfirst($order['status'])) ?></span>
              <?php if (!empty($order['payment_method'])): ?>
              <span class="pay-badge"><i class="fa-solid <?= $payIcon ?>"></i><?= htmlspecialchars(ucfirst($order['payment_method'])) ?></span>
              <?php endif; ?>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]" style="color:rgba(255,255,255,.3)">
              <span><i class="fa-regular fa-calendar mr-1"></i><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
              <?php if (!empty($order['shipping_city'])): ?>
              <span><i class="fa-solid fa-location-dot mr-1"></i><?= htmlspecialchars(ucfirst($order['shipping_city'])) ?></span>
              <?php endif; ?>
              <span><i class="fa-solid fa-box mr-1"></i><?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?></span>
            </div>
          </div>
          <div class="text-right flex-shrink-0">
            <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:rgba(255,255,255,.25)">Total</div>
            <div class="text-xl font-extrabold text-amber-400">Rs.<?= number_format($order['total_amount'], 0) ?></div>
          </div>
        </div>
        <?php if (!empty($order['shipping_name']) || !empty($order['shipping_address'])): ?>
        <div class="mt-2 flex items-start gap-2 px-3 py-2 rounded-lg" style="background:rgba(255,255,255,.02)">
          <i class="fa-solid fa-truck text-[10px] mt-0.5" style="color:rgba(255,255,255,.2)"></i>
          <div class="text-[11px] leading-relaxed" style="color:rgba(255,255,255,.35)">
            <?php
            $shipParts = array_filter(array($order['shipping_name'], $order['shipping_phone'], $order['shipping_address'], $order['shipping_city']));
            echo htmlspecialchars(implode(', ', $shipParts));
            ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="px-5 pb-5">
        <?php foreach ($order['items'] as $item): ?>
        <div class="product-row">
          <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="product-thumb no-underline">
            <?php if (!empty($item['image']) && file_exists("../backend/uploads/" . $item['image'])): ?>
            <img src="../backend/uploads/<?= $item['image'] ?>" alt="" class="w-full h-full object-cover">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center" style="background:rgba(255,255,255,.04)"><i class="fa-solid fa-box text-sm" style="color:rgba(255,255,255,.15)"></i></div>
            <?php endif; ?>
          </a>
          <div class="flex-1 min-w-0">
            <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="block text-sm font-semibold text-white truncate no-underline hover:text-amber-400 transition-colors" style="max-width:100%"><?= htmlspecialchars($item['name']) ?></a>
            <div class="flex items-center gap-3 mt-1">
              <span class="text-sm font-bold text-amber-400">Rs.<?= number_format($item['price'], 0) ?></span>
              <span class="text-xs" style="color:rgba(255,255,255,.25)">× <?= $item['qty'] ?></span>
              <span class="text-xs font-semibold" style="color:rgba(255,255,255,.4)">= Rs.<?= number_format($item['price'] * $item['qty'], 0) ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="glass rounded-[20px] p-16 text-center rv on">
      <div class="w-24 h-24 mx-auto mb-5 rounded-full flex items-center justify-center" style="background:rgba(255,255,255,.04)"><i class="fa-solid fa-box-open text-4xl" style="color:rgba(255,255,255,.1)"></i></div>
      <h3 class="text-xl font-bold mb-2 text-white">No Orders Yet</h3>
      <p class="text-sm mb-6" style="color:rgba(255,255,255,.35)">You haven't placed any orders yet. Start shopping to see them here!</p>
      <a href="index.php" class="glass-btn px-7 py-3 rounded-xl text-sm font-bold no-underline"><i class="fa-solid fa-bag-shopping text-xs mr-2"></i>Start Shopping</a>
    </div>
    <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-center gap-2 mt-10 rv d5">
      <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>" class="page-btn"><i class="fa-solid fa-chevron-left text-xs"></i></a>
      <?php endif; ?>
      <?php
      $start = max(1, $page - 2);
      $end = min($total_pages, $page + 2);
      for ($i = $start; $i <= $end; $i++):
      ?>
      <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
      <a href="?page=<?= $page + 1 ?>" class="page-btn"><i class="fa-solid fa-chevron-right text-xs"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Online Widget -->
    <div class="fixed bottom-6 right-5 z-40 glass rounded-xl px-3.5 py-2 flex items-center gap-2.5">
      <div class="online-dot"></div>
      <span class="text-xs font-medium" style="color:rgba(255,255,255,.5)"><span id="onlineNum">0</span> visiting</span>
    </div>

  </div>
</main>

<script>
(function(){var o=new IntersectionObserver(function(e,obs){e.forEach(function(el){if(el.isIntersecting){el.target.classList.add('on');obs.unobserve(el.target)}})},{threshold:.08});document.querySelectorAll('.rv').forEach(function(el){o.observe(el)})})();
window.addEventListener('scroll',function(){var n=document.getElementById('mainNav');if(n)n.classList.toggle('scrolled',window.scrollY>20)});
(function(){var n=Math.floor(Math.random()*30)+12,el=document.getElementById('onlineNum');if(el)el.textContent=n;setInterval(function(){n+=Math.floor(Math.random()*5)-2;n=Math.max(5,Math.min(60,n));if(el)el.textContent=n},4000)})();
</script>
</body>
</html>