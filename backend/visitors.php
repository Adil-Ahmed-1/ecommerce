<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}
include('../backend/config/db.php');

// Clean stale
mysqli_query($conn, "DELETE FROM active_visitors WHERE last_activity < NOW() - INTERVAL 3 MINUTE");

/* ===== FILTERS ===== */
 $filter_date   = $_GET['date'] ?? '';
 $filter_device = $_GET['device'] ?? '';
 $filter_browser= $_GET['browser'] ?? '';
 $filter_search = trim($_GET['search'] ?? '');
 $page_num      = max(1, intval($_GET['page'] ?? 1));
 $per_page      = 20;

 $where = "1=1";
if ($filter_date === 'today')     $where .= " AND visit_date = CURDATE()";
elseif ($filter_date === 'week')  $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
elseif ($filter_date === 'month') $where .= " AND visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
if ($filter_device)  $where .= " AND device_type = '" . mysqli_real_escape_string($conn, $filter_device) . "'";
if ($filter_browser) $where .= " AND browser = '" . mysqli_real_escape_string($conn, $filter_browser) . "'";
if ($filter_search)  $where .= " AND (ip_address LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%' OR country LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%' OR city LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%' OR page_url LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%')";

/* ===== STATS ===== */
 $total_all     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors"))['c'];
 $total_today   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE visit_date = CURDATE()"))['c'];
 $unique_today  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE visit_date = CURDATE() AND is_unique = 1"))['c'];
 $active_now    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM active_visitors"))['c'];
 $total_week    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"))['c'];
 $total_month   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"))['c'];

/* ===== CHART DATA (last 30 days) ===== */
 $chart_data = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $day_label = date('M j', strtotime($d));
    $cr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE visit_date = '$d'"));
    $ur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE visit_date = '$d' AND is_unique = 1"));
    $chart_data[] = ['label' => $day_label, 'total' => (int)$cr['c'], 'unique' => (int)$ur['c']];
}

/* ===== BROWSER STATS ===== */
 $browser_stats = [];
 $br = mysqli_query($conn, "SELECT browser, COUNT(*) as c FROM visitors WHERE $where GROUP BY browser ORDER BY c DESC LIMIT 6");
while ($b = mysqli_fetch_assoc($br)) $browser_stats[] = $b;

/* ===== DEVICE STATS ===== */
 $device_stats = [];
 $dr = mysqli_query($conn, "SELECT device_type, COUNT(*) as c FROM visitors WHERE $where GROUP BY device_type ORDER BY c DESC");
while ($d = mysqli_fetch_assoc($dr)) $device_stats[] = $d;

/* ===== OS STATS ===== */
 $os_stats = [];
 $osr = mysqli_query($conn, "SELECT os, COUNT(*) as c FROM visitors WHERE $where GROUP BY os ORDER BY c DESC LIMIT 8");
while ($o = mysqli_fetch_assoc($osr)) $os_stats[] = $o;

/* ===== COUNTRY STATS ===== */
 $country_stats = [];
 $ctr = mysqli_query($conn, "SELECT country, COUNT(*) as c FROM visitors WHERE $where AND country != 'Unknown' GROUP BY country ORDER BY c DESC LIMIT 10");
while ($c = mysqli_fetch_assoc($ctr)) $country_stats[] = $c;

/* ===== TOP PAGES ===== */
 $top_pages = [];
 $tpr = mysqli_query($conn, "SELECT page_url, COUNT(*) as c FROM visitors WHERE $where GROUP BY page_url ORDER BY c DESC LIMIT 10");
while ($tp = mysqli_fetch_assoc($tpr)) $top_pages[] = $tp;

/* ===== TOP REFERRERS ===== */
 $top_referrers = [];
 $trr = mysqli_query($conn, "SELECT referrer, COUNT(*) as c FROM visitors WHERE $where AND referrer != '' GROUP BY referrer ORDER BY c DESC LIMIT 8");
while ($tr = mysqli_fetch_assoc($trr)) $top_referrers[] = $tr;

/* ===== ACTIVE VISITORS NOW ===== */
 $active_list = [];
 $alr = mysqli_query($conn, "SELECT * FROM active_visitors ORDER BY last_activity DESC LIMIT 50");
