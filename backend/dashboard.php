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

/* ===== STATS ===== */
 $total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
 $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) as s FROM orders WHERE status='delivered'"))['s'];
 $total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
 $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='user'"))['c'];

/* ===== ORDER STATUS BREAKDOWN ===== */
 $statuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];
 $status_counts = [];
foreach ($statuses as $s) {
    $status_counts[$s] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='$s'"))['c'];
}
 $max_status = max(array_values($status_counts)) ?: 1;

/* ===== RECENT ORDERS ===== */
 $recent_orders = mysqli_query($conn, "SELECT o.*, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 6");

/* ===== RECENT PAYMENTS (PENDING) ===== */
 $pending_payments = mysqli_query($conn, "SELECT p.*, u.name AS user_name FROM payments p LEFT JOIN users u ON u.id = p.user_id WHERE p.status='pending' ORDER BY p.created_at DESC LIMIT 5");

/* ===== MONTHLY REVENUE (LAST 7 MONTHS) ===== */
 $monthly = [];
for ($i = 6; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $val = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) as s FROM orders WHERE status='delivered' AND DATE_FORMAT(created_at,'%Y-%m')='$m'"))['s'];
    $monthly[] = ['label' => $label, 'value' => (float)$val];
}
 $max_monthly = max(array_column($monthly, 'value')) ?: 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }, colors: { brand: { 50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442',800:'#095c37',900:'#084b2e',950:'#032a1a' }}}}}
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
  .dropdown-enter{animation:dropIn .2s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes dropIn{from{opacity:0;transform:translateY(-8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
  .bar-animate{animation:barGrow .8s cubic-bezier(.4,0,.2,1) forwards;transform-origin:bottom}
  @keyframes barGrow{from{transform:scaleY(0)}to{transform:scaleY(1)}}
  .status-bar{transition:width 1s cubic-bezier(.4,0,.2,1)}
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
    <a href="dashboard.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <?php if ($user_role === 'Admin') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <?php
      $pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='pending'"))['c'];
      $pending_pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE status='pending'"))['c'];
    ?>
    <a href="order-detail.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span>
      <?php if ($pending_count > 0): ?><span class="ml-auto bg-amber-500 text-brand-950 text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300"><?= $pending_count ?></span><?php endif; ?>
    </a>
    <a href="payments.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span>
      <?php if ($pending_pay > 0): ?><span class="ml-auto bg-amber-500 text-brand-950 text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300"><?= $pending_pay ?></span><?php endif; ?>
    </a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- ========== MAIN ========== -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">

  <!-- TOPBAR -->
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Dashboard</h1>
      <p class="text-xs text-gray-400 mt-0.5"><?= date('l, d F Y') ?></p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70"><i class="fa-solid fa-moon text-sm"></i></button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
          <span class="hidden sm:block text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></span>
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl rounded-2xl w-48 py-2 dropdown-enter">
          <a href="profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile</a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1"><a href="../user/logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition"><i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout</a></div>
        </div>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 hover:shadow-md transition-shadow" style="animation-delay:.05s">
        <div class="flex items-center justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center"><i class="fa-solid fa-cart-shopping text-brand-500 text-base"></i></div>
          <span class="text-[10px] font-bold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-950 px-2 py-0.5 rounded-md">Total</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= number_format($total_orders) ?></p>
        <p class="text-[11px] text-gray-400 mt-0.5">Orders placed</p>
      </div>
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 hover:shadow-md transition-shadow" style="animation-delay:.1s">
        <div class="flex items-center justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center"><i class="fa-solid fa-pakistan-rupee-sign text-emerald-500 text-base"></i></div>
          <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950 px-2 py-0.5 rounded-md">Revenue</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white">Rs. <?= number_format($total_revenue, 0) ?></p>
        <p class="text-[11px] text-gray-400 mt-0.5">Delivered orders</p>
      </div>
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 hover:shadow-md transition-shadow" style="animation-delay:.15s">
        <div class="flex items-center justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-violet-50 dark:bg-violet-950 flex items-center justify-center"><i class="fa-solid fa-box text-violet-500 text-base"></i></div>
          <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950 px-2 py-0.5 rounded-md">Products</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= number_format($total_products) ?></p>
        <p class="text-[11px] text-gray-400 mt-0.5">Active listings</p>
      </div>
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 hover:shadow-md transition-shadow" style="animation-delay:.2s">
        <div class="flex items-center justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-950 flex items-center justify-center"><i class="fa-solid fa-users text-sky-500 text-base"></i></div>
          <span class="text-[10px] font-bold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950 px-2 py-0.5 rounded-md">Users</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= number_format($total_users) ?></p>
        <p class="text-[11px] text-gray-400 mt-0.5">Registered users</p>
      </div>
    </div>

    <!-- CHART + STATUS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

      <!-- Revenue Chart -->
      <div class="fade-up lg:col-span-2 bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-6" style="animation-delay:.25s">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Monthly Revenue</h2>
            <p class="text-[11px] text-gray-400 mt-0.5">Last 7 months (delivered orders)</p>
          </div>
          <span class="text-xs font-bold text-brand-500 bg-brand-50 dark:bg-brand-950 px-3 py-1 rounded-lg">Rs. <?= number_format($total_revenue, 0) ?></span>
        </div>
        <div class="flex items-end gap-3 h-[180px]">
          <?php foreach ($monthly as $i => $m): ?>
          <div class="flex-1 flex flex-col items-center gap-2">
            <span class="text-[10px] font-bold text-gray-500 dark:text-white/50">Rs. <?= $m['value'] > 0 ? number_format($m['value']/1000, 1).'K' : '0' ?></span>
            <div class="w-full rounded-t-lg bg-gradient-to-t from-brand-500 to-brand-300 bar-animate" style="height:<?= max(4, ($m['value'] / $max_monthly) * 140) ?>px;animation-delay:<?= $i * 0.1 ?>s"></div>
            <span class="text-[10px] text-gray-400 font-medium"><?= explode(' ', $m['label'])[0] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Order Status -->
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-6" style="animation-delay:.3s">
        <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-5">Order Status</h2>
        <div class="space-y-3.5">
          <?php
          $scolors = ['pending'=>'bg-amber-500','confirmed'=>'bg-blue-500','processing'=>'bg-violet-500','shipped'=>'bg-indigo-500','delivered'=>'bg-emerald-500','cancelled'=>'bg-red-500'];
          $dcolors = ['pending'=>'text-amber-600 dark:text-amber-400','confirmed'=>'text-blue-600 dark:text-blue-400','processing'=>'text-violet-600 dark:text-violet-400','shipped'=>'text-indigo-600 dark:text-indigo-400','delivered'=>'text-emerald-600 dark:text-emerald-400','cancelled'=>'text-red-600 dark:text-red-400'];
          foreach ($statuses as $s):
            $pct = $total_orders > 0 ? round(($status_counts[$s] / $total_orders) * 100) : 0;
          ?>
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-[11px] font-semibold text-gray-600 dark:text-white/70"><?= ucfirst($s) ?></span>
              <span class="text-[11px] font-bold <?= $dcolors[$s] ?>"><?= $status_counts[$s] ?> (<?= $pct ?>%)</span>
            </div>
            <div class="w-full h-2 bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
              <div class="h-full <?= $scolors[$s] ?> rounded-full status-bar" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- RECENT ORDERS + PENDING PAYMENTS -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

      <!-- Recent Orders -->
      <div class="fade-up lg:col-span-3 bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden" style="animation-delay:.35s">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/5">
          <h2 class="text-sm font-bold text-gray-900 dark:text-white">Recent Orders</h2>
          <a href="view.php" class="text-[11px] font-semibold text-brand-500 hover:text-brand-600 transition">View All <i class="fa-solid fa-arrow-right text-[9px] ml-1"></i></a>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-white/[0.03]">
          <?php if (mysqli_num_rows($recent_orders) > 0): ?>
            <?php while ($ro = mysqli_fetch_assoc($recent_orders)):
              $soc = ['pending'=>'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400','confirmed'=>'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400','processing'=>'bg-violet-100 text-violet-700 dark:bg-violet-900/20 dark:text-violet-400','shipped'=>'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400','delivered'=>'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400','cancelled'=>'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'];
            ?>
            <a href="detail.php?id=<?= $ro['id'] ?>" class="flex items-center gap-4 px-6 py-3.5 hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
              <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bag-shopping text-gray-400 text-sm"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-gray-900 dark:text-white font-mono">#<?= htmlspecialchars($ro['order_id']) ?></p>
                <p class="text-[10px] text-gray-400 truncate"><?= htmlspecialchars($ro['shipping_name']) ?></p>
              </div>
              <p class="text-xs font-bold text-gray-900 dark:text-white shrink-0">Rs. <?= number_format($ro['total_amount'], 0) ?></p>
              <span class="text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg shrink-0 <?= $soc[$ro['status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($ro['status']) ?></span>
            </a>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-10"><p class="text-xs text-gray-400">No orders yet</p></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Pending Payments -->
      <div class="fade-up lg:col-span-2 bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden" style="animation-delay:.4s">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/5">
          <h2 class="text-sm font-bold text-gray-900 dark:text-white">Pending Payments</h2>
          <a href="payments.php?status=pending" class="text-[11px] font-semibold text-brand-500 hover:text-brand-600 transition">View All <i class="fa-solid fa-arrow-right text-[9px] ml-1"></i></a>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-white/[0.03]">
          <?php if (mysqli_num_rows($pending_payments) > 0): ?>
            <?php while ($pp = mysqli_fetch_assoc($pending_payments)): ?>
            <div class="flex items-center gap-3 px-6 py-3.5">
              <?php if (!empty($pp['proof_image'])): ?>
                <img src="uploads/<?= htmlspecialchars($pp['proof_image']) ?>" class="w-9 h-9 rounded-lg object-cover shrink-0 border border-gray-100 dark:border-white/5">
              <?php else: ?>
                <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-white/5 flex items-center justify-center shrink-0"><i class="fa-solid fa-image text-gray-300 text-[10px]"></i></div>
              <?php endif; ?>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($pp['sender_name'] ?? $pp['user_name']) ?></p>
                <p class="text-[10px] text-gray-400"><?= htmlspecialchars(ucfirst($pp['method'] ?? 'N/A')) ?></p>
              </div>
              <p class="text-xs font-bold text-gray-900 dark:text-white shrink-0">Rs. <?= number_format($pp['amount'], 0) ?></p>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-10"><p class="text-xs text-gray-400">No pending payments</p></div>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </div>
</main>

<script>
function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>'}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
/* Animate status bars on load */
window.addEventListener('load',()=>{document.querySelectorAll('.status-bar').forEach(b=>{const w=b.style.width;b.style.width='0%';setTimeout(()=>b.style.width=w,100)})});
</script>

</body>
</html>