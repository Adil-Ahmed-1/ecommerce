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

function getProductStatus($p, $has_is_active, $has_status) {
    if ($has_is_active && isset($p['is_active'])) return $p['is_active'] == 1 ? 'active' : 'inactive';
    if ($has_status && isset($p['status'])) return $p['status'];
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
        if ($cur) { $new = $cur['is_active'] == 1 ? 0 : 1; mysqli_query($conn, "UPDATE products SET is_active = $new WHERE id = $tid"); $_SESSION['toast'] = ['type'=>'success','message'=>'Status updated to ' . ($new ? 'Active' : 'Inactive')]; }
    } elseif ($has_status) {
        $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM products WHERE id = $tid"));
        if ($cur) { $new = $cur['status'] === 'active' ? 'inactive' : 'active'; mysqli_query($conn, "UPDATE products SET status = '$new' WHERE id = $tid"); $_SESSION['toast'] = ['type'=>'success','message'=>'Status updated to ' . ucfirst($new)]; }
    }
    header("Location: view.php"); exit;
}

/* ===== CSV UPLOAD ===== */
 $csv_errors = [];
 $csv_success = 0;

if (isset($_POST['csv_upload']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $csv_errors[] = "File upload failed. Error code: " . $file['error'];
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $csv_errors[] = "Only .csv files are allowed.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $csv_errors[] = "File size must be under 5MB.";
        } else {
            if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
                $row_num = 0;
                $required = [$prod_name_col, 'category_id', 'price'];

                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $row_num++;
                    if ($row_num === 1) {
                        $headers = array_map('strtolower', array_map('trim', $data));
                        foreach ($required as $rh) {
                            if (!in_array($rh, $headers)) { $csv_errors[] = "Missing required column: '$rh'. Required: " . implode(', ', $required); break 2; }
                        }
                        continue;
                    }
                    if (empty(array_filter($data))) continue;

                    $row_assoc = [];
                    foreach ($headers as $idx => $header) { $row_assoc[$header] = trim($data[$idx] ?? ''); }

                    $pname = mysqli_real_escape_string($conn, $row_assoc[$prod_name_col] ?? '');
                    $cat_id = (int)($row_assoc['category_id'] ?? 0);
                    $price = (float)($row_assoc['price'] ?? 0);
                    $disc = $has_discount_col ? (float)($row_assoc['discount_price'] ?? 0) : 0;
                    $desc = mysqli_real_escape_string($conn, $row_assoc['description'] ?? '');
                    $status_csv = strtolower($row_assoc['status'] ?? 'active');
                    if (!in_array($status_csv, ['active','inactive'])) $status_csv = 'active';

                    if (empty($pname)) { $csv_errors[] = "Row $row_num: Product name empty, skipped."; continue; }
                    if ($cat_id < 1) { $csv_errors[] = "Row $row_num: Invalid category_id, skipped."; continue; }
                    if ($price <= 0) { $csv_errors[] = "Row $row_num: Price must be > 0, skipped."; continue; }

                    $cat_exist = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM categories WHERE id = $cat_id"));
                    if (!$cat_exist) { $csv_errors[] = "Row $row_num: Category ID $cat_id not found, skipped."; continue; }

                    $columns = "category_id, $prod_name_col, price, image, description, created_at";
                    $values = "$cat_id, '$pname', $price, '', '$desc', NOW()";
                    if ($has_discount_col) { $columns .= ", discount_price"; $values .= ", $disc"; }
                    if ($has_is_active) { $columns .= ", is_active"; $values .= ", " . ($status_csv === 'active' ? 1 : 0); }
                    elseif ($has_status) { $columns .= ", status"; $values .= ", '$status_csv'"; }

                    $ins = mysqli_query($conn, "INSERT INTO products ($columns) VALUES ($values)");
                    if ($ins) { $csv_success++; } else { $csv_errors[] = "Row $row_num: DB error — " . mysqli_error($conn); }
                }
                fclose($handle);
                if ($csv_success > 0 && empty($csv_errors)) {
                    $_SESSION['toast'] = ['type'=>'success','message'=>"$csv_success products imported successfully!"];
                    header("Location: view.php"); exit;
                }
            } else { $csv_errors[] = "Could not read the CSV file."; }
        }
    }
}

 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
 $cat_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
 $where = "1=1";
