<?php
include("config/db.php");

/* DATA */
 $cat = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM categories"))['total'];
 $prod = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM products"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
          50: '#edfcf2', 100: '#d3f8e0', 200: '#aaf0c6',
          300: '#73e2a5', 400: '#3acd7e', 500: '#16b364',
          600: '#0a9150', 700: '#087442', 800: '#095c37',
          900: '#084b2e', 950: '#032a1a'
        }
      }
    }
  }
}
</script>

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Plus Jakarta Sans', sans-serif; }

  /* Scrollbar */
  ::-webkit-scrollbar { width: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 99px; }
  .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

  /* Sidebar glass */
  .sidebar-glass {
    background: rgba(8, 75, 46, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
  }
  .dark .sidebar-glass {
    background: rgba(3, 42, 26, 0.98);
  }

  /* Nav link active line */
  .nav-link { position: relative; transition: all 0.25s cubic-bezier(.4,0,.2,1); }
  .nav-link::before {
    content: '';
    position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    width: 3px; height: 0; border-radius: 0 4px 4px 0;
    background: #3acd7e;
    transition: height 0.25s cubic-bezier(.4,0,.2,1);
  }
  .nav-link:hover::before, .nav-link.active::before { height: 60%; }
  .nav-link.active { background: rgba(58, 205, 126, 0.12); color: #3acd7e; }
  .nav-link:hover { background: rgba(255,255,255,0.06); }

  /* Stat card shimmer */
  .stat-card {
    position: relative; overflow: hidden;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1), box-shadow 0.3s ease;
  }
  .stat-card::after {
    content: '';
    position: absolute; top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(58,205,126,0.06) 0%, transparent 60%);
    opacity: 0; transition: opacity 0.4s ease;
    pointer-events: none;
  }
  .stat-card:hover::after { opacity: 1; }
  .stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -12px rgba(0,0,0,0.12); }
  .dark .stat-card:hover { box-shadow: 0 20px 40px -12px rgba(0,0,0,0.4); }

  /* Counter animation */
  .counter { display: inline-block; }

  /* Icon container pulse */
  .icon-ring {
    animation: iconPulse 3s ease-in-out infinite;
  }
  @keyframes iconPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(58,205,126,0.2); }
    50% { box-shadow: 0 0 0 8px rgba(58,205,126,0); }
  }

  /* Fade-in on load */
  .fade-up {
    opacity: 0; transform: translateY(20px);
    animation: fadeUp 0.6s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
  }
  .fade-up:nth-child(1) { animation-delay: 0.05s; }
  .fade-up:nth-child(2) { animation-delay: 0.1s; }
  .fade-up:nth-child(3) { animation-delay: 0.15s; }
  .fade-up:nth-child(4) { animation-delay: 0.2s; }

  /* Dropdown */
  .dropdown-enter { animation: dropIn 0.2s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes dropIn {
    from { opacity: 0; transform: translateY(-8px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }

  /* Chart container */
  .chart-glow {
    position: relative;
  }
  .chart-glow::before {
    content: '';
    position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
    width: 60%; height: 40%;
    background: radial-gradient(ellipse, rgba(58,205,126,0.08) 0%, transparent 70%);
    pointer-events: none;
  }

  /* Sidebar collapsed text hide */
  .sidebar-collapsed .sidebar-text { opacity: 0; width: 0; overflow: hidden; }
  .sidebar-collapsed .sidebar-logo-text { opacity: 0; width: 0; overflow: hidden; }
  .sidebar-collapsed .sidebar-avatar { width: 36px; height: 36px; }

  /* Dot indicator */
  .live-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #3acd7e;
    animation: livePulse 2s ease-in-out infinite;
  }
  @keyframes livePulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
  }

  /* Topbar gradient border */
  .topbar-border {
    position: relative;
  }
  .topbar-border::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(58,205,126,0.3), transparent);
  }
</style>

