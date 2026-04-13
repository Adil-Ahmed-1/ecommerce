<?php
include("../config/db.php");

/* PAGINATION */
 $limit = 5;
 $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
 $start = ($page - 1) * $limit;

/* TOTAL */
 $total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM categories");
 $total_row = mysqli_fetch_assoc($total_result);
 $total_pages = ceil($total_row['total'] / $limit);

/* DATA — resolve parent name via LEFT JOIN */
 $query = "
    SELECT c.*, p.category_name AS parent_name
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY c.id DESC
    LIMIT $start, $limit
";
 $result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Categories</title>

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

  /* Table row hover */
  .trow {
    transition:background 0.2s ease;
  }
  .trow:hover {
    background:rgba(58,205,126,0.04);
  }
  .dark .trow:hover {
    background:rgba(58,205,126,0.06);
  }

  /* Action buttons */
  .act-btn {
    width:32px;height:32px;border-radius:8px;
    display:inline-flex;align-items:center;justify-content:center;
    transition:all 0.2s ease;font-size:12px;
  }
  .act-btn:hover { transform:translateY(-1px); }

  /* Pagination */
  .pg-btn {
    min-width:36px;height:36px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:0.8rem;font-weight:600;
    transition:all 0.2s cubic-bezier(.4,0,.2,1);
    border:1px solid transparent;
  }
  .pg-btn:hover:not(.pg-active):not(:disabled) {
    background:rgba(58,205,126,0.08);
    border-color:rgba(58,205,126,0.2);
    color:#16b364;
  }
  .pg-active {
    background:linear-gradient(135deg,#16b364,#0a9150);
    color:#fff !important;
    box-shadow:0 4px 12px -3px rgba(22,179,100,0.4);
  }
  .pg-btn:disabled {
    opacity:0.3;cursor:not-allowed;
  }

  /* Fade in */
  .fade-up {
    opacity:0;transform:translateY(16px);
    animation:fadeUp 0.5s cubic-bezier(.4,0,.2,1) forwards;
  }
  @keyframes fadeUp { to { opacity:1;transform:translateY(0); } }

  /* Dropdown */
  .dropdown-enter { animation:dropIn 0.2s cubic-bezier(.4,0,.2,1) forwards; }
  @keyframes dropIn {
    from { opacity:0;transform:translateY(-8px) scale(0.96); }
    to { opacity:1;transform:translateY(0) scale(1); }
  }

  /* Search input */
  .search-input:focus {
    border-color:#3acd7e;
    box-shadow:0 0 0 3px rgba(58,205,126,0.1);
    outline:none;
  }

  /* Status dot */
  .status-dot {
    width:7px;height:7px;border-radius:50%;
    display:inline-block;margin-right:6px;
  }
  .status-dot.active { background:#3acd7e; box-shadow:0 0 6px rgba(58,205,126,0.5); }
  .status-dot.inactive { background:#ef4444; box-shadow:0 0 6px rgba(239,68,68,0.3); }

  /* Delete confirm modal */
  .modal-overlay {
    position:fixed;inset:0;z-index:9999;
    background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);
    display:flex;align-items:center;justify-content:center;
    opacity:0;pointer-events:none;
    transition:opacity 0.25s ease;
  }
  .modal-overlay.show { opacity:1;pointer-events:auto; }
  .modal-box {
    transform:scale(0.92);transition:transform 0.25s cubic-bezier(.4,0,.2,1);
  }
  .modal-overlay.show .modal-box { transform:scale(1); }
</style>

</head>

<body class="bg-[#f4f6f8] dark:bg-[#0a0f0d] transition-colors duration-500 min-h-screen">

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="modal-overlay">
  <div class="modal-box bg-white dark:bg-[#151d19] rounded-2xl border border-gray-200 dark:border-white/10 shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
    <div class="w-14 h-14 rounded-2xl bg-red-50 dark:bg-red-950 flex items-center justify-center mx-auto mb-4">
      <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl"></i>
    </div>
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Delete Category?</h3>
    <p class="text-sm text-gray-500 mt-2 leading-relaxed">This action cannot be undone. All products in this category may be affected.</p>
    <div class="flex items-center gap-3 mt-6">
      <button onclick="closeDeleteModal()" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-sm font-semibold text-gray-600 dark:text-white/60 hover:bg-gray-50 dark:hover:bg-white/5 transition">
        Cancel
      </button>
      <a id="deleteLink" href="#" class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold flex items-center justify-center gap-2 transition">
        <i class="fa-solid fa-trash-can text-xs"></i> Delete
      </a>
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
    <button onclick="toggleSidebar()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-sm">
      <i class="fa-solid fa-bars text-xs"></i>
    </button>
  </div>

  <div class="px-5 py-4 flex items-center gap-3 border-t border-white/10">
    <img src="../uploads/about.png" class="sidebar-avatar w-10 h-10 rounded-xl object-cover border-2 border-brand-400/40 transition-all duration-300 shrink-0">
    <div class="sidebar-text transition-all duration-300">
      <p class="text-sm font-semibold leading-tight">Adil Khoso</p>
      <p class="text-[11px] text-white/50 mt-0.5">Super Admin</p>
    </div>
  </div>

  <nav class="flex-1 mt-2 px-3 space-y-1 overflow-y-auto">

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mb-2 transition-all duration-300">Main</p>
    <a href="../index.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-grid-2 w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Dashboard</span>
    </a>

    <p class="sidebar-text text-[10px] uppercase tracking-widest text-white/30 font-semibold px-3 mt-5 mb-2 transition-all duration-300">Manage</p>
    <a href="add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-folder-plus w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Category</span>
    </a>
    <a href="view.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium">
      <i class="fa-solid fa-layer-group w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">View Categories</span>
    </a>
    <a href="../product/add.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-box-open w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">Add Product</span>
    </a>
    <a href="../product/view.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:text-white">
      <i class="fa-solid fa-boxes-stacked w-5 text-center text-[13px]"></i>
      <span class="sidebar-text transition-all duration-300">View Products</span>
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
      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">View Categories</h1>
      <p class="text-xs text-gray-400 mt-0.5"><?= $total_row['total'] ?> total categories</p>
    </div>

    <div class="flex items-center gap-3">

      <button onclick="toggleDark()" id="darkBtn" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 flex items-center justify-center transition text-gray-600 dark:text-white/70">
        <i class="fa-solid fa-moon text-sm"></i>
      </button>

      <div class="relative">
        <button onclick="toggleMenu()" class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 transition">
          <img src="../uploads/about.png" class="w-8 h-8 rounded-lg object-cover">
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

    <!-- BREADCRUMB -->
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5 fade-up">
      <a href="../index.php" class="hover:text-brand-500 transition">Dashboard</a>
      <i class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-gray-700"></i>
      <span class="text-gray-700 dark:text-white font-medium">All Categories</span>
    </div>

    <!-- TOOLBAR -->
    <div class="fade-up flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">

      <!-- Search -->
      <div class="relative w-full sm:w-72">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        <input
          type="text" id="searchInput"
          class="search-input w-full bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-sm text-gray-800 dark:text-white placeholder:text-gray-400 transition"
          placeholder="Search categories..."
          oninput="filterTable()"
        >
      </div>

      <!-- Add button -->
      <a href="add.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-500/20 shrink-0">
        <i class="fa-solid fa-plus text-xs"></i>
        Add Category
      </a>

    </div>

    <!-- TABLE CARD -->
    <div class="fade-up bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm overflow-hidden" style="animation-delay:0.08s">

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm" id="catTable">
          <thead>
            <tr class="border-b border-gray-100 dark:border-white/5">
              <th class="text-left px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">ID</th>
              <th class="text-left px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Category Name</th>
              <th class="text-left px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Parent</th>
              <th class="text-left px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Description</th>
              <th class="text-left px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Status</th>
              <th class="text-right px-6 py-4 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>

          <tbody id="tableBody">
            <?php if (mysqli_num_rows($result) > 0) { ?>
              <?php while ($row = mysqli_fetch_assoc($result)) {
                $isActive = ($row['status'] ?? 'active') === 'active';
                $parentName = !empty($row['parent_id']) ? ($row['parent_name'] ?? 'Unknown') : null;
              ?>
              <tr class="trow border-b border-gray-50 dark:border-white/[0.03]">
                <td class="px-6 py-4">
                  <span class="text-xs font-mono text-gray-400 bg-gray-100 dark:bg-white/5 px-2 py-1 rounded-md">#<?= $row['id'] ?></span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center shrink-0">
                      <i class="fa-solid fa-folder text-brand-500 text-xs"></i>
                    </div>
                    <span class="font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($row['category_name']) ?></span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <?php if ($parentName) { ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950 px-2.5 py-1 rounded-lg">
                      <i class="fa-solid fa-link text-[9px]"></i>
                      <?= htmlspecialchars($parentName) ?>
                    </span>
                  <?php } else { ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-400 bg-gray-50 dark:bg-white/[0.03] px-2.5 py-1 rounded-lg">
                      <i class="fa-solid fa-crown text-[9px]"></i>
                      Main
                    </span>
                  <?php } ?>
                </td>
                <td class="px-6 py-4 hidden lg:table-cell">
                  <p class="text-gray-500 dark:text-white/50 text-xs leading-relaxed max-w-[220px] truncate">
                    <?= htmlspecialchars($row['description'] ?? 'No description') ?>
                  </p>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-lg
                    <?= $isActive
                      ? 'text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-950'
                      : 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950'
                    ?>">
                    <span class="status-dot <?= $isActive ? 'active' : 'inactive' ?>"></span>
                    <?= $isActive ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <a href="../product/category_products.php?cat_id=<?= $row['id'] ?>"
                       class="act-btn bg-sky-50 dark:bg-sky-950 text-sky-500 hover:bg-sky-100 dark:hover:bg-sky-900" title="View Products">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="edit.php?id=<?= $row['id'] ?>"
                       class="act-btn bg-amber-50 dark:bg-amber-950 text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900" title="Edit">
                      <i class="fa-solid fa-pen"></i>
                    </a>
                    <button onclick="openDeleteModal('delete.php?id=<?= $row['id'] ?>')"
                       class="act-btn bg-red-50 dark:bg-red-950 text-red-500 hover:bg-red-100 dark:hover:bg-red-900" title="Delete">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php } ?>
            <?php } else { ?>
              <!-- Empty State -->
              <tr>
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mb-4">
                      <i class="fa-solid fa-folder-open text-gray-300 dark:text-gray-700 text-2xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-400 dark:text-white/40">No categories found</p>
                    <p class="text-xs text-gray-300 dark:text-gray-700 mt-1">Start by adding your first category</p>
                    <a href="add.php" class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-brand-500 hover:text-brand-600 transition">
                      <i class="fa-solid fa-plus text-[10px]"></i> Add Category
                    </a>
                  </div>
                </td>
              </tr>
            <?php } ?>
          </tbody>

        </table>
      </div>

      <!-- Table Footer -->
      <?php if ($total_row['total'] > 0) { ?>
      <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-white/5 gap-3">
        <p class="text-xs text-gray-400">
          Showing <span class="font-semibold text-gray-600 dark:text-white/60"><?= $start + 1 ?></span> to
          <span class="font-semibold text-gray-600 dark:text-white/60"><?= min($start + $limit, $total_row['total']) ?></span> of
          <span class="font-semibold text-gray-600 dark:text-white/60"><?= $total_row['total'] ?></span> results
        </p>

        <!-- PAGINATION -->
        <div class="flex items-center gap-1.5">
          <button class="pg-btn text-gray-500 dark:text-white/40" <?= $page <= 1 ? 'disabled' : '' ?> onclick="window.location.href='?page=<?= $page - 1 ?>'">
            <i class="fa-solid fa-chevron-left text-[10px]"></i>
          </button>

          <?php
            /* Smart pagination — show first, last, and neighbors */
            $range = 2;
            $pages_html = '';
            for ($i = 1; $i <= $total_pages; $i++) {
              if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                $pages_html .= '<a href="?page='.$i.'" class="pg-btn text-gray-600 dark:text-white/60 '.($page == $i ? 'pg-active' : '').'">'.$i.'</a>';
              } elseif (
                ($i == $page - $range - 1 && $i > 1) ||
                ($i == $page + $range + 1 && $i < $total_pages)
              ) {
                $pages_html .= '<span class="px-1 text-gray-300 dark:text-gray-700 text-xs">...</span>';
              }
            }
            echo $pages_html;
          ?>

          <button class="pg-btn text-gray-500 dark:text-white/40" <?= $page >= $total_pages ? 'disabled' : '' ?> onclick="window.location.href='?page=<?= $page + 1 ?>'">
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
          </button>
        </div>
      </div>
      <?php } ?>

    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 fade-up" style="animation-delay:0.15s">

      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-layer-group text-brand-500"></i>
        </div>
        <div>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $total_row['total'] ?></p>
          <p class="text-xs text-gray-400 mt-0.5">Total Categories</p>
        </div>
      </div>

      <?php
        $active_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM categories WHERE status='active'"))['c'];
        $inactive_count = $total_row['total'] - $active_count;
      ?>
      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-circle-check text-brand-500"></i>
        </div>
        <div>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $active_count ?></p>
          <p class="text-xs text-gray-400 mt-0.5">Active</p>
        </div>
      </div>

      <div class="bg-white dark:bg-[#131a16] rounded-2xl border border-gray-100 dark:border-white/5 p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-red-50 dark:bg-red-950 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-circle-xmark text-red-500"></i>
        </div>
        <div>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= $inactive_count ?></p>
          <p class="text-xs text-gray-400 mt-0.5">Inactive</p>
        </div>
      </div>

    </div>

  </div>

