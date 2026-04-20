<?php
session_start();

// ============ DATABASE CONFIG ============
 $host     = 'localhost';
 $dbname   = 'ecommerce_v2';
 $username = 'root';
 $password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div style='background:#1a1a2e;color:#ff4d6a;padding:30px;font-family:sans-serif;border-radius:12px;margin:40px'><h2>Database Connection Failed</h2><p>" . $e->getMessage() . "</p></div>");
}

// ============ ADMIN LOGIN CHECK ============
 $loginPage = '../login.php';
 $admin = null;

// Try admins table
if (isset($_SESSION['admin_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
    } catch (PDOException $e) {}
}

// Try users table
if (!$admin && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch();
    } catch (PDOException $e) {}
}

// Session mein pura object hai
if (!$admin && isset($_SESSION['admin'])) {
    $admin = $_SESSION['admin'];
}

// Nahi mila to login pe bhejo
if (!$admin) {
    header("Location: " . $loginPage);
    exit;
}

// Safe extract — jo column hai woh lega, nahi to default
 $adminName = $admin['name'] ?? $admin['username'] ?? $admin['full_name'] ?? 'Admin';
 $adminData = [
    'id'         => $admin['id'] ?? 0,
    'name'       => $adminName,
    'email'      => $admin['email'] ?? $admin['user_email'] ?? '',
    'phone'      => $admin['phone'] ?? $admin['mobile'] ?? $admin['contact'] ?? '',
    'avatar'     => 'https://ui-avatars.com/api/?name=' . urlencode($adminName) . '&background=16b364&color=fff&size=200&bold=true',
    'role'       => $admin['role'] ?? $admin['user_role'] ?? 'Admin',
    'joined_at'  => $admin['created_at'] ?? $admin['date_added'] ?? date('Y-m-d H:i:s'),
    'last_login' => $admin['last_login'] ?? $admin['login_at'] ?? date('Y-m-d H:i:s'),
];

// Last login update — sirf agar column exist kare
try {
    $tbl = isset($_SESSION['admin_id']) ? 'admins' : 'users';
    // Pehle check karo column hai ya nahi
    $cols = $pdo->query("SHOW COLUMNS FROM $tbl LIKE 'last_login'")->fetchAll();
    if (count($cols) > 0) {
        $pdo->prepare("UPDATE $tbl SET last_login = NOW() WHERE id = ?")->execute([$adminData['id']]);
    }
} catch (PDOException $e) {
    // Koi baat nahi, skip
}

// ============ AJAX HANDLER ============
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id     = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['pending', 'approved', 'rejected'];
        if ($id > 0 && in_array($status, $allowed)) {
            $stmt = $pdo->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true, 'message' => ucfirst($status) . ' successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
        exit;
    }

    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name && $email) {
            $tbl = isset($_SESSION['admin_id']) ? 'admins' : 'users';

            // Phone column check karo
            $phoneCols = $pdo->query("SHOW COLUMNS FROM $tbl LIKE 'phone'")->fetchAll();
            if (count($phoneCols) > 0) {
                $pdo->prepare("UPDATE $tbl SET name = ?, email = ?, phone = ? WHERE id = ?")->execute([$name, $email, $phone, $adminData['id']]);
            } else {
                $pdo->prepare("UPDATE $tbl SET name = ?, email = ? WHERE id = ?")->execute([$name, $email, $adminData['id']]);
            }

            $adminData['name']  = $name;
            $adminData['email'] = $email;
            $adminData['phone'] = $phone;
            $adminData['avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=16b364&color=fff&size=200&bold=true';
            if (isset($_SESSION['admin'])) $_SESSION['admin']['name'] = $name;
            echo json_encode(['success' => true, 'admin' => $adminData]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        }
        exit;
    }

    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => $loginPage]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ============ FETCH PAYMENTS ============
try {
    // Pehle check karo users table hai ya nahi
    $usersExists = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($usersExists) > 0) {
        $stmt = $pdo->query("
            SELECT p.*,
                   COALESCE(u.name, u.username, p.sender_name) AS user_name,
                   COALESCE(u.email, u.user_email, '') AS user_email
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
        ");
    } else {
        $stmt = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC");
    }
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    $stmt = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC");
    $payments = $stmt->fetchAll();
}

// Ensure keys exist in every payment row
foreach ($payments as &$p) {
    $p['user_name']  = $p['user_name']  ?? $p['sender_name'] ?? 'Unknown';
    $p['user_email'] = $p['user_email'] ?? '';
    $p['notes']      = $p['notes']      ?? '';
    $p['proof_image']= $p['proof_image']?? '';
    $p['status']     = $p['status']     ?? 'pending';
    $p['method']     = $p['method']     ?? 'N/A';
}
unset($p);

 $paymentsJSON = json_encode($payments);
 $adminJSON    = json_encode($adminData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments — Admin Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }, colors: { brand: { 50:'#edfcf2',100:'#d3f8e0',200:'#aaf0c6',300:'#73e2a5',400:'#3acd7e',500:'#16b364',600:'#0a9150',700:'#087442',800:'#095c37',900:'#084b2e',950:'#032a1a' }}}}}
