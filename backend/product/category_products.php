<?php
include("../config/db.php");

if (!isset($_GET['cat_id']) || !is_numeric($_GET['cat_id'])) {
    header("Location: ../category/view.php");
    exit;
}

 $cat_id = intval($_GET['cat_id']);

/* CATEGORY */
 $stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $cat_id);
mysqli_stmt_execute($stmt);
 $cat_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($cat_result) === 0) {
    header("Location: ../category/view.php");
    exit;
}
 $cat = mysqli_fetch_assoc($cat_result);

/* PRODUCTS */
 $stmt2 = mysqli_prepare($conn, "SELECT * FROM products WHERE category_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt2, "i", $cat_id);
mysqli_stmt_execute($stmt2);
 $product_result = mysqli_stmt_get_result($stmt2);
 $product_count = mysqli_num_rows($product_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($cat['category_name']) ?> — Products</title>

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

  .dropdown-enter { animation:dropIn 0.2s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes dropIn {
    from { opacity:0;transform:translateY(-8px) scale(0.96); }
    to { opacity:1;transform:translateY(0) scale(1); }
  }

  /* Product Card */
  .product-card {
    transition:transform 0.35s cubic-bezier(.4,0,.2,1), box-shadow 0.35s ease;
  }
  .product-card:hover {
    transform:translateY(-6px);
    box-shadow:0 20px 40px -12px rgba(0,0,0,0.12);
  }
  .dark .product-card:hover {
    box-shadow:0 20px 40px -12px rgba(0,0,0,0.5);
  }

  /* Image zoom on hover */
  .card-img-wrap {
    overflow:hidden;
  }
  .card-img-wrap img {
    transition:transform 0.5s cubic-bezier(.4,0,.2,1);
  }
  .product-card:hover .card-img-wrap img {
    transform:scale(1.08);
  }

  /* Overlay gradient on image */
  .card-img-wrap::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:50%;
    background:linear-gradient(to top, rgba(0,0,0,0.4), transparent);
    pointer-events:none;
    opacity:0;transition:opacity 0.35s ease;
  }
  .product-card:hover .card-img-wrap::after {
    opacity:1;
  }

  /* Quick-view button on hover */
  .quick-view {
    position:absolute;bottom:12px;left:50%;transform:translateX(-50%) translateY(10px);
    opacity:0;transition:all 0.3s cubic-bezier(.4,0,.2,1);
    pointer-events:none;
  }
  .product-card:hover .quick-view {
    opacity:1;transform:translateX(-50%) translateY(0);
    pointer-events:auto;
  }

  /* Cart button */
  .btn-cart {
    background:linear-gradient(135deg,#16b364,#0a9150);
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;
  }
  .btn-cart::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);
    transition:left 0.5s ease;
  }
  .btn-cart:hover::before { left:100%; }
  .btn-cart:hover { box-shadow:0 6px 20px -4px rgba(22,179,100,0.5); }

  /* Fade in */
  .fade-up {
    opacity:0;transform:translateY(16px);
    animation:fadeUp 0.5s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  /* Category badge pulse */
  .cat-badge {
    animation:badgePulse 3s ease-in-out infinite;
  }
  @keyframes badgePulse {
    0%,100% { box-shadow:0 0 0 0 rgba(58,205,126,0.3); }
    50% { box-shadow:0 0 0 8px rgba(58,205,126,0); }
  }

  /* Stat counter bar */
  .stat-bar {
    height:4px;border-radius:99px;overflow:hidden;
    background:rgba(0,0,0,0.06);
  }
  .dark .stat-bar { background:rgba(255,255,255,0.06); }
  .stat-bar-fill {
    height:100%;border-radius:99px;
    background:linear-gradient(90deg,#3acd7e,#16b364);
    transition:width 1s cubic-bezier(.4,0,.2,1);
  }
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
    <img src="../uploads/about.png" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <p class="text-sm font-semibold leading-tight">Adil Khoso</p>
      <p class="text-[11px] text-white/50 mt-0.5">Super Admin</p>
    </div>
  </div>

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="../index.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="../category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Category</span>
    </a>
    <a href="../category/view.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">View Categories</span>
    </a>
    <a href="../product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Product</span>
    </a>
    <a href="../product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
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

  <!-- TOPBAR -->
  <header class="topbar-line sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">

    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Category Products</h1>
      <p class="text-xs text-gray-400 mt-0.5">Browsing products in a specific category</p>
    </div>

    <div class="flex items-center gap-3">

      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>

      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="../uploads/about.png" class="w-8 h-8 rounded-lg object-cover">
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl dark:shadow-2xl rounded-2xl w-48 py-2 overflow-hidden dropdown-enter">
          <div class="px-4 py-2.5 border-b border-gray-100 dark:border-white/5">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Adil Khoso</p>
            <p class="text-[11px] text-gray-400">admin@example.com</p>
          </div>
          <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition">
            <i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile
          </a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1">
            <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition">
              <i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout
            </a>
          </div>
        </div>
      </div>

    </div>
  </header>

  <!-- CONTENT -->
  <div class="px-8 py-6">

    <!-- BREADCRUMB -->
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5 fade-up">
      <a href="../index.php" class="hover:text-brand-500 transition">Dashboard</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-gray-700"></i>
      <a href="../category/view.php" class="hover:text-brand-500 transition">Categories</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-gray-700"></i>
      <span class="text-gray-700 dark:text-white font-medium"><?= htmlspecialchars($cat['category_name']) ?></span>
    </div>

    <!-- CATEGORY HEADER CARD -->
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-6 mb-6">

      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">

        <div class="flex items-center gap-4">

          <div class="cat-badge w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-folder-open text-brand-500 text-xl"></i>
          </div>

          <div>
            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white"><?= htmlspecialchars($cat['category_name']) ?></h2>
            <p class="text-xs text-gray-400 mt-1 max-w-md leading-relaxed"><?= htmlspecialchars($cat['description'] ?? 'No description provided for this category.') ?></p>
          </div>

        </div>

        <div class="flex items-center gap-3 shrink-0">
          <a href="../category/view.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 dark:text-white/50 hover:text-gray-700 dark:hover:text-white/80 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/[0.03] transition">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Back
          </a>
          <a href="add.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-500/20">
            <i class="fa-solid fa-plus text-xs"></i>
            Add Product
          </a>
        </div>

      </div>

      <!-- Stats row -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-5 border-t border-gray-100 dark:border-white/5">

        <div>
          <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">Products</p>
          <p class="text-lg font-extrabold text-gray-900 dark:text-white mt-1"><?= $product_count ?></p>
        </div>

        <div>
          <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">Status</p>
          <p class="text-sm font-semibold mt-1.5 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full <?= ($cat['status'] ?? 'active') === 'active' ? 'bg-brand-400' : 'bg-red-400' ?>"></span>
            <span class="<?= ($cat['status'] ?? 'active') === 'active' ? 'text-brand-600 dark:text-brand-400' : 'text-red-600 dark:text-red-400' ?>">
              <?= ucfirst($cat['status'] ?? 'active') ?>
            </span>
          </p>
        </div>

        <div>
          <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">Parent</p>
          <p class="text-sm font-semibold text-gray-700 dark:text-white/70 mt-1.5">
            <?= !empty($cat['parent_id']) ? 'Sub-category' : 'Top-level' ?>
          </p>
        </div>

        <div>
          <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">Fill Rate</p>
          <div class="mt-2">
            <?php
              $fill = $product_count > 0 ? min(100, round(($product_count / max($product_count, 1)) * 100)) : 0;
              /* Show relative fill — if products exist, show based on a nice bar */
              $barWidth = $product_count > 0 ? min(100, $product_count * 10) : 0;
            ?>
            <div class="stat-bar">
              <div class="stat-bar-fill" style="width:<?= $barWidth ?>%"></div>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- SECTION HEADER -->
    <div class="flex items-center justify-between mb-5 fade-up" style="animation-delay:0.08s">
      <div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">All Products</h3>
        <p class="text-xs text-gray-400 mt-0.5"><?= $product_count ?> item<?= $product_count !== 1 ? 's' : '' ?> in this category</p>
      </div>

      <!-- Grid toggle -->
      <div class="flex items-center bg-gray-100 dark:bg-white/5 rounded-xl p-1 gap-1">
        <button id="gridBtn" onclick="setView('grid')" class="w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-white/10 text-gray-700 dark:text-white shadow-sm transition">
          <i class="fa-solid fa-grid-2 text-xs"></i>
        </button>
        <button id="listBtn" onclick="setView('list')" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-white/60 transition">
          <i class="fa-solid fa-list text-xs"></i>
        </button>
      </div>
    </div>

    <!-- PRODUCTS GRID -->
    <?php if ($product_count > 0) { ?>

      <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-5">

        <?php
          $delay = 0.12;
          while ($product = mysqli_fetch_assoc($product_result)) {
            $desc = htmlspecialchars($product['description'] ?? '');
            $shortDesc = strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : ($desc ?: 'No description');
            $price = $product['price'];
            $imgPath = "../uploads/" . $product['image'];
        ?>

        <div class="product-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden" style="animation-delay:<?= $delay ?>s" data-view="grid">

          <!-- Image -->
          <div class="card-img-wrap relative h-52 bg-gray-100 dark:bg-white/[0.02]">
            <img
              src="<?= $imgPath ?>"
              alt="<?= htmlspecialchars($product['product_name']) ?>"
              class="w-full h-full object-cover"
              onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>/400/300.jpg'"
            >
            <div class="quick-view">
              <a href="#" class="inline-flex items-center gap-2 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm text-gray-800 dark:text-white text-xs font-semibold px-4 py-2 rounded-xl shadow-lg hover:bg-white dark:hover:bg-gray-800 transition">
                <i class="fa-solid fa-eye text-[10px]"></i> Quick View
              </a>
            </div>
            <!-- Price tag overlay -->
            <div class="absolute top-3 right-3 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm px-3 py-1.5 rounded-xl">
              <span class="text-sm font-extrabold text-brand-600 dark:text-brand-400">Rs. <?= number_format($price, 0) ?></span>
            </div>
          </div>

          <!-- Body -->
          <div class="p-4">
            <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug"><?= htmlspecialchars($product['product_name']) ?></h4>
            <p class="text-xs text-gray-400 mt-1.5 leading-relaxed"><?= $shortDesc ?></p>

            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-white/5">

              <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Price</p>
                <p class="text-base font-extrabold text-gray-900 dark:text-white">Rs. <?= number_format($price, 0) ?></p>
              </div>

              <button class="btn-cart text-white text-xs font-semibold px-4 py-2.5 rounded-xl flex items-center gap-2">
                <i class="fa-solid fa-cart-plus text-[10px]"></i>
                Add to Cart
              </button>

            </div>
          </div>

        </div>

        <!-- LIST VIEW (hidden by default) -->
        <div class="product-card fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden hidden" style="animation-delay:<?= $delay ?>s" data-view="list">
          <div class="flex items-center gap-4 p-4">

            <div class="w-20 h-20 rounded-xl overflow-hidden bg-gray-100 dark:bg-white/[0.02] shrink-0">
              <img
                src="<?= $imgPath ?>"
                alt="<?= htmlspecialchars($product['product_name']) ?>"
                class="w-full h-full object-cover"
                onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>/100/100.jpg'"
              >
            </div>

            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($product['product_name']) ?></h4>
              <p class="text-xs text-gray-400 mt-0.5 truncate"><?= $shortDesc ?></p>
            </div>

            <div class="text-right shrink-0">
              <p class="text-base font-extrabold text-brand-600 dark:text-brand-400">Rs. <?= number_format($price, 0) ?></p>
              <button class="btn-cart text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 mt-1 ml-auto">
                <i class="fa-solid fa-cart-plus text-[9px]"></i> Cart
              </button>
            </div>

          </div>
        </div>

        <?php
            $delay += 0.06;
          }
        ?>

      </div>

    <?php } else { ?>

      <!-- EMPTY STATE -->
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-16 text-center">

        <div class="w-20 h-20 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-5">
          <i class="fa-solid fa-box-open text-gray-300 dark:text-gray-700 text-3xl"></i>
        </div>

        <h3 class="text-lg font-bold text-gray-700 dark:text-white/60">No Products Yet</h3>
        <p class="text-sm text-gray-400 mt-2 max-w-sm mx-auto leading-relaxed">
          This category doesn't have any products. Start by adding the first one.
        </p>

        <div class="flex items-center justify-center gap-3 mt-6">
          <a href="add.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white text-sm font-semibold px-6 py-3 rounded-xl transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-500/20">
            <i class="fa-solid fa-plus text-xs"></i>
            Add Product
          </a>
          <a href="../category/view.php" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 dark:text-white/40 hover:text-gray-700 dark:hover:text-white/70 px-5 py-3 rounded-xl border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/[0.03] transition">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Back to Categories
          </a>
        </div>

      </div>

    <?php } ?>

  </div>

</main>

<!-- ========== SCRIPTS ========== -->
<script>

/* Sidebar Toggle */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const main = document.getElementById('main');
  const collapsed = sidebar.classList.toggle('sidebar-collapsed');
  sidebar.style.width = collapsed ? '78px' : '260px';
  main.style.marginLeft = collapsed ? '78px' : '260px';
}

/* Dark Mode */
function toggleDark() {
  const html = document.documentElement;
  const body = document.body;
  const btn = document.getElementById('darkBtn');
  const isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);
  btn.innerHTML = isDark
    ? '<i class="fa-solid fa-sun text-sm"></i>'
    : '<i class="fa-solid fa-moon text-sm"></i>';
}

/* Profile Menu */
function toggleMenu() {
  document.getElementById('menu').classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
  const menu = document.getElementById('menu');
  if (!e.target.closest('.relative') && !menu.classList.contains('hidden')) {
    menu.classList.add('hidden');
  }
});

