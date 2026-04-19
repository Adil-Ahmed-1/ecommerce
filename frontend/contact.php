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

/* ===== HANDLE CONTACT FORM SUBMISSION ===== */
 $formMsg = '';
 $formType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = trim(mysqli_real_escape_string($conn, $_POST['contact_name'] ?? ''));
    $email = trim(mysqli_real_escape_string($conn, $_POST['contact_email'] ?? ''));
    $subject = trim(mysqli_real_escape_string($conn, $_POST['contact_subject'] ?? ''));
    $message = trim(mysqli_real_escape_string($conn, $_POST['contact_message'] ?? ''));

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $formMsg = 'Please fill in all fields.';
        $formType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formMsg = 'Please enter a valid email address.';
        $formType = 'error';
    } elseif (strlen($message) < 10) {
        $formMsg = 'Message must be at least 10 characters long.';
        $formType = 'error';
    } else {
        // Insert into contact_messages table
        $insert = mysqli_query($conn, "INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES ('$name', '$email', '$subject', '$message', NOW())");
        if ($insert) {
            $formMsg = 'Your message has been sent successfully! We\'ll get back to you soon.';
            $formType = 'success';
            // Clear fields
            $name = $email = $subject = $message = '';
        } else {
            $formMsg = 'Something went wrong. Please try again later.';
            $formType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — BeatsShop</title>
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

  .contact-orb {
    position:absolute;border-radius:50%;filter:blur(120px);opacity:0.3;pointer-events:none;
  }
  .contact-orb-1 {
    width:450px;height:450px;top:-120px;left:-80px;
    background:radial-gradient(circle,rgba(251,191,36,0.18),transparent 70%);
    animation:orbDrift 14s ease-in-out infinite alternate;
  }
  .contact-orb-2 {
    width:300px;height:300px;bottom:-60px;right:-50px;
    background:radial-gradient(circle,rgba(245,158,11,0.1),transparent 70%);
    animation:orbDrift 18s ease-in-out infinite alternate-reverse;
  }
  @keyframes orbDrift {
    0%{transform:translate(0,0) scale(1)} 50%{transform:translate(20px,-20px) scale(1.05)} 100%{transform:translate(-10px,10px) scale(0.95)}
  }

  .text-shimmer {
    background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24);
    background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;animation:shimmer 3s linear infinite;
  }
  @keyframes shimmer { to{background-position:200% center} }

  .fade-up { opacity:0;transform:translateY(24px);animation:fadeUp 0.65s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes fadeUp { to{opacity:1;transform:translateY(0)} }

  .form-card {
    background:#101018;border:1px solid rgba(255,255,255,0.05);border-radius:24px;
    padding:36px 32px;position:relative;overflow:hidden;
  }
  .form-card::before {
    content:'';position:absolute;top:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(251,191,36,0.2),transparent);
  }

  .form-group { margin-bottom:20px; }
  .form-label {
    display:block;font-size:0.78rem;font-weight:700;color:rgba(255,255,255,0.55);
    margin-bottom:8px;text-transform:uppercase;letter-spacing:0.04em;
  }
  .form-input {
    width:100%;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:13px 16px;font-size:0.85rem;color:#fff;
    font-family:'Plus Jakarta Sans',sans-serif;transition:all 0.25s ease;
  }
  .form-input::placeholder { color:rgba(255,255,255,0.18); }
  .form-input:focus {
    outline:none;border-color:rgba(251,191,36,0.4);
    box-shadow:0 0 0 3px rgba(251,191,36,0.06);
    background:rgba(255,255,255,0.04);
  }
  .form-input.error { border-color:rgba(239,68,68,0.4); box-shadow:0 0 0 3px rgba(239,68,68,0.06); }

  textarea.form-input { resize:vertical;min-height:120px;max-height:220px;line-height:1.6; }

  .btn-submit {
    display:inline-flex;align-items:center;gap:10px;padding:14px 32px;border-radius:14px;
    font-size:0.88rem;font-weight:700;background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;border:none;cursor:pointer;transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;width:100%;justify-content:center;
  }
  .btn-submit::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);
    transition:left 0.5s ease;
  }
  .btn-submit:hover::before { left:100%; }
  .btn-submit:hover { box-shadow:0 8px 30px -6px rgba(251,191,36,0.5); transform:translateY(-2px); }
  .btn-submit:active { transform:translateY(0); }
  .btn-submit:disabled { opacity:0.5;cursor:not-allowed;transform:none !important;box-shadow:none !important; }
  .btn-submit:disabled::before { display:none; }

  .form-alert {
    display:flex;align-items:center;gap:10px;padding:14px 16px;border-radius:14px;
    font-size:0.8rem;font-weight:600;margin-bottom:20px;
  }
  .form-alert.success {
    background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.15);color:#4ade80;
  }
  .form-alert.error {
    background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.15);color:#f87171;
  }

  .info-card {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:20px;
    padding:24px;transition:all 0.35s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;
  }
  .info-card::before {
    content:'';position:absolute;inset:0;border-radius:20px;
    background:linear-gradient(135deg,rgba(251,191,36,0.05),transparent 60%);
    opacity:0;transition:opacity 0.35s ease;pointer-events:none;
  }
  .info-card:hover {
    transform:translateY(-4px);border-color:rgba(251,191,36,0.1);
    box-shadow:0 16px 40px -12px rgba(0,0,0,0.4);
  }
  .info-card:hover::before { opacity:1; }

  .info-icon {
    width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;transition:all 0.3s ease;flex-shrink:0;
  }
  .info-card:hover .info-icon { transform:scale(1.08); }

  .faq-item {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:16px;
    overflow:hidden;transition:all 0.3s ease;
  }
  .faq-item:hover { border-color:rgba(255,255,255,0.08); }
  .faq-item.open { border-color:rgba(251,191,36,0.15); }
  .faq-question {
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:18px 22px;cursor:pointer;transition:all 0.2s ease;
    border:none;background:none;width:100%;text-align:left;
    font-family:'Plus Jakarta Sans',sans-serif;
  }
  .faq-question:hover { background:rgba(255,255,255,0.02); }
  .faq-question h4 { font-size:0.85rem;font-weight:700;color:rgba(255,255,255,0.75);transition:color 0.2s; }
  .faq-item.open .faq-question h4 { color:#fbbf24; }
  .faq-arrow {
    width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.04);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
    transition:all 0.3s ease;color:rgba(255,255,255,0.25);font-size:0.7rem;
  }
  .faq-item.open .faq-arrow { transform:rotate(180deg);background:rgba(251,191,36,0.1);color:#fbbf24; }
  .faq-answer {
    max-height:0;overflow:hidden;transition:max-height 0.35s cubic-bezier(.4,0,.2,1);
  }
  .faq-answer-inner {
    padding:0 22px 18px;font-size:0.8rem;color:rgba(255,255,255,0.35);line-height:1.7;
  }

  .map-card {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:20px;
    overflow:hidden;position:relative;
  }
  .map-card::after {
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(10,10,15,0.3),rgba(10,10,15,0.1));
    pointer-events:none;border-radius:20px;
  }

  .social-link {
    display:flex;align-items:center;gap:14px;padding:14px 18px;border-radius:14px;
    background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);
    text-decoration:none;transition:all 0.25s ease;
  }
  .social-link:hover {
    background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);
    transform:translateX(4px);
  }
  .social-icon {
    width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;
    font-size:1rem;flex-shrink:0;transition:transform 0.2s ease;
  }
  .social-link:hover .social-icon { transform:scale(1.08); }
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
      <a href="about.php" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">About</a>
      <a href="contact.php" class="text-sm font-medium text-gold-400">Contact</a>
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
    <a href="about.php" class="block py-2.5 text-sm font-medium text-white/50">About</a>
    <a href="contact.php" class="block py-2.5 text-sm font-medium text-gold-400">Contact</a>
  </div>
