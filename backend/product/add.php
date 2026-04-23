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

 $cats_res = mysqli_query($conn, "SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC");
 $error = '';

 $col_check = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'discount_price'");
 $has_discount = (mysqli_num_rows($col_check) > 0);

 $active_check = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'is_active'");
 $has_is_active = (mysqli_num_rows($active_check) > 0);

 $status_check = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'status'");
 $has_status = (mysqli_num_rows($status_check) > 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : 0;
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    
    $status_val = mysqli_real_escape_string($conn, $_POST['status']);
    $is_active = ($status_val === 'active') ? 1 : 0;

    if (empty($name)) $error = 'Product name is required';
    elseif ($category_id < 1) $error = 'Select a category';
    elseif ($price <= 0) $error = 'Price must be greater than 0';
    elseif ($has_discount && $discount_price > 0 && $discount_price >= $price) $error = 'Discount price must be less than regular price';
    else {
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                $image = time() . '_' . rand(1000,9999) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
            } else { $error = 'Invalid image format. Use jpg, png, webp, or gif'; }
        }
        if (!$error) {
            $columns = "category_id, product_name, price, image, description, created_at";
            $values = "$category_id, '$name', $price, '$image', '$description', NOW()";
            
            if ($has_discount) { $columns .= ", discount_price"; $values .= ", $discount_price"; }
            if ($has_is_active) { $columns .= ", is_active"; $values .= ", $is_active"; }
            elseif ($has_status) { $columns .= ", status"; $values .= ", '$status_val'"; }
            
            mysqli_query($conn, "INSERT INTO products ($columns) VALUES ($values)");
            $_SESSION['toast'] = ['type'=>'success','message'=>'Product added successfully'];
            header("Location: view.php"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product</title>
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

  .fi{width:100%;padding:11px 14px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;color:#1f2937;font-size:13px;font-weight:500;outline:none;transition:all .2s}
  .dark .fi{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.07);color:rgba(255,255,255,0.9)}
  .fi::placeholder{color:#b0b8c4;font-weight:400}
  .fi:focus{border-color:#16b364;box-shadow:0 0 0 3px rgba(22,179,100,0.08)}
  .fi:hover:not(:focus){border-color:#d1d5db}
  .dark .fi:hover:not(:focus){border-color:rgba(255,255,255,0.12)}
  textarea.fi{resize:vertical;min-height:110px;line-height:1.6}
  .fl{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px;letter-spacing:.01em}
  .dark .fl{color:rgba(255,255,255,0.65)}
  .fl .req{color:#ef4444;font-size:14px;line-height:0}
  .fl .opt{font-size:10px;font-weight:500;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em}
  .dark .fl .opt{color:rgba(255,255,255,0.25)}

  .upload-zone{border:2px dashed #e0e3e8;border-radius:16px;padding:28px;text-align:center;cursor:pointer;transition:all .25s;position:relative;overflow:hidden}
  .dark .upload-zone{border-color:rgba(255,255,255,0.08)}
  .upload-zone:hover{border-color:#16b364;background:rgba(22,179,100,0.02)}
  .upload-zone.dragover{border-color:#16b364;background:rgba(22,179,100,0.04);transform:scale(1.005)}
  .upload-zone.has-image{border-style:solid;border-color:#16b364;background:rgba(22,179,100,0.02)}
  .dark .upload-zone.has-image{border-color:rgba(22,179,100,0.4)}

  .status-pill{position:relative;cursor:pointer}
  .status-pill input{position:absolute;opacity:0;pointer-events:none}
  .status-pill .pill{display:flex;align-items:center;gap:8px;padding:10px 18px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;transition:all .2s;font-size:13px;font-weight:600;color:#6b7280}
  .dark .status-pill .pill{border-color:rgba(255,255,255,0.07);background:rgba(255,255,255,0.02);color:rgba(255,255,255,0.4)}
  .status-pill .pill:hover{border-color:#d1d5db}
  .dark .status-pill .pill:hover{border-color:rgba(255,255,255,0.12)}
  .status-pill input:checked+.pill{border-color:#16b364;background:rgba(22,179,100,0.06);color:#16b364}
  .dark .status-pill input:checked+.pill{border-color:rgba(22,179,100,0.5);background:rgba(22,179,100,0.08);color:#3acd7e}
  .status-pill input:checked+.pill .pill-dot{background:#16b364;box-shadow:0 0 8px rgba(22,179,100,0.4)}
  .pill-dot{width:8px;height:8px;border-radius:50%;background:#d1d5db;transition:all .2s}
  .dark .pill-dot{background:rgba(255,255,255,0.15)}
  .status-pill input:focus-visible+.pill{box-shadow:0 0 0 3px rgba(22,179,100,0.12)}

  .section-line{height:1px;background:linear-gradient(90deg,transparent,#e5e7eb 20%,#e5e7eb 80%,transparent)}
  .dark .section-line{background:linear-gradient(90deg,transparent,rgba(255,255,255,0.05) 20%,rgba(255,255,255,0.05) 80%,transparent)}

  .sticky-actions{position:sticky;bottom:0;background:linear-gradient(to top,#f4f6f8 60%,transparent);padding:20px 0 0}
  .dark .sticky-actions{background:linear-gradient(to top,#0a0f0d 60%,transparent)}

  .hero-banner{position:relative;overflow:hidden;background:linear-gradient(135deg,#084b2e 0%,#032a1a 50%,#084b2e 100%);border-radius:20px}
  .hero-banner::before{content:'';position:absolute;top:-40%;right:-20%;width:300px;height:300px;border-radius:50%;background:rgba(22,179,100,0.08);filter:blur(60px)}
  .hero-banner::after{content:'';position:absolute;bottom:-30%;left:-10%;width:250px;height:250px;border-radius:50%;background:rgba(203,205,58,0.06);filter:blur(50px)}
  .hero-grid{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.03) 1px,transparent 1px);background-size:24px 24px}

  .remove-img{position:absolute;top:8px;right:8px;width:28px;height:28px;border-radius:8px;background:rgba(0,0,0,0.6);backdrop-filter:blur(8px);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;font-size:11px;opacity:0;transform:scale(.8)}
  .upload-zone:hover .remove-img{opacity:1;transform:scale(1)}
  .remove-img:hover{background:rgba(239,68,68,0.8)}

  .discount-badge{display:none;align-items:center;gap:6px;padding:6px 12px;border-radius:10px;font-size:11px;font-weight:700;animation:fadeUp .3s ease forwards}
  .discount-badge.show{display:inline-flex}
  .discount-badge.good{background:rgba(16,185,129,0.08);color:#10b981}
  .discount-badge.great{background:rgba(22,179,100,0.1);color:#16b364}
  .discount-badge.hot{background:rgba(245,158,11,0.08);color:#f59e0b}

  .price-input-wrap{position:relative}
  .price-prefix{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:13px;font-weight:700;color:#9ca3af;pointer-events:none}
  .dark .price-prefix{color:rgba(255,255,255,0.3)}
  .price-input-wrap .fi{padding-left:36px}

  .cat-select-wrap{position:relative}
  .cat-select-wrap::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:10px;color:#9ca3af;pointer-events:none}
  .dark .cat-select-wrap::after{color:rgba(255,255,255,0.2)}
  .cat-select-wrap .fi{padding-right:36px;appearance:none;-webkit-appearance:none;cursor:pointer}

  .char-ct{font-size:10px;font-weight:600;color:#c4c9d1;transition:color .2s;font-variant-numeric:tabular-nums}
  .char-ct.warn{color:#f59e0b}.char-ct.danger{color:#ef4444}
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
    <a href="../dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <?php if ($user_role === '1') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="../category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="../category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="add.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="../orders/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <a href="../payments/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span></a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300 flex flex-col">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center shrink-0">
    <div class="flex items-center gap-3">
      <a href="view.php" class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-500 dark:text-white/60"><i class="fa-solid fa-arrow-left text-xs"></i></a>
      <div><h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Add Product</h1><p class="text-xs text-gray-400 mt-0.5">Create a new product listing</p></div>
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
          <a href="../profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile</a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1"><a href="../logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition"><i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout</a></div>
        </div>
      </div>
    </div>
  </header>

  <div class="flex-1 px-8 py-6 pb-2">

    <!-- Hero Banner -->
    <div class="fade-up hero-banner p-7 mb-6" style="animation-delay:.05s">
      <div class="hero-grid"></div>
      <div class="relative z-10 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-box-open text-2xl text-brand-300"></i>
        </div>
        <div>
          <h2 class="text-lg font-bold text-white tracking-tight">New Product</h2>
          <p class="text-xs text-white/40 mt-1 max-w-lg leading-relaxed">Add a product to your catalog. Fill in the details, set pricing, upload an image, and choose visibility status.</p>
        </div>
      </div>
      <div class="relative z-10 flex items-center gap-2 mt-5 ml-[76px]">
        <div class="flex items-center gap-2 text-[11px] font-semibold">
          <span class="w-5 h-5 rounded-md bg-brand-400 text-brand-950 flex items-center justify-center text-[10px] font-extrabold">1</span>
          <span class="text-white/80">Details</span>
        </div>
        <div class="w-8 h-px bg-white/10"></div>
        <div class="flex items-center gap-2 text-[11px] font-semibold">
          <span class="w-5 h-5 rounded-md bg-brand-400 text-brand-950 flex items-center justify-center text-[10px] font-extrabold">2</span>
          <span class="text-white/80">Pricing</span>
        </div>
        <div class="w-8 h-px bg-white/10"></div>
        <div class="flex items-center gap-2 text-[11px] font-semibold">
          <span class="w-5 h-5 rounded-md bg-white/10 text-white/40 flex items-center justify-center text-[10px] font-extrabold">3</span>
          <span class="text-white/30">Media & Status</span>
        </div>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="fade-up mb-5 flex items-center gap-3 px-5 py-3.5 bg-red-50 dark:bg-red-900/10 border border-red-200/60 dark:border-red-900/20 rounded-2xl text-sm text-red-600 dark:text-red-400 font-semibold" style="animation-delay:.08s">
        <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/20 flex items-center justify-center shrink-0"><i class="fa-solid fa-circle-exclamation text-xs text-red-500"></i></div>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="prodForm">

      <!-- SECTION 1: Product Details -->
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden mb-5" style="animation-delay:.1s">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/15 flex items-center justify-center"><i class="fa-solid fa-pen-nib text-xs text-brand-500"></i></div>
          <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Product Details</h3>
            <p class="text-[10px] text-gray-400 mt-0.5">Basic information about the product</p>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div>
              <label class="fl"><i class="fa-solid fa-tag text-[10px] text-gray-400"></i>Product Name <span class="req">*</span></label>
              <input type="text" name="name" id="prodName" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="fi" placeholder="e.g. Wireless Bluetooth Headphones" required>
            </div>
            <div>
              <label class="fl"><i class="fa-solid fa-folder text-[10px] text-gray-400"></i>Category <span class="req">*</span></label>
              <div class="cat-select-wrap">
                <select name="category_id" id="catSelect" class="fi" required>
                  <option value="">Select a category...</option>
                  <?php 
                  mysqli_data_seek($cats_res, 0);
                  while ($c = mysqli_fetch_assoc($cats_res)): ?>
                    <option value="<?= $c['id'] ?>" <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <p class="text-[10px] text-gray-400 mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-info text-[8px]"></i>Only active categories are shown</p>
            </div>
          </div>
          <!-- Description -->
          <div class="mt-5">
            <label class="fl"><i class="fa-solid fa-align-left text-[10px] text-gray-400"></i>Description <span class="opt">optional</span></label>
            <textarea name="description" id="prodDesc" class="fi" placeholder="Describe the product features, specifications, and details..." maxlength="1000" oninput="updateCharCount()"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            <div class="flex items-center justify-between mt-1.5">
              <p class="text-[10px] text-gray-400 flex items-center gap-1"><i class="fa-solid fa-lightbulb text-[8px] text-amber-400"></i>Good descriptions help customers make decisions</p>
              <span id="charCount" class="char-ct">0 / 1000</span>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 2: Pricing -->
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden mb-5" style="animation-delay:.16s">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/15 flex items-center justify-center"><i class="fa-solid fa-indian-rupee-sign text-xs text-emerald-500"></i></div>
            <div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Pricing</h3>
              <p class="text-[10px] text-gray-400 mt-0.5">Set the selling price of this product</p>
            </div>
          </div>
          <div id="discountBadge" class="discount-badge">
            <i class="fa-solid fa-percent text-[10px]"></i>
            <span id="discountText"></span>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 <?= $has_discount ? 'lg:grid-cols-2' : '' ?> gap-5">
            <div>
              <label class="fl"><i class="fa-solid fa-indian-rupee-sign text-[10px] text-gray-400"></i>Regular Price <span class="req">*</span></label>
              <div class="price-input-wrap">
                <span class="price-prefix">Rs.</span>
                <input type="number" name="price" id="priceInput" step="0.01" min="0" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" class="fi" placeholder="0.00" required oninput="calcDiscount()">
              </div>
              <p class="text-[10px] text-gray-400 mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-info text-[8px]"></i>Must be greater than 0</p>
            </div>
            <?php if ($has_discount): ?>
            <div>
              <label class="fl"><i class="fa-solid fa-scissors text-[10px] text-gray-400"></i>Discount Price <span class="opt">optional</span></label>
              <div class="price-input-wrap">
                <span class="price-prefix">Rs.</span>
                <input type="number" name="discount_price" id="discInput" step="0.01" min="0" value="<?= htmlspecialchars($_POST['discount_price'] ?? '') ?>" class="fi" placeholder="0.00" oninput="calcDiscount()">
              </div>
              <p class="text-[10px] text-gray-400 mt-1.5 flex items-center gap-1"><i class="fa-solid fa-circle-info text-[8px]"></i>Must be less than regular price</p>
            </div>
            <?php endif; ?>
          </div>
          <?php if ($has_discount): ?>
          <!-- Price Comparison Visual -->
          <div id="priceCompare" class="hidden mt-5 p-4 bg-gray-50 dark:bg-white/[0.02] rounded-xl border border-gray-100 dark:border-white/5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Price Comparison</p>
                <div class="flex items-center gap-4">
                  <div>
                    <p class="text-[10px] text-gray-400">Regular</p>
                    <p id="compareRegular" class="text-sm font-bold text-gray-400 line-through">Rs. 0</p>
                  </div>
                  <i class="fa-solid fa-arrow-right text-[10px] text-gray-300 dark:text-gray-600"></i>
                  <div>
                    <p class="text-[10px] text-gray-400">After Discount</p>
                    <p id="compareDiscount" class="text-sm font-extrabold text-brand-600 dark:text-brand-400">Rs. 0</p>
                  </div>
                </div>
              </div>
              <div class="text-right">
                <p class="text-[10px] text-gray-400">Customer Saves</p>
                <p id="compareSave" class="text-lg font-extrabold text-emerald-500">Rs. 0</p>
              </div>
            </div>
            <!-- Mini bar -->
            <div class="mt-3 w-full h-2 bg-gray-200 dark:bg-white/5 rounded-full overflow-hidden">
              <div id="saveBar" class="h-full bg-gradient-to-r from-brand-400 to-emerald-400 rounded-full transition-all duration-500" style="width:0%"></div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- SECTION 3: Image & Status -->
      <div class="fade-up grid grid-cols-1 lg:grid-cols-5 gap-5 mb-5" style="animation-delay:.22s">
        <!-- Image -->
        <div class="lg:col-span-3 bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/15 flex items-center justify-center"><i class="fa-solid fa-image text-xs text-purple-500"></i></div>
            <div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Product Image</h3>
              <p class="text-[10px] text-gray-400 mt-0.5">Main photo for this product</p>
            </div>
          </div>
          <div class="p-6">
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('prodImage').click()">
              <input type="file" name="image" id="prodImage" accept="image/*" class="hidden" onchange="previewImage(this)">
              <div id="uploadPlaceholder">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 dark:bg-white/[0.03] flex items-center justify-center mx-auto mb-3">
                  <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-300 dark:text-gray-600"></i>
                </div>
                <p class="text-sm font-semibold text-gray-600 dark:text-white/50">Drop image here or click to browse</p>
                <p class="text-[11px] text-gray-400 mt-1">JPG, PNG, WebP, GIF &nbsp;·&nbsp; Max 2MB</p>
                <p class="text-[10px] text-gray-300 dark:text-gray-700 mt-2">Recommended: 800×800px, square ratio</p>
              </div>
              <div id="imagePreviewWrap" class="hidden relative">
                <img id="imagePreview" src="" class="max-h-52 rounded-xl mx-auto object-contain">
                <button type="button" class="remove-img" onclick="event.stopPropagation(); clearImage()"><i class="fa-solid fa-xmark"></i></button>
              </div>
            </div>
          </div>
        </div>

        <!-- Status -->
        <div class="lg:col-span-2 bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/15 flex items-center justify-center"><i class="fa-solid fa-toggle-on text-xs text-amber-500"></i></div>
            <div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Visibility</h3>
              <p class="text-[10px] text-gray-400 mt-0.5">Control product visibility</p>
            </div>
          </div>
          <div class="p-6 space-y-4">
            <label class="status-pill block">
              <input type="radio" name="status" value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'checked' : '' ?>>
              <div class="pill">
                <span class="pill-dot"></span>
                <div>
                  <p class="text-sm font-semibold">Active</p>
                  <p class="text-[10px] font-normal opacity-60 mt-0.5">Visible in store</p>
                </div>
                <i class="fa-solid fa-eye ml-auto text-xs opacity-40"></i>
              </div>
            </label>
            <label class="status-pill block">
              <input type="radio" name="status" value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'checked' : '' ?>>
              <div class="pill">
                <span class="pill-dot"></span>
                <div>
                  <p class="text-sm font-semibold">Inactive</p>
                  <p class="text-[10px] font-normal opacity-60 mt-0.5">Hidden from store</p>
                </div>
                <i class="fa-solid fa-eye-slash ml-auto text-xs opacity-40"></i>
              </div>
            </label>

            <div class="section-line my-4"></div>

            <div class="space-y-2.5">
              <div class="flex items-center gap-2.5 text-[11px] text-gray-400">
                <i class="fa-solid fa-shield-halved text-[10px] text-brand-400"></i>
                <span>Toggle anytime from view page</span>
              </div>
              <div class="flex items-center gap-2.5 text-[11px] text-gray-400">
                <i class="fa-solid fa-tag text-[10px] text-brand-400"></i>
                <span>Category stays linked</span>
              </div>
              <div class="flex items-center gap-2.5 text-[11px] text-gray-400">
                <i class="fa-solid fa-clock-rotate-left text-[10px] text-brand-400"></i>
                <span>No data lost on toggle</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- STICKY ACTIONS -->
      <div class="sticky-actions fade-up" style="animation-delay:.28s">
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 px-6 py-4 flex items-center justify-between">
          <a href="view.php" class="px-5 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/60 rounded-xl text-sm font-semibold transition flex items-center gap-2">
            <i class="fa-solid fa-xmark text-xs"></i>Discard
          </a>
          <div class="flex items-center gap-3">
            <p class="hidden sm:block text-[11px] text-gray-400"><i class="fa-solid fa-keyboard text-[9px] mr-1"></i>Ctrl + Enter to submit</p>
            <button type="submit" class="px-7 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-lg shadow-brand-500/20 hover:shadow-brand-500/30">
              <i class="fa-solid fa-box-open text-xs"></i>Add Product
            </button>
          </div>
        </div>
      </div>

    </form>
  </div>
</main>

<script>
function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>'}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});

function previewImage(input){
  const file=input.files[0];if(!file)return;
  if(file.size>2*1024*1024){showFormMsg('Image must be under 2MB','error');input.value='';return}
  const reader=new FileReader();
  reader.onload=function(e){
    document.getElementById('imagePreview').src=e.target.result;
    document.getElementById('imagePreviewWrap').classList.remove('hidden');
    document.getElementById('uploadPlaceholder').classList.add('hidden');
    document.getElementById('uploadZone').classList.add('has-image');
  };
  reader.readAsDataURL(file);
}

function clearImage(){
  document.getElementById('prodImage').value='';
  document.getElementById('imagePreview').src='';
  document.getElementById('imagePreviewWrap').classList.add('hidden');
  document.getElementById('uploadPlaceholder').classList.remove('hidden');
  document.getElementById('uploadZone').classList.remove('has-image');
}

function updateCharCount(){
  const t=document.getElementById('prodDesc'),c=document.getElementById('charCount'),len=t.value.length;
  c.textContent=len+' / 1000';
  c.className='char-ct'+(len>900?' danger':len>700?' warn':'');
}

<?php if ($has_discount): ?>
function calcDiscount(){
  const price=parseFloat(document.getElementById('priceInput').value)||0;
  const disc=parseFloat(document.getElementById('discInput').value)||0;
  const badge=document.getElementById('discountBadge');
  const compare=document.getElementById('priceCompare');

  if(price>0&&disc>0&&disc<price){
    const pct=Math.round(((price-disc)/price)*100);
    const save=price-disc;

    document.getElementById('discountText').textContent=pct+'% OFF';
    badge.className='discount-badge show '+(pct>=30?'hot':pct>=15?'great':'good');

    document.getElementById('compareRegular').textContent='Rs. '+price.toFixed(2);
    document.getElementById('compareDiscount').textContent='Rs. '+disc.toFixed(2);
    document.getElementById('compareSave').textContent='Rs. '+save.toFixed(2);
    document.getElementById('saveBar').style.width=Math.min(pct,100)+'%';
    compare.classList.remove('hidden');
  } else {
    badge.className='discount-badge';
    compare.classList.add('hidden');
  }
}
<?php endif; ?>

function showFormMsg(msg,type){
  let el=document.getElementById('formMsg');
  if(!el){el=document.createElement('div');el.id='formMsg';el.style.animation='fadeUp .3s ease forwards';document.getElementById('prodForm').prepend(el)}
  el.className='mb-5 flex items-center gap-3 px-5 py-3.5 rounded-2xl text-sm font-semibold '+(type==='error'?'bg-red-50 dark:bg-red-900/10 border border-red-200/60 dark:border-red-900/20 text-red-600 dark:text-red-400':'bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200/60 dark:border-emerald-900/20 text-emerald-600 dark:text-emerald-400');
  el.innerHTML='<div class="w-8 h-8 rounded-lg '+(type==='error'?'bg-red-100 dark:bg-red-900/20':'bg-emerald-100 dark:bg-emerald-900/20')+' flex items-center justify-center shrink-0"><i class="fa-solid fa-'+(type==='error'?'circle-exclamation':'check')+' text-xs '+(type==='error'?'text-red-500':'text-emerald-500')+'"></i></div>'+msg;
  setTimeout(()=>{if(el)el.remove()},4000);
}

/* Drag & Drop */
const uz=document.getElementById('uploadZone');
['dragenter','dragover'].forEach(ev=>uz.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();uz.classList.add('dragover')}));
['dragleave','drop'].forEach(ev=>uz.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();uz.classList.remove('dragover')}));
uz.addEventListener('drop',function(e){
  e.preventDefault();uz.classList.remove('dragover');
  if(e.dataTransfer.files.length){
    const dt=new DataTransfer();dt.items.add(e.dataTransfer.files[0]);
    document.getElementById('prodImage').files=dt.files;
    previewImage(document.getElementById('prodImage'));
  }
});

/* Init */
updateCharCount();
<?php if ($has_discount): ?>calcDiscount();<?php endif; ?>

/* Ctrl+Enter submit */
document.addEventListener('keydown',function(e){
  if((e.ctrlKey||e.metaKey)&&e.key==='Enter'){
    e.preventDefault();
    document.getElementById('prodForm').dispatchEvent(new Event('submit',{cancelable:true}));
  }
});
</script>
</body>
</html>