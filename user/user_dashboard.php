<?php
session_start();
include("../backend/config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_role'] === 'admin') {
    header("Location: ../admin.php");
    exit;
}

 $uid = $_SESSION['user_id'];

 $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->bind_param("i", $uid);
 $stmt->execute();
 $result = $stmt->get_result();
 $user = mysqli_fetch_assoc($result);

if (!$user) {
    session_unset();
    header("Location: login.php");
    exit;
}

 $user_name  = $user['name'] ?? $_SESSION['user_name'] ?? 'User';
 $user_email = $user['email'] ?? '';
 $user_image = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true&size=128';

/* ===== FETCH STATS ===== */
 $total_orders = 0;
 $pending_orders = 0;
 $completed_orders = 0;
 $recent_orders = [];

 $check = $conn->query("SHOW TABLES LIKE 'orders'");
if ($check->num_rows > 0) {
    $oStmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM orders WHERE user_id = ? GROUP BY status");
    $oStmt->bind_param("i", $uid);
    $oStmt->execute();
    $oResult = $oStmt->get_result();
    while ($row = $oResult->fetch_assoc()) {
        $total_orders += $row['cnt'];
        $st = strtolower($row['status']);
        if ($st === 'pending') $pending_orders = $row['cnt'];
        if (in_array($st, ['completed','delivered'])) $completed_orders = $row['cnt'];
    }

    $rStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $rStmt->bind_param("i", $uid);
    $rStmt->execute();
    $rResult = $rStmt->get_result();
    while ($r = $rResult->fetch_assoc()) {
        $recent_orders[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family+Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        brand: {
          50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',
          400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442',
          800:'#095c37',900:'#084b2e',950:'#032a1a'
        }
      }
    }
  }
}
</script>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Plus Jakarta Sans',sans-serif; }
  ::-webkit-scrollbar { width:6px; }
  ::-webkit-scrollbar-track { background:transparent; }
  ::-webkit-scrollbar-thumb { background:rgba(0,0,0,0.12); border-radius:99px; }
  .dark ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.08); }

  .sidebar-glass {
    background:rgba(8,75,46,0.95);
    backdrop-filter:blur(20px);
  }
  .dark .sidebar-glass { background:rgba(3,42,26,0.98); }

  .nav-link { position:relative; transition:all 0.25s ease; }
  .nav-link::before {
    content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
    width:3px;height:0;border-radius:0 4px 4px 0;
    background:#3acd7e;transition:height 0.25s ease;
  }
  .nav-link:hover::before,.nav-link.active::before { height:60%; }
  .nav-link.active { background:rgba(58,205,126,0.12); color:#3acd7e; }
  .nav-link:hover { background:rgba(255,255,255,0.06); }

  .topbar-line::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(58,205,126,0.25),transparent);
  }

  .stat-card { transition:all 0.3s ease; }
  .stat-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px -8px rgba(0,0,0,0.12); }
  .dark .stat-card:hover { box-shadow:0 12px 32px -8px rgba(0,0,0,0.4); }

  .fade-up { opacity:0;transform:translateY(20px); animation:fadeUp 0.5s ease forwards; }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  .sidebar-collapsed .sidebar-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-logo-text { opacity:0; width:0; overflow:hidden; }

  .order-row { transition:all 0.2s ease; }
  .order-row:hover { background:rgba(58,205,126,0.04); }

  .status-badge {
    font-size:10px;font-weight:700;letter-spacing:0.03em;
    text-transform:uppercase;padding:3px 8px;border-radius:6px;
  }
  .status-pending { background:rgba(245,158,11,0.15); color:#f59e0b; }
  .status-completed { background:rgba(22,179,100,0.15); color:#16b364; }
  .status-delivered { background:rgba(22,179,100,0.15); color:#16b364; }
  .status-cancelled { background:rgba(239,68,68,0.15); color:#ef4444; }
  .status-processing { background:rgba(59,130,246,0.15); color:#3b82f6; }
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">
        <i class="fa-solid fa-store text-xs"></i>
      </div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">Commerce</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm">
      <i class="fa-solid fa-bars text-xs"></i>
    </button>
  </div>

  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="<?= $user_image ?>" class="w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p>
      <p class="text-[11px] text-white/50 mt-0.5"><?= htmlspecialchars($user_email) ?></p>
    </div>
  </div>

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="user_dashboard.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-house w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-user-gear w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">My Profile</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Shop</p>
    <a href="../frontend/" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Browse Products</span>
    </a>
    <a href="../frontend/category_products.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Categories</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Account</p>
    <a href="logout.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10">
      <i class="fa-solid fa-right-from-bracket w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Logout</span>
    </a>
  </nav>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">

  <header class="topbar-line sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Dashboard</h1>
      <p class="text-xs text-gray-400 mt-0.5">Welcome back, <?= htmlspecialchars($user_name) ?>!</p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>
      <div class="flex items-center gap-2.5 pl-3 pr-4 py-1.5 rounded-xl bg-gray-50 dark:bg-white/5">
        <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover">
        <span class="text-sm font-semibold text-gray-700 dark:text-white hidden sm:block"><?= htmlspecialchars($user_name) ?></span>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
            <i class="fa-solid fa-bag-shopping text-brand-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-brand-500 bg-brand-50 dark:bg-brand-950 px-2 py-1 rounded-md">Total</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $total_orders ?></p>
        <p class="text-xs text-gray-400 mt-1">Total Orders</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.05s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-yellow-50 dark:bg-yellow-950 flex items-center justify-center">
            <i class="fa-solid fa-clock text-yellow-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-yellow-500 bg-yellow-50 dark:bg-yellow-950 px-2 py-1 rounded-md">Pending</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $pending_orders ?></p>
        <p class="text-xs text-gray-400 mt-1">Pending Orders</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.1s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-green-50 dark:bg-green-950 flex items-center justify-center">
            <i class="fa-solid fa-circle-check text-green-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-green-500 bg-green-50 dark:bg-green-950 px-2 py-1 rounded-md">Done</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $completed_orders ?></p>
        <p class="text-xs text-gray-400 mt-1">Completed</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.15s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-violet-50 dark:bg-violet-950 flex items-center justify-center">
            <i class="fa-solid fa-fingerprint text-violet-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-violet-500 bg-violet-50 dark:bg-violet-950 px-2 py-1 rounded-md">ID</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white">#<?= str_pad($uid, 4, '0', STR_PAD_LEFT) ?></p>
        <p class="text-xs text-gray-400 mt-1">Account ID</p>
      </div>
    </div>

    <!-- BOTTOM GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- RECENT ORDERS -->
      <div class="lg:col-span-2 fade-up" style="animation-delay:0.2s">
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
                <i class="fa-solid fa-clock-rotate-left text-brand-500 text-sm"></i>
              </div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recent Orders</h3>
            </div>
          </div>

          <div class="divide-y divide-gray-50 dark:divide-white/5">
            <?php if (empty($recent_orders)) { ?>
            <div class="px-6 py-12 text-center">
              <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-bag-shopping text-gray-300 text-2xl"></i>
              </div>
              <p class="text-sm font-semibold text-gray-400">No orders yet</p>
              <p class="text-xs text-gray-300 mt-1">Start shopping to see your orders here</p>
              <a href="../frontend/" class="inline-flex items-center gap-2 text-xs font-semibold text-brand-600 hover:text-brand-700 mt-4 transition">
                <i class="fa-solid fa-arrow-right text-[10px]"></i> Browse Products
              </a>
            </div>
            <?php } else { ?>
              <?php foreach ($recent_orders as $order) {
                $status = strtolower($order['status'] ?? 'pending');
                $statusClass = 'status-pending';
                if (in_array($status, ['completed','delivered'])) $statusClass = 'status-delivered';
                if ($status === 'cancelled') $statusClass = 'status-cancelled';
                if ($status === 'processing') $statusClass = 'status-processing';
              ?>
              <div class="order-row px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                    <i class="fa-solid fa-receipt text-gray-400 text-sm"></i>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></p>
                    <p class="text-[11px] text-gray-400"><?= date('d M, Y', strtotime($order['created_at'] ?? 'now')) ?></p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold text-gray-800 dark:text-white">Rs. <?= number_format($order['total_amount'] ?? 0) ?></p>
                  <span class="status-badge <?= $statusClass ?>"><?= ucfirst($status) ?></span>
                </div>
              </div>
              <?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>

      <!-- PROFILE CARD -->
      <div class="fade-up" style="animation-delay:0.25s">
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          <div class="h-24 bg-gradient-to-r from-brand-500 to-brand-600 relative">
            <div class="absolute inset-0 bg-[url('https://picsum.photos/seed/user-cover/800/300')] bg-cover bg-center opacity-20"></div>
          </div>
          <div class="px-6 pb-6 -mt-10 relative z-10 text-center">
            <img src="<?= $user_image ?>" class="w-20 h-20 rounded-2xl object-cover border-4 border-white dark:border-[#131a16] shadow-lg mx-auto">
            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white mt-3"><?= htmlspecialchars($user_name) ?></h3>
            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($user_email) ?></p>
            <span class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wider text-brand-600 bg-brand-50 dark:bg-brand-950 px-3 py-1 rounded-md">Customer</span>
          </div>
          <div class="px-6 pb-6 space-y-3">
            <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gray-50 dark:bg-white/5 hover:bg-brand-50 dark:hover:bg-brand-950 transition group">
              <i class="fa-solid fa-user-pen text-gray-400 group-hover:text-brand-500 text-sm transition"></i>
              <span class="text-sm font-medium text-gray-600 dark:text-white/70 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition">Edit Profile</span>
              <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 ml-auto"></i>
            </a>
            <a href="../frontend/" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gray-50 dark:bg-white/5 hover:bg-brand-50 dark:hover:bg-brand-950 transition group">
              <i class="fa-solid fa-cart-shopping text-gray-400 group-hover:text-brand-500 text-sm transition"></i>
              <span class="text-sm font-medium text-gray-600 dark:text-white/70 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition">Shop Now</span>
              <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 ml-auto"></i>
            </a>
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-950 hover:bg-red-100 dark:hover:bg-red-900 transition group">
              <i class="fa-solid fa-right-from-bracket text-red-400 text-sm"></i>
              <span class="text-sm font-medium text-red-500">Logout</span>
              <i class="fa-solid fa-chevron-right text-[10px] text-red-300 ml-auto"></i>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<script>
function toggleSidebar() {
  var sidebar = document.getElementById('sidebar');
  var main = document.getElementById('main');
  var collapsed = sidebar.classList.toggle('sidebar-collapsed');
  sidebar.style.width = collapsed ? '78px' : '260px';
  main.style.marginLeft = collapsed ? '78px' : '260px';
}
function toggleDark() {
  var html = document.documentElement;
  var body = document.body;
  var btn = document.getElementById('darkBtn');
  var isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);
  btn.innerHTML = isDark ? '<i class="fa-solid fa-sun text-sm"></i>' : '<i class="fa-solid fa-moon text-sm"></i>';
}
</script>

</body>
</html>