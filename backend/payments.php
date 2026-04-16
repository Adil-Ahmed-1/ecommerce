<?php
session_start();
include("../backend/config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle approve/reject
if (isset($_POST['action']) && isset($_POST['pay_id'])) {
    $pay_id = intval($_POST['pay_id']);
    $action = $_POST['action'];
    $admin_note = trim($_POST['admin_note'] ?? '');

    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    $upd = $conn->prepare("UPDATE payments SET status = ?, notes = ? WHERE id = ?");
    $upd->bind_param("ssi", $new_status, $admin_note, $pay_id);
    $upd->execute();

    header("Location: payments.php?msg=" . $new_status);
    exit;
}

 $msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'approved') $msg = "Payment approved successfully.";
    if ($_GET['msg'] === 'rejected') $msg = "Payment rejected.";
}

// Fetch all payments with user info
 $all_payments = [];
 $stmt = $conn->query("
    SELECT p.*, u.name as user_name, u.email as user_email 
    FROM payments p 
    LEFT JOIN users u ON p.user_id = u.id 
    ORDER BY p.id DESC
");
while ($row = $stmt->fetch_assoc()) {
    $all_payments[] = $row;
}

// Stats
 $total_amount = 0;
 $pending_count = 0;
 $approved_count = 0;
 $rejected_count = 0;

foreach ($all_payments as $p) {
    $st = strtolower($p['status']);
    $total_amount += $p['amount'];
    if ($st === 'pending') $pending_count++;
    if ($st === 'approved') $approved_count++;
    if ($st === 'rejected') $rejected_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Management</title>
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

  .modal-overlay {
    position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);
    z-index:100;display:flex;align-items:center;justify-content:center;
    opacity:0;pointer-events:none;transition:opacity 0.2s ease;
  }
  .modal-overlay.active { opacity:1; pointer-events:auto; }
  .modal-box {
    background:#fff;border-radius:20px;max-width:480px;width:90%;padding:32px;
    transform:scale(0.95);transition:transform 0.2s ease;
    box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);
  }
  .dark .modal-box { background:#131a16; }
  .modal-overlay.active .modal-box { transform:scale(1); }
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- SIDEBAR -->
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

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="../admin.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="../category/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Categories</span>
    </a>
    <a href="../product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Products</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Finance</p>
    <a href="payments.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Payments</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Account</p>
    <a href="../profile.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-user-gear w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">My Profile</span>
    </a>
    <a href="../logout.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10">
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
      <p class="text-xs text-gray-400 mt-0.5">Verify & manage user payments</p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>
    </div>
  </header>

  <div class="px-8 py-6">

    <?php if (!empty($msg)) { ?>
    <div class="flex items-center gap-3 bg-brand-50 dark:bg-brand-950 border border-brand-200 dark:border-brand-800 text-brand-700 dark:text-brand-300 rounded-xl px-5 py-3 mb-6 fade-up">
      <i class="fa-solid fa-circle-check text-brand-500"></i>
      <p class="text-sm font-semibold"><?= $msg ?></p>
    </div>
    <?php } ?>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
            <i class="fa-solid fa-money-bill-wave text-brand-500"></i>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white">Rs. <?= number_format($total_amount) ?></p>
        <p class="text-xs text-gray-400 mt-1">Total Received</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.05s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-yellow-50 dark:bg-yellow-950 flex items-center justify-center">
            <i class="fa-solid fa-clock text-yellow-500"></i>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $pending_count ?></p>
        <p class="text-xs text-gray-400 mt-1">Pending Verification</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.1s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-green-50 dark:bg-green-950 flex items-center justify-center">
            <i class="fa-solid fa-circle-check text-green-500"></i>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $approved_count ?></p>
        <p class="text-xs text-gray-400 mt-1">Approved</p>
      </div>

      <div class="stat-card bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-5 fade-up" style="animation-delay:0.15s">
        <div class="flex items-center justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-red-50 dark:bg-red-950 flex items-center justify-center">
            <i class="fa-solid fa-circle-xmark text-red-500"></i>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $rejected_count ?></p>
        <p class="text-xs text-gray-400 mt-1">Rejected</p>
      </div>
    </div>

    <!-- ALL PAYMENTS TABLE -->
    <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden fade-up" style="animation-delay:0.2s">
      <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
            <i class="fa-solid fa-list text-brand-500 text-sm"></i>
          </div>
          <h3 class="text-sm font-bold text-gray-900 dark:text-white">All Transactions</h3>
        </div>
        <span class="text-xs text-gray-400"><?= count($all_payments) ?> total</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-100 dark:border-white/5">
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-6 py-3">User</th>
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-3">Amount</th>
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-3">Method</th>
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-3">Transaction ID</th>
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-3">Sender</th>
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-3">Proof</th>
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-3">Date</th>
              <th class="text-left text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-3">Status</th>
              <th class="text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider px-6 py-3">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-white/5">
            <?php if (empty($all_payments)) { ?>
            <tr>
              <td colspan="9" class="px-6 py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4">
                  <i class="fa-solid fa-inbox text-gray-300 text-2xl"></i>
                </div>
                <p class="text-sm font-semibold text-gray-400">No payments received yet</p>
              </td>
            </tr>
            <?php } else { ?>
              <?php foreach ($all_payments as $pay) {
                $st = strtolower($pay['status']);
                $statusClass = 'status-pending';
                if ($st === 'approved') $statusClass = 'status-approved';
                if ($st === 'rejected') $statusClass = 'status-rejected';

                $mt = strtolower($pay['method']);
                $methodClass = 'method-cod';
                if ($mt === 'jazzcash') $methodClass = 'method-jazzcash';
                if ($mt === 'easypaisa') $methodClass = 'method-easypaisa';
                if ($mt === 'bank_transfer') $methodClass = 'method-bank_transfer';
              ?>
              <tr class="pay-row">
                <td class="px-6 py-4">
                  <p class="text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($pay['user_name'] ?? 'Unknown') ?></p>
                  <p class="text-[11px] text-gray-400"><?= htmlspecialchars($pay['user_email'] ?? '') ?></p>
                </td>
                <td class="px-4 py-4">
                  <p class="text-sm font-bold text-gray-800 dark:text-white">Rs. <?= number_format($pay['amount']) ?></p>
                </td>
                <td class="px-4 py-4">
                  <span class="method-badge <?= $methodClass ?>"><?= str_replace('_', ' ', $mt) ?></span>
                </td>
                <td class="px-4 py-4">
                  <p class="text-xs font-mono text-gray-600 dark:text-white/70"><?= htmlspecialchars($pay['transaction_id']) ?></p>
                </td>
                <td class="px-4 py-4">
                  <p class="text-sm text-gray-700 dark:text-white/80"><?= htmlspecialchars($pay['sender_name'] ?: 'N/A') ?></p>
                  <p class="text-[11px] text-gray-400"><?= htmlspecialchars($pay['sender_number']) ?></p>
                </td>
                <td class="px-4 py-4">
                  <?php if ($pay['proof_image']) { ?>
                  <a href="../uploads/payments/<?= $pay['proof_image'] ?>" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-semibold transition">
                    <i class="fa-solid fa-image text-[10px]"></i> View
                  </a>
                  <?php } else { ?>
                  <span class="text-xs text-gray-300">None</span>
                  <?php } ?>
                </td>
                <td class="px-4 py-4">
                  <p class="text-xs text-gray-500 dark:text-white/60"><?= date('d M Y', strtotime($pay['created_at'])) ?></p>
                  <p class="text-[10px] text-gray-400"><?= date('h:i A', strtotime($pay['created_at'])) ?></p>
                </td>
                <td class="px-4 py-4">
                  <span class="status-badge <?= $statusClass ?>"><?= ucfirst($st) ?></span>
                </td>
                <td class="px-6 py-4 text-center">
                  <?php if ($st === 'pending') { ?>
                  <div class="flex items-center justify-center gap-2">
                    <button onclick="openModal('approve', <?= $pay['id'] ?>)" class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-950 hover:bg-green-100 dark:hover:bg-green-900 flex items-center justify-center transition" title="Approve">
                      <i class="fa-solid fa-check text-green-500 text-xs"></i>
                    </button>
                    <button onclick="openModal('reject', <?= $pay['id'] ?>)" class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-950 hover:bg-red-100 dark:hover:bg-red-900 flex items-center justify-center transition" title="Reject">
                      <i class="fa-solid fa-xmark text-red-500 text-xs"></i>
                    </button>
                  </div>
                  <?php } else { ?>
                  <span class="text-[10px] text-gray-400">Done</span>
                  <?php } ?>
                </td>
              </tr>
              <?php } ?>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- MODAL -->
<div id="actionModal" class="modal-overlay">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-5">
      <h3 id="modalTitle" class="text-lg font-bold text-gray-900 dark:text-white">Approve Payment</h3>
      <button onclick="closeModal()" class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition">
        <i class="fa-solid fa-xmark text-gray-500 text-sm"></i>
      </button>
    </div>

    <div id="modalIcon" class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
      <i id="modalIconI" class="text-2xl"></i>
    </div>
    <p id="modalDesc" class="text-sm text-gray-500 dark:text-white/60 text-center mb-5">Are you sure?</p>

    <form method="POST" action="" id="modalForm">
      <input type="hidden" name="pay_id" id="modalPayId">
      <input type="hidden" name="action" id="modalAction">
      
      <div class="mb-5">
        <label class="block text-xs font-semibold text-gray-500 dark:text-white/50 uppercase tracking-wider mb-2">Note <span class="normal-case text-gray-300">(Optional)</span></label>
        <textarea name="admin_note" rows="2" id="modalNote"
          class="form-input w-full bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-3 px-4 text-sm text-gray-800 dark:text-white resize-none"
          placeholder="Add a note..."></textarea>
      </div>

      <div class="flex gap-3">
        <button type="button" onclick="closeModal()" class="flex-1 py-3 rounded-xl border border-gray-200 dark:border-white/10 text-sm font-semibold text-gray-600 dark:text-white/70 hover:bg-gray-50 dark:hover:bg-white/5 transition">
          Cancel
        </button>
        <button type="submit" id="modalSubmitBtn" class="flex-1 py-3 rounded-xl text-sm font-bold text-white transition">
          Confirm
        </button>
      </div>
    </form>
  </div>
</div>

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

function openModal(action, payId) {
  var modal = document.getElementById('actionModal');
  var title = document.getElementById('modalTitle');
  var desc = document.getElementById('modalDesc');
  var icon = document.getElementById('modalIcon');
  var iconI = document.getElementById('modalIconI');
  var btn = document.getElementById('modalSubmitBtn');

  document.getElementById('modalPayId').value = payId;
  document.getElementById('modalAction').value = action;
  document.getElementById('modalNote').value = '';

  if (action === 'approve') {
    title.textContent = 'Approve Payment';
    desc.textContent = 'This payment will be marked as approved.';
    icon.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-green-50 dark:bg-green-950';
    iconI.className = 'fa-solid fa-circle-check text-green-500 text-2xl';
    btn.className = 'flex-1 py-3 rounded-xl text-sm font-bold text-white transition bg-green-500 hover:bg-green-600';
    btn.textContent = 'Approve';
  } else {
    title.textContent = 'Reject Payment';
    desc.textContent = 'This payment will be marked as rejected.';
    icon.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-red-50 dark:bg-red-950';
    iconI.className = 'fa-solid fa-circle-xmark text-red-500 text-2xl';
    btn.className = 'flex-1 py-3 rounded-xl text-sm font-bold text-white transition bg-red-500 hover:bg-red-600';
    btn.textContent = 'Reject';
  }

  modal.classList.add('active');
}

function closeModal() {
  document.getElementById('actionModal').classList.remove('active');
}

document.getElementById('actionModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

</body>
</html>