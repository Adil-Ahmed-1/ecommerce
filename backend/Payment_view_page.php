<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

 $uid = $_SESSION['user_id'];
 $user_res = mysqli_query($conn, "SELECT name, email, image, role FROM users WHERE id = $uid");
 $user = mysqli_fetch_assoc($user_res);
 $user_name = $user['name'] ?? 'Unknown';
 $user_email = $user['email'] ?? '';
 $user_image = !empty($user['image']) ? 'uploads/' . $user['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true';
 $user_role = ucfirst($user['role'] ?? 'user');

/* ===== FILTERS ===== */
 $status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
 $method_filter = isset($_GET['method']) ? mysqli_real_escape_string($conn, $_GET['method']) : '';
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
 $page = max(1, (int)($_GET['page'] ?? 1));
 $limit = 15;
 $offset = ($page - 1) * $limit;

 $where = "1=1";
if ($status_filter) $where .= " AND p.status = '$status_filter'";
if ($method_filter) $where .= " AND p.method = '$method_filter'";
if ($search) $where .= " AND (p.transaction_id LIKE '%$search%' OR p.sender_name LIKE '%$search%' OR p.sender_number LIKE '%$search%' OR p.amount LIKE '%$search%')";

 $total_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM payments p WHERE $where");
 $total_payments = mysqli_fetch_assoc($total_res)['c'];
 $total_pages = ceil($total_payments / $limit);

 $payments_res = mysqli_query($conn, "
    SELECT p.*, u.name as user_name, u.email as user_email
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
");

/* ===== STATUS COUNTS ===== */
 $counts = [
    'all'      => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments"))['c'],
    'pending'  => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE status='pending'"))['c'],
    'approved' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE status='approved'"))['c'],
    'rejected' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE status='rejected'"))['c'],
];

 $sum_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE status='approved'"))['t'];
 $sum_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE status='pending'"))['t'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments</title>
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
  .order-status{font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:3px 10px;border-radius:8px;white-space:nowrap}
  .pay-status-pending{background:rgba(245,158,11,0.12);color:#f59e0b}.pay-status-approved{background:rgba(16,185,129,0.12);color:#10b981}.pay-status-rejected{background:rgba(239,68,68,0.12);color:#ef4444}
  .method-jazzcash{background:rgba(239,68,68,0.12);color:#ef4444}.method-easypaisa{background:rgba(16,185,129,0.12);color:#10b981}.method-bank_transfer{background:rgba(59,130,246,0.12);color:#3b82f6}.method-card{background:rgba(139,92,246,0.12);color:#8b5cf6}
  .filter-tab{padding:7px 16px;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s;white-space:nowrap}
  .dark .filter-tab{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6)}
  .filter-tab:hover{background:#f9fafb}.filter-tab.active{background:#16b364;color:#fff;border-color:#16b364}
  .page-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;text-decoration:none;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s}
  .dark .page-btn{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6)}
  .page-btn:hover{background:#f3f4f6}.page-btn.active{background:#16b364;color:#fff;border-color:#16b364}
  .toast{position:fixed;top:20px;right:20px;z-index:9999;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,0.15);animation:toastIn .3s ease,toastOut .3s ease 2.7s forwards}
  .toast-success{background:#10b981;color:#fff}.toast-error{background:#ef4444;color:#fff}
  @keyframes toastIn{from{opacity:0;transform:translateY(-20px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes toastOut{from{opacity:1;transform:translateY(0)}to{opacity:0;transform:translateY(-20px)}}
  .fade-up{opacity:0;transform:translateY(20px);animation:fadeUp .5s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
  .proof-thumb{width:48px;height:48px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:all .2s}
  .proof-thumb:hover{border-color:#16b364;transform:scale(1.05)}
  .lightbox{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .3s}
  .lightbox.active{opacity:1;pointer-events:all}
  .lightbox img{max-width:90%;max-height:85vh;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
  .dropdown-enter{animation:dropIn .2s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes dropIn{from{opacity:0;transform:translateY(-8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<div id="lightbox" class="lightbox" onclick="this.classList.remove('active')"><img id="lightboxImg" src=""></div>

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
    <?php if ($user_role === 'Admin') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="Order.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <a href="view.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span>
      <?php if ($counts['pending'] > 0): ?><span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300"><?= $counts['pending'] ?></span><?php endif; ?>
    </a>
    <?php } ?>
  </nav>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div><h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Payments</h1><p class="text-xs text-gray-400 mt-0.5"><?= $total_payments ?> total payments</p></div>
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

    <!-- Summary Cards -->
    <div class="fade-up grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950 flex items-center justify-center"><i class="fa-solid fa-clock text-amber-500 text-lg"></i></div>
        <div><p class="text-xs text-gray-400 font-medium">Pending</p><p class="text-xl font-extrabold text-gray-900 dark:text-white"><?= $counts['pending'] ?></p><p class="text-[11px] text-amber-500 font-semibold">Rs. <?= number_format($sum_pending, 0) ?></p></div>
      </div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center"><i class="fa-solid fa-check-double text-brand-500 text-lg"></i></div>
        <div><p class="text-xs text-gray-400 font-medium">Approved</p><p class="text-xl font-extrabold text-gray-900 dark:text-white"><?= $counts['approved'] ?></p><p class="text-[11px] text-brand-500 font-semibold">Rs. <?= number_format($sum_approved, 0) ?></p></div>
      </div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950 flex items-center justify-center"><i class="fa-solid fa-xmark text-red-500 text-lg"></i></div>
        <div><p class="text-xs text-gray-400 font-medium">Rejected</p><p class="text-xl font-extrabold text-gray-900 dark:text-white"><?= $counts['rejected'] ?></p><p class="text-[11px] text-gray-400 font-medium">Blocked</p></div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="fade-up flex gap-2 mb-5 overflow-x-auto pb-1">
      <a href="view.php" class="filter-tab <?= !$status_filter ? 'active' : '' ?>">All (<?= $counts['all'] ?>)</a>
      <a href="view.php?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">Pending (<?= $counts['pending'] ?>)</a>
      <a href="view.php?status=approved" class="filter-tab <?= $status_filter === 'approved' ? 'active' : '' ?>">Approved (<?= $counts['approved'] ?>)</a>
      <a href="view.php?status=rejected" class="filter-tab <?= $status_filter === 'rejected' ? 'active' : '' ?>">Rejected (<?= $counts['rejected'] ?>)</a>
      <a href="view.php?method=jazzcash" class="filter-tab <?= $method_filter === 'jazzcash' ? 'active' : '' ?>"><i class="fa-solid fa-mobile-screen text-[10px] mr-1"></i>JazzCash</a>
      <a href="view.php?method=easypaisa" class="filter-tab <?= $method_filter === 'easypaisa' ? 'active' : '' ?>"><i class="fa-solid fa-mobile-screen text-[10px] mr-1"></i>EasyPaisa</a>
      <a href="view.php?method=bank_transfer" class="filter-tab <?= $method_filter === 'bank_transfer' ? 'active' : '' ?>"><i class="fa-solid fa-building-columns text-[10px] mr-1"></i>Bank</a>
    </div>

    <!-- Search -->
    <form method="GET" class="fade-up mb-5">
      <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= $status_filter ?>"><?php endif; ?>
      <?php if ($method_filter): ?><input type="hidden" name="method" value="<?= $method_filter ?>"><?php endif; ?>
      <div class="flex gap-3">
        <div class="flex-1 flex items-center bg-white dark:bg-[#131a16] rounded-xl px-4 py-2.5 gap-2 border border-gray-100 dark:border-white/5">
          <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by transaction ID, sender name, number, amount..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400">
        </div>
        <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition">Search</button>
        <?php if ($search || $status_filter || $method_filter): ?><a href="view.php" class="px-5 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-sm font-semibold transition">Clear</a><?php endif; ?>
      </div>
    </form>

    <!-- Table -->
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
      <?php if (mysqli_num_rows($payments_res) > 0): ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50/80 dark:bg-white/[0.02]">
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Payment</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">User</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Amount</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Method</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Sender</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Proof</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Status</th>
              <th class="text-left text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Date</th>
              <th class="text-center text-[10px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-white/[0.03]">
            <?php while ($row = mysqli_fetch_assoc($payments_res)): ?>
            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
              <td class="px-5 py-3.5">
                <p class="text-xs font-bold text-gray-900 dark:text-white font-mono">#PAY-<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></p>
                <?php if ($row['transaction_id']): ?><p class="text-[10px] text-gray-400 font-mono mt-0.5"><?= htmlspecialchars($row['transaction_id']) ?></p><?php endif; ?>
                <?php if (!empty($row['notes'])): ?><p class="text-[10px] text-gray-400 mt-0.5 truncate max-w-[140px]" title="<?= htmlspecialchars($row['notes']) ?>"> <?= htmlspecialchars($row['notes']) ?></p><?php endif; ?>
              </td>
              <td class="px-5 py-3.5">
                <p class="text-xs font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($row['user_name'] ?? 'Unknown') ?></p>
                <p class="text-[10px] text-gray-400"><?= htmlspecialchars($row['user_email'] ?? '') ?></p>
              </td>
              <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white text-xs">Rs. <?= number_format($row['amount'], 0) ?></td>
              <td class="px-5 py-3.5"><span class="order-status method-<?= $row['method'] ?>"><?= ucfirst(str_replace('_', ' ', $row['method'])) ?></span></td>
              <td class="px-5 py-3.5">
                <p class="text-xs font-medium text-gray-800 dark:text-white"><?= htmlspecialchars($row['sender_name'] ?? '-') ?></p>
                <p class="text-[10px] text-gray-400"><?= htmlspecialchars($row['sender_number'] ?? '-') ?></p>
              </td>
              <td class="px-5 py-3.5">
                <?php if (!empty($row['proof_image'])): ?>
                  <img src="../uploads/payments/<?= htmlspecialchars($row['proof_image']) ?>" class="proof-thumb" onclick="openLightbox(this.src)" onerror="this.style.display='none'">
                <?php else: ?>
                  <span class="text-[10px] text-gray-300">No proof</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3.5"><span class="order-status pay-status-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
              <td class="px-5 py-3.5">
                <p class="text-[11px] text-gray-500"><?= date('d M Y', strtotime($row['created_at'])) ?></p>
                <p class="text-[10px] text-gray-400"><?= date('h:i A', strtotime($row['created_at'])) ?></p>
              </td>
              <td class="px-5 py-3.5 text-center">
                <?php if ($row['status'] === 'pending'): ?>
                  <a href="detail.php?id=<?= $row['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400 rounded-lg text-[11px] font-semibold hover:bg-amber-100 dark:hover:bg-amber-900 transition">
                    <i class="fa-solid fa-eye text-[10px]"></i> Verify
                  </a>
                <?php else: ?>
                  <a href="detail.php?id=<?= $row['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 rounded-lg text-[11px] font-semibold hover:bg-gray-100 dark:hover:bg-white/10 transition">
                    <i class="fa-solid fa-eye text-[10px]"></i> View
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <?php if ($total_pages > 1): ?>
      <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100 dark:border-white/5">
        <p class="text-xs text-gray-400">Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $total_payments) ?> of <?= $total_payments ?></p>
        <div class="flex gap-1.5">
          <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $method_filter ? '&method='.$method_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-btn"><i class="fa-solid fa-chevron-left text-[10px]"></i></a><?php endif; ?>
          <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <a href="?page=<?= $i ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $method_filter ? '&method='.$method_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?><a href="?page=<?= $page+1 ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $method_filter ? '&method='.$method_filter : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="page-btn"><i class="fa-solid fa-chevron-right text-[10px]"></i></a><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="text-center py-16"><i class="fa-solid fa-inbox text-4xl text-gray-200 dark:text-gray-700 mb-4"></i><p class="text-sm text-gray-400 font-medium">No payments found</p></div>
      <?php endif; ?>
    </div>

  </div>
</main>

<script>
function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>'}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
function openLightbox(src){document.getElementById('lightboxImg').src=src;document.getElementById('lightbox').classList.add('active')}
</script>

</body>
</html>