</nav>

<!-- ========== HERO ========== -->
<section class="relative min-h-[55vh] flex items-center overflow-hidden pt-20">
  <div class="contact-orb contact-orb-1"></div>
  <div class="contact-orb contact-orb-2"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-10 w-full text-center">
    <div class="fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-6">
        <i class="fa-solid fa-message text-gold-400 text-xs"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Get in Touch</span>
      </div>
      <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.1] tracking-tight">
        We'd Love to<br><span class="text-shimmer">Hear From You</span>
      </h1>
      <p class="text-white/40 mt-5 text-base lg:text-lg max-w-lg mx-auto leading-relaxed">
        Have a question, feedback, or need help with an order? Our team is here for you — reach out anytime.
      </p>
    </div>
  </div>
</section>

<!-- ========== CONTACT FORM + INFO ========== -->
<section class="py-12 pb-20">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

      <!-- LEFT: Contact Form -->
      <div class="lg:col-span-3 fade-up" style="animation-delay:0.05s">
        <div class="form-card">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gold-500/10 flex items-center justify-center">
              <i class="fa-solid fa-paper-plane text-gold-400 text-sm"></i>
            </div>
            <div>
              <h2 class="text-lg font-bold text-white">Send a Message</h2>
              <p class="text-xs text-white/30">We typically respond within 24 hours</p>
            </div>
          </div>

          <?php if ($formMsg): ?>
            <div class="form-alert <?= $formType ?>">
              <i class="fa-solid <?= $formType === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
              <span><?= htmlspecialchars($formMsg) ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" action="" id="contactForm" novalidate>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="contact_name" class="form-input" placeholder="John Doe" required
                  value="<?= htmlspecialchars($name ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="contact_email" class="form-input" placeholder="john@example.com" required
                  value="<?= htmlspecialchars($email ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Subject</label>
              <input type="text" name="contact_subject" class="form-input" placeholder="e.g. Order issue, Product inquiry" required
                value="<?= htmlspecialchars($subject ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Message</label>
              <textarea name="contact_message" class="form-input" placeholder="Tell us how we can help you..." required><?= htmlspecialchars($message ?? '') ?></textarea>
            </div>
            <button type="submit" name="submit_contact" class="btn-submit" id="btnSubmit">
              <i class="fa-solid fa-paper-plane text-sm"></i>
              <span>Send Message</span>
            </button>
          </form>
        </div>
      </div>

      <!-- RIGHT: Contact Info -->
      <div class="lg:col-span-2 space-y-5 fade-up" style="animation-delay:0.15s">

        <!-- Email -->
        <div class="info-card">
          <div class="flex items-start gap-4">
            <div class="info-icon bg-gold-500/10 text-gold-400"><i class="fa-solid fa-envelope"></i></div>
            <div>
              <h3 class="text-sm font-bold text-white mb-1">Email Us</h3>
              <p class="text-xs text-white/35 leading-relaxed mb-2">For general inquiries and support</p>
              <a href="mailto:ahmedadilbaloch95@gmail.com" class="text-xs text-gold-400 font-semibold hover:text-gold-300 transition">ahmedadilbaloch95@gmail.com</a>
            </div>
          </div>
        </div>

        <!-- Phone -->
        <div class="info-card">
          <div class="flex items-start gap-4">
            <div class="info-icon bg-green-500/10 text-green-400"><i class="fa-solid fa-phone"></i></div>
            <div>
              <h3 class="text-sm font-bold text-white mb-1">Call Us</h3>
              <p class="text-xs text-white/35 leading-relaxed mb-2">Mon–Sat, 10 AM – 8 PM PKT</p>
              <a href="tel:+923233703689" class="text-xs text-green-400 font-semibold hover:text-green-300 transition">+92 323 3703689</a>
            </div>
          </div>
        </div>

        <!-- WhatsApp -->
        <div class="info-card">
          <div class="flex items-start gap-4">
            <div class="info-icon bg-emerald-500/10 text-emerald-400"><i class="fa-brands fa-whatsapp"></i></div>
            <div>
              <h3 class="text-sm font-bold text-white mb-1">WhatsApp</h3>
              <p class="text-xs text-white/35 leading-relaxed mb-2">Quick replies, orders & support</p>
              <a href="https://wa.me/923233703689" target="_blank" class="text-xs text-emerald-400 font-semibold hover:text-emerald-300 transition">Chat on WhatsApp →</a>
            </div>
          </div>
        </div>

        <!-- Location -->
        <div class="info-card">
          <div class="flex items-start gap-4">
            <div class="info-icon bg-blue-500/10 text-blue-400"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <h3 class="text-sm font-bold text-white mb-1">Visit Us</h3>
              <p class="text-xs text-white/35 leading-relaxed">Karachi, Sindh, Pakistan</p>
              <p class="text-[10px] text-white/20 mt-1">Walk-ins available by appointment</p>
            </div>
          </div>
        </div>

        <!-- Social Links -->
        <div class="pt-2">
          <p class="text-xs font-bold text-white/40 uppercase tracking-wider mb-3">Follow Us</p>
          <div class="space-y-2.5">
            <a href="#" class="social-link">
              <div class="social-icon bg-pink-500/10 text-pink-400"><i class="fa-brands fa-instagram"></i></div>
              <div>
                <p class="text-xs font-bold text-white/70">Instagram</p>
                <p class="text-[10px] text-white/25">@beatsshop_pk</p>
              </div>
            </a>
            <a href="#" class="social-link">
              <div class="social-icon bg-blue-500/10 text-blue-400"><i class="fa-brands fa-facebook-f"></i></div>
              <div>
                <p class="text-xs font-bold text-white/70">Facebook</p>
                <p class="text-[10px] text-white/25">BeatsShop Pakistan</p>
              </div>
            </a>
            <a href="#" class="social-link">
              <div class="social-icon bg-sky-500/10 text-sky-400"><i class="fa-brands fa-twitter"></i></div>
              <div>
                <p class="text-xs font-bold text-white/70">Twitter / X</p>
                <p class="text-[10px] text-white/25">@beatsshop_pk</p>
              </div>
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- ========== MAP ========== -->
<section class="py-12 pb-20">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="text-center mb-10 fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fa-solid fa-map-location-dot text-gold-400 text-[10px]"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Our Location</span>
      </div>
      <h2 class="text-2xl font-extrabold text-white tracking-tight">Find Us in <span class="text-shimmer">Karachi</span></h2>
    </div>
    <div class="map-card fade-up" style="animation-delay:0.05s">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d57901.25736088782!2d67.0011362!3d24.8609654!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb33f90157042d3%3A0x93d609e8bfb4e64!2sKarachi%2C%20Pakistan!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s"
        width="100%" height="380" style="border:0;display:block;border-radius:20px;"
        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </div>
