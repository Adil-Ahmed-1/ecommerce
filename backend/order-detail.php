<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit;
}

 $uid = $_SESSION['user_id'];
 $user_res = mysqli_query($conn, "SELECT name, email, image, role FROM users WHERE id = $uid");
 $user = mysqli_fetch_assoc($user_res);
 $user_name = $user['name'] ?? 'Unknown';
 $user_email = $user['email'] ?? '';
 $user_image = !empty($user['image']) ? 'uploads/' . $user['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true';
 $user_role = ucfirst($user['role'] ?? 'user');

/* ===== GET ORDER ===== */
 $order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    header("Location: view.php");
    exit;
}

 $order_res = mysqli_query($conn, "
    SELECT o.*, u.email AS user_email, u.name AS user_name, u.phone AS user_phone
    FROM orders o 
    LEFT JOIN users u ON u.id = o.user_id 
    WHERE o.id = $order_id
");
 $order = mysqli_fetch_assoc($order_res);

if (!$order) {
    header("Location: view.php");
    exit;
}

/* ===== GET ORDER ITEMS ===== */
 $items_res = mysqli_query($conn, "
    SELECT * FROM order_items 
    WHERE order_db_id = $order_id 
    ORDER BY id ASC
");

/* ===== PROGRESS ===== */
 $status_steps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
 $current_index = array_search($order['status'], $status_steps);
if ($current_index === false) $current_index = -1;
 $is_cancelled = $order['status'] === 'cancelled';

/* ===== TIMELINE ===== */
 $timeline = [];
 $timeline[] = ['status' => $order['status'], 'label' => ucfirst($order['status']), 'time' => $order['updated_at'] ?? $order['created_at'], 'active' => true];
if ($order['created_at'] !== ($order['updated_at'] ?? null)) {
    $timeline[] = ['status' => 'pending', 'label' => 'Order Placed', 'time' => $order['created_at'], 'active' => false];
}

/* ===== CALCULATE TOTALS ===== */
 $subtotal = 0;
 $items_array = [];
while ($item = mysqli_fetch_assoc($items_res)) {
    $items_array[] = $item;
    $subtotal += $item['price'] * $item['quantity'];
}
 $shipping_charge = $order['shipping_charge'] ?? 0;
 $discount = $order['discount'] ?? 0;
 $grand_total = $subtotal + $shipping_charge - $discount;

/* ===== STATUS UPDATE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    if ($new_status === 'delivered') {
        mysqli_query($conn, "UPDATE orders SET status = '$new_status', payment_status = 'paid' WHERE id = $order_id");
    } else {
        mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE id = $order_id");
    }
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Order status updated to ' . ucfirst($new_status)];
    header("Location: order-detail.php?id=$order_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?= htmlspecialchars($order['order_id']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }, colors: { brand: { 50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442,800:'#095c37,900:'#084b2e',950:'#032a1a' }}}}}
</script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Plus Jakarta Sans',sans-serif}
  ::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.15);border-radius:99px}
  .sidebar-glass{background:rgba(8,75,46,0.95);backdrop-filter:blur(20px)}.dark .sidebar-glass{background:rgba(3,42,26,0.98)}
  .nav-link{position:relative;transition:all .25s cubic-bezier(.4,0,.2,1)}.nav-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;border-radius:0 4px 4px 0;background:#cbcd3a;transition:height .25s cubic-bezier(.4,0,.2,1)}
  .nav-link:hover::before,.nav-link.active::before{height:60%}.nav-link.active{background:rgba(58,205,126,0.12);color:#cbcd3a}.nav-link:hover{background:rgba(255,255,255,0.06)}
  .sidebar-collapsed .sidebar-text{opacity:0;width:0;overflow:hidden}.sidebar-collapsed .sidebar-logo-text{opacity:0;width:0;overflow:hidden}.sidebar-collapsed .sidebar-avatar{width:36px;height:36px}
  .topbar-border{position:relative}.topbar-border::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(58,205,126,0.3),transparent)}
  .role-badge{font-size:9px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:2px 7px;border-radius:6px}
  .role-admin{background:rgba(58,205,126,0.15);color:#cbcd3a}.role-user{background:rgba(96,165,250,0.15);color:#60a5fa}
  .toast{position:fixed;top:20px;right:20px;z-index:9999;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,0.15);animation:toastIn .3s ease,toastOut .3s ease 2.7s forwards}
  .toast-success{background:#10b981;color:#fff}.toast-error{background:#ef4444;color:#fff}
  @keyframes toastIn{from{opacity:0;transform:translateY(-20px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes toastOut{from{opacity:1;transform:translateY(0)}to{opacity:0;transform:translateY(-20px)}}
  .fade-up{opacity:0;transform:translateY(20px);animation:fadeUp .5s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
  .order-status{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:4px 12px;border-radius:8px;white-space:nowrap}
  .status-pending{background:rgba(245,158,11,0.12);color:#f59e0b}.status-confirmed{background:rgba(59,130,246,0.12);color:#3b82f6}
  .status-processing{background:rgba(139,92,246,0.12);color:#8b5cf6}.status-shipped{background:rgba(99,102,241,0.12);color:#6366f1}
  .status-delivered{background:rgba(16,185,129,0.12);color:#10b981}.status-cancelled{background:rgba(239,68,68,0.12);color:#ef4444}
  .info-card{background:#fff;border:1px solid #f0f0f0;border-radius:16px;padding:24px}
  .dark .info-card{background:#131a16;border-color:rgba(255,255,255,0.05)}
  .timeline-dot{width:12px;height:12px;border-radius:50%;border:2px solid #d1d5db;flex-shrink:0;position:relative;z-index:2}
  .timeline-dot.active{border-color:#16b364;background:#16b364;box-shadow:0 0 0 4px rgba(22,179,100,0.15)}
  .timeline-dot.cancelled-dot{border-color:#ef4444;background:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,0.15)}
  .timeline-line{width:2px;flex-shrink:0;background:#e5e7eb}.dark .timeline-line{background:rgba(255,255,255,0.08)}
  .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);z-index:100;display:flex;align-items:center;justify-content:center;animation:fadeIn .2s ease}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  .modal-box{animation:modalIn .3s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
  .dropdown-enter{animation:dropIn .2s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes dropIn{from{opacity:0;transform:translateY(-8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<?php if (isset($_SESSION['toast'])): ?>
  <div class="toast toast-<?= $_SESSION['toast']['type'] ?>">
    <i class="fa-solid fa-<?= $_SESSION['toast']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i>
    <?= htmlspecialchars($_SESSION['toast']['message']) ?>
  </div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<!-- ========== SIDEBAR ========== -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm"><i class="fa-solid fa-bars text-xs"></i></button>
  </div>
  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="<?= $user_image ?>" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
    <div class="sidebar-text transition-all duration-300">
      <div class="flex items-center gap-2"><p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p><span class="role-badge <?= $user_role === 'Admin' ? 'role-admin' : 'role-user' ?>"><?= $user_role ?></span></div>
      <p class="text-[11px] text-white/50 mt-0.5"><?= htmlspecialchars($user_email) ?></p>
    </div>
  </div>
  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <?php if ($user_role === 'Admin') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="sproduct/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="view.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- ========== MAIN ========== -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">

  <!-- TOPBAR -->
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div class="flex items-center gap-3">
      <a href="view.php" class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-500 dark:text-white/60">
        <i class="fa-solid fa-arrow-left text-xs"></i>
      </a>
      <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Order #<?= htmlspecialchars($order['order_id']) ?></h1>
        <p class="text-xs text-gray-400 mt-0.5">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <span class="order-status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70"><i class="fa-solid fa-moon text-sm"></i></button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
          <span class="hidden sm:block text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></span>
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl rounded-2xl w-48 py-2 dropdown-enter">
          <a href="../profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile</a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1"><a href="../logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition"><i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout</a></div>
        </div>
      </div>
    </div>
  </header>

  <!-- CONTENT -->
  <div class="px-8 py-6 space-y-6">

    <!-- ===== PROGRESS BAR ===== -->
    <div class="fade-up info-card" style="animation-delay:.05s">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Order Progress</h3>
        <?php if (!$is_cancelled && $order['status'] !== 'delivered'): ?>
        <button onclick="openStatusModal()" class="group flex items-center gap-2.5 text-xs font-semibold text-gray-600 dark:text-white/60 hover:text-brand-600 dark:hover:text-brand-400 transition-colors duration-300">
  <span class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-white/5 group-hover:bg-brand-50 dark:group-hover:bg-brand-500/10 flex items-center justify-center transition-all duration-300">
    <i class="fa-solid fa-pen-to-square text-[11px] text-gray-400 group-hover:text-brand-500 transition-colors duration-300"></i>
  </span>
  Update Status
</button>
        <?php endif; ?>
      </div>
      <?php if ($is_cancelled): ?>
        <div class="flex items-center gap-3 bg-red-50 dark:bg-red-500/5 rounded-xl px-5 py-4">
          <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-500/10 flex items-center justify-center"><i class="fa-solid fa-ban text-red-500"></i></div>
          <div>
            <p class="text-sm font-bold text-red-600 dark:text-red-400">Order Cancelled</p>
            <p class="text-xs text-red-400 mt-0.5">This order has been cancelled and cannot proceed further.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="flex items-center justify-between relative px-2">
          <div class="absolute top-[18px] left-[40px] right-[40px] h-[3px] bg-gray-100 dark:bg-white/5 rounded-full"></div>
          <?php if ($current_index >= 0): ?>
          <div class="absolute top-[18px] left-[40px] h-[3px] bg-brand-500 rounded-full transition-all duration-700" style="width: calc(<?= ($current_index / (count($status_steps) - 1)) * 100 ?>% - 0px);"></div>
          <?php endif; ?>
          <?php foreach ($status_steps as $i => $step): 
            $done = $i <= $current_index;
            $current = $i === $current_index;
            $icon_map = ['pending'=>'fa-clock','confirmed'=>'fa-circle-check','processing'=>'fa-gear','shipped'=>'fa-truck-fast','delivered'=>'fa-circle-check'];
          ?>
          <div class="flex flex-col items-center relative z-10" style="flex:1">
            <div class="w-[36px] h-[36px] rounded-full flex items-center justify-center text-sm transition-all duration-500 
              <?= $done ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/25' : 'bg-white dark:bg-[#1a2420] text-gray-300 dark:text-gray-600 border-2 border-gray-200 dark:border-white/10' ?>
              <?= $current ? 'ring-4 ring-brand-500/20' : '' ?>">
              <i class="fa-solid <?= $icon_map[$step] ?> text-xs"></i>
            </div>
            <p class="text-[10px] font-semibold mt-2.5 text-center leading-tight
              <?= $done ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400 dark:text-gray-600' ?>">
              <?= ucfirst($step) ?>
            </p>
            <?php if ($current): ?><span class="text-[9px] text-brand-500 font-bold mt-0.5">CURRENT</span><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ===== TWO COLUMNS ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- LEFT COLUMN -->
      <div class="lg:col-span-1 space-y-6">

        <!-- Customer Info -->
        <div class="fade-up info-card" style="animation-delay:.1s">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-brand-50 dark:bg-brand-950 flex items-center justify-center"><i class="fa-solid fa-user text-brand-500 text-[10px]"></i></div>
            Customer Info
          </h3>
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <img src="https://ui-avatars.com/api/?name=<?= urlencode($order['shipping_name']) ?>&background=16b364&color=fff&bold=true&size=40" class="w-10 h-10 rounded-xl">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($order['shipping_name']) ?></p>
                <p class="text-[11px] text-gray-400">Customer</p>
              </div>
            </div>
            <div class="pt-3 border-t border-gray-100 dark:border-white/5 space-y-2.5">
              <div class="flex items-center gap-2.5 text-xs"><i class="fa-solid fa-envelope w-4 text-center text-gray-300 dark:text-gray-600 text-[10px]"></i><span class="text-gray-500 dark:text-white/50"><?= htmlspecialchars($order['user_email'] ?? 'N/A') ?></span></div>
              <div class="flex items-center gap-2.5 text-xs"><i class="fa-solid fa-phone w-4 text-center text-gray-300 dark:text-gray-600 text-[10px]"></i><span class="text-gray-500 dark:text-white/50"><?= htmlspecialchars($order['shipping_phone']) ?></span></div>
            </div>
          </div>
        </div>

        <!-- Shipping Info -->
        <div class="fade-up info-card" style="animation-delay:.15s">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950 flex items-center justify-center"><i class="fa-solid fa-location-dot text-blue-500 text-[10px]"></i></div>
            Shipping Address
          </h3>
          <div class="text-xs text-gray-600 dark:text-white/60 space-y-1.5 leading-relaxed">
            <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($order['shipping_name']) ?></p>
            <p><?= htmlspecialchars($order['shipping_address']) ?></p>
            <p><?= htmlspecialchars($order['shipping_city'] ?? '') ?></p>
            <?php if (!empty($order['shipping_state'])): ?><p><?= htmlspecialchars($order['shipping_state']) ?></p><?php endif; ?>
            <?php if (!empty($order['shipping_zip'])): ?><p><?= htmlspecialchars($order['shipping_zip']) ?></p><?php endif; ?>
          </div>
        </div>

        <!-- Payment Info -->
        <div class="fade-up info-card" style="animation-delay:.2s">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-950 flex items-center justify-center"><i class="fa-solid fa-credit-card text-purple-500 text-[10px]"></i></div>
            Payment Info
          </h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-400">Method</span>
              <span class="text-xs font-semibold text-gray-900 dark:text-white"><?= ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-400">Status</span>
              <?php 
                $pay_status = $order['payment_status'] ?? 'pending';
                if ($order['status'] === 'delivered' && $pay_status !== 'failed') {
                    $pay_status = 'paid';
                }
                $pay_color = $pay_status === 'paid' ? 'text-green-500 bg-green-50 dark:bg-green-500/5' : ($pay_status === 'failed' ? 'text-red-500 bg-red-50 dark:bg-red-500/5' : 'text-amber-500 bg-amber-50 dark:bg-amber-500/5');
              ?>
              <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-lg <?= $pay_color ?>"><?= ucfirst($pay_status) ?></span>
            </div>
            <?php if (!empty($order['transaction_id'])): ?>
            <div class="flex justify-between items-center">
              <span class="text-xs text-gray-400">Txn ID</span>
              <span class="text-[10px] font-mono text-gray-500 dark:text-white/40"><?= htmlspecialchars($order['transaction_id']) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Timeline -->
        <div class="fade-up info-card" style="animation-delay:.25s">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center"><i class="fa-solid fa-timeline text-indigo-500 text-[10px]"></i></div>
            Timeline
          </h3>
          <div class="space-y-0">
            <?php foreach ($timeline as $ti => $t): 
              $is_last = $ti === count($timeline) - 1;
              $dot_class = ($t['status'] === 'cancelled') ? 'cancelled-dot' : 'active';
            ?>
            <div class="flex gap-4">
              <div class="flex flex-col items-center">
                <div class="timeline-dot <?= $dot_class ?>"></div>
                <?php if (!$is_last): ?><div class="timeline-line flex-1 my-1"></div><?php endif; ?>
              </div>
              <div class="pb-4 -mt-0.5">
                <p class="text-xs font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($t['label']) ?></p>
                <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-1"><?= date('d M Y, h:i A', strtotime($t['time'])) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

      <!-- RIGHT COLUMN -->
      <div class="lg:col-span-2 space-y-6">

        <!-- Order Items -->
        <div class="fade-up info-card" style="animation-delay:.1s">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950 flex items-center justify-center"><i class="fa-solid fa-bag-shopping text-amber-500 text-[10px]"></i></div>
            Order Items (<?= count($items_array) ?>)
          </h3>
          
          <?php if (count($items_array) > 0): ?>
          <div class="space-y-3">
            <?php foreach ($items_array as $item): 
              $img = !empty($item['image']) ? '../uploads/' . $item['image'] : 'https://ui-avatars.com/api/?name=Product&background=e5e7eb&color=6b7280&bold=true&size=80';
              $item_total = $item['price'] * $item['quantity'];
            ?>
            <div class="flex items-center gap-4 bg-gray-50/50 dark:bg-white/[0.02] rounded-xl p-3.5 hover:bg-gray-50 dark:hover:bg-white/[0.03] transition">
              <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 dark:bg-white/5 flex-shrink-0">
                <img src="<?= $img ?>" class="w-full h-full object-cover" onerror="this.src='https://ui-avatars.com/api/?name=Product&background=e5e7eb&color=6b7280&size=80'">
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></p>
                <div class="flex items-center gap-3 mt-1">
                  <span class="text-[11px] text-gray-400">Rs. <?= number_format($item['price'], 0) ?> × <?= $item['quantity'] ?></span>
                </div>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Rs. <?= number_format($item_total, 0) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div class="text-center py-10">
            <i class="fa-solid fa-box-open text-3xl text-gray-200 dark:text-gray-700 mb-3"></i>
            <p class="text-xs text-gray-400">No items found</p>
          </div>
          <?php endif; ?>

          <!-- Summary -->
          <div class="mt-5 pt-5 border-t border-gray-100 dark:border-white/5 space-y-2.5">
            <div class="flex justify-between text-xs"><span class="text-gray-400">Subtotal</span><span class="font-semibold text-gray-700 dark:text-white/80">Rs. <?= number_format($subtotal, 0) ?></span></div>
            <?php if ($shipping_charge > 0): ?>
            <div class="flex justify-between text-xs"><span class="text-gray-400">Shipping</span><span class="font-semibold text-gray-700 dark:text-white/80">Rs. <?= number_format($shipping_charge, 0) ?></span></div>
            <?php else: ?>
            <div class="flex justify-between text-xs"><span class="text-gray-400">Shipping</span><span class="font-semibold text-brand-500">FREE</span></div>
            <?php endif; ?>
            <?php if ($discount > 0): ?>
            <div class="flex justify-between text-xs"><span class="text-gray-400">Discount</span><span class="font-semibold text-red-500">- Rs. <?= number_format($discount, 0) ?></span></div>
            <?php endif; ?>
            <div class="flex justify-between items-center pt-3 border-t border-gray-100 dark:border-white/5">
              <span class="text-sm font-bold text-gray-900 dark:text-white">Grand Total</span>
              <span class="text-lg font-extrabold text-brand-600 dark:text-brand-400">Rs. <?= number_format($grand_total, 0) ?></span>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <?php if (!empty($order['notes'])): ?>
        <div class="fade-up info-card" style="animation-delay:.2s">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-orange-50 dark:bg-orange-950 flex items-center justify-center"><i class="fa-solid fa-note-sticky text-orange-500 text-[10px]"></i></div>
            Customer Notes
          </h3>
          <p class="text-xs text-gray-600 dark:text-white/60 leading-relaxed bg-orange-50/50 dark:bg-orange-500/5 rounded-xl p-4"><?= htmlspecialchars($order['notes']) ?></p>
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Action Buttons -->
    <div class="fade-up flex items-center gap-3 pt-2" style="animation-delay:.3s">
      <a href="view.php" class="px-5 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-xs font-semibold transition flex items-center gap-2">
        <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Orders
      </a>
      <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
<button onclick="openStatusModal()" class="group flex items-center gap-2.5 text-xs font-semibold text-gray-600 dark:text-white/60 hover:text-brand-600 dark:hover:text-brand-400 transition-colors duration-300">
  <span class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-white/5 group-hover:bg-brand-50 dark:group-hover:bg-brand-500/10 flex items-center justify-center transition-all duration-300">
    <i class="fa-solid fa-pen-to-square text-[11px] text-gray-400 group-hover:text-brand-500 transition-colors duration-300"></i>
  </span>
  Update Status
</button>
      <button onclick="confirmCancel()" class="px-5 py-2.5 bg-red-50 dark:bg-red-500/5 hover:bg-red-100 dark:hover:bg-red-500/10 text-red-500 rounded-xl text-xs font-semibold transition flex items-center gap-2">
        <i class="fa-solid fa-ban text-[10px]"></i> Cancel Order
      </button>
      <?php endif; ?>
    </div>

  </div>
</main>

<!-- ========== STATUS UPDATE MODAL ========== -->
<div id="statusModal" class="hidden">
  <div class="modal-overlay" onclick="closeStatusModal()">
    <div class="modal-box bg-white dark:bg-[#131a16] rounded-2xl shadow-2xl border border-gray-100 dark:border-white/5 w-full max-w-md mx-4" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-white/5">
        <h3 class="text-base font-bold text-gray-900 dark:text-white">Update Order Status</h3>
        <button onclick="closeStatusModal()" class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-white/5 flex items-center justify-center transition text-gray-400"><i class="fa-solid fa-xmark text-sm"></i></button>
      </div>
      <form method="POST" class="p-6 space-y-4">
        <input type="hidden" name="update_status" value="1">
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 mb-2">Current Status</label>
          <span class="order-status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 mb-2">New Status</label>
          <select name="new_status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-sm text-gray-900 dark:text-white outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 transition" required>
            <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="button" onclick="closeStatusModal()" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-xs font-semibold transition">Cancel</button>
          <button type="submit" class="flex-1 px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-xs font-semibold transition">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========== CANCEL CONFIRM MODAL ========== -->
<div id="cancelModal" class="hidden">
  <div class="modal-overlay" onclick="closeCancelModal()">
    <div class="modal-box bg-white dark:bg-[#131a16] rounded-2xl shadow-2xl border border-gray-100 dark:border-white/5 w-full max-w-sm mx-4 text-center" onclick="event.stopPropagation()">
      <div class="p-6">
        <div class="w-14 h-14 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center mx-auto mb-4">
          <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl"></i>
        </div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">Cancel Order?</h3>
        <p class="text-xs text-gray-400 leading-relaxed mb-6">Order #<?= htmlspecialchars($order['order_id']) ?> ko cancel karna chahte hain?</p>
        <div class="flex gap-3">
          <button onclick="closeCancelModal()" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-xs font-semibold transition">Nahi</button>
          <form method="POST" class="flex-1">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="new_status" value="cancelled">
            <button type="submit" class="w-full px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-semibold transition">Haan, Cancel</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>'}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
function openStatusModal(){document.getElementById('statusModal').classList.remove('hidden');document.body.style.overflow='hidden'}
function closeStatusModal(){document.getElementById('statusModal').classList.add('hidden');document.body.style.overflow=''}
function confirmCancel(){document.getElementById('cancelModal').classList.remove('hidden');document.body.style.overflow='hidden'}
function closeCancelModal(){document.getElementById('cancelModal').classList.add('hidden');document.body.style.overflow=''}
document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeStatusModal();closeCancelModal()}});
</script>

</body>
</html>