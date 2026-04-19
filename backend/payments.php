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
  ::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.15);border-radius:99px}
  .sidebar-glass{background:rgba(8,75,46,0.95);backdrop-filter:blur(20px)}.dark .sidebar-glass{background:rgba(3,42,26,0.98)}
  .nav-link{position:relative;transition:all .25s cubic-bezier(.4,0,.2,1)}.nav-link::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:0;border-radius:0 4px 4px 0;background:#cbcd3a;transition:height .25s cubic-bezier(.4,0,.2,1)}
  .nav-link:hover::before,.nav-link.active::before{height:60%}.nav-link.active{background:rgba(58,205,126,0.12);color:#cbcd3a}.nav-link:hover{background:rgba(255,255,255,0.06)}
  .sidebar-collapsed .sidebar-text{opacity:0;width:0;overflow:hidden}.sidebar-collapsed .sidebar-logo-text{opacity:0;width:0;overflow:hidden}.sidebar-collapsed .sidebar-avatar{width:36px!important;height:36px!important}
  .topbar-border{position:relative}.topbar-border::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(58,205,126,0.3),transparent)}
  .role-badge{font-size:9px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:2px 7px;border-radius:6px}
  .role-admin{background:rgba(58,205,126,0.15);color:#cbcd3a}.role-user{background:rgba(96,165,250,0.15);color:#60a5fa}
  .status-select{padding:5px 10px;border-radius:8px;font-size:11px;font-weight:600;border:1px solid #e5e7eb;outline:none;cursor:pointer;transition:all .2s;background:#f9fafb;color:#374151}
  .dark .status-select{background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);color:rgba(255,255,255,0.8)}
  .status-select:focus{border-color:#3acd7e;box-shadow:0 0 0 3px rgba(58,205,126,0.1)}
  .toast-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px}
  .toast{padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,0.15);animation:toastIn .3s ease,toastOut .3s ease 2.7s forwards;display:flex;align-items:center;gap:8px}
  .toast-success{background:#10b981;color:#fff}.toast-error{background:#ef4444;color:#fff}
  @keyframes toastIn{from{opacity:0;transform:translateX(40px) scale(.95)}to{opacity:1;transform:translateX(0) scale(1)}}
  @keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(40px)}}
  .fade-up{opacity:0;transform:translateY(20px);animation:fadeUp .5s cubic-bezier(.4,0,.2,1) forwards}
  @keyframes fadeUp{to{opacity:1;transform:translateY(0)}}
  .filter-tab{padding:7px 16px;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s;white-space:nowrap;cursor:pointer;user-select:none}
  .dark .filter-tab{background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6)}
  .filter-tab:hover{background:#f9fafb}.dark .filter-tab:hover{background:rgba(255,255,255,0.06)}
  .filter-tab.active{background:#16b364;color:#fff;border-color:#16b364}
  .page-btn{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;text-decoration:none;background:#fff;color:#6b7280;border:1px solid #e5e7eb;transition:all .2s;cursor:pointer}
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
</style>
</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- ========== IMAGE MODAL ========== -->
<div id="imageModal" class="modal-overlay" onclick="closeImageModal(event)">
  <div class="modal-box">
    <img id="modalImage" src="" class="max-w-full max-h-[85vh] object-contain" alt="Payment proof">
  </div>
</div>

<!-- ========== CONFIRM MODAL ========== -->
<div id="confirmModal" class="modal-overlay" style="align-items:center;justify-content:center">
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

<!-- ========== SIDEBAR ========== -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm"><i class="fa-solid fa-bars text-xs"></i></button>
  </div>
  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="https://picsum.photos/seed/adminface/80/80.jpg" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <div class="flex items-center gap-2"><p class="text-sm font-semibold leading-tight">Ahmed Khan</p><span class="role-badge role-admin">Admin</span></div>
      <p class="text-[11px] text-white/50 mt-0.5">ahmed@admin.com</p>
    </div>
  </div>
  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Dashboard</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Category</span></a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Categories</span></a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Add Product</span></a>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">View Products</span></a>
    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Sales</p>
    <a href="#" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white"><i class="fa-solid fa-cart-shopping w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">All Orders</span></a>
    <a href="#" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium"><i class="fa-solid fa-wallet w-5 text-center text-[13px]"></i><span class="sidebar-text transition-all duration-300">Payments</span><span id="sidebarPendingBadge" class="ml-auto bg-amber-500 text-brand-950 text-[10px] font-bold px-2 py-0.5 rounded-full sidebar-text transition-all duration-300 pulse-dot"></span></a>
  </nav>
  <div class="px-3 pb-5"><div class="sidebar-text bg-white/5 rounded-xl p-4 transition-all duration-300"><p class="text-[11px] text-white/40 mb-1">Storage Used</p><div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden"><div class="h-full w-[38%] bg-gradient-to-r from-brand-400 to-brand-300 rounded-full"></div></div><p class="text-[11px] text-white/50 mt-1.5">38% of 10 GB</p></div></div>
</aside>

<!-- ========== MAIN ========== -->
<main id="main" class="ml-[260px] min-h-screen transition-all duration-300">

  <!-- TOPBAR -->
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Payments</h1>
      <p id="topbarCount" class="text-xs text-gray-400 mt-0.5">0 total payments</p>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70"><i class="fa-solid fa-moon text-sm"></i></button>
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="https://picsum.photos/seed/adminface/80/80.jpg" class="w-8 h-8 rounded-lg object-cover">
          <span class="hidden sm:block text-sm font-semibold text-gray-900 dark:text-white">Ahmed Khan</span>
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>
        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl rounded-2xl w-48 py-2 dropdown-enter">
          <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition"><i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile</a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1"><a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition"><i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout</a></div>
        </div>
      </div>
    </div>
  </header>

  <!-- CONTENT -->
  <div class="px-8 py-6">

    <!-- Summary Cards -->
    <div class="fade-up grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 hover:shadow-md transition-shadow duration-300">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center"><i class="fa-solid fa-money-bill-wave text-gray-500 dark:text-white/40 text-sm"></i></div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total</span>
        </div>
        <p id="sumAll" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p>
        <p id="countAll" class="text-[11px] text-gray-400 mt-0.5">0 payments</p>
      </div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-amber-100 dark:border-amber-900/20 p-5 hover:shadow-md transition-shadow duration-300">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/10 flex items-center justify-center"><i class="fa-solid fa-clock text-amber-500 text-sm"></i></div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Pending</span>
        </div>
        <p id="sumPending" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p>
        <p id="countPending" class="text-[11px] text-gray-400 mt-0.5">0 awaiting review</p>
      </div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-emerald-100 dark:border-emerald-900/20 p-5 hover:shadow-md transition-shadow duration-300">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 flex items-center justify-center"><i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i></div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-500">Approved</span>
        </div>
        <p id="sumApproved" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p>
        <p id="countApproved" class="text-[11px] text-gray-400 mt-0.5">0 verified</p>
      </div>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-red-100 dark:border-red-900/20 p-5 hover:shadow-md transition-shadow duration-300">
        <div class="flex items-center justify-between mb-3">
          <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/10 flex items-center justify-center"><i class="fa-solid fa-circle-xmark text-red-500 text-sm"></i></div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-red-500">Rejected</span>
        </div>
        <p id="sumRejected" class="text-lg font-extrabold text-gray-900 dark:text-white">Rs. 0</p>
        <p id="countRejected" class="text-[11px] text-gray-400 mt-0.5">0 declined</p>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="fade-up flex gap-2 mb-5 overflow-x-auto pb-1" id="filterTabs">
      <button onclick="setFilter('')" class="filter-tab active" data-filter="">All (<span id="tabAll">0</span>)</button>
      <button onclick="setFilter('pending')" class="filter-tab" data-filter="pending">Pending (<span id="tabPending">0</span>)</button>
      <button onclick="setFilter('approved')" class="filter-tab" data-filter="approved">Approved (<span id="tabApproved">0</span>)</button>
      <button onclick="setFilter('rejected')" class="filter-tab" data-filter="rejected">Rejected (<span id="tabRejected">0</span>)</button>
    </div>

    <!-- Search -->
    <div class="fade-up mb-5">
      <div class="flex gap-3">
        <div class="flex-1 flex items-center bg-white dark:bg-[#131a16] rounded-xl px-4 py-2.5 gap-2 border border-gray-100 dark:border-white/5 focus-within:border-brand-400 focus-within:shadow-[0_0_0_3px_rgba(22,179,100,0.1)] transition-all">
          <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
          <input id="searchInput" type="text" placeholder="Search by transaction ID, sender name, phone, email..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400" oninput="handleSearch()">
        </div>
        <button id="clearBtn" onclick="clearSearch()" class="px-5 py-2.5 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-white/70 rounded-xl text-sm font-semibold transition hidden">Clear</button>
      </div>
    </div>

    <!-- Payments Table -->
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
          <tbody id="paymentsBody" class="divide-y divide-gray-50 dark:divide-white/[0.03]">
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div id="emptyState" class="text-center py-16 hidden">
        <i class="fa-solid fa-wallet text-4xl text-gray-200 dark:text-gray-700 mb-4"></i>
        <p class="text-sm text-gray-400 font-medium">No payments found</p>
        <p id="emptySearchHint" class="text-xs text-gray-300 mt-1 hidden"></p>
      </div>

      <!-- Pagination -->
      <div id="pagination" class="hidden items-center justify-between px-5 py-4 border-t border-gray-100 dark:border-white/5">
        <p id="pageInfo" class="text-xs text-gray-400"></p>
        <div id="pageButtons" class="flex gap-1.5"></div>
      </div>
    </div>

  </div>
</main>

<script>
/* ===== MOCK DATA ===== */
const payments = [
  { id:1, user_id:5, user_name:"Ali Raza", user_email:"ali@gmail.com", amount:4500, method:"JazzCash", status:"pending", transaction_id:"TXN-20250115-001", sender_number:"03001234567", sender_name:"Ali Raza", notes:"Monthly subscription", proof_image:"https://picsum.photos/seed/proof1/400/600.jpg", created_at:"2025-01-15 10:23:00", updated_at:"2025-01-15 10:23:00" },
  { id:2, user_id:8, user_name:"Sara Ahmed", user_email:"sara@hotmail.com", amount:12000, method:"Bank Transfer", status:"approved", transaction_id:"TXN-20250114-002", sender_number:"03219876543", sender_name:"Sara Ahmed", notes:"", proof_image:"https://picsum.photos/seed/proof2/400/600.jpg", created_at:"2025-01-14 14:05:00", updated_at:"2025-01-14 16:30:00" },
  { id:3, user_id:12, user_name:"Usman Malik", user_email:"usman@yahoo.com", amount:7800, method:"Easypaisa", status:"approved", transaction_id:"TXN-20250114-003", sender_number:"03334567890", sender_name:"Usman Malik", notes:"Order #1042", proof_image:"https://picsum.photos/seed/proof3/400/600.jpg", created_at:"2025-01-14 09:12:00", updated_at:"2025-01-14 11:45:00" },
  { id:4, user_id:3, user_name:"Fatima Noor", user_email:"fatima@gmail.com", amount:3200, method:"JazzCash", status:"rejected", transaction_id:"TXN-20250113-004", sender_number:"03007654321", sender_name:"Fatima Noor", notes:"Blurry screenshot", proof_image:"https://picsum.photos/seed/proof4/400/600.jpg", created_at:"2025-01-13 18:40:00", updated_at:"2025-01-13 20:15:00" },
  { id:5, user_id:15, user_name:"Hassan Siddiqui", user_email:"hassan@outlook.com", amount:25000, method:"Bank Transfer", status:"pending", transaction_id:"TXN-20250113-005", sender_number:"03123456789", sender_name:"Hassan Siddiqui", notes:"Bulk order payment", proof_image:"https://picsum.photos/seed/proof5/400/600.jpg", created_at:"2025-01-13 11:30:00", updated_at:"2025-01-13 11:30:00" },
  { id:6, user_id:7, user_name:"Ayesha Tariq", user_email:"ayesha@gmail.com", amount:1500, method:"Easypaisa", status:"approved", transaction_id:"TXN-20250112-006", sender_number:"03456789012", sender_name:"Ayesha Tariq", notes:"", proof_image:"https://picsum.photos/seed/proof6/400/600.jpg", created_at:"2025-01-12 15:20:00", updated_at:"2025-01-12 17:00:00" },
  { id:7, user_id:20, user_name:"Bilal Iqbal", user_email:"bilal@gmail.com", amount:6700, method:"JazzCash", status:"pending", transaction_id:"TXN-20250112-007", sender_number:"03009876543", sender_name:"Bilal Iqbal", notes:"Urgent delivery", proof_image:"https://picsum.photos/seed/proof7/400/600.jpg", created_at:"2025-01-12 08:55:00", updated_at:"2025-01-12 08:55:00" },
  { id:8, user_id:9, user_name:"Nadia Hussain", user_email:"nadia@hotmail.com", amount:9400, method:"Bank Transfer", status:"approved", transaction_id:"TXN-20250111-008", sender_number:"03218765432", sender_name:"Nadia Hussain", notes:"Product order #987", proof_image:"https://picsum.photos/seed/proof8/400/600.jpg", created_at:"2025-01-11 13:10:00", updated_at:"2025-01-11 15:40:00" },
  { id:9, user_id:11, user_name:"Kamran Shah", user_email:"kamran@yahoo.com", amount:2100, method:"COD", status:"pending", transaction_id:"TXN-20250111-009", sender_number:"03339876543", sender_name:"Kamran Shah", notes:"Cash on delivery", proof_image:"", created_at:"2025-01-11 10:00:00", updated_at:"2025-01-11 10:00:00" },
  { id:10, user_id:14, user_name:"Zainab Ali", user_email:"zainab@gmail.com", amount:18500, method:"Bank Transfer", status:"rejected", transaction_id:"TXN-20250110-010", sender_number:"03451112233", sender_name:"Zainab Ali", notes:"Wrong account number used", proof_image:"https://picsum.photos/seed/proof10/400/600.jpg", created_at:"2025-01-10 16:30:00", updated_at:"2025-01-10 18:00:00" },
  { id:11, user_id:6, user_name:"Rizwan Ahmed", user_email:"rizwan@outlook.com", amount:5600, method:"JazzCash", status:"approved", transaction_id:"TXN-20250110-011", sender_number:"03005556677", sender_name:"Rizwan Ahmed", notes:"", proof_image:"https://picsum.photos/seed/proof11/400/600.jpg", created_at:"2025-01-10 09:45:00", updated_at:"2025-01-10 12:20:00" },
  { id:12, user_id:18, user_name:"Mariam Javed", user_email:"mariam@gmail.com", amount:8900, method:"Easypaisa", status:"pending", transaction_id:"TXN-20250109-012", sender_number:"03127778899", sender_name:"Mariam Javed", notes:"Gift item order", proof_image:"https://picsum.photos/seed/proof12/400/600.jpg", created_at:"2025-01-09 14:20:00", updated_at:"2025-01-09 14:20:00" },
  { id:13, user_id:2, user_name:"Tahir Mehmood", user_email:"tahir@hotmail.com", amount:3300, method:"JazzCash", status:"approved", transaction_id:"TXN-20250109-013", sender_number:"03009998888", sender_name:"Tahir Mehmood", notes:"", proof_image:"https://picsum.photos/seed/proof13/400/600.jpg", created_at:"2025-01-09 07:30:00", updated_at:"2025-01-09 09:15:00" },
  { id:14, user_id:21, user_name:"Sana Khan", user_email:"sana@yahoo.com", amount:15200, method:"Bank Transfer", status:"pending", transaction_id:"TXN-20250108-014", sender_number:"03214445566", sender_name:"Sana Khan", notes:"Wholesale purchase", proof_image:"https://picsum.photos/seed/proof14/400/600.jpg", created_at:"2025-01-08 17:10:00", updated_at:"2025-01-08 17:10:00" },
  { id:15, user_id:4, user_name:"Imran Butt", user_email:"imran@gmail.com", amount:4800, method:"Easypaisa", status:"rejected", transaction_id:"TXN-20250108-015", sender_number:"03336667778", sender_name:"Imran Butt", notes:"Duplicate payment", proof_image:"https://picsum.photos/seed/proof15/400/600.jpg", created_at:"2025-01-08 12:50:00", updated_at:"2025-01-08 14:30:00" },
  { id:16, user_id:10, user_name:"Hina Shahid", user_email:"hina@outlook.com", amount:7200, method:"JazzCash", status:"approved", transaction_id:"TXN-20250107-016", sender_number:"03003334445", sender_name:"Hina Shahid", notes:"Premium membership", proof_image:"https://picsum.photos/seed/proof16/400/600.jpg", created_at:"2025-01-07 11:00:00", updated_at:"2025-01-07 13:45:00" },
  { id:17, user_id:16, user_name:"Farhan Ali", user_email:"farhan@gmail.com", amount:11000, method:"Bank Transfer", status:"pending", transaction_id:"TXN-20250107-017", sender_number:"03122223334", sender_name:"Farhan Ali", notes:"Electronics order", proof_image:"https://picsum.photos/seed/proof17/400/600.jpg", created_at:"2025-01-07 08:15:00", updated_at:"2025-01-07 08:15:00" },
  { id:18, user_id:13, user_name:"Sobia Riaz", user_email:"sobia@hotmail.com", amount:2900, method:"COD", status:"approved", transaction_id:"TXN-20250106-018", sender_number:"03458889900", sender_name:"Sobia Riaz", notes:"Confirmed by phone", proof_image:"", created_at:"2025-01-06 19:30:00", updated_at:"2025-01-06 20:00:00" },
  { id:19, user_id:19, user_name:"Waqar Younis", user_email:"waqar@yahoo.com", amount:20500, method:"Bank Transfer", status:"pending", transaction_id:"TXN-20250106-019", sender_number:"03005556668", sender_name:"Waqar Younis", notes:"Large order - needs verification", proof_image:"https://picsum.photos/seed/proof19/400/600.jpg", created_at:"2025-01-06 10:45:00", updated_at:"2025-01-06 10:45:00" },
  { id:20, user_id:17, user_name:"Asma Latif", user_email:"asma@gmail.com", amount:4100, method:"Easypaisa", status:"approved", transaction_id:"TXN-20250105-020", sender_number:"03127778800", sender_name:"Asma Latif", notes:"", proof_image:"https://picsum.photos/seed/proof20/400/600.jpg", created_at:"2025-01-05 15:55:00", updated_at:"2025-01-05 18:10:00" },
  { id:21, user_id:22, user_name:"Danish Baig", user_email:"danish@outlook.com", amount:6350, method:"JazzCash", status:"rejected", transaction_id:"TXN-20250105-021", sender_number:"03334445566", sender_name:"Danish Baig", notes:"Amount mismatch", proof_image:"https://picsum.photos/seed/proof21/400/600.jpg", created_at:"2025-01-05 09:20:00", updated_at:"2025-01-05 11:00:00" },
  { id:22, user_id:1, user_name:"Admin User", user_email:"admin@site.com", amount:999, method:"JazzCash", status:"pending", transaction_id:"TXN-20250115-022", sender_number:"03001112223", sender_name:"Test Sender", notes:"Test payment for verification", proof_image:"https://picsum.photos/seed/proof22/400/600.jpg", created_at:"2025-01-15 12:00:00", updated_at:"2025-01-15 12:00:00" },
];

/* ===== STATE ===== */
let currentFilter = '';
let currentSearch = '';
let currentPage = 1;
const perPage = 10;

/* ===== UTILITIES ===== */
function fmt(n) { return new Intl.NumberFormat('en-PK').format(n); }
function fmtDate(d) { return new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); }
function fmtTime(d) { return new Date(d).toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', hour12:true }); }
function fmtDateTime(d) { return new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short' }) + ' ' + new Date(d).toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', hour12:true }); }

function getMethodClass(m) {
  const ml = m.toLowerCase();
  if (ml.includes('jazz')) return 'method-jazzcash';
  if (ml.includes('easy')) return 'method-easypaisa';
  if (ml.includes('bank')) return 'method-bank';
  if (ml.includes('cod') || ml.includes('cash')) return 'method-cod';
  return 'method-default';
}

/* ===== TOAST ===== */
function showToast(type, message) {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>${message}`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3200);
}

/* ===== COMPUTED DATA ===== */
function getFilteredPayments() {
  return payments.filter(p => {
    if (currentFilter && p.status !== currentFilter) return false;
    if (currentSearch) {
      const s = currentSearch.toLowerCase();
      return p.transaction_id.toLowerCase().includes(s) ||
             p.sender_name.toLowerCase().includes(s) ||
             p.sender_number.includes(s) ||
             p.user_email.toLowerCase().includes(s) ||
             p.user_name.toLowerCase().includes(s);
    }
    return true;
  });
}

function getCounts() {
  const all = payments.length;
  const pending = payments.filter(p => p.status === 'pending').length;
  const approved = payments.filter(p => p.status === 'approved').length;
  const rejected = payments.filter(p => p.status === 'rejected').length;
  return { all, pending, approved, rejected };
}

function getSums() {
  const sum = (arr) => arr.reduce((a, p) => a + p.amount, 0);
  return {
    all: sum(payments),
    pending: sum(payments.filter(p => p.status === 'pending')),
    approved: sum(payments.filter(p => p.status === 'approved')),
    rejected: sum(payments.filter(p => p.status === 'rejected')),
  };
}

/* ===== RENDER ===== */
function render() {
  const counts = getCounts();
  const sums = getSums();
  const filtered = getFilteredPayments();
  const total = filtered.length;
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  if (currentPage > totalPages) currentPage = totalPages;
  const start = (currentPage - 1) * perPage;
  const pageData = filtered.slice(start, start + perPage);

  /* Summary cards */
  document.getElementById('sumAll').textContent = 'Rs. ' + fmt(sums.all);
  document.getElementById('countAll').textContent = counts.all + ' payments';
  document.getElementById('sumPending').textContent = 'Rs. ' + fmt(sums.pending);
  document.getElementById('countPending').textContent = counts.pending + ' awaiting review';
  document.getElementById('sumApproved').textContent = 'Rs. ' + fmt(sums.approved);
  document.getElementById('countApproved').textContent = counts.approved + ' verified';
  document.getElementById('sumRejected').textContent = 'Rs. ' + fmt(sums.rejected);
  document.getElementById('countRejected').textContent = counts.rejected + ' declined';

  /* Topbar */
  document.getElementById('topbarCount').textContent = total + ' total payments';

  /* Tabs */
  document.getElementById('tabAll').textContent = counts.all;
  document.getElementById('tabPending').textContent = counts.pending;
  document.getElementById('tabApproved').textContent = counts.approved;
  document.getElementById('tabRejected').textContent = counts.rejected;
  document.querySelectorAll('#filterTabs .filter-tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.filter === currentFilter);
  });

  /* Sidebar badge */
  const badge = document.getElementById('sidebarPendingBadge');
  if (counts.pending > 0) { badge.textContent = counts.pending; badge.style.display = ''; }
  else { badge.style.display = 'none'; }

  /* Search clear button */
  document.getElementById('clearBtn').classList.toggle('hidden', !currentSearch);

  /* Table body */
  const tbody = document.getElementById('paymentsBody');
  const empty = document.getElementById('emptyState');
  const emptyHint = document.getElementById('emptySearchHint');

  if (total === 0) {
    tbody.innerHTML = '';
    empty.classList.remove('hidden');
    if (currentSearch) {
      emptyHint.classList.remove('hidden');
      emptyHint.textContent = `No results for "${currentSearch}"`;
    } else {
      emptyHint.classList.add('hidden');
    }
  } else {
    empty.classList.add('hidden');
    tbody.innerHTML = pageData.map(p => {
      const isPending = p.status === 'pending';
      const isApproved = p.status === 'approved';
      const isRejected = p.status === 'rejected';
      const updatedDiff = p.updated_at !== p.created_at;

      return `
      <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition ${isPending ? 'bg-amber-50/30 dark:bg-amber-900/5' : ''}" id="row-${p.id}">
        <td class="px-5 py-3.5">
          <span class="font-bold text-gray-900 dark:text-white text-xs font-mono">${esc(p.transaction_id)}</span>
        </td>
        <td class="px-5 py-3.5">
          <p class="text-xs font-semibold text-gray-800 dark:text-white">${esc(p.user_name)}</p>
          <p class="text-[10px] text-gray-400">${esc(p.user_email)}</p>
        </td>
        <td class="px-5 py-3.5">
          <p class="text-xs font-semibold text-gray-800 dark:text-white">${esc(p.sender_name)}</p>
          <p class="text-[10px] text-gray-400">${esc(p.sender_number)}</p>
          ${p.notes ? `<p class="text-[10px] text-gray-400 mt-0.5 italic max-w-[140px] truncate" title="${esc(p.notes)}"><i class="fa-solid fa-note-sticky text-[8px] mr-1"></i>${esc(p.notes)}</p>` : ''}
        </td>
        <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white text-xs">Rs. ${fmt(p.amount)}</td>
        <td class="px-5 py-3.5">
          <span class="method-badge ${getMethodClass(p.method)}">${esc(p.method)}</span>
        </td>
        <td class="px-5 py-3.5">
          ${p.proof_image
            ? `<img src="${p.proof_image}" class="proof-thumb" onclick="openImageModal('${p.proof_image}')" alt="Proof" onerror="this.outerHTML='<div class=\\'w-11 h-11 rounded-[10px] bg-gray-100 dark:bg-white/5 flex items-center justify-center text-gray-300 dark:text-gray-600\\'><i class=\\'fa-solid fa-image text-xs\\'></i></div>'">`
            : `<div class="w-11 h-11 rounded-[10px] bg-gray-100 dark:bg-white/5 flex items-center justify-center text-gray-300 dark:text-gray-600"><i class="fa-solid fa-image text-xs"></i></div>`}
        </td>
        <td class="px-5 py-3.5">
          <select onchange="handleStatusChange(${p.id}, this.value)" class="status-select">
            <option value="pending" ${isPending ? 'selected' : ''}>Pending</option>
            <option value="approved" ${isApproved ? 'selected' : ''}>Approved</option>
            <option value="rejected" ${isRejected ? 'selected' : ''}>Rejected</option>
          </select>
        </td>
        <td class="px-5 py-3.5">
          <p class="text-[11px] text-gray-500">${fmtDate(p.created_at)}</p>
          <p class="text-[10px] text-gray-400">${fmtTime(p.created_at)}</p>
          ${updatedDiff ? `<p class="text-[9px] text-gray-300 dark:text-gray-600 mt-0.5">Updated: ${fmtDateTime(p.updated_at)}</p>` : ''}
        </td>
        <td class="px-5 py-3.5 text-center">
          <div class="flex items-center justify-center gap-1.5">
            ${isPending ? `
              <button onclick="confirmAction(${p.id},'approved')" class="action-btn bg-emerald-50 dark:bg-emerald-900/10 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400" title="Approve"><i class="fa-solid fa-check"></i></button>
              <button onclick="confirmAction(${p.id},'rejected')" class="action-btn bg-red-50 dark:bg-red-900/10 hover:bg-red-100 dark:hover:bg-red-900/20 text-red-500 dark:text-red-400" title="Reject"><i class="fa-solid fa-xmark"></i></button>
            ` : isApproved ? `
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-900/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold"><i class="fa-solid fa-check-circle text-[10px]"></i> Verified</span>
            ` : `
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 dark:bg-red-900/10 text-red-500 dark:text-red-400 text-[10px] font-bold"><i class="fa-solid fa-times-circle text-[10px]"></i> Rejected</span>
            `}
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  /* Pagination */
  const pag = document.getElementById('pagination');
  if (totalPages > 1) {
    pag.classList.remove('hidden');
    pag.classList.add('flex');
    document.getElementById('pageInfo').textContent = `Showing ${start + 1}–${Math.min(start + perPage, total)} of ${total}`;
    let btns = '';
    if (currentPage > 1) btns += `<button onclick="goPage(${currentPage - 1})" class="page-btn"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>`;
    const rangeStart = Math.max(1, currentPage - 2);
    const rangeEnd = Math.min(totalPages, currentPage + 2);
    if (rangeStart > 1) { btns += `<button onclick="goPage(1)" class="page-btn">1</button>`; if (rangeStart > 2) btns += `<span class="w-9 h-9 flex items-center justify-center text-gray-300 text-xs">...</span>`; }
    for (let i = rangeStart; i <= rangeEnd; i++) btns += `<button onclick="goPage(${i})" class="page-btn ${i === currentPage ? 'active' : ''}">${i}</button>`;
    if (rangeEnd < totalPages) { if (rangeEnd < totalPages - 1) btns += `<span class="w-9 h-9 flex items-center justify-center text-gray-300 text-xs">...</span>`; btns += `<button onclick="goPage(${totalPages})" class="page-btn">${totalPages}</button>`; }
    if (currentPage < totalPages) btns += `<button onclick="goPage(${currentPage + 1})" class="page-btn"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>`;
    document.getElementById('pageButtons').innerHTML = btns;
  } else {
    pag.classList.add('hidden');
    pag.classList.remove('flex');
  }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

/* ===== ACTIONS ===== */
function setFilter(f) {
  currentFilter = f;
  currentPage = 1;
  render();
}

function handleSearch() {
  currentSearch = document.getElementById('searchInput').value.trim();
  currentPage = 1;
  render();
}

function clearSearch() {
  document.getElementById('searchInput').value = '';
  currentSearch = '';
  currentPage = 1;
  render();
}

function goPage(p) {
  currentPage = p;
  render();
  /* Scroll table into view smoothly */
  document.querySelector('table').scrollIntoView({ behavior:'smooth', block:'start' });
}

/* Status change via dropdown */
function handleStatusChange(id, newStatus) {
  const p = payments.find(x => x.id === id);
  if (!p) return;
  const oldStatus = p.status;
  p.status = newStatus;
  p.updated_at = new Date().toISOString().slice(0,19).replace('T',' ');
  showToast('success', `Payment ${ucFirst(newStatus)} successfully`);
  render();
}

/* Confirm modal for action buttons */
let pendingAction = null;

function confirmAction(id, newStatus) {
  const p = payments.find(x => x.id === id);
  if (!p) return;
  pendingAction = { id, newStatus };
  const modal = document.getElementById('confirmModal');
  const icon = document.getElementById('confirmIcon');
  const title = document.getElementById('confirmTitle');
  const desc = document.getElementById('confirmDesc');
  const btn = document.getElementById('confirmAction');

  if (newStatus === 'approved') {
    icon.className = 'w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/10';
    icon.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>';
    title.textContent = 'Approve Payment';
    desc.textContent = `Approve Rs. ${fmt(p.amount)} from ${p.sender_name}?`;
    btn.className = 'flex-1 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold transition';
    btn.textContent = 'Approve';
  } else {
    icon.className = 'w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-red-50 dark:bg-red-900/10';
    icon.innerHTML = '<i class="fa-solid fa-circle-xmark text-red-500 text-2xl"></i>';
    title.textContent = 'Reject Payment';
    desc.textContent = `Reject Rs. ${fmt(p.amount)} from ${p.sender_name}?`;
    btn.className = 'flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition';
    btn.textContent = 'Reject';
  }
  btn.onclick = executeConfirmedAction;
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function executeConfirmedAction() {
  if (!pendingAction) return;
  const p = payments.find(x => x.id === pendingAction.id);
  if (p) {
    p.status = pendingAction.newStatus;
    p.updated_at = new Date().toISOString().slice(0,19).replace('T',' ');
    showToast('success', `Payment ${ucFirst(pendingAction.newStatus)} successfully`);
  }
  pendingAction = null;
  closeConfirmModal();
  render();
}

function closeConfirmModal() {
  document.getElementById('confirmModal').classList.remove('show');
  document.body.style.overflow = '';
  pendingAction = null;
}

function ucFirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

/* ===== IMAGE MODAL ===== */
function openImageModal(src) {
  const modal = document.getElementById('imageModal');
  document.getElementById('modalImage').src = src;
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}
function closeImageModal(e) {
  if (e.target === document.getElementById('imageModal')) {
    document.getElementById('imageModal').classList.remove('show');
    document.body.style.overflow = '';
  }
}

/* ===== SIDEBAR ===== */
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const m = document.getElementById('main');
  const c = s.classList.toggle('sidebar-collapsed');
  s.style.width = c ? '78px' : '260px';
  m.style.marginLeft = c ? '78px' : '260px';
}

/* ===== DARK MODE ===== */
function toggleDark() {
  const html = document.documentElement;
  const body = document.body;
  const btn = document.getElementById('darkBtn');
  const isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);
  btn.innerHTML = isDark ? '<i class="fa-solid fa-sun text-sm"></i>' : '<i class="fa-solid fa-moon text-sm"></i>';
  localStorage.setItem('darkMode', isDark ? '1' : '0');
}

/* Init dark mode from preference */
(function() {
  const saved = localStorage.getItem('darkMode');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  if (saved === '1' || (!saved && prefersDark)) {
    document.body.classList.add('dark');
    document.documentElement.classList.add('dark');
    document.getElementById('darkBtn').innerHTML = '<i class="fa-solid fa-sun text-sm"></i>';
  }
})();

/* ===== PROFILE DROPDOWN ===== */
function toggleMenu() {
  document.getElementById('menu').classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
  const m = document.getElementById('menu');
  if (!e.target.closest('.relative') && !m.classList.contains('hidden')) m.classList.add('hidden');
});

/* ===== KEYBOARD ===== */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.getElementById('imageModal').classList.remove('show');
    closeConfirmModal();
    document.body.style.overflow = '';
  }
});

/* Confirm modal click-outside */
document.getElementById('confirmModal').addEventListener('click', function(e) {
  if (e.target === this) closeConfirmModal();
});

/* ===== INIT ===== */
render();
</script>

</body>
</html>