</section>

<!-- ========== FAQ ========== -->
<section class="py-12 pb-20 relative">
  <div class="absolute inset-0 bg-gradient-to-b from-transparent via-gold-500/[0.015] to-transparent pointer-events-none"></div>
  <div class="max-w-3xl mx-auto px-6 lg:px-10 relative z-10">
    <div class="text-center mb-10 fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fa-solid fa-circle-question text-gold-400 text-[10px]"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">FAQ</span>
      </div>
      <h2 class="text-2xl font-extrabold text-white tracking-tight">Frequently Asked <span class="text-shimmer">Questions</span></h2>
    </div>
    <div class="space-y-3" id="faqContainer">

      <div class="faq-item fade-up" style="animation-delay:0.05s">
        <button class="faq-question" onclick="toggleFaq(this)">
          <h4>How long does delivery take?</h4>
          <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
        </button>
        <div class="faq-answer">
          <div class="faq-answer-inner">We deliver within 2–5 business days across all major cities in Pakistan. Remote areas may take 5–7 days. You'll receive a tracking number via SMS/email once your order ships.</div>
        </div>
      </div>

      <div class="faq-item fade-up" style="animation-delay:0.08s">
        <button class="faq-question" onclick="toggleFaq(this)">
          <h4>Is delivery really free?</h4>
          <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
        </button>
        <div class="faq-answer">
          <div class="faq-answer-inner">Yes! We offer free delivery on all orders across Pakistan — no minimum order required. This is our way of saying thank you for choosing BeatsShop.</div>
        </div>
      </div>

      <div class="faq-item fade-up" style="animation-delay:0.11s">
        <button class="faq-question" onclick="toggleFaq(this)">
          <h4>What's your return/refund policy?</h4>
          <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
        </button>
        <div class="faq-answer">
          <div class="faq-answer-inner">You can return any product within 7 days of delivery in its original condition and packaging. We'll issue a full refund or exchange — your choice. Defective items are covered under our 1-year warranty.</div>
        </div>
      </div>

      <div class="faq-item fade-up" style="animation-delay:0.14s">
        <button class="faq-question" onclick="toggleFaq(this)">
          <h4>Do your products come with a warranty?</h4>
          <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
        </button>
        <div class="faq-answer">
          <div class="faq-answer-inner">Absolutely. All products come with a minimum 1-year manufacturer warranty. Some premium items include extended warranties of up to 2 years. Warranty cards are included in every package.</div>
        </div>
      </div>

      <div class="faq-item fade-up" style="animation-delay:0.17s">
        <button class="faq-question" onclick="toggleFaq(this)">
          <h4>What payment methods do you accept?</h4>
          <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
        </button>
        <div class="faq-answer">
          <div class="faq-answer-inner">We accept Cash on Delivery (COD), JazzCash, EasyPaisa, bank transfer, and all major debit/credit cards. Online payments are processed securely through trusted Pakistani payment gateways.</div>
        </div>
      </div>

      <div class="faq-item fade-up" style="animation-delay:0.2s">
        <button class="faq-question" onclick="toggleFaq(this)">
          <h4>Are your products original and authentic?</h4>
          <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
        </button>
        <div class="faq-answer">
          <div class="faq-answer-inner">100%. We source directly from authorized distributors and brands. Every product is genuine, comes with original packaging, and includes warranty documentation. We have zero tolerance for counterfeit goods.</div>
        </div>
      </div>

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
/* ===== PROFILE DROPDOWN ===== */
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

