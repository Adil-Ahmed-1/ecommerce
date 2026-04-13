<?php
include("../backend/config/db.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

 $id = intval($_GET['id']);

 $stmt = mysqli_prepare($conn, "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: index.php");
    exit;
}
 $product = mysqli_fetch_assoc($result);

/* RELATED PRODUCTS — same category, exclude current */
 $related_result = mysqli_query($conn, "
    SELECT * FROM products 
    WHERE category_id = " . $product['category_id'] . " AND id != $id 
    ORDER BY RAND() LIMIT 4
");

 $count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['product_name']) ?> — BeatsShop</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

  .nav-blur {
    background:rgba(10,10,15,0.75);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(255,255,255,0.04);
  }

  /* Main image */
  .main-img-wrap {
    position:relative;border-radius:24px;overflow:hidden;
    background:#16161f;
  }
  .main-img-wrap::after {
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(251,191,36,0.06),transparent 50%);
    pointer-events:none;
  }
  .main-img-wrap img {
    width:100%;height:100%;object-fit:cover;
    transition:transform 0.6s cubic-bezier(.4,0,.2,1);
  }
  .main-img-wrap:hover img { transform:scale(1.05); }

  /* Thumbnail */
  .thumb {
    width:72px;height:72px;border-radius:14px;overflow:hidden;cursor:pointer;
    border:2px solid transparent;
    transition:all 0.25s ease;
    background:#16161f;
  }
  .thumb img { width:100%;height:100%;object-fit:cover; }
  .thumb.active, .thumb:hover {
    border-color:#fbbf24;
    box-shadow:0 0 16px -4px rgba(251,191,36,0.3);
  }

  /* Feature pill */
  .feat-pill {
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);
    border-radius:12px;padding:10px 16px;font-size:0.8rem;
    color:rgba(255,255,255,0.5);transition:all 0.25s ease;
  }
  .feat-pill:hover {
    background:rgba(251,191,36,0.06);
    border-color:rgba(251,191,36,0.15);
    color:#fbbf24;
  }

  /* Cart button */
  .btn-cart {
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;font-weight:700;border:none;border-radius:14px;
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
    box-shadow:0 8px 30px -4px rgba(251,191,36,0.5);
    transform:translateY(-2px);
  }

  .btn-outline {
    background:transparent;border:1px solid rgba(255,255,255,0.1);
    color:rgba(255,255,255,0.6);border-radius:14px;font-weight:600;
    transition:all 0.25s ease;
  }
  .btn-outline:hover {
    background:rgba(255,255,255,0.06);
    border-color:rgba(255,255,255,0.2);
    color:#fff;
  }

  /* Related card */
  .rel-card {
    background:#101018;border-radius:18px;overflow:hidden;
    border:1px solid rgba(255,255,255,0.04);
    transition:all 0.35s cubic-bezier(.4,0,.2,1);
  }
  .rel-card:hover {
    transform:translateY(-6px);
    border-color:rgba(251,191,36,0.12);
    box-shadow:0 16px 40px -12px rgba(0,0,0,0.5);
  }
  .rel-card img {
    width:100%;height:180px;object-fit:cover;
    transition:transform 0.5s ease;
  }
  .rel-card:hover img { transform:scale(1.06); }

  /* Text shimmer */
  .text-shimmer {
    background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24);
    background-size:200% auto;
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    animation:shimmer 3s linear infinite;
  }
  @keyframes shimmer { to { background-position:200% center; } }

  /* Fade in */
  .fade-up {
    opacity:0;transform:translateY(20px);
    animation:fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  /* Breadcrumb link */
  .bc-link {
    color:rgba(255,255,255,0.3);font-size:0.8rem;text-decoration:none;
    transition:color 0.2s;
  }
  .bc-link:hover { color:#fbbf24; }

  /* Toast notification */
  .toast-msg {
    position:fixed;top:24px;right:24px;z-index:9999;
    background:#101018;border:1px solid rgba(251,191,36,0.2);
    border-radius:16px;padding:16px 20px;
    box-shadow:0 20px 50px -10px rgba(0,0,0,0.6);
    opacity:0;transform:translateY(-12px) scale(0.96);
    transition:all 0.35s cubic-bezier(.4,0,.2,1);
    pointer-events:none;
  }
  .toast-msg.show {
    opacity:1;transform:translateY(0) scale(1);pointer-events:auto;
  }

  /* Quantity buttons */
  .qty-btn {
    width:40px;height:40px;border-radius:10px;
    background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);
    color:#fff;display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:all 0.2s;font-size:0.9rem;
  }
  .qty-btn:hover {
    background:rgba(251,191,36,0.1);border-color:rgba(251,191,36,0.3);
    color:#fbbf24;
  }

  /* Tab */
  .tab-btn {
    padding:10px 20px;border-radius:10px;font-size:0.8rem;font-weight:600;
    color:rgba(255,255,255,0.35);background:transparent;
    border:1px solid transparent;cursor:pointer;transition:all 0.25s;
  }
  .tab-btn.active {
    background:rgba(251,191,36,0.08);
    border-color:rgba(251,191,36,0.15);
    color:#fbbf24;
  }
  .tab-btn:hover:not(.active) { color:rgba(255,255,255,0.6); }

  /* Star rating */
  .star { color:#fbbf24; }
  .star-empty { color:#2a2a3a; }

  /* Image zoom lens */
  .zoom-lens {
    position:absolute;width:150px;height:150px;
    border:2px solid rgba(251,191,36,0.4);border-radius:50%;
    background-repeat:no-repeat;pointer-events:none;
    opacity:0;transition:opacity 0.2s;
    box-shadow:0 0 20px rgba(0,0,0,0.5);
  }
  .main-img-wrap:hover .zoom-lens { opacity:1; }
</style>

</head>

<body>

<!-- Toast -->
<div id="toast" class="toast-msg flex items-center gap-3">
  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shrink-0">
    <i class="fa-solid fa-check text-surface-900 text-sm"></i>
  </div>
  <div>
    <p class="text-sm font-semibold text-white">Added to Cart</p>
    <p class="text-xs text-white/30 mt-0.5">Item has been added successfully</p>
  </div>
</div>

<!-- ========== NAVBAR ========== -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50 px-6 lg:px-10 py-4">

  <div class="max-w-7xl mx-auto flex items-center justify-between">

    <a href="index.php" class="flex items-center gap-3 text-decoration-none">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-lg shadow-gold-500/20">
        <i class="fa-solid fa-headphones text-surface-900 text-sm"></i>
      </div>
      <span class="text-lg font-extrabold text-white tracking-tight">Beats<span class="text-gold-400">Shop</span></span>
    </a>

    <div class="flex items-center gap-3">
      <a href="index.php" class="hidden sm:flex items-center gap-2 text-sm font-medium text-white/50 hover:text-white transition px-3 py-2 rounded-xl hover:bg-white/5">
        <i class="fa-solid fa-house text-xs"></i> Home
      </a>
      <a href="cart.php" class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60 hover:text-white">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($count > 0) { ?>
          <span class="absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $count ?></span>
        <?php } ?>
      </a>
    </div>

  </div>

</nav>


<!-- ========== MAIN CONTENT ========== -->
<main class="pt-24 pb-20">

  <div class="max-w-7xl mx-auto px-6 lg:px-10">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 mb-8 fade-up">
      <a href="index.php" class="bc-link"><i class="fa-solid fa-house text-[10px]"></i> Home</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-white/10"></i>
      <a href="?cat_id=<?= $product['category_id'] ?>" class="bc-link"><?= htmlspecialchars($product['category_name']) ?></a>
      <i class="fa-solid fa-chevron-right text-[8px] text-white/10"></i>
      <span class="text-xs text-white/60 font-medium truncate max-w-[200px]"><?= htmlspecialchars($product['product_name']) ?></span>
    </div>

    <!-- PRODUCT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">

      <!-- LEFT — Images -->
      <div class="fade-up">

        <!-- Main Image -->
        <div class="main-img-wrap aspect-square mb-4">
          <img
            id="mainImg"
            src="../backend/uploads/<?= $product['image'] ?>"
            alt="<?= htmlspecialchars($product['product_name']) ?>"
            onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>/600/600.jpg'"
          >
          <!-- Category badge -->
          <div class="absolute top-4 left-4 z-10 bg-surface-900/70 backdrop-blur-sm border border-white/10 rounded-xl px-3 py-1.5 text-xs font-semibold text-white/60">
            <i class="fa-solid fa-folder text-[9px] text-gold-400 mr-1.5"></i>
            <?= htmlspecialchars($product['category_name']) ?>
          </div>
        </div>

        <!-- Thumbnails (placeholder for multiple images) -->
        <div class="flex items-center gap-3">
          <div class="thumb active" onclick="changeThumb(this, '../backend/uploads/<?= $product['image'] ?>')">
            <img src="../backend/uploads/<?= $product['image'] ?>" alt="" onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>a/100/100.jpg'">
          </div>
          <div class="thumb" onclick="changeThumb(this, 'https://picsum.photos/seed/<?= $product['id'] ?>b/600/600.jpg')">
            <img src="https://picsum.photos/seed/<?= $product['id'] ?>b/100/100.jpg" alt="">
          </div>
          <div class="thumb" onclick="changeThumb(this, 'https://picsum.photos/seed/<?= $product['id'] ?>c/600/600.jpg')">
            <img src="https://picsum.photos/seed/<?= $product['id'] ?>c/100/100.jpg" alt="">
          </div>
          <div class="thumb" onclick="changeThumb(this, 'https://picsum.photos/seed/<?= $product['id'] ?>d/600/600.jpg')">
            <img src="https://picsum.photos/seed/<?= $product['id'] ?>d/100/100.jpg" alt="">
          </div>
        </div>

      </div>

      <!-- RIGHT — Info -->
      <div class="fade-up" style="animation-delay:0.1s">

        <!-- Title -->
        <h1 class="text-3xl lg:text-4xl font-extrabold text-white leading-tight tracking-tight">
          <?= htmlspecialchars($product['product_name']) ?>
        </h1>

        <!-- Rating -->
        <div class="flex items-center gap-3 mt-4">
          <div class="flex items-center gap-0.5">
            <i class="fa-solid fa-star text-xs star"></i>
            <i class="fa-solid fa-star text-xs star"></i>
            <i class="fa-solid fa-star text-xs star"></i>
            <i class="fa-solid fa-star text-xs star"></i>
            <i class="fa-solid fa-star-half-stroke text-xs star"></i>
          </div>
          <span class="text-xs text-white/30">(4.8) · 124 reviews</span>
          <span class="text-xs text-gold-400 bg-gold-500/10 px-2 py-0.5 rounded-md font-semibold">In Stock</span>
        </div>

        <!-- Price -->
        <div class="mt-6 flex items-end gap-3">
          <span class="text-4xl font-extrabold text-shimmer">Rs. <?= number_format($product['price'], 0) ?></span>
          <span class="text-lg text-white/20 line-through mb-1">Rs. <?= number_format($product['price'] * 1.3, 0) ?></span>
          <span class="text-xs font-bold text-brand-500 bg-brand-500/10 px-2.5 py-1 rounded-lg mb-1.5">-30%</span>
        </div>

        <!-- Description -->
        <p class="text-sm text-white/35 leading-relaxed mt-6 max-w-lg">
          <?= htmlspecialchars($product['description'] ?? 'Premium quality product engineered for exceptional performance. Designed with attention to detail, built to last, and crafted for those who demand the very best.') ?>
        </p>

        <!-- Feature Pills -->
        <div class="flex flex-wrap gap-3 mt-6">
          <div class="feat-pill">
            <i class="fa-solid fa-truck-fast text-gold-400 text-xs"></i>
            <span>Free Delivery</span>
          </div>
          <div class="feat-pill">
            <i class="fa-solid fa-shield-halved text-gold-400 text-xs"></i>
            <span>1 Year Warranty</span>
          </div>
          <div class="feat-pill">
            <i class="fa-solid fa-rotate-left text-gold-400 text-xs"></i>
            <span>7 Days Return</span>
          </div>
          <div class="feat-pill">
            <i class="fa-solid fa-box text-gold-400 text-xs"></i>
            <span>Premium Packaging</span>
          </div>
        </div>

        <!-- Quantity + Add to Cart -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mt-8">

          <!-- Quantity -->
          <div class="flex items-center gap-3 bg-white/[0.03] border border-white/[0.06] rounded-2xl px-4 py-2">
            <button class="qty-btn" onclick="changeQty(-1)">
              <i class="fa-solid fa-minus text-xs"></i>
            </button>
            <span id="qtyValue" class="text-lg font-bold text-white w-8 text-center">1</span>
            <button class="qty-btn" onclick="changeQty(1)">
              <i class="fa-solid fa-plus text-xs"></i>
            </button>
          </div>

          <!-- Add to Cart -->
          <a href="add_to_cart.php?id=<?= $product['id'] ?>" id="cartLink" onclick="showToast()" class="btn-cart flex-1 sm:flex-none px-8 py-4 text-sm flex items-center justify-center gap-2 text-center">
            <i class="fa-solid fa-bag-shopping text-xs"></i>
            Add to Cart
          </a>

          <!-- Buy Now -->
          <a href="cart.php" class="btn-outline px-8 py-4 text-sm flex items-center justify-center gap-2 text-center">
            <i class="fa-solid fa-bolt text-xs"></i>
            Buy Now
          </a>

        </div>

        <!-- Trust badges -->
        <div class="flex items-center gap-6 mt-8 pt-6 border-t border-white/[0.04]">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-lock text-xs text-gold-400/50"></i>
            <span class="text-xs text-white/25">Secure Payment</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-certificate text-xs text-gold-400/50"></i>
            <span class="text-xs text-white/25">100% Genuine</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-headset text-xs text-gold-400/50"></i>
            <span class="text-xs text-white/25">24/7 Support</span>
          </div>
        </div>

      </div>

    </div>

    <!-- TABS SECTION -->
    <div class="mt-16 fade-up" style="animation-delay:0.2s">

      <div class="flex items-center gap-2 mb-6">
        <button class="tab-btn active" onclick="switchTab('desc', this)">Description</button>
        <button class="tab-btn" onclick="switchTab('specs', this)">Specifications</button>
        <button class="tab-btn" onclick="switchTab('reviews', this)">Reviews</button>
      </div>

      <div id="tab-desc" class="bg-surface-800 rounded-2xl border border-white/[0.04] p-8">
        <h3 class="text-lg font-bold text-white mb-4">Product Description</h3>
        <div class="text-sm text-white/35 leading-relaxed space-y-3">
          <p><?= htmlspecialchars($product['description'] ?? 'Premium quality product engineered for exceptional performance. Designed with attention to detail, built to last, and crafted for those who demand the very best.') ?></p>
          <p>Experience unmatched audio quality with advanced driver technology that delivers deep, punchy bass and crystal-clear highs. The ergonomic design ensures all-day comfort, while the premium materials guarantee durability that stands the test of time.</p>
          <p>Whether you're commuting, working out, or relaxing at home, this product adapts to your lifestyle with seamless connectivity and intuitive controls.</p>
        </div>
      </div>

      <div id="tab-specs" class="hidden bg-surface-800 rounded-2xl border border-white/[0.04] p-8">
        <h3 class="text-lg font-bold text-white mb-4">Specifications</h3>
        <div class="space-y-0 divide-y divide-white/[0.04]">
          <?php
            $specs = [
              ['Driver Size', '40mm Dynamic'],
              ['Frequency Response', '20Hz - 20kHz'],
              ['Impedance', '32 Ohm'],
              ['Sensitivity', '98 dB/mW'],
              ['Battery Life', 'Up to 30 hours'],
              ['Charging Time', '2 hours (USB-C)'],
              ['Bluetooth', '5.3 with multipoint'],
              ['Weight', '250g'],
              ['Noise Cancellation', 'Active (ANC)'],
              ['Water Resistance', 'IPX4'],
            ];
            foreach ($specs as $spec) {
          ?>
          <div class="flex items-center justify-between py-3.5">
            <span class="text-sm text-white/30"><?= $spec[0] ?></span>
            <span class="text-sm font-semibold text-white/70"><?= $spec[1] ?></span>
          </div>
          <?php } ?>
        </div>
      </div>

      <div id="tab-reviews" class="hidden bg-surface-800 rounded-2xl border border-white/[0.04] p-8">
        <h3 class="text-lg font-bold text-white mb-6">Customer Reviews</h3>
        <div class="space-y-6">
          <?php
            $reviews = [
              ['Ahmed K.', 5, 'Absolutely love the sound quality! Bass is deep and the mids are crystal clear. Best purchase this year.', '2 days ago'],
              ['Sara M.', 4, 'Great build quality and very comfortable for long sessions. Only wish the case was a bit smaller.', '1 week ago'],
              ['Bilal R.', 5, 'Noise cancellation is top-notch. I use these in the office and they block out everything. Worth every rupee.', '2 weeks ago'],
            ];
            foreach ($reviews as $rev) {
              $stars = '';
              for ($s = 0; $s < 5; $s++) {
                $stars .= $s < $rev[1]
                  ? '<i class="fa-solid fa-star text-xs star"></i>'
                  : '<i class="fa-solid fa-star text-xs star-empty"></i>';
              }
          ?>
          <div class="flex gap-4">
            <div class="w-10 h-10 rounded-xl bg-surface-600 flex items-center justify-center shrink-0 text-sm font-bold text-gold-400">
              <?= $rev[0][0] ?>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-1">
                <span class="text-sm font-semibold text-white"><?= $rev[0] ?></span>
                <div class="flex items-center gap-0.5"><?= $stars ?></div>
                <span class="text-xs text-white/20"><?= $rev[3] ?></span>
              </div>
              <p class="text-sm text-white/30 leading-relaxed"><?= $rev[2] ?></p>
            </div>
          </div>
          <?php } ?>
        </div>
      </div>

    </div>

    <!-- RELATED PRODUCTS -->
    <?php if (mysqli_num_rows($related_result) > 0) { ?>

    <div class="mt-16 fade-up" style="animation-delay:0.25s">

      <div class="flex items-end justify-between mb-8">
        <div>
          <h2 class="text-xl font-extrabold text-white tracking-tight">You May Also Like</h2>
          <p class="text-sm text-white/25 mt-1">Similar products in <?= htmlspecialchars($product['category_name']) ?></p>
        </div>
        <a href="?cat_id=<?= $product['category_id'] ?>" class="bc-link text-xs font-semibold flex items-center gap-1">
          View All <i class="fa-solid fa-arrow-right text-[9px]"></i>
        </a>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">

        <?php while ($rel = mysqli_fetch_assoc($related_result)) { ?>

        <a href="product_detail.php?id=<?= $rel['id'] ?>" class="rel-card text-decoration-none">
          <div class="overflow-hidden">
            <img
              src="../backend/uploads/<?= $rel['image'] ?>"
              alt="<?= htmlspecialchars($rel['product_name']) ?>"
              onerror="this.src='https://picsum.photos/seed/<?= $rel['id'] ?>/400/300.jpg'"
            >
          </div>
          <div class="p-4">
            <h4 class="text-sm font-bold text-white truncate"><?= htmlspecialchars($rel['product_name']) ?></h4>
            <p class="text-base font-extrabold text-gold-400 mt-1.5">Rs. <?= number_format($rel['price'], 0) ?></p>
          </div>
        </a>

        <?php } ?>

      </div>

    </div>

    <?php } ?>

  </div>

</main>


<!-- ========== FOOTER ========== -->
<footer class="bg-surface-800 border-t border-white/[0.03] py-8">
  <div class="max-w-7xl mx-auto px-6 lg:px-10 flex flex-col sm:flex-row items-center justify-between gap-3">
    <a href="index.php" class="flex items-center gap-2 text-decoration-none">
      <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center">
        <i class="fa-solid fa-headphones text-surface-900 text-[10px]"></i>
      </div>
      <span class="text-sm font-extrabold text-white">Beats<span class="text-gold-400">Shop</span></span>
    </a>
    <p class="text-xs text-white/15">&copy; <?= date('Y') ?> BeatsShop. All rights reserved.</p>
  </div>
</footer>


<!-- ========== SCRIPTS ========== -->
<script>

/* Thumbnail Switch */
function changeThumb(el, src) {
  document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  const mainImg = document.getElementById('mainImg');
  mainImg.style.opacity = '0';
  setTimeout(() => {
    mainImg.src = src;
    mainImg.style.opacity = '1';
  }, 150);
  mainImg.style.transition = 'opacity 0.15s ease';
}

/* Quantity */
let qty = 1;
function changeQty(delta) {
  qty = Math.max(1, Math.min(10, qty + delta));
  document.getElementById('qtyValue').textContent = qty;
}

/* Toast */
function showToast() {
  const toast = document.getElementById('toast');
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2500);
}

/* Tabs */
function switchTab(tab, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['desc', 'specs', 'reviews'].forEach(t => {
    document.getElementById('tab-' + t).classList.toggle('hidden', t !== tab);
  });
}

/* Intersection Observer */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) entry.target.style.animationPlayState = 'running';
  });
}, { threshold: 0.1 });

document.querySelectorAll('.fade-up').forEach(el => {
  el.style.animationPlayState = 'paused';
  observer.observe(el);
});

/* Navbar scroll effect */
window.addEventListener('scroll', function() {
  const nav = document.querySelector('.nav-blur');
  if (window.scrollY > 50) {
    nav.style.background = 'rgba(10,10,15,0.9)';
  } else {
    nav.style.background = 'rgba(10,10,15,0.75)';
  }
});
</script>

</body>
</html>