<?php
session_start();
// Note: For the preview to work purely as HTML, you might need to comment out DB connections if no DB exists.
// But keeping your logic intact for production use.
include("config/db.php"); 

// Mock Data for Preview if DB connection fails or session is missing (Optional for UI testing)
 $mock_data = false;
if (!isset($_SESSION['user_id'])) {
    // $mock_data = true; // Uncomment to test UI without login
    // header("Location: ../user/login.php");
    // exit;
}

 $uid = $_SESSION['user_id'] ?? 1; // Default for testing if mocked
 if (!$mock_data) {
    $user_res = mysqli_query($conn, "SELECT name, email, image, role FROM users WHERE id = $uid");
    $user = mysqli_fetch_assoc($user_res);
 } else {
    $user = ['name' => 'Admin User', 'email' => 'admin@example.com', 'image' => '', 'role' => 'admin'];
 }
 
 $user_name = $user['name'] ?? 'Unknown';
 $user_email = $user['email'] ?? '';
 $user_image = !empty($user['image']) ? 'uploads/' . $user['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true';
 $user_role = ucfirst($user['role'] ?? 'user');

 if (!$mock_data) {
    $total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
    $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) as s FROM orders WHERE status='delivered'"))['s'];
    $total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
    $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='user'"))['c'];
 } else {
    $total_orders = 128; $total_revenue = 45000; $total_products = 45; $total_users = 120;
 }

 $statuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];
 $status_counts = [];
 foreach ($statuses as $s) {
    if(!$mock_data){
        $status_counts[$s] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='$s'"))['c'];
    } else {
        $status_counts[$s] = rand(5, 50);
    }
 }
 $max_status = max(array_values($status_counts)) ?: 1;

 if (!$mock_data) {
    $recent_orders = mysqli_query($conn, "SELECT o.*, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 6");
    $pending_payments = mysqli_query($conn, "SELECT p.*, u.name AS user_name FROM payments p LEFT JOIN users u ON u.id = p.user_id WHERE p.status='pending' ORDER BY p.created_at DESC LIMIT 5");
 }

 $monthly = [];
for ($i = 6; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    if(!$mock_data){
        $val = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) as s FROM orders WHERE status='delivered' AND DATE_FORMAT(created_at,'%Y-%m')='$m'"))['s'];
    } else {
        $val = rand(10000, 50000);
    }
    $monthly[] = ['label' => $label, 'value' => (float)$val];
}
 $max_monthly = max(array_column($monthly, 'value')) ?: 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Glass Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Plus Jakarta Sans','sans-serif']},colors:{brand:{50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442',800:'#095c37',900:'#084b2e',950:'#032a1a'}}}}}</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Plus Jakarta Sans',sans-serif}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.2);border-radius:99px}

/* --- GLASSMORPHISM DESIGN SYSTEM --- */

/* The Ambient Background */
body {
    background-color: #f0f2f5;
    overflow-x: hidden;
    transition: background-color 0.5s ease;
}

.dark body {
    background-color: #050b08;
}