</main>

<!-- ========== SCRIPTS ========== -->
<script>

/* Sidebar Toggle */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const main = document.getElementById('main');
  const collapsed = sidebar.classList.toggle('sidebar-collapsed');
  sidebar.style.width = collapsed ? '78px' : '260px';
  main.style.marginLeft = collapsed ? '78px' : '260px';
}

/* Dark Mode */
function toggleDark() {
  const html = document.documentElement;
  const body = document.body;
  const btn = document.getElementById('darkBtn');
  const isDark = body.classList.toggle('dark');
  html.classList.toggle('dark', isDark);
  btn.innerHTML = isDark
    ? '<i class="fa-solid fa-sun text-sm"></i>'
    : '<i class="fa-solid fa-moon text-sm"></i>';
}

/* Profile Menu */
function toggleMenu() {
  document.getElementById('menu').classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
  const menu = document.getElementById('menu');
  if (!e.target.closest('.relative') && !menu.classList.contains('hidden')) {
    menu.classList.add('hidden');
  }
});

/* Delete Modal */
function openDeleteModal(url) {
  document.getElementById('deleteLink').href = url;
  document.getElementById('deleteModal').classList.add('show');
}
function closeDeleteModal() {
  document.getElementById('deleteModal').classList.remove('show');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeDeleteModal();
});

/* Client-side Search Filter */
function filterTable() {
  const query = document.getElementById('searchInput').value.toLowerCase();
  const rows = document.querySelectorAll('#tableBody tr');
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(query) ? '' : 'none';
  });
}

</script>

</body>
</html>