/* ===== FAQ ACCORDION ===== */
function toggleFaq(btn) {
  var item = btn.closest('.faq-item');
  var answer = item.querySelector('.faq-answer');
  var inner = answer.querySelector('.faq-answer-inner');
  var isOpen = item.classList.contains('open');

  // Close all
  document.querySelectorAll('.faq-item.open').forEach(function(openItem) {
    openItem.classList.remove('open');
    openItem.querySelector('.faq-answer').style.maxHeight = '0';
  });

  // Open clicked (if it was closed)
  if (!isOpen) {
    item.classList.add('open');
    answer.style.maxHeight = inner.scrollHeight + 20 + 'px';
  }
}

/* ===== FORM CLIENT-SIDE VALIDATION ===== */
var contactForm = document.getElementById('contactForm');
if (contactForm) {
  contactForm.addEventListener('submit', function(e) {
    var inputs = this.querySelectorAll('.form-input');
    var valid = true;

    inputs.forEach(function(input) {
      input.classList.remove('error');
      if (!input.value.trim()) {
        input.classList.add('error');
        valid = false;
      }
    });

    // Email validation
    var emailInput = this.querySelector('input[type="email"]');
    if (emailInput && emailInput.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
      emailInput.classList.add('error');
      valid = false;
    }

    // Message min length
    var textarea = this.querySelector('textarea');
    if (textarea && textarea.value.trim().length < 10) {
      textarea.classList.add('error');
      valid = false;
    }

    if (!valid) e.preventDefault();
  });

  // Remove error on focus
  contactForm.querySelectorAll('.form-input').forEach(function(input) {
    input.addEventListener('focus', function() {
      this.classList.remove('error');
    });
  });
}

/* ===== SCROLL ANIMATIONS ===== */
var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(entry) {
    if (entry.isIntersecting) entry.target.style.animationPlayState = 'running';
  });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(function(el) {
  el.style.animationPlayState = 'paused';
  observer.observe(el);
});

/* ===== NAV SCROLL EFFECT ===== */
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