/* Ambient Blobs (The colored lights behind the glass) */
.blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    z-index: -1;
    opacity: 0.6;
    animation: float 10s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
}
.blob-1 { top: -10%; left: -10%; width: 50vw; height: 50vw; background: #aaf0c6; animation-delay: 0s; }
.blob-2 { bottom: -10%; right: -10%; width: 60vw; height: 60vw; background: #60a5fa; animation-delay: -2s; }
.blob-3 { top: 40%; left: 40%; width: 40vw; height: 40vw; background: #c084fc; animation-delay: -4s; opacity: 0.4; }

.dark .blob-1 { background: #064e3b; }
.dark .blob-2 { background: #1e3a8a; }
.dark .blob-3 { background: #581c87; }

@keyframes float {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(20px, 40px) scale(1.1); }
}

/* Glass Base Utility */
.glass-panel {
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
}

.dark .glass-panel {
    background: rgba(13, 20, 16, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
}

/* Sidebar Specific Glass */
.sidebar-glass {
    background: rgba(8, 75, 46, 0.85);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}
.dark .sidebar-glass {
    background: rgba(3, 42, 26, 0.85);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
}

/* Topbar Glass */
.topbar-glass {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
}
.dark .topbar-glass {
    background: rgba(10, 20, 15, 0.7);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

/* ------------------------------- */

.nav-link{position:relative;transition:all .25s cubic-bezier(.4,0,.2,1)}
.nav-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;border-radius:0 4px 4px 0;background:#cbcd3a;transition:height .25s cubic-bezier(.4,0,.2,1)}
.nav-link:hover::before,.nav-link.active::before{height:60%}
.nav-link.active{background:rgba(255,255,255,0.1);color:#cbcd3a}.nav-link:hover{background:rgba(255,255,255,0.05)}

.role-badge{font-size:9px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:2px 7px;border-radius:6px}
.role-admin{background:rgba(58,205,126,0.2);color:#cbcd3a;backdrop-filter: blur(4px);}.role-user{background:rgba(96,165,250,0.2);color:#60a5fa;backdrop-filter: blur(4px);}

.toast{position:fixed;top:20px;right:20px;z-index:9999;padding:14px 20px;border-radius:16px;font-size:13px;font-weight:600;backdrop-filter: blur(12px); box-shadow:0 10px 30px rgba(0,0,0,0.15);animation:toastIn .3s ease,toastOut .3s ease 2.7s forwards}
.toast-success{background:rgba(16,185,129,0.9);color:#fff}.toast-error{background:rgba(239,68,68,0.9);color:#fff}
@keyframes toastIn{from{opacity:0;transform:translateY(-20px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes toastOut{from{opacity:1;transform:translateY(0)}to{opacity:0;transform:translateY(-20px)}}
.fade-up{opacity:0;transform:translateY(20px);animation:fadeUp .6s cubic-bezier(.4,0,.2,1) forwards}
@keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
.dropdown-enter{animation:dropIn .2s cubic-bezier(.4,0,.2,1) forwards}
@keyframes dropIn{from{opacity:0;transform:translateY(-8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.bar-animate{animation:barGrow .8s cubic-bezier(.4,0,.2,1) forwards;transform-origin:bottom}
@keyframes barGrow{from{transform:scaleY(0)}to{transform:scaleY(1)}}
.status-bar{transition:width 1s cubic-bezier(.4,0,.2,1)}

/* Sidebar responsive */
#sidebar{transition:transform .3s cubic-bezier(.4,0,.2,1)}
#sidebarOverlay{position:fixed;inset:0;z-index:45;background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);opacity:0;pointer-events:none;transition:opacity .3s}
#sidebarOverlay.show{opacity:1;pointer-events:auto}

/* Desktop sidebar */
@media(min-width:1024px){
  #sidebar{transform:translateX(0)!important}
  #sidebarOverlay{display:none!important}
  #mainArea{margin-left:260px}
  #mainArea.sidebar-collapsed{margin-left:78px}
  #sidebar.sidebar-collapsed .sidebar-text{opacity:0;width:0;overflow:hidden}
  #sidebar.sidebar-collapsed .sidebar-logo-text{opacity:0;width:0;overflow:hidden}
  #sidebar.sidebar-collapsed .sidebar-avatar{width:36px!important;height:36px!important}
  #sidebar.sidebar-collapsed .sidebar-section-label{opacity:0;height:0;margin:0;padding:0;overflow:hidden}
}

/* Tablet & Mobile sidebar */
@media(max-width:1023px){
  #sidebar{transform:translateX(-100%);z-index:50;will-change:transform}
  #sidebar.sidebar-open{transform:translateX(0)}
  #mainArea{margin-left:0!important}
  .mobile-menu-btn{display:flex!important}
}
@media(min-width:1024px){
  .mobile-menu-btn{display:none!important}
}

/* Stat cards responsive grid */
@media(max-width:639px){
  .stat-grid{grid-template-columns:1fr 1fr!important;gap:12px!important}
  .stat-card{padding:16px!important}
  .stat-card .stat-val{font-size:1.25rem!important}
}
@media(max-width:374px){
  .stat-grid{grid-template-columns:1fr!important}
}

/* Chart responsive */
@media(max-width:639px){
  .chart-bars{height:140px!important;gap:6px!important}
  .chart-label{font-size:8px!important}
  .chart-val{font-size:8px!important}
}

/* Status section */
@media(max-width:639px){
  .status-text{font-size:10px!important}
}

/* Recent items responsive */
@media(max-width:639px){
  .recent-item{padding:12px 16px!important;gap:10px!important}
  .recent-item .item-mono{font-size:10px!important}
  .recent-item .item-name{font-size:10px!important}
  .recent-item .item-amount{font-size:11px!important}
  .recent-item .item-status{font-size:8px!important;padding:3px 6px!important}
  .pay-item{padding:12px 16px!important;gap:10px!important}
  .pay-item .pay-name{font-size:10px!important}
  .pay-item .pay-amount{font-size:11px!important}
}

/* Topbar responsive */
@media(max-width:639px){
  .topbar-title{font-size:1rem!important}
  .topbar-right .user-name-text{display:none!important}
}
</style>
</head>

<body class="min-h-screen text-gray-800 dark:text-white transition-colors duration-500">

<!-- Ambient Background Blobs -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<?php if (isset($_SESSION['toast'])): ?>
  <div class="toast toast-<?= $_SESSION['toast']['type'] ?>"><i class="fa-solid fa-<?= $_SESSION['toast']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i><?= htmlspecialchars($_SESSION['toast']['message']) ?></div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<!-- Mobile Overlay -->
<div id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 flex flex-col shadow-2xl">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shadow-lg shadow-brand-500/20 shrink-0">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="toggleSidebarCollapse()" class="hidden lg:flex w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 items-center justify-center transition text-sm backdrop-blur-md"><i class="fa-solid fa-bars text-xs"></i></button>
      <button onclick="closeMobileSidebar()" class="lg:hidden w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm backdrop-blur-md"><i class="fa-solid fa-xmark text-xs"></i></button>
    </div>
  </div>
  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="<?= $user_image ?>" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0 shadow-lg" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
    <div class="sidebar-text transition-all duration-300">
      <div class="flex items-center gap-2"><p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p><span class="role-badge <?= $user_role === 'Admin' ? 'role-admin' : 'role-user' ?>"><?= $user_role ?></span></div>
      <p class="text-[11px] text-white/60 mt-0.5"><?= htmlspecialchars($user_email) ?></p>
    </div>
  </div>
  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto pb-4">
    <p class="sidebar-text sidebar-section-label text-[10px] uppercase tracking-widest text-white/40 font-semibold px-3 mb-2 mt-4 transition-all duration-300">Main</p>
    <a href="dashboard.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <?php if ($user_role === 'Admin' || $user_role === '1') { ?>
    <p class="sidebar-text sidebar-section-label text-[10px] uppercase tracking-widest text-white/40 font-semibold px-3 mb-2 mt-5 transition-all duration-300">Manage</p>
    <a href="category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text sidebar-section-label text-[10px] uppercase tracking-widest text-white/40 font-semibold px-3 mb-2 mt-5 transition-all duration-300">Sales</p>
    <?php
      $pending_count = isset($pending_count) ? $pending_count : mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='pending'"))['c'];
      $pending_pay = isset($pending_pay) ? $pending_pay : mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE status='pending'"))['c'];
    ?>
    <a href="view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span>
      <?php if ($pending_count > 0): ?><span class="ml-auto bg-amber-500/90 text-white text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300 shadow-lg shadow-amber-500/30"><?= $pending_count ?></span><?php endif; ?>
    </a>
    <a href="payments.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span>
      <?php if ($pending_pay > 0): ?><span class="ml-auto bg-amber-500/90 text-white text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300 shadow-lg shadow-amber-500/30"><?= $pending_pay ?></span><?php endif; ?>
    </a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5">
      <div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300 border border-white/5 backdrop-blur-sm">
        <p class="text-[11px] text-white/40 mb-1">Storage</p>
        <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full shadow-[0_0_10px_rgba(58,205,126,0.5)]"></div></div>
        <p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p>
      </div>
  </div>
</aside>

<!-- MAIN -->
<main id="mainArea" class="min-h-screen transition-all duration-300 relative z-10">
  <header class="sticky top-0 z-40 topbar-glass px-4 sm:px-6 lg:px-8 py-3 lg:py-4 flex justify-between items-center gap-3">
    <div class="flex items-center gap-3">
      <button onclick="openMobileSidebar()" class="mobile-menu-btn w-10 h-10 rounded-xl bg-white/50 dark:bg-white/5 hover:bg-white dark:hover:bg-white/10 items-center justify-center transition text-gray-600 dark:text-white/70 backdrop-blur-md border border-white/20">
        <i class="fa-solid fa-bars text-sm"></i>
      </button>
      <div class="min-w-0">
        <h1 class="topbar-title text-lg lg:text-xl font-bold text-gray-900 dark:text-white tracking-tight truncate">Dashboard</h1>
        <p class="text-[11px] lg:text-xs text-gray-500 dark:text-gray-400 mt-0.5 hidden sm:block"><?= date('l, d F Y') ?></p>
      </div>
    </div>
    <div class="topbar-right flex items-center gap-2 sm:gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-9 h-9 lg:w-10 lg:h-10 rounded-xl bg-white/50 dark:bg-white/5 hover:bg-white dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70 backdrop-blur-md border border-white/20">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-white/50 dark:hover:bg-white/5 transition border border-transparent hover:border-white/20">
          <img src="<?= $user_image ?>" class="w-7 h-7 lg:w-8 lg:h-8 rounded-lg object-cover shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
          <span class="user-name-text hidden sm:block text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></span>
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 glass-panel dark:glass-panel shadow-2xl rounded-2xl w-48 py-2 dropdown-enter overflow-hidden">
          <a href="profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/80 hover:bg-white/50 dark:hover:bg-white/10 transition"><i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile</a>
          <div class="border-t border-gray-200/50 dark:border-white/5 mt-1 pt-1"><a href="../user/logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-500/10 transition"><i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout</a></div>
        </div>
      </div>
    </div>
  </header>

  <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6">

    <!-- STAT CARDS -->
    <div class="stat-grid grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6">
      <div class="fade-up glass-panel rounded-2xl p-4 sm:p-5 hover:scale-[1.02] transition-transform duration-300" style="animation-delay:.05s">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
          <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white/50 dark:bg-white/5 flex items-center justify-center shadow-sm border border-white/40 dark:border-white/10"><i class="fa-solid fa-cart-shopping text-brand-500 text-sm sm:text-base"></i></div>
          <span class="text-[9px] sm:text-[10px] font-bold text-brand-600 dark:text-brand-400 bg-brand-50/80 dark:bg-brand-950/50 backdrop-blur-sm px-2 py-0.5 rounded-md border border-brand-100 dark:border-brand-500/20">Total</span>
        </div>
        <p class="stat-val text-xl sm:text-2xl font-extrabold text-gray-800 dark:text-white"><?= number_format($total_orders) ?></p>
        <p class="text-[10px] sm:text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Orders placed</p>
      </div>
      <div class="fade-up glass-panel rounded-2xl p-4 sm:p-5 hover:scale-[1.02] transition-transform duration-300" style="animation-delay:.1s">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
          <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white/50 dark:bg-white/5 flex items-center justify-center shadow-sm border border-white/40 dark:border-white/10"><i class="fa-solid fa-indian-rupee-sign text-emerald-500 text-sm sm:text-base"></i></div>
          <span class="text-[9px] sm:text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50/80 dark:bg-emerald-950/50 backdrop-blur-sm px-2 py-0.5 rounded-md border border-emerald-100 dark:border-emerald-500/20">Revenue</span>
        </div>
        <p class="stat-val text-xl sm:text-2xl font-extrabold text-gray-800 dark:text-white">Rs. <?= number_format($total_revenue, 0) ?></p>
        <p class="text-[10px] sm:text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Delivered orders</p>
      </div>
      <div class="fade-up glass-panel rounded-2xl p-4 sm:p-5 hover:scale-[1.02] transition-transform duration-300" style="animation-delay:.15s">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
          <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white/50 dark:bg-white/5 flex items-center justify-center shadow-sm border border-white/40 dark:border-white/10"><i class="fa-solid fa-box text-violet-500 text-sm sm:text-base"></i></div>
          <span class="text-[9px] sm:text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50/80 dark:bg-violet-950/50 backdrop-blur-sm px-2 py-0.5 rounded-md border border-violet-100 dark:border-violet-500/20">Products</span>
        </div>
        <p class="stat-val text-xl sm:text-2xl font-extrabold text-gray-800 dark:text-white"><?= number_format($total_products) ?></p>
        <p class="text-[10px] sm:text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Active listings</p>
      </div>
      <div class="fade-up glass-panel rounded-2xl p-4 sm:p-5 hover:scale-[1.02] transition-transform duration-300" style="animation-delay:.2s">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
          <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-white/50 dark:bg-white/5 flex items-center justify-center shadow-sm border border-white/40 dark:border-white/10"><i class="fa-solid fa-users text-sky-500 text-sm sm:text-base"></i></div>
          <span class="text-[9px] sm:text-[10px] font-bold text-sky-600 dark:text-sky-400 bg-sky-50/80 dark:bg-sky-950/50 backdrop-blur-sm px-2 py-0.5 rounded-md border border-sky-100 dark:border-sky-500/20">Users</span>
        </div>
        <p class="stat-val text-xl sm:text-2xl font-extrabold text-gray-800 dark:text-white"><?= number_format($total_users) ?></p>
        <p class="text-[10px] sm:text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Registered users</p>
      </div>
    </div>

    <!-- CHART + STATUS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5 sm:mb-6">

      <!-- Revenue Chart -->
      <div class="fade-up lg:col-span-2 glass-panel rounded-2xl p-4 sm:p-6" style="animation-delay:.25s">
        <div class="flex items-center justify-between mb-4 sm:mb-6">
          <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Monthly Revenue</h2>
            <p class="text-[10px] sm:text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Last 7 months</p>
          </div>
          <span class="text-[10px] sm:text-xs font-bold text-brand-600 dark:text-brand-400 bg-brand-50/80 dark:bg-brand-950/50 backdrop-blur-md px-2 sm:px-3 py-1 rounded-lg whitespace-nowrap border border-brand-100 dark:border-brand-500/20">Rs. <?= number_format($total_revenue, 0) ?></span>
        </div>
        <div class="chart-bars flex items-end gap-2 sm:gap-3 h-[140px] sm:h-[180px]">
          <?php foreach ($monthly as $i => $m): ?>
          <div class="flex-1 flex flex-col items-center gap-1 sm:gap-2">
            <span class="chart-val text-[9px] sm:text-[10px] font-bold text-gray-500 dark:text-white/60 truncate w-full text-center"><?= $m['value'] > 0 ? number_format($m['value']/1000, 1).'K' : '0' ?></span>
            <div class="w-full rounded-t-lg bg-gradient-to-t from-brand-500 to-brand-300 bar-animate shadow-lg shadow-brand-500/20" style="height:<?= max(4, ($m['value'] / $max_monthly) * 130) ?>px;animation-delay:<?= $i * 0.1 ?>s"></div>
            <span class="chart-label text-[8px] sm:text-[10px] text-gray-400 font-medium truncate"><?= explode(' ', $m['label'])[0] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Order Status -->
      <div class="fade-up glass-panel rounded-2xl p-4 sm:p-6" style="animation-delay:.3s">
        <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-4 sm:mb-5">Order Status</h2>
        <div class="space-y-3 sm:space-y-3.5">
          <?php
          $scolors = ['pending'=>'bg-amber-500 shadow-amber-500/30','confirmed'=>'bg-blue-500 shadow-blue-500/30','processing'=>'bg-violet-500 shadow-violet-500/30','shipped'=>'bg-indigo-500 shadow-indigo-500/30','delivered'=>'bg-emerald-500 shadow-emerald-500/30','cancelled'=>'bg-red-500 shadow-red-500/30'];
          $dcolors = ['pending'=>'text-amber-600 dark:text-amber-400','confirmed'=>'text-blue-600 dark:text-blue-400','processing'=>'text-violet-600 dark:text-violet-400','shipped'=>'text-indigo-600 dark:text-indigo-400','delivered'=>'text-emerald-600 dark:text-emerald-400','cancelled'=>'text-red-600 dark:text-red-400'];
          foreach ($statuses as $s):
            $pct = $total_orders > 0 ? round(($status_counts[$s] / $total_orders) * 100) : 0;
          ?>
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="status-text text-[11px] font-semibold text-gray-600 dark:text-white/70"><?= ucfirst($s) ?></span>
              <span class="text-[11px] font-bold <?= $dcolors[$s] ?>"><?= $status_counts[$s] ?><?= $pct > 0 ? ' ('.$pct.'%)' : '' ?></span>
            </div>
            <div class="w-full h-2 bg-gray-200/50 dark:bg-white/5 rounded-full overflow-hidden border border-gray-200/50 dark:border-white/5">
              <div class="h-full <?= $scolors[$s] ?> rounded-full status-bar shadow-sm" style="width:0%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- RECENT ORDERS + PENDING PAYMENTS -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

      <!-- Recent Orders -->
      <div class="fade-up lg:col-span-3 glass-panel rounded-2xl overflow-hidden" style="animation-delay:.35s">
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200/50 dark:border-white/5">
          <h2 class="text-sm font-bold text-gray-900 dark:text-white">Recent Orders</h2>
          <a href="view.php" class="text-[11px] font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-500 transition whitespace-nowrap">View All <i class="fa-solid fa-arrow-right text-[9px] ml-1"></i></a>
        </div>
        <div class="divide-y divide-gray-200/50 dark:divide-white/[0.05]">
          <?php if (isset($recent_orders) && mysqli_num_rows($recent_orders) > 0): ?>
            <?php while ($ro = mysqli_fetch_assoc($recent_orders)):
              $soc = ['pending'=>'bg-amber-100/80 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800','confirmed'=>'bg-blue-100/80 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800','processing'=>'bg-violet-100/80 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 border border-violet-200 dark:border-violet-800','shipped'=>'bg-indigo-100/80 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800','delivered'=>'bg-emerald-100/80 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800','cancelled'=>'bg-red-100/80 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800'];
            ?>
            <a href="detail.php?id=<?= $ro['id'] ?>" class="recent-item flex items-center gap-3 sm:gap-4 px-4 sm:px-6 py-3 sm:py-3.5 hover:bg-white/40 dark:hover:bg-white/[0.03] transition">
              <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gray-100/50 dark:bg-white/5 flex items-center justify-center shrink-0 border border-white/20">
                <i class="fa-solid fa-bag-shopping text-gray-400 text-xs sm:text-sm"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="item-mono text-[10px] sm:text-xs font-bold text-gray-900 dark:text-white font-mono">#<?= htmlspecialchars($ro['order_id']) ?></p>
                <p class="item-name text-[10px] sm:text-[11px] text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($ro['shipping_name']) ?></p>
              </div>
              <p class="item-amount text-[11px] sm:text-xs font-bold text-gray-900 dark:text-white shrink-0 hidden sm:block">Rs. <?= number_format($ro['total_amount'], 0) ?></p>
              <span class="item-status text-[8px] sm:text-[9px] font-bold uppercase tracking-wider px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-lg shrink-0 backdrop-blur-md <?= $soc[$ro['status']] ?? 'bg-gray-100/50 text-gray-600 border border-gray-200' ?>"><?= ucfirst($ro['status']) ?></span>
            </a>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-8 sm:py-10"><i class="fa-solid fa-inbox text-2xl text-gray-300 dark:text-gray-700 mb-2 block"></i><p class="text-xs text-gray-400">No orders yet</p></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Pending Payments -->
      <div class="fade-up lg:col-span-2 glass-panel rounded-2xl overflow-hidden" style="animation-delay:.4s">
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200/50 dark:border-white/5">
          <h2 class="text-sm font-bold text-gray-900 dark:text-white">Pending Payments</h2>
          <a href="payments.php?status=pending" class="text-[11px] font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-500 transition whitespace-nowrap">View All <i class="fa-solid fa-arrow-right text-[9px] ml-1"></i></a>
        </div>
        <div class="divide-y divide-gray-200/50 dark:divide-white/[0.05]">
          <?php if (isset($pending_payments) && mysqli_num_rows($pending_payments) > 0): ?>
            <?php while ($pp = mysqli_fetch_assoc($pending_payments)): ?>
            <div class="pay-item flex items-center gap-3 px-4 sm:px-6 py-3 sm:py-3.5">
              <?php if (!empty($pp['proof_image'])): ?>
                <img src="uploads/<?= htmlspecialchars($pp['proof_image']) ?>" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg object-cover shrink-0 border border-gray-200/50 dark:border-white/10 shadow-sm" onerror="this.outerHTML='<div class=\'w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-gray-100/50 dark:bg-white/5 flex items-center justify-center border border-white/10\'><i class=\'fa-solid fa-image text-gray-300 text-[10px]\'></i></div>'">
              <?php else: ?>
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-gray-100/50 dark:bg-white/5 flex items-center justify-center shrink-0 border border-white/10"><i class="fa-solid fa-image text-gray-300 text-[10px]"></i></div>
              <?php endif; ?>
              <div class="flex-1 min-w-0">
                <p class="pay-name text-[10px] sm:text-xs font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($pp['sender_name'] ?? $pp['user_name']) ?></p>
                <p class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400"><?= htmlspecialchars(ucfirst($pp['method'] ?? 'N/A')) ?></p>
              </div>
              <p class="pay-amount text-[11px] sm:text-xs font-bold text-gray-900 dark:text-white shrink-0">Rs. <?= number_format($pp['amount'], 0) ?></p>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-center py-8 sm:py-10"><i class="fa-solid fa-check-circle text-2xl text-emerald-200 dark:text-emerald-900 mb-2 block"></i><p class="text-xs text-gray-400">All clear!</p></div>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </div>
</main>

<script>
function openMobileSidebar(){document.getElementById('sidebar').classList.add('sidebar-open');document.getElementById('sidebarOverlay').classList.add('show');document.body.style.overflow='hidden'}
function closeMobileSidebar(){document.getElementById('sidebar').classList.remove('sidebar-open');document.getElementById('sidebarOverlay').classList.remove('show');document.body.style.overflow=''}
function toggleSidebarCollapse(){document.getElementById('sidebar').classList.toggle('sidebar-collapsed');const s=document.getElementById('sidebar'),m=document.getElementById('mainArea'),c=s.classList.contains('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-solid fa-moon text-sm"></i>';localStorage.setItem('darkMode',d?'1':'0')}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});

/* Status bar animation on load */
window.addEventListener('load',function(){
  setTimeout(function(){
    document.querySelectorAll('.status-bar').forEach(function(b){
      var w=b.getAttribute('data-width');
      if(!w){w=b.style.width;b.setAttribute('data-width',w);b.style.width='0%'}
      setTimeout(function(){b.style.width=w},50);
    });
  },200);
});

/* Restore dark mode */
(function(){var s=localStorage.getItem('darkMode'),p=window.matchMedia('(prefers-color-scheme:dark)').matches;if(s==='1'||(!s&&p)){document.body.classList.add('dark');document.documentElement.classList.add('dark');document.getElementById('darkBtn').innerHTML='<i class="fa-solid fa-sun text-sm"></i>'}})();

/* Close sidebar on mobile when clicking nav link */
document.querySelectorAll('#sidebar .nav-link').forEach(function(link){
  link.addEventListener('click',function(){
    if(window.innerWidth<1024){closeMobileSidebar()}
  });
});
</script>
</body>
</html>