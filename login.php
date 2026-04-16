<?php
session_start();
include("../ecommerce/backend/config/db.php");

/* ===== AGAR ALREADY LOGIN HAI TOH DASHBOARD PE BHIJWA DO ===== */
if (isset($_SESSION['user_id'])) {
    header("Location: ../ecommerce/backend/dashboard.php");
    exit;
}

 $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        $is_valid = false;

        // 1. Check hashed password
        if ($user && password_verify($password, $user['password'])) {
            $is_valid = true;
        } 
        // 2. Fallback: Check plain text password
        elseif ($user && $password === $user['password']) {
            $is_valid = true;
            
            // Plain text ko hash karke DB mein update kar do
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_pw, $user['id']);
            mysqli_stmt_execute($update_stmt);
        }

        if ($is_valid) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: ../ecommerce/backend/dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Admin Panel</title>

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

  .orb {
    position:fixed; border-radius:50%; filter:blur(100px); opacity:0.4; pointer-events:none;
    animation:orbFloat 12s ease-in-out infinite alternate;
  }
  .orb-1 {
    width:500px; height:500px; top:-150px; right:-100px;
    background:radial-gradient(circle,rgba(22,179,100,0.25),transparent 70%);
  }
  .orb-2 {
    width:400px; height:400px; bottom:-100px; left:-80px;
    background:radial-gradient(circle,rgba(58,205,126,0.15),transparent 70%);
    animation-delay:-5s; animation-duration:16s;
  }
  .orb-3 {
    width:300px; height:300px; top:50%; left:50%;
    transform:translate(-50%,-50%);
    background:radial-gradient(circle,rgba(22,179,100,0.1),transparent 70%);
    animation-delay:-8s; animation-duration:20s;
  }
  @keyframes orbFloat {
    0% { transform:translate(0,0) scale(1); }
    50% { transform:translate(30px,-25px) scale(1.06); }
    100% { transform:translate(-20px,20px) scale(0.95); }
  }

  .card-float { animation:cardFloat 5s ease-in-out infinite; }
  @keyframes cardFloat {
    0%,100% { transform:translateY(0); }
    50% { transform:translateY(-8px); }
  }

  .fade-up {
    opacity:0; transform:translateY(24px);
    animation:fadeUp 0.7s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1; transform:translateY(0); } }

  .form-input {
    transition:border-color 0.2s ease, box-shadow 0.2s ease;
  }
  .form-input:focus {
    border-color:#16b364;
    box-shadow:0 0 0 3px rgba(22,179,100,0.12);
    outline:none;
  }

  .btn-brand {
    background:linear-gradient(135deg,#16b364,#0a9150);
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative; overflow:hidden;
  }
  .btn-brand::before {
    content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);
    transition:left 0.5s ease;
  }
  .btn-brand:hover::before { left:100%; }
  .btn-brand:hover {
    transform:translateY(-1px);
    box-shadow:0 10px 30px -6px rgba(22,179,100,0.5);
  }
  .btn-brand:active { transform:translateY(0); }

  .shake { animation:shake 0.4s ease; }
  @keyframes shake {
    0%,100% { transform:translateX(0); }
    20% { transform:translateX(-6px); }
    40% { transform:translateX(6px); }
    60% { transform:translateX(-4px); }
    80% { transform:translateX(4px); }
  }

  .pw-toggle { transition:color 0.2s ease; }
  .pw-toggle:hover { color:#16b364; }

  .grid-pattern {
    background-image:
      linear-gradient(rgba(22,179,100,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(22,179,100,0.03) 1px, transparent 1px);
    background-size:40px 40px;
  }
  .dark .grid-pattern {
    background-image:
      linear-gradient(rgba(22,179,100,0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(22,179,100,0.04) 1px, transparent 1px);
  }

  ::-webkit-scrollbar { width:6px; }
  ::-webkit-scrollbar-track { background:transparent; }
  ::-webkit-scrollbar-thumb { background:rgba(0,0,0,0.15); border-radius:99px; }
  .dark ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.08); }
</style>

</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen grid-pattern">

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="min-h-screen flex items-center justify-center px-4 py-8">

  <div class="w-full max-w-[960px] grid grid-cols-1 lg:grid-cols-2 gap-6 items-center">

    <!-- LEFT -->
    <div class="fade-up hidden lg:block text-center lg:text-left" style="animation-delay:0.1s">

      <div class="inline-flex items-center gap-3 mb-8">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/25">
          <i class="fa-solid fa-headphones text-white text-lg"></i>
        </div>
        <span class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Beats<span class="text-brand-500">Shop</span></span>
      </div>

      <h1 class="text-4xl xl:text-5xl font-extrabold text-gray-900 dark:text-white leading-[1.15] tracking-tight">
        Admin<br>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 to-brand-400">Dashboard</span>
      </h1>

      <p class="text-gray-400 mt-4 text-base leading-relaxed max-w-sm">
        Manage your products, categories, orders and track analytics — all in one place.
      </p>

      <div class="flex items-center gap-6 mt-10 justify-center lg:justify-start">
        <div class="text-center">
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white">100%</p>
          <p class="text-xs text-gray-400 mt-0.5">Secure</p>
        </div>
        <div class="w-px h-10 bg-gray-200 dark:bg-white/10"></div>
        <div class="text-center">
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white">24/7</p>
          <p class="text-xs text-gray-400 mt-0.5">Access</p>
        </div>
        <div class="w-px h-10 bg-gray-200 dark:bg-white/10"></div>
        <div class="text-center">
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white">Fast</p>
          <p class="text-xs text-gray-400 mt-0.5">Performance</p>
        </div>
      </div>

      <div class="card-float mt-12 hidden xl:block">
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-xl p-5 max-w-[280px] ml-auto">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
              <i class="fa-solid fa-chart-line text-brand-500 text-sm"></i>
            </div>
            <div>
              <p class="text-sm font-bold text-gray-900 dark:text-white">Revenue</p>
              <p class="text-xs text-gray-400">This month</p>
            </div>
          </div>
          <div class="flex items-end gap-1.5 h-12">
            <div class="flex-1 bg-brand-100 dark:bg-brand-900/40 rounded-md" style="height:40%"></div>
            <div class="flex-1 bg-brand-200 dark:bg-brand-900/60 rounded-md" style="height:65%"></div>
            <div class="flex-1 bg-brand-100 dark:bg-brand-900/40 rounded-md" style="height:45%"></div>
            <div class="flex-1 bg-brand-300 dark:bg-brand-800/60 rounded-md" style="height:80%"></div>
            <div class="flex-1 bg-brand-400 dark:bg-brand-700 rounded-md" style="height:100%"></div>
            <div class="flex-1 bg-brand-300 dark:bg-brand-800/60 rounded-md" style="height:70%"></div>
            <div class="flex-1 bg-brand-500 dark:bg-brand-600 rounded-md" style="height:90%"></div>
          </div>
        </div>
      </div>

    </div>

    <!-- RIGHT -->
    <div class="fade-up" style="animation-delay:0.2s">

      <div id="loginCard" class="bg-white dark:bg-[#131a16] rounded-3xl border border-gray-100 dark:border-white/5 shadow-xl dark:shadow-2xl p-8 xl:p-10">

        <div class="lg:hidden flex items-center justify-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center">
            <i class="fa-solid fa-headphones text-white text-sm"></i>
          </div>
          <span class="text-xl font-extrabold text-gray-900 dark:text-white">Beats<span class="text-brand-500">Shop</span></span>
        </div>

        <div class="text-center mb-8">
          <div class="w-16 h-16 rounded-2xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-right-to-bracket text-brand-500 text-2xl"></i>
          </div>
          <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Welcome Back</h2>
          <p class="text-sm text-gray-400 mt-1">Sign in to access your dashboard</p>
        </div>

        <?php if (!empty($error)) { ?>
          <div id="errorBox" class="shake mb-5 flex items-center gap-3 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 rounded-xl px-4 py-3">
            <div class="w-7 h-7 rounded-lg bg-red-500 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-xmark text-white text-xs"></i>
            </div>
            <p class="text-sm font-medium"><?= htmlspecialchars($error) ?></p>
          </div>
        <?php } ?>

        <form method="POST" action="" class="space-y-5">

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Email Address</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                <i class="fa-solid fa-envelope text-sm"></i>
              </span>
              <input
                type="email" name="email"
                class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-sm text-gray-800 dark:text-white placeholder:text-gray-400"
                placeholder="admin@example.com"
                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                required autocomplete="email"
              >
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Password</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                <i class="fa-solid fa-lock text-sm"></i>
              </span>
              <input
                type="password" name="password" id="pwInput"
                class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3.5 pl-11 pr-11 text-sm text-gray-800 dark:text-white placeholder:text-gray-400"
                placeholder="Enter your password"
                required autocomplete="current-password"
              >
              <button type="button" onclick="togglePw()" class="pw-toggle absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                <i class="fa-solid fa-eye" id="pwIcon"></i>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer group">
              <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 dark:border-white/10 text-brand-500 focus:ring-brand-500 focus:ring-offset-0 dark:bg-white/5 cursor-pointer">
              <span class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition">Remember me</span>
            </label>
            <a href="#" class="text-xs font-semibold text-brand-500 hover:text-brand-600 transition">Forgot password?</a>
          </div>

          <button type="submit" class="btn-brand w-full text-white font-bold text-sm py-3.5 rounded-xl flex items-center justify-center gap-2">
            <span>Sign In</span>
            <i class="fa-solid fa-arrow-right text-xs"></i>
          </button>

        </form>

        <div class="flex items-center gap-3 my-6">
          <div class="flex-1 h-px bg-gray-200 dark:bg-white/5"></div>
          <span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Secure Login</span>
          <div class="flex-1 h-px bg-gray-200 dark:bg-white/5"></div>
        </div>

        <p class="text-center text-xs text-gray-400">
          Protected with <i class="fa-solid fa-shield-halved text-brand-500 mx-0.5"></i> encryption
        </p>

      </div>

    </div>

  </div>

</div>

<button onclick="toggleDark()" id="darkBtn" class="fixed top-5 right-5 z-50 w-10 h-10 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 shadow-lg hover:shadow-xl flex items-center justify-center transition text-gray-600 dark:text-white/70 hover:bg-gray-50 dark:hover:bg-white/10">
  <i class="fa-solid fa-moon text-sm dark:hidden"></i>
  <i class="fa-solid fa-sun text-sm hidden dark:inline-block"></i>
</button>

<script>

function toggleDark() {
  document.documentElement.classList.toggle('dark');
  document.body.classList.toggle('dark');
  localStorage.setItem('dark', document.body.classList.contains('dark'));
}
if (localStorage.getItem('dark') === 'true') {
  document.documentElement.classList.add('dark');
  document.body.classList.add('dark');
}

function togglePw() {
  const input = document.getElementById('pwInput');
  const icon = document.getElementById('pwIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

<?php if (!empty($error)) { ?>
  setTimeout(function() {
    const box = document.getElementById('errorBox');
    if (box) {
      box.style.transition = 'all 0.3s ease';
      box.style.opacity = '0';
      box.style.transform = 'translateY(-8px)';
      setTimeout(() => box.remove(), 300);
    }
  }, 6000);
<?php } ?>

</script>

</body>
</html>