if ($search) $where .= " AND (p.$prod_name_col LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($cat_filter) $where .= " AND p.category_id = $cat_filter";
 $prods_res = mysqli_query($conn, "SELECT p.*, c.$cat_name_col AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.created_at DESC");
 $total_prods = mysqli_num_rows($prods_res);
 $all_cats = mysqli_query($conn, "SELECT id, $cat_name_col FROM categories WHERE status='active' ORDER BY $cat_name_col");

 $active_prods = 0; $inactive_prods = 0; $total_revenue = 0; $discounted_count = 0;
 $stats_res = mysqli_query($conn, "SELECT p.* FROM products p WHERE 1=1");
while ($sp = mysqli_fetch_assoc($stats_res)) {
    $st = getProductStatus($sp, $has_is_active, $has_status);
    if ($st === 'active') $active_prods++; else $inactive_prods++;
    $total_revenue += (float)$sp['price'];
    if ($has_discount_col && isset($sp['discount_price']) && (float)$sp['discount_price'] > 0 && (float)$sp['discount_price'] < (float)$sp['price']) $discounted_count++;
}

/* ===== Build sample CSV data for download ===== */
 $csv_headers = [$prod_name_col, 'category_id', 'price'];
if ($has_discount_col) $csv_headers[] = 'discount_price';
 $csv_headers[] = 'description';
 $csv_headers[] = 'status';

 $csv_sample_rows = [
    ['Wireless Bluetooth Headphones', '2', '2999', $has_discount_col ? '2499' : '', 'Premium sound quality with noise cancellation', 'active'],
    ['Cotton Casual T-Shirt', '1', '899', $has_discount_col ? '' : '', 'Soft breathable fabric, multiple colors', 'active'],
    ['Premium Notebook', '3', '149', $has_discount_col ? '' : '', '120 pages ruled, hardcover binding', 'inactive'],
];
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
  .modal-overlay{position:fixed;inset:0;z-index:100;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;animation:fadeIn .2s ease}
  .modal-box{animation:modalIn .3s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes modalIn{from{opacity:0;transform:scale(.92) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
  .drop-zone{border:2px dashed #d1d5db;transition:all .2s}.drop-zone.dragover{border-color:#16b364;background:rgba(22,179,100,0.04)}
  .dark .drop-zone{border-color:rgba(255,255,255,0.1)}.dark .drop-zone.dragover{border-color:#16b364;background:rgba(22,179,100,0.06)}
  .tbl-row{transition:background .15s ease}.tbl-row:hover{background:rgba(22,179,100,0.03)}.dark .tbl-row:hover{background:rgba(22,179,100,0.04)}
  .tbl-row td{border-bottom:1px solid #f3f4f6}.dark .tbl-row td{border-bottom-color:rgba(255,255,255,0.04)}
  .tbl-row:last-child td{border-bottom:none}
  .action-btn{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;transition:all .15s ease;font-size:12px}
  .action-btn:hover{transform:translateY(-1px)}
  .status-dot{width:7px;height:7px;border-radius:50%;display:inline-block}
  .stat-card{position:relative;overflow:hidden}
  .form-input{width:100%;padding:10px 14px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:13px;outline:none;transition:all .2s}
  .dark .form-input{background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.85)}
  .form-input:focus{border-color:#16b364;box-shadow:0 0 0 3px rgba(22,179,100,0.1)}
  .cat-select-wrap{position:relative}.cat-select-wrap::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:10px;color:#9ca3af;pointer-events:none}.dark .cat-select-wrap::after{color:rgba(255,255,255,0.2)}.cat-select-wrap .form-input{padding-right:36px;appearance:none;-webkit-appearance:none;cursor:pointer}
  .dl-btn{position:relative;overflow:hidden}.dl-btn::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,transparent 40%,rgba(255,255,255,0.1) 50%,transparent 60%);transform:translateX(-100%);transition:transform .6s}.dl-btn:hover::after{transform:translateX(100%)}
</style>
</head>
<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<?php if (isset($_SESSION['toast'])): ?>
  <div class="toast toast-<?= $_SESSION['toast']['type'] ?>"><i class="fa-solid fa-<?= $_SESSION['toast']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> mr-2"></i><?= htmlspecialchars($_SESSION['toast']['message']) ?></div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<?php if (!empty($csv_errors)): ?>
  <?php foreach ($csv_errors as $i => $err): ?>
    <div class="toast toast-error" style="top:<?php echo 20 + $i*60; ?>px"><i class="fa-solid fa-triangle-exclamation mr-2"></i><?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>
<?php endif; ?>
<?php if ($csv_success > 0 && !empty($csv_errors)): ?>
  <div class="toast toast-success" style="top:<?php echo 20 + count($csv_errors)*60; ?>px"><i class="fa-solid fa-check-circle mr-2"></i><?= $csv_success ?> products imported (with warnings)</div>
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
    <a href="../dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <?php if ($user_role === '1') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="../category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="../category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="view.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="../orders/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <a href="../payments/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span></a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div><h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Products</h1><p class="text-xs text-gray-400 mt-0.5">Manage your product catalog</p></div>
    <div class="flex items-center gap-3">
      <button onclick="openCsvModal()" class="px-5 py-2.5 bg-gray-900 dark:bg-white/10 hover:bg-gray-800 dark:hover:bg-white/15 text-white rounded-xl text-sm font-semibold transition"><i class="fa-solid fa-file-csv mr-2 text-xs"></i>Import CSV</button>
      <a href="add.php" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition"><i class="fa-solid fa-plus mr-2 text-xs"></i>Add New</a>
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
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">Total Products</p><p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1"><?= $total_prods ?></p></div><div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center"><i class="fa-solid fa-boxes-stacked text-brand-500"></i></div></div>
        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-gray-400"><i class="fa-solid fa-database text-[9px]"></i>All registered products</div>
      </div>
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5" style="animation-delay:.1s">
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">Active</p><p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1"><?= $active_prods ?></p></div><div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center"><i class="fa-solid fa-circle-check text-emerald-500"></i></div></div>
        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-emerald-500"><i class="fa-solid fa-arrow-up text-[9px]"></i><?= $total_prods > 0 ? round(($active_prods/$total_prods)*100) : 0 ?>% of total</div>
      </div>
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5" style="animation-delay:.15s">
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">On Discount</p><p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1"><?= $discounted_count ?></p></div><div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center"><i class="fa-solid fa-scissors text-amber-500"></i></div></div>
        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-amber-500"><i class="fa-solid fa-tag text-[9px]"></i>Products with discount</div>
      </div>
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5" style="animation-delay:.2s">
        <div class="flex items-center justify-between"><div><p class="text-[11px] font-semibold text-gray-400 dark:text-white/30 uppercase tracking-wider">Catalog Value</p><p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">Rs. <?= number_format($total_revenue, 0) ?></p></div><div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center"><i class="fa-solid fa-indian-rupee-sign text-blue-500"></i></div></div>
        <div class="mt-3 flex items-center gap-1.5 text-[11px] text-blue-500"><i class="fa-solid fa-chart-line text-[9px]"></i>Sum of all prices</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 mb-4" style="animation-delay:.25s">
      <form method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-4">
        <div class="flex-1 flex items-center bg-gray-50 dark:bg-white/[0.03] rounded-xl px-4 py-2.5 gap-2 border border-gray-100 dark:border-white/5">
          <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by product name or description..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400">
        </div>
        <div class="cat-select-wrap">
          <select name="category" class="form-input w-auto min-w-[170px]">
            <option value="">All Categories</option>
            <?php while ($c = mysqli_fetch_assoc($all_cats)): ?>
              <option value="<?= $c['id'] ?>" <?= $cat_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c[$cat_name_col]) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition"><i class="fa-solid fa-magnifying-glass mr-2 text-xs"></i>Search</button>
          <?php if ($search || $cat_filter): ?><a href="view.php" class="px-4 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/60 rounded-xl text-sm font-semibold transition">Clear</a><?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Table -->
    <?php if ($total_prods > 0): ?>
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden" style="animation-delay:.3s">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/5">
        <div class="flex items-center gap-3"><h2 class="text-sm font-bold text-gray-900 dark:text-white">All Products</h2><span class="text-[10px] font-bold bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400 px-2.5 py-0.5 rounded-md"><?= $total_prods ?></span></div>
        <div class="flex items-center gap-2 text-[11px] text-gray-400"><i class="fa-solid fa-arrow-down-wide-short text-[10px]"></i><span>Newest first</span></div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-50/80 dark:bg-white/[0.02]">
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider w-16">#</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Image</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider text-right">Price</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider text-center">Status</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider">Created</th>
              <th class="px-6 py-3 text-[10px] font-bold text-gray-400 dark:text-white/30 uppercase tracking-wider text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $counter = 0; while ($p = mysqli_fetch_assoc($prods_res)): $counter++;
              $prod_title = $p[$prod_name_col] ?? 'Untitled';
              $disc_price = ($has_discount_col && isset($p['discount_price'])) ? (float)$p['discount_price'] : 0;
              $has_disc = $disc_price > 0 && $disc_price < (float)$p['price'];
              $disc_pct = $has_disc ? round((($p['price'] - $disc_price) / $p['price']) * 100) : 0;
              $p_status = getProductStatus($p, $has_is_active, $has_status);
            ?>
            <tr class="tbl-row">
              <td class="px-6 py-4"><span class="text-xs font-bold text-gray-300 dark:text-white/10"><?= str_pad($counter, 2, '0', STR_PAD_LEFT) ?></span></td>
              <td class="px-6 py-4">
                <div class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-white/[0.03] overflow-hidden flex items-center justify-center shrink-0 border border-gray-100 dark:border-white/5">
                  <?php if (!empty($p['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($p['image']) ?>" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image text-gray-300 dark:text-gray-600 text-sm\'></i>'">
                  <?php else: ?>
                    <i class="fa-solid fa-image text-gray-300 dark:text-gray-600 text-sm"></i>
                  <?php endif; ?>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-start gap-2">
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-[220px]" title="<?= htmlspecialchars($prod_title) ?>"><?= htmlspecialchars($prod_title) ?></p>
                    <?php if (!empty($p['description'])): ?>
                      <p class="text-[10px] text-gray-400 truncate max-w-[200px] mt-0.5"><?= htmlspecialchars($p['description']) ?></p>
                    <?php endif; ?>
                  </div>
                  <?php if ($has_disc): ?><span class="shrink-0 text-[9px] font-bold bg-red-50 text-red-500 dark:bg-red-900/15 dark:text-red-400 px-1.5 py-0.5 rounded-md">-<?= $disc_pct ?>%</span><?php endif; ?>
                </div>
              </td>
              <td class="px-6 py-4">
                <?php if (!empty($p['category_name'])): ?>
                  <span class="text-xs font-semibold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/15 px-2.5 py-1 rounded-lg"><?= htmlspecialchars($p['category_name']) ?></span>
                <?php else: ?><span class="text-xs text-gray-400">—</span><?php endif; ?>
              </td>
              <td class="px-6 py-4 text-right">
                <?php if ($has_disc): ?>
                  <p class="text-sm font-extrabold text-brand-600 dark:text-brand-400">Rs. <?= number_format($disc_price, 0) ?></p>
                  <p class="text-[10px] text-gray-400 line-through">Rs. <?= number_format($p['price'], 0) ?></p>
                <?php else: ?>
                  <p class="text-sm font-bold text-gray-900 dark:text-white">Rs. <?= number_format($p['price'], 0) ?></p>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 text-center">
                <?php if ($p_status === 'active'): ?>
                  <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/15 px-3 py-1 rounded-full"><span class="status-dot bg-emerald-500"></span>Active</span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-gray-500 bg-gray-100 dark:bg-white/5 px-3 py-1 rounded-full"><span class="status-dot bg-gray-400"></span>Inactive</span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4">
                <p class="text-xs text-gray-500 dark:text-white/40"><?= date('M d, Y', strtotime($p['created_at'])) ?></p>
                <p class="text-[10px] text-gray-400 dark:text-white/20"><?= date('h:i A', strtotime($p['created_at'])) ?></p>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-1.5">
                  <a href="edit.php?id=<?= (int)$p['id'] ?>" class="action-btn bg-blue-50 dark:bg-blue-900/10 text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/20" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                  <a href="view.php?toggle=<?= (int)$p['id'] ?>" class="action-btn bg-amber-50 dark:bg-amber-900/10 text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900/20" title="Toggle"><i class="fa-solid fa-toggle-on"></i></a>
                  <a href="view.php?delete=<?= (int)$p['id'] ?>" onclick="return confirm('Delete this product?')" class="action-btn bg-red-50 dark:bg-red-900/10 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20" title="Delete"><i class="fa-solid fa-trash-can"></i></a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.01]">
        <p class="text-[11px] text-gray-400">Showing <span class="font-semibold text-gray-600 dark:text-white/50"><?= $total_prods ?></span> products</p>
        <div class="flex items-center gap-1 text-[11px] text-gray-400"><i class="fa-solid fa-circle-info text-[9px]"></i><span>Edit to modify · Toggle to change status</span></div>
      </div>
    </div>
    <?php else: ?>
    <div class="fade-up text-center py-20 bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5" style="animation-delay:.3s">
      <div class="w-20 h-20 rounded-3xl bg-gray-100 dark:bg-white/[0.03] flex items-center justify-center mx-auto mb-5"><i class="fa-solid fa-boxes-stacked text-3xl text-gray-200 dark:text-gray-700"></i></div>
      <p class="text-base font-bold text-gray-900 dark:text-white mb-1">No products found</p>
      <p class="text-sm text-gray-400 mb-5"><?= $search ? 'Try adjusting your search query' : 'Get started by adding your first product' ?></p>
      <?php if ($search): ?>
        <a href="view.php" class="inline-block px-6 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/60 rounded-xl text-sm font-semibold transition mr-3">Clear Search</a>
      <?php endif; ?>
      <a href="add.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition"><i class="fa-solid fa-plus mr-2 text-xs"></i>Add Product</a>
    </div>
    <?php endif; ?>
  </div>
</main>

<!-- CSV MODAL -->
<div id="csvModal" class="hidden">
  <div class="modal-overlay" onclick="closeCsvModal(event)">
    <div class="modal-box bg-white dark:bg-[#131a16] rounded-3xl shadow-2xl border border-gray-100 dark:border-white/5 w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">

      <!-- Header -->
      <div class="px-7 pt-7 pb-0 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">Import Products</h2>
          <p class="text-xs text-gray-400 mt-1">Bulk import products via CSV file</p>
        </div>
        <button onclick="closeCsvModal()" class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-400"><i class="fa-solid fa-xmark text-sm"></i></button>
      </div>

      <form method="POST" enctype="multipart/form-data" id="csvForm" class="p-7">

        <!-- Download Sample Button -->
        <button type="button" onclick="downloadSampleCSV()" class="dl-btn w-full flex items-center justify-between gap-3 p-4 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white rounded-2xl mb-5 transition group shadow-lg shadow-brand-500/15">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center"><i class="fa-solid fa-download text-base"></i></div>
            <div class="text-left">
              <p class="text-sm font-bold">Download Sample CSV</p>
              <p class="text-[10px] text-white/60 mt-0.5">Pre-formatted template with example data — edit & upload</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-semibold bg-white/15 px-2.5 py-1 rounded-lg">.csv</span>
            <i class="fa-solid fa-arrow-right text-xs opacity-50 group-hover:translate-x-0.5 transition-transform"></i>
          </div>
        </button>

        <!-- Upload Zone -->
        <div id="dropZone" class="drop-zone rounded-2xl p-8 text-center cursor-pointer mb-5" onclick="document.getElementById('csvFileInput').click()">
          <input type="file" name="csv_file" id="csvFileInput" accept=".csv" class="hidden" onchange="handleFileSelect(this)">
          <div id="dropContent">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center mx-auto mb-4"><i class="fa-solid fa-cloud-arrow-up text-xl text-brand-500"></i></div>
            <p class="text-sm font-semibold text-gray-700 dark:text-white/80">Drop your CSV file here</p>
            <p class="text-xs text-gray-400 mt-1">or <span class="text-brand-500 font-semibold underline underline-offset-2">browse files</span></p>
            <p class="text-[10px] text-gray-400 mt-3">Max 5MB · .csv only</p>
          </div>
          <div id="filePreview" class="hidden">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mx-auto mb-4"><i class="fa-solid fa-file-csv text-xl text-emerald-500"></i></div>
            <p id="fileName" class="text-sm font-semibold text-gray-700 dark:text-white/80"></p>
            <p id="fileSize" class="text-xs text-gray-400 mt-1"></p>
            <button type="button" onclick="event.stopPropagation(); clearFile()" class="text-[11px] text-red-500 font-semibold mt-2 hover:underline">Remove file</button>
          </div>
        </div>

        <!-- Format Info -->
        <div class="bg-gray-50 dark:bg-white/[0.03] rounded-xl p-4 mb-5">
          <p class="text-[11px] font-bold text-gray-500 dark:text-white/40 uppercase tracking-wider mb-2"><i class="fa-solid fa-circle-info mr-1"></i>Expected Columns</p>
          <div class="bg-white dark:bg-[#0d1410] rounded-lg border border-gray-200 dark:border-white/5 p-3 font-mono text-[11px] text-gray-600 dark:text-white/50 leading-relaxed overflow-x-auto">
            <span class="text-brand-500"><?= htmlspecialchars($prod_name_col) ?></span>,<span class="text-brand-500">category_id</span>,<span class="text-brand-500">price</span><?php if ($has_discount_col): ?><span class="text-gray-400">,discount_price</span><?php endif; ?><span class="text-gray-400">,description,status</span>
          </div>
          <div class="mt-3 space-y-1">
            <p class="text-[10px] text-gray-400"><span class="font-semibold text-gray-500">Required:</span> <?= htmlspecialchars($prod_name_col) ?>, category_id, price</p>
            <p class="text-[10px] text-gray-400"><span class="font-semibold text-gray-500">Optional:</span> <?= $has_discount_col ? 'discount_price, ' : '' ?>description, status</p>
            <p class="text-[10px] text-gray-400"><span class="font-semibold text-amber-500">Note:</span> category_id must be an existing category ID number</p>
          </div>
        </div>

        <!-- Warning -->
        <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200/60 dark:border-amber-900/20 rounded-xl p-4 mb-5">
          <p class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Important</p>
          <ul class="space-y-1.5 text-[10px] text-amber-600 dark:text-amber-400/70">
            <li class="flex items-start gap-1.5"><i class="fa-solid fa-circle text-[4px] mt-1.5 shrink-0"></i>Images cannot be imported — add them later via Edit</li>
            <li class="flex items-start gap-1.5"><i class="fa-solid fa-circle text-[4px] mt-1.5 shrink-0"></i>Invalid rows are skipped with error details</li>
            <li class="flex items-start gap-1.5"><i class="fa-solid fa-circle text-[4px] mt-1.5 shrink-0"></i>Use numeric category IDs, not category names</li>
          </ul>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button type="button" onclick="closeCsvModal()" class="flex-1 py-3 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/60 rounded-xl text-sm font-semibold transition">Cancel</button>
          <button type="submit" name="csv_upload" value="1" id="csvSubmitBtn" class="flex-1 py-3 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <i class="fa-solid fa-file-import text-xs"></i>Import CSV
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/* ===== CSV Sample Data (from PHP) ===== */
const csvHeaders = <?= json_encode($csv_headers) ?>;
const csvSampleRows = <?= json_encode($csv_sample_rows) ?>;

function downloadSampleCSV(){
  let csv = csvHeaders.join(',') + '\n';
  csvSampleRows.forEach(row => {
    csv += row.map(cell => {
      /* Wrap in quotes if contains comma, quote, or newline */
      if(cell && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))){
        return '"' + cell.replace(/"/g, '""') + '"';
      }
      return cell;
    }).join(',') + '\n';
  });

  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'products_sample.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);

  /* Flash feedback on button */
  showToast('Sample CSV downloaded!', 'success');
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

function openCsvModal(){document.getElementById('csvModal').classList.remove('hidden');document.body.style.overflow='hidden'}
function closeCsvModal(e){if(e&&e.target!==e.currentTarget)return;document.getElementById('csvModal').classList.add('hidden');document.body.style.overflow='';clearFile()}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeCsvModal()});

function handleFileSelect(input){
  const file=input.files[0];if(!file)return;
  if(!file.name.toLowerCase().endsWith('.csv')){showInlineMsg('Only .csv files are allowed','error');input.value='';return}
  if(file.size>5*1024*1024){showInlineMsg('File must be under 5MB','error');input.value='';return}
  document.getElementById('dropContent').classList.add('hidden');
  document.getElementById('filePreview').classList.remove('hidden');
  document.getElementById('fileName').textContent=file.name;
  document.getElementById('fileSize').textContent=formatSize(file.size);
  document.getElementById('csvSubmitBtn').disabled=false;
  document.getElementById('dropZone').classList.add('border-brand-300','dark:border-brand-600');
  clearInlineMsg();
}
function clearFile(){
  document.getElementById('csvFileInput').value='';
  document.getElementById('dropContent').classList.remove('hidden');
  document.getElementById('filePreview').classList.add('hidden');
  document.getElementById('csvSubmitBtn').disabled=true;
  document.getElementById('dropZone').classList.remove('border-brand-300','dark:border-brand-600');
}
function formatSize(b){if(b<1024)return b+' B';if(b<1048576)return(b/1024).toFixed(1)+' KB';return(b/1048576).toFixed(1)+' MB'}

const dz=document.getElementById('dropZone');
['dragenter','dragover'].forEach(ev=>dz.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();dz.classList.add('dragover')}));
['dragleave','drop'].forEach(ev=>dz.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();dz.classList.remove('dragover')}));
dz.addEventListener('drop',function(e){
  const file=e.dataTransfer.files[0];if(file){
    const dt=new DataTransfer();dt.items.add(file);
    document.getElementById('csvFileInput').files=dt.files;
    handleFileSelect(document.getElementById('csvFileInput'));
  }
});

function showInlineMsg(msg,type){
  let el=document.getElementById('inlineMsg');if(!el){el=document.createElement('div');el.id='inlineMsg';el.className='text-[11px] font-semibold px-3 py-2 rounded-lg mb-4';dz.after(el)}
  el.className='text-[11px] font-semibold px-3 py-2 rounded-lg mb-4 '+(type==='error'?'bg-red-50 text-red-500 dark:bg-red-900/20':'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20');
  el.textContent=msg;
}
function clearInlineMsg(){const el=document.getElementById('inlineMsg');if(el)el.remove()}
</script>
</body>
</html>