/* Grid / List View Toggle */
function setView(mode) {
  const gridBtn = document.getElementById('gridBtn');
  const listBtn = document.getElementById('listBtn');
  const grid = document.getElementById('productsGrid');

  if (!grid) return;

  const cards = grid.querySelectorAll('[data-view]');

  if (mode === 'grid') {
    grid.className = 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-5';
    gridBtn.className = 'w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-white/10 text-gray-700 dark:text-white shadow-sm transition';
    listBtn.className = 'w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-white/60 transition';
    cards.forEach(c => {
      if (c.dataset.view === 'list') c.classList.add('hidden');
      if (c.dataset.view === 'grid') c.classList.remove('hidden');
    });
  } else {
    grid.className = 'grid grid-cols-1 gap-3';
    listBtn.className = 'w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-white/10 text-gray-700 dark:text-white shadow-sm transition';
    gridBtn.className = 'w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-white/60 transition';
    cards.forEach(c => {
      if (c.dataset.view === 'grid') c.classList.add('hidden');
      if (c.dataset.view === 'list') c.classList.remove('hidden');
    });
  }
}

/* Animate stat bar on load */
window.addEventListener('load', function() {
  const bar = document.querySelector('.stat-bar-fill');
  if (bar) {
    const target = bar.style.width;
    bar.style.width = '0%';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        bar.style.width = target;
      });
    });
  }
});

</script>

</body>
</html>