while ($al = mysqli_fetch_assoc($alr)) $active_list[] = $al;

/* ===== VISITOR LOG (paginated) ===== */
 $total_filtered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM visitors WHERE $where"))['c'];
 $total_pages = max(1, ceil($total_filtered / $per_page));
 $offset = ($page_num - 1) * $per_page;
 $log_result = mysqli_query($conn, "SELECT * FROM visitors WHERE $where ORDER BY visit_time DESC LIMIT $offset, $per_page");
 $log = [];
while ($l = mysqli_fetch_assoc($log_result)) $log[] = $l;

/* ===== CSV EXPORT ===== */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=visitors_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','IP','Browser','OS','Device','Page','Referrer','Country','City','Date','Time','Unique']);
    $exp = mysqli_query($conn, "SELECT * FROM visitors WHERE $where ORDER BY visit_time DESC");
    while ($row = mysqli_fetch_assoc($exp)) {
        fputcsv($out, [$row['id'],$row['ip_address'],$row['browser'].' '.$row['browser_version'],$row['os'],$row['device_type'],$row['page_url'],$row['referrer'],$row['country'],$row['city'],$row['visit_date'],$row['visit_time'],$row['is_unique']]);
    }
    fclose($out);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visitor Analytics — AHMUS Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        gold: { 400:'#fbbf24',500:'#f59e0b',600:'#d97706' },
        surface: { 900:'#0a0a0f',800:'#101018',700:'#16161f',600:'#1c1c28',500:'#222230',400:'#2a2a3a',300:'#35354a' }
      }
    }
  }
}
</script>
<style>
  * { margin:0;padding:0;box-sizing:border-box; }
  body { font-family:'Plus Jakarta Sans',sans-serif; background:#0a0a0f; color:#fff; }
  ::-webkit-scrollbar { width:6px; }
  ::-webkit-scrollbar-track { background:#0a0a0f; }
  ::-webkit-scrollbar-thumb { background:#2a2a3a; border-radius:99px; }
  .nav-blur { background:rgba(10,10,15,0.85); backdrop-filter:blur(20px); border-bottom:1px solid rgba(255,255,255,0.04); }
  .stat-card { background:#101018; border:1px solid rgba(255,255,255,0.04); border-radius:20px; padding:24px; transition:all 0.3s ease; position:relative; overflow:hidden; }
  .stat-card::before { content:''; position:absolute; inset:0; border-radius:20px; opacity:0; transition:opacity 0.3s; pointer-events:none; }
  .stat-card:hover { transform:translateY(-3px); border-color:rgba(255,255,255,0.08); box-shadow:0 12px 32px -10px rgba(0,0,0,0.5); }
  .stat-card:hover::before { opacity:1; }
  .panel { background:#101018; border:1px solid rgba(255,255,255,0.04); border-radius:20px; overflow:hidden; }
  .panel-header { padding:18px 24px; border-bottom:1px solid rgba(255,255,255,0.04); display:flex; align-items:center; justify-content:space-between; }
  .panel-body { padding:20px 24px; }
  .data-table { width:100%; border-collapse:collapse; }
  .data-table th { padding:10px 14px; font-size:0.7rem; font-weight:700; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.05em; text-align:left; border-bottom:1px solid rgba(255,255,255,0.04); white-space:nowrap; }
  .data-table td { padding:10px 14px; font-size:0.78rem; color:rgba(255,255,255,0.55); border-bottom:1px solid rgba(255,255,255,0.02); white-space:nowrap; }
  .data-table tr:hover td { background:rgba(255,255,255,0.015); }
  .badge { display:inline-flex; align-items:center; padding:3px 8px; border-radius:6px; font-size:0.65rem; font-weight:700; }
  .badge-green { background:rgba(34,197,94,0.1); color:#4ade80; }
  .badge-blue { background:rgba(59,130,246,0.1); color:#60a5fa; }
  .badge-gold { background:rgba(251,191,36,0.1); color:#fbbf24; }
  .badge-purple { background:rgba(168,85,247,0.1); color:#c084fc; }
  .badge-red { background:rgba(239,68,68,0.1); color:#f87171; }
  .filter-btn { padding:6px 14px; border-radius:8px; font-size:0.72rem; font-weight:600; border:1px solid rgba(255,255,255,0.06); color:rgba(255,255,255,0.4); background:transparent; cursor:pointer; transition:all 0.2s; }
  .filter-btn:hover { border-color:rgba(251,191,36,0.2); color:#fbbf24; }
  .filter-btn.active { background:rgba(251,191,36,0.1); border-color:rgba(251,191,36,0.3); color:#fbbf24; }
  .live-dot { width:8px; height:8px; border-radius:50%; background:#22c55e; animation:livePulse 1.5s ease infinite; }
  @keyframes livePulse { 0%,100%{ box-shadow:0 0 0 0 rgba(34,197,94,0.4); } 50%{ box-shadow:0 0 0 6px rgba(34,197,94,0); } }
  .bar-fill { height:6px; border-radius:99px; transition:width 0.6s cubic-bezier(.4,0,.2,1); }
  .page-link { display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:32px; border-radius:8px; font-size:0.75rem; font-weight:600; border:1px solid rgba(255,255,255,0.06); color:rgba(255,255,255,0.4); background:transparent; cursor:pointer; transition:all 0.2s; text-decoration:none; }
  .page-link:hover { border-color:rgba(251,191,36,0.2); color:#fbbf24; }
  .page-link.active { background:rgba(251,191,36,0.1); border-color:rgba(251,191,36,0.3); color:#fbbf24; }
  .search-input { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:8px 14px; font-size:0.78rem; color:#fff; font-family:inherit; transition:border-color 0.2s; }
  .search-input::placeholder { color:rgba(255,255,255,0.2); }
  .search-input:focus { outline:none; border-color:rgba(251,191,36,0.4); }
  .btn-export { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:10px; font-size:0.72rem; font-weight:700; background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.2); color:#4ade80; cursor:pointer; transition:all 0.2s; text-decoration:none; }
  .btn-export:hover { background:rgba(34,197,94,0.15); border-color:rgba(34,197,94,0.3); }
  .text-shimmer { background:linear-gradient(90deg,#fbbf24,#fde68a,#fbbf24); background-size:200% auto; -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; animation:shimmer 3s linear infinite; }
  @keyframes shimmer { to { background-position:200% center; } }
  .active-item { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; transition:background 0.2s; }
  .active-item:hover { background:rgba(255,255,255,0.02); }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50 px-6 py-3.5">
  <div class="max-w-7xl mx-auto flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center">
        <i class="fa-solid fa-chart-line text-surface-900 text-xs"></i>
      </div>
      <span class="text-base font-extrabold text-white">Visitor <span class="text-gold-400">Analytics</span></span>
    </div>
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2 bg-surface-800 border border-white/[0.06] rounded-xl px-3.5 py-2">
        <div class="live-dot"></div>
        <span id="liveCount" class="text-xs font-bold text-green-400"><?= $active_now ?></span>
        <span class="text-[10px] text-white/30 font-medium">online</span>
      </div>
      <a href="../index.php" class="text-xs font-semibold text-white/40 hover:text-gold-400 transition flex items-center gap-1.5">
        <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Site
      </a>
      <a href="index.php" class="text-xs font-semibold text-white/40 hover:text-gold-400 transition flex items-center gap-1.5">
        <i class="fa-solid fa-gauge text-[10px]"></i> Dashboard
      </a>
    </div>
  </div>
</nav>

<main class="max-w-7xl mx-auto px-6 pt-20 pb-16">

  <!-- PAGE HEADER -->
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8 mt-4">
    <div>
      <h1 class="text-2xl font-extrabold text-white tracking-tight">Visitor <span class="text-shimmer">Insights</span></h1>
      <p class="text-sm text-white/30 mt-1">Track every visitor in real-time</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-export">
        <i class="fa-solid fa-file-csv text-xs"></i> Export CSV
      </a>
      <button onclick="location.reload()" class="filter-btn flex items-center gap-1.5"><i class="fa-solid fa-rotate text-[10px]"></i> Refresh</button>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card" style="--c:rgba(251,191,36,0.06)">
      <div style="position:absolute;inset:0;border-radius:20px;background:linear-gradient(135deg,rgba(251,191,36,0.06),transparent 60%);opacity:0;transition:opacity 0.3s;pointer-events:none" class="group-hover-show"></div>
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-gold-500/10 flex items-center justify-center"><i class="fa-solid fa-users text-gold-400 text-sm"></i></div>
        <p class="text-xs font-bold text-white/40 uppercase tracking-wider">Total Visits</p>
      </div>
      <p class="text-2xl font-extrabold text-white"><?= number_format($total_all) ?></p>
      <p class="text-[10px] text-white/25 mt-1">All time</p>
    </div>
    <div class="stat-card">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center"><i class="fa-solid fa-calendar-day text-green-400 text-sm"></i></div>
        <p class="text-xs font-bold text-white/40 uppercase tracking-wider">Today</p>
      </div>
      <p class="text-2xl font-extrabold text-white"><?= number_format($total_today) ?></p>
      <p class="text-[10px] text-green-400/60 mt-1"><i class="fa-solid fa-user-plus text-[8px] mr-1"></i><?= number_format($unique_today) ?> unique</p>
    </div>
    <div class="stat-card">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center"><i class="fa-solid fa-chart-simple text-blue-400 text-sm"></i></div>
        <p class="text-xs font-bold text-white/40 uppercase tracking-wider">This Week</p>
      </div>
      <p class="text-2xl font-extrabold text-white"><?= number_format($total_week) ?></p>
      <p class="text-[10px] text-white/25 mt-1">Last 7 days</p>
    </div>
    <div class="stat-card">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center"><i class="fa-solid fa-calendar text-purple-400 text-sm"></i></div>
        <p class="text-xs font-bold text-white/40 uppercase tracking-wider">This Month</p>
      </div>
      <p class="text-2xl font-extrabold text-white"><?= number_format($total_month) ?></p>
      <p class="text-[10px] text-white/25 mt-1">Last 30 days</p>
    </div>
  </div>

  <!-- CHART + ACTIVE VISITORS -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- CHART -->
    <div class="panel lg:col-span-2">
      <div class="panel-header">
        <h3 class="text-sm font-bold text-white/70 flex items-center gap-2"><i class="fa-solid fa-chart-area text-gold-400 text-xs"></i> Visitor Trend (30 Days)</h3>
      </div>
      <div class="panel-body" style="height:300px">
        <canvas id="visitorChart"></canvas>
      </div>
    </div>

    <!-- ACTIVE VISITORS LIVE -->
    <div class="panel">
      <div class="panel-header">
        <h3 class="text-sm font-bold text-white/70 flex items-center gap-2">
          <div class="live-dot"></div> Live Now
        </h3>
        <span class="badge badge-green"><?= count($active_list) ?></span>
      </div>
      <div class="panel-body p-2 overflow-y-auto" style="max-height:270px" id="activeList">
        <?php if (empty($active_list)): ?>
          <div class="text-center py-10">
            <i class="fa-solid fa-ghost text-white/5 text-3xl mb-3 block"></i>
            <p class="text-xs text-white/20">No active visitors</p>
          </div>
        <?php else: ?>
          <?php foreach ($active_list as $av): ?>
            <div class="active-item">
              <div class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-circle text-green-400 text-[6px]"></i>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-white/60 truncate"><?= htmlspecialchars($av['ip_address']) ?></p>
                <p class="text-[10px] text-white/25 truncate"><?= htmlspecialchars($av['page_url']) ?></p>
              </div>
              <span class="text-[10px] text-white/20 flex-shrink-0"><?= date('H:i', strtotime($av['last_activity'])) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="panel mb-8">
    <div class="panel-body">
      <form method="GET" class="flex flex-wrap items-center gap-3">
        <input type="hidden" name="page" value="1">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-[10px] font-bold text-white/30 uppercase tracking-wider mr-1">Period:</span>
          <?php foreach (['today'=>'Today','week'=>'7 Days','month'=>'30 Days'] as $k => $v): ?>
            <button type="submit" name="date" value="<?= $k ?>" class="filter-btn <?= $filter_date === $k ? 'active' : '' ?>"><?= $v ?></button>
          <?php endforeach; ?>
          <?php if ($filter_date): ?>
            <a href="?" class="filter-btn" style="color:#f87171;border-color:rgba(239,68,68,0.15)"><i class="fa-solid fa-xmark text-[9px]"></i></a>
          <?php endif; ?>
        </div>
        <div class="w-px h-6 bg-white/5 hidden sm:block"></div>
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-[10px] font-bold text-white/30 uppercase tracking-wider mr-1">Device:</span>
          <?php foreach ([''=>'All','Desktop'=>'Desktop','Mobile'=>'Mobile','Tablet'=>'Tablet'] as $k => $v): ?>
            <button type="submit" name="device" value="<?= $k ?>" class="filter-btn <?= $filter_device === $k ? 'active' : '' ?>"><?= $v ?></button>
          <?php endforeach; ?>
        </div>
        <div class="w-px h-6 bg-white/5 hidden sm:block"></div>
        <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Search IP, country, page..." class="search-input w-48">
        <button type="submit" class="filter-btn active"><i class="fa-solid fa-magnifying-glass text-[10px]"></i></button>
      </form>
    </div>
  </div>

  <!-- BROWSER + DEVICE + OS + COUNTRY -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- BROWSERS -->
    <div class="panel">
      <div class="panel-header"><h3 class="text-xs font-bold text-white/50"><i class="fa-brands fa-chrome text-gold-400 mr-1.5"></i>Browsers</h3></div>
      <div class="panel-body space-y-3">
        <?php $max_br = $browser_stats ? $browser_stats[0]['c'] : 1;
        foreach ($browser_stats as $bs): $pct = round(($bs['c'] / max(1, $total_filtered)) * 100, 1); ?>
          <div>
            <div class="flex justify-between mb-1">
              <span class="text-xs font-semibold text-white/60"><?= htmlspecialchars($bs['browser']) ?></span>
              <span class="text-[10px] text-white/30"><?= $pct ?>%</span>
            </div>
            <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
              <div class="bar-fill bg-gradient-to-r from-gold-400 to-gold-600" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- DEVICES -->
    <div class="panel">
      <div class="panel-header"><h3 class="text-xs font-bold text-white/50"><i class="fa-solid fa-mobile-screen text-blue-400 mr-1.5"></i>Devices</h3></div>
      <div class="panel-body space-y-3">
        <?php $max_dev = $device_stats ? $device_stats[0]['c'] : 1;
        $dev_colors = ['Desktop'=>'from-blue-400 to-blue-600','Mobile'=>'from-green-400 to-green-600','Tablet'=>'from-purple-400 to-purple-600'];
        foreach ($device_stats as $ds): $pct = round(($ds['c'] / max(1, $total_filtered)) * 100, 1); ?>
          <div>
            <div class="flex justify-between mb-1">
              <span class="text-xs font-semibold text-white/60"><?= htmlspecialchars($ds['device_type']) ?></span>
              <span class="text-[10px] text-white/30"><?= number_format($ds['c']) ?> (<?= $pct ?>%)</span>
            </div>
            <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
              <div class="bar-fill bg-gradient-to-r <?= $dev_colors[$ds['device_type']] ?? 'from-gold-400 to-gold-600' ?>" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- OS -->
    <div class="panel">
      <div class="panel-header"><h3 class="text-xs font-bold text-white/50"><i class="fa-brands fa-windows text-purple-400 mr-1.5"></i>Operating Systems</h3></div>
      <div class="panel-body space-y-3">
        <?php foreach ($os_stats as $os): $pct = round(($os['c'] / max(1, $total_filtered)) * 100, 1); ?>
          <div>
            <div class="flex justify-between mb-1">
              <span class="text-xs font-semibold text-white/60 truncate mr-2"><?= htmlspecialchars($os['os']) ?></span>
              <span class="text-[10px] text-white/30 flex-shrink-0"><?= $pct ?>%</span>
            </div>
            <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
              <div class="bar-fill bg-gradient-to-r from-purple-400 to-purple-600" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- COUNTRIES -->
    <div class="panel">
      <div class="panel-header"><h3 class="text-xs font-bold text-white/50"><i class="fa-solid fa-earth-americas text-emerald-400 mr-1.5"></i>Countries</h3></div>
      <div class="panel-body space-y-2.5">
        <?php foreach ($country_stats as $ct): $pct = round(($ct['c'] / max(1, $total_filtered)) * 100, 1); ?>
          <div class="flex items-center gap-2.5">
            <span class="text-sm">🌍</span>
            <div class="min-w-0 flex-1">
              <div class="flex justify-between">
                <span class="text-xs font-semibold text-white/60 truncate"><?= htmlspecialchars($ct['country']) ?></span>
                <span class="text-[10px] text-white/30"><?= number_format($ct['c']) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($country_stats)) echo '<p class="text-xs text-white/20 text-center py-4">No data yet</p>'; ?>
      </div>
    </div>
  </div>

  <!-- TOP PAGES + TOP REFERRERS -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="panel">
      <div class="panel-header"><h3 class="text-xs font-bold text-white/50"><i class="fa-solid fa-file text-gold-400 mr-1.5"></i>Top Pages</h3></div>
      <div class="panel-body p-0">
        <table class="data-table">
          <thead><tr><th>#</th><th>Page</th><th>Visits</th></tr></thead>
          <tbody>
            <?php $tp_max = $top_pages ? $top_pages[0]['c'] : 1;
            foreach ($top_pages as $i => $tp): ?>
              <tr>
                <td class="font-bold text-white/20"><?= $i + 1 ?></td>
                <td><span class="text-white/70 font-medium"><?= htmlspecialchars($tp['page_url']) ?></span></td>
                <td>
                  <div class="flex items-center gap-2">
                    <div class="w-16 h-1.5 bg-white/5 rounded-full overflow-hidden">
                      <div class="bar-fill bg-gold-400" style="width:<?= round(($tp['c']/$tp_max)*100) ?>%"></div>
                    </div>
                    <span class="font-bold text-white/50"><?= number_format($tp['c']) ?></span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header"><h3 class="text-xs font-bold text-white/50"><i class="fa-solid fa-arrow-up-right-from-square text-blue-400 mr-1.5"></i>Top Referrers</h3></div>
      <div class="panel-body p-0">
        <table class="data-table">
          <thead><tr><th>#</th><th>Source</th><th>Visits</th></tr></thead>
          <tbody>
            <?php if (empty($top_referrers)): ?>
              <tr><td colspan="3" class="text-center py-8 text-white/20">No referrer data</td></tr>
            <?php else:
              $tr_max = $top_referrers[0]['c'];
              foreach ($top_referrers as $i => $tr):
                $host = parse_url($tr['referrer'], PHP_URL_HOST);
                $display = $host ?: 'Direct';
              ?>
              <tr>
                <td class="font-bold text-white/20"><?= $i + 1 ?></td>
                <td><span class="text-white/70 font-medium"><?= htmlspecialchars($display) ?></span></td>
                <td>
                  <div class="flex items-center gap-2">
                    <div class="w-16 h-1.5 bg-white/5 rounded-full overflow-hidden">
                      <div class="bar-fill bg-blue-400" style="width:<?= round(($tr['c']/$tr_max)*100) ?>%"></div>
                    </div>
                    <span class="font-bold text-white/50"><?= number_format($tr['c']) ?></span>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- FULL VISITOR LOG -->
  <div class="panel">
    <div class="panel-header">
      <h3 class="text-sm font-bold text-white/70 flex items-center gap-2"><i class="fa-solid fa-list text-gold-400 text-xs"></i> Visitor Log</h3>
      <span class="text-[10px] text-white/25"><?= number_format($total_filtered) ?> records</span>
    </div>
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>IP Address</th>
            <th>Browser</th>
            <th>OS</th>
            <th>Device</th>
            <th>Page</th>
            <th>Country</th>
            <th>Date</th>
            <th>Time</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($log)): ?>
            <tr><td colspan="10" class="text-center py-12 text-white/20"><i class="fa-solid fa-inbox text-2xl mb-3 block text-white/5"></i>No visitors found</td></tr>
          <?php else:
            $device_badges = ['Desktop'=>'badge-blue','Mobile'=>'badge-green','Tablet'=>'badge-purple'];
            foreach ($log as $i => $v): ?>
            <tr>
              <td class="font-bold text-white/15"><?= $total_filtered - ($offset + $i) ?></td>
              <td><span class="font-mono text-xs text-white/70"><?= htmlspecialchars($v['ip_address']) ?></span></td>
              <td><?= htmlspecialchars($v['browser']) ?> <span class="text-white/20"><?= htmlspecialchars($v['browser_version']) ?></span></td>
              <td class="text-white/50"><?= htmlspecialchars($v['os']) ?></td>
              <td><span class="badge <?= $device_badges[$v['device_type']] ?? 'badge-blue' ?>"><?= htmlspecialchars($v['device_type']) ?></span></td>
              <td><span class="text-gold-400/70 text-xs"><?= htmlspecialchars($v['page_url']) ?></span></td>
              <td class="text-white/50"><?= htmlspecialchars($v['country']) ?> <?php if ($v['city'] !== 'Unknown') echo '<span class="text-white/20">' . htmlspecialchars($v['city']) . '</span>'; ?></td>
              <td class="text-white/40"><?= $v['visit_date'] ?></td>
              <td class="text-white/40"><?= date('H:i:s', strtotime($v['visit_time'])) ?></td>
              <td><?= $v['is_unique'] ? '<span class="badge badge-gold">New</span>' : '<span class="badge" style="background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.3)">Return</span>' ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-center gap-2 py-5 border-t border-white/[0.03]">
      <?php if ($page_num > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page_num - 1])) ?>" class="page-link"><i class="fa-solid fa-chevron-left text-[10px]"></i></a>
      <?php endif; ?>
      <?php
        $start = max(1, $page_num - 2); $end = min($total_pages, $page_num + 2);
        if ($start > 1) { echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="page-link">1</a>'; if ($start > 2) echo '<span class="text-white/15 text-xs px-1">...</span>'; }
        for ($p = $start; $p <= $end; $p++) {
          echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $p])) . '" class="page-link ' . ($p === $page_num ? 'active' : '') . '">' . $p . '</a>';
        }
        if ($end < $total_pages) { if ($end < $total_pages - 1) echo '<span class="text-white/15 text-xs px-1">...</span>'; echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '" class="page-link">' . $total_pages . '</a>'; }
      ?>
      <?php if ($page_num < $total_pages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page_num + 1])) ?>" class="page-link"><i class="fa-solid fa-chevron-right text-[10px]"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

</main>

<script>
/* ===== CHART ===== */
var ctx = document.getElementById('visitorChart');
if (ctx) {
  var labels = <?= json_encode(array_column($chart_data, 'label')) ?>;
  var totals = <?= json_encode(array_column($chart_data, 'total')) ?>;
  var uniques = <?= json_encode(array_column($chart_data, 'unique')) ?>;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Total Visits',
          data: totals,
          borderColor: '#fbbf24',
          backgroundColor: 'rgba(251,191,36,0.08)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 0,
          pointHoverRadius: 5,
          pointHoverBackgroundColor: '#fbbf24',
        },
        {
          label: 'Unique',
          data: uniques,
          borderColor: '#22c55e',
          backgroundColor: 'rgba(34,197,94,0.05)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 0,
          pointHoverRadius: 5,
          pointHoverBackgroundColor: '#22c55e',
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: {
          position: 'top',
          align: 'end',
          labels: { color: 'rgba(255,255,255,0.35)', font: { size: 11, family: 'Plus Jakarta Sans', weight: '600' }, boxWidth: 12, boxHeight: 2, padding: 16 }
        },
        tooltip: {
          backgroundColor: '#16161f',
          borderColor: 'rgba(255,255,255,0.08)',
          borderWidth: 1,
          titleColor: 'rgba(255,255,255,0.7)',
          bodyColor: 'rgba(255,255,255,0.4)',
          titleFont: { family: 'Plus Jakarta Sans', weight: '700', size: 12 },
          bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
          padding: 12,
          cornerRadius: 10,
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.02)', drawBorder: false },
          ticks: { color: 'rgba(255,255,255,0.2)', font: { size: 10, family: 'Plus Jakarta Sans' }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.02)', drawBorder: false },
          ticks: { color: 'rgba(255,255,255,0.2)', font: { size: 10, family: 'Plus Jakarta Sans' }, padding: 8 },
          beginAtZero: true
        }
      }
    }
  });
}

/* ===== LIVE REFRESH ===== */
setInterval(function() {
  fetch('../backend/get_active_visitors.php')
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var el = document.getElementById('liveCount');
      if (el) el.textContent = d.active;
    });
}, 5000);
</script>

</body>
</html>