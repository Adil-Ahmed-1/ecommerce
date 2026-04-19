<?php
session_start();
include("../config/db.php");

/* ===== CHECK LOGIN ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* ===== FETCH LOGGED-IN USER ===== */
 $uid = $_SESSION['user_id'];
 $user_res = mysqli_query($conn, "SELECT name, email, role FROM users WHERE id = $uid");
 $user = mysqli_fetch_assoc($user_res);

 $user_name = $user['name'] ?? 'Unknown';
 $user_email = $user['email'] ?? '';
 $user_image = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true';
 $user_role = ucfirst($user['role'] ?? 'user');

/* ===== PRODUCT LOGIC ===== */
 $cat_query = "SELECT * FROM categories";
 $cat_result = mysqli_query($conn, $cat_query);

if (isset($_POST['add_product'])) {

    $name = trim($_POST['product_name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $desc = trim($_POST['description']);
    $category = intval($_POST['category_id']);

    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    if ($image && $tmp) {
        $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $unique_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $image);
            move_uploaded_file($tmp, "../uploads/" . $unique_name);
            $image = $unique_name;
        } else {
            $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
            $image = null;
        }
    } else {
        $error = "Please select a product image.";
        $image = null;
    }

    if (!isset($error) && !empty($name) && $category > 0) {
        $query = "INSERT INTO products (category_id, product_name, price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isiiss", $category, $name, $price, $stock, $desc, $image);
        mysqli_stmt_execute($stmt);
        
        // Naye product ki ID get karein
        $new_product_id = mysqli_insert_id($conn);

        // ===== SPECIFICATIONS SAVE KARNE KA LOGIC =====
        if ($new_product_id && isset($_POST['spec_name']) && is_array($_POST['spec_name'])) {
            $spec_names = $_POST['spec_name'];
            $spec_values = $_POST['spec_value'];
            
            $spec_stmt = mysqli_prepare($conn, "INSERT INTO product_specifications (product_id, spec_name, spec_value) VALUES (?, ?, ?)");
            
            foreach ($spec_names as $index => $s_name) {
                $s_value = $spec_values[$index] ?? '';
                // Sirf wahi rows save karein jahan dono (name aur value) empty na hon
                if (!empty(trim($s_name)) && !empty(trim($s_value))) {
                    mysqli_stmt_bind_param($spec_stmt, "iss", $new_product_id, $s_name, $s_value);
                    mysqli_stmt_execute($spec_stmt);
                }
            }
        }

        $success = "Product Added Successfully!";
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
    -webkit-backdrop-filter:blur(20px);
  }
  .dark .sidebar-glass { background:rgba(3,42,26,0.98); }

  .nav-link { position:relative; transition:all 0.25s cubic-bezier(.4,0,.2,1); }
  .nav-link::before {
    content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
    width:3px;height:0;border-radius:0 4px 4px 0;
    background:#3acd7e;transition:height 0.25s cubic-bezier(.4,0,.2,1);
  }
  .nav-link:hover::before,.nav-link.active::before { height:60%; }
  .nav-link.active { background:rgba(58,205,126,0.12); color:#3acd7e; }
  .nav-link:hover { background:rgba(255,255,255,0.06); }

  .topbar-line::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(58,205,126,0.25),transparent);
  }

  .form-input {
    transition:border-color 0.2s ease, box-shadow 0.2s ease;
  }
  .form-input:focus {
    border-color:#3acd7e;
    box-shadow:0 0 0 3px rgba(58,205,126,0.12);
    outline:none;
  }

  select.form-input {
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%239ca3af' viewBox='0 0 16 16'%3E%3Cpath d='M4.5 6l3.5 4 3.5-4z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 12px center;
    background-size:16px;
    padding-right:36px;
  }

  .btn-brand {
    background:linear-gradient(135deg,#16b364,#0a9150);
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;
  }
  .btn-brand::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);
    transition:left 0.5s ease;
  }
  .btn-brand:hover::before { left:100%; }
  .btn-brand:hover { transform:translateY(-1px); box-shadow:0 8px 24px -6px rgba(22,179,100,0.45); }
  .btn-brand:active { transform:translateY(0); }

  .fade-up {
    opacity:0;transform:translateY(16px);
    animation:fadeUp 0.5s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  .toast-slide { animation:toastIn 0.4s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes toastIn {
    from { opacity:0;transform:translateY(-12px) scale(0.96); }
    to { opacity:1;transform:translateY(0) scale(1); }
  }
  .toast-out { animation:toastOut 0.3s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes toastOut {
    to { opacity:0;transform:translateY(-12px) scale(0.96); }
  }

  .dropdown-enter { animation:dropIn 0.2s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes dropIn {
    from { opacity:0;transform:translateY(-8px) scale(0.96); }
    to { opacity:1;transform:translateY(0) scale(1); }
  }

  .field-group label {
    font-size:0.75rem;font-weight:600;text-transform:uppercase;
    letter-spacing:0.05em;color:#9ca3af;
    margin-bottom:6px;display:block;
  }
  .dark .field-group label { color:#6b7280; }

  .dropzone {
    border:2px dashed #d1d5db;
    transition:all 0.25s ease;
    cursor:pointer;
  }
  .dark .dropzone { border-color:rgba(255,255,255,0.1); }
  .dropzone:hover, .dropzone.drag-over {
    border-color:#3acd7e;
    background:rgba(58,205,126,0.04);
  }
  .dark .dropzone:hover, .dark .dropzone.drag-over {
    background:rgba(58,205,126,0.06);
  }
  .dropzone.has-image {
    border-style:solid;
    border-color:#3acd7e;
  }

  .preview-img {
    max-height:160px;
    object-fit:contain;
    border-radius:12px;
  }

  .role-badge {
    font-size:9px;font-weight:700;letter-spacing:0.05em;
    text-transform:uppercase;padding:2px 7px;border-radius:6px;
  }
  .role-admin { background:rgba(58,205,126,0.15); color:#3acd7e; }
  .role-user { background:rgba(96,165,250,0.15); color:#60a5fa; }

  .sidebar-collapsed .sidebar-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-logo-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-avatar { width:36px; height:36px; }
  
  /* Specifications Table Styling */
  .spec-row { animation: fadeUp 0.3s ease forwards; }
</style>

</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- ========== SIDEBAR ========== -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm">
      <i class="fa-solid fa-bars text-xs"></i>
    </button>
  </div>

  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="<?= $user_image ?>" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
    <div class="sidebar-text transition-all duration-300">
      <div class="flex items-center gap-2">
        <p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p>
        <span class="role-badge <?= $user_role === 'Admin' ? 'role-admin' : 'role-user' ?>"><?= $user_role ?></span>
      </div>
      <p class="text-[11px] text-white/50 mt-0.5"><?= htmlspecialchars($user_email) ?></p>
    </div>
  </div>

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="../category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Category</span>
    </a>
    <a href="../category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">View Categories</span>
    </a>
    <a href="add.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Product</span>
    </a>
    <a href="view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">View Products</span>
    </a>
  </nav>

  <div class="px-3 pb-5">
    <div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300">
      <p class="text-[11px] text-white/40 mb-1">Storage Used</p>
      <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
        <div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div>
      </div>
      <p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p>
    </div>
  </div>
</aside>

<!-- ========== MAIN ========== -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-line sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Add Product</h1>
      <p class="text-xs text-gray-400 mt-0.5">Add a new product to your inventory</p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl dark:shadow-2xl rounded-2xl w-52 py-2 overflow-hidden dropdown-enter">
          <div class="px-4 py-3 border-b border-gray-100 dark:border-white/5">
            <div class="flex items-center gap-3">
              <img src="<?= $user_image ?>" class="w-10 h-10 rounded-xl object-cover" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=16b364&color=fff&bold=true'">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></p>
                <p class="text-[11px] text-gray-400"><?= htmlspecialchars($user_email) ?></p>
                <span class="role-badge mt-1 inline-block <?= $user_role === 'Admin' ? 'role-admin' : 'role-user' ?>"><?= $user_role ?></span>
              </div>
            </div>
          </div>
          <a href="../profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition">
            <i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile
          </a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1">
            <a href="../logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition">
              <i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">
    <!-- Success Toast -->
    <?php if(isset($success)) { ?>
      <div id="toast" class="toast-slide mb-6 flex items-center gap-3 bg-brand-50 dark:bg-brand-950 border border-brand-200 dark:border-brand-800 text-brand-700 dark:text-brand-300 rounded-2xl px-5 py-4">
        <div class="w-8 h-8 rounded-xl bg-brand-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-white text-sm"></i></div>
        <div class="flex-1">
          <p class="text-sm font-semibold"><?= $success ?></p>
          <p class="text-xs text-brand-500 dark:text-brand-400 mt-0.5">Your product has been saved to the inventory.</p>
        </div>
        <button onclick="dismissToast()" class="w-7 h-7 rounded-lg hover:bg-brand-100 dark:hover:bg-brand-900 flex items-center justify-center transition"><i class="fa-solid fa-xmark text-xs"></i></button>
      </div>
    <?php } ?>

    <!-- Error Toast -->
    <?php if(isset($error)) { ?>
      <div id="errorToast" class="toast-slide mb-6 flex items-center gap-3 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-2xl px-5 py-4">
        <div class="w-8 h-8 rounded-xl bg-red-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-xmark text-white text-sm"></i></div>
        <div class="flex-1"><p class="text-sm font-semibold"><?= $error ?></p></div>
        <button onclick="dismissErrorToast()" class="w-7 h-7 rounded-lg hover:bg-red-100 dark:hover:bg-red-900 flex items-center justify-center transition"><i class="fa-solid fa-xmark text-xs"></i></button>
      </div>
    <?php } ?>

    <!-- BREADCRUMB -->
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5 fade-up">
      <a href="dashboard.php" class="hover:text-brand-500 transition">Dashboard</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-gray-700"></i>
      <a href="view.php" class="hover:text-brand-500 transition">Products</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-gray-700"></i>
      <span class="text-gray-700 dark:text-white font-medium">Add New</span>
    </div>

    <!-- FORM CARD -->
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
      <div class="px-7 py-5 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950 flex items-center justify-center">
          <i class="fa-solid fa-box-open text-amber-500 text-sm"></i>
        </div>
        <div>
          <h2 class="text-base font-bold text-gray-900 dark:text-white">Create New Product</h2>
          <p class="text-xs text-gray-400 mt-0.5">Fill in the product details below</p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" id="productForm" class="p-7">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-x-8 gap-y-5">
          
          <!-- LEFT COLUMN -->
          <div class="lg:col-span-3 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
              <!-- Product Name -->
              <div class="field-group sm:col-span-1">
                <label for="prod_name">Product Name</label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-tag"></i></span>
                  <input type="text" id="prod_name" name="product_name" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white placeholder:text-gray-400" placeholder="e.g. Headphones" required>
                </div>
              </div>
              <!-- Price -->
              <div class="field-group">
                <label for="prod_price">Price (Rs.)</label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"><i class="fa-solid fa-dollar-sign"></i></span>
                  <input type="number" id="prod_price" name="price" step="0.01" min="0" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white placeholder:text-gray-400" placeholder="0.00" required>
                </div>
              </div>
              <!-- Stock -->
              <div class="field-group">
                <label for="prod_stock">Stock Qty</label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"><i class="fa-solid fa-warehouse"></i></span>
                  <input type="number" id="prod_stock" name="stock" min="0" value="0" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white placeholder:text-gray-400" placeholder="0" required>
                </div>
              </div>
            </div>

            <!-- Category -->
            <div class="field-group">
              <label for="prod_cat">Category</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-folder"></i></span>
                <select id="prod_cat" name="category_id" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white" required>
                  <option value="">Select a category</option>
                  <?php mysqli_data_seek($cat_result, 0); while($cat = mysqli_fetch_assoc($cat_result)) { ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>

            <!-- Description -->
            <div class="field-group">
              <label for="prod_desc">Description</label>
              <textarea id="prod_desc" name="description" rows="4" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 px-4 text-sm text-gray-800 dark:text-white placeholder:text-gray-400 resize-none" placeholder="Describe your product — features, specs, materials..."></textarea>
              <div class="flex justify-between mt-1.5">
                <p class="text-[11px] text-gray-400 flex items-center gap-1"><i class="fa-solid fa-circle-info text-[10px]"></i> Optional but recommended for SEO</p>
                <span id="charCount" class="text-[11px] text-gray-400">0 / 1000</span>
              </div>
            </div>

            <!-- ===== NEW SPECIFICATIONS SECTION ===== -->
            <div class="field-group">
              <div class="flex items-center justify-between mb-2">
                <label style="margin-bottom: 0;">Specifications</label>
                <button type="button" onclick="addSpecRow()" class="text-xs font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 flex items-center gap-1.5 transition">
                  <i class="fa-solid fa-plus text-[10px]"></i> Add Row
                </button>
              </div>
              
              <div class="border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden">
                <!-- Table Header -->
                <div class="grid grid-cols-12 bg-gray-100 dark:bg-white/[0.03] text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  <div class="col-span-5 px-4 py-2.5 border-r border-gray-200 dark:border-white/10">Spec Name</div>
                  <div class="col-span-5 px-4 py-2.5 border-r border-gray-200 dark:border-white/10">Spec Value</div>
                  <div class="col-span-2 px-4 py-2.5 text-center">Action</div>
                </div>
                
                <!-- Container for Dynamic Rows -->
                <div id="specContainer" class="divide-y divide-gray-100 dark:divide-white/5 max-h-[220px] overflow-y-auto">
                  <!-- Default empty state message -->
                  <div id="specEmptyState" class="py-6 text-center text-xs text-gray-400 dark:text-gray-600">
                    <i class="fa-solid fa-list-check text-lg mb-2 block opacity-50"></i>
                    No specifications added. Click "Add Row".
                  </div>
                </div>
              </div>
              <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                <i class="fa-solid fa-circle-info text-[10px]"></i>
                Optional. E.g., Weight: 500g, Color: Black
              </p>
            </div>

          </div>

          <!-- RIGHT COLUMN (Image Upload) -->
          <div class="lg:col-span-2">
            <div class="field-group">
              <label>Product Image</label>
              <div id="dropzone" class="dropzone rounded-2xl p-6 flex flex-col items-center justify-center text-center min-h-[240px] bg-gray-50 dark:bg-white/[0.02]" onclick="document.getElementById('fileInput').click()">
                <div id="dropDefault">
                  <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-300 dark:text-gray-700"></i>
                  </div>
                  <p class="text-sm font-semibold text-gray-600 dark:text-white/60">Drop your image here</p>
                  <p class="text-xs text-gray-400 mt-1">or click to browse</p>
                  <div class="flex items-center justify-center gap-3 mt-4">
                    <span class="text-[10px] font-medium text-gray-400 bg-gray-100 dark:bg-white/5 px-2.5 py-1 rounded-md">JPG</span>
                    <span class="text-[10px] font-medium text-gray-400 bg-gray-100 dark:bg-white/5 px-2.5 py-1 rounded-md">PNG</span>
                    <span class="text-[10px] font-medium text-gray-400 bg-gray-100 dark:bg-white/5 px-2.5 py-1 rounded-md">GIF</span>
                    <span class="text-[10px] font-medium text-gray-400 bg-gray-100 dark:bg-white/5 px-2.5 py-1 rounded-md">WEBP</span>
                  </div>
                </div>
                <div id="dropPreview" class="hidden w-full">
                  <img id="previewImg" src="" class="preview-img mx-auto mb-3" alt="Preview">
                  <p id="previewName" class="text-xs font-medium text-gray-500 dark:text-white/50 truncate max-w-[200px] mx-auto"></p>
                  <button type="button" onclick="removeImage(event)" class="mt-3 text-xs font-semibold text-red-500 hover:text-red-600 flex items-center gap-1.5 mx-auto transition">
                    <i class="fa-solid fa-trash-can text-[10px]"></i> Remove
                  </button>
                </div>
              </div>
              <input type="file" id="fileInput" name="image" accept="image/*" class="hidden" onchange="handleFile(this)">
              <p class="text-[11px] text-gray-400 mt-2 flex items-center gap-1">
                <i class="fa-solid fa-shield-halved text-[10px]"></i> Max 5 MB — uploaded securely
              </p>
            </div>
          </div>

        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100 dark:border-white/5">
          <button type="submit" name="add_product" class="btn-brand text-white font-semibold text-sm px-8 py-3 rounded-xl flex items-center gap-2">
            <i class="fa-solid fa-plus text-xs"></i> Add Product
          </button>
          <a href="view.php" class="text-sm text-gray-500 dark:text-white/40 hover:text-gray-700 dark:hover:text-white/70 font-medium px-5 py-3 rounded-xl border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/[0.03] transition">Cancel</a>
        </div>
      </form>
    </div>

    <!-- Helper Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 fade-up" style="animation-delay:0.15s">
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-image text-brand-500 text-xs"></i></div>
        <div>
          <p class="text-sm font-semibold text-gray-800 dark:text-white">Use Clear Images</p>
          <p class="text-xs text-gray-400 mt-1 leading-relaxed">High-resolution product photos boost customer trust.</p>
        </div>
      </div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-dollar-sign text-amber-500 text-xs"></i></div>
        <div>
          <p class="text-sm font-semibold text-gray-800 dark:text-white">Set Fair Prices</p>
          <p class="text-xs text-gray-400 mt-1 leading-relaxed">Compare with similar products before pricing.</p>
        </div>
      </div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-list-check text-sky-500 text-xs"></i></div>
        <div>
          <p class="text-sm font-semibold text-gray-800 dark:text-white">Add Specifications</p>
          <p class="text-xs text-gray-400 mt-1 leading-relaxed">Detailed specs help buyers make quick decisions.</p>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
// --- Previous Functions (Sidebar, Dark Mode, Image Upload, etc.) ---
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const main = document.getElementById('main');
  const collapsed = sidebar.classList.toggle('sidebar-collapsed');
  sidebar.style.width = collapsed ? '78px' : '260px';
  main.style.marginLeft = collapsed ? '78px' : '260px';
}

function toggleDark() {
  const html = document.documentElement;
  const body = document.body;
  const btn = document.getElementById('darkBtn');
  const isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);
  btn.innerHTML = isDark ? '<i class="fa-solid fa-sun text-sm"></i>' : '<i class="fa-solid fa-moon text-sm"></i>';
}

function toggleMenu() { document.getElementById('menu').classList.toggle('hidden'); }
document.addEventListener('click', function(e) {
  const menu = document.getElementById('menu');
  if (!e.target.closest('.relative') && !menu.classList.contains('hidden')) { menu.classList.add('hidden'); }
});

function dismissToast() {
  const t = document.getElementById('toast'); if (!t) return;
  t.classList.remove('toast-slide'); t.classList.add('toast-out');
  setTimeout(() => t.remove(), 300);
}
function dismissErrorToast() {
  const t = document.getElementById('errorToast'); if (!t) return;
  t.classList.remove('toast-slide'); t.classList.add('toast-out');
  setTimeout(() => t.remove(), 300);
}
<?php if(isset($success)) { ?> setTimeout(dismissToast, 5000); <?php } ?>
<?php if(isset($error)) { ?> setTimeout(dismissErrorToast, 6000); <?php } ?>

const textarea = document.getElementById('prod_desc');
const counter = document.getElementById('charCount');
if (textarea && counter) {
  textarea.addEventListener('input', function() {
    let len = this.value.length;
    if (len > 1000) { this.value = this.value.substring(0, 1000); len = 1000; }
    counter.textContent = len + ' / 1000';
    counter.style.color = len > 900 ? (len >= 1000 ? '#ef4444' : '#f59e0b') : '';
  });
}

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const dropDefault = document.getElementById('dropDefault');
const dropPreview = document.getElementById('dropPreview');
const previewImg = document.getElementById('previewImg');
const previewName = document.getElementById('previewName');

function handleFile(input) {
  const file = input.files[0]; if (!file) return;
  if (file.size > 5 * 1024 * 1024) { alert('Image must be under 5 MB.'); input.value = ''; return; }
  const valid = ['image/jpeg','image/png','image/gif','image/webp'];
  if (!valid.includes(file.type)) { alert('Only JPG, PNG, GIF, and WEBP are allowed.'); input.value = ''; return; }
  const reader = new FileReader();
  reader.onload = function(e) {
    previewImg.src = e.target.result; previewName.textContent = file.name;
    dropDefault.classList.add('hidden'); dropPreview.classList.remove('hidden'); dropzone.classList.add('has-image');
  };
  reader.readAsDataURL(file);
}

function removeImage(e) {
  e.stopPropagation(); fileInput.value = ''; previewImg.src = ''; previewName.textContent = '';
  dropDefault.classList.remove('hidden'); dropPreview.classList.add('hidden'); dropzone.classList.remove('has-image');
}

dropzone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
dropzone.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('drag-over'); });
dropzone.addEventListener('drop', function(e) {
  e.preventDefault(); this.classList.remove('drag-over');
  if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; handleFile(fileInput); }
});

document.getElementById('productForm').addEventListener('submit', function(e) {
  if (!fileInput.files.length) {
    e.preventDefault(); dropzone.style.animation = 'none'; dropzone.offsetHeight;
    dropzone.style.animation = 'shake 0.4s ease'; dropzone.style.borderColor = '#ef4444';
    setTimeout(() => { dropzone.style.borderColor = ''; dropzone.style.animation = ''; }, 1500);
  }
});

const shakeStyle = document.createElement('style');
shakeStyle.textContent = `@keyframes shake { 0%,100% { transform:translateX(0); } 20% { transform:translateX(-6px); } 40% { transform:translateX(6px); } 60% { transform:translateX(-4px); } 80% { transform:translateX(4px); } }`;
document.head.appendChild(shakeStyle);


// --- NEW DYNAMIC SPECIFICATIONS LOGIC ---
function addSpecRow() {
  const container = document.getElementById('specContainer');
  const emptyState = document.getElementById('specEmptyState');
  
  // Agar empty state show ho raha hai toh use hide karein
  if(emptyState) emptyState.style.display = 'none';

  // Naya row create karein
  const row = document.createElement('div');
  row.className = 'grid grid-cols-12 spec-row';
  
  row.innerHTML = `
    <div class="col-span-5 px-2 py-2">
      <input type="text" name="spec_name[]" placeholder="e.g. Weight" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-lg py-2 px-3 text-sm text-gray-800 dark:text-white placeholder:text-gray-400">
    </div>
    <div class="col-span-5 px-2 py-2 border-l border-gray-100 dark:border-white/5">
      <input type="text" name="spec_value[]" placeholder="e.g. 500g" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-lg py-2 px-3 text-sm text-gray-800 dark:text-white placeholder:text-gray-400">
    </div>
    <div class="col-span-2 px-2 py-2 border-l border-gray-100 dark:border-white/5 flex justify-center items-center">
      <button type="button" onclick="removeSpecRow(this)" class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 text-red-500 flex items-center justify-center transition">
        <i class="fa-solid fa-trash-can text-xs"></i>
      </button>
    </div>
  `;

  container.appendChild(row);
}

function removeSpecRow(btn) {
  const row = btn.closest('.grid.grid-cols-12');
  row.remove();
  
  // Agar sab rows delete ho jayein toh wapis empty state show karein
  const container = document.getElementById('specContainer');
  if(container.children.length === 0) {
    const emptyState = document.getElementById('specEmptyState');
    if(emptyState) emptyState.style.display = 'block';
  }
}
</script>

</body>
</html>