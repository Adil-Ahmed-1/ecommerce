<?php
session_start();
include("../backend/config/db.php");

/* ================= USER DATA (IF LOGGED IN) ================= */
 $user = null;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $u_res = mysqli_query($conn, "SELECT id, name, email, image FROM users WHERE id = $uid LIMIT 1");
    $user = mysqli_fetch_assoc($u_res);
}

/* ================= CATEGORIES ================= */
 $cat_query = "SELECT * FROM categories";
 $cat_result = mysqli_query($conn, $cat_query);

/* ================= PRODUCTS FILTER (WITH REVIEWS) ================= */
if (isset($_GET['cat_id']) && $_GET['cat_id'] !== "") {
    $cat_id = intval($_GET['cat_id']);
    $product_query = "
    SELECT p.*, c.category_name,
    IFNULL(AVG(r.rating),0) as avg_rating,
    COUNT(r.id) as total_reviews
    FROM products p 
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.category_id = $cat_id
    GROUP BY p.id
    ORDER BY p.id DESC";
    $active_cat = $cat_id;
} else {
    $product_query = "
    SELECT p.*, c.category_name,
    IFNULL(AVG(r.rating),0) as avg_rating,
    COUNT(r.id) as total_reviews
    FROM products p 
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id
    ORDER BY p.id DESC";
    $active_cat = 0;
}

 $product_result = mysqli_query($conn, $product_query);
 $product_count = mysqli_num_rows($product_result);

/* ================= ACTIVE CATEGORY ================= */
 $active_cat_name = 'All Products';
if ($active_cat > 0) {
    $ac = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category_name FROM categories WHERE id = $active_cat"));
    if ($ac) $active_cat_name = $ac['category_name'];
}

/* ================= CART COUNT (DB + SESSION) ================= */
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
    $row = mysqli_fetch_assoc($res);
    $count = $row['total'] ?? 0;
} else {
    $count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
}

