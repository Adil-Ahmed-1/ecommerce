<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) { header("Location: ../user/login.php"); exit; }

 $uid = $_SESSION['user_id'];
 $user_res = mysqli_query($conn, "SELECT name, email, image, role FROM users WHERE id = $uid");
 $user = mysqli_fetch_assoc($user_res);
 $user_name = $user['name'] ?? 'Unknown';
 $user_email = $user['email'] ?? '';
 $user_image = !empty($user['image']) ? 'uploads/' . $user['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true';
 $user_role = ucfirst($user['role'] ?? 'user');

/* ✅ AUTO-DETECT: which columns actually exist */
 $chk = mysqli_query($conn, "SHOW COLUMNS FROM categories LIKE 'category_name'");
 $cat_name_col = (mysqli_num_rows($chk) > 0) ? 'category_name' : 'name';

 $chk2 = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'product_name'");
 $prod_name_col = (mysqli_num_rows($chk2) > 0) ? 'product_name' : 'name';

 $chk3 = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'discount_price'");
 $has_discount_col = (mysqli_num_rows($chk3) > 0);

 $chk4 = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'is_active'");
 $has_is_active = (mysqli_num_rows($chk4) > 0);

 $chk5 = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'status'");
 $has_status = (mysqli_num_rows($chk5) > 0);

/* Helper: get product status display value */
function getProductStatus($p, $has_is_active, $has_status) {
    if ($has_is_active && isset($p['is_active'])) {
        return $p['is_active'] == 1 ? 'active' : 'inactive';
    }
    if ($has_status && isset($p['status'])) {
        return $p['status'];
    }
    return 'active';
}

/* ===== DELETE ===== */
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id = $did"));
    if ($prod && !empty($prod['image']) && file_exists(__DIR__ . '/uploads/' . $prod['image'])) unlink(__DIR__ . '/uploads/' . $prod['image']);
    mysqli_query($conn, "DELETE FROM products WHERE id = $did");
    $_SESSION['toast'] = ['type'=>'success','message'=>'Product deleted'];
    header("Location: view.php"); exit;
}

/* ===== TOGGLE STATUS ===== */
if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    if ($has_is_active) {
        $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM products WHERE id = $tid"));
        if ($cur) {
            $new = $cur['is_active'] == 1 ? 0 : 1;
            mysqli_query($conn, "UPDATE products SET is_active = $new WHERE id = $tid");
            $_SESSION['toast'] = ['type'=>'success','message'=>'Status updated to ' . ($new ? 'Active' : 'Inactive')];
        }
    } elseif ($has_status) {
        $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM products WHERE id = $tid"));
        if ($cur) {
            $new = $cur['status'] === 'active' ? 'inactive' : 'active';
            mysqli_query($conn, "UPDATE products SET status = '$new' WHERE id = $tid");
            $_SESSION['toast'] = ['type'=>'success','message'=>'Status updated to ' . ucfirst($new)];
        }
    }
    header("Location: view.php"); exit;
}

 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
 $cat_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

 $where = "1=1";
/* ✅ FIX: use detected column name for product name */
if ($search) $where .= " AND (p.$prod_name_col LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($cat_filter) $where .= " AND p.category_id = $cat_filter";

/* ✅ FIX: use c.category_name instead of c.name */
 $prods_res = mysqli_query($conn, "SELECT p.*, c.$cat_name_col AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.created_at DESC");
 $total_prods = mysqli_num_rows($prods_res);

