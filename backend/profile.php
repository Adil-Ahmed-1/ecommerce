<?php
session_start();
include("config/db.php");

/* ===== CHECK LOGIN ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

 $uid = $_SESSION['user_id'];

/* ===== FETCH USER FROM users TABLE ===== */
 $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->bind_param("i", $uid);
 $stmt->execute();
 $result = $stmt->get_result();
 $user = mysqli_fetch_assoc($result);

/* ===== IF NOT FOUND - SHOW ERROR ===== */
if (!$user) {
    die("<div style='background:#111;color:#fff;padding:40px;margin:40px;border-radius:12px;font-family:monospace;'>
        <h3 style='color:#f87171;'>❌ User ID {$uid} not found in users table</h3>
        <p>Check your database.</p>
    </div>");
}

/* ===== GET USER DATA (USE SESSION AS FALLBACK) ===== */
 $user_name    = $user['name'] ?? $_SESSION['user_name'] ?? 'Unknown';
 $user_email   = $user['email'] ?? '';
 $user_role    = ucfirst($_SESSION['user_role'] ?? 'user');
 $user_created = 'N/A';

// Only use created_at if column exists
if (isset($user['created_at']) && $user['created_at']) {
    $user_created = date('d M, Y', strtotime($user['created_at']));
}

/* ===== DEFAULT VALUES ===== */
 $post_name      = $user_name;
 $post_email     = $user_email;
 $info_success   = "";
 $info_error     = "";
 $pw_success     = "";
 $pw_error       = [];
 $avatar_success = "";
 $avatar_error   = "";

/* ===== AVATAR URL (NO image COLUMN - USE UI AVATARS) ===== */
 $user_image    = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true&size=128';
 $avatar_image  = $user_image;

/* ===== HANDLE PROFILE UPDATE ===== */
if (isset($_POST['update_info'])) {
    $post_name  = trim($_POST['full_name']);
    $post_email = trim($_POST['email']);
    $info_error = "";

    if (empty($post_name)) {
        $info_error = "Name is required.";
    } elseif (empty($post_email)) {
        $info_error = "Email is required.";
    } elseif (!filter_var($post_email, FILTER_VALIDATE_EMAIL)) {
        $info_error = "Enter a valid email address.";
    }

    if (empty($info_error)) {
        $upd = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "ssi", $post_name, $post_email, $uid);
        if (mysqli_stmt_execute($upd)) {
            $info_success = "Profile updated successfully!";
            
            // Update session too
            $_SESSION['user_name'] = $post_name;
            
            $user_name  = $post_name;
            $user_email = $post_email;
            $post_name  = $user_name;
            $post_email = $user_email;
            
            $user_image   = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true&size=128';
            $avatar_image = $user_image;
        }
    }
}

