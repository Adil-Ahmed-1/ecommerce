<?php
session_start();
include("../backend/config/db.php");

 $user = null;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $u_res = mysqli_query($conn, "SELECT id, name, email, image FROM users WHERE id = $uid LIMIT 1");
    $user = mysqli_fetch_assoc($u_res);
}

 $cat_query = "SELECT * FROM categories";
 $cat_result = mysqli_query($conn, $cat_query);

if (isset($_GET['cat_id']) && $_GET['cat_id'] !== "") {
    $cat_id = intval($_GET['cat_id']);
    $product_query = "SELECT p.*, c.category_name, IFNULL(AVG(r.rating),0) as avg_rating, COUNT(r.id) as total_reviews FROM products p JOIN categories c ON p.category_id = c.id LEFT JOIN reviews r ON p.id = r.product_id WHERE p.category_id = $cat_id GROUP BY p.id ORDER BY p.id DESC";
    $active_cat = $cat_id;
} else {
    $product_query = "SELECT p.*, c.category_name, IFNULL(AVG(r.rating),0) as avg_rating, COUNT(r.id) as total_reviews FROM products p JOIN categories c ON p.category_id = c.id LEFT JOIN reviews r ON p.id = r.product_id GROUP BY p.id ORDER BY p.id DESC";
    $active_cat = 0;
}
 $product_result = mysqli_query($conn, $product_query);
 $product_count = mysqli_num_rows($product_result);

 $active_cat_name = 'All Products';
