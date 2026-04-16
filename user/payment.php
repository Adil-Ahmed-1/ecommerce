<?php
session_start();
include("../backend/config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_role'] === 'admin') {
    header("Location: ../admin.php");
    exit;
}

 $uid = $_SESSION['user_id'];
 $user_name = $_SESSION['user_name'] ?? 'User';

 $success = "";
 $error = "";
 $post_method = "";
 $post_amount = "";
 $post_txn = "";
 $post_number = "";
 $post_sender = "";
 $post_notes = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_amount  = trim($_POST['amount']);
    $post_method  = trim($_POST['method']);
    $post_txn     = trim($_POST['transaction_id']);
    $post_number  = trim($_POST['sender_number']);
    $post_sender  = trim($_POST['sender_name']);
    $post_notes   = trim($_POST['notes']);

    if (empty($post_amount) || empty($post_method) || empty($post_txn) || empty($post_number)) {
        $error = "Amount, Method, Transaction ID and Sender Number are required.";
    } elseif (!is_numeric($post_amount) || $post_amount <= 0) {
        $error = "Enter a valid amount.";
    } elseif (strlen($post_txn) < 6) {
        $error = "Transaction ID must be at least 6 characters.";
    } else {
        // Handle proof image
        $proof_name = null;
        if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $file = $_FILES['proof'];
            
            if ($file['size'] > 5 * 1024 * 1024) {
                $error = "Proof image must be under 5 MB.";
            } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), $allowed)) {
                $error = "Only JPG, PNG, GIF, WEBP images allowed.";
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $proof_name = 'pay_' . $uid . '_' . time() . '.' . $ext;
                $upload_dir = '../uploads/payments/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                move_uploaded_file($file['tmp_name'], $upload_dir . $proof_name);
            }
        }

        if (empty($error)) {
            $ins = $conn->prepare("INSERT INTO payments (user_id, amount, method, transaction_id, sender_number, sender_name, notes, proof_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("sdssssss", $uid, $post_amount, $post_method, $post_txn, $post_number, $post_sender, $post_notes, $proof_name);
            
            if ($ins->execute()) {
                $success = "Payment submitted successfully! Admin will verify it shortly.";
                $post_amount = $post_method = $post_txn = $post_number = $post_sender = $post_notes = "";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

// Fetch user's payment history
 $payments = [];
 $pStmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY id DESC");
 $pStmt->bind_param("i", $uid);
 $pStmt->execute();
 $pResult = $pStmt->get_result();
while ($p = $pResult->fetch_assoc()) {
    $payments[] = $p;
}

// Stats
 $total_paid = 0;
 $pending_count = 0;
 $approved_count = 0;
 $rejected_count = 0;

foreach ($payments as $pay) {
    $st = strtolower($pay['status']);
    if ($st === 'approved') {
        $total_paid += $pay['amount'];
        $approved_count++;
    }
    if ($st === 'pending') $pending_count++;
    if ($st === 'rejected') $rejected_count++;
}

 $user_image = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=16b364&color=fff&bold=true&size=128';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family+Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

  .sidebar-glass { background:rgba(8,75,46,0.95); backdrop-filter:blur(20px); }
  .dark .sidebar-glass { background:rgba(3,42,26,0.98); }

  .nav-link { position:relative; transition:all 0.25s ease; }
  .nav-link::before {
    content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
    width:3px;height:0;border-radius:0 4px 4px 0;
    background:#3acd7e;transition:height 0.25s ease;
  }
  .nav-link:hover::before,.nav-link.active::before { height:60%; }
  .nav-link.active { background:rgba(58,205,126,0.12); color:#3acd7e; }
  .nav-link:hover { background:rgba(255,255,255,0.06); }

  .topbar-line::after {
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(58,205,126,0.25),transparent);
  }
  .form-input { transition:border-color 0.2s ease, box-shadow 0.2s ease; }
  .form-input:focus { border-color:#3acd7e; box-shadow:0 0 0 3px rgba(58,205,126,0.12); outline:none; }

  .btn-brand {
    background:linear-gradient(135deg,#16b364,#0a9150);
    transition:all 0.25s ease; position:relative; overflow:hidden;
  }
  .btn-brand:hover { transform:translateY(-1px); box-shadow:0 8px 24px -6px rgba(22,179,100,0.45); }

  .stat-card { transition:all 0.3s ease; }
  .stat-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px -8px rgba(0,0,0,0.12); }

  .fade-up { opacity:0;transform:translateY(20px); animation:fadeUp 0.5s ease forwards; }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  .sidebar-collapsed .sidebar-text { opacity:0; width:0; overflow:hidden; }
  .sidebar-collapsed .sidebar-logo-text { opacity:0; width:0; overflow:hidden; }

  .pay-row { transition:all 0.2s ease; }
  .pay-row:hover { background:rgba(58,205,126,0.04); }

  .status-badge {
    font-size:10px;font-weight:700;letter-spacing:0.03em;
    text-transform:uppercase;padding:3px 8px;border-radius:6px;
  }
  .status-pending { background:rgba(245,158,11,0.15); color:#f59e0b; }
  .status-approved { background:rgba(22,179,100,0.15); color:#16b364; }
  .status-rejected { background:rgba(239,68,68,0.15); color:#ef4444; }

  .method-badge {
    font-size:10px;font-weight:700;letter-spacing:0.03em;
    text-transform:uppercase;padding:3px 8px;border-radius:6px;
  }
  .method-jazzcash { background:rgba(239,68,68,0.1); color:#dc2626; }
  .method-easypaisa { background:rgba(34,197,94,0.1); color:#16a34a; }
  .method-bank_transfer { background:rgba(59,130,246,0.1); color:#2563eb; }
  .method-cod { background:rgba(168,85,247,0.1); color:#9333ea; }

  .proof-upload {
    border:2px dashed rgba(0,0,0,0.1);
    border-radius:16px;
    transition:all 0.3s ease;
    cursor:pointer;
  }
  .dark .proof-upload { border-color:rgba(255,255,255,0.08); }
  .proof-upload:hover { border-color:#3acd7e; background:rgba(58,205,126,0.04); }
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">
        <i class="fa-solid fa-store text-xs"></i>
      </div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">Commerce</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm">
      <i class="fa-solid fa-bars text-xs"></i>
    </button>
  </div>

  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="<?= $user_image ?>" class="w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <p class="text-sm font-semibold leading-tight"><?= htmlspecialchars($user_name) ?></p>
      <p class="text-[11px] text-white/50 mt-0.5">Customer</p>
    </div>
  </div>

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="user_dashboard.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-house w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>
    <a href="payment.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Payments</span>
    </a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-user-gear w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">My Profile</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Shop</p>
    <a href="../frontend/" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Browse Products</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Account</p>
    <a href="logout.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10">
      <i class="fa-solid fa-right-from-bracket w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Logout</span>
    </a>
  </nav>
</aside>

<!-- MAIN -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">

  <header class="topbar-line sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Payments</h1>
      <p class="text-xs text-gray-400 mt-0.5">Send payment & track transactions</p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>
      <div class="flex items-center gap-2.5 pl-3 pr-4 py-1.5 rounded-xl bg-gray-50 dark:bg-white/5">
        <img src="<?= $user_image ?>" class="w-8 h-8 rounded-lg object-cover">
        <span class="text-sm font-semibold text-gray-700 dark:text-white hidden sm:block"><?= htmlspecialchars($user_name) ?></span>
      </div>
    </div>
  </header>

  <div class="px-8 py-6">

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
            <i class="fa-solid fa-money-bill-wave text-brand-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-brand-500 bg-brand-50 dark:bg-brand-950 px-2 py-1 rounded-md">Paid</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white">Rs. <?= number_format($total_paid) ?></p>
        <p class="text-xs text-gray-400 mt-1">Total Approved</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.05s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-yellow-50 dark:bg-yellow-950 flex items-center justify-center">
            <i class="fa-solid fa-clock text-yellow-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-yellow-500 bg-yellow-50 dark:bg-yellow-950 px-2 py-1 rounded-md">Pending</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $pending_count ?></p>
        <p class="text-xs text-gray-400 mt-1">Awaiting Verification</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.1s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-green-50 dark:bg-green-950 flex items-center justify-center">
            <i class="fa-solid fa-circle-check text-green-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-green-500 bg-green-50 dark:bg-green-950 px-2 py-1 rounded-md">Approved</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $approved_count ?></p>
        <p class="text-xs text-gray-400 mt-1">Successful Payments</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.15s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-red-50 dark:bg-red-950 flex items-center justify-center">
            <i class="fa-solid fa-circle-xmark text-red-500"></i>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-red-500 bg-red-50 dark:bg-red-950 px-2 py-1 rounded-md">Rejected</span>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $rejected_count ?></p>
        <p class="text-xs text-gray-400 mt-1">Failed Payments</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- PAYMENT FORM -->
      <div class="lg:col-span-1 fade-up" style="animation-delay:0.2s">
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
              <i class="fa-solid fa-paper-plane text-brand-500 text-sm"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Send Payment</h3>
              <p class="text-[11px] text-gray-400">Submit payment proof</p>
            </div>
          </div>

          <?php if (!empty($success)) { ?>
          <div class="px-6 pt-4">
            <div class="flex items-center gap-3 bg-brand-50 dark:bg-brand-950 border border-brand-200 dark:border-brand-800 text-brand-700 dark:text-brand-300 rounded-xl px-4 py-3">
              <i class="fa-solid fa-circle-check text-brand-500"></i>
              <p class="text-sm font-medium"><?= $success ?></p>
            </div>
          </div>
          <?php } ?>

          <?php if (!empty($error)) { ?>
          <div class="px-6 pt-4">
            <div class="flex items-center gap-3 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-300 rounded-xl px-4 py-3">
              <i class="fa-solid fa-circle-exclamation text-red-500"></i>
              <p class="text-sm font-medium"><?= $error ?></p>
            </div>
          </div>
          <?php } ?>

          <form method="POST" action="" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Amount (Rs.)</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">Rs.</span>
                <input type="number" name="amount" step="0.01" min="1" required
                  class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-12 pr-4 text-sm text-gray-800 dark:text-white"
                  placeholder="0.00" value="<?= htmlspecialchars($post_amount) ?>">
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Payment Method</label>
              <select name="method" required
                class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 px-4 text-sm text-gray-800 dark:text-white appearance-none cursor-pointer">
                <option value="" disabled selected>Select method</option>
                <option value="jazzcash" <?= $post_method === 'jazzcash' ? 'selected' : '' ?>>JazzCash</option>
                <option value="easypaisa" <?= $post_method === 'easypaisa' ? 'selected' : '' ?>>EasyPaisa</option>
                <option value="bank_transfer" <?= $post_method === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                <option value="cod" <?= $post_method === 'cod' ? 'selected' : '' ?>>Cash on Delivery</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Transaction ID</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-hashtag text-xs"></i></span>
                <input type="text" name="transaction_id" required
                  class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white"
                  placeholder="e.g. TRX123456789" value="<?= htmlspecialchars($post_txn) ?>">
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Sender Number</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><i class="fa-solid fa-phone text-xs"></i></span>
                <input type="text" name="sender_number" required
                  class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 pl-10 pr-4 text-sm text-gray-800 dark:text-white"
                  placeholder="03XX-XXXXXXX" value="<?= htmlspecialchars($post_number) ?>">
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Sender Name <span class="text-gray-300 normal-case">(Optional)</span></label>
              <input type="text" name="sender_name"
                class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 px-4 text-sm text-gray-800 dark:text-white"
                placeholder="Name on account" value="<?= htmlspecialchars($post_sender) ?>">
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Notes <span class="text-gray-300 normal-case">(Optional)</span></label>
              <textarea name="notes" rows="2"
                class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 px-4 text-sm text-gray-800 dark:text-white resize-none"
                placeholder="Any additional info..."><?= htmlspecialchars($post_notes) ?></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Payment Proof <span class="text-gray-300 normal-case">(Optional)</span></label>
              <div class="proof-upload p-4 text-center" onclick="document.getElementById('proofInput').click()">
                <input type="file" id="proofInput" name="proof" accept="image/*" class="hidden" onchange="previewProof(this)">
                <img id="proofPreview" src="" class="hidden max-h-32 mx-auto rounded-lg mb-2">
                <div id="proofPlaceholder">
                  <i class="fa-solid fa-cloud-arrow-up text-gray-300 text-2xl"></i>
                  <p class="text-xs text-gray-400 mt-2">Click to upload screenshot</p>
                  <p class="text-[10px] text-gray-300 mt-1">JPG, PNG — Max 5 MB</p>
                </div>
              </div>
            </div>

            <button type="submit" class="btn-brand w-full text-white font-bold text-sm py-3.5 rounded-xl flex items-center justify-center gap-2">
              <i class="fa-solid fa-paper-plane text-xs"></i>
              Submit Payment
            </button>
          </form>
        </div>
      </div>

      <!-- PAYMENT HISTORY -->
      <div class="lg:col-span-2 fade-up" style="animation-delay:0.25s">
        <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
                <i class="fa-solid fa-clock-rotate-left text-brand-500 text-sm"></i>
              </div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Transaction History</h3>
            </div>
            <span class="text-xs text-gray-400"><?= count($payments) ?> transactions</span>
          </div>

          <div class="divide-y divide-gray-50 dark:divide-white/5">
            <?php if (empty($payments)) { ?>
            <div class="px-6 py-16 text-center">
              <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-receipt text-gray-300 text-2xl"></i>
              </div>
              <p class="text-sm font-semibold text-gray-400">No transactions yet</p>
              <p class="text-xs text-gray-300 mt-1">Your payment history will appear here</p>
            </div>
            <?php } else { ?>
              <?php foreach ($payments as $pay) {
                $st = strtolower($pay['status']);
                $statusClass = 'status-pending';
                if ($st === 'approved') $statusClass = 'status-approved';
                if ($st === 'rejected') $statusClass = 'status-rejected';

                $mt = strtolower($pay['method']);
                $methodClass = 'method-cod';
                if ($mt === 'jazzcash') $methodClass = 'method-jazzcash';
                if ($mt === 'easypaisa') $methodClass = 'method-easypaisa';
                if ($mt === 'bank_transfer') $methodClass = 'method-bank_transfer';

                $methodIcons = [
                  'jazzcash' => 'fa-mobile-screen',
                  'easypaisa' => 'fa-mobile-screen',
                  'bank_transfer' => 'fa-building-columns',
                  'cod' => 'fa-truck'
                ];
                $mIcon = $methodIcons[$mt] ?? 'fa-money-bill';
              ?>
              <div class="pay-row px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center shrink-0">
                      <i class="fa-solid <?= $mIcon ?> text-gray-400 text-sm"></i>
                    </div>
                    <div>
                      <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-bold text-gray-800 dark:text-white">Rs. <?= number_format($pay['amount']) ?></p>
                        <span class="method-badge <?= $methodClass ?>"><?= str_replace('_', ' ', $mt) ?></span>
                      </div>
                      <p class="text-[11px] text-gray-400 mt-1">
                        <i class="fa-solid fa-hashtag text-[9px] mr-1"></i><?= htmlspecialchars($pay['transaction_id']) ?>
                        <span class="mx-1">•</span>
                        <?= date('d M, Y h:i A', strtotime($pay['created_at'])) ?>
                      </p>
                      <?php if ($pay['proof_image']) { ?>
                      <a href="../uploads/payments/<?= $pay['proof_image'] ?>" target="_blank" class="text-[11px] text-brand-500 hover:text-brand-600 transition mt-1 inline-flex items-center gap-1">
                        <i class="fa-solid fa-image text-[9px]"></i> View Proof
                      </a>
                      <?php } ?>
                    </div>
                  </div>
                  <div class="text-right shrink-0 ml-4">
                    <span class="status-badge <?= $statusClass ?>"><?= ucfirst($st) ?></span>
                    <?php if ($st === 'rejected' && $pay['notes']) { ?>
                    <p class="text-[10px] text-red-400 mt-1 max-w-[150px] truncate" title="<?= htmlspecialchars($pay['notes']) ?>">
                      <i class="fa-solid fa-comment text-[8px] mr-1"></i><?= htmlspecialchars($pay['notes']) ?>
                    </p>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <?php } ?>
            <?php } ?>
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
function previewProof(input) {
  var file = input.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('proofPreview').src = e.target.result;
    document.getElementById('proofPreview').classList.remove('hidden');
    document.getElementById('proofPlaceholder').classList.add('hidden');
  };
  reader.readAsDataURL(file);
}
</script>

</body>
</html>