<?php
session_start();
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

 $specStmt = mysqli_prepare($conn, "SELECT spec_name, spec_value FROM product_specifications WHERE product_id = ? ORDER BY id ASC");
mysqli_stmt_bind_param($specStmt, "i", $id);
mysqli_stmt_execute($specStmt);
 $specResult = mysqli_stmt_get_result($specStmt);
 $specs = [];
while ($specRow = mysqli_fetch_assoc($specResult)) {
    $specs[] = [$specRow['spec_name'], $specRow['spec_value']];
}

 $reviewStats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(AVG(rating), 0) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE product_id = $id"));
 $avgRating = round(floatval($reviewStats['avg_rating']), 1);
 $totalReviews = intval($reviewStats['total_reviews']);

 $reviewsResult = mysqli_query($conn, "SELECT r.rating, r.comment, r.created_at, u.name as user_name, u.image as user_image, u.id as user_id FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = $id ORDER BY r.created_at DESC");
 $dbReviews = [];
while ($rev = mysqli_fetch_assoc($reviewsResult)) {
    $dbReviews[] = $rev;
}

 $userOwnReview = null;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $ownRes = mysqli_query($conn, "SELECT rating, comment, created_at FROM reviews WHERE user_id = $uid AND product_id = $id LIMIT 1");
    if ($ownRes && mysqli_num_rows($ownRes) > 0) {
        $userOwnReview = mysqli_fetch_assoc($ownRes);
    }
}

 $related_result = mysqli_query($conn, "SELECT * FROM products WHERE category_id = " . intval($product['category_id']) . " AND id != $id ORDER BY RAND() LIMIT 4");

 $count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;

 $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
 $loginPage = "../user/login.php";
 $logoutPage = "../user/logout.php";
 $userName = '';
 $userEmail = '';
 $userInitial = '';
 $userId = 0;
 $userImage = '';

if ($isLoggedIn) {
    $userId = intval($_SESSION['user_id']);
    $uStmt = mysqli_prepare($conn, "SELECT name, email, image FROM users WHERE id = ?");
    mysqli_stmt_bind_param($uStmt, "i", $userId);
    mysqli_stmt_execute($uStmt);
    $uResult = mysqli_stmt_get_result($uStmt);
    if ($uRow = mysqli_fetch_assoc($uResult)) {
        $userName = $uRow['name'];
        $userEmail = $uRow['email'];
        $userImage = $uRow['image'];
        $userInitial = strtoupper(mb_substr(trim($userName), 0, 1));
    }
    if (empty($userInitial)) {
        $userInitial = 'U';
        $userName = 'User';
    }
}