if ($active_cat > 0) {
    $ac = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category_name FROM categories WHERE id = $active_cat"));
    if ($ac) $active_cat_name = $ac['category_name'];
}

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = $uid");
    $row = mysqli_fetch_assoc($res);
    $count = $row['total'] ?? 0;
} else {
    $count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
}

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
            $formMsg = 'Message sent successfully!';
            $formType = 'success';
            $c_name = $c_email = $c_subject = $c_message = '';
        } else {
            $formMsg = 'Something went wrong.';
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
<title>AHMUS Shop — Premium Products</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}
</script>
<style>
/* ════════ BASE ════════ */
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;overflow-x:hidden;color:#fff;min-height:100vh}
.glass-bg{
  position:fixed;inset:0;z-index:-1;
  background:linear-gradient(135deg,#0f0c29 0%,#1a1a3e 25%,#24243e 50%,#0f0c29 100%);
}
/* Animated floating blobs */
.blob{position:fixed;border-radius:50%;filter:blur(80px);opacity:.45;pointer-events:none;z-index:-1}
.blob-1{width:500px;height:500px;background:radial-gradient(circle,#f59e0b,transparent 70%);top:-10%;left:-5%;animation:blobFloat1 18s ease-in-out infinite}
.blob-2{width:450px;height:450px;background:radial-gradient(circle,#8b5cf6,transparent 70%);top:30%;right:-10%;animation:blobFloat2 22s ease-in-out infinite}
.blob-3{width:400px;height:400px;background:radial-gradient(circle,#06b6d4,transparent 70%);bottom:-5%;left:20%;animation:blobFloat3 20s ease-in-out infinite}
.blob-4{width:350px;height:350px;background:radial-gradient(circle,#ec4899,transparent 70%);top:60%;left:-8%;animation:blobFloat4 24s ease-in-out infinite}
.blob-5{width:300px;height:300px;background:radial-gradient(circle,#22c55e,transparent 70%);top:10%;right:25%;animation:blobFloat5 16s ease-in-out infinite}
@keyframes blobFloat1{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(60px,40px) scale(1.1)}66%{transform:translate(-30px,70px) scale(.95)}}
@keyframes blobFloat2{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(-50px,-30px) scale(1.08)}66%{transform:translate(40px,50px) scale(.92)}}
@keyframes blobFloat3{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(40px,-60px) scale(1.05)}66%{transform:translate(-60px,20px) scale(.97)}}
@keyframes blobFloat4{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(70px,30px) scale(.93)}66%{transform:translate(-40px,-40px) scale(1.1)}}
@keyframes blobFloat5{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-30px,50px) scale(1.12)}}

/* ════════ GLASS CLASSES ════════ */
.glass{
  background:rgba(255,255,255,.06);
  backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid rgba(255,255,255,.1);
  box-shadow:0 8px 32px rgba(0,0,0,.15),inset 0 1px 0 rgba(255,255,255,.08);
}
.glass-strong{
  background:rgba(255,255,255,.1);
  backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 8px 32px rgba(0,0,0,.2),inset 0 1px 0 rgba(255,255,255,.1);
}
.glass-light{
  background:rgba(255,255,255,.04);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border:1px solid rgba(255,255,255,.07);
  box-shadow:0 4px 16px rgba(0,0,0,.1);
}
.glass-input{
  background:rgba(255,255,255,.06);
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.12);
  color:#fff;font-family:'Inter',sans-serif;
  transition:all .25s;
}
.glass-input::placeholder{color:rgba(255,255,255,.35)}
.glass-input:focus{
  outline:none;
  border-color:rgba(245,158,11,.5);
  box-shadow:0 0 0 3px rgba(245,158,11,.15),inset 0 1px 0 rgba(255,255,255,.05);
  background:rgba(255,255,255,.09);
}
.glass-input.err{border-color:rgba(239,68,68,.6);box-shadow:0 0 0 3px rgba(239,68,68,.12)}
.glass-btn{
  background:linear-gradient(135deg,rgba(245,158,11,.85),rgba(234,88,12,.85));
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.15);
  color:#fff;font-family:'Inter',sans-serif;
  box-shadow:0 4px 20px rgba(245,158,11,.25);
  transition:all .3s;
}
.glass-btn:hover{
  box-shadow:0 6px 28px rgba(245,158,11,.4);
  transform:translateY(-1px);
}
.glass-btn-dark{
  background:rgba(255,255,255,.08);
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.15);
  color:#fff;font-family:'Inter',sans-serif;
  transition:all .25s;
}
.glass-btn-dark:hover{
  background:rgba(255,255,255,.14);
  border-color:rgba(255,255,255,.25);
}

/* ════════ ANIMATIONS ════════ */
.rv{opacity:0;transform:translateY(30px);transition:all .8s cubic-bezier(.22,1,.36,1);will-change:opacity,transform}
.rv.on{opacity:1;transform:none}
.d1{transition-delay:.05s}.d2{transition-delay:.1s}.d3{transition-delay:.15s}.d4{transition-delay:.2s}.d5{transition-delay:.25s}.d6{transition-delay:.3s}

/* ════════ NAV ════════ */
.nav-glass{
  background:rgba(15,12,41,.5);
  backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
  border-bottom:1px solid rgba(255,255,255,.06);
  transition:all .3s;
}
.nav-glass.scrolled{
  background:rgba(15,12,41,.75);
  border-bottom-color:rgba(255,255,255,.1);
  box-shadow:0 4px 30px rgba(0,0,0,.3);
}

/* ════════ PRODUCT CARD ════════ */
.pcard{
  border-radius:20px;overflow:hidden;
  background:rgba(255,255,255,.05);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border:1px solid rgba(255,255,255,.08);
  box-shadow:0 8px 32px rgba(0,0,0,.15);
  transition:all .35s cubic-bezier(.22,1,.36,1);
}
.pcard:hover{
  transform:translateY(-6px);
  border-color:rgba(255,255,255,.18);
  box-shadow:0 20px 50px rgba(0,0,0,.3),0 0 0 1px rgba(255,255,255,.1);
  background:rgba(255,255,255,.08);
}
.pcard-img{position:relative;overflow:hidden;background:rgba(255,255,255,.03)}
.pcard-img img{width:100%;height:240px;object-fit:cover;transition:transform .5s ease}
.pcard:hover .pcard-img img{transform:scale(1.06)}
.pcard-badge{
  position:absolute;top:12px;left:12px;
  background:linear-gradient(135deg,rgba(245,158,11,.9),rgba(234,88,12,.9));
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  color:#fff;font-size:.65rem;font-weight:700;padding:4px 12px;border-radius:10px;
  border:1px solid rgba(255,255,255,.2);
}
.btn-cart{
  display:flex;align-items:center;justify-content:center;gap:7px;width:100%;padding:10px;
  border-radius:14px;font-size:.8rem;font-weight:600;cursor:pointer;
  background:rgba(255,255,255,.08);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.12);color:#fff;
  transition:all .25s;font-family:'Inter',sans-serif;
}
.btn-cart:hover{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.25)}
.btn-cart.added{background:rgba(34,197,94,.2);border-color:rgba(34,197,94,.4);color:#4ade80;pointer-events:none}
.btn-cart .spin{animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.btn-wish{
  width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,.06);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.4);
  cursor:pointer;transition:all .25s;font-size:.9rem;flex-shrink:0;
}
.btn-wish:hover{border-color:rgba(236,72,153,.5);color:#f472b6;background:rgba(236,72,153,.1)}

/* ════════ CATEGORY PILL ════════ */
.cat-pill{
  display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border-radius:99px;
  background:rgba(255,255,255,.05);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.08);font-size:.8rem;font-weight:500;
  color:rgba(255,255,255,.55);cursor:pointer;transition:all .25s;white-space:nowrap;text-decoration:none;
}
.cat-pill:hover{background:rgba(255,255,255,.1);color:rgba(255,255,255,.85);border-color:rgba(255,255,255,.15)}
.cat-pill.active{
  background:linear-gradient(135deg,rgba(245,158,11,.25),rgba(234,88,12,.2));
  border-color:rgba(245,158,11,.4);color:#fbbf24;
  box-shadow:0 4px 20px rgba(245,158,11,.15);
}
.cat-pill.active i{color:#fbbf24}

/* ════════ SKELETON ════════ */
.skel{
  background:linear-gradient(90deg,rgba(255,255,255,.04) 25%,rgba(255,255,255,.08) 50%,rgba(255,255,255,.04) 75%);
  background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:8px;
}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* ════════ FAQ ════════ */
.faq-item{
  border-radius:16px;overflow:hidden;
  background:rgba(255,255,255,.04);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,.07);transition:all .3s;
}
.faq-item.open{border-color:rgba(245,158,11,.3);background:rgba(255,255,255,.07)}
.faq-btn{
  display:flex;align-items:center;justify-content:space-between;width:100%;padding:18px 20px;
  font-size:.88rem;font-weight:600;color:rgba(255,255,255,.85);background:none;
  border:none;cursor:pointer;text-align:left;font-family:'Inter',sans-serif;gap:12px;
}
.faq-btn:hover{color:#fff}
.faq-btn i{font-size:.6rem;color:rgba(255,255,255,.3);transition:transform .3s;flex-shrink:0}
.faq-item.open .faq-btn i{transform:rotate(180deg);color:#fbbf24}
.faq-body{max-height:0;overflow:hidden;transition:max-height .35s ease}
.faq-inner{padding:0 20px 18px;font-size:.84rem;color:rgba(255,255,255,.5);line-height:1.7}

/* ════════ REVIEW MODAL ════════ */
.rev-overlay{
  position:fixed;inset:0;z-index:9000;
  background:rgba(0,0,0,.5);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  display:flex;align-items:center;justify-content:center;padding:16px;
  opacity:0;visibility:hidden;transition:all .3s;
}
.rev-overlay.open{opacity:1;visibility:visible}
.rev-modal{
  width:100%;max-width:560px;max-height:85vh;border-radius:24px;overflow:hidden;
  background:rgba(30,27,60,.85);backdrop-filter:blur(30px);-webkit-backdrop-filter:blur(30px);
  border:1px solid rgba(255,255,255,.1);
  box-shadow:0 25px 80px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.08);
  transform:translateY(24px) scale(.97);
  transition:all .35s cubic-bezier(.22,1,.36,1);
  display:flex;flex-direction:column;
}
.rev-overlay.open .rev-modal{transform:translateY(0) scale(1)}
.rev-head{
  padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.06);
  display:flex;align-items:center;gap:14px;flex-shrink:0;
}
.rev-head img{width:52px;height:52px;border-radius:14px;object-fit:cover;border:1px solid rgba(255,255,255,.1)}
.rev-close{
  margin-left:auto;width:36px;height:36px;border-radius:12px;
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
  color:rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:all .2s;font-size:.8rem;flex-shrink:0;
}
.rev-close:hover{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:#f87171}
.rev-body{flex:1;overflow-y:auto}
.rev-body::-webkit-scrollbar{width:4px}
.rev-body::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:99px}
.star-sel{display:flex;gap:5px;margin-bottom:10px}
.star-sel .sb{background:none;border:none;cursor:pointer;font-size:1.5rem;color:rgba(255,255,255,.12);transition:all .15s;line-height:1;padding:0}
.star-sel .sb:hover{transform:scale(1.15)}
.star-sel .sb.hov{color:rgba(245,158,11,.6)}
.star-sel .sb.sel{color:#fbbf24}
.star-lbl{font-size:.72rem;font-weight:600;color:rgba(255,255,255,.25);margin-left:8px;transition:color .2s}
.star-lbl.on{color:#fbbf24}
.rev-ta{
  width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
  border-radius:14px;padding:12px 14px;font-size:.84rem;color:#fff;
  font-family:'Inter',sans-serif;resize:vertical;min-height:80px;max-height:140px;
  line-height:1.6;outline:none;transition:border-color .2s;
}
.rev-ta::placeholder{color:rgba(255,255,255,.25)}
.rev-ta:focus{border-color:rgba(245,158,11,.4);box-shadow:0 0 0 3px rgba(245,158,11,.1)}
.btn-sub-rev{
  display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:10px 22px;
  border-radius:12px;font-size:.82rem;font-weight:600;
  background:linear-gradient(135deg,rgba(245,158,11,.8),rgba(234,88,12,.8));
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.15);color:#fff;cursor:pointer;
  transition:all .2s;font-family:'Inter',sans-serif;
  box-shadow:0 4px 16px rgba(245,158,11,.2);
}
.btn-sub-rev:hover{box-shadow:0 6px 24px rgba(245,158,11,.35);transform:translateY(-1px)}
.btn-sub-rev:disabled{opacity:.35;cursor:not-allowed;transform:none;box-shadow:none}
.rev-item{padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.04)}
.rev-item:last-child{border-bottom:none}
.rev-av{
  width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:.7rem;background:rgba(245,158,11,.15);color:#fbbf24;
  flex-shrink:0;overflow:hidden;border:1px solid rgba(245,158,11,.2);
}
.rev-av img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.rev-empty{padding:40px 20px;text-align:center}
.rev-empty i{font-size:2rem;color:rgba(255,255,255,.1);display:block;margin-bottom:10px}
.rev-empty p{font-size:.88rem;color:rgba(255,255,255,.3)}
.rev-loading{padding:40px 20px;text-align:center}
.spinner{width:28px;height:28px;border:3px solid rgba(255,255,255,.08);border-top-color:#fbbf24;border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 10px}

/* ════════ TOAST ════════ */
.toast-box{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column-reverse;gap:8px;pointer-events:none}
.toast{
  display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:14px;
  font-size:.84rem;font-weight:500;pointer-events:auto;
  transform:translateX(120%);opacity:0;transition:all .35s cubic-bezier(.22,1,.36,1);
  max-width:360px;backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  box-shadow:0 8px 32px rgba(0,0,0,.3);
}
.toast.show{transform:translateX(0);opacity:1}
.toast.exit{transform:translateX(120%);opacity:0}
.toast-success{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.25)}
.toast-error{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.25)}
.toast-info{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.25)}