</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- ========== SIDEBAR ========== -->
<aside id="sidebar" class="sidebar-glass fixed left-0 top-0 h-full w-[260px] text-white z-50 transition-all duration-300 flex flex-col">

  <!-- Logo -->
  <div class="flex items-center justify-between px-5 pt-6 pb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-brand-400 flex items-center justify-center text-brand-950 font-extrabold text-sm shrink-0">A</div>
      <span class="sidebar-logo-text font-bold text-base tracking-tight transition-all duration-300">AdminPanel</span>
    </div>
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm">
      <i class="fa-solid fa-bars text-xs"></i>
    </button>
  </div>

  <!-- Avatar -->
  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="uploads/about.png" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <p class="text-sm font-semibold leading-tight">Adil Khoso</p>
      <p class="text-[11px] text-white/50 mt-0.5">Super Admin</p>
    </div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>

    <a href="#" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
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

  </nav>

  <!-- Bottom -->
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
  <header class="topbar-border sticky top-0 z-40 bg-white/80 dark:bg-[#0d1410]/80 backdrop-blur-xl px-8 py-4 flex justify-between items-center">

    <div>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Dashboard</h1>
      <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1.5">
        <span class="live-dot"></span> Live overview
      </p>
    </div>

    <div class="flex items-center gap-3">

      <!-- Search -->
      <div class="hidden md:flex items-center bg-gray-100 dark:bg-white/5 rounded-xl px-4 py-2 gap-2 w-56">
        <i class="fa-solid fa-magnifying-glass text-gray-400 text-xs"></i>
        <input type="text" placeholder="Search..." class="bg-transparent outline-none text-sm text-gray-700 dark:text-white/80 w-full placeholder:text-gray-400">
      </div>

      <!-- Dark Mode Toggle -->
      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>

      <!-- Notification -->
      <button class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70 relative">
        <i class="fa-solid fa-bell text-sm"></i>
        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
      </button>

      <!-- Profile -->
      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="uploads/about.png" class="w-8 h-8 rounded-lg object-cover">
          <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
        </button>

        <div id="menu" class="hidden absolute right-0 mt-2 bg-white dark:bg-[#151d19] border border-gray-200 dark:border-white/10 shadow-xl dark:shadow-2xl rounded-2xl w-48 py-2 overflow-hidden dropdown-enter">
          <div class="px-4 py-2.5 border-b border-gray-100 dark:border-white/5">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Adil Khoso</p>
            <p class="text-[11px] text-gray-400">admin@example.com</p>
          </div>
          <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition">
            <i class="fa-solid fa-user w-4 text-center text-xs"></i> Profile
          </a>
          <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition">
            <i class="fa-solid fa-gear w-4 text-center text-xs"></i> Settings
          </a>
          <div class="border-t border-gray-100 dark:border-white/5 mt-1 pt-1">
            <a href="#" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/5 transition">
              <i class="fa-solid fa-right-from-bracket w-4 text-center text-xs"></i> Logout
            </a>
          </div>
        </div>
      </div>

    </div>
  </header>

  <!-- CONTENT -->
  <div class="px-8 py-6">

    <!-- STATS GRID -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

      <!-- Categories -->
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-5 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Categories</p>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 counter" data-target="<?= $cat ?>">0</h2>
            <p class="text-xs text-brand-500 font-medium mt-2 flex items-center gap-1">
              <i class="fa-solid fa-arrow-trend-up text-[10px]"></i> Active
            </p>
          </div>
          <div class="icon-ring w-12 h-12 rounded-2xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center">
            <i class="fa-solid fa-folder text-brand-500"></i>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-5 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Products</p>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 counter" data-target="<?= $prod ?>">0</h2>
            <p class="text-xs text-brand-500 font-medium mt-2 flex items-center gap-1">
              <i class="fa-solid fa-arrow-trend-up text-[10px]"></i> Listed
            </p>
          </div>
          <div class="icon-ring w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950 flex items-center justify-center">
            <i class="fa-solid fa-box text-amber-500"></i>
          </div>
        </div>
      </div>

      <!-- Orders -->
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-5 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Orders</p>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 counter" data-target="0">0</h2>
            <p class="text-xs text-gray-400 font-medium mt-2 flex items-center gap-1">
              <i class="fa-solid fa-minus text-[10px]"></i> No data
            </p>
          </div>
          <div class="icon-ring w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950 flex items-center justify-center">
            <i class="fa-solid fa-cart-shopping text-sky-500"></i>
          </div>
        </div>
      </div>

      <!-- Users -->
      <div class="stat-card fade-up bg-white dark:bg-[#131a16] rounded-2xl p-5 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Users</p>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2 counter" data-target="0">0</h2>
            <p class="text-xs text-gray-400 font-medium mt-2 flex items-center gap-1">
              <i class="fa-solid fa-minus text-[10px]"></i> No data
            </p>
          </div>
          <div class="icon-ring w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-950 flex items-center justify-center">
            <i class="fa-solid fa-users text-violet-500"></i>
          </div>
        </div>
      </div>

    </div>

    <!-- CHART + QUICK INFO ROW -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mt-6">

      <!-- Chart -->
      <div class="xl:col-span-2 fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Analytics Overview</h3>
            <p class="text-xs text-gray-400 mt-0.5">Categories vs Products distribution</p>
          </div>
          <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-brand-400"></span>Categories</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>Products</span>
          </div>
        </div>
        <div class="chart-glow flex items-center justify-center" style="height: 300px;">
          <canvas id="chart"></canvas>
        </div>
      </div>

      <!-- Quick Info -->
      <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm p-6 flex flex-col">
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Quick Info</h3>

        <div class="flex-1 space-y-4">
          <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/[0.03]">
            <div class="w-9 h-9 rounded-lg bg-brand-50 dark:bg-brand-950 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-database text-brand-500 text-xs"></i>
            </div>
            <div>
              <p class="text-xs text-gray-400">Database</p>
              <p class="text-sm font-semibold text-gray-800 dark:text-white">Connected</p>
            </div>
            <span class="ml-auto w-2 h-2 rounded-full bg-brand-400"></span>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/[0.03]">
            <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-950 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-server text-amber-500 text-xs"></i>
            </div>
            <div>
              <p class="text-xs text-gray-400">Server</p>
              <p class="text-sm font-semibold text-gray-800 dark:text-white">Localhost</p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/[0.03]">
            <div class="w-9 h-9 rounded-lg bg-sky-50 dark:bg-sky-950 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-code text-sky-500 text-xs"></i>
            </div>
            <div>
              <p class="text-xs text-gray-400">PHP Version</p>
              <p class="text-sm font-semibold text-gray-800 dark:text-white"><?= PHP_VERSION ?></p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/[0.03]">
            <div class="w-9 h-9 rounded-lg bg-violet-50 dark:bg-violet-950 flex items-center justify-center shrink-0">
              <i class="fa-solid fa-clock text-violet-500 text-xs"></i>
            </div>
            <div>
              <p class="text-xs text-gray-400">Last Login</p>
              <p class="text-sm font-semibold text-gray-800 dark:text-white"><?= date('d M, h:i A') ?></p>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5">
          <p class="text-[11px] text-gray-400 text-center">Built with PHP & Tailwind CSS</p>
        </div>
      </div>

    </div>

  </div>