/* ================= CONTACT FORM HANDLER ================= */
 $formMsg = '';
 $formType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $c_name = trim(mysqli_real_escape_string($conn, $_POST['contact_name'] ?? ''));
    $c_email = trim(mysqli_real_escape_string($conn, $_POST['contact_email'] ?? ''));
    $c_subject = trim(mysqli_real_escape_string($conn, $_POST['contact_subject'] ?? ''));
    $c_message = trim(mysqli_real_escape_string($conn, $_POST['contact_message'] ?? ''));

    if (empty($c_name) || empty($c_email) || empty($c_subject) || empty($c_message)) {
        $formMsg = 'Please fill in all fields.';
        $formType = 'error';
    } elseif (!filter_var($c_email, FILTER_VALIDATE_EMAIL)) {
        $formMsg = 'Please enter a valid email address.';
        $formType = 'error';
    } elseif (strlen($c_message) < 10) {
        $formMsg = 'Message must be at least 10 characters.';
        $formType = 'error';
    } else {
        $insert = mysqli_query($conn, "INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES ('$c_name', '$c_email', '$c_subject', '$c_message', NOW())");
        if ($insert) {
            $formMsg = 'Message sent successfully! We\'ll get back to you soon.';
            $formType = 'success';
            $c_name = $c_email = $c_subject = $c_message = '';
        } else {
            $formMsg = 'Something went wrong. Please try again.';
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
<title>Beats Shop — Premium Audio</title>

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

  .profile-dropdown {
    position:absolute;top:calc(100% + 8px);right:0;
    width:260px;background:#16161f;
    border:1px solid rgba(255,255,255,0.08);
    border-radius:16px;padding:0;
    opacity:0;visibility:hidden;
    transform:translateY(-8px) scale(0.97);
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    box-shadow:0 25px 60px -12px rgba(0,0,0,0.7);
    z-index:999;overflow:hidden;
  }
  .profile-dropdown.open {
    opacity:1;visibility:visible;
    transform:translateY(0) scale(1);
  }
  .profile-dropdown::before {
    content:'';position:absolute;top:-6px;right:16px;
    width:12px;height:12px;background:#16161f;
    border-left:1px solid rgba(255,255,255,0.08);
    border-top:1px solid rgba(255,255,255,0.08);
    transform:rotate(45deg);
  }
  .dropdown-item {
    display:flex;align-items:center;gap:10px;
    padding:10px 16px;font-size:0.82rem;font-weight:500;
    color:rgba(255,255,255,0.55);text-decoration:none;
    transition:all 0.15s ease;cursor:pointer;
    border:none;background:none;width:100%;text-align:left;
  }
  .dropdown-item:hover { background:rgba(255,255,255,0.04); color:#fff; }
  .dropdown-item i { width:18px;text-align:center;font-size:0.78rem; }
  .dropdown-divider { height:1px;background:rgba(255,255,255,0.06);margin:4px 0; }
  .dropdown-item.danger:hover { background:rgba(239,68,68,0.08); color:#f87171; }

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
    position:relative;overflow:hidden;background:#16161f;cursor:pointer;
  }
  .prod-img-wrap img {
    width:100%;height:260px;object-fit:cover;
    transition:transform 0.6s cubic-bezier(.4,0,.2,1);
  }
  .prod-card:hover .prod-img-wrap img { transform:scale(1.08); }
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

  .hero-img { animation:heroFloat 4s ease-in-out infinite; }
  @keyframes heroFloat {
    0%,100% { transform:translateY(0); }
    50% { transform:translateY(-15px); }
  }

  .footer-link {
    color:rgba(255,255,255,0.35);font-size:0.85rem;
    text-decoration:none;transition:color 0.2s;display:block;padding:4px 0;
  }
  .footer-link:hover { color:#fbbf24; }

  .cart-pulse { animation:cartPop 0.3s cubic-bezier(.4,0,.2,1); }
  @keyframes cartPop {
    0% { transform:scale(1); }
    50% { transform:scale(1.4); }
    100% { transform:scale(1); }
  }

  .empty-icon { animation:emptyBounce 2s ease-in-out infinite; }
  @keyframes emptyBounce {
    0%,100% { transform:translateY(0); }
    50% { transform:translateY(-8px); }
  }

  .cat-scroll::-webkit-scrollbar { display:none; }
  .cat-scroll { -ms-overflow-style:none;scrollbar-width:none; }

  .skeleton {
    background:linear-gradient(90deg,#16161f 25%,#1c1c28 50%,#16161f 75%);
    background-size:200% 100%;animation:skeletonPulse 1.5s ease infinite;
  }
  @keyframes skeletonPulse { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

  .btn-login {
    display:inline-flex;align-items:center;gap:8px;
    padding:8px 18px;border-radius:12px;
    font-size:0.82rem;font-weight:700;
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;text-decoration:none;
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;
  }
  .btn-login::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);
    transition:left 0.5s ease;
  }
  .btn-login:hover::before { left:100%; }
  .btn-login:hover {
    box-shadow:0 6px 24px -4px rgba(251,191,36,0.5);
    transform:translateY(-1px);
  }

  .profile-avatar {
    width:38px;height:38px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-weight:800;font-size:0.82rem;
    cursor:pointer;transition:all 0.2s ease;
    position:relative;overflow:hidden;
    border:2px solid transparent;
  }
  .profile-avatar:hover {
    border-color:rgba(251,191,36,0.4);
    box-shadow:0 0 20px -4px rgba(251,191,36,0.2);
  }
  .profile-avatar img { width:100%;height:100%;object-fit:cover;border-radius:10px; }
  .online-dot {
    position:absolute;bottom:0;right:0;
    width:10px;height:10px;border-radius:50%;
    background:#22c55e;border:2px solid #0a0a0f;
  }

  .review-trigger {
    cursor:pointer;transition:all 0.2s ease;border-radius:6px;padding:2px 0;
  }
  .review-trigger:hover .review-trigger-text { color:#fbbf24 !important; }
  .review-trigger:hover .review-trigger-icon { color:#fbbf24 !important; transform:scale(1.1); }

  .review-overlay {
    position:fixed;inset:0;z-index:9000;
    background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    display:flex;align-items:center;justify-content:center;padding:16px;
    opacity:0;visibility:hidden;transition:all 0.3s cubic-bezier(.4,0,.2,1);
  }
  .review-overlay.open { opacity:1;visibility:visible; }

  .review-modal {
    width:100%;max-width:580px;max-height:88vh;background:#13131b;
    border:1px solid rgba(255,255,255,0.08);border-radius:24px;overflow:hidden;
    transform:translateY(30px) scale(0.96);transition:all 0.35s cubic-bezier(.4,0,.2,1);
    box-shadow:0 40px 80px -20px rgba(0,0,0,0.8);display:flex;flex-direction:column;
  }
  .review-overlay.open .review-modal { transform:translateY(0) scale(1); }

  .review-modal-header {
    padding:20px 24px 16px;border-bottom:1px solid rgba(255,255,255,0.05);
    display:flex;align-items:center;gap:16px;flex-shrink:0;
  }
  .review-modal-header img {
    width:56px;height:56px;border-radius:14px;object-fit:cover;border:1px solid rgba(255,255,255,0.06);
  }
  .review-modal-close {
    margin-left:auto;width:36px;height:36px;border-radius:10px;
    background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.06);
    color:rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:all 0.2s ease;font-size:0.8rem;flex-shrink:0;
  }
  .review-modal-close:hover { background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.2);color:#f87171; }

  .review-modal-body { flex:1;overflow-y:auto;padding:0; }
  .review-modal-body::-webkit-scrollbar { width:5px; }
  .review-modal-body::-webkit-scrollbar-track { background:transparent; }
  .review-modal-body::-webkit-scrollbar-thumb { background:#2a2a3a;border-radius:99px; }

  .write-review-section {
    padding:20px 24px;border-bottom:1px solid rgba(255,255,255,0.05);
    background:linear-gradient(180deg,rgba(251,191,36,0.03),transparent);
  }
  .write-review-section h4 { font-size:0.82rem;font-weight:700;color:rgba(255,255,255,0.7);margin-bottom:14px;display:flex;align-items:center;gap:8px; }
  .write-review-section h4 i { color:#fbbf24;font-size:0.75rem; }

  .star-selector { display:flex;align-items:center;gap:6px;margin-bottom:14px; }
  .star-selector .star-btn {
    background:none;border:none;cursor:pointer;padding:2px;font-size:1.4rem;
    color:rgba(255,255,255,0.08);transition:all 0.15s ease;line-height:1;
  }
  .star-selector .star-btn:hover { transform:scale(1.2); }
  .star-selector .star-btn.hovered { color:rgba(251,191,36,0.5); }
  .star-selector .star-btn.selected { color:#fbbf24;filter:drop-shadow(0 0 6px rgba(251,191,36,0.4)); }
  .star-label { font-size:0.75rem;font-weight:600;color:rgba(255,255,255,0.25);margin-left:6px;min-width:80px;transition:color 0.2s ease; }
  .star-label.active { color:#fbbf24; }

  .review-textarea {
    width:100%;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:14px 16px;font-size:0.82rem;color:#fff;
    font-family:'Plus Jakarta Sans',sans-serif;resize:vertical;min-height:80px;max-height:160px;
    transition:border-color 0.2s ease;line-height:1.6;
  }
  .review-textarea::placeholder { color:rgba(255,255,255,0.2); }
  .review-textarea:focus { outline:none;border-color:rgba(251,191,36,0.4);box-shadow:0 0 0 3px rgba(251,191,36,0.06); }

  .btn-submit-review {
    display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:10px 24px;
    border-radius:12px;font-size:0.8rem;font-weight:700;
    background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#0a0a0f;border:none;
    cursor:pointer;transition:all 0.25s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;
  }
  .btn-submit-review::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);transition:left 0.5s ease;
  }
  .btn-submit-review:hover::before { left:100%; }
  .btn-submit-review:hover { box-shadow:0 6px 24px -4px rgba(251,191,36,0.5);transform:translateY(-1px); }
  .btn-submit-review:disabled { opacity:0.4;cursor:not-allowed;transform:none !important;box-shadow:none !important; }
  .btn-submit-review:disabled::before { display:none; }

  .login-to-review {
    display:flex;align-items:center;gap:12px;padding:16px;
    background:rgba(255,255,255,0.02);border:1px dashed rgba(255,255,255,0.08);border-radius:14px;
  }
  .login-to-review i { font-size:1.2rem;color:rgba(255,255,255,0.15); }
  .login-to-review p { font-size:0.8rem;color:rgba(255,255,255,0.35);line-height:1.5; }
  .login-to-review a { color:#fbbf24;font-weight:700;text-decoration:none;transition:color 0.2s; }
  .login-to-review a:hover { color:#fde68a; }

  .already-reviewed {
    display:flex;align-items:center;gap:10px;padding:14px 16px;
    background:rgba(34,197,94,0.05);border:1px solid rgba(34,197,94,0.12);border-radius:14px;
  }
  .already-reviewed i { color:#22c55e;font-size:1rem; }
  .already-reviewed p { font-size:0.8rem;color:rgba(34,197,94,0.7);font-weight:600; }

  .reviews-list-header {
    padding:16px 24px 12px;display:flex;align-items:center;justify-content:space-between;
  }
  .reviews-list-header h4 {
    font-size:0.78rem;font-weight:700;color:rgba(255,255,255,0.5);
    text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:8px;
  }
  .reviews-list-header span { font-size:0.7rem;font-weight:600;background:rgba(255,255,255,0.05);padding:2px 8px;border-radius:6px;color:rgba(255,255,255,0.3); }

  .review-item { padding:16px 24px;border-bottom:1px solid rgba(255,255,255,0.03);transition:background 0.2s ease; }
  .review-item:hover { background:rgba(255,255,255,0.015); }
  .review-item:last-child { border-bottom:none; }
  .review-item-top { display:flex;align-items:center;gap:10px;margin-bottom:8px; }
  .review-avatar {
    width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    font-weight:800;font-size:0.7rem;background:linear-gradient(135deg,rgba(251,191,36,0.15),rgba(251,191,36,0.05));
    color:#fbbf24;flex-shrink:0;overflow:hidden;border:1px solid rgba(255,255,255,0.06);
  }
  .review-avatar img { width:100%;height:100%;object-fit:cover;border-radius:9px; }
  .review-username { font-size:0.8rem;font-weight:700;color:rgba(255,255,255,0.8); }
  .review-time { font-size:0.65rem;color:rgba(255,255,255,0.2);margin-left:auto;flex-shrink:0; }
  .review-stars { display:flex;align-items:center;gap:2px;margin-bottom:8px; }
  .review-stars i { font-size:0.65rem; }
  .review-stars .fa-solid.fa-star { color:#fbbf24; }
  .review-stars .fa-regular.fa-star { color:rgba(255,255,255,0.08); }
  .review-comment { font-size:0.8rem;color:rgba(255,255,255,0.45);line-height:1.65;word-break:break-word; }

  .reviews-empty { padding:40px 24px;text-align:center; }
  .reviews-empty i { font-size:2rem;color:rgba(255,255,255,0.06);margin-bottom:12px;display:block; }
  .reviews-empty p { font-size:0.82rem;color:rgba(255,255,255,0.2); }
  .reviews-loading { padding:40px 24px;text-align:center; }
  .reviews-loading .spinner {
    width:32px;height:32px;border:3px solid rgba(255,255,255,0.05);border-top-color:#fbbf24;
    border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 12px;
  }
  @keyframes spin { to { transform:rotate(360deg); } }
  .reviews-loading p { font-size:0.78rem;color:rgba(255,255,255,0.25); }

  .toast-container {
    position:fixed;bottom:24px;right:24px;z-index:99999;
    display:flex;flex-direction:column-reverse;gap:10px;pointer-events:none;
  }
  .toast {
    display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:14px;
    font-size:0.8rem;font-weight:600;pointer-events:auto;
    transform:translateX(120%);opacity:0;transition:all 0.35s cubic-bezier(.4,0,.2,1);
    box-shadow:0 15px 40px -10px rgba(0,0,0,0.6);max-width:360px;
  }
  .toast.show { transform:translateX(0);opacity:1; }
  .toast.exit { transform:translateX(120%);opacity:0; }
  .toast-success { background:#13131b;border:1px solid rgba(34,197,94,0.2);color:#4ade80; }
  .toast-success i { color:#22c55e; }
  .toast-error { background:#13131b;border:1px solid rgba(239,68,68,0.2);color:#f87171; }
  .toast-error i { color:#ef4444; }
  .toast-info { background:#13131b;border:1px solid rgba(251,191,36,0.2);color:#fbbf24; }
  .toast-info i { color:#f59e0b; }

  /* ===== ABOUT SECTION STYLES ===== */
  .section-orb {
    position:absolute;border-radius:50%;filter:blur(120px);opacity:0.25;pointer-events:none;
  }
  .section-orb-1 {
    width:400px;height:400px;top:-100px;right:-60px;
    background:radial-gradient(circle,rgba(251,191,36,0.18),transparent 70%);
    animation:orbFloat 14s ease-in-out infinite alternate;
  }
  .section-orb-2 {
    width:300px;height:300px;bottom:-60px;left:-40px;
    background:radial-gradient(circle,rgba(245,158,11,0.1),transparent 70%);
    animation:orbFloat 18s ease-in-out infinite alternate-reverse;
  }

  .value-card {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:20px;
    padding:28px 24px;transition:all 0.4s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;
  }
  .value-card::before {
    content:'';position:absolute;inset:0;border-radius:20px;
    background:linear-gradient(135deg,rgba(251,191,36,0.06),transparent 60%);
    opacity:0;transition:opacity 0.4s ease;pointer-events:none;
  }
  .value-card:hover {
    transform:translateY(-5px);border-color:rgba(251,191,36,0.12);
    box-shadow:0 16px 40px -12px rgba(0,0,0,0.5),0 0 30px -8px rgba(251,191,36,0.05);
  }
  .value-card:hover::before { opacity:1; }
  .value-icon {
    width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;
    font-size:1.15rem;transition:all 0.3s ease;
  }
  .value-card:hover .value-icon { transform:scale(1.08); }

  .stat-block { text-align:center;padding:20px 12px;border-right:1px solid rgba(255,255,255,0.05); }
  .stat-block:last-child { border-right:none; }
  .stat-number {
    font-size:2rem;font-weight:800;line-height:1;
    background:linear-gradient(135deg,#fbbf24,#f59e0b);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  }

  .timeline-line {
    position:absolute;left:21px;top:0;bottom:0;width:2px;
    background:linear-gradient(180deg,rgba(251,191,36,0.25),rgba(251,191,36,0.04));
  }
  .timeline-dot {
    width:10px;height:10px;border-radius:50%;background:#fbbf24;
    border:2px solid #0a0a0f;position:absolute;left:17px;top:8px;z-index:2;
    box-shadow:0 0 10px rgba(251,191,36,0.3);
  }

  /* ===== CONTACT SECTION STYLES ===== */
  .form-card {
    background:#101018;border:1px solid rgba(255,255,255,0.05);border-radius:24px;
    padding:32px 28px;position:relative;overflow:hidden;
  }
  .form-card::before {
    content:'';position:absolute;top:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(251,191,36,0.2),transparent);
  }
  .form-group { margin-bottom:18px; }
  .form-label {
    display:block;font-size:0.75rem;font-weight:700;color:rgba(255,255,255,0.5);
    margin-bottom:7px;text-transform:uppercase;letter-spacing:0.04em;
  }
  .form-input {
    width:100%;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:12px 15px;font-size:0.84rem;color:#fff;
    font-family:'Plus Jakarta Sans',sans-serif;transition:all 0.25s ease;
  }
  .form-input::placeholder { color:rgba(255,255,255,0.18); }
  .form-input:focus {
    outline:none;border-color:rgba(251,191,36,0.4);
    box-shadow:0 0 0 3px rgba(251,191,36,0.06);background:rgba(255,255,255,0.04);
  }
  .form-input.error { border-color:rgba(239,68,68,0.4);box-shadow:0 0 0 3px rgba(239,68,68,0.06); }
  textarea.form-input { resize:vertical;min-height:110px;max-height:200px;line-height:1.6; }

  .btn-contact-submit {
    display:inline-flex;align-items:center;gap:10px;padding:13px 28px;border-radius:14px;
    font-size:0.86rem;font-weight:700;background:linear-gradient(135deg,#fbbf24,#f59e0b);
    color:#0a0a0f;border:none;cursor:pointer;transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;width:100%;justify-content:center;
  }
  .btn-contact-submit::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);transition:left 0.5s ease;
  }
  .btn-contact-submit:hover::before { left:100%; }
  .btn-contact-submit:hover { box-shadow:0 8px 28px -6px rgba(251,191,36,0.5);transform:translateY(-2px); }

  .form-alert {
    display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:14px;
    font-size:0.8rem;font-weight:600;margin-bottom:18px;
  }
  .form-alert.success { background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.15);color:#4ade80; }
  .form-alert.error { background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.15);color:#f87171; }

  .info-card {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:18px;
    padding:20px;transition:all 0.35s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden;
  }
  .info-card::before {
    content:'';position:absolute;inset:0;border-radius:18px;
    background:linear-gradient(135deg,rgba(251,191,36,0.05),transparent 60%);
    opacity:0;transition:opacity 0.35s ease;pointer-events:none;
  }
  .info-card:hover { transform:translateY(-3px);border-color:rgba(251,191,36,0.1);box-shadow:0 12px 32px -10px rgba(0,0,0,0.4); }
  .info-card:hover::before { opacity:1; }
  .info-icon {
    width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;
    font-size:1rem;transition:all 0.3s ease;flex-shrink:0;
  }
  .info-card:hover .info-icon { transform:scale(1.08); }

  .faq-item {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:16px;overflow:hidden;transition:all 0.3s ease;
  }
  .faq-item:hover { border-color:rgba(255,255,255,0.08); }
  .faq-item.open { border-color:rgba(251,191,36,0.15); }
  .faq-question {
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:16px 20px;cursor:pointer;transition:all 0.2s ease;border:none;background:none;
    width:100%;text-align:left;font-family:'Plus Jakarta Sans',sans-serif;
  }
  .faq-question:hover { background:rgba(255,255,255,0.02); }
  .faq-question h4 { font-size:0.83rem;font-weight:700;color:rgba(255,255,255,0.7);transition:color 0.2s; }
  .faq-item.open .faq-question h4 { color:#fbbf24; }
  .faq-arrow {
    width:26px;height:26px;border-radius:8px;background:rgba(255,255,255,0.04);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
    transition:all 0.3s ease;color:rgba(255,255,255,0.25);font-size:0.65rem;
  }
  .faq-item.open .faq-arrow { transform:rotate(180deg);background:rgba(251,191,36,0.1);color:#fbbf24; }
  .faq-answer { max-height:0;overflow:hidden;transition:max-height 0.35s cubic-bezier(.4,0,.2,1); }
  .faq-answer-inner { padding:0 20px 16px;font-size:0.78rem;color:rgba(255,255,255,0.35);line-height:1.7; }

  .map-card {
    background:#101018;border:1px solid rgba(255,255,255,0.04);border-radius:20px;overflow:hidden;position:relative;
  }
  .map-card::after {
    content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(10,10,15,0.2),rgba(10,10,15,0.05));
    pointer-events:none;border-radius:20px;
  }

  .social-link {
    display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;
    background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);
    text-decoration:none;transition:all 0.25s ease;
  }
  .social-link:hover { background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);transform:translateX(3px); }
  .social-icon {
    width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    font-size:0.9rem;flex-shrink:0;transition:transform 0.2s ease;
  }
  .social-link:hover .social-icon { transform:scale(1.08); }

  .section-divider {
    height:1px;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent);
    margin:0 auto;max-width:200px;
  }
</style>

</head>

<body>

<!-- ========== REVIEW MODAL ========== -->
<div class="review-overlay" id="reviewOverlay">
  <div class="review-modal">
    <div class="review-modal-header">
      <img id="reviewProdImg" src="" alt="" onerror="this.src='https://picsum.photos/seed/review/100/100.jpg'">
      <div class="min-w-0 flex-1">
        <h3 id="reviewProdName" class="text-sm font-bold text-white leading-snug truncate">Product Name</h3>
        <p id="reviewProdPrice" class="text-base font-extrabold text-gold-400 mt-0.5">Rs. 0</p>
        <div class="flex items-center gap-2 mt-1.5">
          <div id="reviewProdStars" class="flex items-center gap-0.5"></div>
          <span id="reviewProdStats" class="text-[10px] text-white/25 font-medium"></span>
        </div>
      </div>
      <button class="review-modal-close" onclick="closeReviewModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="review-modal-body" id="reviewBody">
      <div class="reviews-loading" id="reviewLoading"><div class="spinner"></div><p>Loading reviews...</p></div>
    </div>
  </div>
</div>

<!-- ========== TOAST CONTAINER ========== -->
<div class="toast-container" id="toastContainer"></div>


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
      <a href="index.php" class="text-sm font-medium text-white hover:text-gold-400 transition">Home</a>
      <a href="#products" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">Products</a>
      <a href="#about" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">About</a>
      <a href="#contact" class="text-sm font-medium text-white/50 hover:text-gold-400 transition">Contact</a>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
      <button onclick="toggleSearch()" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition text-white/60 hover:text-white">
        <i class="fa-solid fa-magnifying-glass text-sm"></i>
      </button>
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
              <a href="#" class="dropdown-item"><i class="fa-solid fa-heart"></i><span>Wishlist</span></a>
              <a href="#" class="dropdown-item"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
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
    <a href="#about" class="block py-2.5 text-sm font-medium text-white/50">About</a>
    <a href="#contact" class="block py-2.5 text-sm font-medium text-white/50">Contact</a>
    <?php if ($user): ?>
      <div class="dropdown-divider mt-2 mb-2"></div>
      <div class="flex items-center gap-3 py-2.5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400/20 to-gold-600/10 flex items-center justify-center text-gold-400 font-extrabold text-xs overflow-hidden">
          <?php if (!empty($user['image']) && file_exists("../backend/uploads/" . $user['image'])): ?>
            <img src="../backend/uploads/<?= $user['image'] ?>" class="w-full h-full object-cover rounded-lg" alt="">
          <?php else: ?>
            <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($user['name']) ?></p>
          <p class="text-[11px] text-white/30"><?= htmlspecialchars($user['email']) ?></p>
        </div>
      </div>
      <a href="#" class="block py-2.5 text-sm font-medium text-white/50">My Orders</a>
      <a href="cart.php" class="block py-2.5 text-sm font-medium text-white/50">My Cart</a>
      <a href="../backend/logout.php" class="block py-2.5 text-sm font-medium text-red-400/70 hover:text-red-400">Logout</a>
    <?php else: ?>
      <div class="dropdown-divider mt-2 mb-2"></div>
      <a href="../user/login.php" class="btn-login justify-center mt-1"><i class="fa-solid fa-right-to-bracket text-xs"></i><span>Login / Sign Up</span></a>
    <?php endif; ?>
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
          Next Level<br><span class="text-shimmer">Sound Experience</span>
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
          <div><p class="text-2xl font-extrabold text-white">50K+</p><p class="text-xs text-white/30 mt-0.5">Happy Customers</p></div>
          <div class="w-px h-10 bg-white/10"></div>
          <div><p class="text-2xl font-extrabold text-white">4.9★</p><p class="text-xs text-white/30 mt-0.5">Average Rating</p></div>
          <div class="w-px h-10 bg-white/10"></div>
          <div><p class="text-2xl font-extrabold text-white">200+</p><p class="text-xs text-white/30 mt-0.5">Products</p></div>
        </div>
      </div>
      <div class="flex-1 flex justify-center fade-up" style="animation-delay:0.15s">
        <div class="relative">
          <div class="absolute inset-0 bg-gradient-to-br from-gold-400/20 to-transparent rounded-full blur-3xl scale-75"></div>
          <img src="../backend/uploads/image5.png" alt="Featured Product" class="hero-img relative w-80 lg:w-[420px] drop-shadow-2xl" onerror="this.src='https://picsum.photos/seed/hero-beats/500/500.jpg'">
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
<section id="products" class="py-8 pb-16">
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
          $avg = round($product['avg_rating'], 1);
          $specs = [];
          if (!empty($product['specs_data'])) {
              $specPairs = explode('||', $product['specs_data']);
              foreach ($specPairs as $pair) {
                  $parts = explode('::', $pair, 2);
                  if (count($parts) == 2) { $specs[$parts[0]] = $parts[1]; }
              }
          }
      ?>
      <div class="prod-card fade-up" style="animation-delay:<?= $delay ?>s">
        <a href="product_detail.php?id=<?= $product['id'] ?>" class="prod-img-wrap block">
          <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>/400/300.jpg'">
          <span class="cat-badge"><i class="fa-solid fa-folder text-[8px] mr-1"></i><?= htmlspecialchars($product['category_name']) ?></span>
        </a>
        <div class="p-5 relative z-10">
          <h3 class="text-sm font-bold text-white leading-snug"><?= htmlspecialchars($product['product_name']) ?></h3>
          <p class="text-xs text-white/30 mt-1.5 leading-relaxed line-clamp-2"><?= htmlspecialchars($product['description'] ?? 'Premium quality product with exceptional performance.') ?></p>
          <?php if (!empty($specs)) { ?>
          <div class="flex flex-wrap gap-1.5 mt-3">
              <?php $displaySpecs = array_slice($specs, 0, 3);
              foreach ($displaySpecs as $sName => $sValue) { ?>
                  <span class="inline-flex items-center gap-1 bg-white/[0.04] border border-white/[0.06] rounded-lg px-2 py-1 text-[10px]">
                      <span class="text-white/40"><?= htmlspecialchars($sName) ?>:</span>
                      <span class="text-white/70 font-semibold"><?= htmlspecialchars($sValue) ?></span>
                  </span>
              <?php } ?>
              <?php if (count($specs) > 3) { ?>
                  <span class="text-[10px] text-gold-400/70 font-semibold self-center">+<?= count($specs) - 3 ?> more</span>
              <?php } ?>
          </div>
          <?php } ?>
          <div class="review-trigger flex items-center gap-1.5 mt-3" data-review-trigger="<?= $product['id'] ?>">
            <div class="flex items-center gap-0.5">
              <?php for ($i = 1; $i <= 5; $i++) {
                if ($i <= floor($avg)) { ?>
                  <i class="fa-solid fa-star text-gold-400 text-[9px] review-trigger-icon transition"></i>
              <?php } elseif ($i - 0.5 <= $avg) { ?>
                  <i class="fa-solid fa-star-half-stroke text-gold-400 text-[9px] review-trigger-icon transition"></i>
              <?php } else { ?>
                  <i class="fa-regular fa-star text-white/10 text-[9px] review-trigger-icon transition"></i>
              <?php } } ?>
            </div>
            <?php if ($product['total_reviews'] > 0) { ?>
              <span class="text-[10px] text-white/25 font-medium review-trigger-text transition"><?= $avg ?> (<?= $product['total_reviews'] ?> reviews)</span>
            <?php } else { ?>
              <span class="text-[10px] text-white/20 font-medium review-trigger-text transition">No reviews yet</span>
            <?php } ?>
            <i class="fa-solid fa-pen-to-square text-[8px] text-white/10 review-trigger-icon transition ml-auto"></i>
          </div>
          <div class="flex items-end justify-between mt-4 pt-4 border-t border-white/5">
            <div>
              <p class="text-[10px] text-white/25 uppercase tracking-wider font-medium">Price</p>
              <p class="text-xl font-extrabold text-gold-400 mt-0.5">Rs. <?= number_format($product['price'], 0) ?></p>
            </div>
            <div class="flex items-center gap-2">
              <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-view w-10 h-10 flex items-center justify-center" title="View Details"><i class="fa-solid fa-eye text-xs"></i></a>
              <button data-add-cart="<?= $product['id'] ?>" class="btn-cart w-10 h-10 flex items-center justify-center" title="Add to Cart"><i class="fa-solid fa-bag-shopping text-xs"></i></button>
            </div>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>
</section>


<!-- ========== SECTION DIVIDER ========== -->
<div class="section-divider"></div>


<!-- ========================================== -->
<!-- ========== ABOUT US SECTION ============== -->
<!-- ========================================== -->
<section id="about" class="py-20 relative overflow-hidden">
  <div class="section-orb section-orb-1"></div>
  <div class="section-orb section-orb-2"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-10 relative z-10">

    <!-- Section Header -->
    <div class="text-center mb-14 fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fa-solid fa-heart text-gold-400 text-[10px]"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Our Story</span>
      </div>
      <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">About <span class="text-shimmer">BeatsShop</span></h2>
      <p class="text-sm text-white/30 mt-3 max-w-lg mx-auto leading-relaxed">Born from a love of sound, built on trust, and driven by the mission to make premium audio accessible to everyone.</p>
    </div>

    <!-- Stats Bar -->
    <div class="bg-surface-800 border border-white/[0.05] rounded-2xl overflow-hidden mb-14 fade-up" style="animation-delay:0.05s">
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

    <!-- About Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center mb-16">
      <!-- Left: Image -->
      <div class="fade-up" style="animation-delay:0.08s">
        <div class="relative">
          <div class="absolute inset-0 bg-gradient-to-br from-gold-400/15 to-transparent rounded-3xl blur-2xl scale-90"></div>
          <img src="../backend/uploads/image5.png" alt="About BeatsShop" class="relative w-full max-w-md mx-auto rounded-3xl drop-shadow-2xl border border-white/[0.04]" style="animation:heroFloat 5s ease-in-out infinite" onerror="this.src='https://picsum.photos/seed/about-beats/600/500.jpg'">
          <!-- Floating badges -->
          <div class="absolute -left-3 sm:-left-6 top-1/4 bg-surface-700 border border-white/[0.06] rounded-2xl px-4 py-3 shadow-2xl z-10" style="animation:heroFloat 4s ease-in-out infinite 0.5s">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-green-500/15 flex items-center justify-center"><i class="fa-solid fa-truck-fast text-green-400 text-xs"></i></div>
              <div><p class="text-[10px] font-bold text-white/80">Free Delivery</p><p class="text-[9px] text-white/30">All over Pakistan</p></div>
            </div>
          </div>
          <div class="absolute -right-3 sm:-right-4 bottom-1/4 bg-surface-700 border border-white/[0.06] rounded-2xl px-4 py-3 shadow-2xl z-10" style="animation:heroFloat 4.5s ease-in-out infinite 1s">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-gold-500/15 flex items-center justify-center"><i class="fa-solid fa-shield-check text-gold-400 text-xs"></i></div>
              <div><p class="text-[10px] font-bold text-white/80">1 Year Warranty</p><p class="text-[9px] text-white/30">On all products</p></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Text -->
      <div class="fade-up" style="animation-delay:0.13s">
        <h3 class="text-xl font-extrabold text-white mb-4">We Started With One Simple Idea</h3>
        <p class="text-sm text-white/35 leading-relaxed mb-4">
          Frustrated by overpriced audio gear that didn't deliver on quality, our founder Ahmed Adil started BeatsShop from a small room in Karachi with just 15 products and a big dream — to bring studio-grade sound to everyone without the ridiculous markup.
        </p>
        <p class="text-sm text-white/35 leading-relaxed mb-6">
          Today, with 50,000+ happy customers and 200+ curated products, we've proven that premium audio doesn't have to cost a fortune. Every product is hand-tested, every order is packed with care, and every customer is treated like family.
        </p>

        <!-- Values mini list -->
        <div class="space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gold-500/10 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-check text-gold-400 text-xs"></i></div>
            <p class="text-sm text-white/55 font-medium">100% Original & Authentic Products</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gold-500/10 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-check text-gold-400 text-xs"></i></div>
            <p class="text-sm text-white/55 font-medium">Free Delivery Across Pakistan</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gold-500/10 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-check text-gold-400 text-xs"></i></div>
            <p class="text-sm text-white/55 font-medium">7-Day Easy Returns & Refunds</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gold-500/10 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-check text-gold-400 text-xs"></i></div>
            <p class="text-sm text-white/55 font-medium">1-Year Warranty on All Products</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Core Values -->
    <div class="mb-16">
      <h3 class="text-center text-lg font-extrabold text-white mb-8 fade-up">Why People <span class="text-shimmer">Choose Us</span></h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="value-card fade-up" style="animation-delay:0.05s">
          <div class="value-icon bg-gold-500/10 text-gold-400 mb-4"><i class="fa-solid fa-headphones-simple"></i></div>
          <h4 class="text-sm font-bold text-white mb-1.5">Premium Sound</h4>
          <p class="text-xs text-white/30 leading-relaxed">Every product hand-tested for audio quality. Only gear that passes our benchmark makes the cut.</p>
        </div>
        <div class="value-card fade-up" style="animation-delay:0.1s">
          <div class="value-icon bg-blue-500/10 text-blue-400 mb-4"><i class="fa-solid fa-tags"></i></div>
          <h4 class="text-sm font-bold text-white mb-1.5">Fair Pricing</h4>
          <p class="text-xs text-white/30 leading-relaxed">Direct sourcing, no middlemen. Premium audio at prices that actually make sense.</p>
        </div>
        <div class="value-card fade-up" style="animation-delay:0.15s">
          <div class="value-icon bg-green-500/10 text-green-400 mb-4"><i class="fa-solid fa-handshake-angle"></i></div>
          <h4 class="text-sm font-bold text-white mb-1.5">Customer First</h4>
          <p class="text-xs text-white/30 leading-relaxed">Pre-sale guidance to post-sale support. Your satisfaction isn't a goal — it's our standard.</p>
        </div>
        <div class="value-card fade-up" style="animation-delay:0.2s">
          <div class="value-icon bg-purple-500/10 text-purple-400 mb-4"><i class="fa-solid fa-rotate-left"></i></div>
          <h4 class="text-sm font-bold text-white mb-1.5">Easy Returns</h4>
          <p class="text-xs text-white/30 leading-relaxed">7-day return window, full refund, no questions asked. We stand behind what we sell.</p>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <!-- <div class="max-w-2xl mx-auto">
      <h3 class="text-center text-lg font-extrabold text-white mb-10 fade-up">Our <span class="text-shimmer">Journey</span></h3>
      <div class="relative pl-14">
        <div class="timeline-line"></div>
        <div class="relative pb-10 fade-up" style="animation-delay:0.05s">
          <div class="timeline-dot"></div>
          <div class="bg-surface-800 border border-white/[0.05] rounded-2xl p-5">
            <span class="text-xs font-extrabold text-gold-400 uppercase tracking-wider">2021</span>
            <h4 class="text-sm font-bold text-white mt-1.5">The Spark</h4>
            <p class="text-xs text-white/30 mt-1 leading-relaxed">Started from a small room in Karachi with 15 products and a dream to democratize premium audio.</p>
          </div>
        </div>
        <div class="relative pb-10 fade-up" style="animation-delay:0.1s">
          <div class="timeline-dot"></div>
          <div class="bg-surface-800 border border-white/[0.05] rounded-2xl p-5">
            <span class="text-xs font-extrabold text-gold-400 uppercase tracking-wider">2022</span>
            <h4 class="text-sm font-bold text-white mt-1.5">First 10K Customers</h4>
            <p class="text-xs text-white/30 mt-1 leading-relaxed">Quality spoke for itself. Crossed 10,000 happy customers and expanded to 80+ products.</p>
          </div>
        </div>
        <div class="relative pb-10 fade-up" style="animation-delay:0.15s">
          <div class="timeline-dot"></div>
          <div class="bg-surface-800 border border-white/[0.05] rounded-2xl p-5">
            <span class="text-xs font-extrabold text-gold-400 uppercase tracking-wider">2023</span>
            <h4 class="text-sm font-bold text-white mt-1.5">Going Nationwide</h4>
            <p class="text-xs text-white/30 mt-1 leading-relaxed">Free delivery across Pakistan. Launched warranty program and customer support hotline.</p>
          </div>
        </div>
        <div class="relative fade-up" style="animation-delay:0.2s">
          <div class="timeline-dot" style="background:#22c55e;box-shadow:0 0 10px rgba(34,197,94,0.4)"></div>
          <div class="bg-surface-800 border border-green-500/10 rounded-2xl p-5">
            <span class="text-xs font-extrabold text-green-400 uppercase tracking-wider">2025 — Now</span>
            <h4 class="text-sm font-bold text-white mt-1.5">50K+ Strong & Growing</h4>
            <p class="text-xs text-white/30 mt-1 leading-relaxed">200+ products, full review system, and expanding into smart audio. The beat goes on.</p>
          </div>
        </div>
      </div>
    </div> -->

  </div>
</section>


<!-- ========== SECTION DIVIDER ========== -->
<div class="section-divider"></div>

<!-- ========== CONTACT US SECTION ============== -->

<section id="contact" class="py-20 relative overflow-hidden">
  <div class="section-orb section-orb-1" style="left:-80px;right:auto"></div>
  <div class="section-orb section-orb-2" style="right:-50px;left:auto"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-10 relative z-10">

    <!-- Section Header -->
    <div class="text-center mb-14 fade-up">
      <div class="inline-flex items-center gap-2 bg-gold-500/10 border border-gold-500/20 rounded-full px-4 py-1.5 mb-4">
        <i class="fa-solid fa-message text-gold-400 text-[10px]"></i>
        <span class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Get in Touch</span>
      </div>
      <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Contact <span class="text-shimmer">Us</span></h2>
      <p class="text-sm text-white/30 mt-3 max-w-lg mx-auto leading-relaxed">Have a question, feedback, or need help? Our team is ready — reach out anytime.</p>
    </div>

    <!-- Form + Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mb-14">

      <!-- LEFT: Form -->
      <div class="lg:col-span-3 fade-up" style="animation-delay:0.05s">
        <div class="form-card">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gold-500/10 flex items-center justify-center">
              <i class="fa-solid fa-paper-plane text-gold-400 text-sm"></i>
            </div>
            <div>
              <h3 class="text-base font-bold text-white">Send a Message</h3>
              <p class="text-xs text-white/30">We typically respond within 24 hours</p>
            </div>
          </div>

          <?php if ($formMsg): ?>
            <div class="form-alert <?= $formType ?>">
              <i class="fa-solid <?= $formType === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
              <span><?= htmlspecialchars($formMsg) ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" action="#contact" id="contactForm" novalidate>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="contact_name" class="form-input" placeholder="John Doe" required value="<?= htmlspecialchars($c_name ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="contact_email" class="form-input" placeholder="john@example.com" required value="<?= htmlspecialchars($c_email ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Subject</label>
              <input type="text" name="contact_subject" class="form-input" placeholder="e.g. Order issue, Product inquiry" required value="<?= htmlspecialchars($c_subject ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Message</label>
              <textarea name="contact_message" class="form-input" placeholder="Tell us how we can help you..." required><?= htmlspecialchars($c_message ?? '') ?></textarea>
            </div>
            <button type="submit" name="submit_contact" class="btn-contact-submit">
              <i class="fa-solid fa-paper-plane text-sm"></i>
              <span>Send Message</span>
            </button>
          </form>
        </div>
      </div>

      <!-- RIGHT: Info Cards -->
      <div class="lg:col-span-2 space-y-4 fade-up" style="animation-delay:0.12s">

        <div class="info-card">
          <div class="flex items-start gap-3.5">
            <div class="info-icon bg-gold-500/10 text-gold-400"><i class="fa-solid fa-envelope"></i></div>
            <div>
              <h4 class="text-sm font-bold text-white mb-0.5">Email Us</h4>
              <p class="text-[11px] text-white/30 mb-1.5">For general inquiries & support</p>
              <a href="mailto:ahmedadilbaloch95@gmail.com" class="text-xs text-gold-400 font-semibold hover:text-gold-300 transition">ahmedadilbaloch95@gmail.com</a>
            </div>
          </div>
        </div>

        <div class="info-card">
          <div class="flex items-start gap-3.5">
            <div class="info-icon bg-green-500/10 text-green-400"><i class="fa-solid fa-phone"></i></div>
            <div>
              <h4 class="text-sm font-bold text-white mb-0.5">Call Us</h4>
              <p class="text-[11px] text-white/30 mb-1.5">Mon–Sat, 10 AM – 8 PM PKT</p>
              <a href="tel:+923233703689" class="text-xs text-green-400 font-semibold hover:text-green-300 transition">+92 323 3703689</a>
            </div>
          </div>
        </div>

        <div class="info-card">
          <div class="flex items-start gap-3.5">
            <div class="info-icon bg-emerald-500/10 text-emerald-400"><i class="fa-brands fa-whatsapp"></i></div>
            <div>
              <h4 class="text-sm font-bold text-white mb-0.5">WhatsApp</h4>
              <p class="text-[11px] text-white/30 mb-1.5">Quick replies & order support</p>
              <a href="https://wa.me/923233703689" target="_blank" class="text-xs text-emerald-400 font-semibold hover:text-emerald-300 transition">Chat on WhatsApp →</a>
            </div>
          </div>
        </div>

        <div class="info-card">
          <div class="flex items-start gap-3.5">
            <div class="info-icon bg-blue-500/10 text-blue-400"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <h4 class="text-sm font-bold text-white mb-0.5">Visit Us</h4>
              <p class="text-[11px] text-white/30">Karachi, Sindh, Pakistan</p>
              <p class="text-[10px] text-white/18 mt-0.5">Walk-ins by appointment</p>
            </div>
          </div>
        </div>

        <!-- Social Links -->
        <div class="pt-1">
          <p class="text-[11px] font-bold text-white/35 uppercase tracking-wider mb-2.5">Follow Us</p>
          <div class="space-y-2">
            <a href="#" class="social-link">
              <div class="social-icon bg-pink-500/10 text-pink-400"><i class="fa-brands fa-instagram"></i></div>
              <div><p class="text-xs font-bold text-white/60">Instagram</p><p class="text-[10px] text-white/22">@beatsshop_pk</p></div>
            </a>
            <a href="#" class="social-link">
              <div class="social-icon bg-blue-500/10 text-blue-400"><i class="fa-brands fa-facebook-f"></i></div>
              <div><p class="text-xs font-bold text-white/60">Facebook</p><p class="text-[10px] text-white/22">BeatsShop Pakistan</p></div>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Map -->
    <div class="mb-14 fade-up" style="animation-delay:0.05s">
      <div class="map-card">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d57901.25736088782!2d67.0011362!3d24.8609654!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb33f90157042d3%3A0x93d609e8bfb4e64!2sKarachi%2C%20Pakistan!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s"
          width="100%" height="320" style="border:0;display:block;border-radius:20px;"
          allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>

    <!-- FAQ -->
    <div class="max-w-2xl mx-auto">
      <div class="text-center mb-8 fade-up">
        <h3 class="text-lg font-extrabold text-white">Frequently Asked <span class="text-shimmer">Questions</span></h3>
      </div>
      <div class="space-y-3" id="faqContainer">
        <div class="faq-item fade-up" style="animation-delay:0.05s">
          <button class="faq-question" onclick="toggleFaq(this)">
            <h4>How long does delivery take?</h4>
            <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
          </button>
          <div class="faq-answer"><div class="faq-answer-inner">We deliver within 2–5 business days across major cities. Remote areas may take 5–7 days. You'll receive a tracking number via SMS/email.</div></div>
        </div>
        <div class="faq-item fade-up" style="animation-delay:0.08s">
          <button class="faq-question" onclick="toggleFaq(this)">
            <h4>Is delivery really free?</h4>
            <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
          </button>
          <div class="faq-answer"><div class="faq-answer-inner">Yes! Free delivery on all orders across Pakistan — no minimum order required. Our way of saying thank you.</div></div>
        </div>
        <div class="faq-item fade-up" style="animation-delay:0.11s">
          <button class="faq-question" onclick="toggleFaq(this)">
            <h4>What's your return/refund policy?</h4>
            <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
          </button>
          <div class="faq-answer"><div class="faq-answer-inner">Return any product within 7 days in original condition for a full refund or exchange. Defective items covered under our 1-year warranty.</div></div>
        </div>
        <div class="faq-item fade-up" style="animation-delay:0.14s">
          <button class="faq-question" onclick="toggleFaq(this)">
            <h4>Do products come with warranty?</h4>
            <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
          </button>
          <div class="faq-answer"><div class="faq-answer-inner">All products include minimum 1-year warranty. Premium items may have up to 2 years. Warranty cards included in every package.</div></div>
        </div>
        <div class="faq-item fade-up" style="animation-delay:0.17s">
          <button class="faq-question" onclick="toggleFaq(this)">
            <h4>What payment methods do you accept?</h4>
            <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
          </button>
          <div class="faq-answer"><div class="faq-answer-inner">COD, JazzCash, EasyPaisa, bank transfer, and all major debit/credit cards. Online payments processed through trusted Pakistani gateways.</div></div>
        </div>
        <div class="faq-item fade-up" style="animation-delay:0.2s">
          <button class="faq-question" onclick="toggleFaq(this)">
            <h4>Are your products original?</h4>
            <div class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></div>
          </button>
          <div class="faq-answer"><div class="faq-answer-inner">100%. We source from authorized distributors. Every product is genuine with original packaging and warranty documentation. Zero tolerance for counterfeits.</div></div>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ========== SECTION DIVIDER ========== -->
<div class="section-divider"></div>


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
        <a href="#products" class="footer-link">Products</a>
        <a href="#about" class="footer-link">About Us</a>
        <a href="#contact" class="footer-link">Contact Us</a>
      </div>
      <div>
        <p class="text-xs font-bold text-white/50 uppercase tracking-wider mb-4">Support</p>
        <a href="#contact" class="footer-link">Help Center</a>
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


<!-- ========== SCRIPTS ========== -->
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
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    if (profileOpen) { profileOpen = false; document.getElementById('profileDropdown').classList.remove('open'); }
    closeReviewModal();
  }
});


/* ===== FAQ ACCORDION ===== */
function toggleFaq(btn) {
  var item = btn.closest('.faq-item');
  var answer = item.querySelector('.faq-answer');
  var inner = answer.querySelector('.faq-answer-inner');
  var isOpen = item.classList.contains('open');

  document.querySelectorAll('.faq-item.open').forEach(function(openItem) {
    openItem.classList.remove('open');
    openItem.querySelector('.faq-answer').style.maxHeight = '0';
  });

  if (!isOpen) {
    item.classList.add('open');
    answer.style.maxHeight = inner.scrollHeight + 20 + 'px';
  }
}


/* ===== CONTACT FORM VALIDATION ===== */
var contactForm = document.getElementById('contactForm');
if (contactForm) {
  contactForm.addEventListener('submit', function(e) {
    var inputs = this.querySelectorAll('.form-input');
    var valid = true;
    inputs.forEach(function(input) {
      input.classList.remove('error');
      if (!input.value.trim()) { input.classList.add('error'); valid = false; }
    });
    var emailInput = this.querySelector('input[type="email"]');
    if (emailInput && emailInput.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
      emailInput.classList.add('error'); valid = false;
    }
    var textarea = this.querySelector('textarea');
    if (textarea && textarea.value.trim().length < 10) { textarea.classList.add('error'); valid = false; }
    if (!valid) e.preventDefault();
  });
  contactForm.querySelectorAll('.form-input').forEach(function(input) {
    input.addEventListener('focus', function() { this.classList.remove('error'); });
  });
}


/* ===== REVIEW SYSTEM ===== */
var selectedRating = 0, hoverRating = 0, currentReviewProduct = 0;
var isLoggedIn = <?= $user ? 'true' : 'false' ?>;
var ratingLabels = ['', 'Terrible', 'Poor', 'Average', 'Good', 'Excellent'];

function openReviewModal(productId) {
  currentReviewProduct = productId;
  selectedRating = 0; hoverRating = 0;
  var overlay = document.getElementById('reviewOverlay');
  var body = document.getElementById('reviewBody');
  body.innerHTML = '<div class="reviews-loading"><div class="spinner"></div><p>Loading reviews...</p></div>';
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  fetch('fetch_reviews.php?product_id=' + productId)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.error) { body.innerHTML = '<div class="reviews-empty"><i class="fa-solid fa-triangle-exclamation"></i><p>' + data.error + '</p></div>'; return; }

      document.getElementById('reviewProdImg').src = data.product_image || 'https://picsum.photos/seed/rev' + productId + '/100/100.jpg';
      document.getElementById('reviewProdName').textContent = data.product_name;
      document.getElementById('reviewProdPrice').textContent = 'Rs. ' + Number(data.product_price).toLocaleString();

      var headerStars = '';
      for (var i = 1; i <= 5; i++) {
        if (i <= Math.floor(data.avg_rating)) headerStars += '<i class="fa-solid fa-star text-gold-400 text-[10px]"></i>';
        else if (i - 0.5 <= data.avg_rating) headerStars += '<i class="fa-solid fa-star-half-stroke text-gold-400 text-[10px]"></i>';
        else headerStars += '<i class="fa-regular fa-star text-white/10 text-[10px]"></i>';
      }
      document.getElementById('reviewProdStars').innerHTML = headerStars;
      document.getElementById('reviewProdStats').textContent = data.avg_rating + ' (' + data.total_reviews + ' reviews)';

      var html = '<div class="write-review-section">';
      if (!isLoggedIn) {
        html += '<div class="login-to-review"><i class="fa-solid fa-lock"></i><p>Please <a href="../user/login.php">login</a> to write a review.</p></div>';
      } else if (data.user_review) {
        html += '<div class="already-reviewed"><i class="fa-solid fa-circle-check"></i><p>You already reviewed this product — ' + data.user_review.rating + ' star' + (data.user_review.rating > 1 ? 's' : '') + '</p></div>';
      } else {
        html += '<h4><i class="fa-solid fa-pen"></i> Write a Review</h4>';
        html += '<div class="star-selector" id="starSelector">';
        for (var i = 1; i <= 5; i++) html += '<button type="button" class="star-btn" data-star="' + i + '"><i class="fa-solid fa-star"></i></button>';
        html += '<span class="star-label" id="starLabel">Select rating</span></div>';
        html += '<textarea class="review-textarea" id="reviewComment" placeholder="Share your experience..." maxlength="500"></textarea>';
        html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px">';
        html += '<span id="charCount" style="font-size:0.7rem;color:rgba(255,255,255,0.15)">0 / 500</span>';
        html += '<button class="btn-submit-review" id="btnSubmitReview" onclick="submitReview()" disabled><i class="fa-solid fa-paper-plane text-xs"></i> Submit Review</button></div>';
      }
      html += '</div>';

      html += '<div class="reviews-list-header"><h4><i class="fa-solid fa-comments text-[10px]"></i> All Reviews</h4><span>' + data.total_reviews + '</span></div>';

      if (data.reviews && data.reviews.length > 0) {
        for (var r = 0; r < data.reviews.length; r++) {
          var rev = data.reviews[r];
          html += '<div class="review-item"><div class="review-item-top">';
          html += '<div class="review-avatar">';
          if (rev.user_image) html += '<img src="../backend/uploads/' + rev.user_image + '" alt="" onerror="this.parentNode.textContent=\'' + rev.user_name.charAt(0).toUpperCase() + '\'">';
          else html += rev.user_name.charAt(0).toUpperCase();
          html += '</div>';
          html += '<span class="review-username">' + escapeHtml(rev.user_name) + '</span>';
          if (rev.is_own) html += '<span style="font-size:0.6rem;font-weight:700;color:rgba(251,191,36,0.6);background:rgba(251,191,36,0.08);padding:2px 7px;border-radius:5px;margin-left:4px">YOU</span>';
          html += '<span class="review-time">' + timeAgo(rev.created_at) + '</span></div>';
          html += '<div class="review-stars">';
          for (var s = 1; s <= 5; s++) html += s <= rev.rating ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
          html += '</div>';
          html += '<p class="review-comment">' + escapeHtml(rev.comment) + '</p></div>';
        }
      } else {
        html += '<div class="reviews-empty"><i class="fa-regular fa-comment-dots"></i><p>No reviews yet. Be the first!</p></div>';
      }

      body.innerHTML = html;
      if (isLoggedIn && !data.user_review) { bindStarSelector(); bindCharCount(); }
    })
    .catch(function() { body.innerHTML = '<div class="reviews-empty"><i class="fa-solid fa-triangle-exclamation"></i><p>Failed to load reviews.</p></div>'; });
}

function closeReviewModal() {
  var overlay = document.getElementById('reviewOverlay');
  if (!overlay.classList.contains('open')) return;
  overlay.classList.remove('open');
  document.body.style.overflow = '';
  currentReviewProduct = 0;
}
document.getElementById('reviewOverlay').addEventListener('click', function(e) { if (e.target === this) closeReviewModal(); });

function bindStarSelector() {
  var selector = document.getElementById('starSelector');
  if (!selector) return;
  var stars = selector.querySelectorAll('.star-btn');
  var label = document.getElementById('starLabel');
  stars.forEach(function(star) {
    star.addEventListener('mouseenter', function() { hoverRating = parseInt(this.dataset.star); updateStarDisplay(stars, hoverRating, selectedRating, label); });
    star.addEventListener('mouseleave', function() { hoverRating = 0; updateStarDisplay(stars, hoverRating, selectedRating, label); });
    star.addEventListener('click', function() { selectedRating = parseInt(this.dataset.star); updateStarDisplay(stars, 0, selectedRating, label); checkSubmitReady(); });
  });
}
function updateStarDisplay(stars, hover, selected, label) {
  var active = hover > 0 ? hover : selected;
  stars.forEach(function(s) { var val = parseInt(s.dataset.star); s.classList.remove('hovered', 'selected'); if (val <= active) s.classList.add(hover > 0 ? 'hovered' : 'selected'); });
  if (label) { if (active > 0) { label.textContent = ratingLabels[active]; label.classList.add('active'); } else { label.textContent = 'Select rating'; label.classList.remove('active'); } }
}
function bindCharCount() {
  var textarea = document.getElementById('reviewComment');
  var counter = document.getElementById('charCount');
  if (!textarea || !counter) return;
  textarea.addEventListener('input', function() { counter.textContent = this.value.length + ' / 500'; counter.style.color = this.value.length > 450 ? 'rgba(251,191,36,0.6)' : 'rgba(255,255,255,0.15)'; checkSubmitReady(); });
}
function checkSubmitReady() {
  var btn = document.getElementById('btnSubmitReview'); var textarea = document.getElementById('reviewComment');
  if (!btn || !textarea) return; btn.disabled = !(selectedRating > 0 && textarea.value.trim().length >= 3);
}
function submitReview() {
  if (selectedRating === 0 || !currentReviewProduct) return;
  var comment = document.getElementById('reviewComment').value.trim();
  if (comment.length < 3) { showToast('Please write at least 3 characters.', 'error'); return; }
  var btn = document.getElementById('btnSubmitReview'); var origHTML = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Submitting...'; btn.disabled = true;
  var formData = new FormData(); formData.append('product_id', currentReviewProduct); formData.append('rating', selectedRating); formData.append('comment', comment);
  fetch('submit_review.php', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) { showToast('Review submitted!', 'success'); openReviewModal(currentReviewProduct); updateCardRating(currentReviewProduct, data.new_avg, data.new_total); }
      else { showToast(data.message || 'Failed to submit.', 'error'); btn.innerHTML = origHTML; btn.disabled = false; checkSubmitReady(); }
    })
    .catch(function() { showToast('Network error.', 'error'); btn.innerHTML = origHTML; btn.disabled = false; checkSubmitReady(); });
}
function updateCardRating(productId, newAvg, newTotal) {
  var trigger = document.querySelector('[data-review-trigger="' + productId + '"]');
  if (!trigger) return;
  var avg = Math.round(newAvg * 10) / 10;
  var starsHtml = '';
  for (var i = 1; i <= 5; i++) {
    if (i <= Math.floor(avg)) starsHtml += '<i class="fa-solid fa-star text-gold-400 text-[9px] review-trigger-icon transition"></i>';
    else if (i - 0.5 <= avg) starsHtml += '<i class="fa-solid fa-star-half-stroke text-gold-400 text-[9px] review-trigger-icon transition"></i>';
    else starsHtml += '<i class="fa-regular fa-star text-white/10 text-[9px] review-trigger-icon transition"></i>';
  }
  var sc = trigger.querySelector('.flex.items-center.gap-0\\.5');
  if (sc) sc.innerHTML = starsHtml;
  var te = trigger.querySelector('.review-trigger-text');
  if (te) te.textContent = avg + ' (' + newTotal + ' reviews)';
}
document.addEventListener('click', function(e) {
  var trigger = e.target.closest('[data-review-trigger]');
  if (trigger) { e.preventDefault(); e.stopPropagation(); openReviewModal(parseInt(trigger.dataset.reviewTrigger)); }
});


/* ===== HELPERS ===== */
function timeAgo(dateStr) {
  var now = new Date(); var date = new Date(dateStr.replace(/-/g, '/')); var seconds = Math.floor((now - date) / 1000);
  if (seconds < 60) return 'Just now';
  var minutes = Math.floor(seconds / 60); if (minutes < 60) return minutes + ' min ago';
  var hours = Math.floor(minutes / 60); if (hours < 24) return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
  var days = Math.floor(hours / 24); if (days < 30) return days + ' day' + (days > 1 ? 's' : '') + ' ago';
  var months = Math.floor(days / 30); if (months < 12) return months + ' month' + (months > 1 ? 's' : '') + ' ago';
  var years = Math.floor(months / 12); return years + ' year' + (years > 1 ? 's' : '') + ' ago';
}
function escapeHtml(str) { var div = document.createElement('div'); div.appendChild(document.createTextNode(str)); return div.innerHTML; }


/* ===== TOAST ===== */
function showToast(message, type) {
  var container = document.getElementById('toastContainer');
  var toast = document.createElement('div');
  toast.className = 'toast toast-' + (type || 'info');
  var iconMap = { success: 'fa-solid fa-circle-check', error: 'fa-solid fa-circle-xmark', info: 'fa-solid fa-circle-info' };
  toast.innerHTML = '<i class="' + (iconMap[type] || iconMap.info) + '"></i><span>' + message + '</span>';
  container.appendChild(toast);
  requestAnimationFrame(function() { requestAnimationFrame(function() { toast.classList.add('show'); }); });
  setTimeout(function() { toast.classList.remove('show'); toast.classList.add('exit'); setTimeout(function() { toast.remove(); }, 350); }, 3500);
}


/* ===== CATEGORY AJAX ===== */
var currentCat = <?= $active_cat ?>;
function loadCategory(catId) {
  if (catId === currentCat) return;
  currentCat = catId;
  var grid = document.getElementById('productsGrid');
  var title = document.getElementById('sectionTitle');
  var countEl = document.getElementById('sectionCount');
  var clearBtn = document.getElementById('clearFilter');
  document.querySelectorAll('#catBar .cat-pill').forEach(function(pill) { pill.classList.toggle('active', parseInt(pill.dataset.cat) === catId); });
  clearBtn.classList.toggle('hidden', catId === 0);
  grid.innerHTML = '';
  for (var i = 0; i < 4; i++) grid.innerHTML += '<div class="rounded-2xl overflow-hidden border border-white/[0.04] bg-[#101018]"><div class="skeleton" style="height:260px"></div><div class="p-5 space-y-3"><div class="skeleton h-4 w-3/4 rounded-lg"></div><div class="skeleton h-3 w-full rounded-lg"></div><div class="skeleton h-3 w-5/6 rounded-lg"></div><div class="pt-4 mt-4 border-t border-white/5 flex justify-between items-end"><div><div class="skeleton h-3 w-12 mb-2 rounded-lg"></div><div class="skeleton h-6 w-20 rounded-lg"></div></div><div class="flex gap-2"><div class="skeleton w-10 h-10 rounded-xl"></div><div class="skeleton w-10 h-10 rounded-xl"></div></div></div></div></div>';
  document.getElementById('products').scrollIntoView({ behavior: 'smooth', block: 'start' });
  fetch('fetch_products.php?cat_id=' + catId)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.html === 'EMPTY') {
        grid.innerHTML = '<div class="text-center py-20 col-span-full"><div class="empty-icon inline-block mb-5"><div class="w-20 h-20 rounded-2xl bg-surface-700 flex items-center justify-center mx-auto"><i class="fa-solid fa-box-open text-white/10 text-3xl"></i></div></div><h3 class="text-lg font-bold text-white/40">No Products Found</h3><p class="text-sm text-white/20 mt-2">This category doesn\'t have any products yet.</p><button onclick="loadCategory(0)" class="inline-flex items-center gap-2 mt-5 text-sm font-semibold text-gold-400 hover:text-gold-300 transition bg-transparent border-none cursor-pointer"><i class="fa-solid fa-arrow-left text-xs"></i> Browse All Products</button></div>';
        title.textContent = data.name; countEl.textContent = '0 products available';
      } else {
        grid.innerHTML = data.html; title.textContent = data.name;
        countEl.textContent = data.count + ' product' + (data.count !== 1 ? 's' : '') + ' available';
        grid.querySelectorAll('.fade-up').forEach(function(el) {
          el.style.animationPlayState = 'paused';
          var obs = new IntersectionObserver(function(entries) { entries.forEach(function(entry) { if (entry.isIntersecting) { entry.target.style.animationPlayState = 'running'; obs.unobserve(entry.target); } }); }, { threshold: 0.1 });
          obs.observe(el);
        });
      }
    })
    .catch(function() { grid.innerHTML = '<p class="text-center text-red-400 py-20 col-span-full">Failed to load products.</p>'; });
  history.pushState({ cat_id: catId }, '', catId === 0 ? 'index.php' : 'index.php?cat_id=' + catId);
}
window.addEventListener('popstate', function(e) { var catId = (e.state && e.state.cat_id !== undefined) ? e.state.cat_id : 0; currentCat = -1; loadCategory(catId); });


/* ===== AJAX ADD TO CART ===== */
document.addEventListener('click', function(e) {
  var cartBtn = e.target.closest('.btn-cart[data-add-cart]');
  if (!cartBtn) return;
  e.preventDefault();
  var pid = cartBtn.dataset.addCart;
  var origHTML = cartBtn.innerHTML;
  cartBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i>';
  cartBtn.style.pointerEvents = 'none';
  var formData = new FormData(); formData.append('product_id', pid); formData.append('quantity', 1);
  fetch('add_to_cart.php', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        cartBtn.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
        cartBtn.style.background = 'linear-gradient(135deg,#22c55e,#16a34a)';
        var badge = document.querySelector('a[href="cart.php"] .cart-pulse');
        if (badge) { badge.textContent = data.cart_count; badge.classList.remove('cart-pulse'); void badge.offsetWidth; badge.classList.add('cart-pulse'); }
        else { var cartLink = document.querySelector('a[href="cart.php"]'); if (cartLink) { var span = document.createElement('span'); span.className = 'cart-pulse absolute -top-1 -right-1 w-5 h-5 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 text-surface-900 text-[10px] font-extrabold flex items-center justify-center'; span.textContent = data.cart_count; cartLink.appendChild(span); } }
        setTimeout(function() { cartBtn.innerHTML = origHTML; cartBtn.style.background = ''; cartBtn.style.pointerEvents = ''; }, 1500);
      } else {
        cartBtn.innerHTML = '<i class="fa-solid fa-xmark text-xs"></i>';
        cartBtn.style.background = 'linear-gradient(135deg,#ef4444,#dc2626)';
        setTimeout(function() { cartBtn.innerHTML = origHTML; cartBtn.style.background = ''; cartBtn.style.pointerEvents = ''; }, 1200);
      }
    })
    .catch(function() { cartBtn.innerHTML = origHTML; cartBtn.style.pointerEvents = ''; window.location.href = 'add_to_cart.php'; });
});


/* ===== SEARCH & MENU ===== */
function toggleSearch() { var bar = document.getElementById('searchBar'); bar.classList.toggle('hidden'); if (!bar.classList.contains('hidden')) bar.querySelector('input').focus(); }
function toggleMobileMenu() { document.getElementById('mobileMenu').classList.toggle('hidden'); }

document.querySelectorAll('a[href^="#"]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    var target = document.querySelector(this.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); document.getElementById('mobileMenu').classList.add('hidden'); }
  });
});

window.addEventListener('scroll', function() {
  var nav = document.querySelector('.nav-blur');
  if (window.scrollY > 50) { nav.style.borderBottomColor = 'rgba(255,255,255,0.06)'; nav.style.background = 'rgba(10,10,15,0.9)'; }
  else { nav.style.borderBottomColor = 'rgba(255,255,255,0.04)'; nav.style.background = 'rgba(10,10,15,0.75)'; }
});

var observer = new IntersectionObserver(function(entries) { entries.forEach(function(entry) { if (entry.isIntersecting) entry.target.style.animationPlayState = 'running'; }); }, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(function(el) { el.style.animationPlayState = 'paused'; observer.observe(el); });
</script>

</body>
</html>