/* ✅ FIX: use detected column name for categories */
 $all_cats = mysqli_query($conn, "SELECT id, $cat_name_col FROM categories WHERE status='active' ORDER BY $cat_name_col");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Plus Jakarta Sans','sans-serif']},colors:{brand:{50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442',800:'#095c37',900:'#084b2e',950:'#032a1a'}}}}}</script>
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
  .form-input{width:100%;padding:10px 14px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:13px;outline:none;transition:all .2s}
  .dark .form-input{background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.85)}
  .form-input:focus{border-color:#16b364;box-shadow:0 0 0 3px rgba(22,179,100,0.1)}
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
    <?php if ($user_role === 'Admin') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="../category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="../category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="view.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <a href="payments.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span></a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div><h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Products</h1><p class="text-xs text-gray-400 mt-0.5"><?= $total_prods ?> products</p></div>
    <div class="flex items-center gap-3">
      <a href="product/add.php" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition"><i class="fa-solid fa-plus mr-2 text-xs"></i>Add New</a>
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

    <!-- Filters -->
    <form method="GET" class="fade-up flex flex-wrap gap-3 mb-5">
      <div class="flex-1 min-w-[200px] flex items-center bg-white dark:bg-[#131a16] rounded-xl px-4 py-2.5 gap-2 border border-gray-100 dark:border-white/5">
        <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400">
      </div>
      <select name="category" class="form-input w-auto min-w-[160px]">
        <option value="">All Categories</option>
        <?php while ($c = mysqli_fetch_assoc($all_cats)): ?>
          <option value="<?= $c['id'] ?>" <?= $cat_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c[$cat_name_col]) ?></option>
        <?php endwhile; ?>
      </select>
      <button type="submit" class="px-5 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-sm font-semibold transition">Filter</button>
      <?php if ($search || $cat_filter): ?>
        <a href="view.php" class="px-5 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-sm font-semibold transition">Clear</a>
      <?php endif; ?>
    </form>

    <!-- Products Grid -->
    <?php if (mysqli_num_rows($prods_res) > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <?php while ($p = mysqli_fetch_assoc($prods_res)):
        /* ✅ FIX: use detected column name for product name */
        $prod_title = $p[$prod_name_col] ?? 'Untitled';

        /* ✅ FIX: safe discount check */
        $disc_price = ($has_discount_col && isset($p['discount_price'])) ? (float)$p['discount_price'] : 0;
        $has_discount = $disc_price > 0 && $disc_price < (float)$p['price'];
        $discount_pct = $has_discount ? round((($p['price'] - $disc_price) / $p['price']) * 100) : 0;

        /* ✅ FIX: safe status detection */
        $p_status = getProductStatus($p, $has_is_active, $has_status);
      ?>
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden hover:shadow-md transition-shadow group">
        <!-- Image -->
        <div class="h-44 bg-gray-50 dark:bg-white/[0.02] flex items-center justify-center overflow-hidden relative">
          <?php if (!empty($p['image'])): ?>
            <img src="uploads/<?= htmlspecialchars($p['image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image text-3xl text-gray-200 dark:text-gray-700\'></i>'">
          <?php else: ?>
            <i class="fa-solid fa-image text-3xl text-gray-200 dark:text-gray-700"></i>
          <?php endif; ?>
          <?php if ($has_discount): ?>
            <span class="absolute top-3 left-3 bg-red-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-md uppercase">-<?= $discount_pct ?>%</span>
          <?php endif; ?>
          <span class="absolute top-3 right-3 text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md <?= $p_status === 'active' ? 'bg-emerald-500 text-white' : 'bg-gray-400 text-white' ?>"><?= ucfirst($p_status) ?></span>
        </div>
        <!-- Info -->
        <div class="p-4">
          <?php if (!empty($p['category_name'])): ?>
            <p class="text-[10px] font-semibold text-brand-500 dark:text-brand-400 uppercase tracking-wider mb-1"><?= htmlspecialchars($p['category_name']) ?></p>
          <?php endif; ?>
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1 line-clamp-2 leading-snug"><?= htmlspecialchars($prod_title) ?></h3>
          <?php if (!empty($p['description'])): ?>
            <p class="text-[10px] text-gray-400 line-clamp-2 mb-3 leading-relaxed"><?= htmlspecialchars($p['description']) ?></p>
          <?php else: ?>
            <div class="mb-3"></div>
          <?php endif; ?>
          <div class="flex items-end justify-between mb-3">
            <div>
              <?php if ($has_discount): ?>
                <p class="text-base font-extrabold text-brand-600 dark:text-brand-400">Rs. <?= number_format($disc_price, 0) ?></p>
                <p class="text-[10px] text-gray-400 line-through">Rs. <?= number_format($p['price'], 0) ?></p>
              <?php else: ?>
                <p class="text-base font-extrabold text-gray-900 dark:text-white">Rs. <?= number_format($p['price'], 0) ?></p>
              <?php endif; ?>
            </div>
            <p class="text-[9px] text-gray-300 dark:text-gray-600"><?= date('d M Y', strtotime($p['created_at'])) ?></p>
          </div>
          <div class="flex gap-2">
            <a href="product/edit.php?id=<?= $p['id'] ?>" class="flex-1 text-center py-2 bg-gray-50 dark:bg-white/[0.03] hover:bg-gray-100 dark:hover:bg-white/[0.06] text-gray-600 dark:text-white/60 rounded-lg text-[11px] font-semibold transition"><i class="fa-solid fa-pen text-[9px] mr-1"></i>Edit</a>
            <a href="view.php?toggle=<?= $p['id'] ?>" class="flex-1 text-center py-2 bg-gray-50 dark:bg-white/[0.03] hover:bg-gray-100 dark:hover:bg-white/[0.06] text-gray-600 dark:text-white/60 rounded-lg text-[11px] font-semibold transition"><i class="fa-solid fa-toggle-on text-[9px] mr-1"></i>Toggle</a>
            <a href="view.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')" class="py-2 px-3 bg-red-50 dark:bg-red-900/10 hover:bg-red-100 dark:hover:bg-red-900/20 text-red-500 rounded-lg text-[11px] font-semibold transition"><i class="fa-solid fa-trash text-[9px]"></i></a>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="fade-up text-center py-20 bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5">
      <i class="fa-solid fa-boxes-stacked text-4xl text-gray-200 dark:text-gray-700 mb-4"></i>
      <p class="text-sm text-gray-400 font-medium">No products found</p>
      <?php if ($search): ?>
        <p class="text-xs text-gray-300 mt-1">No results for "<?= htmlspecialchars($search) ?>"</p>
      <?php endif; ?>
      <a href="product/add.php" class="inline-block mt-3 px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition">Add First Product</a>
    </div>
    <?php endif; ?>

  </div>
</main>

<script>
function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>'}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
</script>
</body>
</html>