/* ════════ USER DROPDOWN ════════ */
.ud{
  position:absolute;top:calc(100% + 10px);right:0;width:260px;
  background:rgba(30,27,60,.9);backdrop-filter:blur(30px);-webkit-backdrop-filter:blur(30px);
  border:1px solid rgba(255,255,255,.1);border-radius:18px;
  box-shadow:0 16px 50px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.06);
  opacity:0;visibility:hidden;transform:translateY(-8px);
  transition:all .25s cubic-bezier(.22,1,.36,1);z-index:999;overflow:hidden;
}
.ud.open{opacity:1;visibility:visible;transform:translateY(0)}
.ud-item{
  display:flex;align-items:center;gap:10px;padding:11px 16px;font-size:.84rem;
  color:rgba(255,255,255,.6);text-decoration:none;transition:all .15s;
  cursor:pointer;border:none;background:none;width:100%;text-align:left;font-family:'Inter',sans-serif;
}
.ud-item:hover{background:rgba(255,255,255,.06);color:#fff}
.ud-item i{width:18px;text-align:center;color:rgba(255,255,255,.25);font-size:.8rem}
.ud-sep{height:1px;background:rgba(255,255,255,.06);margin:4px 0}
.ud-item.danger{color:#f87171}
.ud-item.danger:hover{background:rgba(239,68,68,.08)}

/* ════════ INFO BOX ════════ */
.info-box{
  border-radius:18px;padding:18px;transition:all .3s;
  background:rgba(255,255,255,.04);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,.07);
}
.info-box:hover{
  background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.14);
  box-shadow:0 8px 30px rgba(0,0,0,.2);transform:translateY(-2px);
}