/* ===== HANDLE PASSWORD UPDATE ===== */
if (isset($_POST['update_password'])) {
    $current_pw = trim($_POST['current_password']);
    $new_pw     = trim($_POST['new_password']);
    $confirm_pw = trim($_POST['confirm_password']);
    $pw_error   = [];

    if (empty($current_pw))      $pw_error[] = "Current password is required.";
    if (empty($new_pw))          $pw_error[] = "New password is required.";
    if (strlen($new_pw) < 6)     $pw_error[] = "Password must be at least 6 characters.";
    if ($new_pw !== $confirm_pw) $pw_error[] = "New passwords do not match.";

    if (empty($pw_error)) {
        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        $udata = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($udata && password_verify($current_pw, $udata['password'])) {
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "si", $hashed_pw, $uid);
            if (mysqli_stmt_execute($upd)) {
                session_unset();
                header("Location: login.php?msg=password_changed");
                exit;
            }
        } else {
            $pw_error[] = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
  .dark ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.08); }

  .sidebar-glass {
    background:rgba(8,75,46,0.95);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
  }
  .dark .sidebar-glass { background:rgba(3,42,26,0.98); }

  .nav-link { position:relative; transition:all 0.25s cubic-bezier(.4,0,.2,1); }
  .nav-link::before {
    content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
    width:3px;height:0;border-radius:0 4px 4px 0;
    background:#3acd7e;transition:height 0.25s cubic-bezier(.4,0,.2,1);
  }
  .nav-link:hover::before,.nav-link.active::before { height:60%; }
  .nav-link.active { background:rgba(58,205,126,0.12); color:#3acd7e; }
  .nav-link:hover { background:rgba(255,255,255,0.06); }

  .topbar-line::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(58,205,126,0.25),transparent);
  }
  .form-input { transition:border-color 0.2s ease, box-shadow 0.2s ease; }
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
  .btn-brand:hover { transform:translateY(-1px); box-shadow:0 8px 24px -6px rgba(22,179,100,0.45); }
  .btn-brand:active { transform:translateY(0); }

  .fade-up {
    opacity:0;transform:translateY(20px);
    animation:fadeUp 0.5s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  .dropdown-enter { animation:dropIn 0.2s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes dropIn {
    from { opacity:0;transform:translateY(-8px) scale(0.96); }
    to { opacity:1;transform:translateY(0) scale(1); }
  }

  .pw-toggle { transition:color 0.2s ease; cursor:pointer; }
  .pw-toggle:hover { color:#3acd7e; }

  .role-badge {
    font-size:9px;font-weight:700;letter-spacing:0.05em;
    text-transform:uppercase;padding:2px 7px;border-radius:6px;
  }
  .role-admin { background:rgba(58,205,126,0.15); color:#3acd7e; }
  .role-user { background:rgba(96,165,250,0.15); color:#60a5fa; }

  .sidebar-collapsed .sidebar-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-logo-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-avatar { width:36px; height:36px; }

  .field-group label {
    display:block;
    font-size:12px;
    font-weight:600;
    color:#6b7280;
    margin-bottom:6px;
  }
  .dark .field-group label { color:rgba(255,255,255,0.5); }
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- ========== SIDEBAR ========== -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm">
      <i class="fa-solid fa-bars text-xs"></i>
    </button>
  </div>

  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img id="sidebarAvatar" src="<?= $user_image ?>" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <div class="flex items-center gap-2">
        <p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p>
        <span class="role-badge role-admin"><?= $user_role ?></span>
      </div>
      <p class="text-[11px] text-white/50 mt-0.5"><?= htmlspecialchars($user_email) ?></p>
    </div>
  </div>

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="category/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Category</span>
    </a>
    <a href="category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">View Categories</span>
    </a>
    <a href="product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Product</span>
    </a>
    <a href="product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">View Products</span>
    </a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Account</p>
    <a href="profile.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-user-gear w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">My Profile</span>
    </a>
  </nav>

  <div class="px-3 pb-5">
    <div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300">
      <p class="text-[11px] text-white/40 mb-1">Storage Used</p>
      <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
        <div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div>
      </div>
      <p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p>
    </div>
  </div>
</aside>

<!-- ========== MAIN ========== -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">

  <!-- TOPBAR -->
  <header class="topbar-line sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">My Profile</h1>
      <p class="text-xs text-gray-400 mt-0.5">Manage your account settings</p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover">
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl dark:shadow-2xl rounded-2xl w-52 py-2 overflow-hidden dropdown-enter">
          <div class="px-4 py-3 border-b border-gray-100 dark:border-white/5">
            <div class="flex items-center gap-3">
              <img src="<?= $user_image ?>" class="w-10 h-10 rounded-xl object-cover">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></p>
                <p class="text-[11px] text-gray-400"><?= htmlspecialchars($user_email) ?></p>
                <span class="role-badge role-admin mt-1 inline-block"><?= $user_role ?></span>
              </div>
            </div>
          </div>
          <a href="profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition">
            <i class="fa-solid fa-user-gear w-4 text-center text-xs"></i> Profile
          </a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1">
            <a href="logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition">
              <i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-6 fade-up">
      <a href="dashboard.php" class="hover:text-brand-500 transition">Dashboard</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-gray-700"></i>
      <span class="text-gray-700 dark:text-white font-medium">My Profile</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- ===== LEFT: PROFILE CARD ===== -->
      <div class="lg:col-span-1 fade-up">
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          
          <div class="h-28 bg-gradient-to-r from-brand-500/80 to-brand-600/60 relative overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://picsum.photos/seed/cover-profile/1200/400')] bg-cover bg-center opacity-20"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
          </div>

          <div class="px-6 pb-4 -mt-12 relative z-10 text-center">
            <div class="relative inline-block">
              <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-white dark:border-[#131a16] shadow-xl">
                <img src="<?= $avatar_image ?>" class="w-full h-full object-cover">
              </div>
            </div>
          </div>

          <div class="px-6 pb-8 text-center">
            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></h2>
            <div class="flex items-center justify-center gap-2 mt-2 flex-wrap">
              <span class="role-badge role-admin"><?= $user_role ?></span>
              <span class="text-gray-400">•</span>
              <span class="text-xs text-gray-400 truncate max-w-[180px]"><?= htmlspecialchars($user_email) ?></span>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-white/5 grid grid-cols-2 gap-4 text-left">
              <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Role</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-white mt-1"><?= $user_role ?></p>
              </div>
              <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">User ID</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-white mt-1">#<?= $uid ?></p>
              </div>
            </div>

            <div class="mt-8">
              <a href="logout.php" class="inline-flex items-center gap-2 text-sm font-semibold text-red-500 hover:text-red-600 transition px-6 py-3 rounded-xl border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900">
                <i class="fa-solid fa-right-from-bracket text-xs"></i> Sign Out
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== RIGHT: SETTINGS CARDS ===== -->
      <div class="lg:col-span-2 space-y-6 fade-up" style="animation-delay:0.1s">

        <!-- UPDATE INFO -->
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-user-pen text-brand-500 text-sm"></i>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">Update Profile</h3>
              <p class="text-xs text-gray-400 mt-0.5">Change your name and email</p>
            </div>
          </div>

          <?php if (!empty($info_success)) { ?>
          <div class="px-7 pt-5">
            <div class="flex items-center gap-3 bg-brand-50 dark:bg-brand-950 border border-brand-200 dark:border-brand-800 text-brand-700 dark:text-brand-300 rounded-xl px-5 py-3">
              <div class="w-7 h-7 rounded-lg bg-brand-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-white text-xs"></i></div>
              <p class="text-sm font-semibold"><?= $info_success ?></p>
            </div>
          </div>
          <?php } ?>

          <?php if (!empty($info_error)) { ?>
          <div class="px-7 pt-5">
            <div class="flex items-center gap-3 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 rounded-xl px-5 py-3">
              <div class="w-7 h-7 rounded-lg bg-red-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-xmark text-white text-xs"></i></div>
              <p class="text-sm font-semibold"><?= $info_error ?></p>
            </div>
          </div>
          <?php } ?>

          <form method="POST" action="" class="px-7 py-5 space-y-5">
            <input type="hidden" name="update_info" value="1">
            <div class="field-group">
              <label for="full_name">Full Name</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-user text-xs"></i></span>
                <input type="text" id="full_name" name="full_name" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white" value="<?= htmlspecialchars($post_name) ?>" required>
              </div>
            </div>
            <div class="field-group">
              <label for="profile_email">Email Address</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-envelope text-xs"></i></span>
                <input type="email" id="profile_email" name="email" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white" value="<?= htmlspecialchars($post_email) ?>" required>
              </div>
            </div>
            <button type="submit" class="btn-brand text-white font-semibold text-sm px-8 py-3 rounded-xl flex items-center gap-2">
              <i class="fa-solid fa-floppy-disk text-xs"></i> Save Changes
            </button>
          </form>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-lock text-red-500 text-sm"></i>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">Change Password</h3>
              <p class="text-xs text-gray-400 mt-0.5">Enter your current password to set a new one</p>
            </div>
          </div>

          <?php if (!empty($pw_error)) { ?>
          <div class="px-7 pt-5 space-y-2">
            <?php foreach ($pw_error as $err) { ?>
            <div class="flex items-center gap-2 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 rounded-xl px-5 py-3">
              <i class="fa-solid fa-circle-exclamation text-red-500 text-xs shrink-0"></i>
              <p class="text-sm font-medium"><?= $err ?></p>
            </div>
            <?php } ?>
          </div>
          <?php } ?>

          <form method="POST" action="" class="px-7 py-5 space-y-5">
            <input type="hidden" name="update_password" value="1">
            <div class="field-group">
              <label for="current_password">Current Password</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-lock text-xs"></i></span>
                <input type="password" id="current_password" name="current_password" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-10 text-sm text-gray-800 dark:text-white" required>
                <button type="button" onclick="togglePw('current_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pw-toggle"><i class="fa-solid fa-eye text-xs"></i></button>
              </div>
            </div>
            <div class="field-group">
              <label for="new_password">New Password</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-key text-xs"></i></span>
                <input type="password" id="new_password" name="new_password" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-10 text-sm text-gray-800 dark:text-white" placeholder="Minimum 6 characters" required minlength="6">
                <button type="button" onclick="togglePw('new_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pw-toggle"><i class="fa-solid fa-eye text-xs"></i></button>
              </div>
            </div>
            <div class="field-group">
              <label for="confirm_password">Confirm New Password</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-key text-xs"></i></span>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-10 text-sm text-gray-800 dark:text-white" required minlength="6">
                <button type="button" onclick="togglePw('confirm_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pw-toggle"><i class="fa-solid fa-eye text-xs"></i></button>
              </div>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold text-sm py-3 rounded-xl flex items-center justify-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-500/20">
              <i class="fa-solid fa-key text-xs"></i> Update Password
            </button>
          </form>
        </div>

        <!-- DANGER ZONE -->
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-triangle-exclamation text-red-500 text-sm"></i>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">Danger Zone</h3>
              <p class="text-xs text-gray-400 mt-0.5">Irreversible account actions</p>
            </div>
          </div>
          <div class="px-7 py-5">
            <button onclick="delete_account()" class="w-full text-sm font-semibold text-red-500 hover:text-red-600 bg-red-50 dark:bg-red-950 hover:bg-red-100 dark:hover:bg-red-900 px-5 py-3 rounded-xl border border-red-200 dark:border-red-800 flex items-center justify-center gap-2 transition hover:-translate-y-0.5">
              <i class="fa-solid fa-trash-can text-xs"></i> Delete Account Permanently
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</main>

<script>
function toggleSidebar() {
  var sidebar = document.getElementById('sidebar');
  var main = document.getElementById('main');
  var collapsed = sidebar.classList.toggle('sidebar-collapsed');
  sidebar.style.width = collapsed ? '78px' : '260px';
  main.style.marginLeft = collapsed ? '78px' : '260px';
}

function toggleDark() {
  var html = document.documentElement;
  var body = document.body;
  var btn = document.getElementById('darkBtn');
  var isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);
  btn.innerHTML = isDark ? '<i class="fa-solid fa-sun text-sm"></i>' : '<i class="fa-solid fa-moon text-sm"></i>';
}

function toggleMenu() {
  document.getElementById('menu').classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
  var menu = document.getElementById('menu');
  if (!e.target.closest('.relative') && !menu.classList.contains('hidden')) {
    menu.classList.add('hidden');
  }
});

function togglePw(id) {
  var input = document.getElementById(id);
  var icon = input.parentElement.querySelector('.pw-toggle i');
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

function delete_account() {
  Swal.fire({
    title: 'Delete Account?',
    text: 'Are you sure? This will permanently delete your account.',
    icon: 'warning',
    iconColor: '#ef4444',
    confirmButtonColor: '#dc2626',
    confirmButtonText: 'Yes, Delete',
    showCancelButton: true,
    cancelButtonColor: '#6b7280',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    customClass: { popup: 'rounded-2xl' }
  }).then(function(result) {
    if (result.isConfirmed) {
      window.location.href = 'logout.php?type=delete';
    }
  });
}
</script>

</body>
</html>