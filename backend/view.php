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

 $status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
 $page = max(1, (int)($_GET['page'] ?? 1));
 $limit = 15;
 $offset = ($page - 1) * $limit;

 $where = "1=1";
if ($status_filter) $where .= " AND o.status = '$status_filter'";
if ($search) $where .= " AND (o.order_id LIKE '%$search%' OR o.shipping_name LIKE '%$search%' OR o.shipping_phone LIKE '%$search%' OR u.email LIKE '%$search%')";

 $total_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE $where");
 $total_orders = mysqli_fetch_assoc($total_res)['c'];
 $total_pages = ceil($total_orders / $limit);

 $orders_res = mysqli_query($conn, "
    SELECT o.*, u.email AS user_email 
    FROM orders o 
    LEFT JOIN users u ON u.id = o.user_id 
    WHERE $where 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
");

 $counts = [
    'all'       => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'],
    'pending'   => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='pending'"))['c'],
    'confirmed' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='confirmed'"))['c'],
    'processing'=> mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='processing'"))['c'],
    'shipped'   => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='shipped'"))['c'],
    'delivered' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='delivered'"))['c'],
    'cancelled' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status='cancelled'"))['c'],
];

/* ===== Revenue Stats ===== */
 $rev_res = mysqli_query($conn, "SELECT 
    COALESCE(SUM(CASE WHEN status NOT IN ('cancelled') THEN total_amount ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN status='delivered' THEN total_amount ELSE 0 END), 0) as delivered_revenue,
    COALESCE(AVG(total_amount), 0) as avg_order
FROM orders");
 $rev = mysqli_fetch_assoc($rev_res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Orders</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Plus Jakarta Sans','sans-serif']},colors:{brand:{50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442',800:'#095c37',900:'#084b2e',950:'#032a1a'}}}}}</script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Plus Jakarta Sans',sans-serif}
  ::-webkit-scrollbar{width:6px;height:6px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.15);border-radius:99px}
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
  .stat-card{position:relative;overflow:hidden}
  .tbl-row{transition:background .15s ease}.tbl-row:hover{background:rgba(22,179,100,0.03)}.dark .tbl-row:hover{background:rgba(22,179,100,0.04)}
  .tbl-row td{border-bottom:1px solid #f3f4f6}.dark .tbl-row td{border-bottom-color:rgba(255,255,255,0.04)}
  .tbl-row:last-child td{border-bottom:none}
  .status-select{padding:6px 28px 6px 10px;border-radius:8px;font-size:11px;font-weight:600;border:1.5px solid #e5e7eb;outline:none;cursor:pointer;transition:all .2s;background:#f9fafb;color:#374151;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center}
  .dark .status-select{background-color:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);color:rgba(255,255,255,0.8);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E")}
  .status-select:focus{border-color:#16b364;box-shadow:0 0 0 3px rgba(22,179,100,0.08)}
  .filter-tab{padding:7px 16px;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s;white-space:nowrap;display:inline-flex;align-items:center;gap:6px}
  .dark .filter-tab{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6)}
  .filter-tab:hover{background:#f9fafb;border-color:#e5e7eb}.dark .filter-tab:hover{background:rgba(255,255,255,0.06)}
  .filter-tab.active{background:#16b364;color:#fff;border-color:#16b364;box-shadow:0 2px 8px rgba(22,179,100,0.25)}
  .filter-count{font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px}
  .filter-tab:not(.active) .filter-count{background:rgba(0,0,0,0.06);color:#9ca3af}
  .dark .filter-tab:not(.active) .filter-count{background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.3)}
  .filter-tab.active .filter-count{background:rgba(255,255,255,0.2);color:#fff}
  .page-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;text-decoration:none;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s}
  .dark .page-btn{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6)}
  .page-btn:hover{background:#f3f4f6}.page-btn.active{background:#16b364;color:#fff;border-color:#16b364}
  .dl-btn{position:relative;overflow:hidden}.dl-btn::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,transparent 40%,rgba(255,255,255,0.15) 50%,transparent 60%);transform:translateX(-100%);transition:transform .6s}.dl-btn:hover::after{transform:translateX(100%)}
  .action-btn{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;transition:all .15s ease;font-size:12px}
  .action-btn:hover{transform:translateY(-1px)}
  .order-id-link{font-size:12px;font-weight:700;font-family:'Plus Jakarta Sans',monospace;letter-spacing:.02em;color:#374151;transition:color .15s}
  .dark .order-id-link{color:rgba(255,255,255,0.9)}
  .order-id-link:hover{color:#16b364}
</style>
</head>
<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<?php if (isset($_SESSION['toast'])): ?>
  <div class="toast toast-<?= $_SESSION['toast']['type'] ?>"><i class="fa-solid fa-<?= $_SESSION['toast']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i><?= htmlspecialchars($_SESSION['toast']['message']) ?></div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3"><div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">A</div><span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span></div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm"><i class="fa-solid fa-bars text-xs"></i></button>
  </div>
  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="<?= $user_image ?>" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
    <div class="sidebar-text transition-all duration-300"><div class="flex items-center gap-2"><p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p><span class="role-badge <?= $user_role === 'Admin' ? 'role-admin' : 'role-user' ?>"><?= $user_role ?></span></div><p class="text-[11px] text-white/50 mt-0.5"><?= htmlspecialchars($user_email) ?></p></div>
  </div>
  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <?php if ($user_role === '1') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="view.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span>
      <?php if ($counts['pending'] > 0): ?><span class="ml-auto bg-amber-500 text-brand-950 text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300"><?= $counts['pending'] ?></span><?php endif; ?>
    </a>
    <a href="payments/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span></a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div><h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">All Orders</h1><p class="text-xs text-gray-400 mt-0.5">Track and manage customer orders</p></div>
    <div class="flex items-center gap-3">
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

  <div class="px-8 py-6">

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5" style="animation-delay:.05s">
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">Total Orders</p><p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1"><?= $total_orders ?></p></div><div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center"><i class="fa-solid fa-cart-shopping text-brand-500"></i></div></div>
        <div class="mt-3 flex items-center gap-3">
          <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/15 px-2 py-0.5 rounded-md"><i class="fa-solid fa-clock text-[8px] mr-1"></i><?= $counts['pending'] ?> pending</span>
          <span class="text-[10px] text-gray-400"><?= $counts['delivered'] ?> delivered</span>
        </div>
      </div>
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5" style="animation-delay:.1s">
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">Total Revenue</p><p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">Rs. <?= number_format($rev['total_revenue'], 0) ?></p></div><div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center"><i class="fa-solid fa-indian-rupee-sign text-emerald-500"></i></div></div>
        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-emerald-500"><i class="fa-solid fa-check-circle text-[9px]"></i>Excluding cancelled</div>
      </div>
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5" style="animation-delay:.15s">
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">Delivered Revenue</p><p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">Rs. <?= number_format($rev['delivered_revenue'], 0) ?></p></div><div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center"><i class="fa-solid fa-circle-check text-blue-500"></i></div></div>
        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-blue-500"><i class="fa-solid fa-truck text-[9px]"></i>Completed orders only</div>
      </div>
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5" style="animation-delay:.2s">
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">Avg. Order Value</p><p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">Rs. <?= number_format($rev['avg_order'], 0) ?></p></div><div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center"><i class="fa-solid fa-chart-simple text-purple-500"></i></div></div>
        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-purple-500"><i class="fa-solid fa-calculator text-[9px]"></i>Per order average</div>
      </div>
    </div>

    <!-- Filter Tabs + Download -->
    <div class="fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4" style="animation-delay:.25s">
      <div class="flex gap-2 overflow-x-auto pb-1 -mb-1">
        <a href="view.php" class="filter-tab <?= !$status_filter ? 'active' : '' ?>">All <span class="filter-count"><?= $counts['all'] ?></span></a>
        <a href="view.php?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">Pending <span class="filter-count"><?= $counts['pending'] ?></span></a>
        <a href="view.php?status=confirmed" class="filter-tab <?= $status_filter === 'confirmed' ? 'active' : '' ?>">Confirmed <span class="filter-count"><?= $counts['confirmed'] ?></span></a>
        <a href="view.php?status=processing" class="filter-tab <?= $status_filter === 'processing' ? 'active' : '' ?>">Processing <span class="filter-count"><?= $counts['processing'] ?></span></a>
        <a href="view.php?status=shipped" class="filter-tab <?= $status_filter === 'shipped' ? 'active' : '' ?>">Shipped <span class="filter-count"><?= $counts['shipped'] ?></span></a>
        <a href="view.php?status=delivered" class="filter-tab <?= $status_filter === 'delivered' ? 'active' : '' ?>">Delivered <span class="filter-count"><?= $counts['delivered'] ?></span></a>
        <a href="view.php?status=cancelled" class="filter-tab <?= $status_filter === 'cancelled' ? 'active' : '' ?>">Cancelled <span class="filter-count"><?= $counts['cancelled'] ?></span></a>
      </div>
      <button onclick="downloadOrdersCSV()" class="dl-btn shrink-0 flex items-center gap-2 px-5 py-2.5 bg-gray-900 dark:bg-white/10 hover:bg-gray-800 dark:hover:bg-white/15 text-white rounded-xl text-sm font-semibold transition shadow-sm">
        <i class="fa-solid fa-download text-xs"></i>Download CSV
      </button>
    </div>

    <!-- Search -->
    <form method="GET" class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 mb-4" style="animation-delay:.28s">
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-4">
        <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= $status_filter ?>"><?php endif; ?>
        <div class="flex-1 flex items-center bg-gray-50 dark:bg-white/[0.03] rounded-xl px-4 py-2.5 gap-2 border border-gray-100 dark:border-white/5">
          <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by order ID, name, phone, email..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400">
        </div>
        <div class="flex gap-2">
          <button type="submit" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition"><i class="fa-solid fa-magnifying-glass mr-2 text-xs"></i>Search</button>
          <?php if ($search || $status_filter): ?><a href="view.php" class="px-4 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/60 rounded-xl text-sm font-semibold transition">Clear</a><?php endif; ?>
        </div>
      </div>
    </form>

    <!-- Table -->
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden" style="animation-delay:.32s">

      <?php if (mysqli_num_rows($orders_res) > 0): ?>
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/5">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-bold text-gray-900 dark:text-white">Order List</h2>
          <span class="text-[10px] font-bold bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400 px-2.5 py-0.5 rounded-md"><?= $total_orders ?> results</span>
        </div>
        <div class="flex items-center gap-2 text-[11px] text-gray-400"><i class="fa-solid fa-arrow-down-wide-short text-[10px]"></i><span>Newest first</span></div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-50/80 dark:bg-white/[0.02]">
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider w-16">#</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Order ID</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Customer</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Shipping</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider text-right">Amount</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider text-center">Status</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Payment</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $counter = 0; while ($row = mysqli_fetch_assoc($orders_res)): $counter++;
              $status_colors = ['pending'=>'amber','confirmed'=>'blue','processing'=>'purple','shipped'=>'indigo','delivered'=>'emerald','cancelled'=>'red'];
              $sc = $status_colors[$row['status']] ?? 'gray';
            ?>
            <tr class="tbl-row">
              <td class="px-6 py-4"><span class="text-xs font-bold text-gray-300 dark:text-white/10"><?= str_pad($counter, 2, '0', STR_PAD_LEFT) ?></span></td>
              <td class="px-6 py-4">
                <a href="order-detail.php?id=<?= $row['id'] ?>" class="order-id-link">#<?= htmlspecialchars($row['order_id']) ?></a>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-white/[0.04] flex items-center justify-center shrink-0 border border-gray-100 dark:border-white/5">
                    <i class="fa-solid fa-user text-[10px] text-gray-400"></i>
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate max-w-[160px]"><?= htmlspecialchars($row['shipping_name']) ?></p>
                    <p class="text-[10px] text-gray-400 truncate max-w-[160px]"><?= htmlspecialchars($row['user_email'] ?? 'N/A') ?></p>
                  </div>
                </div>
                <p class="text-[10px] text-gray-400 mt-1 ml-[42px]"><i class="fa-solid fa-phone text-[8px] mr-1"></i><?= htmlspecialchars($row['shipping_phone'] ?? 'N/A') ?></p>
              </td>
              <td class="px-6 py-4">
                <p class="text-[11px] text-gray-600 dark:text-white/70 max-w-[180px] truncate" title="<?= htmlspecialchars($row['shipping_address'] ?? '') ?>"><?= htmlspecialchars($row['shipping_address'] ?? 'N/A') ?></p>
                <?php if (!empty($row['shipping_city'])): ?>
                  <p class="text-[10px] text-gray-400 font-semibold mt-0.5"><?= htmlspecialchars($row['shipping_city']) ?><?= !empty($row['shipping_zip']) ? ' — ' . htmlspecialchars($row['shipping_zip']) : '' ?></p>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 text-right">
                <p class="text-sm font-extrabold text-gray-900 dark:text-white">Rs. <?= number_format($row['total_amount'], 0) ?></p>
              </td>
              <td class="px-6 py-4 text-center">
                <form method="POST" action="update-order-status.php" class="inline">
                  <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                  <input type="hidden" name="redirect" value="view">
                  <input type="hidden" name="status_filter" value="<?= $status_filter ?>">
                  <input type="hidden" name="search" value="<?= $search ?>">
                  <select name="status" onchange="this.form.submit()" class="status-select">
                    <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1.5">
                  <i class="fa-solid fa-<?= ($row['payment_method'] ?? '') === 'cod' ? 'truck' : (($row['payment_method'] ?? '') === 'stripe' ? 'credit-card' : 'wallet') ?> text-[10px] text-gray-400"></i>
                  <span class="text-[11px] text-gray-500 font-medium"><?= ucfirst(str_replace('_', ' ', $row['payment_method'] ?? 'N/A')) ?></span>
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-xs text-gray-500 dark:text-white/40"><?= date('M d, Y', strtotime($row['created_at'])) ?></p>
                <p class="text-[10px] text-gray-400 dark:text-white/20"><?= date('h:i A', strtotime($row['created_at'])) ?></p>
              </td>
              <td class="px-6 py-4 text-center">
                <a href="order-detail.php?id=<?= $row['id'] ?>" class="action-btn bg-brand-50 dark:bg-brand-900/10 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/20" title="View Details"><i class="fa-solid fa-eye"></i></a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.01]">
        <p class="text-[11px] text-gray-400">Showing <span class="font-semibold text-gray-600 dark:text-white/50"><?= $offset + 1 ?>–<?= min($offset + $limit, $total_orders) ?></span> of <span class="font-semibold text-gray-600 dark:text-white/50"><?= $total_orders ?></span></p>
        <div class="flex gap-1.5">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-btn"><i class="fa-solid fa-chevron-left text-[10px]"></i></a>
          <?php endif; ?>
          <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="?page=<?= $i ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-btn"><i class="fa-solid fa-chevron-right text-[10px]"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.01]">
        <p class="text-[11px] text-gray-400">Showing <span class="font-semibold text-gray-600 dark:text-white/50"><?= $total_orders ?></span> orders</p>
        <div class="flex items-center gap-1 text-[11px] text-gray-400"><i class="fa-solid fa-circle-info text-[9px]"></i><span>Change status inline · Click eye for details</span></div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="text-center py-20">
        <div class="w-20 h-20 rounded-3xl bg-gray-100 dark:bg-white/[0.03] flex items-center justify-center mx-auto mb-5"><i class="fa-solid fa-inbox text-3xl text-gray-200 dark:text-gray-700"></i></div>
        <p class="text-base font-bold text-gray-900 dark:text-white mb-1">No orders found</p>
        <p class="text-sm text-gray-400 mb-5"><?= $search ? 'Try adjusting your search query' : ($status_filter ? 'No ' . ucfirst($status_filter) . ' orders yet' : 'Orders will appear here when customers place them') ?></p>
        <?php if ($search || $status_filter): ?>
          <a href="view.php" class="inline-block px-6 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/60 rounded-xl text-sm font-semibold transition">Clear Filters</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<script>
/* ===== ORDERS DATA (from PHP) ===== */
const ordersData = <?= json_encode(
    array_map(function($row) {
        return [
            $row['order_id'],
            $row['shipping_name'],
            $row['shipping_phone'] ?? '',
            $row['user_email'] ?? '',
            $row['shipping_address'] ?? '',
            $row['shipping_city'] ?? '',
            $row['shipping_zip'] ?? '',
            $row['total_amount'],
            $row['status'],
            str_replace('_', ' ', $row['payment_method'] ?? 'N/A'),
            date('M d, Y', strtotime($row['created_at'])),
            date('h:i A', strtotime($row['created_at']))
        ];
    }, iterator_to_array($orders_res, true))
) ?>;

function downloadOrdersCSV(){
  if(!ordersData.length){showToast('No orders to export','error');return}

  const headers = ['Order ID','Customer Name','Phone','Email','Address','City','Zip','Amount (Rs.)','Status','Payment Method','Date','Time'];
  let csv = headers.join(',') + '\n';

  ordersData.forEach(row => {
    csv += row.map(cell => {
      const s = String(cell ?? '');
      if(s.includes(',') || s.includes('"') || s.includes('\n')){
        return '"' + s.replace(/"/g, '""') + '"';
      }
      return s;
    }).join(',') + '\n';
  });

  const filter = '<?= $status_filter ?>';
  const search = '<?= addslashes($search) ?>';
  const suffix = filter ? '_' + filter : '';
  const suffix2 = search ? '_search' : '';
  const filename = 'orders' + suffix + suffix2 + '.csv';

  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);

  showToast(ordersData.length + ' orders exported!', 'success');
}

function showToast(msg, type){
  const t = document.createElement('div');
  t.className = 'toast toast-' + type;
  t.innerHTML = '<i class="fa-solid fa-' + (type==='success'?'check-circle':'exclamation-circle') + ' mr-2"></i>' + msg;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(), 3200);
}

function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>'}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
</script>
</body>
</html>