/* ════════ ONLINE WIDGET ════════ */
.online-dot{width:8px;height:8px;border-radius:50%;background:#4ade80;animation:pulse 2s ease infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.4)}50%{box-shadow:0 0 0 6px rgba(74,222,128,0)}}

/* ════════ MOBILE NAV ════════ */
.mob-nav{
  display:none;position:fixed;bottom:0;left:0;right:0;z-index:50;
  background:rgba(15,12,41,.7);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
  border-top:1px solid rgba(255,255,255,.06);
  padding:6px 0;padding-bottom:max(6px,env(safe-area-inset-bottom));
}
@media(max-width:1023px){.desk-nav{display:none!important}.mob-nav{display:flex;justify-content:space-around}.desk-stats{display:none!important}}
.mob-tab{
  display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 14px;
  font-size:.6rem;color:rgba(255,255,255,.35);text-decoration:none;font-weight:500;
  transition:color .2s;border:none;background:none;cursor:pointer;position:relative;
}
.mob-tab:hover,.mob-tab.active{color:#fbbf24}
.mob-badge{
  position:absolute;top:1px;right:6px;min-width:16px;height:16px;border-radius:99px;
  background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;font-size:.55rem;
  font-bold;display:flex;align-items:center;justify-content:center;
  border:1px solid rgba(255,255,255,.15);
}

/* ════════ BADGE POP ════════ */
@keyframes badgePop{0%{transform:scale(1)}50%{transform:scale(1.4)}100%{transform:scale(1)}}
.badge-pop{animation:badgePop .3s ease}

/* ════════ GLOW TEXT ════════ */
.glow-text{
  background:linear-gradient(135deg,#fbbf24,#f97316,#ef4444,#f472b6,#a78bfa,#38bdf8,#fbbf24);
  background-size:300% 300%;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;
  animation:gradientShift 6s ease infinite;
}
@keyframes gradientShift{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

/* ════════ SECTION LABEL ════════ */
.sec-label{
  display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;
  background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);
  font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#fbbf24;
}

/* ════════ GLASS DIVIDER ════════ */
.glass-divider{
  height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.1),transparent);
}
</style>
</head>
<body>

<!-- ═══ BG LAYERS ═══ -->
<div class="glass-bg"></div>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>
<div class="blob blob-4"></div>
<div class="blob blob-5"></div>

<!-- ═══ REVIEW MODAL ═══ -->
<div class="rev-overlay" id="reviewOverlay">
  <div class="rev-modal">
    <div class="rev-head">
      <img id="reviewProdImg" src="" alt="" onerror="this.src='https://picsum.photos/seed/rev/100/100.jpg'">
      <div class="min-w-0 flex-1">
        <h3 id="reviewProdName" class="text-sm font-bold truncate" style="color:#fff">Product</h3>
        <p id="reviewProdPrice" class="text-lg font-extrabold" style="color:#fbbf24">Rs. 0</p>
        <div class="flex items-center gap-2 mt-1">
          <div id="reviewProdStars" class="flex items-center gap-0.5"></div>
          <span id="reviewProdStats" class="text-[11px] font-medium" style="color:rgba(255,255,255,.3)"></span>
        </div>
      </div>
      <button class="rev-close" onclick="closeReviewModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="rev-body" id="reviewBody">
      <div class="rev-loading"><div class="spinner"></div><p style="font-size:.82rem;color:rgba(255,255,255,.3)">Loading...</p></div>
    </div>
  </div>
</div>

<!-- ═══ TOAST ═══ -->
<div class="toast-box" id="toastContainer"></div>

<!-- ═══ DESKTOP NAV ═══ -->
<nav class="desk-nav nav-glass fixed top-0 left-0 right-0 z-50" id="mainNav">
  <div class="max-w-[1500px] mx-auto px-5 lg:px-10 flex items-center h-16 gap-4">
    <a href="index.php" class="flex items-center gap-2.5 flex-shrink-0 no-underline">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,rgba(245,158,11,.8),rgba(234,88,12,.8));border:1px solid rgba(255,255,255,.15)">
        <i class="fa-solid fa-headphones text-white text-sm"></i>
      </div>
      <span class="text-xl font-extrabold tracking-tight text-white">ahmus<span class="text-amber-400">Shop</span></span>
    </a>
    <div class="flex-1 max-w-2xl relative">
      <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs" style="color:rgba(255,255,255,.3)"></i>
      <input type="text" placeholder="Search AHMUS Shop..." id="navSearchInput" class="glass-input w-full h-11 pl-11 pr-12 rounded-xl text-sm">
      <button onclick="doSearch()" class="absolute right-1.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg glass-btn border-none text-sm cursor-pointer flex items-center justify-center"><i class="fa-solid fa-magnifying-glass text-xs"></i></button>
    </div>
    <div class="flex items-center gap-2.5 flex-shrink-0">
      <a href="cart.php" class="relative w-11 h-11 rounded-xl flex items-center justify-center glass-light no-underline transition-all hover:bg-white/10" style="color:rgba(255,255,255,.6);cursor:pointer">
        <i class="fa-solid fa-bag-shopping text-sm"></i>
        <?php if ($count > 0): ?>
        <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:linear-gradient(135deg,#f59e0b,#ea580c);border:1px solid rgba(255,255,255,.2)" id="navCartBadge"><?= $count ?></span>
        <?php endif; ?>
      </a>
      <?php if ($user): ?>
      <div class="relative" id="profileWrap">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center cursor-pointer overflow-hidden transition-all hover:border-amber-400/50" style="background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.1)" onclick="toggleProfile()">
          <?php if (!empty($user['image']) && file_exists("../backend/uploads/" . $user['image'])): ?>
          <img src="../backend/uploads/<?= $user['image'] ?>" alt="" class="w-full h-full object-cover">
          <?php else: ?>
          <span class="text-sm font-bold" style="color:rgba(255,255,255,.6)"><?= strtoupper(mb_substr($user['name'], 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <div class="ud" id="userDropdown">
          <div class="px-4 py-3" style="background:rgba(255,255,255,.03);border-bottom:1px solid rgba(255,255,255,.06)">
            <div class="text-sm font-bold text-white"><?= htmlspecialchars($user['name']) ?></div>
            <div class="text-xs mt-0.5" style="color:rgba(255,255,255,.3)"><?= htmlspecialchars($user['email']) ?></div>
          </div>
          <div class="py-1">
            <a href="My-profile.php" class="ud-item"><i class="fa-solid fa-user"></i>My Profile</a>
            <a href="My-Orders.php" class="ud-item"><i class="fa-solid fa-box"></i>My Orders</a>
            <a href="cart.php" class="ud-item"><i class="fa-solid fa-bag-shopping"></i>My Cart</a>
            <a href="#" class="ud-item"><i class="fa-solid fa-heart"></i>Wishlist</a>
          </div>
          <div class="ud-sep"></div>
          <div class="py-1">
            <a href="../user/logout.php" class="ud-item danger"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
          </div>
        </div>
      </div>
      <?php else: ?>
      <a href="../user/login.php" class="glass-btn inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold no-underline"><i class="fa-solid fa-right-to-bracket text-xs"></i>Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ═══ HERO ═══ -->
<section class="pt-28 pb-8 lg:pt-36 lg:pb-12 relative">
  <div class="max-w-[1500px] mx-auto px-5 lg:px-10">
    <div class="grid lg:grid-cols-2 gap-10 items-center relative z-10">
      <div class="rv on">
        <div class="sec-label mb-6">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
          PREMIUM QUALITY PRODUCTS
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-[1.05] tracking-tight mb-6 text-white">
          Shop Smart.<br>Live <span class="glow-text">Better.</span>
        </h1>
        <p class="text-base lg:text-lg leading-relaxed mb-8 max-w-lg" style="color:rgba(255,255,255,.45)">
          Discover premium quality computers & accessories at unbeatable prices. Trusted by 50K+ customers across Pakistan.
        </p>
        <div class="flex flex-wrap gap-3 mb-10">
          <a href="#products" class="glass-btn inline-flex items-center gap-2 px-8 py-4 rounded-2xl text-sm font-bold no-underline">
            <i class="fa-solid fa-bolt text-xs"></i>Shop Now
          </a>
          <a href="#about" class="glass-btn-dark inline-flex items-center gap-2 px-8 py-4 rounded-2xl text-sm font-semibold no-underline">
            Learn More
          </a>
        </div>
        <div class="flex flex-wrap gap-8 desk-stats">
          <div>
            <p class="text-2xl font-extrabold text-white">50K+</p>
            <p class="text-xs mt-0.5" style="color:rgba(255,255,255,.3)">Happy Customers</p>
          </div>
          <div class="w-px self-stretch" style="background:rgba(255,255,255,.08)"></div>
          <div>
            <p class="text-2xl font-extrabold text-white">4.9★</p>
            <p class="text-xs mt-0.5" style="color:rgba(255,255,255,.3)">Avg Rating</p>
          </div>
          <div class="w-px self-stretch" style="background:rgba(255,255,255,.08)"></div>
          <div>
            <p class="text-2xl font-extrabold text-white">200+</p>
            <p class="text-xs mt-0.5" style="color:rgba(255,255,255,.3)">Products</p>
          </div>
        </div>
      </div>
      <div class="rv d2 on flex justify-center lg:justify-end">
        <div class="relative">
          <div class="absolute inset-0 rounded-3xl" style="background:linear-gradient(135deg,rgba(245,158,11,.2),rgba(139,92,246,.2));filter:blur(40px);transform:scale(.85)"></div>
          <img src="../backend/uploads/image5.png" alt="Featured Product" class="relative w-full max-w-md lg:max-w-lg rounded-3xl" style="border:1px solid rgba(255,255,255,.1)" onerror="this.src='https://picsum.photos/seed/hero-amz/600/500.jpg'">
        </div>
      </div>
    </div>
  </div>
</section>

<div class="glass-divider max-w-[1500px] mx-auto"></div>

<!-- ═══ CATEGORIES ═══ -->
<section class="py-8">
  <div class="max-w-[1500px] mx-auto px-5 lg:px-10">
    <div class="flex items-center justify-between mb-4 rv">
      <h2 class="text-xs font-bold tracking-widest uppercase" style="color:rgba(255,255,255,.35)">
        <i class="fa-solid fa-layer-group text-amber-400 mr-2"></i>Categories
      </h2>
      <button id="clearFilter" class="<?= $active_cat > 0 ? '' : 'hidden' ?> glass-btn-dark text-xs font-semibold rounded-lg px-3 py-1.5 cursor-pointer" onclick="loadCategory(0)">
        <i class="fa-solid fa-xmark text-[10px] mr-1"></i>Clear
      </button>
    </div>
    <div class="overflow-x-auto pb-2 rv" style="-webkit-overflow-scrolling:touch">
      <div class="flex gap-2.5 min-w-max" id="catBar">
        <a href="javascript:void(0)" class="cat-pill <?= $active_cat === 0 ? 'active' : '' ?>" data-cat="0" onclick="loadCategory(0)">
          <i class="fa-solid fa-border-all text-xs"></i>All
        </a>
        <?php
        mysqli_data_seek($cat_result, 0);
        $catIcons = array('fa-laptop','fa-mobile-screen','fa-headphones','fa-gamepad','fa-camera','fa-shirt','fa-gem','fa-home','fa-futbol','fa-book','fa-plug','fa-car','fa-baby','fa-utensils','fa-box-open');
        $catIdx = 0;
        while ($cat = mysqli_fetch_assoc($cat_result)) {
            $icon = isset($catIcons[$catIdx]) ? $catIcons[$catIdx] : 'fa-tag';
            $catIdx++;
        ?>
        <a href="javascript:void(0)" class="cat-pill <?= $active_cat == $cat['id'] ? 'active' : '' ?>" data-cat="<?= $cat['id'] ?>" onclick="loadCategory(<?= $cat['id'] ?>)">
          <i class="fa-solid <?= $icon ?> text-xs"></i><?= htmlspecialchars($cat['category_name']) ?>
        </a>
        <?php } ?>
      </div>
    </div>
  </div>
</section>

<!-- ═══ PRODUCTS ═══ -->
<section id="products" class="pb-16">
  <div class="max-w-[1500px] mx-auto px-5 lg:px-10">
    <div class="flex items-end justify-between mb-6 rv">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-white"><?= htmlspecialchars($active_cat_name) ?></h2>
        <p class="text-sm mt-1" style="color:rgba(255,255,255,.3)"><?= $product_count ?> result<?= $product_count !== 1 ? 's' : '' ?></p>
      </div>
    </div>
    <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <?php
      $di = 0;
      while ($product = mysqli_fetch_assoc($product_result)) {
          $dc = 'd' . (1 + ($di % 4));
          $di++;
          $imgP = "../backend/uploads/" . $product['image'];
          $avg = round($product['avg_rating'], 1);
          $original_price = !empty($product['original_price']) ? $product['original_price'] : 0;
          $discount = 0;
          if ($original_price > 0 && $product['price'] > 0) {
              $discount = round((1 - $product['price'] / $original_price) * 100);
          }
      ?>
      <div class="pcard rv <?= $dc ?>">
        <div class="pcard-img">
          <a href="product_detail.php?id=<?= $product['id'] ?>" class="block no-underline">
            <img src="<?= $imgP ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" onerror="this.src='https://picsum.photos/seed/p<?= $product['id'] ?>/400/300.jpg'">
          </a>
          <?php if ($discount > 0): ?>
          <span class="pcard-badge">-<?= $discount ?>%</span>
          <?php endif; ?>
        </div>
        <div class="p-4">
          <a href="product_detail.php?id=<?= $product['id'] ?>" class="block text-sm font-semibold leading-snug no-underline mb-2 text-white" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($product['product_name']) ?></a>
          <div class="flex items-center gap-1.5 mb-3" style="color:rgba(255,255,255,.5)">
            <?php
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= floor($avg)) echo '<i class="fa-solid fa-star text-amber-400" style="font-size:.65rem"></i>';
                elseif ($i - 0.5 <= $avg) echo '<i class="fa-solid fa-star-half-stroke text-amber-400" style="font-size:.65rem"></i>';
                else echo '<i class="fa-regular fa-star" style="font-size:.65rem;color:rgba(255,255,255,.12)"></i>';
            }
            ?>
            <span class="text-xs font-medium"><?= $avg ?><?php if ($product['total_reviews'] > 0): ?> (<?= $product['total_reviews'] ?>)<?php endif; ?></span>
            <span class="review-trigger ml-auto text-xs underline cursor-pointer transition-colors hover:text-amber-400" style="color:rgba(255,255,255,.3)" data-review-trigger="<?= $product['id'] ?>">Reviews</span>
          </div>
          <div class="flex items-baseline gap-2 mb-4">
            <?php if ($original_price > 0): ?>
            <span class="text-xs line-through" style="color:rgba(255,255,255,.25)">Rs. <?= number_format($original_price, 0) ?></span>
            <?php endif; ?>
            <span class="text-lg font-extrabold text-white">Rs. <?= number_format($product['price'], 0) ?></span>
          </div>
          <div class="flex gap-2">
            <button class="btn-wish" title="Wishlist"><i class="fa-regular fa-heart"></i></button>
            <button class="btn-cart" data-add-cart="<?= $product['id'] ?>"><i class="fa-solid fa-cart-plus text-xs"></i>Add to Cart</button>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>
</section>

<div class="glass-divider max-w-[1500px] mx-auto"></div>

<!-- ═══ ABOUT ═══ -->
<section id="about" class="py-20">
  <div class="max-w-[1500px] mx-auto px-5 lg:px-10">
    <div class="text-center mb-12 rv">
      <div class="sec-label mb-4">
        <i class="fa-solid fa-heart text-amber-400" style="font-size:.6rem"></i>Our Story
      </div>
      <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-white">Why Choose <span class="glow-text">AHMUS</span>?</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <div class="rv d1 glass rounded-2xl text-center p-7 transition-all hover:bg-white/[.08]">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl flex items-center justify-center text-xl" style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.2);color:#fbbf24"><i class="fa-solid fa-headphones-simple"></i></div>
        <h4 class="text-sm font-bold mb-1.5 text-white">Premium Quality</h4>
        <p class="text-xs leading-relaxed" style="color:rgba(255,255,255,.4)">Every product carefully selected to meet our quality standards.</p>
      </div>
      <div class="rv d2 glass rounded-2xl text-center p-7 transition-all hover:bg-white/[.08]">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl flex items-center justify-center text-xl" style="background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.2);color:#60a5fa"><i class="fa-solid fa-tags"></i></div>
        <h4 class="text-sm font-bold mb-1.5 text-white">Fair Pricing</h4>
        <p class="text-xs leading-relaxed" style="color:rgba(255,255,255,.4)">Direct sourcing keeps prices fair and honest.</p>
      </div>
      <div class="rv d3 glass rounded-2xl text-center p-7 transition-all hover:bg-white/[.08]">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl flex items-center justify-center text-xl" style="background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.2);color:#4ade80"><i class="fa-solid fa-truck-fast"></i></div>
        <h4 class="text-sm font-bold mb-1.5 text-white">Free Delivery</h4>
        <p class="text-xs leading-relaxed" style="color:rgba(255,255,255,.4)">Free shipping on all orders across Pakistan.</p>
      </div>
      <div class="rv d4 glass rounded-2xl text-center p-7 transition-all hover:bg-white/[.08]">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl flex items-center justify-center text-xl" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);color:#f87171"><i class="fa-solid fa-rotate-left"></i></div>
        <h4 class="text-sm font-bold mb-1.5 text-white">Easy Returns</h4>
        <p class="text-xs leading-relaxed" style="color:rgba(255,255,255,.4)">7-day hassle-free return with full refund.</p>
      </div>
    </div>
  </div>
