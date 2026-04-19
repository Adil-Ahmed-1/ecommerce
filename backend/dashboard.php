<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

 $uid = $_SESSION['user_id'];
 $user_res = mysqli_query($conn, "SELECT name, email, image, role FROM users WHERE id = $uid");
 $user = mysqli_fetch_assoc($user_res);

 $user_name = $user['name'] ?? 'Unknown';
 $user_email = $user['email'] ?? '';
 $user_image = !empty($user['image']) ? 'uploads/' . $user['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true';
 $user_role = ucfirst($user['role'] ?? 'user');

/* ===== DASHBOARD STATS ===== */
 $cat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories"))['total'];
 $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
 $user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];

/* ===== ORDER STATS ===== */
 $orders_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'];
 $orders_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'"))['total'];
 $orders_delivered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status = 'delivered'"))['total'];
 $orders_cancelled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled'"))['total'];
 $orders_processing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status = 'processing'"))['total'];
 $orders_shipped = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status = 'shipped'"))['total'];

/* ===== PAYMENT STATS (only existing columns) ===== */
 $payments_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payments"))['total'];
 $payments_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payments WHERE status = 'pending'"))['total'];
 $payments_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payments WHERE status = 'approved'"))['total'];
 $payments_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payments WHERE status = 'rejected'"))['total'];
 $payments_amount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'approved'"))['total'];
 $payments_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'approved' AND DATE(created_at) = CURDATE()"))['total'];

/* ===== REVENUE ===== */
 $revenue = $payments_amount;
 $revenue_today = $payments_today;

/* ===== CHART DATA ===== */
 $chart_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'pending'"))['c'];
 $chart_confirmed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'confirmed'"))['c'];
 $chart_processing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'processing'"))['c'];
 $chart_shipped = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'shipped'"))['c'];
 $chart_delivered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'delivered'"))['c'];
 $chart_cancelled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'cancelled'"))['c'];