</script>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Plus Jakarta Sans',sans-serif}
::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.12);border-radius:99px}
.dark ::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.08)}
.sidebar-glass{background:linear-gradient(180deg,rgba(8,75,46,0.97) 0%,rgba(3,42,26,0.99) 100%);backdrop-filter:blur(20px)}
.dark .sidebar-glass{background:linear-gradient(180deg,rgba(2,30,18,0.99) 0%,rgba(3,42,26,1) 100%)}
.nav-link{position:relative;transition:all .25s cubic-bezier(.4,0,.2,1)}
.nav-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;border-radius:0 4px 4px 0;background:#cbcd3a;transition:height .25s cubic-bezier(.4,0,.2,1)}
.nav-link:hover::before,.nav-link.active::before{height:60%}
.nav-link.active{background:rgba(58,205,126,0.12);color:#cbcd3a}
.nav-link:hover{background:rgba(255,255,255,0.06)}
.sidebar-collapsed .sidebar-text{opacity:0;width:0;overflow:hidden}
.sidebar-collapsed .sidebar-logo-text{opacity:0;width:0;overflow:hidden}
.sidebar-collapsed .sidebar-avatar{width:36px!important;height:36px!important}
.sidebar-collapsed .sidebar-section-label{opacity:0;height:0;margin:0;padding:0;overflow:hidden}
.topbar-border{position:relative}
.topbar-border::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(58,205,126,0.3),transparent)}
.role-badge{font-size:9px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:2px 7px;border-radius:6px}
.role-admin{background:rgba(58,205,126,0.15);color:#cbcd3a}
.status-select{padding:5px 10px;border-radius:8px;font-size:11px;font-weight:600;border:1px solid #e5e7eb;outline:none;cursor:pointer;transition:all .2s;background:#f9fafb;color:#374151}
.dark .status-select{background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);color:rgba(255,255,255,0.8)}
.status-select:focus{border-color:#3acd7e;box-shadow:0 0 0 3px rgba(58,205,126,0.1)}
.toast-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,0.15);animation:toastIn .3s ease,toastOut .3s ease 2.7s forwards;display:flex;align-items:center;gap:8px}
.toast-success{background:#10b981;color:#fff}.toast-error{background:#ef4444;color:#fff}.toast-info{background:#3b82f6;color:#fff}
@keyframes toastIn{from{opacity:0;transform:translateX(40px) scale(.95)}to{opacity:1;transform:translateX(0) scale(1)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(40px)}}
.fade-up{opacity:0;transform:translateY(20px);animation:fadeUp .5s cubic-bezier(.4,0,.2,1) forwards}
@keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
.filter-tab{padding:7px 16px;border-radius:10px;font-size:12px;font-weight:600;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s;white-space:nowrap;cursor:pointer;user-select:none}
.dark .filter-tab{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6)}
.filter-tab:hover{background:#f9fafb}.dark .filter-tab:hover{background:rgba(255,255,255,0.06)}
.filter-tab.active{background:#16b364;color:#fff;border-color:#16b364}
.page-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s;cursor:pointer}
.dark .page-btn{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6)}
.page-btn:hover{background:#f3f4f6}.page-btn.active{background:#16b364;color:#fff;border-color:#16b364}
.dropdown-enter{animation:dropIn .2s cubic-bezier(.4,0,.2,1) forwards}
@keyframes dropIn{from{opacity:0;transform:translateY(-8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.proof-thumb{width:44px;height:44px;border-radius:10px;object-fit:cover;cursor:pointer;border:2px solid transparent;transition:all .2s}
.proof-thumb:hover{border-color:#3acd7e;transform:scale(1.08);box-shadow:0 4px 12px rgba(22,179,100,0.2)}
.modal-overlay{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal-box{max-width:90vw;max-height:90vh;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.4);animation:modalIn .25s cubic-bezier(.4,0,.2,1)}
@keyframes modalIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
.method-badge{font-size:10px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;padding:3px 10px;border-radius:8px;white-space:nowrap}
.method-jazzcash{background:rgba(239,68,68,0.1);color:#ef4444}.method-easypaisa{background:rgba(34,197,94,0.1);color:#22c55e}
.method-bank{background:rgba(59,130,246,0.1);color:#3b82f6}.method-cod{background:rgba(245,158,11,0.1);color:#f59e0b}
.method-default{background:rgba(107,114,128,0.1);color:#6b7280}
.action-btn{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;transition:all .15s;cursor:pointer;border:none;font-size:11px}
.action-btn:active{transform:scale(0.9)}
.confirm-modal-box{background:#fff;border-radius:20px;padding:28px;max-width:400px;width:90vw;box-shadow:0 25px 60px rgba(0,0,0,0.3);animation:modalIn .25s cubic-bezier(.4,0,.2,1)}
.dark .confirm-modal-box{background:#151d19}
@keyframes pulseGlow{0%,100%{box-shadow:0 0 0 0 rgba(22,179,100,0.3)}50%{box-shadow:0 0 0 6px rgba(22,179,100,0)}}
.pulse-dot{animation:pulseGlow 2s infinite}
tbody tr{transition:background .15s}
.profile-cover{background:linear-gradient(135deg,#084b2e 0%,#16b364 50%,#cbcd3a 100%);height:100px;position:relative;border-radius:20px 20px 0 0;overflow:hidden}
.profile-cover::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.profile-avatar-ring{width:88px;height:88px;border-radius:50%;padding:3px;background:linear-gradient(135deg,#16b364,#cbcd3a);position:absolute;bottom:-44px;left:32px;z-index:2}
.profile-avatar-ring img{width:100%;height:100%;border-radius:50%;object-fit:cover;border:3px solid #fff}
.dark .profile-avatar-ring img{border-color:#151d19}
.profile-stat-card{background:#f9fafb;border-radius:14px;padding:14px 18px;text-align:center;transition:all .2s}
.dark .profile-stat-card{background:rgba(255,255,255,0.03)}
.profile-stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.08)}
.dark .profile-stat-card:hover{box-shadow:0 6px 20px rgba(0,0,0,0.3)}
.info-row{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6}
.dark .info-row{border-bottom-color:rgba(255,255,255,0.04)}
.info-row:last-child{border-bottom:none}
.info-row .info-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px}
.edit-input{width:100%;padding:8px 12px;border-radius:10px;border:1px solid #e5e7eb;font-size:13px;font-family:inherit;outline:none;transition:all .2s;background:#f9fafb;color:#1f2937}
.dark .edit-input{background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);color:rgba(255,255,255,0.85)}
.edit-input:focus{border-color:#3acd7e;box-shadow:0 0 0 3px rgba(22,179,100,0.1)}
.db-dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.db-online{background:#10b981;box-shadow:0 0 6px rgba(16,185,129,0.5)}
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<div id="toastContainer" class="toast-container"></div>

<div id="imageModal" class="modal-overlay" onclick="closeImageModal(event)">
  <div class="modal-box"><img id="modalImage" src="" class="max-w-full max-h-[85vh] object-contain" alt="Proof"></div>
</div>

<div id="confirmModal" class="modal-overlay">
  <div class="confirm-modal-box" onclick="event.stopPropagation()">
    <div id="confirmIcon" class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center"></div>
    <h3 id="confirmTitle" class="text-lg font-bold text-center text-gray-900 dark:text-white mb-2"></h3>
    <p id="confirmDesc" class="text-sm text-center text-gray-400 mb-6"></p>
    <div class="flex gap-3">
      <button onclick="closeConfirmModal()" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-white/70 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-white/10 transition">Cancel</button>
      <button id="confirmAction" class="flex-1 py-2.5 rounded-xl text-white text-sm font-semibold transition">Confirm</button>
    </div>
  </div>
</div>

<div id="profileModal" class="modal-overlay">
  <div class="bg-white dark:bg-[#131a16] rounded-2xl w-[520px] max-w-[95vw] max-h-[92vh] overflow-y-auto shadow-2xl" onclick="event.stopPropagation()" style="animation:modalIn .25s cubic-bezier(.4,0,.2,1)">
    <div class="profile-cover"></div>
    <div class="relative px-8 pt-2">
      <div class="profile-avatar-ring"><img id="profileAvatar" src="" alt="Admin"></div>
      <button onclick="closeProfileModal()" class="absolute top-2 right-4 w-8 h-8 rounded-lg bg-white/20 hover:bg-white/30 dark:bg-white/10 dark:hover:bg-white/20 flex items-center justify-center text-white transition backdrop-blur-sm"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <div class="px-8 mt-12 pb-1">
      <div class="flex items-center gap-3 flex-wrap">
        <h2 id="profileName" class="text-xl font-extrabold text-gray-900 dark:text-white"></h2>
        <span class="role-badge role-admin" id="profileRoleBadge">Admin</span>
        <span class="flex items-center gap-1.5 text-[10px] font-bold text-emerald-500 ml-auto"><span class="db-dot db-online"></span>Online</span>
      </div>
      <p id="profileEmail" class="text-sm text-gray-500 dark:text-white/40 mt-0.5"></p>
    </div>
    <div class="grid grid-cols-3 gap-3 px-8 mt-5">
      <div class="profile-stat-card"><p id="profileStatPayments" class="text-lg font-extrabold text-gray-900 dark:text-white">0</p><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Payments</p></div>
      <div class="profile-stat-card"><p id="profileStatApproved" class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">0</p><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Approved</p></div>
      <div class="profile-stat-card"><p id="profileStatRevenue" class="text-lg font-extrabold text-gray-900 dark:text-white">0</p><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5">Revenue</p></div>
    </div>
    <div class="px-8 mt-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Profile Information</h3>
        <button id="profileEditBtn" onclick="toggleProfileEdit()" class="text-[11px] font-semibold text-brand-500 hover:text-brand-600 transition flex items-center gap-1"><i class="fa-solid fa-pen text-[9px]"></i> Edit</button>
      </div>
      <div id="profileViewMode">
        <div class="info-row"><div class="info-icon bg-brand-50 dark:bg-brand-900/10 text-brand-500"><i class="fa-solid fa-user"></i></div><div><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Full Name</p><p id="infoName" class="text-sm font-semibold text-gray-800 dark:text-white mt-0.5"></p></div></div>
        <div class="info-row"><div class="info-icon bg-blue-50 dark:bg-blue-900/10 text-blue-500"><i class="fa-solid fa-envelope"></i></div><div><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Email Address</p><p id="infoEmail" class="text-sm font-semibold text-gray-800 dark:text-white mt-0.5"></p></div></div>
        <div class="info-row"><div class="info-icon bg-purple-50 dark:bg-purple-900/10 text-purple-500"><i class="fa-solid fa-phone"></i></div><div><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Phone Number</p><p id="infoPhone" class="text-sm font-semibold text-gray-800 dark:text-white mt-0.5"></p></div></div>
        <div class="info-row"><div class="info-icon bg-amber-50 dark:bg-amber-900/10 text-amber-500"><i class="fa-solid fa-shield-halved"></i></div><div><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Role</p><p id="infoRole" class="text-sm font-semibold text-gray-800 dark:text-white mt-0.5"></p></div></div>
        <div class="info-row"><div class="info-icon bg-teal-50 dark:bg-teal-900/10 text-teal-500"><i class="fa-solid fa-calendar-plus"></i></div><div><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Joined</p><p id="infoJoined" class="text-sm font-semibold text-gray-800 dark:text-white mt-0.5"></p></div></div>
        <div class="info-row"><div class="info-icon bg-indigo-50 dark:bg-indigo-900/10 text-indigo-500"><i class="fa-solid fa-clock-rotate-left"></i></div><div><p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Last Login</p><p id="infoLastLogin" class="text-sm font-semibold text-gray-800 dark:text-white mt-0.5"></p></div></div>
      </div>
      <div id="profileEditMode" class="hidden space-y-3">
        <div><label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block mb-1.5">Full Name</label><input id="editName" type="text" class="edit-input"></div>
        <div><label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block mb-1.5">Email Address</label><input id="editEmail" type="email" class="edit-input"></div>
        <div><label class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block mb-1.5">Phone Number</label><input id="editPhone" type="text" class="edit-input"></div>
        <div class="flex gap-3 pt-2">
          <button onclick="saveProfile()" class="flex-1 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold transition"><i class="fa-solid fa-check mr-1.5"></i>Save Changes</button>
          <button onclick="toggleProfileEdit()" class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-white/70 text-sm font-semibold hover:bg-gray-200 dark:hover:bg-white/10 transition">Cancel</button>
        </div>
      </div>
    </div>
    <div class="mx-8 mt-6 mb-6 p-4 rounded-xl bg-gray-50 dark:bg-white/[0.02] border border-gray-100 dark:border-white/5">
      <div class="flex items-center gap-2 mb-2"><span class="db-dot db-online"></span><span class="text-[11px] font-bold text-gray-500 dark:text-white/50 uppercase tracking-wider">Database Connected</span></div>
      <p class="text-[11px] text-gray-400 dark:text-white/30">All data fetched from MySQL. Changes save in real-time.</p>
      <div class="flex gap-3 mt-3">
        <button onclick="exportData()" class="text-[11px] font-semibold text-blue-500 hover:text-blue-600 transition flex items-center gap-1"><i class="fa-solid fa-download text-[9px]"></i> Export JSON</button>
      </div>
    </div>
  </div>
</div>

<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm"><i class="fa-solid fa-bars text-xs"></i></button>
  </div>
  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10 cursor-pointer hover:bg-white/5 transition rounded-lg mx-2" onclick="openProfileModal()">
    <img id="sidebarAvatar" src="" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <div class="flex items-center gap-2"><p id="sidebarName" class="text-sm font-semibold leading-tight"></p><span class="role-badge role-admin">Admin</span></div>
      <p id="sidebarEmail" class="text-[11px] text-white/50 mt-0.5"></p>
    </div>
  </div>
  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text sidebar-section-label text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 mt-4 transition-all duration-300">Main</p>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <p class="sidebar-text sidebar-section-label text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 mt-5 transition-all duration-300">Manage</p>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text sidebar-section-label text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 mt-5 transition-all duration-300">Sales</p>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <a href="#" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span><span id="sidebarPendingBadge" class="ml-auto bg-amber-500 text-brand-950 text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300 pulse-dot"></span></a>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Database</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-300 rounded-full" style="width:100%"></div></div><p class="text-[11px] text-white/50 mt-1.5"><i class="fa-solid fa-database mr-1"></i>MySQL Connected</p></div></div>
</aside>

<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Payments</h1>
      <p id="topbarCount" class="text-xs text-gray-400 mt-0.5">0 total payments</p>
    </div>
    <div class="flex items-center gap-3">
      <div id="dbStatusTopbar" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/10">
        <span class="db-dot db-online"></span>
        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">DB Connected</span>
      </div>
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70"><i class="fa-solid fa-moon text-sm"></i></button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img id="topbarAvatar" src="" class="w-8 h-8 rounded-lg object-cover">
          <span id="topbarName" class="hidden sm:block text-sm font-semibold text-gray-900 dark:text-white"></span>
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl rounded-2xl w-52 py-2 dropdown-enter">
          <div class="px-4 py-2.5 border-b border-gray-100 dark:border-white/5"><p id="menuName" class="text-sm font-semibold text-gray-900 dark:text-white"></p><p id="menuEmail" class="text-[11px] text-gray-400 mt-0.5"></p></div>
          <a href="#" onclick="event.preventDefault();toggleMenu();openProfileModal()" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-user w-4 text-center text-xs"></i> My Profile</a>
          <a href="#" onclick="event.preventDefault();toggleMenu()" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-gear w-4 text-center text-xs"></i> Settings</a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1"><a href="#" onclick="event.preventDefault();handleLogout()" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition"><i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout</a></div>
        </div>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">
    <div class="fade-up grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 hover:shadow-md transition-shadow duration-300"><div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center"><i class="fa-solid fa-money-bill-wave text-gray-500 dark:text-white/40 text-sm"></i></div><span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total</span></div><p id="sumAll" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p><p id="countAll" class="text-[11px] text-gray-400 mt-0.5">0 payments</p></div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-amber-100 dark:border-amber-900/20 p-5 hover:shadow-md transition-shadow duration-300"><div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/10 flex items-center justify-center"><i class="fa-solid fa-clock text-amber-500 text-sm"></i></div><span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Pending</span></div><p id="sumPending" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p><p id="countPending" class="text-[11px] text-gray-400 mt-0.5">0 awaiting review</p></div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-emerald-100 dark:border-emerald-900/20 p-5 hover:shadow-md transition-shadow duration-300"><div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 flex items-center justify-center"><i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i></div><span class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Approved</span></div><p id="sumApproved" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p><p id="countApproved" class="text-[11px] text-gray-400 mt-0.5">0 verified</p></div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-red-100 dark:border-red-900/20 p-5 hover:shadow-md transition-shadow duration-300"><div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/10 flex items-center justify-center"><i class="fa-solid fa-circle-xmark text-red-500 text-sm"></i></div><span class="text-[10px] font-bold uppercase tracking-wider text-red-500">Rejected</span></div><p id="sumRejected" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p><p id="countRejected" class="text-[11px] text-gray-400 mt-0.5">0 declined</p></div>
    </div>
    <div class="fade-up flex gap-2 mb-5 overflow-x-auto pb-1" id="filterTabs">
      <button onclick="setFilter('')" class="filter-tab active" data-filter="">All (<span id="tabAll">0</span>)</button>
      <button onclick="setFilter('pending')" class="filter-tab" data-filter="pending">Pending (<span id="tabPending">0</span>)</button>
      <button onclick="setFilter('approved')" class="filter-tab" data-filter="approved">Approved (<span id="tabApproved">0</span>)</button>
      <button onclick="setFilter('rejected')" class="filter-tab" data-filter="rejected">Rejected (<span id="tabRejected">0</span>)</button>
    </div>
    <div class="fade-up mb-5">
      <div class="flex gap-3">
        <div class="flex-1 flex items-center bg-white dark:bg-[#131a16] rounded-xl px-4 py-2.5 gap-2 border border-gray-100 dark:border-white/5 focus-within:border-brand-400 focus-within:shadow-[0_0_0_3px_rgba(22,179,100,0.1)] transition-all">
          <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
          <input id="searchInput" type="text" placeholder="Search by transaction ID, sender name, phone..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400" oninput="handleSearch()">
        </div>
        <button id="clearBtn" onclick="clearSearch()" class="px-5 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-sm font-semibold transition hidden">Clear</button>
      </div>
    </div>
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50/80 dark:bg-white/[0.02]">
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Txn ID</th>
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">User</th>
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Sender</th>
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Amount</th>
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Method</th>
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Proof</th>
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Status</th>
              <th class="text-left text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Date</th>
              <th class="text-center text-[11px] uppercase tracking-wider text-gray-400 font-semibold px-5 py-3.5">Action</th>
            </tr>
          </thead>
          <tbody id="paymentsBody" class="divide-y divide-gray-50 dark:divide-white/[0.03]"></tbody>
        </table>
      </div>
      <div id="emptyState" class="text-center py-16 hidden">
        <i class="fa-solid fa-wallet text-4xl text-gray-200 dark:text-gray-700 mb-4"></i>
        <p class="text-sm text-gray-400 font-medium">No payments found</p>
        <p id="emptySearchHint" class="text-xs text-gray-300 mt-1 hidden"></p>
      </div>
      <div id="pagination" class="hidden items-center justify-between px-5 py-4 border-t border-gray-100 dark:border-white/5">
        <p id="pageInfo" class="text-xs text-gray-400"></p>
        <div id="pageButtons" class="flex gap-1.5"></div>
      </div>
    </div>
  </div>
</main>

<script>
let payments=<?= $paymentsJSON ?>;
let admin=<?= $adminJSON ?>;
let currentFilter='',currentSearch='',currentPage=1;
const perPage=10;

function fmt(n){return new Intl.NumberFormat('en-PK').format(n)}
function fmtDate(d){return new Date(d).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}
function fmtTime(d){return new Date(d).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true})}
function fmtDateTime(d){return fmtDate(d)+' '+fmtTime(d)}
function fmtFullDate(d){return new Date(d).toLocaleDateString('en-US',{weekday:'long',day:'numeric',month:'long',year:'numeric'})}
function getMethodClass(m){const ml=m.toLowerCase();if(ml.includes('jazz'))return'method-jazzcash';if(ml.includes('easy'))return'method-easypaisa';if(ml.includes('bank'))return'method-bank';if(ml.includes('cod')||ml.includes('cash'))return'method-cod';return'method-default'}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML}
function ucFirst(s){return s.charAt(0).toUpperCase()+s.slice(1)}

function showToast(t,m){const c=document.getElementById('toastContainer'),e=document.createElement('div');e.className=`toast toast-${t}`;const i={success:'check-circle',error:'exclamation-circle',info:'info-circle'};e.innerHTML=`<i class="fa-solid fa-${i[t]||'info-circle'}"></i>${esc(m)}`;c.appendChild(e);setTimeout(()=>e.remove(),3200)}

function updateAdminUI(){document.getElementById('sidebarAvatar').src=admin.avatar;document.getElementById('sidebarName').textContent=admin.name;document.getElementById('sidebarEmail').textContent=admin.email;document.getElementById('topbarAvatar').src=admin.avatar;document.getElementById('topbarName').textContent=admin.name;document.getElementById('menuName').textContent=admin.name;document.getElementById('menuEmail').textContent=admin.email}

function getFilteredPayments(){return payments.filter(p=>{if(currentFilter&&p.status!==currentFilter)return false;if(currentSearch){const s=currentSearch.toLowerCase();return p.transaction_id.toLowerCase().includes(s)||p.sender_name.toLowerCase().includes(s)||p.sender_number.includes(s)||(p.user_email||'').toLowerCase().includes(s)||(p.user_name||'').toLowerCase().includes(s)}return true})}
function getCounts(){return{all:payments.length,pending:payments.filter(x=>x.status==='pending').length,approved:payments.filter(x=>x.status==='approved').length,rejected:payments.filter(x=>x.status==='rejected').length}}
function getSums(){const s=a=>a.reduce((b,x)=>b+Number(x.amount),0);return{all:s(payments),pending:s(payments.filter(x=>x.status==='pending')),approved:s(payments.filter(x=>x.status==='approved')),rejected:s(payments.filter(x=>x.status==='rejected'))}}

async function ajaxPost(p){const b=new URLSearchParams();for(const k in p)b.append(k,p[k]);const r=await fetch(window.location.href,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:b.toString()});return r.json()}

function render(){
  const co=getCounts(),su=getSums(),fi=getFilteredPayments(),to=fi.length,tp=Math.max(1,Math.ceil(to/perPage));
  if(currentPage>tp)currentPage=tp;
  const st=(currentPage-1)*perPage,pd=fi.slice(st,st+perPage);
  document.getElementById('sumAll').textContent='Rs. '+fmt(su.all);document.getElementById('countAll').textContent=co.all+' payments';
  document.getElementById('sumPending').textContent='Rs. '+fmt(su.pending);document.getElementById('countPending').textContent=co.pending+' awaiting review';
  document.getElementById('sumApproved').textContent='Rs. '+fmt(su.approved);document.getElementById('countApproved').textContent=co.approved+' verified';
  document.getElementById('sumRejected').textContent='Rs. '+fmt(su.rejected);document.getElementById('countRejected').textContent=co.rejected+' declined';
  document.getElementById('topbarCount').textContent=to+' total payments';
  document.getElementById('tabAll').textContent=co.all;document.getElementById('tabPending').textContent=co.pending;
  document.getElementById('tabApproved').textContent=co.approved;document.getElementById('tabRejected').textContent=co.rejected;
  document.querySelectorAll('#filterTabs .filter-tab').forEach(b=>b.classList.toggle('active',b.dataset.filter===currentFilter));
  const bd=document.getElementById('sidebarPendingBadge');if(co.pending>0){bd.textContent=co.pending;bd.style.display=''}else{bd.style.display='none'}
  document.getElementById('clearBtn').classList.toggle('hidden',!currentSearch);
  const tb=document.getElementById('paymentsBody'),em=document.getElementById('emptyState'),eh=document.getElementById('emptySearchHint');
  if(to===0){tb.innerHTML='';em.classList.remove('hidden');if(currentSearch){eh.classList.remove('hidden');eh.textContent='No results for "'+esc(currentSearch)+'"'}else{eh.classList.add('hidden')}}
  else{em.classList.add('hidden');tb.innerHTML=pd.map(p=>{
    const iP=p.status==='pending',iA=p.status==='approved',iR=p.status==='rejected',uD=p.updated_at!==p.created_at;
    const pi=p.proof_image?esc(p.proof_image):'';
    const pH=pi?`<img src="${pi}" class="proof-thumb" onclick="openImageModal('${pi}')" alt="Proof" onerror="this.outerHTML='<div class=\\'w-11 h-11 rounded-[10px] bg-gray-100 dark:bg-white/5 flex items-center justify-center text-gray-300 dark:text-gray-600\\'><i class=\\'fa-solid fa-image text-xs\\'></i></div>'">`:`<div class="w-11 h-11 rounded-[10px] bg-gray-100 dark:bg-white/5 flex items-center justify-center text-gray-300 dark:text-gray-600"><i class="fa-solid fa-image text-xs"></i></div>`;
    const nH=p.notes?`<p class="text-[10px] text-gray-400 mt-0.5 italic max-w-[140px] truncate" title="${esc(p.notes)}"><i class="fa-solid fa-note-sticky text-[8px] mr-1"></i>${esc(p.notes)}</p>`:'';
    const uH=uD?`<p class="text-[9px] text-gray-300 dark:text-gray-600 mt-0.5">Updated: ${fmtDateTime(p.updated_at)}</p>`:'';
    let aH;if(iP)aH=`<button onclick="confirmAction(${p.id},'approved')" class="action-btn bg-emerald-50 dark:bg-emerald-900/10 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400" title="Approve"><i class="fa-solid fa-check"></i></button><button onclick="confirmAction(${p.id},'rejected')" class="action-btn bg-red-50 dark:bg-red-900/10 hover:bg-red-100 dark:hover:bg-red-900/20 text-red-500 dark:text-red-400" title="Reject"><i class="fa-solid fa-xmark"></i></button>`;
    else if(iA)aH=`<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold"><i class="fa-solid fa-check-circle text-[10px]"></i> Verified</span>`;
    else aH=`<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 dark:bg-red-900/10 text-red-500 dark:text-red-400 text-[10px] font-bold"><i class="fa-solid fa-times-circle text-[10px]"></i> Rejected</span>`;
    return`<tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition ${iP?'bg-amber-50/30 dark:bg-amber-900/5':''}">
      <td class="px-5 py-3.5"><span class="font-bold text-gray-900 dark:text-white text-xs font-mono">${esc(p.transaction_id)}</span></td>
      <td class="px-5 py-3.5"><p class="text-xs font-semibold text-gray-800 dark:text-white">${esc(p.user_name||'N/A')}</p><p class="text-[10px] text-gray-400">${esc(p.user_email||'')}</p></td>
      <td class="px-5 py-3.5"><p class="text-xs font-semibold text-gray-800 dark:text-white">${esc(p.sender_name)}</p><p class="text-[10px] text-gray-400">${esc(p.sender_number)}</p>${nH}</td>
      <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white text-xs">Rs. ${fmt(p.amount)}</td>
      <td class="px-5 py-3.5"><span class="method-badge ${getMethodClass(p.method)}">${esc(p.method)}</span></td>
      <td class="px-5 py-3.5">${pH}</td>
      <td class="px-5 py-3.5"><select onchange="handleStatusChange(${p.id},this.value)" class="status-select"><option value="pending" ${iP?'selected':''}>Pending</option><option value="approved" ${iA?'selected':''}>Approved</option><option value="rejected" ${iR?'selected':''}>Rejected</option></select></td>
      <td class="px-5 py-3.5"><p class="text-[11px] text-gray-500">${fmtDate(p.created_at)}</p><p class="text-[10px] text-gray-400">${fmtTime(p.created_at)}</p>${uH}</td>
      <td class="px-5 py-3.5 text-center"><div class="flex items-center justify-center gap-1.5">${aH}</div></td>
    </tr>`}).join('')}
  const pg=document.getElementById('pagination');
  if(tp>1){pg.classList.remove('hidden');pg.classList.add('flex');document.getElementById('pageInfo').textContent=`Showing ${st+1}\u2013${Math.min(st+perPage,to)} of ${to}`;let b='';if(currentPage>1)b+=`<button onclick="goPage(${currentPage-1})" class="page-btn"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>`;const rs=Math.max(1,currentPage-2),re=Math.min(tp,currentPage+2);if(rs>1){b+=`<button onclick="goPage(1)" class="page-btn">1</button>`;if(rs>2)b+=`<span class="w-9 h-9 flex items-center justify-center text-gray-300 text-xs">...</span>`}for(let i=rs;i<=re;i++)b+=`<button onclick="goPage(${i})" class="page-btn ${i===currentPage?'active':''}">${i}</button>`;if(re<tp){if(re<tp-1)b+=`<span class="w-9 h-9 flex items-center justify-center text-gray-300 text-xs">...</span>`;b+=`<button onclick="goPage(${tp})" class="page-btn">${tp}</button>`}if(currentPage<tp)b+=`<button onclick="goPage(${currentPage+1})" class="page-btn"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>`;document.getElementById('pageButtons').innerHTML=b}else{pg.classList.add('hidden');pg.classList.remove('flex')}
}

function setFilter(f){currentFilter=f;currentPage=1;render()}
function handleSearch(){currentSearch=document.getElementById('searchInput').value.trim();currentPage=1;render()}
function clearSearch(){document.getElementById('searchInput').value='';currentSearch='';currentPage=1;render()}
function goPage(p){currentPage=p;render();document.querySelector('table').scrollIntoView({behavior:'smooth',block:'start'})}

async function handleStatusChange(id,ns){try{const r=await ajaxPost({action:'update_status',id,status:ns});if(r.success){const p=payments.find(x=>x.id==id);if(p){p.status=ns;p.updated_at=new Date().toISOString().slice(0,19).replace('T',' ')}showToast('success','Payment '+ucFirst(ns)+' successfully');render()}else showToast('error',r.message||'Failed')}catch(e){showToast('error','Network error')}}

let pendingAction=null;
function confirmAction(id,ns){const p=payments.find(x=>x.id==id);if(!p)return;pendingAction={id,ns};const ic=document.getElementById('confirmIcon'),ti=document.getElementById('confirmTitle'),de=document.getElementById('confirmDesc'),bt=document.getElementById('confirmAction');if(ns==='approved'){ic.className='w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/10';ic.innerHTML='<i class="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>';ti.textContent='Approve Payment';de.textContent='Approve Rs. '+fmt(p.amount)+' from '+p.sender_name+'?';bt.className='flex-1 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold transition';bt.textContent='Approve'}else{ic.className='w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-red-50 dark:bg-red-900/10';ic.innerHTML='<i class="fa-solid fa-circle-xmark text-red-500 text-2xl"></i>';ti.textContent='Reject Payment';de.textContent='Reject Rs. '+fmt(p.amount)+' from '+p.sender_name+'?';bt.className='flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition';bt.textContent='Reject'}bt.onclick=executeConfirmedAction;document.getElementById('confirmModal').classList.add('show');document.body.style.overflow='hidden'}
async function executeConfirmedAction(){if(!pendingAction)return;try{const r=await ajaxPost({action:'update_status',id:pendingAction.id,status:pendingAction.ns});if(r.success){const p=payments.find(x=>x.id==pendingAction.id);if(p){p.status=pendingAction.ns;p.updated_at=new Date().toISOString().slice(0,19).replace('T',' ')}showToast('success','Payment '+ucFirst(pendingAction.ns)+' successfully')}else showToast('error',r.message||'Failed')}catch(e){showToast('error','Network error')}pendingAction=null;closeConfirmModal();render()}
function closeConfirmModal(){document.getElementById('confirmModal').classList.remove('show');document.body.style.overflow='';pendingAction=null}

function openImageModal(s){document.getElementById('modalImage').src=s;document.getElementById('imageModal').classList.add('show');document.body.style.overflow='hidden'}
function closeImageModal(e){if(e.target===document.getElementById('imageModal')){document.getElementById('imageModal').classList.remove('show');document.body.style.overflow=''}}

function openProfileModal(){const c=getCounts(),s=getSums();document.getElementById('profileAvatar').src=admin.avatar;document.getElementById('profileName').textContent=admin.name;document.getElementById('profileEmail').textContent=admin.email;document.getElementById('profileRoleBadge').textContent=admin.role||'Admin';document.getElementById('infoName').textContent=admin.name;document.getElementById('infoEmail').textContent=admin.email;document.getElementById('infoPhone').textContent=admin.phone||'N/A';document.getElementById('infoRole').textContent=admin.role||'Administrator';document.getElementById('infoJoined').textContent=fmtFullDate(admin.joined_at);document.getElementById('infoLastLogin').textContent=fmtDateTime(admin.last_login);document.getElementById('profileStatPayments').textContent=c.all;document.getElementById('profileStatApproved').textContent=c.approved;document.getElementById('profileStatRevenue').textContent='Rs. '+fmt(s.approved);document.getElementById('profileViewMode').classList.remove('hidden');document.getElementById('profileEditMode').classList.add('hidden');document.getElementById('profileEditBtn').classList.remove('hidden');document.getElementById('profileModal').classList.add('show');document.body.style.overflow='hidden'}
function closeProfileModal(){document.getElementById('profileModal').classList.remove('show');document.body.style.overflow=''}

let profileEditing=false;
function toggleProfileEdit(){profileEditing=!profileEditing;if(profileEditing){document.getElementById('editName').value=admin.name;document.getElementById('editEmail').value=admin.email;document.getElementById('editPhone').value=admin.phone||'';document.getElementById('profileViewMode').classList.add('hidden');document.getElementById('profileEditMode').classList.remove('hidden');document.getElementById('profileEditBtn').classList.add('hidden')}else{document.getElementById('profileViewMode').classList.remove('hidden');document.getElementById('profileEditMode').classList.add('hidden');document.getElementById('profileEditBtn').classList.remove('hidden')}}

async function saveProfile(){const n=document.getElementById('editName').value.trim(),e=document.getElementById('editEmail').value.trim(),p=document.getElementById('editPhone').value.trim();if(!n){showToast('error','Name is required');return}if(!e||!e.includes('@')){showToast('error','Valid email is required');return}try{const r=await ajaxPost({action:'update_profile',name:n,email:e,phone:p});if(r.success){admin=r.admin;updateAdminUI();profileEditing=false;document.getElementById('profileViewMode').classList.remove('hidden');document.getElementById('profileEditMode').classList.add('hidden');document.getElementById('profileEditBtn').classList.remove('hidden');document.getElementById('profileName').textContent=admin.name;document.getElementById('profileEmail').textContent=admin.email;document.getElementById('infoName').textContent=admin.name;document.getElementById('infoEmail').textContent=admin.email;document.getElementById('infoPhone').textContent=admin.phone;showToast('success','Profile updated successfully')}else showToast('error',r.message||'Failed')}catch(er){showToast('error','Network error')}}

function exportData(){const d={payments,admin,exported_at:new Date().toISOString()};const b=new Blob([JSON.stringify(d,null,2)],{type:'application/json'});const u=URL.createObjectURL(b);const a=document.createElement('a');a.href=u;a.download='payments_backup_'+new Date().toISOString().slice(0,10)+'.json';a.click();URL.revokeObjectURL(u);showToast('success','Data exported as JSON')}

async function handleLogout(){try{const r=await ajaxPost({action:'logout'});if(r.redirect)window.location.href=r.redirect}catch(e){window.location.href='../login.php'}toggleMenu()}

function toggleSidebar(){const s=document.getElementById('sidebar'),m=document.getElementById('main'),c=s.classList.toggle('sidebar-collapsed');s.style.width=c?'78px':'260px';m.style.marginLeft=c?'78px':'260px'}
function toggleDark(){const h=document.documentElement,b=document.body,t=document.getElementById('darkBtn'),d=b.classList.toggle('dark');h.classList.toggle('dark',d);t.innerHTML=d?'<i class="fa-solid fa-sun text-sm"></i>':'<i class="fa-solid fa-moon text-sm"></i>';localStorage.setItem('darkMode',d?'1':'0')}
(function(){const s=localStorage.getItem('darkMode'),p=window.matchMedia('(prefers-color-scheme:dark)').matches;if(s==='1'||(!s&&p)){document.body.classList.add('dark');document.documentElement.classList.add('dark');document.getElementById('darkBtn').innerHTML='<i class="fa-solid fa-sun text-sm"></i>'}})();
function toggleMenu(){document.getElementById('menu').classList.toggle('hidden')}
document.addEventListener('click',function(e){const m=document.getElementById('menu');if(!e.target.closest('.relative')&&!m.classList.contains('hidden'))m.classList.add('hidden')});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){document.getElementById('imageModal').classList.remove('show');closeConfirmModal();closeProfileModal();document.body.style.overflow=''}});
document.getElementById('confirmModal').addEventListener('click',function(e){if(e.target===this)closeConfirmModal()});
document.getElementById('profileModal').addEventListener('click',function(e){if(e.target===this)closeProfileModal()});

updateAdminUI();render();
setTimeout(()=>{const el=document.getElementById('dbStatusTopbar');el.style.transition='opacity .5s';el.style.opacity='0';setTimeout(()=>{el.style.display='none'},500)},3000);
</script>
</body>
</html>