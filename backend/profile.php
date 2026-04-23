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

/* ===== GET USER DATA & ROLE LOGIC ===== */
 $user_name    = $user['name'] ?? $_SESSION['user_name'] ?? 'Unknown';
 $user_email   = $user['email'] ?? '';
 $user_created = 'N/A';

// Role Logic: 1 = Admin, 0 = User (Default User)
 $user_role_id = isset($user['role']) ? (int)$user['role'] : 0; 

// Set Badge Text and Class based on Role
if ($user_role_id == 1) {
    $user_role_text = "Admin";
    $role_badge_class = "role-admin"; // Green Badge
} else {
    $user_role_text = "User";
    $role_badge_class = "role-user";  // Blue Badge
}

// Handle Image: Check if DB column 'image' exists and has value
 $db_image = isset($user['image']) && !empty($user['image']) ? $user['image'] : null;

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

/* ===== AVATAR URL LOGIC ===== */
// Priority: Session (if just updated) -> DB -> Default
 $final_image = isset($_SESSION['user_image']) ? $_SESSION['user_image'] : $db_image;

if ($final_image) {
    $user_image = $final_image; 
} else {
    $user_image = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true&size=128';
}
 $avatar_image = $user_image;

/* ===== HANDLE PROFILE UPDATE & IMAGE UPLOAD ===== */
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
        // Image Upload Logic Start
        $upload_ok = 1;
        $new_image_path = $db_image; 

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_name = $_FILES['profile_image']['name'];
            $file_tmp  = $_FILES['profile_image']['tmp_name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = array("jpg", "jpeg", "png", "webp");

            if (in_array($file_ext, $allowed_ext)) {
                if ($file_size < 2000000) {
                    $new_file_name = "user_" . $uid . "_" . time() . "." . $file_ext;
                    $target_file = $target_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $new_image_path = $target_file;
                    } else {
                        $info_error = "Error uploading file.";
                        $upload_ok = 0;
                    }
                } else {
                    $info_error = "File size must be less than 2MB.";
                    $upload_ok = 0;
                }
            } else {
                $info_error = "Invalid file format. Only JPG, JPEG, PNG, WEBP allowed.";
                $upload_ok = 0;
            }
        }
        // Image Upload Logic End

        if ($upload_ok) {
            $upd = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, image = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "sssi", $post_name, $post_email, $new_image_path, $uid);
            
            if (mysqli_stmt_execute($upd)) {
                $info_success = "Profile updated successfully!";
                $_SESSION['user_name'] = $post_name;
                
                // UPDATE SESSION IMAGE SO IT SHOWS ON ALL PAGES
                if($new_image_path) {
                    $_SESSION['user_image'] = $new_image_path;
                } else {
                    // If image was removed or kept same, ensure session doesn't break
                    if(isset($_SESSION['user_image'])) unset($_SESSION['user_image']);
                }
                
                // Refresh local variables for display
                $user_name  = $post_name;
                $user_email = $post_email;
                
                if ($new_image_path) {
                    $user_image = $new_image_path;
                    $avatar_image = $user_image;
                } else {
                    $user_image = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true&size=128';
                    $avatar_image = $user_image;
                }
            } else {
                $info_error = "Database update failed.";
            }
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
  body { font-family:'Plus Jakarta Sans',sans-serif; transition: background-color 0.5s ease; }
  ::-webkit-scrollbar { width:6px; }
  ::-webkit-scrollbar-track { background:transparent; }
  ::-webkit-scrollbar-thumb { background:rgba(0,0,0,0.2); border-radius:99px; }
  .dark ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); }

  /* --- GLASSMORPHISM DESIGN --- */
  body { background-color: #f0f2f5; }
  .dark body { background-color: #050b08; }

  .blob { position:fixed; border-radius:50%; filter:blur(80px); z-index:-1; opacity:0.6; animation:float 10s infinite alternate cubic-bezier(0.4,0,0.2,1); }
  .blob-1 { top:-10%; left:-10%; width:50vw; height:50vw; background:#aaf0c6; animation-delay:0s; }
  .blob-2 { bottom:-10%; right:-10%; width:60vw; height:60vw; background:#60a5fa; animation-delay:-2s; }
  .blob-3 { top:40%; left:40%; width:40vw; height:40vw; background:#c084fc; animation-delay:-4s; opacity:0.4; }
  .dark .blob-1 { background:#064e3b; }
  .dark .blob-2 { background:#1e3a8a; }
  .dark .blob-3 { background:#581c87; }

  @keyframes float { 0% { transform:translate(0,0) scale(1); } 100% { transform:translate(20px,40px) scale(1.1); } }

  .glass-panel {
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
  }
  .dark .glass-panel {
    background: rgba(13, 20, 16, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
  }

  .sidebar-glass {
    background: rgba(8, 75, 46, 0.85);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-right: 1px solid rgba(255, 255, 255, 0.1);
  }
  .dark .sidebar-glass {
    background: rgba(3, 42, 26, 0.85);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
  }

  .topbar-glass {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
  }
  .dark .topbar-glass {
    background: rgba(10, 20, 15, 0.7);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  }
  /* --------------------------- */

  .nav-link { position:relative; transition:all 0.25s cubic-bezier(.4,0,.2,1); }
  .nav-link::before {
    content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
    width:3px;height:0;border-radius:0 4px 4px 0;
    background:#cbcd3a;transition:height 0.25s cubic-bezier(.4,0,.2,1);
  }
  .nav-link:hover::before,.nav-link.active::before { height:60%; }
  .nav-link.active { background:rgba(255,255,255,0.1); color:#cbcd3a; }
  .nav-link:hover { background:rgba(255,255,255,0.05); }

  .topbar-line::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(58,205,126,0.25),transparent);
  }

  .form-input { 
    transition:border-color 0.2s ease, box-shadow 0.2s ease; 
    background: rgba(255, 255, 255, 0.5); 
    border: 1px solid rgba(0,0,0,0.05);
  }
  .dark .form-input { 
    background: rgba(255, 255, 255, 0.03); 
    border: 1px solid rgba(255, 255, 255, 0.05);
  }
  .form-input:focus {
    border-color:#3acd7e;
    box-shadow:0 0 0 3px rgba(58,205,126,0.12);
    outline:none;
    background: #fff;
  }
  .dark .form-input:focus { background: rgba(255, 255, 255, 0.05); }

  .btn-brand {
    background:linear-gradient(135deg,#16b364,#0a9150);
    transition:all 0.25s cubic-bezier(.4,0,.2,1);
    position:relative;overflow:hidden;
    box-shadow: 0 4px 12px rgba(22,179,100,0.2);
  }
  .btn-brand::before {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);
    transition:left 0.5s ease;
  }
  .btn-brand:hover::before { left:100%; }
  .btn-brand:hover { transform:translateY(-1px); box-shadow:0 8px 24px -6px rgba(22,179,100,0.45); }

  .fade-up { opacity:0;transform:translateY(20px); animation:fadeUp 0.5s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  .dropdown-enter { animation:dropIn 0.2s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes dropIn { from { opacity:0;transform:translateY(-8px) scale(0.96); } to { opacity:1;transform:translateY(0) scale(1); } }

  .pw-toggle { transition:color 0.2s ease; cursor:pointer; }
  .pw-toggle:hover { color:#3acd7e; }

  .role-badge { font-size:9px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;padding:2px 7px;border-radius:6px; backdrop-filter:blur(4px); }
  .role-admin { background:rgba(58,205,126,0.2); color:#3acd7e; }
  .role-user { background:rgba(96,165,250,0.2); color:#60a5fa; }

  .sidebar-collapsed .sidebar-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-logo-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-avatar { width:36px; height:36px; }

  .field-group label { display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:6px; }
  .dark .field-group label { color:rgba(255,255,255,0.5); }

  /* File Input Styling */
  .file-upload-wrapper {
    position: relative;
    width: 100%;
    height: 140px;
    border: 2px dashed rgba(0,0,0,0.1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.3);
    overflow: hidden;
  }
  .dark .file-upload-wrapper {
    border-color: rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.02);
  }
  .file-upload-wrapper:hover { border-color: #3acd7e; background: rgba(58,205,126,0.05); }
  .file-upload-wrapper input[type="file"] { position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2; }
  .file-preview { position: absolute; width: 100%; height: 100%; object-fit: cover; z-index: 1; display: none; }
  .file-upload-wrapper.has-image .file-preview { display: block; }
  .file-upload-wrapper.has-image .file-placeholder { display: none; }
</style>
</head>

<body class="min-h-screen text-gray-800 dark:text-white transition-colors duration-500">

<!-- Ambient Backgrounds -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- ========== SIDEBAR ========== -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col shadow-2xl">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0 shadow-lg shadow-brand-500/20">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm backdrop-blur-md">
      <i class="fa-solid fa-bars text-xs"></i>
    </button>
  </div>

  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <!-- Image source prioritizes session to show immediate updates -->
    <img id="sidebarAvatar" src="<?= $user_image ?>" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0 shadow-sm">
    <div class="sidebar-text transition-all duration-300">
      <div class="flex items-center gap-2">
        <p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p>
        <span class="role-badge <?= $role_badge_class ?>"><?= $user_role_text ?></span>
      </div>
      <p class="text-[11px] text-white/60 mt-0.5"><?= htmlspecialchars($user_email) ?></p>
    </div>
  </div>

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/40 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>

    <?php if ($user_role_id == 1) { ?>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/40 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
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
    <?php } ?>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/40 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Account</p>
    <a href="profile.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-user-gear w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">My Profile</span>
    </a>
  </nav>

  <div class="px-3 pb-5">
    <div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300 border border-white/5 backdrop-blur-sm">
      <p class="text-[11px] text-white/40 mb-1">Storage Used</p>
      <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
        <div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full shadow-[0_0_10px_rgba(58,205,126,0.5)]"></div>
      </div>
      <p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p>
    </div>
  </div>
</aside>

<!-- ========== MAIN ========== -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300 relative z-10">

  <!-- TOPBAR -->
  <header class="topbar-glass sticky top-0 z-40 px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">My Profile</h1>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Manage your account settings</p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-white/50 dark:bg-white/5 hover:bg-white dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70 backdrop-blur-md border border-white/20">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-white/50 dark:hover:bg-white/5 transition border border-transparent hover:border-white/20">
          <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover shadow-sm">
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 glass-panel dark:glass-panel shadow-2xl rounded-2xl w-52 py-2 overflow-hidden dropdown-enter">
          <div class="px-4 py-3 border-b border-gray-200/50 dark:border-white/5">
            <div class="flex items-center gap-3">
              <img src="<?= $user_image ?>" class="w-10 h-10 rounded-xl object-cover">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400"><?= htmlspecialchars($user_email) ?></p>
                <span class="role-badge <?= $role_badge_class ?> mt-1 inline-block"><?= $user_role_text ?></span>
              </div>
            </div>
          </div>
          <a href="profile.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/80 hover:bg-white/50 dark:hover:bg-white/10 transition">
            <i class="fa-solid fa-user-gear w-4 text-center text-xs"></i> Profile
          </a>
          <div class="border-t border-gray-200/50 dark:border-white/5 mt-1 pt-1">
            <a href="logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
              <i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">

    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-6 fade-up">
      <a href="dashboard.php" class="hover:text-brand-500 transition">Dashboard</a>
      <i class="fa-solid fa-chevron-right text-[8px]"></i>
      <span class="text-gray-900 dark:text-white font-medium">My Profile</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- ===== LEFT: PROFILE CARD ===== -->
      <div class="lg:col-span-1 fade-up">
        <div class="glass-panel rounded-2xl shadow-sm overflow-hidden relative">
          <!-- Cover Image -->
          <div class="h-32 bg-gradient-to-r from-brand-500/80 to-brand-600/60 relative">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
          </div>

          <div class="px-6 pb-6 -mt-16 relative z-10 text-center">
            <div class="relative inline-block">
              <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white dark:border-[#131a16] shadow-xl bg-gray-200">
                <img src="<?= $avatar_image ?>" class="w-full h-full object-cover">
              </div>
            </div>
          </div>

          <div class="px-6 pb-8 text-center">
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= htmlspecialchars($user_name) ?></h2>
            <div class="flex items-center justify-center gap-2 mt-2 flex-wrap">
              <span class="role-badge <?= $role_badge_class ?>"><?= $user_role_text ?></span>
              <span class="text-gray-400 dark:text-gray-600">•</span>
              <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[180px]"><?= htmlspecialchars($user_email) ?></span>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200/50 dark:border-white/5 grid grid-cols-2 gap-4 text-left">
              <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Role</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-white mt-1"><?= $user_role_text ?></p>
              </div>
              <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">User ID</p>
                <p class="text-sm font-semibold text-gray-800 dark:text-white mt-1">#<?= $uid ?></p>
              </div>
            </div>

            <div class="mt-8">
              <a href="logout.php" class="inline-flex items-center gap-2 text-sm font-semibold text-red-500 hover:text-red-600 transition px-6 py-3 rounded-xl border border-red-200 dark:border-red-900/50 hover:bg-red-50 dark:hover:bg-red-900/20 w-full justify-center">
                <i class="fa-solid fa-right-from-bracket text-xs"></i> Sign Out
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== RIGHT: SETTINGS CARDS ===== -->
      <div class="lg:col-span-2 space-y-6 fade-up" style="animation-delay:0.1s">

        <!-- UPDATE INFO -->
        <div class="glass-panel rounded-2xl shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-gray-200/50 dark:border-white/5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-950/50 flex items-center justify-center shrink-0 border border-brand-100 dark:border-brand-500/20">
              <i class="fa-solid fa-user-pen text-brand-500 text-sm"></i>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">Update Profile</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Change your name, email and avatar</p>
            </div>
          </div>

          <?php if (!empty($info_success)) { ?>
          <div class="px-7 pt-5">
            <div class="flex items-center gap-3 bg-brand-50/80 dark:bg-brand-900/30 border border-brand-200 dark:border-brand-800 text-brand-700 dark:text-brand-300 rounded-xl px-5 py-3 backdrop-blur-sm">
              <div class="w-7 h-7 rounded-lg bg-brand-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-white text-xs"></i></div>
              <p class="text-sm font-semibold"><?= $info_success ?></p>
            </div>
          </div>
          <?php } ?>

          <?php if (!empty($info_error)) { ?>
          <div class="px-7 pt-5">
            <div class="flex items-center gap-3 bg-red-50/80 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 rounded-xl px-5 py-3 backdrop-blur-sm">
              <div class="w-7 h-7 rounded-lg bg-red-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-xmark text-white text-xs"></i></div>
              <p class="text-sm font-semibold"><?= $info_error ?></p>
            </div>
          </div>
          <?php } ?>

          <form method="POST" action="" enctype="multipart/form-data" class="px-7 py-5 space-y-5">
            <input type="hidden" name="update_info" value="1">
            
            <!-- Avatar Upload Section -->
            <div class="field-group">
              <label>Profile Picture</label>
              <div class="file-upload-wrapper <?= !empty($db_image) || !empty($_SESSION['user_image']) ? 'has-image' : '' ?>" id="uploadWrapper">
                <input type="file" name="profile_image" id="profile_image" accept="image/*" onchange="previewImage(this)">
                <img src="<?= $user_image ?>" id="imagePreview" class="file-preview object-cover">
                <div class="file-placeholder flex flex-col items-center gap-2 text-center p-4">
                  <div class="w-10 h-10 rounded-full bg-white/50 dark:bg-white/10 flex items-center justify-center text-gray-500 dark:text-gray-400 backdrop-blur-sm shadow-sm">
                    <i class="fa-solid fa-camera text-sm"></i>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">Click to upload avatar</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">SVG, PNG, JPG or WEBP (Max 2MB)</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="field-group">
              <label for="full_name">Full Name</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-user text-xs"></i></span>
                <input type="text" id="full_name" name="full_name" class="form-input w-full rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white" value="<?= htmlspecialchars($post_name) ?>" required>
              </div>
            </div>
            <div class="field-group">
              <label for="profile_email">Email Address</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-envelope text-xs"></i></span>
                <input type="email" id="profile_email" name="email" class="form-input w-full rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white" value="<?= htmlspecialchars($post_email) ?>" required>
              </div>
            </div>
            <button type="submit" class="btn-brand text-white font-semibold text-sm px-8 py-3 rounded-xl flex items-center gap-2 w-full justify-center">
              <i class="fa-solid fa-floppy-disk text-xs"></i> Save Changes
            </button>
          </form>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="glass-panel rounded-2xl shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-gray-200/50 dark:border-white/5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50/80 dark:bg-red-950/50 flex items-center justify-center shrink-0 border border-red-200 dark:border-red-900/50">
              <i class="fa-solid fa-lock text-red-500 text-sm"></i>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">Change Password</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Enter your current password to set a new one</p>
            </div>
          </div>

          <?php if (!empty($pw_error)) { ?>
          <div class="px-7 pt-5 space-y-2">
            <?php foreach ($pw_error as $err) { ?>
            <div class="flex items-center gap-2 bg-red-50/80 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 rounded-xl px-5 py-3 backdrop-blur-sm">
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
                <input type="password" id="current_password" name="current_password" class="form-input w-full rounded-xl py-3 pl-10 pr-10 text-sm text-gray-800 dark:text-white" required>
                <button type="button" onclick="togglePw('current_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pw-toggle"><i class="fa-solid fa-eye text-xs"></i></button>
              </div>
            </div>
            <div class="field-group">
              <label for="new_password">New Password</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-key text-xs"></i></span>
                <input type="password" id="new_password" name="new_password" class="form-input w-full rounded-xl py-3 pl-10 pr-10 text-sm text-gray-800 dark:text-white" placeholder="Minimum 6 characters" required minlength="6">
                <button type="button" onclick="togglePw('new_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pw-toggle"><i class="fa-solid fa-eye text-xs"></i></button>
              </div>
            </div>
            <div class="field-group">
              <label for="confirm_password">Confirm New Password</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-key text-xs"></i></span>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input w-full rounded-xl py-3 pl-10 pr-10 text-sm text-gray-800 dark:text-white" required minlength="6">
                <button type="button" onclick="togglePw('confirm_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pw-toggle"><i class="fa-solid fa-eye text-xs"></i></button>
              </div>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold text-sm py-3 rounded-xl flex items-center justify-center gap-2 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-red-500/20">
              <i class="fa-solid fa-key text-xs"></i> Update Password
            </button>
          </form>
        </div>

        <!-- DANGER ZONE -->
        <div class="glass-panel rounded-2xl shadow-sm overflow-hidden border-red-200/30 dark:border-red-900/30">
          <div class="px-7 py-5 border-b border-red-200/50 dark:border-white/5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50/80 dark:bg-red-950/50 flex items-center justify-center shrink-0 border border-red-200 dark:border-red-900/50">
              <i class="fa-solid fa-triangle-exclamation text-red-500 text-sm"></i>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 dark:text-white">Danger Zone</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Irreversible account actions</p>
            </div>
          </div>
          <div class="px-7 py-5">
            <button onclick="delete_account()" class="w-full text-sm font-semibold text-red-500 hover:text-red-600 bg-red-50/50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 px-5 py-3 rounded-xl border border-red-200 dark:border-red-900/50 flex items-center justify-center gap-2 transition hover:-translate-y-0.5">
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

function previewImage(input) {
  var wrapper = document.getElementById('uploadWrapper');
  var preview = document.getElementById('imagePreview');
  
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      wrapper.classList.add('has-image');
    }
    reader.readAsDataURL(input.files[0]);
  } else {
    // Revert to original image if canceled
    preview.src = '<?= $user_image ?>';
    if('<?= $user_image ?>' !== 'https://ui-avatars.com/api/?name=' + encodeURIComponent('<?= $user_name ?>') + '&background=16b364&color=fff&bold=true&size=128') {
        wrapper.classList.add('has-image');
    } else {
        wrapper.classList.remove('has-image');
    }
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