<?php
session_start();
include("../backend/config/db.php");

/* CATEGORIES */
 $cat_query = "SELECT * FROM categories";
 $cat_result = mysqli_query($conn, $cat_query);

/* PRODUCTS FILTER */
if (isset($_GET['cat_id']) && $_GET['cat_id'] !== "") {
    $cat_id = intval($_GET['cat_id']);
    $product_query = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = $cat_id ORDER BY p.id DESC";
    $active_cat = $cat_id;
} else {
    $product_query = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC";
    $active_cat = 0;
}
 $product_result = mysqli_query($conn, $product_query);
 $product_count = mysqli_num_rows($product_result);

/* ACTIVE CATEGORY NAME */
 $active_cat_name = 'All Products';
if ($active_cat > 0) {
    $ac = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category_name FROM categories WHERE id = $active_cat"));
    if ($ac) $active_cat_name = $ac['category_name'];
}

 $count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beats Shop — Premium Audio</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family+Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        gold: {
          50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',
          400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',
          800:'#92400e',900:'#78350f'
        },
        surface: {
          900:'#0a0a0f',800:'#101018',700:'#16161f',600:'#1c1c28',
          500:'#222230',400:'#2a2a3a',300:'#35354a'
        }
      }
    }
  }
}
</script>

<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Plus Jakarta Sans',sans-serif; background:#0a0a0f; color:#fff; }

  ::-webkit-scrollbar { width:8px; }
  ::-webkit-scrollbar-track { background:#0a0a0f; }
  ::-webkit-scrollbar-thumb { background:#2a2a3a; border-radius:99px; }
  ::-webkit-scrollbar-thumb:hover { background:#3a3a4a; }

  .hero-orb {
    position:absolute;border-radius:50%;filter:blur(100px);opacity:0.4;
    animation:orbFloat 10s ease-in-out infinite alternate;
  }
  .hero-orb-1 {
    width:600px;height:600px;top:-200px;right:-100px;
    background:radial-gradient(circle,rgba(251,191,36,0.25),transparent 70%);
  }
  .hero-orb-2 {
    width:400px;height:400px;bottom:-100px;left:-50px;
    background:radial-gradient(circle,rgba(245,158,11,0.15),transparent 70%);
    animation-delay:-4s;animation-duration:14s;
  }
  @keyframes orbFloat {
    0% { transform:translate(0,0) scale(1); }
    50% { transform:translate(30px,-30px) scale(1.08); }
    100% { transform:translate(-20px,20px) scale(0.95); }
  }

  .nav-blur {
    background:rgba(10,10,15,0.75);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(255,255,255,0.04);
  }

  .cat-pill {
    padding:8px 20px;border-radius:99px;font-size:0.8rem;font-weight:600;
    border:1px solid rgba(255,255,255,0.08);color:rgba(255,255,255,0.5);
    text-decoration:none;white-space:nowrap;transition:all 0.25s ease;
    background:transparent;cursor:pointer;display:inline-flex;align-items:center;
  }
  .cat-pill:hover {
    border-color:rgba(251,191,36,0.3);color:#fbbf24;
    background:rgba(251,191,36,0.06);
  }
  .cat-pill.active {
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;border-color:transparent;
    box-shadow:0 4px 16px -4px rgba(251,191,36,0.4);
  }

  .prod-card {
    background:#101018;border-radius:20px;overflow:hidden;
    border:1px solid rgba(255,255,255,0.04);
    transition:all 0.4s cubic-bezier(.4,0,.2,1);
    position:relative;
  }
  .prod-card::before {
    content:'';position:absolute;inset:0;border-radius:20px;
    background:linear-gradient(135deg,rgba(251,191,36,0.08),transparent 60%);
    opacity:0;transition:opacity 0.4s ease;pointer-events:none;z-index:1;
  }
  .prod-card:hover {
    transform:translateY(-8px);
    border-color:rgba(251,191,36,0.15);
    box-shadow:0 20px 50px -15px rgba(0,0,0,0.6), 0 0 40px -10px rgba(251,191,36,0.08);
  }
  .prod-card:hover::before { opacity:1; }

  .prod-img-wrap {
    position:relative;overflow:hidden;background:#16161f;
  }
  .prod-img-wrap img {
    width:100%;height:260px;object-fit:cover;
    transition:transform 0.6s cubic-bezier(.4,0,.2,1);
  }
  .prod-card:hover .prod-img-wrap img {
    transform:scale(1.08);
  }
  .prod-img-wrap::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:40%;
    background:linear-gradient(to top,#101018,transparent);pointer-events:none;
  }

  .cat-badge {
    position:absolute;top:12px;left:12px;z-index:2;
    background:rgba(10,10,15,0.7);backdrop-filter:blur(8px);
    padding:4px 10px;border-radius:8px;font-size:0.65rem;font-weight:600;
    color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.06);
  }

  .btn-cart {
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;font-weight:700;border:none;border-radius:12px;
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;
  }
  .btn-cart::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);
    transition:left 0.5s ease;
  }
  .btn-cart:hover::before { left:100%; }
  .btn-cart:hover {
    box-shadow:0 6px 24px -4px rgba(251,191,36,0.5);
    transform:translateY(-1px);
  }

  .btn-view {
    background:transparent;border:1px solid rgba(255,255,255,0.1);
    color:rgba(255,255,255,0.6);border-radius:12px;font-weight:600;
    transition:all 0.25s ease;
  }
  .btn-view:hover {
    background:rgba(255,255,255,0.06);
    border-color:rgba(255,255,255,0.2);
    color:#fff;
  }

  .fade-up {
    opacity:0;transform:translateY(20px);
    animation:fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  .text-shimmer {
    background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24);
    background-size:200% auto;
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    animation:shimmer 3s linear infinite;
  }
  @keyframes shimmer { to { background-position:200% center; } }

  .hero-img {
    animation:heroFloat 4s ease-in-out infinite;
  }
  @keyframes heroFloat {
    0%,100% { transform:translateY(0); }
    50% { transform:translateY(-15px); }
  }

  .footer-link {
    color:rgba(255,255,255,0.35);font-size:0.85rem;
    text-decoration:none;transition:color 0.2s;display:block;padding:4px 0;
  }
  .footer-link:hover { color:#fbbf24; }

  .cart-pulse {
    animation:cartPop 0.3s cubic-bezier(.4,0,.2,1);
  }
  @keyframes cartPop {
    0% { transform:scale(1); }
    50% { transform:scale(1.4); }
    100% { transform:scale(1); }
  }

  .empty-icon {
    animation:emptyBounce 2s ease-in-out infinite;
  }
  @keyframes emptyBounce {
    0%,100% { transform:translateY(0); }
    50% { transform:translateY(-8px); }
  }

  .cat-scroll::-webkit-scrollbar { display:none; }
  .cat-scroll { -ms-overflow-style:none;scrollbar-width:none; }

  /* Skeleton loading */
  .skeleton {
    background:linear-gradient(90deg,#16161f 25%,#1c1c28 50%,#16161f 75%);
    background-size:200% 100%;animation:skeletonPulse 1.5s ease infinite;
  }
  @keyframes skeletonPulse { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
</style>

</head>

<body>

<!-- ========== NAVBAR ========== -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50 px-6 lg:px-10 py-4">

  <div class="max-w-7xl mx-auto flex items-center justify-between">

    <a href="index.php" class="flex items-center gap-3 text-decoration-none">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-lg shadow-gold-500/20">
        <i class="fa-solid fa-headphones text-surface-900 text-sm"></i>
      </div>
      <span class="text-lg font-extrabold text-white tracking-tight">Beats<span class="text-gold-400">Shop</span></span>
    </a>

    <div class="hidden md:flex items-center gap-8">
      <a href="index.php" class="text-sm font-medium text-white hover:text-gold-400 transition">Home</a>
      <a href="#products" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">Products</a>
      <a href="#" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">About</a>
      <a href="#" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">Contact</a>
    </div>

    <div class="flex items-center gap-3">
      <button onclick="toggleSearch()" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60 hover:text-white">
        <i class="fa-solid fa-magnifying-glass text-sm"></i>
      </button>
      <a href="cart.php" class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60 hover:text-white">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($count > 0) { ?>
          <span class="cart-pulse absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $count ?></span>
        <?php } ?>
      </a>
      <button onclick="toggleMobileMenu()" class="md:hidden w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60">
        <i class="fa-solid fa-bars text-sm"></i>
      </button>
    </div>

  </div>

  <div id="searchBar" class="hidden max-w-7xl mx-auto mt-4">
    <div class="relative">
      <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/30 text-sm"></i>
      <input type="text" placeholder="Search products..." class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-sm text-white placeholder:text-white/30 focus:outline-none focus:border-gold-500/40 transition">
    </div>
  </div>

  <div id="mobileMenu" class="hidden md:hidden mt-4 pb-2 border-t border-white/5 pt-4">
    <a href="index.php" class="block py-2.5 text-sm font-medium text-white">Home</a>
    <a href="#products" class="block py-2.5 text-sm font-medium text-white/50">Products</a>
    <a href="#" class="block py-2.5 text-sm font-medium text-white/50">About</a>
    <a href="#" class="block py-2.5 text-sm font-medium text-white/50">Contact</a>
  </div>

</nav>


<!-- ========== HERO ========== -->
<section class="relative min-h-[92vh] flex items-center overflow-hidden pt-20">

  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-10 w-full">
    <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">

      <div class="flex-1 text-center lg:text-left fade-up">
        <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-6">
          <span class="w-2 h-2 rounded-full bg-gold-400 animate-pulse"></span>
          <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">New Collection 2025</span>
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.1] tracking-tight">
          Next Level<br>
          <span class="text-shimmer">Sound Experience</span>
        </h1>
        <p class="text-white/40 mt-5 text-base lg:text-lg max-w-md mx-auto lg:mx-0 leading-relaxed">
          Premium headphones engineered for deep bass, crystal clarity, and all-day comfort. Feel every beat.
        </p>
        <div class="flex items-center gap-4 mt-8 justify-center lg:justify-start">
          <a href="#products" class="btn-cart px-7 py-3.5 text-sm flex items-center gap-2">
            <i class="fa-solid fa-headphones text-xs"></i> Shop Now
          </a>
          <a href="#" class="btn-view px-7 py-3.5 text-sm flex items-center gap-2">
            <i class="fa-solid fa-play text-[10px]"></i> Watch Video
          </a>
        </div>
        <div class="flex items-center gap-8 mt-12 justify-center lg:justify-start">
          <div>
            <p class="text-2xl font-extrabold text-white">50K+</p>
            <p class="text-xs text-white/30 mt-0.5">Happy Customers</p>
          </div>
          <div class="w-px h-10 bg-white/10"></div>
          <div>
            <p class="text-2xl font-extrabold text-white">4.9★</p>
            <p class="text-xs text-white/30 mt-0.5">Average Rating</p>
          </div>
          <div class="w-px h-10 bg-white/10"></div>
          <div>
            <p class="text-2xl font-extrabold text-white">200+</p>
            <p class="text-xs text-white/30 mt-0.5">Products</p>
          </div>
        </div>
      </div>

      <div class="flex-1 flex justify-center fade-up" style="animation-delay:0.15s">
        <div class="relative">
          <div class="absolute inset-0 bg-gradient-to-br from-gold-400/20 to-transparent rounded-full blur-3xl scale-75"></div>
          <img src="../backend/uploads/image5.png" alt="Featured Product" class="hero-img relative w-80 lg:w-[420px] drop-shadow-2xl">
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ========== CATEGORIES ========== -->
<section class="py-6 relative">

  <div class="max-w-7xl mx-auto px-6 lg:px-10">

    <div class="flex items-center gap-4 mb-5">
      <h2 class="text-sm font-bold text-white/80 uppercase tracking-wider">
        <i class="fa-solid fa-filter text-gold-400 mr-2 text-xs"></i>Browse by Category
      </h2>
      <span id="clearFilter" class="<?= $active_cat > 0 ? '' : 'hidden' ?> text-xs font-medium text-gold-400 hover:text-gold-300 transition flex items-center gap-1 cursor-pointer" onclick="loadCategory(0)">
        <i class="fa-solid fa-xmark text-[10px]"></i> Clear Filter
      </span>
    </div>

    <div class="cat-scroll flex items-center gap-3 overflow-x-auto pb-2" id="catBar">

      <span class="cat-pill <?= $active_cat === 0 ? 'active' : '' ?>" data-cat="0" onclick="loadCategory(0)">
        <i class="fa-solid fa-grid-2 mr-1.5 text-[10px]"></i>All
      </span>

      <?php
        mysqli_data_seek($cat_result, 0);
        while ($cat = mysqli_fetch_assoc($cat_result)) {
      ?>
        <span class="cat-pill <?= $active_cat == $cat['id'] ? 'active' : '' ?>" data-cat="<?= $cat['id'] ?>" onclick="loadCategory(<?= $cat['id'] ?>)">
          <?= htmlspecialchars($cat['category_name']) ?>
        </span>
      <?php } ?>

    </div>

  </div>

</section>


<!-- ========== PRODUCTS ========== -->
<section id="products" class="py-8 pb-20">

  <div class="max-w-7xl mx-auto px-6 lg:px-10">

    <div class="flex items-end justify-between mb-8 fade-up">
      <div>
        <h2 id="sectionTitle" class="text-2xl font-extrabold text-white tracking-tight"><?= $active_cat_name ?></h2>
        <p id="sectionCount" class="text-sm text-white/30 mt-1"><?= $product_count ?> product<?= $product_count !== 1 ? 's' : '' ?> available</p>
      </div>
    </div>

    <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

      <?php
        $delay = 0;
        while ($product = mysqli_fetch_assoc($product_result)) {
          $delay += 0.06;
          $imgPath = "../backend/uploads/" . $product['image'];
      ?>

      <div class="prod-card fade-up" style="animation-delay:<?= $delay ?>s">

        <div class="prod-img-wrap">
          <img
            src="<?= $imgPath ?>"
            alt="<?= htmlspecialchars($product['product_name']) ?>"
            onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>/400/300.jpg'"
          >
          <span class="cat-badge">
            <i class="fa-solid fa-folder text-[8px] mr-1"></i><?= htmlspecialchars($product['category_name']) ?>
          </span>
        </div>

        <div class="p-5 relative z-10">

          <h3 class="text-sm font-bold text-white leading-snug"><?= htmlspecialchars($product['product_name']) ?></h3>

          <p class="text-xs text-white/30 mt-1.5 leading-relaxed line-clamp-2">
            <?= htmlspecialchars($product['description'] ?? 'Premium quality product with exceptional performance.') ?>
          </p>

          <div class="flex items-end justify-between mt-4 pt-4 border-t border-white/5">

            <div>
              <p class="text-[10px] text-white/25 uppercase tracking-wider font-medium">Price</p>
              <p class="text-xl font-extrabold text-gold-400 mt-0.5">Rs. <?= number_format($product['price'], 0) ?></p>
            </div>

            <div class="flex items-center gap-2">
              <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-view w-10 h-10 flex items-center justify-center" title="View Details">
                <i class="fa-solid fa-eye text-xs"></i>
              </a>
              <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="btn-cart w-10 h-10 flex items-center justify-center" title="Add to Cart">
                <i class="fa-solid fa-bag-shopping text-xs"></i>
              </a>
            </div>

          </div>

        </div>

      </div>

      <?php } ?>

    </div>

  </div>
</section>


<!-- ========== NEWSLETTER ========== -->
<section class="py-16 relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-r from-gold-500/5 to-transparent"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-10 relative z-10 text-center">
    <h2 class="text-2xl font-extrabold text-white">Stay in the Loop</h2>
    <p class="text-sm text-white/30 mt-2 max-w-md mx-auto">Get notified about new drops, exclusive deals, and audio tips.</p>
    <div class="flex items-center gap-3 max-w-md mx-auto mt-6">
      <input type="email" placeholder="your@email.com" class="flex-1 bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-sm text-white placeholder:text-white/25 focus:outline-none focus:border-gold-500/40 transition">
      <button class="btn-cart px-6 py-3 text-sm shrink-0">Subscribe</button>
    </div>
  </div>
</section>


<!-- ========== FOOTER ========== -->
<footer class="bg-surface-800 border-t border-white/[0.03] pt-14 pb-8">

  <div class="max-w-7xl mx-auto px-6 lg:px-10">

    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">

      <div class="col-span-2 md:col-span-1">
        <a href="index.php" class="flex items-center gap-3 mb-4 text-decoration-none">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center">
            <i class="fa-solid fa-headphones text-surface-900 text-xs"></i>
          </div>
          <span class="text-base font-extrabold text-white">Beats<span class="text-gold-400">Shop</span></span>
        </a>
        <p class="text-xs text-white/25 leading-relaxed">Premium audio gear for those who demand the best. Engineered with passion.</p>
        <div class="flex items-center gap-3 mt-5">
          <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-gold-500/10 flex items-center justify-center text-white/30 hover:text-gold-400 transition"><i class="fa-brands fa-facebook-f text-xs"></i></a>
          <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-gold-500/10 flex items-center justify-center text-white/30 hover:text-gold-400 transition"><i class="fa-brands fa-instagram text-xs"></i></a>
          <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-gold-500/10 flex items-center justify-center text-white/30 hover:text-gold-400 transition"><i class="fa-brands fa-twitter text-xs"></i></a>
          <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-gold-500/10 flex items-center justify-center text-white/30 hover:text-gold-400 transition"><i class="fa-brands fa-youtube text-xs"></i></a>
        </div>
      </div>

      <div>
        <p class="text-xs font-bold text-white/50 uppercase tracking-wider mb-4">Quick Links</p>
        <a href="index.php" class="footer-link">Home</a>
        <a href="#products" class="footer-link">Products</a>
        <a href="cart.php" class="footer-link">Cart</a>
        <a href="#" class="footer-link">About Us</a>
      </div>

      <div>
        <p class="text-xs font-bold text-white/50 uppercase tracking-wider mb-4">Categories</p>
        <?php
          mysqli_data_seek($cat_result, 0);
          $catCount = 0;
          while ($cat = mysqli_fetch_assoc($cat_result) && $catCount < 5) {
            $catCount++;
        ?>
          <span class="footer-link cursor-pointer" onclick="loadCategory(<?= $cat['id'] ?>);document.getElementById('products').scrollIntoView({behavior:'smooth'})"><?= htmlspecialchars($cat['category_name']) ?></span>
        <?php } ?>
      </div>

      <div>
        <p class="text-xs font-bold text-white/50 uppercase tracking-wider mb-4">Contact</p>
        <p class="footer-link"><i class="fa-solid fa-envelope text-[10px] mr-2 text-gold-400/50"></i>hello@beatsshop.com</p>
        <p class="footer-link"><i class="fa-solid fa-phone text-[10px] mr-2 text-gold-400/50"></i>+92 300 1234567</p>
        <p class="footer-link"><i class="fa-solid fa-location-dot text-[10px] mr-2 text-gold-400/50"></i>Karachi, Pakistan</p>
      </div>

    </div>

    <div class="border-t border-white/[0.03] pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-xs text-white/20">&copy; <?= date('Y') ?> BeatsShop. All rights reserved.</p>
      <div class="flex items-center gap-4">
        <a href="#" class="text-xs text-white/20 hover:text-white/40 transition">Privacy Policy</a>
        <a href="#" class="text-xs text-white/20 hover:text-white/40 transition">Terms of Service</a>
      </div>
    </div>

  </div>

</footer>


<!-- ========== SCRIPTS ========== -->
<script>

/* ===== CATEGORY AJAX FILTER — NO RELOAD ===== */
let currentCat = <?= $active_cat ?>;

function loadCategory(catId) {
  if (catId === currentCat) return;
  currentCat = catId;

  const grid = document.getElementById('productsGrid');
  const title = document.getElementById('sectionTitle');
  const countEl = document.getElementById('sectionCount');
  const clearBtn = document.getElementById('clearFilter');

  /* Update active pill */
  document.querySelectorAll('#catBar .cat-pill').forEach(function(pill) {
    pill.classList.toggle('active', parseInt(pill.dataset.cat) === catId);
  });

  /* Show/hide clear filter */
  clearBtn.classList.toggle('hidden', catId === 0);

  /* Show skeleton loading */
  grid.innerHTML = '';
  for (let i = 0; i < 4; i++) {
    grid.innerHTML += '<div class="rounded-2xl overflow-hidden border border-white/[0.04] bg-[#101018]">' +
      '<div class="skeleton" style="height:260px"></div>' +
      '<div class="p-5 space-y-3">' +
        '<div class="skeleton h-4 w-3/4 rounded-lg"></div>' +
        '<div class="skeleton h-3 w-full rounded-lg"></div>' +
        '<div class="skeleton h-3 w-5/6 rounded-lg"></div>' +
        '<div class="pt-4 mt-4 border-t border-white/5 flex justify-between items-end">' +
          '<div><div class="skeleton h-3 w-12 mb-2 rounded-lg"></div><div class="skeleton h-6 w-20 rounded-lg"></div></div>' +
          '<div class="flex gap-2"><div class="skeleton w-10 h-10 rounded-xl"></div><div class="skeleton w-10 h-10 rounded-xl"></div></div>' +
        '</div></div></div>';
  }

  /* Smooth scroll to products */
  document.getElementById('products').scrollIntoView({ behavior: 'smooth', block: 'start' });

  /* Fetch products via AJAX */
  fetch('fetch_products.php?cat_id=' + catId)
    .then(function(r) { return r.json(); })
    .then(function(data) {

      if (data.html === 'EMPTY') {
        grid.innerHTML =
          '<div class="text-center py-20 col-span-full">' +
            '<div class="empty-icon inline-block mb-5"><div class="w-20 h-20 rounded-2xl bg-surface-700 flex items-center justify-center mx-auto"><i class="fa-solid fa-box-open text-white/10 text-3xl"></i></div></div>' +
            '<h3 class="text-lg font-bold text-white/40">No Products Found</h3>' +
            '<p class="text-sm text-white/20 mt-2">This category doesn\'t have any products yet.</p>' +
            '<button onclick="loadCategory(0)" class="inline-flex items-center gap-2 mt-5 text-sm font-semibold text-gold-400 hover:text-gold-300 transition bg-transparent border-none cursor-pointer"><i class="fa-solid fa-arrow-left text-xs"></i> Browse All Products</button>' +
          '</div>';
        title.textContent = data.name;
        countEl.textContent = '0 products available';
      } else {
        grid.innerHTML = data.html;
        title.textContent = data.name;
        countEl.textContent = data.count + ' product' + (data.count !== 1 ? 's' : '') + ' available';

        /* Re-trigger fade animations for new cards */
        grid.querySelectorAll('.fade-up').forEach(function(el) {
          el.style.animationPlayState = 'paused';
          var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
              if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                obs.unobserve(entry.target);
              }
            });
          }, { threshold: 0.1 });
          obs.observe(el);
        });
      }
    })
    .catch(function() {
      grid.innerHTML = '<p class="text-center text-red-400 py-20 col-span-full">Failed to load products.</p>';
    });

  /* Update URL without page reload */
  var url = catId === 0 ? 'index.php' : 'index.php?cat_id=' + catId;
  history.pushState({ cat_id: catId }, '', url);
}