</section>

<div class="glass-divider max-w-[1500px] mx-auto"></div>

<!-- ═══ CONTACT ═══ -->
<section id="contact" class="py-20">
  <div class="max-w-[1500px] mx-auto px-5 lg:px-10">
    <div class="text-center mb-12 rv">
      <div class="sec-label mb-4">
        <i class="fa-solid fa-message text-amber-400" style="font-size:.6rem"></i>Get in Touch
      </div>
      <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-white">Contact <span class="glow-text">Us</span></h2>
    </div>
    <div class="grid lg:grid-cols-5 gap-6">
      <div class="lg:col-span-3 rv">
        <div class="glass rounded-3xl p-8">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.2)"><i class="fa-solid fa-paper-plane text-amber-400"></i></div>
            <div>
              <h3 class="text-base font-bold text-white">Send a Message</h3>
              <p class="text-xs" style="color:rgba(255,255,255,.3)">We respond within 24 hours</p>
            </div>
          </div>
          <?php if ($formMsg): ?>
          <div class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium mb-5 <?= $formType === 'success' ? '' : '' ?>" style="background:<?= $formType === 'success' ? 'rgba(34,197,94,.12)' : 'rgba(239,68,68,.12)' ?>;border:1px solid <?= $formType === 'success' ? 'rgba(34,197,94,.25)' : 'rgba(239,68,68,.25)' ?>;color:<?= $formType === 'success' ? '#4ade80' : '#f87171' ?>">
            <i class="fa-solid <?= $formType === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
            <?= htmlspecialchars($formMsg) ?>
          </div>
          <?php endif; ?>
          <form method="POST" action="#contact" id="contactForm" novalidate>
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:rgba(255,255,255,.5)">Full Name</label>
                <input type="text" name="contact_name" class="glass-input w-full px-4 py-3 rounded-xl text-sm" placeholder="Your name" required value="<?= htmlspecialchars($c_name ?? '') ?>">
              </div>
              <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:rgba(255,255,255,.5)">Email</label>
                <input type="email" name="contact_email" class="glass-input w-full px-4 py-3 rounded-xl text-sm" placeholder="you@example.com" required value="<?= htmlspecialchars($c_email ?? '') ?>">
              </div>
            </div>
            <div class="mb-4">
              <label class="block text-xs font-semibold mb-1.5" style="color:rgba(255,255,255,.5)">Subject</label>
              <input type="text" name="contact_subject" class="glass-input w-full px-4 py-3 rounded-xl text-sm" placeholder="How can we help?" required value="<?= htmlspecialchars($c_subject ?? '') ?>">
            </div>
            <div class="mb-5">
              <label class="block text-xs font-semibold mb-1.5" style="color:rgba(255,255,255,.5)">Message</label>
              <textarea name="contact_message" class="glass-input w-full px-4 py-3 rounded-xl text-sm" placeholder="Tell us how we can help..." required><?= htmlspecialchars($c_message ?? '') ?></textarea>
            </div>
            <button type="submit" name="submit_contact" class="glass-btn w-full flex items-center justify-center gap-2 py-3.5 rounded-xl text-sm font-bold no-underline cursor-pointer border-none">
              <i class="fa-solid fa-paper-plane text-xs"></i>Send Message
            </button>
          </form>
        </div>
      </div>
      <div class="lg:col-span-2 flex flex-col gap-3 rv d2">
        <div class="info-box flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,158,11,.1);color:#fbbf24"><i class="fa-solid fa-envelope"></i></div>
          <div><h4 class="text-sm font-bold text-white">Email</h4><a href="mailto:ahmedadilbaloch95@gmail.com" class="text-xs font-medium no-underline hover:underline" style="color:#60a5fa">ahmedadilbaloch95@gmail.com</a></div>
        </div>
        <div class="info-box flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(34,197,94,.1);color:#4ade80"><i class="fa-solid fa-phone"></i></div>
          <div><h4 class="text-sm font-bold text-white">Call Us</h4><p class="text-xs mb-0.5" style="color:rgba(255,255,255,.3)">Mon-Sat, 10-8 PKT</p><a href="tel:+923233703689" class="text-xs font-semibold no-underline hover:underline" style="color:#4ade80">+92 323 3703689</a></div>
        </div>
        <div class="info-box flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(34,197,94,.1);color:#22c55e"><i class="fa-brands fa-whatsapp text-lg"></i></div>
          <div><h4 class="text-sm font-bold text-white">WhatsApp</h4><a href="https://wa.me/923233703689" target="_blank" class="text-xs font-semibold no-underline hover:underline" style="color:#22c55e">Chat Now →</a></div>
        </div>
        <div class="info-box flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(59,130,246,.1);color:#60a5fa"><i class="fa-solid fa-location-dot"></i></div>
          <div><h4 class="text-sm font-bold text-white">Visit Us</h4><p class="text-xs" style="color:rgba(255,255,255,.4)">Karachi, Sindh, Pakistan</p></div>
        </div>
        <div class="info-box flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(192,38,211,.1);color:#e879f9"><i class="fa-brands fa-instagram"></i></div>
          <div><h4 class="text-sm font-bold text-white">Instagram</h4><a href="#" class="text-xs font-semibold no-underline hover:underline" style="color:#e879f9">@ahmusshop_pk</a></div>
        </div>
        <div class="info-box flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(59,130,246,.1);color:#60a5fa"><i class="fa-brands fa-facebook-f"></i></div>
          <div><h4 class="text-sm font-bold text-white">Facebook</h4><a href="#" class="text-xs font-semibold no-underline hover:underline" style="color:#60a5fa">AHMUSShop Pakistan</a></div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="glass-divider max-w-[1500px] mx-auto"></div>

