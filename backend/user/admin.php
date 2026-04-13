<?php
session_start();

/* Agar already logged in hai to dashboard pe bhejo */
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../index.php");
    exit;
}

/* ===== YAHAN PATH THEEK KIYA HAI ===== */
include("../config/db.php");
/* ======================================== */

 $error = '';

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        $stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {

            if (password_verify($password, $row['password'])) {

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_name'] = $row['name'];
                $_SESSION['admin_email'] = $row['email'];

                header("Location: ../index.php");  // ← FIXED: Changed from "index.php" to "../index.php"
                exit;

            } else {
                $error = "Incorrect password.";
            }

        } else {
            $error = "No account found with this email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>

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

  .blob {
    position:absolute;border-radius:50%;filter:blur(80px);opacity:0.5;
    animation:blobFloat 8s ease-in-out infinite alternate;
  }
  .blob-1 {
    width:500px;height:500px;top:-120px;left:-100px;
    background:radial-gradient(circle,rgba(58,205,126,0.3),transparent 70%);
  }
  .blob-2 {
    width:400px;height:400px;bottom:-80px;right:-60px;
    background:radial-gradient(circle,rgba(22,179,100,0.2),transparent 70%);
    animation-delay:-3s;animation-duration:10s;
  }
  .blob-3 {
    width:300px;height:300px;top:40%;left:60%;
    background:radial-gradient(circle,rgba(245,158,11,0.15),transparent 70%);
    animation-delay:-5s;animation-duration:12s;
  }
  .dark .blob { opacity:0.25; }

  @keyframes blobFloat {
    0% { transform:translate(0,0) scale(1); }
    33% { transform:translate(30px,-20px) scale(1.05); }
    66% { transform:translate(-20px,30px) scale(0.95); }
    100% { transform:translate(10px,-10px) scale(1.02); }
  }

  .grid-pattern {
    background-image:
      linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
    background-size:40px 40px;
  }
  .dark .grid-pattern {
    background-image:
      linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  }

  .login-card {
    background:rgba(255,255,255,0.7);
    backdrop-filter:blur(24px);
    -webkit-backdrop-filter:blur(24px);
    border:1px solid rgba(255,255,255,0.5);
  }
  .dark .login-card {
    background:rgba(19,26,22,0.8);
    border:1px solid rgba(255,255,255,0.06);
  }

  .form-input {
    transition:border-color 0.2s ease, box-shadow 0.2s ease;
  }
  .form-input:focus {
    border-color:#3acd7e;
    box-shadow:0 0 0 3px rgba(58,205,126,0.12);
    outline:none;
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
  .btn-brand:hover { transform:translateY(-1px); box-shadow:0 10px 30px -6px rgba(22,179,100,0.5); }
  .btn-brand:active { transform:translateY(0); }

  .spinner {
    width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);
    border-top-color:#fff;border-radius:50%;
    animation:spin 0.6s linear infinite;display:none;
  }
  @keyframes spin { to { transform:rotate(360deg); } }

  .fade-up {
    opacity:0;transform:translateY(20px);
    animation:fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }
  .fade-up-2 { animation-delay:0.1s; }
  .fade-up-3 { animation-delay:0.2s; }

  .shake { animation:shake 0.4s ease; }
  @keyframes shake {
    0%,100% { transform:translateX(0); }
    20% { transform:translateX(-6px); }
    40% { transform:translateX(6px); }
    60% { transform:translateX(-4px); }
    80% { transform:translateX(4px); }
  }

  .pw-toggle {
    position:absolute;right:14px;top:50%;transform:translateY(-50%);
    background:none;border:none;cursor:pointer;color:#9ca3af;
    transition:color 0.2s;
  }
  .pw-toggle:hover { color:#6b7280; }

  .custom-check {
    appearance:none;-webkit-appearance:none;
    width:16px;height:16px;border:2px solid #d1d5db;border-radius:4px;
    cursor:pointer;transition:all 0.2s;position:relative;
  }
  .custom-check:checked { background:#16b364;border-color:#16b364; }
  .custom-check:checked::after {
    content:'✓';position:absolute;top:50%;left:50%;
    transform:translate(-50%,-50%);color:#fff;font-size:10px;font-weight:700;
  }
  .dark .custom-check { border-color:rgba(255,255,255,0.15); }
</style>

</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen grid-pattern overflow-hidden">

<div class="fixed inset-0 pointer-events-none overflow-hidden">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="fixed top-5 right-5 z-50">
  <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-lg border border-gray-200/50 dark:border-white/10 hover:bg-white dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70 shadow-sm">
    <i class="fa-solid fa-moon text-sm"></i>
  </button>
</div>

<div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-8">

  <div class="w-full max-w-[420px]">

    <div class="text-center mb-8 fade-up">
      <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center mx-auto shadow-lg shadow-brand-500/25 mb-5">
        <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
      </div>
      <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Welcome Back</h1>
      <p class="text-sm text-gray-400 mt-1.5">Sign in to your admin panel</p>
    </div>

    <div id="loginCard" class="login-card rounded-3xl shadow-xl dark:shadow-2xl p-8 fade-up fade-up-2">

      <?php if ($error) { ?>
        <div id="errorMsg" class="shake flex items-center gap-3 bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400 rounded-xl px-4 py-3 mb-6">
          <div class="w-7 h-7 rounded-lg bg-red-500 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-xmark text-white text-xs"></i>
          </div>
          <p class="text-sm font-medium"><?= $error ?></p>
        </div>
      <?php } ?>

      <form method="POST" id="loginForm" onsubmit="return handleLogin(event)">

        <div class="mb-4">
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
              <i class="fa-solid fa-envelope"></i>
            </span>
            <input
              type="email" name="email" id="emailInput"
              class="form-input w-full bg-gray-50/80 dark:bg-white/[0.04] border border-gray-200 dark:border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-sm text-gray-800 dark:text-white placeholder:text-gray-400"
              placeholder="admin@example.com"
              value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
              required
            >
          </div>
        </div>

        <div class="mb-5">
          <div class="flex items-center justify-between mb-2">
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Password</label>
            <a href="#" class="text-[11px] font-medium text-brand-500 hover:text-brand-600 transition">Forgot password?</a>
          </div>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
              <i class="fa-solid fa-lock"></i>
            </span>
            <input
              type="password" name="password" id="pwInput"
              class="form-input w-full bg-gray-50/80 dark:bg-white/[0.04] border border-gray-200 dark:border-white/10 rounded-xl py-3.5 pl-11 pr-12 text-sm text-gray-800 dark:text-white placeholder:text-gray-400"
              placeholder="Enter your password"
              required
            >
            <button type="button" onclick="togglePw()" class="pw-toggle">
              <i id="pwIcon" class="fa-solid fa-eye text-sm"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between mb-7">
          <label class="flex items-center gap-2.5 cursor-pointer">
            <input type="checkbox" name="remember" class="custom-check">
            <span class="text-sm text-gray-600 dark:text-white/50">Remember me</span>
          </label>
        </div>

        <button type="submit" name="login" id="loginBtn" class="btn-brand w-full text-white font-semibold text-sm py-3.5 rounded-xl flex items-center justify-center gap-2">
          <span id="btnText">Sign In</span>
          <div id="btnSpinner" class="spinner"></div>
          <i id="btnArrow" class="fa-solid fa-arrow-right text-xs"></i>
        </button>

      </form>

      <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-gray-200 dark:bg-white/5"></div>
        <span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Secure Access</span>
        <div class="flex-1 h-px bg-gray-200 dark:bg-white/5"></div>
      </div>

      <div class="text-center">
        <div class="inline-flex items-center gap-2 text-xs text-gray-400 bg-gray-50 dark:bg-white/[0.03] px-4 py-2 rounded-xl">
          <i class="fa-solid fa-lock text-[10px] text-brand-400"></i>
          Protected with encrypted authentication
        </div>
      </div>

    </div>

    <p class="text-center text-xs text-gray-400 mt-6 fade-up fade-up-3">
      AdminPanel &copy; <?= date('Y') ?> &mdash; Built with PHP & Tailwind CSS
    </p>

  </div>

</div>

<script>

function toggleDark() {
  const html = document.documentElement;
  const body = document.body;
  const btn = document.getElementById('darkBtn');
  const isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);
  btn.innerHTML = isDark ? '<i class="fa-solid fa-sun text-sm"></i>' : '<i class="fa-solid fa-moon text-sm"></i>';
  localStorage.setItem('darkMode', isDark ? '1' : '0');
}

(function() {
  if (localStorage.getItem('darkMode') === '1') {
    document.documentElement.classList.add('dark');
    document.body.classList.add('dark');
    document.getElementById('darkBtn').innerHTML = '<i class="fa-solid fa-sun text-sm"></i>';
  }
})();

function togglePw() {
  const input = document.getElementById('pwInput');
  const icon = document.getElementById('pwIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

function handleLogin(e) {
  const btn = document.getElementById('loginBtn');
  document.getElementById('btnText').textContent = 'Signing in...';
  document.getElementById('btnSpinner').style.display = 'block';
  document.getElementById('btnArrow').style.display = 'none';
  btn.disabled = true;
  btn.style.opacity = '0.8';
  return true;
}

<?php if ($error) { ?>
setTimeout(function() {
  const el = document.getElementById('errorMsg');
  if (el) {
    el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-8px)';
    setTimeout(() => el.remove(), 300);
  }
}, 6000);
<?php } ?>

document.getElementById('emailInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('pwInput').focus();
  }
});

</script>

</body>
</html>