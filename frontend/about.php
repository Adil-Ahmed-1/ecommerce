<?php
session_start();
include("../backend/config/db.php");

 $user = null;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $u_res = mysqli_query($conn, "SELECT id, name, email, image FROM users WHERE id = $uid LIMIT 1");
    $user = mysqli_fetch_assoc($u_res);
}

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
    $row = mysqli_fetch_assoc($res);
    $count = $row['total'] ?? 0;
} else {
    $count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — BeatsShop</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        gold: { 50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',800:'#92400e',900:'#78350f' },
        surface: { 900:'#0a0a0f',800:'#101018',700:'#16161f',600:'#1c1c28',500:'#222230',400:'#2a2a3a',300:'#35354a' }
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

  .nav-blur { background:rgba(10,10,15,0.75); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border-bottom:1px solid rgba(255,255,255,0.04); }

  .profile-dropdown {
    position:absolute;top:calc(100% + 8px);right:0;width:260px;background:#16161f;
    border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:0;
    opacity:0;visibility:hidden;transform:translateY(-8px) scale(0.97);
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    box-shadow:0 25px 60px -12px rgba(0,0,0,0.7);z-index:999;overflow:hidden;
  }
  .profile-dropdown.open { opacity:1;visibility:visible;transform:translateY(0) scale(1); }
  .profile-dropdown::before {
    content:'';position:absolute;top:-6px;right:16px;width:12px;height:12px;
    background:#16161f;border-left:1px solid rgba(255,255,255,0.08);
    border-top:1px solid rgba(255,255,255,0.08);transform:rotate(45deg);
  }
  .dropdown-item {
    display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:0.82rem;
    font-weight:500;color:rgba(255,255,255,0.55);text-decoration:none;
    transition:all 0.15s ease;cursor:pointer;border:none;background:none;width:100%;text-align:left;
  }
  .dropdown-item:hover { background:rgba(255,255,255,0.04); color:#fff; }
  .dropdown-item i { width:18px;text-align:center;font-size:0.78rem; }
  .dropdown-divider { height:1px;background:rgba(255,255,255,0.06);margin:4px 0; }
  .dropdown-item.danger:hover { background:rgba(239,68,68,0.08); color:#f87171; }

  .btn-login {
    display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:12px;
    font-size:0.82rem;font-weight:700;background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;text-decoration:none;transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;
  }
  .btn-login:hover { box-shadow:0 6px 24px -4px rgba(251,191,36,0.5); transform:translateY(-1px); }

  .profile-avatar {
    width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;
    font-weight:800;font-size:0.82rem;cursor:pointer;transition:all 0.2s ease;
    position:relative;overflow:hidden;border:2px solid transparent;
  }
  .profile-avatar:hover { border-color:rgba(251,191,36,0.4); box-shadow:0 0 20px -4px rgba(251,191,36,0.2); }
  .profile-avatar img { width:100%;height:100%;object-fit:cover;border-radius:10px; }
  .online-dot { position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;background:#22c55e;border:2px solid #0a0a0f; }

  .cart-pulse { animation:cartPop 0.3s cubic-bezier(.4,0,.2,1); }
  @keyframes cartPop { 0%{transform:scale(1)} 50%{transform:scale(1.4)} 100%{transform:scale(1)} }

  .footer-link { color:rgba(255,255,255,0.35);font-size:0.85rem;text-decoration:none;transition:color 0.2s;display:block;padding:4px 0; }
  .footer-link:hover { color:#fbbf24; }

  .about-orb {
    position:absolute;border-radius:50%;filter:blur(120px);opacity:0.35;pointer-events:none;
  }
  .about-orb-1 {
    width:500px;height:500px;top:-150px;right:-100px;
    background:radial-gradient(circle,rgba(251,191,36,0.2),transparent 70%);
    animation:orbDrift 12s ease-in-out infinite alternate;
  }
  .about-orb-2 {
    width:350px;height:350px;bottom:-80px;left:-60px;
    background:radial-gradient(circle,rgba(245,158,11,0.12),transparent 70%);
    animation:orbDrift 16s ease-in-out infinite alternate-reverse;
  }
  @keyframes orbDrift {
    0%{transform:translate(0,0) scale(1)} 50%{transform:translate(25px,-25px) scale(1.06)} 100%{transform:translate(-15px,15px) scale(0.94)}
  }

  .text-shimmer {
    background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24);
    background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;animation:shimmer 3s linear infinite;
  }
  @keyframes shimmer { to{background-position:200% center} }

  .fade-up { opacity:0;transform:translateY(24px);animation:fadeUp 0.65s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes fadeUp { to{opacity:1;transform:translateY(0)} }

  .value-card {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:20px;
    padding:32px 28px;transition:all 0.4s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;
  }
  .value-card::before {
    content:'';position:absolute;inset:0;border-radius:20px;
    background:linear-gradient(135deg,rgba(251,191,36,0.06),transparent 60%);
    opacity:0;transition:opacity 0.4s ease;pointer-events:none;
  }
  .value-card:hover {
    transform:translateY(-6px);border-color:rgba(251,191,36,0.12);
    box-shadow:0 20px 50px -15px rgba(0,0,0,0.5),0 0 40px -10px rgba(251,191,36,0.06);
  }
  .value-card:hover::before { opacity:1; }

  .value-icon {
    width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;
    font-size:1.3rem;transition:all 0.3s ease;
  }
  .value-card:hover .value-icon { transform:scale(1.08); }

  .timeline-line {
    position:absolute;left:23px;top:0;bottom:0;width:2px;
    background:linear-gradient(180deg,rgba(251,191,36,0.3),rgba(251,191,36,0.05));
  }
  .timeline-dot {
    width:12px;height:12px;border-radius:50%;background:#fbbf24;
    border:3px solid #0a0a0f;position:absolute;left:18px;top:6px;z-index:2;
    box-shadow:0 0 12px rgba(251,191,36,0.3);
  }

  .team-card {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:20px;
    padding:28px 24px;text-align:center;transition:all 0.4s cubic-bezier(.4,0,.2,1);
    overflow:hidden;position:relative;
  }
  .team-card::before {
    content:'';position:absolute;top:-60px;left:50%;transform:translateX(-50%);
    width:200px;height:200px;border-radius:50%;
    background:radial-gradient(circle,rgba(251,191,36,0.08),transparent 70%);
    opacity:0;transition:opacity 0.4s ease;pointer-events:none;
  }
  .team-card:hover {
    transform:translateY(-6px);border-color:rgba(251,191,36,0.12);
    box-shadow:0 20px 50px -15px rgba(0,0,0,0.5);
  }
  .team-card:hover::before { opacity:1; }

  .team-avatar {
    width:80px;height:80px;border-radius:20px;margin:0 auto 16px;
    display:flex;align-items:center;justify-content:center;
    font-size:1.6rem;font-weight:800;overflow:hidden;
    border:2px solid rgba(255,255,255,0.06);transition:all 0.3s ease;
  }
  .team-card:hover .team-avatar {
    border-color:rgba(251,191,36,0.3);
    box-shadow:0 0 24px -4px rgba(251,191,36,0.15);
  }

  .stat-block {
    text-align:center;padding:24px 16px;
    border-right:1px solid rgba(255,255,255,0.05);
  }
  .stat-block:last-child { border-right:none; }
  .stat-number {
    font-size:2.2rem;font-weight:800;line-height:1;
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  }

  .cta-btn {
    display:inline-flex;align-items:center;gap:10px;padding:14px 32px;border-radius:14px;
    font-size:0.9rem;font-weight:700;background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;text-decoration:none;transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;border:none;cursor:pointer;
  }
  .cta-btn::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);
    transition:left 0.5s ease;
  }
  .cta-btn:hover::before { left:100%; }
  .cta-btn:hover { box-shadow:0 8px 30px -6px rgba(251,191,36,0.5); transform:translateY(-2px); }

  .ghost-btn {
    display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:14px;
    font-size:0.9rem;font-weight:700;background:transparent;
    border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.6);
    text-decoration:none;transition:all 0.25s ease;cursor:pointer;
  }
  .ghost-btn:hover { background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.2); color:#fff; }

  .img-float { animation:imgFloat 5s ease-in-out infinite; }
  @keyframes imgFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
</style>
</head>

<body>

<!-- ========== NAVBAR ========== -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50 px-6 lg:px-10 py-4">
  <div class="max-w-7xl mx-auto flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-3" style="text-decoration:none">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-lg shadow-gold-500/20">
        <i class="fa-solid fa-headphones text-surface-900 text-sm"></i>
      </div>
      <span class="text-lg font-extrabold text-white tracking-tight">Beats<span class="text-gold-400">Shop</span></span>
    </a>
    <div class="hidden md:flex items-center gap-8">
      <a href="index.php" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">Home</a>
      <a href="index.php#products" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">Products</a>
      <a href="about.php" class="text-sm font-medium text-gold-400">About</a>
      <a href="contact.php" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">Contact</a>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
      <a href="cart.php" class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60 hover:text-white">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($count > 0) { ?>
          <span class="cart-pulse absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $count ?></span>
        <?php } ?>
      </a>
      <?php if ($user): ?>
        <div class="relative" id="profileWrap">
          <div class="profile-avatar bg-gradient-to-br from-gold-400/20 to-gold-600/10 text-gold-400" onclick="toggleProfile()">
            <?php if (!empty($user['image']) && file_exists("../backend/uploads/" . $user['image'])): ?>
              <img src="../backend/uploads/<?= $user['image'] ?>" alt="<?= htmlspecialchars($user['name']) ?>">
            <?php else: ?>
              <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
            <?php endif; ?>
            <span class="online-dot"></span>
          </div>
          <div class="profile-dropdown" id="profileDropdown">
            <div class="px-4 py-3.5 bg-gradient-to-r from-gold-500/5 to-transparent">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400/20 to-gold-600/10 flex items-center justify-center text-gold-400 font-extrabold text-sm shrink-0 overflow-hidden">
                  <?php if (!empty($user['image']) && file_exists("../backend/uploads/" . $user['image'])): ?>
                    <img src="../backend/uploads/<?= $user['image'] ?>" class="w-full h-full object-cover rounded-[10px]" alt="">
                  <?php else: ?>
                    <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
                  <?php endif; ?>
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($user['name']) ?></p>
                  <p class="text-[11px] text-white/30 truncate"><?= htmlspecialchars($user['email']) ?></p>
                </div>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <div class="py-1.5">
              <a href="#" class="dropdown-item"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
              <a href="#" class="dropdown-item"><i class="fa-solid fa-box"></i><span>My Orders</span></a>
              <a href="cart.php" class="dropdown-item"><i class="fa-solid fa-bag-shopping"></i><span>My Cart</span></a>
            </div>
            <div class="dropdown-divider"></div>
            <div class="py-1.5">
              <a href="../user/logout.php" class="dropdown-item danger"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <a href="../user/login.php" class="btn-login"><i class="fa-solid fa-right-to-bracket text-xs"></i><span class="hidden sm:inline">Login</span></a>
      <?php endif; ?>
      <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="md:hidden w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60">
        <i class="fa-solid fa-bars text-sm"></i>
      </button>
    </div>
  </div>
  <div id="mobileMenu" class="hidden md:hidden mt-4 pb-2 border-t border-white/5 pt-4">
    <a href="index.php" class="block py-2.5 text-sm font-medium text-white/50">Home</a>
    <a href="index.php#products" class="block py-2.5 text-sm font-medium text-white/50">Products</a>
    <a href="about.php" class="block py-2.5 text-sm font-medium text-gold-400">About</a>
    <a href="contact.php" class="block py-2.5 text-sm font-medium text-white/50">Contact</a>
  </div>
</nav>

<!-- ========== HERO SECTION ========== -->
<section class="relative min-h-[70vh] flex items-center overflow-hidden pt-20">
  <div class="about-orb about-orb-1"></div>
  <div class="about-orb about-orb-2"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-10 w-full">
    <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
      <div class="flex-1 text-center lg:text-left fade-up">
        <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-6">
          <i class="fa-solid fa-heart text-gold-400 text-xs"></i>
          <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Our Story</span>
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.1] tracking-tight">
          Born From a<br><span class="text-shimmer">Love of Sound</span>
        </h1>
        <p class="text-white/40 mt-5 text-base lg:text-lg max-w-lg mx-auto lg:mx-0 leading-relaxed">
          We started BeatsShop with one mission — to bring premium, studio-grade audio to everyone. No compromises. No overpricing. Just pure, uncompromised sound.
        </p>
        <div class="flex items-center gap-4 mt-8 justify-center lg:justify-start">
          <a href="contact.php" class="cta-btn"><i class="fa-solid fa-envelope text-xs"></i> Get in Touch</a>
          <a href="index.php#products" class="ghost-btn"><i class="fa-solid fa-headphones text-xs"></i> Browse Products</a>
        </div>
      </div>
      <div class="flex-1 flex justify-center fade-up" style="animation-delay:0.15s">
        <div class="relative">
          <div class="absolute inset-0 bg-gradient-to-br from-gold-400/15 to-transparent rounded-full blur-3xl scale-75"></div>
          <img src="../backend/uploads/image5.png" alt="About BeatsShop" class="img-float relative w-72 lg:w-[400px] drop-shadow-2xl" onerror="this.src='https://picsum.photos/seed/about-hero/500/500.jpg'">
          <!-- Floating badges -->
          <div class="absolute -left-6 top-1/4 bg-surface-700 border border-white/[0.06] rounded-2xl px-4 py-3 shadow-2xl" style="animation:imgFloat 4s ease-in-out infinite 0.5s">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-green-500/15 flex items-center justify-center"><i class="fa-solid fa-truck-fast text-green-400 text-xs"></i></div>
              <div><p class="text-[10px] font-bold text-white/80">Free Delivery</p><p class="text-[9px] text-white/30">All over Pakistan</p></div>
            </div>
          </div>
          <div class="absolute -right-4 bottom-1/4 bg-surface-700 border border-white/[0.06] rounded-2xl px-4 py-3 shadow-2xl" style="animation:imgFloat 4.5s ease-in-out infinite 1s">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-gold-500/15 flex items-center justify-center"><i class="fa-solid fa-shield-check text-gold-400 text-xs"></i></div>
              <div><p class="text-[10px] font-bold text-white/80">1 Year Warranty</p><p class="text-[9px] text-white/30">On all products</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== STATS BAR ========== -->
<section class="py-4 relative">
  <div class="max-w-5xl mx-auto px-6 lg:px-10">
    <div class="bg-surface-800 border border-white/[0.05] rounded-2xl overflow-hidden fade-up" style="animation-delay:0.1s">
      <div class="grid grid-cols-2 md:grid-cols-4">
        <div class="stat-block">
          <p class="stat-number">50K+</p>
          <p class="text-xs text-white/30 mt-1.5 font-medium">Happy Customers</p>
        </div>
        <div class="stat-block">
          <p class="stat-number">200+</p>
          <p class="text-xs text-white/30 mt-1.5 font-medium">Products Listed</p>
        </div>
        <div class="stat-block">
          <p class="stat-number">4.9★</p>
          <p class="text-xs text-white/30 mt-1.5 font-medium">Average Rating</p>
        </div>
        <div class="stat-block">
          <p class="stat-number">3+</p>
          <p class="text-xs text-white/30 mt-1.5 font-medium">Years of Trust</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== OUR VALUES ========== -->
<section class="py-20 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="text-center mb-14 fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fa-solid fa-gem text-gold-400 text-[10px]"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Why Choose Us</span>
      </div>
      <h2 class="text-3xl font-extrabold text-white tracking-tight">Built on <span class="text-shimmer">Core Values</span></h2>
      <p class="text-sm text-white/30 mt-3 max-w-md mx-auto">Every decision we make is guided by these principles — because great audio deserves great integrity.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Value 1 -->
      <div class="value-card fade-up" style="animation-delay:0.05s">
        <div class="value-icon bg-gold-500/10 text-gold-400 mb-5"><i class="fa-solid fa-headphones-simple"></i></div>
        <h3 class="text-base font-bold text-white mb-2">Premium Sound</h3>
        <p class="text-xs text-white/35 leading-relaxed">Every product is hand-tested for audio quality. We only stock gear that meets our strict benchmark for clarity, bass, and fidelity.</p>
      </div>
      <!-- Value 2 -->
      <div class="value-card fade-up" style="animation-delay:0.1s">
        <div class="value-icon bg-blue-500/10 text-blue-400 mb-5"><i class="fa-solid fa-tags"></i></div>
        <h3 class="text-base font-bold text-white mb-2">Fair Pricing</h3>
        <p class="text-xs text-white/35 leading-relaxed">No middlemen, no inflated markups. We source directly and pass the savings to you — premium audio shouldn't break the bank.</p>
      </div>
      <!-- Value 3 -->
      <div class="value-card fade-up" style="animation-delay:0.15s">
        <div class="value-icon bg-green-500/10 text-green-400 mb-5"><i class="fa-solid fa-handshake-angle"></i></div>
        <h3 class="text-base font-bold text-white mb-2">Customer First</h3>
        <p class="text-xs text-white/35 leading-relaxed">From pre-sale guidance to post-sale support, our team is available around the clock. Your satisfaction isn't a goal — it's our standard.</p>
      </div>
      <!-- Value 4 -->
      <div class="value-card fade-up" style="animation-delay:0.2s">
        <div class="value-icon bg-purple-500/10 text-purple-400 mb-5"><i class="fa-solid fa-rotate-left"></i></div>
        <h3 class="text-base font-bold text-white mb-2">Easy Returns</h3>
        <p class="text-xs text-white/35 leading-relaxed">Not satisfied? Return within 7 days for a full refund, no questions asked. We stand behind every product we sell.</p>
      </div>
    </div>
  </div>
</section>

<!-- ========== OUR JOURNEY TIMELINE ========== -->
<section class="py-20 relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-b from-transparent via-gold-500/[0.02] to-transparent pointer-events-none"></div>
  <div class="max-w-3xl mx-auto px-6 lg:px-10 relative z-10">
    <div class="text-center mb-14 fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fa-solid fa-road text-gold-400 text-[10px]"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Our Journey</span>
      </div>
      <h2 class="text-3xl font-extrabold text-white tracking-tight">From <span class="text-shimmer">Passion to Purpose</span></h2>
    </div>
    <div class="relative pl-16">
      <div class="timeline-line"></div>

      <!-- 2021 -->
      <div class="relative pb-12 fade-up" style="animation-delay:0.05s">
        <div class="timeline-dot"></div>
        <div class="bg-surface-800 border border-white/[0.05] rounded-2xl p-6">
          <span class="text-xs font-extrabold text-gold-400 uppercase tracking-wider">2021</span>
          <h3 class="text-sm font-bold text-white mt-2">The Spark</h3>
          <p class="text-xs text-white/35 mt-1.5 leading-relaxed">Frustrated by overpriced audio gear, our founder started BeatsShop from a small room in Karachi with just 15 products and a big dream.</p>
        </div>
      </div>

      <!-- 2022 -->
      <div class="relative pb-12 fade-up" style="animation-delay:0.1s">
        <div class="timeline-dot"></div>
        <div class="bg-surface-800 border border-white/[0.05] rounded-2xl p-6">
          <span class="text-xs font-extrabold text-gold-400 uppercase tracking-wider">2022</span>
          <h3 class="text-sm font-bold text-white mt-2">First 10K Customers</h3>
          <p class="text-xs text-white/35 mt-1.5 leading-relaxed">Word spread fast. Quality spoke for itself. Within a year, we crossed 10,000 happy customers and expanded to 80+ products across all categories.</p>
        </div>
      </div>

      <!-- 2023 -->
      <div class="relative pb-12 fade-up" style="animation-delay:0.15s">
        <div class="timeline-dot"></div>
        <div class="bg-surface-800 border border-white/[0.05] rounded-2xl p-6">
          <span class="text-xs font-extrabold text-gold-400 uppercase tracking-wider">2023</span>
          <h3 class="text-sm font-bold text-white mt-2">Going Nationwide</h3>
          <p class="text-xs text-white/35 mt-1.5 leading-relaxed">Partnered with top courier services to deliver free across Pakistan. Launched our warranty program and customer support hotline.</p>
        </div>
      </div>

      <!-- 2024 -->
      <div class="relative pb-12 fade-up" style="animation-delay:0.2s">
        <div class="timeline-dot"></div>
        <div class="bg-surface-800 border border-white/[0.05] rounded-2xl p-6">
          <span class="text-xs font-extrabold text-gold-400 uppercase tracking-wider">2024</span>
          <h3 class="text-sm font-bold text-white mt-2">200+ Products & Growing</h3>
          <p class="text-xs text-white/35 mt-1.5 leading-relaxed">Hit the 200-product milestone with headphones, earbuds, speakers, and accessories. Introduced a full review system and community features.</p>
        </div>
      </div>

      <!-- 2025 -->
      <div class="relative fade-up" style="animation-delay:0.25s">
        <div class="timeline-dot" style="background:#22c55e;box-shadow:0 0 12px rgba(34,197,94,0.4)"></div>
        <div class="bg-surface-800 border border-green-500/10 rounded-2xl p-6">
          <span class="text-xs font-extrabold text-green-400 uppercase tracking-wider">2025 — Now</span>
          <h3 class="text-sm font-bold text-white mt-2">The Future is Loud</h3>
          <p class="text-xs text-white/35 mt-1.5 leading-relaxed">50K+ customers strong and counting. We're expanding into smart audio and wireless ecosystems. The beat goes on.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== TEAM ========== -->
<section class="py-20 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="text-center mb-14 fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fa-solid fa-users text-gold-400 text-[10px]"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">The Team</span>
      </div>
      <h2 class="text-3xl font-extrabold text-white tracking-tight">Meet the <span class="text-shimmer">People Behind</span></h2>
      <p class="text-sm text-white/30 mt-3 max-w-md mx-auto">A small, passionate team obsessed with audio and customer experience.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-4xl mx-auto">
      <!-- Member 1 -->
      <div class="team-card fade-up" style="animation-delay:0.05s">
        <div class="team-avatar bg-gradient-to-br from-gold-400/20 to-gold-600/10 text-gold-400">A</div>
        <h3 class="text-sm font-bold text-white">Ahmed Adil</h3>
        <p class="text-xs text-gold-400/70 font-semibold mt-0.5">Founder & CEO</p>
        <p class="text-[11px] text-white/25 mt-3 leading-relaxed">Audio enthusiast turned entrepreneur. Built BeatsShop from the ground up with a vision for accessible premium sound.</p>
        <div class="flex items-center justify-center gap-3 mt-4">
          <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-gold-500/10 flex items-center justify-center text-white/25 hover:text-gold-400 transition"><i class="fa-brands fa-instagram text-xs"></i></a>
          <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-gold-500/10 flex items-center justify-center text-white/25 hover:text-gold-400 transition"><i class="fa-brands fa-linkedin-in text-xs"></i></a>
          <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-gold-500/10 flex items-center justify-center text-white/25 hover:text-gold-400 transition"><i class="fa-brands fa-twitter text-xs"></i></a>
        </div>
      </div>
      <!-- Member 2 -->
      <div class="team-card fade-up" style="animation-delay:0.1s">
        <div class="team-avatar bg-gradient-to-br from-blue-400/20 to-blue-600/10 text-blue-400">S</div>
        <h3 class="text-sm font-bold text-white">Sarah Khan</h3>
        <p class="text-xs text-blue-400/70 font-semibold mt-0.5">Head of Operations</p>
        <p class="text-[11px] text-white/25 mt-3 leading-relaxed">Ensures every order is packed with care and delivered on time. She's the reason our logistics run like clockwork.</p>
        <div class="flex items-center justify-center gap-3 mt-4">
          <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-blue-500/10 flex items-center justify-center text-white/25 hover:text-blue-400 transition"><i class="fa-brands fa-instagram text-xs"></i></a>
          <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-blue-500/10 flex items-center justify-center text-white/25 hover:text-blue-400 transition"><i class="fa-brands fa-linkedin-in text-xs"></i></a>
        </div>
      </div>
      <!-- Member 3 -->
      <div class="team-card fade-up" style="animation-delay:0.15s">
        <div class="team-avatar bg-gradient-to-br from-purple-400/20 to-purple-600/10 text-purple-400">R</div>
        <h3 class="text-sm font-bold text-white">Raza Hussain</h3>
        <p class="text-xs text-purple-400/70 font-semibold mt-0.5">Product Curator</p>
        <p class="text-[11px] text-white/25 mt-3 leading-relaxed">Tests every single product before it hits the store. If it doesn't pass his ear test, it doesn't make the cut.</p>
        <div class="flex items-center justify-center gap-3 mt-4">
          <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-purple-500/10 flex items-center justify-center text-white/25 hover:text-purple-400 transition"><i class="fa-brands fa-instagram text-xs"></i></a>
          <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-purple-500/10 flex items-center justify-center text-white/25 hover:text-purple-400 transition"><i class="fa-brands fa-twitter text-xs"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== CTA ========== -->
<section class="py-20 relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-r from-gold-500/5 via-gold-500/[0.02] to-transparent pointer-events-none"></div>
  <div class="max-w-3xl mx-auto px-6 lg:px-10 text-center relative z-10 fade-up">
    <h2 class="text-3xl font-extrabold text-white tracking-tight">Ready to <span class="text-shimmer">Upgrade Your Sound?</span></h2>
    <p class="text-sm text-white/30 mt-4 max-w-md mx-auto leading-relaxed">Join 50,000+ happy customers who trust BeatsShop for their audio needs. Your ears will thank you.</p>
    <div class="flex items-center gap-4 mt-8 justify-center flex-wrap">
      <a href="index.php#products" class="cta-btn"><i class="fa-solid fa-bag-shopping text-xs"></i> Shop Now</a>
      <a href="contact.php" class="ghost-btn"><i class="fa-solid fa-message text-xs"></i> Contact Us</a>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="bg-surface-800 border-t border-white/[0.03] pt-14 pb-8">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">
      <div class="col-span-2 md:col-span-1">
        <a href="index.php" class="flex items-center gap-3 mb-4" style="text-decoration:none">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center"><i class="fa-solid fa-headphones text-surface-900 text-xs"></i></div>
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
        <a href="index.php#products" class="footer-link">Products</a>
        <a href="about.php" class="footer-link">About Us</a>
        <a href="contact.php" class="footer-link">Contact Us</a>
      </div>
      <div>
        <p class="text-xs font-bold text-white/50 uppercase tracking-wider mb-4">Support</p>
        <a href="contact.php" class="footer-link">Help Center</a>
        <a href="#" class="footer-link">Shipping Info</a>
        <a href="#" class="footer-link">Returns & Refunds</a>
        <a href="#" class="footer-link">Warranty Policy</a>
      </div>
      <div>
        <p class="text-xs font-bold text-white/50 uppercase tracking-wider mb-4">Contact</p>
        <p class="footer-link"><i class="fa-solid fa-envelope text-[10px] mr-2 text-gold-500/70"></i>ahmedadilbaloch95@gmail.com</p>
        <p class="footer-link"><i class="fa-solid fa-phone text-[10px] mr-2 text-gold-500/70"></i>+92 323 3703689</p>
        <p class="footer-link"><i class="fa-solid fa-location-dot text-[10px] mr-2 text-gold-500/70"></i>Karachi, Pakistan</p>
      </div>
    </div>
    <div class="border-t border-white/[0.03] pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-xs text-white/50">&copy; <?= date('Y') ?> BeatsShop. All rights reserved.</p>
      <div class="flex items-center gap-4">
        <a href="#" class="text-xs text-white/50 hover:text-white/40 transition">Privacy Policy</a>
        <a href="#" class="text-xs text-white/50 hover:text-white/40 transition">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>

<script>
var profileOpen = false;
function toggleProfile() {
  profileOpen = !profileOpen;
  document.getElementById('profileDropdown').classList.toggle('open', profileOpen);
}
document.addEventListener('click', function(e) {
  var wrap = document.getElementById('profileWrap');
  if (wrap && !wrap.contains(e.target)) {
    profileOpen = false;
    document.getElementById('profileDropdown').classList.remove('open');
  }
});

var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) entry.target.style.animationPlayState = 'running';
  });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(function(el) {
  el.style.animationPlayState = 'paused';
  observer.observe(el);
});

window.addEventListener('scroll', function() {
  var nav = document.querySelector('.nav-blur');
  if (window.scrollY > 50) {
    nav.style.background = 'rgba(10,10,15,0.9)';
    nav.style.borderBottomColor = 'rgba(255,255,255,0.06)';
  } else {
    nav.style.background = 'rgba(10,10,15,0.75)';
    nav.style.borderBottomColor = 'rgba(255,255,255,0.04)';
  }
});
</script>
</body>
</html>