<!-- ═══ FAQ ═══ -->
<section class="py-20">
  <div class="max-w-2xl mx-auto px-5 lg:px-10">
    <div class="text-center mb-10 rv">
      <h2 class="text-2xl font-extrabold tracking-tight text-white">Frequently Asked <span class="glow-text">Questions</span></h2>
    </div>
    <div class="flex flex-col gap-3 rv">
      <?php
      $faqs = array(
          array('How long does delivery take?','2-5 business days for major cities, 5-7 for remote areas across Pakistan.'),
          array('Is delivery free?','Yes! Free delivery on all orders — no minimum required.'),
          array('What is your return policy?','7-day return in original condition with full refund. No questions asked.'),
          array('Do products have warranty?','Minimum 1-year official warranty on all electronics and accessories.'),
          array('What payment methods do you accept?','Cash on Delivery, JazzCash, EasyPaisa, bank transfer, and debit/credit cards.'),
          array('Are products original and genuine?','100% genuine products sourced from authorized distributors with proper invoices.')
      );
      foreach ($faqs as $i => $faq):
      ?>
      <div class="faq-item rv d<?= ($i % 4) + 1 ?>">
        <button class="faq-btn" onclick="toggleFaq(this)">
          <span><?= $faq[0] ?></span>
          <i class="fa-solid fa-chevron-down"></i>
        </button>
        <div class="faq-body"><div class="faq-inner"><?= $faq[1] ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer class="py-12" style="background:rgba(0,0,0,.2);border-top:1px solid rgba(255,255,255,.04)">
  <div class="max-w-[1500px] mx-auto px-5 lg:px-10">
    <div class="glass-divider mb-10"></div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 pb-10" style="border-bottom:1px solid rgba(255,255,255,.05)">
      <div>
        <a href="index.php" class="flex items-center gap-2.5 no-underline mb-4">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,rgba(245,158,11,.8),rgba(234,88,12,.8));border:1px solid rgba(255,255,255,.15)">
            <i class="fa-solid fa-headphones text-white text-sm"></i>
          </div>
          <span class="text-lg font-extrabold text-white">ahmus<span class="text-amber-400">Shop</span></span>
        </a>
        <p class="text-xs leading-relaxed mb-4" style="color:rgba(255,255,255,.3)">Premium quality products for those who demand the best.</p>
        <div class="flex gap-2.5">
          <a href="#" class="w-9 h-9 rounded-xl flex items-center justify-center no-underline transition-all hover:bg-white/10" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)"><i class="fa-brands fa-facebook-f text-xs" style="color:rgba(255,255,255,.3)"></i></a>
          <a href="#" class="w-9 h-9 rounded-xl flex items-center justify-center no-underline transition-all hover:bg-white/10" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)"><i class="fa-brands fa-instagram text-xs" style="color:rgba(255,255,255,.3)"></i></a>
          <a href="#" class="w-9 h-9 rounded-xl flex items-center justify-center no-underline transition-all hover:bg-white/10" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)"><i class="fa-brands fa-youtube text-xs" style="color:rgba(255,255,255,.3)"></i></a>
        </div>
      </div>
      <div>
        <h4 class="text-xs font-bold uppercase tracking-wider mb-4 text-white">Company</h4>
        <a href="#" class="block text-xs py-1 no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.3)">About AHMUS</a>
        <a href="#" class="block text-xs py-1 no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.3)">Careers</a>
        <a href="#" class="block text-xs py-1 no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.3)">Press Releases</a>
      </div>
      <div>
        <h4 class="text-xs font-bold uppercase tracking-wider mb-4 text-white">Support</h4>
        <a href="#" class="block text-xs py-1 no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.3)">Your Account</a>
        <a href="#" class="block text-xs py-1 no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.3)">Your Orders</a>
        <a href="#contact" class="block text-xs py-1 no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.3)">Returns & Refunds</a>
        <a href="#" class="block text-xs py-1 no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.3)">Help Center</a>
      </div>
      <div>
        <h4 class="text-xs font-bold uppercase tracking-wider mb-4 text-white">Contact</h4>
        <p class="text-xs py-1 flex items-center gap-2" style="color:rgba(255,255,255,.3)"><i class="fa-solid fa-envelope text-amber-400/60"></i>ahmedadilbaloch95@gmail.com</p>
        <p class="text-xs py-1 flex items-center gap-2" style="color:rgba(255,255,255,.3)"><i class="fa-solid fa-phone text-amber-400/60"></i>+92 323 3703689</p>
        <p class="text-xs py-1 flex items-center gap-2" style="color:rgba(255,255,255,.3)"><i class="fa-solid fa-location-dot text-amber-400/60"></i>Karachi, Pakistan</p>
      </div>
    </div>
    <div class="flex flex-wrap justify-between items-center gap-3 pt-6">
      <p class="text-[11px]" style="color:rgba(255,255,255,.2)">&copy; <?= date('Y') ?> AHMUS-Shop. All rights reserved.</p>
      <div class="flex gap-4">
        <a href="#" class="text-[11px] no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.2)">Privacy</a>
        <a href="#" class="text-[11px] no-underline transition-colors hover:text-white" style="color:rgba(255,255,255,.2)">Terms</a>
      </div>
    </div>
  </div>
</footer>

<!-- ═══ ONLINE WIDGET ═══ -->
<div class="fixed bottom-20 lg:bottom-6 right-5 z-40 glass rounded-xl px-3.5 py-2 flex items-center gap-2.5" id="onlineWidget">
  <div class="online-dot"></div>
  <span class="text-xs font-medium" style="color:rgba(255,255,255,.5)"><span id="onlineNum">0</span> visiting</span>
</div>

<!-- ═══ MOBILE NAV ═══ -->
<div class="mob-nav">
  <a href="index.php" class="mob-tab active"><i class="fa-solid fa-house text-sm"></i>Home</a>
  <a href="#products" class="mob-tab"><i class="fa-solid fa-grid-2 text-sm"></i>Shop</a>
  <a href="cart.php" class="mob-tab">
    <i class="fa-solid fa-bag-shopping text-sm"></i>Cart
    <?php if ($count > 0): ?><span class="mob-badge"><?= $count ?></span><?php endif; ?>
  </a>
  <?php if ($user): ?>
  <a href="My-profile.php" class="mob-tab"><i class="fa-solid fa-user text-sm"></i>Profile</a>
  <?php else: ?>
  <a href="../user/login.php" class="mob-tab"><i class="fa-solid fa-right-to-bracket text-sm"></i>Login</a>
  <?php endif; ?>
</div>

<!-- ═══ SCRIPTS ═══ -->
<script>
/* ── Reveal ── */
(function(){
  var o=new IntersectionObserver(function(e,obs){e.forEach(function(el){if(el.isIntersecting){el.target.classList.add('on');obs.unobserve(el.target)}})},{threshold:.08});
  document.querySelectorAll('.rv').forEach(function(el){o.observe(el)});
})();

/* ── Nav Scroll ── */
window.addEventListener('scroll',function(){
  var n=document.getElementById('mainNav');
  if(n)n.classList.toggle('scrolled',window.scrollY>20);
});

/* ── Profile ── */
var po=false;
function toggleProfile(){po=!po;document.getElementById('userDropdown').classList.toggle('open',po)}
document.addEventListener('click',function(e){var w=document.getElementById('profileWrap');if(w&&!w.contains(e.target)){po=false;document.getElementById('userDropdown').classList.remove('open')}});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){if(po){po=false;document.getElementById('userDropdown').classList.remove('open')}closeReviewModal()}});

/* ── FAQ ── */
function toggleFaq(btn){
  var item=btn.closest('.faq-item'),body=item.querySelector('.faq-body'),inner=item.querySelector('.faq-inner'),open=item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(function(x){x.classList.remove('open');x.querySelector('.faq-body').style.maxHeight='0'});
  if(!open){item.classList.add('open');body.style.maxHeight=inner.scrollHeight+16+'px'}
}

/* ── Contact Form ── */
var cf=document.getElementById('contactForm');
if(cf)cf.addEventListener('submit',function(e){
  var v=true;this.querySelectorAll('.glass-input').forEach(function(i){i.classList.remove('err');if(!i.value.trim()){i.classList.add('err');v=false}});
  var em=this.querySelector('input[type="email"]');if(em&&em.value.trim()&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim())){em.classList.add('err');v=false}
  var ta=this.querySelector('textarea');if(ta&&ta.value.trim().length<10){ta.classList.add('err');v=false}
  if(!v)e.preventDefault();
});