/* Browser back/forward buttons */
window.addEventListener('popstate', function(e) {
  var catId = (e.state && e.state.cat_id !== undefined) ? e.state.cat_id : 0;
  currentCat = -1;
  loadCategory(catId);
});


/* ===== SEARCH & MENU ===== */
function toggleSearch() {
  var bar = document.getElementById('searchBar');
  bar.classList.toggle('hidden');
  if (!bar.classList.contains('hidden')) bar.querySelector('input').focus();
}
function toggleMobileMenu() {
  document.getElementById('mobileMenu').classList.toggle('hidden');
}

document.querySelectorAll('a[href^="#"]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    var target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      document.getElementById('mobileMenu').classList.add('hidden');
    }
  });
});

/* Navbar on scroll */
window.addEventListener('scroll', function() {
  var nav = document.querySelector('.nav-blur');
  if (window.scrollY > 50) {
    nav.style.borderBottomColor = 'rgba(255,255,255,0.06)';
    nav.style.background = 'rgba(10,10,15,0.9)';
  } else {
    nav.style.borderBottomColor = 'rgba(255,255,255,0.04)';
    nav.style.background = 'rgba(10,10,15,0.75)';
  }
});

/* Fade-in observer */
var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) entry.target.style.animationPlayState = 'running';
  });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(function(el) {
  el.style.animationPlayState = 'paused';
  observer.observe(el);
});
</script>

</body>
</html>