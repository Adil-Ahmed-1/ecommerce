<?php
session_start();
include("../backend/config/db.php");

// Already logged in?
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: ../backend/dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit;
}

 $error = "";
 $success = "";

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'registered') $success = "Account created! Please login.";
    if ($_GET['msg'] === 'logout') $success = "You have been logged out.";
    if ($_GET['msg'] === 'password_changed') $success = "Password changed! Login with new password.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';

            if ($_SESSION['user_role'] === 'admin') {
                header("Location: ../admin.php");
            } else {
                header("Location: user_dashboard.php");
            }
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
<title>Customer Login</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
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
  .form-input { transition:border-color 0.2s ease, box-shadow 0.2s ease; }
  .form-input:focus { border-color:#16b364; box-shadow:0 0 0 3px rgba(22,179,100,0.12); outline:none; }
  .btn-brand {
    background:linear-gradient(135deg,#16b364,#0a9150);
    transition:all 0.25s ease; position:relative; overflow:hidden;
  }
  .btn-brand:hover { transform:translateY(-1px); box-shadow:0 8px 24px -6px rgba(22,179,100,0.45); }
  .btn-brand:active { transform:translateY(0); }

  @keyframes float {
    0%,100% { transform:translateY(0) rotate(0deg); }
    50% { transform:translateY(-20px) rotate(5deg); }
  }
  .float-1 { animation: float 6s ease-in-out infinite; }
  .float-2 { animation: float 8s ease-in-out infinite 1s; }
  .float-3 { animation: float 7s ease-in-out infinite 2s; }

  @keyframes fadeUp {
    from { opacity:0; transform:translateY(30px); }
    to { opacity:1; transform:translateY(0); }
  }
  .fade-up { animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards; }
  .fade-up-2 { animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) 0.1s forwards; opacity:0; }
  .fade-up-3 { animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) 0.2s forwards; opacity:0; }
</style>
</head>

<body class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-50/50 flex items-center justify-center p-4 relative overflow-hidden">

  <div class="absolute top-20 left-10 w-72 h-72 bg-brand-200/30 rounded-full blur-3xl float-1"></div>
  <div class="absolute bottom-20 right-10 w-96 h-96 bg-brand-100/40 rounded-full blur-3xl float-2"></div>
  <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-brand-300/20 rounded-full blur-3xl float-3"></div>

  <div class="w-full max-w-md relative z-10">
    
    <div class="text-center mb-8 fade-up">
      <a href="../frontend/" class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 shadow-lg shadow-brand-500/30 mb-4">
        <i class="fa-solid fa-store text-white text-2xl"></i>
      </a>
      <h1 class="text-2xl font-extrabold text-gray-900">Welcome Back</h1>
      <p class="text-sm text-gray-500 mt-1">Sign in to your customer account</p>
    </div>

    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-white/60 shadow-xl shadow-brand-900/5 p-8 fade-up-2">
      
      <?php if (!empty($success)) { ?>
      <div class="flex items-center gap-3 bg-brand-50 border border-brand-200 text-brand-700 rounded-xl px-4 py-3 mb-6">
        <i class="fa-solid fa-circle-check text-brand-500"></i>
        <p class="text-sm font-medium"><?= $success ?></p>
      </div>
      <?php } ?>

      <?php if (!empty($error)) { ?>
      <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-6">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        <p class="text-sm font-medium"><?= $error ?></p>
      </div>
      <?php } ?>

      <form method="POST" action="" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fa-solid fa-envelope text-sm"></i></span>
            <input type="email" name="email" required
              class="form-input w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 pl-11 pr-4 text-sm text-gray-800 placeholder:text-gray-400"
              placeholder="you@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Password</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fa-solid fa-lock text-sm"></i></span>
            <input type="password" name="password" id="loginPw" required
              class="form-input w-full bg-gray-50 border border-gray-200 rounded-xl py-3.5 pl-11 pr-11 text-sm text-gray-800 placeholder:text-gray-400"
              placeholder="Enter your password">
            <button type="button" onclick="togglePw('loginPw', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-500 transition">
              <i class="fa-solid fa-eye text-sm"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-brand w-full text-white font-bold text-sm py-3.5 rounded-xl flex items-center justify-center gap-2">
          <i class="fa-solid fa-right-to-bracket text-xs"></i>
          Sign In
        </button>
      </form>

      <div class="mt-6 pt-6 border-t border-gray-100 text-center">
        <p class="text-sm text-gray-500">
          Don't have an account?
          <a href="register.php" class="font-semibold text-brand-600 hover:text-brand-700 transition ml-1">Create Account</a>
        </p>
      </div>
    </div>

    <div class="text-center mt-6 fade-up-3 space-y-2">
      <a href="../login.php" class="text-xs text-gray-400 hover:text-brand-500 transition block">
        <i class="fa-solid fa-shield-halved text-[10px] mr-1"></i> Admin Login
      </a>
      <p class="text-xs text-gray-400">&copy; <?= date('Y') ?> Commerce. All rights reserved.</p>
    </div>
  </div>

</body>
</html>
<script>
function togglePw(id, btn) {
  var input = document.getElementById(id);
  var icon = btn.querySelector('i');
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
</script>
</html>