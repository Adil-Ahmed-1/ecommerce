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

/* ✅ Detect if discount_price column exists */
 $col_check = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'discount_price'");
 $has_discount = (mysqli_num_rows($col_check) > 0);

/* ✅ Detect if is_active column exists */
 $active_check = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'is_active'");
 $has_is_active = (mysqli_num_rows($active_check) > 0);

/* ✅ Detect if status column exists (old naming) */
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
    else {
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $image = time() . '_' . rand(1000,9999) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
            } else { $error = 'Invalid image format'; }
        }
        if (!$error) {
            /* ✅ Build query dynamically based on existing columns */
            $columns = "category_id, product_name, price, image, description, created_at";
            $values = "$category_id, '$name', $price, '$image', '$description', NOW()";
            
            if ($has_discount) {
                $columns .= ", discount_price";
                $values .= ", $discount_price";
            }
            
            if ($has_is_active) {
                $columns .= ", is_active";
                $values .= ", $is_active";
            } elseif ($has_status) {
                $columns .= ", status";
                $values .= ", '$status_val'";
            }
            
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
  .form-input{width:100%;padding:10px 14px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:13px;outline:none;transition:all .2s}
  .dark .form-input{background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.85)}
  .form-input:focus{border-color:#16b364;box-shadow:0 0 0 3px rgba(22,179,100,0.1)}
  .form-label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px}
  .dark .form-label{color:rgba(255,255,255,0.7)}
  textarea.form-input{resize:vertical;min-height:100px}
  .upload-zone{border:2px dashed #d1d5db;border-radius:16px;padding:32px;text-align:center;cursor:pointer;transition:all .2s}
  .dark .upload-zone{border-color:rgba(255,255,255,0.1)}
  .upload-zone:hover{border-color:#16b364;background:rgba(22,179,100,0.03)}
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
    <?php if ($user_role === 'Admin') { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="../category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="../category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="add.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="../orders/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <a href="../payments/payments.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span></a>
    <?php } ?>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
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

  <div class="px-8 py-6">
    <div class="fade-up max-w-2xl">
      <?php if ($error): ?>
        <div class="mb-5 flex items-center gap-2 px-4 py-3 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/20 rounded-xl text-sm text-red-600 dark:text-red-400 font-medium"><i class="fa-solid fa-circle-exclamation text-xs"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-6 space-y-5">
        <div>
          <label class="form-label">Product Name <span class="text-red-400">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="form-input" placeholder="e.g. Wireless Headphones" required>
        </div>
        <div>
          <label class="form-label">Category <span class="text-red-400">*</span></label>
          <select name="category_id" class="form-input" required>
            <option value="">Select category</option>
            <?php 
            mysqli_data_seek($cats_res, 0);
            while ($c = mysqli_fetch_assoc($cats_res)): ?>
              <option value="<?= $c['id'] ?>" <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Price (Rs.) <span class="text-red-400">*</span></label>
            <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" class="form-input" placeholder="0" required>
          </div>
          <?php if ($has_discount): ?>
          <div>
            <label class="form-label">Discount Price (Rs.)</label>
            <input type="number" name="discount_price" step="0.01" min="0" value="<?= htmlspecialchars($_POST['discount_price'] ?? '') ?>" class="form-input" placeholder="0">
          </div>
          <?php endif; ?>
        </div>
        <div>
          <label class="form-label">Image</label>
          <div class="upload-zone" id="uploadZone" onclick="document.getElementById('prodImage').click()">
            <input type="file" name="image" id="prodImage" accept="image/*" class="hidden" onchange="previewImage(this)">
            <div id="uploadPlaceholder">
              <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-300 dark:text-gray-600 mb-2"></i>
              <p class="text-xs text-gray-400 font-medium">Click to upload product image</p>
              <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-0.5">JPG, PNG, WebP, GIF</p>
            </div>
            <img id="imagePreview" src="" class="hidden max-h-40 rounded-xl mx-auto">
          </div>
        </div>
        <div>
          <label class="form-label">Description</label>
          <textarea name="description" class="form-input" placeholder="Product description..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="form-label">Status</label>
          <select name="status" class="form-input">
            <option value="active" <?= ($_POST['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-sm font-semibold transition"><i class="fa-solid fa-plus mr-2 text-xs"></i>Add Product</button>
          <a href="view.php" class="px-6 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-sm font-semibold transition">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,btn=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);btn.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>'}
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
function previewImage(input){const file=input.files[0];if(file){const reader=new FileReader();reader.onload=function(e){document.getElementById('imagePreview').src=e.target.result;document.getElementById('imagePreview').classList.remove('hidden');document.getElementById('uploadPlaceholder').classList.add('hidden')};reader.readAsDataURL(file)}}
</script>
</body>
</html>