/* ===== RECENT ORDERS ===== */
 $recent_orders = mysqli_query($conn, "
    SELECT o.* FROM orders o 
    ORDER BY o.created_at DESC LIMIT 5
");

/* ===== RECENT PAYMENTS (only user_id join, no order_id) ===== */
 $recent_payments = mysqli_query($conn, "
    SELECT p.*, u.name as user_name 
    FROM payments p 
    LEFT JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'pending' 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script>
tailwind.config = {
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        brand: {
          50: '#edfcf2', 100: '#d3f8e0', 200: '#aaf0c6',
          300: '#73e2a5', 400: '#3acd7e', 500: '#16b364',
          600: '#0a9150', 700: '#087442', 800: '#095c37',
          900: '#084b2e', 950: '#032a1a'
        }
      }
    }
  }
}
</script>

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Plus Jakarta Sans', sans-serif; }
  ::-webkit-scrollbar { width: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 99px; }
  .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

  .sidebar-glass { background: rgba(8, 75, 46, 0.95); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
  .dark .sidebar-glass { background: rgba(3, 42, 26, 0.98); }

  .nav-link { position: relative; transition: all 0.25s cubic-bezier(.4,0,.2,1); }
  .nav-link::before {
    content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    width: 3px; height: 0; border-radius: 0 4px 4px 0; background: #cbcd3a;
    transition: height 0.25s cubic-bezier(.4,0,.2,1);
  }
  .nav-link:hover::before, .nav-link.active::before { height: 60%; }
  .nav-link.active { background: rgba(58, 205, 126, 0.12); color: #cbcd3a; }
  .nav-link:hover { background: rgba(255,255,255,0.06); }

  .stat-card {
    position: relative; overflow: hidden;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1), box-shadow 0.3s ease;
  }
  .stat-card::after {
    content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(58,205,126,0.06) 0%, transparent 60%);
    opacity: 0; transition: opacity 0.4s ease; pointer-events: none;
  }
  .stat-card:hover::after { opacity: 1; }
  .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -12px rgba(0,0,0,0.12); }
  .dark .stat-card:hover { box-shadow: 0 20px 40px -12px rgba(0,0,0,0.4); }

  .counter { display: inline-block; }

  .icon-ring { animation: iconPulse 3s ease-in-out infinite; }
  @keyframes iconPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(58,205,126,0.2); }
    50% { box-shadow: 0 0 0 8px rgba(58,205,126,0); }
  }

  .fade-up { opacity: 0; transform: translateY(20px); animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
  .fade-up:nth-child(1) { animation-delay: 0.05s; }
  .fade-up:nth-child(2) { animation-delay: 0.1s; }
  .fade-up:nth-child(3) { animation-delay: 0.15s; }
  .fade-up:nth-child(4) { animation-delay: 0.2s; }
  .fade-up:nth-child(5) { animation-delay: 0.25s; }
  .fade-up:nth-child(6) { animation-delay: 0.3s; }

  .dropdown-enter { animation: dropIn 0.2s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes dropIn {
    from { opacity: 0; transform: translateY(-8px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }

  .chart-glow { position: relative; }
  .chart-glow::before {
    content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
    width: 60%; height: 40%;
    background: radial-gradient(ellipse, rgba(58,205,126,0.08) 0%, transparent 70%);
    pointer-events: none;
  }

  .sidebar-collapsed .sidebar-text { opacity: 0; width: 0; overflow: hidden; }
  .sidebar-collapsed .sidebar-logo-text { opacity: 0; width: 0; overflow: hidden; }
  .sidebar-collapsed .sidebar-avatar { width: 36px; height: 36px; }

  .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbcd3a; animation: livePulse 2s ease-in-out infinite; }
  @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

  .topbar-border { position: relative; }
  .topbar-border::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(58,205,126,0.3), transparent);
  }

  .role-badge { font-size: 9px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 7px; border-radius: 6px; }
  .role-admin { background: rgba(58,205,126,0.15); color: #cbcd3a; }
  .role-user { background: rgba(96,165,250,0.15); color: #60a5fa; }

  .order-status { font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; padding: 3px 10px; border-radius: 8px; white-space: nowrap; }
  .status-pending { background: rgba(245,158,11,0.12); color: #f59e0b; }
  .status-confirmed { background: rgba(59,130,246,0.12); color: #3b82f6; }
  .status-processing { background: rgba(139,92,246,0.12); color: #8b5cf6; }
  .status-shipped { background: rgba(99,102,241,0.12); color: #6366f1; }
  .status-delivered { background: rgba(16,185,129,0.12); color: #10b981; }
  .status-cancelled { background: rgba(239,68,68,0.12); color: #ef4444; }

  .pay-status-pending { background: rgba(245,158,11,0.12); color: #f59e0b; }
  .pay-status-approved { background: rgba(16,185,129,0.12); color: #10b981; }
  .pay-status-rejected { background: rgba(239,68,68,0.12); color: #ef4444; }

  .toast { position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 14px 20px; border-radius: 12px; font-size: 13px; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.15); animation: toastIn 0.3s ease, toastOut 0.3s ease 2.7s forwards; }
  .toast-success { background: #10b981; color: #fff; }
  .toast-error { background: #ef4444; color: #fff; }
  @keyframes toastIn { from { opacity: 0; transform: translateY(-20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
  @keyframes toastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-20px); } }

  .proof-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
  .proof-thumb:hover { border-color: #16b364; transform: scale(1.05); }

  .lightbox { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
  .lightbox.active { opacity: 1; pointer-events: all; }
  .lightbox img { max-width: 90%; max-height: 85vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
</style>

</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<div id="lightbox" class="lightbox" onclick="this.classList.remove('active')">
  <img id="lightboxImg" src="">
</div>

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
    <a href="product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="Order.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span>
      <?php if ($orders_pending > 0): ?><span class="ml-auto bg-amber-500 text-brand-950 text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300"><?= $orders_pending ?></span><?php endif; ?>
    </a>
    <a href="Payment_view_page.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span>
      <?php if ($payments_pending > 0): ?><span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300"><?= $payments_pending ?></span><?php endif; ?>
    </a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- ========== MAIN ========== -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Dashboard</h1>
      <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1.5"><span class="live-dot"></span> Live overview</p>
    </div>
    <div class="flex items-center gap-3">
      <div class="hidden md:flex items-center bg-gray-100 dark:bg-white/5 rounded-xl px-4 py-2 gap-2 w-56">
        <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
        <input type="text" placeholder="Search..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400">
      </div>
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70"><i class="fa-solid fa-moon text-sm"></i></button>
      <button class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70 relative">
        <i class="fa-solid fa-bell text-sm"></i>
        <?php if ($payments_pending > 0): ?><span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 rounded-full text-[9px] text-white font-bold flex items-center justify-center"><?= $payments_pending ?></span><?php endif; ?>
      </button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
          <div class="hidden sm:block text-left"><p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight"><?= htmlspecialchars($user_name) ?></p><p class="text-[10px] text-gray-400"><?= $user_role ?></p></div>
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl dark:shadow-2xl rounded-2xl w-52 py-2 overflow-hidden dropdown-enter">
          <div class="px-4 py-3 border-b border-gray-100 dark:border-white/5">
            <div class="flex items-center gap-3">
              <img src="<?= $user_image ?>" class="w-10 h-10 rounded-xl object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
              <div><p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></p><p class="text-[11px] text-gray-400"><?= htmlspecialchars($user_email) ?></p><span class="role-badge mt-1 inline-block <?= $user_role === 'Admin' ? 'role-admin' : 'role-user' ?>"><?= $user_role ?></span></div>
            </div>
          </div>
          <a href="profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile</a>
          <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-gear w-4 text-center text-xs"></i> Settings</a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1"><a href="logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition"><i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout</a></div>
        </div>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">

    <!-- Welcome Banner -->
    <div class="fade-up mb-6 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl p-6 flex items-center gap-5 text-white relative overflow-hidden">
      <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
      <div class="absolute -right-5 -bottom-16 w-32 h-32 bg-white/5 rounded-full blur-xl"></div>
      <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center shrink-0 relative z-10"><i class="fa-solid fa-hand text-2xl"></i></div>
      <div class="relative z-10">
        <h2 class="text-lg font-bold">Welcome back, <?= htmlspecialchars($user_name) ?>!</h2>
        <p class="text-sm text-white/70 mt-0.5">Logged in as <span class="font-semibold text-white"><?= $user_role ?></span>. Here's what's happening today.</p>
      </div>
      <div class="ml-auto hidden lg:flex items-center gap-8 relative z-10">
        <div class="text-center">
          <p class="text-xl font-extrabold"><?= $revenue_today > 0 ? 'Rs. ' . number_format($revenue_today, 0) : 'Rs. 0' ?></p>
          <p class="text-[10px] text-white/50 uppercase tracking-wider">Today's Revenue</p>
        </div>
        <div class="text-center">
          <p class="text-xl font-extrabold"><?= $payments_today > 0 ? 'Rs. ' . number_format($payments_today, 0) : 'Rs. 0' ?></p>
          <p class="text-[10px] text-white/50 uppercase tracking-wider">Today's Payments</p>
        </div>
      </div>
    </div>

    <!-- STATS GRID -->
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4">

      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-4 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Orders</p>
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1 counter" data-target="<?= $orders_total ?>">0</h2>
          </div>
          <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950 flex items-center justify-center"><i class="fa-solid fa-cart-shopping text-sky-500 text-sm"></i></div>
        </div>
        <p class="text-[10px] text-amber-500 font-semibold mt-1.5"><i class="fa-solid fa-clock text-[8px] mr-0.5"></i> <?= $orders_pending ?> pending</p>
      </div>

      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-4 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Delivered</p>
            <h2 class="text-2xl font-extrabold text-brand-500 mt-1 counter" data-target="<?= $orders_delivered ?>">0</h2>
          </div>
          <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center"><i class="fa-solid fa-circle-check text-brand-500 text-sm"></i></div>
        </div>
        <p class="text-[10px] text-gray-400 font-medium mt-1.5"><i class="fa-solid fa-check text-[8px] mr-0.5"></i> Completed</p>
      </div>

      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-4 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Payments</p>
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1 counter" data-target="<?= $payments_total ?>">0</h2>
          </div>
          <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-950 flex items-center justify-center"><i class="fa-solid fa-wallet text-violet-500 text-sm"></i></div>
        </div>
        <p class="text-[10px] text-red-500 font-semibold mt-1.5"><i class="fa-solid fa-exclamation text-[8px] mr-0.5"></i> <?= $payments_pending ?> pending</p>
      </div>

      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-4 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Approved</p>
            <h2 class="text-2xl font-extrabold text-brand-500 mt-1 counter" data-target="<?= $payments_approved ?>">0</h2>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center"><i class="fa-solid fa-check-double text-emerald-500 text-sm"></i></div>
        </div>
        <p class="text-[10px] text-brand-500 font-medium mt-1.5"><i class="fa-solid fa-arrow-trend-up text-[8px] mr-0.5"></i> Verified</p>
      </div>

      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-4 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Products</p>
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1 counter" data-target="<?= $prod ?>">0</h2>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950 flex items-center justify-center"><i class="fa-solid fa-box text-amber-500 text-sm"></i></div>
        </div>
        <p class="text-[10px] text-gray-400 font-medium mt-1.5"><i class="fa-solid fa-arrow-trend-up text-[8px] mr-0.5"></i> Listed</p>
      </div>

      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-4 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Revenue</p>
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-white mt-1">Rs. <?= number_format($payments_amount, 0) ?></h2>
          </div>
          <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950 flex items-center justify-center"><i class="fa-solid fa-money-bill-wave text-rose-500 text-sm"></i></div>
        </div>
        <p class="text-[10px] text-brand-500 font-medium mt-1.5"><i class="fa-solid fa-check text-[8px] mr-0.5"></i> Approved payments</p>
      </div>

    </div>

    <!-- CHART + RECENT -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mt-6">

      <!-- Chart -->
      <div class="xl:col-span-1 fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-6">
        <div class="mb-6">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">Order Status</h3>
          <p class="text-xs text-gray-400 mt-0.5">Distribution by status</p>
        </div>
        <div class="chart-glow flex items-center justify-center" style="height: 240px;"><canvas id="orderChart"></canvas></div>
        <div class="grid grid-cols-2 gap-2 mt-4">
          <span class="flex items-center gap-1.5 text-[10px] text-gray-500"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Pending (<?= $chart_pending ?>)</span>
          <span class="flex items-center gap-1.5 text-[10px] text-gray-500"><span class="w-2 h-2 rounded-full bg-blue-400"></span>Confirmed (<?= $chart_confirmed ?>)</span>
          <span class="flex items-center gap-1.5 text-[10px] text-gray-500"><span class="w-2 h-2 rounded-full bg-violet-400"></span>Processing (<?= $chart_processing ?>)</span>
          <span class="flex items-center gap-1.5 text-[10px] text-gray-500"><span class="w-2 h-2 rounded-full bg-indigo-400"></span>Shipped (<?= $chart_shipped ?>)</span>
          <span class="flex items-center gap-1.5 text-[10px] text-gray-500"><span class="w-2 h-2 rounded-full bg-emerald-400"></span>Delivered (<?= $chart_delivered ?>)</span>
          <span class="flex items-center gap-1.5 text-[10px] text-gray-500"><span class="w-2 h-2 rounded-full bg-red-400"></span>Cancelled (<?= $chart_cancelled ?>)</span>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <div><h3 class="text-base font-bold text-gray-900 dark:text-white">Recent Orders</h3><p class="text-xs text-gray-400 mt-0.5">Last 5 orders</p></div>
          <a href="orders/view.php" class="text-xs font-semibold text-brand-500 hover:text-brand-600 transition flex items-center gap-1">View All <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
        </div>
        <?php if (mysqli_num_rows($recent_orders) > 0): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-100 dark:border-white/5">
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold pb-2.5">Order</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold pb-2.5">Customer</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold pb-2.5">Amount</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold pb-2.5">Status</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold pb-2.5">Method</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50 dark:divide-white/[0.03]">
              <?php while ($row = mysqli_fetch_assoc($recent_orders)): ?>
              <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                <td class="py-2.5"><a href="orders/detail.php?id=<?= $row['id'] ?>" class="font-bold text-gray-900 dark:text-white hover:text-brand-500 transition text-[11px] font-mono"><?= htmlspecialchars($row['order_number']) ?></a></td>
                <td class="py-2.5"><p class="text-[11px] font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></p></td>
                <td class="py-2.5 font-bold text-gray-900 dark:text-white text-[11px]">Rs. <?= number_format($row['total_amount'], 0) ?></td>
                <td class="py-2.5"><span class="order-status status-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                <td class="py-2.5"><span class="text-[10px] text-gray-500"><?= ucfirst(str_replace('_', ' ', $row['payment_method'] ?? 'cod')) ?></span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="text-center py-10"><i class="fa-solid fa-inbox text-3xl text-gray-200 dark:text-gray-700 mb-2"></i><p class="text-xs text-gray-400">No orders yet</p></div>
        <?php endif; ?>
      </div>

      <!-- Pending Payments -->
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <div><h3 class="text-base font-bold text-gray-900 dark:text-white">Pending Payments</h3><p class="text-xs text-gray-400 mt-0.5">Need verification</p></div>
          <a href="Payment_view_page.php" class="text-xs font-semibold text-brand-500 hover:text-brand-600 transition flex items-center gap-1">View All <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
        </div>
        <?php if (mysqli_num_rows($recent_payments) > 0): ?>
        <div class="space-y-3">
          <?php while ($p = mysqli_fetch_assoc($recent_payments)): ?>
          <a href="Payment_detail_page.php?id=<?= $p['id'] ?>" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/[0.02] hover:bg-gray-100 dark:hover:bg-white/[0.04] transition group">
            <?php if ($p['proof_image']): ?>
              <img src="../uploads/payments/<?= htmlspecialchars($p['proof_image']) ?>" class="proof-thumb" onclick="event.stopPropagation(); openLightbox(this.src)" onerror="this.src='https://ui-avatars.com/api/?name=P&background=f3f4f6&color=6b7280&size=48'">
            <?php else: ?>
              <div class="w-12 h-12 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center"><i class="fa-solid fa-image text-gray-400 text-sm"></i></div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-brand-500 transition">Rs. <?= number_format($p['amount'], 0) ?></p>
              <p class="text-[10px] text-gray-400 truncate"><?= htmlspecialchars($p['sender_name'] ?? 'Unknown') ?> · <?= ucfirst($p['method']) ?></p>
              <p class="text-[10px] text-gray-300"><?= date('d M, h:i A', strtotime($p['created_at'])) ?></p>
            </div>
            <span class="order-status pay-status-pending">Pending</span>
          </a>
          <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-10"><i class="fa-solid fa-check-circle text-3xl text-brand-200 dark:text-brand-900 mb-2"></i><p class="text-xs text-gray-400">All payments verified!</p></div>
        <?php endif; ?>
      </div>

    </div>

  </div>
</main>

<script>
function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>';if(window.orderChart){window.orderChart.options.plugins.legend.labels.color=d?'#9ca3af':'#6b7280';window.orderChart.update()}}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
function openLightbox(src){document.getElementById('lightboxImg').src=src;document.getElementById('lightbox').classList.add('active')}

document.querySelectorAll('.counter').forEach(counter=>{const target=parseInt(counter.getAttribute('data-target'));if(target===0)return;const duration=1200;const startTime=performance.now();function update(currentTime){const elapsed=currentTime-startTime;const progress=Math.min(elapsed/duration,1);const eased=1-Math.pow(1-progress,3);counter.textContent=Math.round(eased*target);if(progress<1)requestAnimationFrame(update)}requestAnimationFrame(update)});

window.orderChart=new Chart(document.getElementById('orderChart'),{type:'doughnut',data:{labels:['Pending','Confirmed','Processing','Shipped','Delivered','Cancelled'],datasets:[{data:[<?= $chart_pending ?>,<?= $chart_confirmed ?>,<?= $chart_processing ?>,<?= $chart_shipped ?>,<?= $chart_delivered ?>,<?= $chart_cancelled ?>],backgroundColor:['#f59e0b','#3b82f6','#8b5cf6','#6366f1','#10b981','#ef4444'],hoverBackgroundColor:['#d97706','#2563eb','#7c3aed','#4f46e5','#059669','#dc2626'],borderWidth:0,spacing:3,borderRadius:5}]},options:{responsive:true,maintainAspectRatio:false,cutout:'70%',plugins:{legend:{display:false},tooltip:{backgroundColor:'#1a1a1a',titleFont:{family:'Plus Jakarta Sans',weight:'600',size:12},bodyFont:{family:'Plus Jakarta Sans',size:11},padding:10,cornerRadius:8,displayColors:true,boxPadding:3}},animation:{animateRotate:true,duration:1200,easing:'easeOutQuart'}},plugins:[{id:'centerText',beforeDraw(chart){const{ctx,width,height}=chart;const total=chart.data.datasets[0].data.reduce((a,b)=>a+b,0);ctx.save();ctx.textAlign='center';ctx.textBaseline='middle';ctx.font='800 24px Plus Jakarta Sans';ctx.fillStyle=getComputedStyle(document.body).classList.contains('dark')?'#ffffff':'#111827';ctx.fillText(total,width/2,height/2-6);ctx.font='500 10px Plus Jakarta Sans';ctx.fillStyle='#9ca3af';ctx.fillText('Orders',width/2,height/2+14);ctx.restore()}}]});
</script>

</body>
</html>