function buildStars($rating, $size = 'text-xs') {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="fa-solid fa-star ' . $size . ' star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fa-solid fa-star-half-stroke ' . $size . ' star"></i>';
        } else {
            $html .= '<i class="fa-solid fa-star ' . $size . ' star-empty"></i>';
        }
    }
    return $html;
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min ago';
    return 'Just now';
}
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
        gold: { 50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',800:'#92400e',900:'#78350f' },
        surface: { 900:'#0a0a0f',800:'#101018',700:'#16161f',600:'#1c1c28',500:'#222230',400:'#2a2a3a',300:'#35354a' }
      }
    }
  }
}
</script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Plus Jakarta Sans',sans-serif;background:#0a0a0f;color:#fff}
  ::-webkit-scrollbar{width:8px}
  ::-webkit-scrollbar-track{background:#0a0a0f}
  ::-webkit-scrollbar-thumb{background:#2a2a3a;border-radius:99px}
  .nav-blur{background:rgba(10,10,15,0.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,0.04)}
  .main-img-wrap{position:relative;border-radius:24px;overflow:hidden;background:#16161f}
  .main-img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(251,191,36,0.06),transparent 50%);pointer-events:none}
  .main-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform 0.6s cubic-bezier(.4,0,.2,1)}
  .main-img-wrap:hover img{transform:scale(1.05)}
  .feat-pill{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:10px 16px;font-size:0.8rem;color:rgba(255,255,255,0.5);transition:all 0.25s ease}
  .feat-pill:hover{background:rgba(251,191,36,0.06);border-color:rgba(251,191,36,0.15);color:#fbbf24}
  .btn-cart{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#0a0a0f;font-weight:700;border:none;border-radius:14px;transition:all 0.25s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden}
  .btn-cart::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);transition:left 0.5s ease}
  .btn-cart:hover::before{left:100%}
  .btn-cart:hover{box-shadow:0 8px 30px -4px rgba(251,191,36,0.5);transform:translateY(-2px)}
  .btn-outline{background:transparent;border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.6);border-radius:14px;font-weight:600;transition:all 0.25s ease;text-decoration:none}
  .btn-outline:hover{background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.2);color:#fff}
  .rel-card{background:#101018;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,0.04);transition:all 0.35s cubic-bezier(.4,0,.2,1);text-decoration:none}
  .rel-card:hover{transform:translateY(-6px);border-color:rgba(251,191,36,0.12);box-shadow:0 16px 40px -12px rgba(0,0,0,0.5)}
  .rel-card img{width:100%;height:180px;object-fit:cover;transition:transform 0.5s ease}
  .rel-card:hover img{transform:scale(1.06)}
  .text-shimmer{background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite}
  @keyframes shimmer{to{background-position:200% center}}
  .fade-up{opacity:0;transform:translateY(20px);animation:fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
  .bc-link{color:rgba(255,255,255,0.3);font-size:0.8rem;text-decoration:none;transition:color 0.2s}
  .bc-link:hover{color:#fbbf24}
  .toast-msg{position:fixed;top:24px;right:24px;z-index:9999;background:#101018;border-radius:16px;padding:16px 20px;box-shadow:0 20px 50px -10px rgba(0,0,0,0.6);opacity:0;transform:translateY(-12px) scale(0.96);transition:all 0.35s cubic-bezier(.4,0,.2,1);pointer-events:none;max-width:360px}
  .toast-msg.show{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
  .toast-msg.toast-success{border:1px solid rgba(251,191,36,0.2)}
  .toast-msg.toast-warning{border:1px solid rgba(251,146,60,0.3)}
  .toast-msg.toast-review-ok{border:1px solid rgba(34,197,94,0.25)}
  .toast-msg.toast-review-err{border:1px solid rgba(239,68,68,0.25)}
  .qty-btn{width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s;font-size:0.9rem}
  .qty-btn:hover{background:rgba(251,191,36,0.1);border-color:rgba(251,191,36,0.3);color:#fbbf24}
  .tab-btn{padding:10px 20px;border-radius:10px;font-size:0.8rem;font-weight:600;color:rgba(255,255,255,0.35);background:transparent;border:1px solid transparent;cursor:pointer;transition:all 0.25s}
  .tab-btn.active{background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.15);color:#fbbf24}
  .tab-btn:hover:not(.active){color:rgba(255,255,255,0.6)}
  .star{color:#fbbf24}
  .star-empty{color:#2a2a3a}
  @keyframes badgePop{0%{transform:scale(1)}50%{transform:scale(1.4)}100%{transform:scale(1)}}
  .badge-pop{animation:badgePop 0.35s ease}
  @keyframes headShake{0%{transform:translateX(0)}6.5%{transform:translateX(-6px) rotateY(-5deg)}18.5%{transform:translateX(5px) rotateY(3.5deg)}31.5%{transform:translateX(-3px) rotateY(-2.5deg)}43.5%{transform:translateX(2px) rotateY(1.5deg)}50%{transform:translateX(0)}}
  .head-shake{animation:headShake 0.6s ease-in-out}
  .spec-row{transition:background 0.2s ease;border-radius:8px;padding-left:8px;padding-right:8px}
  .spec-row:hover{background:rgba(255,255,255,0.02)}
  .spec-dot{width:6px;height:6px;border-radius:50%;background:rgba(251,191,36,0.4);flex-shrink:0}
  .profile-wrap{position:relative}
  .profile-btn{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#fbbf24,#f59e0b);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.25s ease;border:none;font-weight:800;font-size:0.85rem;color:#0a0a0f;box-shadow:0 2px 10px -2px rgba(251,191,36,0.3);overflow:hidden}
  .profile-btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px -2px rgba(251,191,36,0.5)}
  .profile-btn.active{box-shadow:0 0 0 2px #0a0a0f,0 0 0 4px rgba(251,191,36,0.4)}
  .profile-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:260px;background:#16161f;border:1px solid rgba(255,255,255,0.06);border-radius:18px;box-shadow:0 20px 60px -12px rgba(0,0,0,0.7);opacity:0;visibility:hidden;transform:translateY(-8px) scale(0.97);transition:all 0.25s cubic-bezier(.4,0,.2,1);z-index:100;overflow:hidden}
  .profile-dropdown.open{opacity:1;visibility:visible;transform:translateY(0) scale(1)}
  .dropdown-item{display:flex;align-items:center;gap:10px;padding:11px 16px;color:rgba(255,255,255,0.5);font-size:0.8rem;font-weight:500;text-decoration:none;transition:all 0.2s ease;cursor:pointer;border:none;background:none;width:100%;text-align:left}
  .dropdown-item:hover{background:rgba(255,255,255,0.04);color:#fff}
  .dropdown-item i{width:16px;text-align:center;font-size:0.75rem}
  .dropdown-item.item-danger:hover{background:rgba(239,68,68,0.06);color:#ef4444}
  .dropdown-divider{height:1px;background:rgba(255,255,255,0.04);margin:4px 12px}
  .login-nav-btn{display:flex;align-items:center;gap:6px;padding:8px 16px;border-radius:12px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);color:rgba(255,255,255,0.6);font-size:0.8rem;font-weight:600;text-decoration:none;transition:all 0.2s ease}
  .login-nav-btn:hover{background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.2);color:#fbbf24}
  .online-dot{position:absolute;bottom:-1px;right:-1px;width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid #0a0a0f}
  .review-form-wrap{background:linear-gradient(135deg,rgba(251,191,36,0.04),rgba(251,191,36,0.01));border:1px solid rgba(251,191,36,0.1);border-radius:18px;padding:24px;margin-bottom:28px}
  .star-selector{display:flex;align-items:center;gap:6px}
  .star-btn{background:none;border:none;cursor:pointer;padding:4px;font-size:1.6rem;color:#2a2a3a;transition:all 0.15s ease;line-height:1}
  .star-btn:hover{transform:scale(1.2)}
  .star-btn.hovered{color:rgba(251,191,36,0.5)}
  .star-btn.selected{color:#fbbf24;filter:drop-shadow(0 0 8px rgba(251,191,36,0.4))}
  .star-label{font-size:0.8rem;font-weight:700;color:rgba(255,255,255,0.2);margin-left:10px;min-width:90px;transition:all 0.2s ease}
  .star-label.active{color:#fbbf24}
  .review-textarea{width:100%;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:14px 18px;font-size:0.85rem;color:#fff;font-family:'Plus Jakarta Sans',sans-serif;resize:vertical;min-height:100px;max-height:200px;transition:all 0.2s ease;line-height:1.7}
  .review-textarea::placeholder{color:rgba(255,255,255,0.15)}
  .review-textarea:focus{outline:none;border-color:rgba(251,191,36,0.4);box-shadow:0 0 0 3px rgba(251,191,36,0.06);background:rgba(255,255,255,0.04)}
  .btn-submit-review{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:12px;font-size:0.82rem;font-weight:700;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#0a0a0f;border:none;cursor:pointer;transition:all 0.25s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden}
  .btn-submit-review::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);transition:left 0.5s ease}
  .btn-submit-review:hover::before{left:100%}
  .btn-submit-review:hover{box-shadow:0 6px 24px -4px rgba(251,191,36,0.5);transform:translateY(-1px)}
  .btn-submit-review:disabled{opacity:0.3;cursor:not-allowed;transform:none!important;box-shadow:none!important}
  .btn-submit-review:disabled::before{display:none}
  .review-locked{display:flex;align-items:center;gap:14px;padding:20px;background:rgba(255,255,255,0.02);border:1px dashed rgba(255,255,255,0.08);border-radius:16px}
  .review-locked i{font-size:1.4rem;color:rgba(255,255,255,0.1)}
  .review-locked p{font-size:0.85rem;color:rgba(255,255,255,0.3);line-height:1.6}
  .review-locked a{color:#fbbf24;font-weight:700;text-decoration:none;transition:color 0.2s}
  .review-locked a:hover{color:#fde68a}
  .already-reviewed-box{display:flex;align-items:center;gap:14px;padding:20px;background:rgba(34,197,94,0.04);border:1px solid rgba(34,197,94,0.12);border-radius:16px}
  .already-reviewed-box i{font-size:1.3rem;color:#22c55e}
  .already-reviewed-box p{font-size:0.85rem;color:rgba(34,197,94,0.7);font-weight:600}
  .review-item{display:flex;gap:14px;padding:18px 0;border-bottom:1px solid rgba(255,255,255,0.03);transition:background 0.2s ease}
  .review-item:last-child{border-bottom:none}
  .review-avatar{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.8rem;background:linear-gradient(135deg,rgba(251,191,36,0.15),rgba(251,191,36,0.05));color:#fbbf24;flex-shrink:0;overflow:hidden;border:1px solid rgba(255,255,255,0.06)}
  .review-avatar img{width:100%;height:100%;object-fit:cover;border-radius:11px}
  .you-badge{font-size:0.58rem;font-weight:700;color:rgba(251,191,36,0.7);background:rgba(251,191,36,0.08);padding:2px 7px;border-radius:5px;letter-spacing:0.04em}
  .reviews-empty{text-align:center;padding:40px 20px}
  .reviews-empty i{font-size:2.5rem;color:rgba(255,255,255,0.04);margin-bottom:14px;display:block}
  .reviews-empty p{font-size:0.85rem;color:rgba(255,255,255,0.15)}
  .rating-bar-track{width:100%;height:6px;border-radius:99px;background:rgba(255,255,255,0.04);overflow:hidden}
  .rating-bar-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#fbbf24,#f59e0b);transition:width 0.6s cubic-bezier(.4,0,.2,1)}
  @keyframes reviewSlideIn{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
  .review-new{animation:reviewSlideIn 0.4s cubic-bezier(.4,0,.2,1) forwards}
</style>
</head>
<body>

<div id="toastSuccess" class="toast-msg toast-success flex items-center gap-3">
  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shrink-0">
    <i class="fa-solid fa-check text-surface-900 text-sm"></i>
  </div>
  <div>
    <p class="text-sm font-semibold text-white">Added to Cart</p>
    <p class="text-xs text-white/30 mt-0.5">Item has been added successfully</p>
  </div>
</div>

<div id="toastLogin" class="toast-msg toast-warning flex items-center gap-3">
  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shrink-0">
    <i class="fa-solid fa-lock text-surface-900 text-sm"></i>
  </div>
  <div class="flex-1">
    <p class="text-sm font-semibold text-white">Please Login First</p>
    <p class="text-xs text-white/30 mt-0.5">Login to add items to your cart</p>
    <a href="<?= $loginPage ?>" class="inline-flex items-center gap-1.5 mt-2 px-4 py-1.5 bg-gradient-to-r from-orange-400 to-orange-500 text-surface-900 text-xs font-bold rounded-lg hover:shadow-lg hover:shadow-orange-500/30 transition-all">
      <i class="fa-solid fa-right-to-bracket text-[10px]"></i> Login Now
    </a>
  </div>
</div>

<div id="toastReviewOk" class="toast-msg toast-review-ok flex items-center gap-3">
  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shrink-0">
    <i class="fa-solid fa-star text-surface-900 text-sm"></i>
  </div>
  <div>
    <p class="text-sm font-semibold text-white">Review Submitted!</p>
    <p class="text-xs text-white/30 mt-0.5">Thank you for your feedback</p>
  </div>
</div>

<div id="toastReviewErr" class="toast-msg toast-review-err flex items-center gap-3">
  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shrink-0">
    <i class="fa-solid fa-xmark text-surface-900 text-sm"></i>
  </div>
  <div>
    <p class="text-sm font-semibold text-white" id="reviewErrMsg">Failed to submit review</p>
    <p class="text-xs text-white/30 mt-0.5">Please try again</p>
  </div>
</div>

<!-- NAVBAR -->
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
      <a href="cart.php" id="cartIconWrap" class="relative w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60 hover:text-white">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($count > 0): ?>
          <span id="cartBadge" class="absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center"><?= $count ?></span>
        <?php endif; ?>
      </a>
      <?php if ($isLoggedIn): ?>
      <div class="profile-wrap" id="profileWrap">
        <button class="profile-btn" id="profileBtn" onclick="toggleProfile()">
          <?php if (!empty($userImage) && file_exists("../backend/uploads/" . $userImage)): ?>
            <img src="../backend/uploads/<?= $userImage ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px" alt="">
          <?php else: ?>
            <?= $userInitial ?>
          <?php endif; ?>
          <span class="online-dot"></span>
        </button>
        <div class="profile-dropdown" id="profileDropdown">
          <div class="px-4 pt-4 pb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-surface-900 font-extrabold text-sm shrink-0 overflow-hidden">
                <?php if (!empty($userImage) && file_exists("../backend/uploads/" . $userImage)): ?>
                  <img src="../backend/uploads/<?= $userImage ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px" alt="">
                <?php else: ?>
                  <?= $userInitial ?>
                <?php endif; ?>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($userName) ?></p>
                <p class="text-[11px] text-white/25 truncate"><?= htmlspecialchars($userEmail) ?></p>
              </div>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item" onclick="closeProfile()"><i class="fa-solid fa-box"></i><span>My Orders</span></button>
          <button class="dropdown-item" onclick="closeProfile()"><i class="fa-regular fa-heart"></i><span>Wishlist</span></button>
          <button class="dropdown-item" onclick="closeProfile()"><i class="fa-solid fa-gear"></i><span>Settings</span></button>
          <div class="dropdown-divider"></div>
          <a href="<?= $logoutPage ?>" class="dropdown-item item-danger"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </div>
      </div>
      <?php else: ?>
      <a href="<?= $loginPage ?>" class="login-nav-btn">
        <i class="fa-solid fa-right-to-bracket text-xs"></i>
        <span class="hidden sm:inline">Login</span>
      </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- MAIN -->
<main class="pt-24 pb-20">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">

    <div class="flex items-center gap-2 mb-8 fade-up">
      <a href="index.php" class="bc-link"><i class="fa-solid fa-house text-[10px]"></i> Home</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-white/10"></i>
      <a href="index.php?cat_id=<?= $product['category_id'] ?>" class="bc-link"><?= htmlspecialchars($product['category_name']) ?></a>
      <i class="fa-solid fa-chevron-right text-[8px] text-white/10"></i>
      <span class="text-xs text-white/60 font-medium truncate max-w-[200px]"><?= htmlspecialchars($product['product_name']) ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
      <div class="fade-up">
        <div class="main-img-wrap aspect-square mb-4">
          <img id="mainImg" src="../backend/uploads/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>/600/600.jpg'">
          <div class="absolute top-4 left-4 z-10 bg-surface-900/70 backdrop-blur-sm border border-white/10 rounded-xl px-3 py-1.5 text-xs font-semibold text-white/60">
            <i class="fa-solid fa-folder text-[9px] text-gold-400 mr-1.5"></i><?= htmlspecialchars($product['category_name']) ?>
          </div>
        </div>
      </div>

      <div class="fade-up" style="animation-delay:0.1s">
        <h1 class="text-3xl lg:text-4xl font-extrabold text-white leading-tight tracking-tight"><?= htmlspecialchars($product['product_name']) ?></h1>

        <div class="flex items-center gap-3 mt-4">
          <div class="flex items-center gap-0.5" id="headerStars"><?= buildStars($avgRating) ?></div>
          <span class="text-xs text-white/30" id="headerStats">(<?= $avgRating ?>) · <?= $totalReviews ?> reviews</span>
          <span class="text-xs text-gold-400 bg-gold-500/10 px-2 py-0.5 rounded-md font-semibold">In Stock</span>
        </div>

        <div class="mt-6 flex items-end gap-3">
          <span class="text-4xl font-extrabold text-shimmer">Rs. <?= number_format($product['price'], 0) ?></span>
          <span class="text-lg text-white/20 line-through mb-1">Rs. <?= number_format($product['price'] * 1.3, 0) ?></span>
          <span class="text-xs font-bold text-red-400 bg-red-500/10 px-2.5 py-1 rounded-lg mb-1.5">-30%</span>
        </div>

        <p class="text-sm text-white/35 leading-relaxed mt-6 max-w-lg"><?= htmlspecialchars($product['description'] ?? 'Premium quality product engineered for exceptional performance.') ?></p>

        <div class="flex flex-wrap gap-3 mt-6">
          <div class="feat-pill"><i class="fa-solid fa-truck-fast text-gold-400 text-xs"></i><span>Free Delivery</span></div>
          <div class="feat-pill"><i class="fa-solid fa-shield-halved text-gold-400 text-xs"></i><span>1 Year Warranty</span></div>
          <div class="feat-pill"><i class="fa-solid fa-rotate-left text-gold-400 text-xs"></i><span>7 Days Return</span></div>
          <div class="feat-pill"><i class="fa-solid fa-box text-gold-400 text-xs"></i><span>Premium Packaging</span></div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mt-8">
          <div class="flex items-center gap-3 bg-white/[0.03] border border-white/[0.06] rounded-2xl px-4 py-2">
            <button class="qty-btn" onclick="changeQty(-1)"><i class="fa-solid fa-minus text-xs"></i></button>
            <span id="qtyValue" class="text-lg font-bold text-white w-8 text-center">1</span>
            <button class="qty-btn" onclick="changeQty(1)"><i class="fa-solid fa-plus text-xs"></i></button>
          </div>
          <button type="button" id="addToCartBtn" onclick="handleAddToCart(<?= $product['id'] ?>)" class="btn-cart flex-1 sm:flex-none px-8 py-4 text-sm flex items-center justify-center gap-2 text-center cursor-pointer">
            <i class="fa-solid fa-bag-shopping text-xs" id="cartBtnIcon"></i>
            <span id="cartBtnText">Add to Cart</span>
          </button>
          <button type="button" onclick="handleBuyNow()" class="btn-outline px-8 py-4 text-sm flex items-center justify-center gap-2 text-center cursor-pointer">
            <i class="fa-solid fa-bolt text-xs"></i> Buy Now
          </button>
        </div>

        <div class="flex items-center gap-6 mt-8 pt-6 border-t border-white/[0.04]">
          <div class="flex items-center gap-2"><i class="fa-solid fa-lock text-xs text-gold-400/50"></i><span class="text-xs text-white/25">Secure Payment</span></div>
          <div class="flex items-center gap-2"><i class="fa-solid fa-certificate text-xs text-gold-400/50"></i><span class="text-xs text-white/25">100% Genuine</span></div>
          <div class="flex items-center gap-2"><i class="fa-solid fa-headset text-xs text-gold-400/50"></i><span class="text-xs text-white/25">24/7 Support</span></div>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="mt-16 fade-up" style="animation-delay:0.2s">
      <div class="flex items-center gap-2 mb-6">
        <button class="tab-btn active" onclick="switchTab('desc', this)">Description</button>
        <?php if (!empty($specs)): ?>
        <button class="tab-btn" onclick="switchTab('specs', this)">Specifications</button>
        <?php endif; ?>
        <button class="tab-btn" onclick="switchTab('reviews', this)">
          Reviews
          <?php if ($totalReviews > 0): ?>
          <span class="ml-1.5 text-[10px] font-bold bg-gold-500/15 text-gold-400 px-2 py-0.5 rounded-md"><?= $totalReviews ?></span>
          <?php endif; ?>
        </button>
      </div>

      <!-- Description -->
      <div id="tab-desc" class="bg-surface-800 rounded-2xl border border-white/[0.04] p-8">
        <h3 class="text-lg font-bold text-white mb-4">Product Description</h3>
        <div class="text-sm text-white/35 leading-relaxed space-y-3">
          <p><?= htmlspecialchars($product['description'] ?? 'Premium quality product engineered for exceptional performance.') ?></p>
          <p>Experience unmatched audio quality with advanced driver technology that delivers deep, punchy bass and crystal-clear highs.</p>
          <p>Whether you're commuting, working out, or relaxing at home, this product adapts to your lifestyle with seamless connectivity and intuitive controls.</p>
        </div>
      </div>

      <!-- Specifications -->
      <?php if (!empty($specs)): ?>
      <div id="tab-specs" class="hidden bg-surface-800 rounded-2xl border border-white/[0.04] p-8">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-bold text-white">Specifications</h3>
          <span class="text-xs text-white/20 bg-white/[0.03] px-3 py-1 rounded-lg"><?= count($specs) ?> specs</span>
        </div>
        <div class="space-y-0">
          <?php foreach ($specs as $i => $spec): ?>
          <div class="spec-row flex items-center justify-between py-3.5 <?= $i < count($specs) - 1 ? 'border-b border-white/[0.04]' : '' ?>">
            <div class="flex items-center gap-3">
              <span class="spec-dot"></span>
              <span class="text-sm text-white/30"><?= htmlspecialchars($spec[0]) ?></span>
            </div>
            <span class="text-sm font-semibold text-white/70"><?= htmlspecialchars($spec[1]) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- REVIEWS TAB -->
      <div id="tab-reviews" class="hidden bg-surface-800 rounded-2xl border border-white/[0.04] p-6 sm:p-8">

        <!-- Rating Summary -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 mb-8 pb-6 border-b border-white/[0.04]">
          <div class="text-center sm:text-left">
            <p class="text-5xl font-extrabold text-white" id="bigRating"><?= $avgRating ?></p>
            <div class="flex items-center gap-0.5 justify-center sm:justify-start mt-2" id="bigStars"><?= buildStars($avgRating, 'text-sm') ?></div>
            <p class="text-xs text-white/20 mt-1.5" id="bigTotal"><?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></p>
          </div>
          <div class="flex-1 w-full space-y-2">
            <?php
            $ratingDist = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
            foreach ($dbReviews as $r) {
                $rv = intval($r['rating']);
                if (isset($ratingDist[$rv])) {
                    $ratingDist[$rv]++;
                }
            }
            foreach ($ratingDist as $star => $cnt):
                $pct = $totalReviews > 0 ? round(($cnt / $totalReviews) * 100) : 0;
            ?>
            <div class="flex items-center gap-3">
              <span class="text-xs text-white/25 font-semibold w-4 text-right"><?= $star ?></span>
              <i class="fa-solid fa-star text-[9px] star"></i>
              <div class="rating-bar-track flex-1"><div class="rating-bar-fill" style="width:<?= $pct ?>%"></div></div>
              <span class="text-[10px] text-white/15 font-semibold w-7 text-right"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Review Form Section -->
        <div id="reviewFormSection">
          <?php if (!$isLoggedIn): ?>
          <div class="review-locked">
            <i class="fa-solid fa-lock"></i>
            <p>Please <a href="<?= $loginPage ?>">login to your account</a> to write a review for this product.</p>
          </div>
          <?php elseif ($userOwnReview): ?>
          <div class="already-reviewed-box">
            <i class="fa-solid fa-circle-check"></i>
            <div>
              <p>You already reviewed this product</p>
              <p style="font-size:0.75rem;color:rgba(34,197,94,0.4);font-weight:500;margin-top:3px"><?= $userOwnReview['rating'] ?> star<?= $userOwnReview['rating'] > 1 ? 's' : '' ?> · <?= timeAgo($userOwnReview['created_at']) ?></p>
            </div>
          </div>
          <?php else: ?>
          <div class="review-form-wrap">
            <h4 class="text-sm font-bold text-white/70 mb-5 flex items-center gap-2">
              <i class="fa-solid fa-pen-to-square text-gold-400 text-xs"></i>
              Write a Review
            </h4>
            <div class="star-selector" id="starSelector">
              <button type="button" class="star-btn" data-star="1"><i class="fa-solid fa-star"></i></button>
              <button type="button" class="star-btn" data-star="2"><i class="fa-solid fa-star"></i></button>
              <button type="button" class="star-btn" data-star="3"><i class="fa-solid fa-star"></i></button>
              <button type="button" class="star-btn" data-star="4"><i class="fa-solid fa-star"></i></button>
              <button type="button" class="star-btn" data-star="5"><i class="fa-solid fa-star"></i></button>
              <span class="star-label" id="starLabel">Select rating</span>
            </div>
            <textarea class="review-textarea" id="reviewComment" placeholder="Share your experience with this product..." maxlength="500"></textarea>
            <div class="flex items-center justify-between mt-4">
              <span id="charCount" class="text-xs text-white/10">0 / 500</span>
              <button type="button" class="btn-submit-review" id="btnSubmitReview" onclick="submitReview()" disabled>
                <i class="fa-solid fa-paper-plane text-xs"></i> Submit Review
              </button>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Reviews List -->
        <div class="mt-6">
          <h4 class="text-xs font-bold text-white/30 uppercase tracking-wider mb-4 flex items-center gap-2">
            <i class="fa-solid fa-comments text-[10px]"></i> All Reviews
          </h4>
          <div id="reviewsList">
            <?php if (empty($dbReviews)): ?>
            <div class="reviews-empty">
              <i class="fa-regular fa-comment-dots"></i>
              <p>No reviews yet. Be the first to share your thoughts!</p>
            </div>
            <?php else: ?>
              <?php foreach ($dbReviews as $rev): ?>
              <div class="review-item">
                <div class="review-avatar">
                  <?php if (!empty($rev['user_image']) && file_exists("../backend/uploads/" . $rev['user_image'])): ?>
                    <img src="../backend/uploads/<?= $rev['user_image'] ?>" alt="">
                  <?php else: ?>
                    <?= strtoupper(mb_substr($rev['user_name'], 0, 1)) ?>
                  <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap mb-1.5">
                    <span class="text-sm font-semibold text-white/80"><?= htmlspecialchars($rev['user_name']) ?></span>
                    <?php if ($isLoggedIn && intval($rev['user_id']) === $userId): ?>
                    <span class="you-badge">YOU</span>
                    <?php endif; ?>
                    <span class="text-[11px] text-white/15 ml-auto"><?= timeAgo($rev['created_at']) ?></span>
                  </div>
                  <div class="flex items-center gap-0.5 mb-2"><?= buildStars(intval($rev['rating']), 'text-[10px]') ?></div>
                  <p class="text-sm text-white/30 leading-relaxed"><?= htmlspecialchars($rev['comment']) ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>

    <!-- RELATED -->
    <?php if (mysqli_num_rows($related_result) > 0): ?>
    <div class="mt-16 fade-up" style="animation-delay:0.25s">
      <div class="flex items-end justify-between mb-8">
        <div>
          <h2 class="text-xl font-extrabold text-white tracking-tight">You May Also Like</h2>
          <p class="text-sm text-white/25 mt-1">Similar products in <?= htmlspecialchars($product['category_name']) ?></p>
        </div>
        <a href="index.php?cat_id=<?= $product['category_id'] ?>" class="bc-link text-xs font-semibold flex items-center gap-1">View All <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <?php while ($rel = mysqli_fetch_assoc($related_result)): ?>
        <a href="product_detail.php?id=<?= $rel['id'] ?>" class="rel-card">
          <div class="overflow-hidden">
            <img src="../backend/uploads/<?= $rel['image'] ?>" alt="<?= htmlspecialchars($rel['product_name']) ?>" onerror="this.src='https://picsum.photos/seed/<?= $rel['id'] ?>/400/300.jpg'">
          </div>
          <div class="p-4">
            <h4 class="text-sm font-bold text-white truncate"><?= htmlspecialchars($rel['product_name']) ?></h4>
            <p class="text-base font-extrabold text-gold-400 mt-1.5">Rs. <?= number_format($rel['price'], 0) ?></p>
          </div>
        </a>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

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

<script>
var IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
var LOGIN_URL = '<?= $loginPage ?>';
var PRODUCT_ID = <?= $id ?>;
var USER_NAME_JS = '<?= addslashes($userName) ?>';
var USER_IMAGE_JS = '<?= addslashes($userImage) ?>';

var profileOpen = false;
function toggleProfile() {
  profileOpen = !profileOpen;
  document.getElementById('profileDropdown').classList.toggle('open', profileOpen);
  document.getElementById('profileBtn').classList.toggle('active', profileOpen);
}
function closeProfile() {
  profileOpen = false;
  var dd = document.getElementById('profileDropdown');
  var btn = document.getElementById('profileBtn');
  if (dd) dd.classList.remove('open');
  if (btn) btn.classList.remove('active');
}
document.addEventListener('click', function(e) {
  var w = document.getElementById('profileWrap');
  if (w && !w.contains(e.target)) closeProfile();
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeProfile(); });

var qty = 1;
function changeQty(d) { qty = Math.max(1, Math.min(10, qty + d)); document.getElementById('qtyValue').textContent = qty; }

function showToast(id, dur) {
  var t = document.getElementById(id);
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, dur || 3000);
}
function showLoginToast() {
  showToast('toastLogin', 4000);
  document.getElementById('addToCartBtn').classList.add('head-shake');
  setTimeout(function() { document.getElementById('addToCartBtn').classList.remove('head-shake'); }, 600);
}
function showSuccessToast() { showToast('toastSuccess', 2500); }

function handleAddToCart(pid) { if (!IS_LOGGED_IN) { showLoginToast(); return; } addToCart(pid); }
function handleBuyNow() { if (!IS_LOGGED_IN) { showLoginToast(); return; } window.location.href = 'cart.php'; }

function addToCart(pid) {
  var btn = document.getElementById('addToCartBtn');
  var txt = document.getElementById('cartBtnText');
  var ico = document.getElementById('cartBtnIcon');
  btn.disabled = true; btn.style.opacity = '0.7'; btn.style.pointerEvents = 'none';
  ico.className = 'fa-solid fa-spinner fa-spin text-xs'; txt.textContent = 'Adding...';
  var fd = new FormData();
  fd.append('product_id', pid);
  fd.append('quantity', qty);
  fetch('add_to_cart.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled = false; btn.style.opacity = '1'; btn.style.pointerEvents = 'auto';
    if (data.success) {
      ico.className = 'fa-solid fa-check text-xs'; txt.textContent = 'Added ✓';
      btn.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
      updateCartCount(data.cart_count); showSuccessToast();
      setTimeout(function() { ico.className = 'fa-solid fa-bag-shopping text-xs'; txt.textContent = 'Add to Cart'; btn.style.background = 'linear-gradient(135deg, #fbbf24, #f59e0b)'; }, 2000);
    } else { ico.className = 'fa-solid fa-bag-shopping text-xs'; txt.textContent = 'Try Again'; setTimeout(function() { txt.textContent = 'Add to Cart'; }, 1500); }
  })
  .catch(function() { btn.disabled = false; btn.style.opacity = '1'; btn.style.pointerEvents = 'auto'; ico.className = 'fa-solid fa-bag-shopping text-xs'; txt.textContent = 'Add to Cart'; });
}

function updateCartCount(c) {
  var w = document.getElementById('cartIconWrap');
  var b = document.getElementById('cartBadge');
  if (c > 0) {
    if (b) { b.textContent = c; } else { b = document.createElement('span'); b.id = 'cartBadge'; b.className = 'absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center'; b.textContent = c; w.appendChild(b); }
    b.classList.remove('badge-pop'); void b.offsetWidth; b.classList.add('badge-pop');
  } else { if (b) b.remove(); }
}

function switchTab(tab, btn) {
  document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
  ['desc', 'specs', 'reviews'].forEach(function(t) { var el = document.getElementById('tab-' + t); if (el) el.classList.toggle('hidden', t !== tab); });
}

var selectedRating = 0;
var hoverRating = 0;
var ratingLabels = ['', 'Terrible', 'Poor', 'Average', 'Good', 'Excellent'];

(function() {
  var selector = document.getElementById('starSelector');
  if (!selector) return;
  var stars = selector.querySelectorAll('.star-btn');
  var label = document.getElementById('starLabel');
  var textarea = document.getElementById('reviewComment');
  var charCount = document.getElementById('charCount');
  var submitBtn = document.getElementById('btnSubmitReview');

  stars.forEach(function(star) {
    star.addEventListener('mouseenter', function() {
      hoverRating = parseInt(this.dataset.star);
      renderStars(stars, hoverRating, selectedRating, label);
    });
    star.addEventListener('mouseleave', function() {
      hoverRating = 0;
      renderStars(stars, 0, selectedRating, label);
    });
    star.addEventListener('click', function() {
      selectedRating = parseInt(this.dataset.star);
      renderStars(stars, 0, selectedRating, label);
      checkReady();
    });
  });

  textarea.addEventListener('input', function() {
    var len = this.value.length;
    charCount.textContent = len + ' / 500';
    charCount.style.color = len > 450 ? 'rgba(251,191,36,0.5)' : 'rgba(255,255,255,0.1)';
    checkReady();
  });

  function checkReady() {
    submitBtn.disabled = !(selectedRating > 0 && textarea.value.trim().length >= 3);
  }
})();

function renderStars(stars, hover, selected, label) {
  var active = hover > 0 ? hover : selected;
  stars.forEach(function(s) {
    var v = parseInt(s.dataset.star);
    s.classList.remove('hovered', 'selected');
    if (v <= active) s.classList.add(hover > 0 ? 'hovered' : 'selected');
  });
  if (label) {
    if (active > 0) { label.textContent = ratingLabels[active]; label.classList.add('active'); }
    else { label.textContent = 'Select rating'; label.classList.remove('active'); }
  }
}

function submitReview() {
  if (selectedRating === 0) return;
  var comment = document.getElementById('reviewComment').value.trim();
  if (comment.length < 3) return;

  var btn = document.getElementById('btnSubmitReview');
  var origHTML = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Submitting...';
  btn.disabled = true;

  var fd = new FormData();
  fd.append('product_id', PRODUCT_ID);
  fd.append('rating', selectedRating);
  fd.append('comment', comment);

  fetch('submit_review.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      showToast('toastReviewOk', 3500);
      var list = document.getElementById('reviewsList');
      var emptyMsg = list.querySelector('.reviews-empty');
      if (emptyMsg) emptyMsg.remove();

      var avatarContent = USER_IMAGE_JS
        ? '<img src="../backend/uploads/' + USER_IMAGE_JS + '" alt="" onerror="this.parentNode.textContent=\'' + USER_NAME_JS.charAt(0).toUpperCase() + '\'">'
        : USER_NAME_JS.charAt(0).toUpperCase();

      var starsHtml = '';
      for (var i = 1; i <= 5; i++) {
        starsHtml += i <= selectedRating
          ? '<i class="fa-solid fa-star text-[10px] star"></i>'
          : '<i class="fa-solid fa-star text-[10px] star-empty"></i>';
      }

      var newHtml = '<div class="review-item review-new">' +
        '<div class="review-avatar">' + avatarContent + '</div>' +
        '<div class="flex-1 min-w-0">' +
          '<div class="flex items-center gap-2 flex-wrap mb-1.5">' +
            '<span class="text-sm font-semibold text-white/80">' + escapeHtml(USER_NAME_JS) + '</span>' +
            '<span class="you-badge">YOU</span>' +
            '<span class="text-[11px] text-white/15 ml-auto">Just now</span>' +
          '</div>' +
          '<div class="flex items-center gap-0.5 mb-2">' + starsHtml + '</div>' +
          '<p class="text-sm text-white/30 leading-relaxed">' + escapeHtml(comment) + '</p>' +
        '</div>' +
      '</div>';

      list.insertAdjacentHTML('afterbegin', newHtml);

      document.getElementById('reviewFormSection').innerHTML =
        '<div class="already-reviewed-box">' +
          '<i class="fa-solid fa-circle-check"></i>' +
          '<div>' +
            '<p>You already reviewed this product</p>' +
            '<p style="font-size:0.75rem;color:rgba(34,197,94,0.4);font-weight:500;margin-top:3px">' + selectedRating + ' star' + (selectedRating > 1 ? 's' : '') + ' · Just now</p>' +
          '</div>' +
        '</div>';

      var newAvg = data.new_avg;
      var newTotal = data.new_total;
      document.getElementById('bigRating').textContent = newAvg;
      document.getElementById('bigTotal').textContent = newTotal + ' review' + (newTotal !== 1 ? 's' : '');
      document.getElementById('headerStats').textContent = '(' + newAvg + ') · ' + newTotal + ' reviews';
      document.getElementById('bigStars').innerHTML = buildStarsJS(newAvg, 'text-sm');
      document.getElementById('headerStars').innerHTML = buildStarsJS(newAvg, 'text-xs');
    } else {
      document.getElementById('reviewErrMsg').textContent = data.message || 'Failed to submit review';
      showToast('toastReviewErr', 3500);
      btn.innerHTML = origHTML;
      btn.disabled = false;
    }
  })
  .catch(function() {
    document.getElementById('reviewErrMsg').textContent = 'Network error. Please try again.';
    showToast('toastReviewErr', 3500);
    btn.innerHTML = origHTML;
    btn.disabled = false;
  });
}

function buildStarsJS(rating, sizeClass) {
  var html = '';
  for (var i = 1; i <= 5; i++) {
    if (i <= Math.floor(rating)) {
      html += '<i class="fa-solid fa-star ' + sizeClass + ' star"></i>';
    } else if (i - 0.5 <= rating) {
      html += '<i class="fa-solid fa-star-half-stroke ' + sizeClass + ' star"></i>';
    } else {
      html += '<i class="fa-solid fa-star ' + sizeClass + ' star-empty"></i>';
    }
  }
  return html;
}

function escapeHtml(str) {
  var d = document.createElement('div');
  d.appendChild(document.createTextNode(str));
  return d.innerHTML;
}

var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) { if (entry.isIntersecting) entry.target.style.animationPlayState = 'running'; });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(function(el) { el.style.animationPlayState = 'paused'; observer.observe(el); });

window.addEventListener('scroll', function() {
  var nav = document.querySelector('.nav-blur');
  nav.style.background = window.scrollY > 50 ? 'rgba(10,10,15,0.9)' : 'rgba(10,10,15,0.75)';
});
</script>

</body>
</html>