/* ── Smooth Scroll ── */
document.querySelectorAll('a[href^="#"]').forEach(function(l){l.addEventListener('click',function(e){var t=document.querySelector(this.getAttribute('href'));if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'})}})});

/* ── Search ── */
function doSearch(){var q=document.getElementById('navSearchInput').value.trim();if(q)window.location.href='search_results.php?q='+encodeURIComponent(q)}
document.getElementById('navSearchInput').addEventListener('keydown',function(e){if(e.key==='Enter')doSearch()});

/* ── Online Widget ── */
(function(){var n=Math.floor(Math.random()*30)+12,el=document.getElementById('onlineNum');if(el)el.textContent=n;setInterval(function(){n+=Math.floor(Math.random()*5)-2;n=Math.max(5,Math.min(60,n));if(el)el.textContent=n},4000)})();

/* ── Toast ── */
function showToast(msg,type){
  var c=document.getElementById('toastContainer');if(!c)return;
  var el=document.createElement('div');el.className='toast toast-'+(type||'info');
  var icons={success:'fa-circle-check',error:'fa-circle-xmark',info:'fa-circle-info'};
  el.innerHTML='<i class="fa-solid '+(icons[type]||icons.info)+'"></i><span>'+msg+'</span>';
  c.appendChild(el);
  requestAnimationFrame(function(){requestAnimationFrame(function(){el.classList.add('show')})});
  setTimeout(function(){el.classList.remove('show');el.classList.add('exit');setTimeout(function(){el.remove()},400)},3500);
}

function escHtml(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML}

function timeAgo(ds){
  var now=new Date(),d=new Date(ds.replace(/-/g,'/')),s=Math.floor((now-d)/1000);
  if(s<60)return'Just now';var m=Math.floor(s/60);if(m<60)return m+'m ago';var h=Math.floor(m/60);if(h<24)return h+'h ago';var dy=Math.floor(h/24);if(dy<30)return dy+'d ago';return Math.floor(dy/30)+'mo ago';
}

/* ── Reviews ── */
var selRating=0,hovRating=0,curReviewPid=0,isLoggedIn=<?= $user?'true':'false' ?>;
var ratingLabels=['','Terrible','Poor','Average','Good','Excellent'];

function openReviewModal(pid){
  curReviewPid=pid;selRating=0;hovRating=0;
  var ov=document.getElementById('reviewOverlay'),bd=document.getElementById('reviewBody');
  bd.innerHTML='<div class="rev-loading"><div class="spinner"></div><p style="font-size:.82rem;color:rgba(255,255,255,.3)">Loading...</p></div>';
  ov.classList.add('open');document.body.style.overflow='hidden';
  fetch('fetch_reviews.php?product_id='+pid).then(function(r){return r.json()}).then(function(data){
    if(data.error){bd.innerHTML='<div class="rev-empty"><i class="fa-solid fa-triangle-exclamation"></i><p>'+escHtml(data.error)+'</p></div>';return}
    document.getElementById('reviewProdImg').src=data.product_image||'https://picsum.photos/seed/rev'+pid+'/100/100.jpg';
    document.getElementById('reviewProdName').textContent=data.product_name;
    document.getElementById('reviewProdPrice').textContent='Rs. '+Number(data.product_price).toLocaleString();
    var sh='';for(var i=1;i<=5;i++){if(i<=Math.floor(data.avg_rating))sh+='<i class="fa-solid fa-star" style="color:#fbbf24;font-size:.6rem"></i>';else if(i-.5<=data.avg_rating)sh+='<i class="fa-solid fa-star-half-stroke" style="color:#fbbf24;font-size:.6rem"></i>';else sh+='<i class="fa-regular fa-star" style="font-size:.6rem;color:rgba(255,255,255,.1)"></i>'}
    document.getElementById('reviewProdStars').innerHTML=sh;
    document.getElementById('reviewProdStats').textContent=data.avg_rating+' ('+data.total_reviews+')';
    var h='<div style="padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.05)">';
    if(!isLoggedIn)h+='<div class="flex items-center gap-3 p-4 rounded-xl" style="background:rgba(255,255,255,.03);border:1px dashed rgba(255,255,255,.1)"><i class="fa-solid fa-lock" style="color:rgba(255,255,255,.15);font-size:1rem"></i><p class="text-sm" style="color:rgba(255,255,255,.4)">Please <a href="../user/login.php" class="font-bold no-underline" style="color:#60a5fa">login</a> to write a review.</p></div>';
    else if(data.user_review)h+='<div class="flex items-center gap-3 p-4 rounded-xl" style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2)"><i class="fa-solid fa-circle-check" style="color:#4ade80"></i><p class="text-sm font-semibold" style="color:#4ade80">You already reviewed — '+data.user_review.rating+' star'+(data.user_review.rating>1?'s':'')+'</p></div>';
    else{h+='<h4 class="text-sm font-bold mb-3 text-white"><i class="fa-solid fa-pen text-amber-400 mr-1"></i> Write a Review</h4><div class="star-sel" id="starSel">';for(var i=1;i<=5;i++)h+='<button type="button" class="sb" data-star="'+i+'"><i class="fa-solid fa-star"></i></button>';h+='<span class="star-lbl" id="starLbl">Select rating</span></div><textarea class="rev-ta" id="revComment" placeholder="Share your experience..." maxlength="500"></textarea><div class="flex items-center justify-between mt-2"><span id="charCt" class="text-[11px]" style="color:rgba(255,255,255,.2)">0 / 500</span><button class="btn-sub-rev" id="btnSubRev" onclick="submitReview()" disabled><i class="fa-solid fa-paper-plane text-xs"></i> Submit</button></div>'}
    h+='</div><div style="padding:12px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.05)"><span class="text-[11px] font-bold uppercase tracking-wider" style="color:rgba(255,255,255,.25)">Reviews</span><span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg" style="background:rgba(255,255,255,.05);color:rgba(255,255,255,.3)">'+data.total_reviews+'</span></div>';
    if(data.reviews&&data.reviews.length>0){for(var r=0;r<data.reviews.length;r++){var rv=data.reviews[r];h+='<div class="rev-item"><div class="flex items-center gap-2.5 mb-2"><div class="rev-av">';if(rv.user_image)h+='<img src="../backend/uploads/'+rv.user_image+'" alt="" onerror="this.parentNode.textContent=\''+rv.user_name.charAt(0).toUpperCase()+'\'">';else h+=rv.user_name.charAt(0).toUpperCase();h+='</div><span class="text-sm font-semibold text-white">'+escHtml(rv.user_name)+'</span><span class="text-[11px] ml-auto" style="color:rgba(255,255,255,.2)">'+timeAgo(rv.created_at)+'</span></div><div class="flex gap-0.5 mb-2">';for(var s=1;s<=5;s++)h+=s<=rv.rating?'<i class="fa-solid fa-star" style="color:#fbbf24;font-size:.6rem"></i>':'<i class="fa-regular fa-star" style="font-size:.6rem;color:rgba(255,255,255,.1)"></i>';h+='</div><p class="text-sm leading-relaxed" style="color:rgba(255,255,255,.45)">'+escHtml(rv.comment)+'</p></div>'}}else h+='<div class="rev-empty"><i class="fa-regular fa-comment-dots"></i><p>No reviews yet. Be the first!</p></div>';
    bd.innerHTML=h;if(isLoggedIn&&!data.user_review)bindStarSelector();
  }).catch(function(){bd.innerHTML='<div class="rev-empty"><i class="fa-solid fa-triangle-exclamation"></i><p>Failed to load reviews.</p></div>'});
}
function closeReviewModal(){var ov=document.getElementById('reviewOverlay');if(!ov.classList.contains('open'))return;ov.classList.remove('open');document.body.style.overflow='';curReviewPid=0}
document.getElementById('reviewOverlay').addEventListener('click',function(e){if(e.target===this)closeReviewModal()});

function bindStarSelector(){
  var sel=document.getElementById('starSel');if(!sel)return;
  var btns=sel.querySelectorAll('.sb'),lbl=document.getElementById('starLbl');
  btns.forEach(function(b){b.addEventListener('mouseenter',function(){hovRating=+this.dataset.star;updateStars(btns,hovRating,selRating,lbl)});b.addEventListener('mouseleave',function(){hovRating=0;updateStars(btns,0,selRating,lbl)});b.addEventListener('click',function(){selRating=+this.dataset.star;updateStars(btns,0,selRating,lbl);checkRevForm()})});
  var ta=document.getElementById('revComment'),ct=document.getElementById('charCt');
  if(ta&&ct)ta.addEventListener('input',function(){ct.textContent=this.value.length+' / 500';ct.style.color=this.value.length>450?'#fbbf24':'rgba(255,255,255,.2)';checkRevForm()});
}
function updateStars(btns,hov,sel,lbl){var a=hov>0?hov:sel;btns.forEach(function(b){var v=+b.dataset.star;b.classList.remove('hov','sel');if(v<=a)b.classList.add(hov>0?'hov':'sel')});if(lbl){if(a>0){lbl.textContent=ratingLabels[a];lbl.classList.add('on')}else{lbl.textContent='Select rating';lbl.classList.remove('on')}}}
function checkRevForm(){var b=document.getElementById('btnSubRev'),t=document.getElementById('revComment');if(!b||!t)return;b.disabled=!(selRating>0&&t.value.trim().length>=3)}

function submitReview(){
  if(selRating===0||!curReviewPid)return;var comment=document.getElementById('revComment').value.trim();
  if(comment.length<3){showToast('Write at least 3 characters.','error');return}
  var btn=document.getElementById('btnSubRev'),orig=btn.innerHTML;btn.innerHTML='<i class="fa-solid fa-spinner spin text-xs"></i>...';btn.disabled=true;
  var fd=new FormData();fd.append('product_id',curReviewPid);fd.append('rating',selRating);fd.append('comment',comment);
  fetch('submit_review.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
    if(d.success){showToast('Review submitted!','success');openReviewModal(curReviewPid);updateCardRating(curReviewPid,d.new_avg,d.new_total)}
    else{showToast(d.message||'Failed.','error');btn.innerHTML=orig;btn.disabled=false;checkRevForm()}
  }).catch(function(){showToast('Network error.','error');btn.innerHTML=orig;btn.disabled=false;checkRevForm()});
}

function updateCardRating(pid,newAvg,newTotal){
  var trigger=document.querySelector('[data-review-trigger="'+pid+'"]');if(!trigger)return;
  var card=trigger.closest('.pcard');if(!card)return;
  var ratingDiv=card.querySelector('.flex.items-center.gap-1\\.5');if(!ratingDiv)return;
  var avg=Math.round(newAvg*10)/10,html='';
  for(var i=1;i<=5;i++){if(i<=Math.floor(avg))html+='<i class="fa-solid fa-star text-amber-400" style="font-size:.65rem"></i>';else if(i-.5<=avg)html+='<i class="fa-solid fa-star-half-stroke text-amber-400" style="font-size:.65rem"></i>';else html+='<i class="fa-regular fa-star" style="font-size:.65rem;color:rgba(255,255,255,.1)"></i>'}
  html+='<span class="text-xs font-medium" style="color:rgba(255,255,255,.5)">'+avg+' ('+newTotal+')</span>';
  html+='<span class="review-trigger ml-auto text-xs underline cursor-pointer transition-colors hover:text-amber-400" style="color:rgba(255,255,255,.3)" data-review-trigger="'+pid+'">Reviews</span>';
  ratingDiv.innerHTML=html;
}

document.addEventListener('click',function(e){var t=e.target.closest('[data-review-trigger]');if(t){e.preventDefault();e.stopPropagation();openReviewModal(+t.dataset.reviewTrigger)}});

/* ── Category Filter ── */
var currentCat=<?= $active_cat ?>;
function loadCategory(cid){
  if(cid===currentCat)return;currentCat=cid;
  document.querySelectorAll('.cat-pill').forEach(function(p){p.classList.toggle('active',+p.dataset.cat===cid)});
  var clearBtn=document.getElementById('clearFilter');if(clearBtn)clearBtn.classList.toggle('hidden',cid===0);
  var grid=document.getElementById('productsGrid'),sh='';
  for(var i=0;i<4;i++){sh+='<div class="rounded-2xl overflow-hidden" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05)"><div class="skel" style="height:240px"></div><div class="p-4"><div class="skel" style="height:12px;width:75%;margin-bottom:8px"></div><div class="skel" style="height:10px;width:100%;margin-bottom:6px"></div><div class="skel" style="height:10px;width:50%;margin-bottom:12px"></div><div class="skel" style="height:20px;width:90px;margin-bottom:14px"></div><div class="flex gap-2"><div class="skel" style="width:42px;height:42px;border-radius:14px"></div><div class="skel" style="flex:1;height:42px;border-radius:14px"></div></div></div></div>'}
  grid.innerHTML=sh;
  history.pushState({cat_id:cid},'','index.php'+(cid>0?'?cat_id='+cid:''));
  document.getElementById('products').scrollIntoView({behavior:'smooth',block:'start'});
  fetch('fetch_products.php?cat_id='+cid).then(function(r){return r.json()}).then(function(data){
    if(data.empty){grid.innerHTML='<div class="sm:col-span-2 lg:col-span-4 text-center py-20"><div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:rgba(255,255,255,.04)"><i class="fa-solid fa-box-open text-3xl" style="color:rgba(255,255,255,.1)"></i></div><h3 class="text-lg font-bold mb-2 text-white">No Products Found</h3><p class="text-sm mb-5" style="color:rgba(255,255,255,.3)">This category is empty.</p><button onclick="loadCategory(0)" class="glass-btn-dark inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold cursor-pointer"><i class="fa-solid fa-arrow-left text-xs"></i> Browse All</button></div>'}
    else{grid.innerHTML=data.html;var cards=grid.querySelectorAll('.pcard');cards.forEach(function(c,i){c.classList.add('rv','d'+((i%4)+1))});var obs=new IntersectionObserver(function(en,o){en.forEach(function(el){if(el.isIntersecting){el.target.classList.add('on');o.unobserve(el.target)}})},{threshold:.08});cards.forEach(function(c){obs.observe(c)})}
  }).catch(function(){grid.innerHTML='<div class="sm:col-span-2 lg:col-span-4 text-center py-20"><p class="text-sm" style="color:#f87171">Failed to load products.</p></div>'});
}
window.addEventListener('popstate',function(e){var c=(e.state&&e.state.cat_id!==undefined)?e.state.cat_id:0;currentCat=-1;loadCategory(c)});

/* ── Add to Cart ── */
document.addEventListener('click',function(e){
  var btn=e.target.closest('[data-add-cart]');if(!btn)return;e.preventDefault();
  var pid=btn.dataset.addCart,orig=btn.innerHTML;
  btn.innerHTML='<i class="fa-solid fa-spinner spin text-xs"></i>';btn.style.pointerEvents='none';
  var fd=new FormData();fd.append('product_id',pid);fd.append('quantity',1);
  fetch('add_to_cart.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(data){
    if(data.success){
      btn.innerHTML='<i class="fa-solid fa-check text-xs"></i> Added';btn.classList.add('added');
      var badge=document.getElementById('navCartBadge');if(badge){badge.textContent=data.cart_count;badge.classList.remove('badge-pop');void badge.offsetWidth;badge.classList.add('badge-pop')}
      var mob=document.querySelector('.mob-badge');if(mob)mob.textContent=data.cart_count;
      showToast('Added to cart!','success');
      setTimeout(function(){btn.innerHTML=orig;btn.classList.remove('added');btn.style.pointerEvents=''},1500);
    }else{btn.innerHTML='<i class="fa-solid fa-xmark text-xs"></i>';btn.style.background='rgba(239,68,68,.15)';btn.style.borderColor='rgba(239,68,68,.3)';btn.style.color='#f87171';showToast(data.message||'Failed.','error');setTimeout(function(){btn.innerHTML=orig;btn.style.background='';btn.style.borderColor='';btn.style.color='';btn.style.pointerEvents=''},1200)}
  }).catch(function(){btn.innerHTML=orig;btn.style.pointerEvents='';showToast('Network error.','error')});
});
</script>
</body>
</html>