</main>

<!-- ========== SCRIPTS ========== -->
<script>

/* ===== SIDEBAR TOGGLE ===== */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const main = document.getElementById('main');
  const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');

  if (isCollapsed) {
    sidebar.style.width = '78px';
    main.style.marginLeft = '78px';
  } else {
    sidebar.style.width = '260px';
    main.style.marginLeft = '260px';
  }
}

/* ===== DARK MODE ===== */
function toggleDark() {
  const html = document.documentElement;
  const body = document.body;
  const btn = document.getElementById('darkBtn');
  const isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);

  btn.innerHTML = isDark
    ? '<i class="fa-solid fa-sun text-sm"></i>'
    : '<i class="fa-solid fa-moon text-sm"></i>';

  // Update chart colors
  if (window.analyticsChart) {
    const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.04)';
    window.analyticsChart.options.plugins.legend.labels.color = isDark ? '#9ca3af' : '#6b7280';
    window.analyticsChart.update();
  }
}

/* ===== PROFILE MENU ===== */
function toggleMenu() {
  const menu = document.getElementById('menu');
  if (menu.classList.contains('hidden')) {
    menu.classList.remove('hidden');
    menu.classList.add('dropdown-enter');
  } else {
    menu.classList.add('hidden');
    menu.classList.remove('dropdown-enter');
  }
}

// Close menu on outside click
document.addEventListener('click', function(e) {
  const menu = document.getElementById('menu');
  if (!e.target.closest('.relative') && !menu.classList.contains('hidden')) {
    menu.classList.add('hidden');
  }
});

/* ===== COUNTER ANIMATION ===== */
document.querySelectorAll('.counter').forEach(counter => {
  const target = parseInt(counter.getAttribute('data-target'));
  if (target === 0) return;

  const duration = 1200;
  const startTime = performance.now();

  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    // Ease-out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    counter.textContent = Math.round(eased * target);
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
});

/* ===== CHART ===== */
window.analyticsChart = new Chart(document.getElementById('chart'), {
  type: 'doughnut',
  data: {
    labels: ['Categories', 'Products'],
    datasets: [{
      data: [<?= $cat ?>, <?= $prod ?>],
      backgroundColor: ['#3acd7e', '#f59e0b'],
      hoverBackgroundColor: ['#2ab56d', '#d97706'],
      borderWidth: 0,
      spacing: 4,
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1a1a1a',
        titleFont: { family: 'Plus Jakarta Sans', weight: '600', size: 13 },
        bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
        padding: 12,
        cornerRadius: 10,
        displayColors: true,
        boxPadding: 4
      }
    },
    animation: {
      animateRotate: true,
      duration: 1400,
      easing: 'easeOutQuart'
    }
  },
  plugins: [{
    id: 'centerText',
    beforeDraw(chart) {
      const { ctx, width, height } = chart;
      const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.font = '800 28px Plus Jakarta Sans';
      ctx.fillStyle = getComputedStyle(document.body).classList.contains('dark') ? '#ffffff' : '#111827';
      ctx.fillText(total, width / 2, height / 2 - 6);
      ctx.font = '500 11px Plus Jakarta Sans';
      ctx.fillStyle = '#9ca3af';
      ctx.fillText('Total Items', width / 2, height / 2 + 16);
      ctx.restore();
    }
  }]
});

</script>

</body>
</html>