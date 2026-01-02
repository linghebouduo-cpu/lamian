<?php
// 🔥 薪資管理.php - 只有 A 級（老闆）可以訪問

require_once __DIR__ . '/includes/auth_check.php';

// 只有 A 級（老闆）可以訪問
if (!check_user_level('A', false)) {
    show_no_permission_page(); // 會 exit
}

// 取得用戶資訊
$user      = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '薪資管理 - 員工管理系統';

// API 路徑（沿用你原本設定）
$API_BASE_URL  = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

  <!-- 保留你原本用的 CSS 引用 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    /* ====== 整體風格：跟 日報表記錄 / 員工資料表 一樣 ====== */
    :root {
      --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 30%, #f5e9ff 100%);
      --text-main: #0f172a;
      --text-subtle: #64748b;

      --card-bg: rgba(255, 255, 255, 0.96);
      --card-radius: 22px;

      --shadow-soft: 0 18px 45px rgba(15, 23, 42, 0.12);
      --shadow-hover: 0 22px 60px rgba(15, 23, 42, 0.18);

      --transition-main: all .25s cubic-bezier(.4, 0, .2, 1);
    }

    * {
      transition: var(--transition-main);
    }

    body {
      min-height: 100vh;
      background:
        radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.24), transparent 55%),
        radial-gradient(circle at 100% 0%, rgba(222, 114, 244, 0.24), transparent 55%),
        var(--bg-gradient);
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--text-main);
    }

    /* ====== Top navbar：藍色漸層（和 日報表記錄 一樣） ====== */
    .sb-topnav {
      background: linear-gradient(120deg, #1e3a8a, #3658ff) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.35);
      box-shadow: 0 14px 35px rgba(15, 23, 42, 0.42);
      backdrop-filter: blur(18px);
    }

    .navbar-brand {
      font-weight: 800;
      letter-spacing: .14em;
      text-transform: uppercase;
      color: #f9fafb !important;
    }

    .navbar-nav .nav-link {
      color: #e5e7eb !important;
    }

    .navbar-nav .nav-link:hover {
      color: #ffffff !important;
    }

    .container-fluid {
      padding: 26px 28px;
    }

    /* ====== Sidebar：與 日報表記錄 相同 ====== */
    .sb-sidenav {
      background:
        radial-gradient(circle at 40% 0%, rgba(56, 189, 248, 0.38), transparent 65%),
        radial-gradient(circle at 80% 100%, rgba(147, 197, 253, 0.34), transparent 70%),
        linear-gradient(180deg, rgba(220, 235, 255, 0.92), rgba(185, 205, 255, 0.9));
      backdrop-filter: blur(22px);
      border-right: 1px solid rgba(255, 255, 255, 0.55);
    }

    .sb-sidenav-menu-heading {
      color: #1e293b !important;
      opacity: 0.75;
      font-size: 0.78rem;
      letter-spacing: .18em;
      margin: 20px 0 8px 16px;
    }

    .sb-sidenav .nav-link {
      color: #0f172a !important;
      font-weight: 600;
      border-radius: 18px;
      padding: 12px 18px;
      margin: 8px 12px;
      border: 2px solid rgba(255, 255, 255, 0.9);
      background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.80),
        rgba(241, 248, 255, 0.95)
      );
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .sb-sidenav .nav-link .sb-nav-link-icon {
      margin-right: 10px;
      color: #1e293b !important;
      opacity: 0.9 !important;
      font-size: 1.05rem;
    }

    .sb-sidenav .sb-sidenav-collapse-arrow i,
    .sb-sidenav .nav-link i.fa-chevron-right {
      color: #1e293b !important;
      opacity: 0.85 !important;
    }

    .sb-sidenav .nav-link:hover {
      border-color: rgba(255, 255, 255, 1);
      box-shadow: 0 14px 30px rgba(59, 130, 246, 0.4);
      transform: translateY(-1px);
    }

    .sb-sidenav .nav-link:hover .sb-nav-link-icon,
    .sb-sidenav .nav-link:hover .sb-sidenav-collapse-arrow i,
    .sb-sidenav .nav-link:hover i.fa-chevron-right {
      color: #0f172a !important;
      opacity: 1 !important;
    }

    .sb-sidenav .nav-link.active {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      border-color: rgba(255, 255, 255, 0.98);
      color: #ffffff !important;
      box-shadow: 0 18px 36px rgba(59, 130, 246, 0.6);
    }

    .sb-sidenav .nav-link.active .sb-nav-link-icon,
    .sb-sidenav .nav-link.active .sb-sidenav-collapse-arrow i {
      color: #e0f2fe !important;
    }

    .sb-sidenav-footer {
      background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.9),
        rgba(226, 232, 255, 0.95)
      ) !important;
      backdrop-filter: blur(16px);
      border-top: 1px solid rgba(148, 163, 184, 0.5);
      padding: 16px 20px;
      color: #111827 !important;
      box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.10);
      font-size: 0.95rem;
    }

    .sb-sidenav-footer .small {
      color: #6b7280 !important;
    }

    /* 修正側欄箭頭顏色 */
    .sb-sidenav .nav-link svg,
    .sb-sidenav .nav-link svg path,
    .sb-sidenav .nav-link i,
    .sb-sidenav .nav-link::after {
      stroke: #1e293b !important;
      color: #1e293b !important;
      fill: #1e293b !important;
      opacity: 0.9 !important;
    }
    .sb-sidenav .nav-link:hover svg,
    .sb-sidenav .nav-link:hover svg path,
    .sb-sidenav .nav-link:hover i,
    .sb-sidenav .nav-link:hover::after {
      stroke: #0f172a !important;
      color: #0f172a !important;
      fill: #0f172a !important;
      opacity: 1 !important;
    }

    /* ====== 標題 & 麵包屑 ====== */
    h1 {
      font-size: 2rem;
      font-weight: 800;
      letter-spacing: .04em;
      background: linear-gradient(120deg, #0f172a, #2563eb);
      -webkit-background-clip: text;
      color: transparent;
      margin-bottom: 8px;
    }

    .breadcrumb {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 999px;
      padding: 6px 14px;
      font-size: 0.8rem;
      border: 1px solid rgba(148, 163, 184, 0.4);
    }

    .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
      color: #9ca3af;
    }

    /* ====== 卡片 / 表格 ====== */
    .card {
      background: var(--card-bg);
      border-radius: var(--card-radius);
      border: 1px solid rgba(226, 232, 240, 0.95);
      box-shadow: var(--shadow-soft);
    }

    .card-header {
      background: linear-gradient(135deg, rgba(248, 250, 252, 0.96), rgba(239, 246, 255, 0.96));
      border-bottom: 1px solid rgba(226, 232, 240, 0.95);
      font-weight: 600;
      font-size: 0.95rem;
      padding-top: 14px;
      padding-bottom: 10px;
    }

    .card-body {
      padding: 18px 20px 20px;
    }

    .table {
      border-radius: var(--card-radius);
      overflow: hidden;
      background: #fff;
    }

    .table thead th {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: #fff;
      border: none;
      font-weight: 600;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
      padding: 12px 10px;
    }
    .table tbody td {
      text-align: center;
      vertical-align: middle;
      white-space: nowrap;
      padding: 12px 10px;
      border-color: rgba(148, 163, 184, .25);
    }
    .table tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
    }

    footer {
      background: transparent;
      border-top: 1px solid rgba(148, 163, 184, 0.35);
      margin-top: 24px;
      padding-top: 14px;
      font-size: 0.8rem;
      color: var(--text-subtle);
    }

    @media (max-width: 992px) {
      .container-fluid {
        padding: 20px 16px;
      }
    }

    @media (max-width: 768px) {
      .container-fluid {
        padding: 16px 12px;
      }
      h1 {
        font-size: 1.6rem;
      }
    }

    /* ====== KPI 四張統計卡（套在薪資統計上） ====== */
    .kpi-card {
      border-radius: 26px;
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.10);
      overflow: hidden;
      position: relative;
    }
    .kpi-card .card-body {
      position: relative;
      z-index: 1;
    }
    .kpi-card::after {
      content: '';
      position: absolute;
      right: -80px;
      bottom: -80px;
      width: 260px;
      height: 180px;
      border-radius: 55% 0 0 0;
      background: radial-gradient(circle at 0 0, #e5e7eb, transparent 60%);
      opacity: 0.9;
    }
    .kpi-card .icon-pill {
      width: 46px;
      height: 46px;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      box-shadow: 0 10px 25px rgba(15,23,42,0.16);
      background: rgba(255,255,255,0.9);
    }

    .kpi-primary {
      background: linear-gradient(135deg, #acc6f6ff, #818cf859) !important;
    }
    .kpi-success {
      background: linear-gradient(135deg, #b1f9caff, #22c55e4d) !important;
    }
    .kpi-warning {
      background: linear-gradient(135deg, #faebaeff, #facc154d) !important;
    }
    .kpi-info {
      background: linear-gradient(135deg, #bce4ffff, #38bdf84d) !important;
    }

    /* ====== Chip 風格按鈕（查詢 / 清除 / 匯出）====== */
    .btn-chip {
      --h: 40px;
      --px: 14px;
      height: var(--h);
      padding: 0 var(--px);
      border-radius: 999px;
      border: 1px solid transparent;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      font-weight: 600;
      letter-spacing: .02em;
      box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
      transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
      font-size: 0.9rem;
      white-space: nowrap;
    }
    .btn-chip .ic {
      font-size: 15px;
      line-height: 1;
    }
    .btn-chip .tx {
      line-height: 1;
    }
    .btn-chip:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(15, 23, 42, .12);
    }
    .btn-chip:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
    }

    .btn-primary-lite {
      background: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%);
      color: #fff;
      border-color: rgba(59, 130, 246, .25);
    }
    .btn-primary-lite:hover {
      filter: brightness(1.03);
    }

    .btn-ghost {
      background: #ffffff;
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, .35);
    }
    .btn-ghost:hover {
      background: #eff6ff;
    }

    .btn-success-lite {
      background: linear-gradient(135deg, #34d399 0%, #22c55e 100%);
      color: #fff;
      border-color: rgba(34, 197, 94, .25);
    }

    @media (max-width: 576px) {
      .btn-chip { --h: 38px; --px: 12px; }
      .btn-chip .tx { display: none; }
    }

    /* ====== 薪資頁專用的小樣式（原本就有的東西） ====== */
    .badge-paytype { font-size:.75rem; }
    .diff-pill{ font-size:.75rem; }
    .readonly-like{ background:#f8fafc; }
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- 上方 Navbar（結構跟日報表記錄一樣） -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
      <i class="fas fa-bars"></i>
    </button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
           data-bs-toggle="dropdown" aria-expanded="false">
          <img class="user-avatar rounded-circle me-1"
               src="https://i.pravatar.cc/40?u=<?php echo urlencode($userName); ?>"
               width="28" height="28" alt="User Avatar" style="vertical-align:middle;">
          <span id="navUserName"><?php echo htmlspecialchars($userName); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
          <li><a class="dropdown-item" href="帳號設置.php">帳號設置</a></li>
          <li><hr class="dropdown-divider" /></li>
          <li><a class="dropdown-item" href="logout.php"><i class="fas fa-right-from-bracket me-2"></i>登出</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <div id="layoutSidenav">
    <!-- Sidebar：結構跟日報表記錄一樣，改 active 在「薪資管理」 -->
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="true">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse show" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav">
                <a class="nav-link" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
                <a class="nav-link" href="假別管理.php">假別管理</a>
                <a class="nav-link" href="打卡管理.php">打卡管理</a>
                <a class="nav-link active" href="薪資管理.php">薪資管理</a>
              </nav>
            </div>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseOperation" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseInventory" aria-expanded="false">
                  庫存管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="operationCollapseInventory" data-bs-parent="#sidenavAccordionOperation">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="庫存查詢.php">庫存查詢</a>
                    <a class="nav-link" href="庫存調整.php">庫存調整</a>
                    <a class="nav-link" href="商品管理.php">商品管理</a>
                  </nav>
                </div>

                <a class="nav-link" href="日報表.php">
                  <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表
                </a>
              </nav>
            </div>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseWebsite" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>網站管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseWebsite" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionWebsite">
                <a class="nav-link" href="layout-static.php">官網資料修改</a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#websiteCollapseMember" aria-expanded="false">
                  會員管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="websiteCollapseMember" data-bs-parent="#sidenavAccordionWebsite">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="member-list.php">會員清單</a>
                    <a class="nav-link" href="member-detail.php">詳細資料頁</a>
                    <a class="nav-link" href="point-manage.php">點數管理</a>
                  </nav>
                </div>
              </nav>
            </div>

            <div class="sb-sidenav-menu-heading">Addons</div>
            <a class="nav-link" href="charts.php">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts
            </a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:</div>
          <span id="loggedAs"><?php echo htmlspecialchars($userName); ?></span>
        </div>
      </nav>
    </div>

    <!-- 主要內容：換成薪資管理的內容 -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1>薪資管理</h1>
  <div class="text-muted">
    <i class="fas fa-calendar-alt me-2"></i>
    <span id="currentDateHeader"></span>
  </div>
</div>


          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">薪資管理</li>
          </ol>

          <div id="loadingIndicator" class="text-center my-4 d-none">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <div id="errorAlert" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage"></span>
          </div>

          <!-- 四張統計卡：改用 kpi-card 風格 -->
          <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-primary">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">員工數</div>
                      <div class="h5" id="summary_employees">0</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-users"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-success">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">總薪資</div>
                      <div class="h5" id="summary_total_payroll">0</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-dollar-sign"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-warning">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">總獎金</div>
                      <div class="h5" id="summary_total_bonus">0</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-gift"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
              <div class="card kpi-card kpi-info">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="small text-muted">總扣款</div>
                      <div class="h5" id="summary_total_deductions">0</div>
                    </div>
                    <div class="icon-pill">
                      <i class="fas fa-minus-circle"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 篩選條件卡片 -->
          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-filter me-2"></i>篩選條件</div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">發薪月份</label>
                  <input type="month" class="form-control" id="monthPicker">
                </div>
                <div class="col-md-4">
                  <label class="form-label">關鍵字搜尋</label>
                  <input type="text" class="form-control" id="keyword" placeholder="員工ID或姓名">
                </div>
                <!-- 🔥 三顆按鈕只改外觀，ID / JS 不動 -->
                <div class="col-md-5 d-flex align-items-end justify-content-end flex-wrap gap-2">
<button id="btnFilter" class="btn btn-chip btn-primary-lite" type="button" onclick="filterSalaries()">
  <i class="ic fas fa-search"></i><span class="tx">查詢</span>
</button>

<button id="btnClear" class="btn btn-chip btn-ghost" type="button" onclick="clearFilters()">
  <i class="ic fas fa-eraser"></i><span class="tx">清除</span>
</button>

</div>
</div>

              <div class="mt-2 small text-muted">
                <i class="fas fa-info-circle me-1"></i>今日日期：<span id="currentDate"></span>
              </div>
            </div>
          </div>

          <!-- 薪資記錄表格 -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="fas fa-table me-2"></i>薪資記錄</div>
    <button id="btnExport" class="btn btn-chip btn-success-lite btn-sm" type="button">
      <i class="ic fas fa-file-excel"></i><span class="tx">匯出 Excel</span>
    </button>
  </div>
  <div class="card-body">

              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                  <thead>
                    <tr>
                      <th>員工ID</th>
                      <th>姓名</th>
                      <th>月份</th>
                      <th>薪資類型</th>
                      <th>底薪/時薪</th>
                      <th>本月工時</th>
                      <th>獎金</th>
                      <th>扣款</th>
                      <th>實領</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody id="salaryTableBody">
                    <tr id="noDataRow" class="d-none">
                      <td colspan="10" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center" id="pagination"></ul>
              </nav>
            </div>
          </div>

        </div>
      </main>

      <!-- 詳細 Modal：原本內容保留 -->
      <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>薪資詳情</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody"></div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
          </div>
        </div>
      </div>

      <!-- 編輯 Modal：原本內容保留，只是樣式跟全站一致 -->
      <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form id="editForm" onsubmit="return submitEdit(event)">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen-to-square me-2"></i>編輯薪資</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" id="edit_user_id">

                <div class="mb-2">
                  <label class="form-label">員工姓名</label>
                  <input type="text" class="form-control readonly-like" id="edit_name" readonly>
                </div>

                <div class="mb-2">
                  <label class="form-label">發薪月份</label>
                  <input type="month" class="form-control readonly-like" id="edit_month" readonly>
                </div>

                <div class="mb-2">
                  <label class="form-label">薪資類型</label>
                  <div class="d-flex gap-3">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="paytype" id="paytype_monthly" value="monthly" disabled>
                      <label class="form-check-label" for="paytype_monthly">月薪</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="paytype" id="paytype_hourly" value="hourly" disabled>
                      <label class="form-check-label" for="paytype_hourly">時薪</label>
                    </div>
                  </div>
                </div>

                <div class="row g-2">
                  <div class="col-6" id="baseSalaryWrap">
                    <label class="form-label">底薪</label>
                    <input type="number" class="form-control readonly-like" id="edit_base_salary" readonly>
                  </div>
                  <div class="col-6" id="hourlyRateWrap">
                    <label class="form-label">時薪</label>
                    <input type="number" class="form-control readonly-like" id="edit_hourly_rate" readonly>
                  </div>
                </div>

                <div class="row g-2 mt-2">
                  <div class="col-6">
                    <label class="form-label">本月工時</label>
                    <input type="number" step="0.01" class="form-control editable-field" id="edit_working_hours">
                  </div>
                  <div class="col-6">
                    <label class="form-label">計算底薪(自動)</label>
                    <input type="text" class="form-control readonly-like" id="edit_calc_basepay" readonly>
                  </div>
                </div>

                <div class="row g-2 mt-2">
                  <div class="col-6">
                    <label class="form-label">獎金</label>
                    <input type="number" class="form-control editable-field" id="edit_bonus" value="0">
                  </div>
                  <div class="col-6">
                    <label class="form-label">扣款</label>
                    <input type="number" class="form-control editable-field" id="edit_deductions" value="0">
                  </div>
                </div>

                <div class="mt-3">
                  <div class="alert alert-light mb-0">
                    <div><strong>實領(自動):</strong> <span id="edit_total_salary">0</span></div>
                    <small class="text-muted">
                      公式：實領 = 計算底薪 + 獎金 - 扣款；
                      計算底薪 =（月薪制：底薪）／（時薪制：時薪 × 工時）
                    </small>
                  </div>
                </div>

              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetEditBtn">恢復原始</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button class="btn btn-primary" type="submit">儲存</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; Xxing0625</div>
            <div>
              <a href="#">Privacy Policy</a> &middot; <a href="#">Terms &amp; Conditions</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- JS 區：完全照你原本的邏輯保留 -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="js/scripts.js"></script>

  <script>
    const API_BASE = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
  </script>

<script src="薪資管理.js?v=<?= filemtime(__DIR__ . '/薪資管理.js') ?>"></script>


  <script>
    // 頁面載入完成後
    document.addEventListener('DOMContentLoaded', () => {
      // 1. 載入登入者資訊＋頭像
      loadLoggedInUser();

      // 2. 顯示今日日期（上方 H1 右邊 + 篩選卡片內）
      const now = new Date();
      const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      };
      const dateText = now.toLocaleDateString('zh-TW', options);

      const elMain   = document.getElementById('currentDate');        // 篩選卡片那行「今日日期：」
      const elHeader = document.getElementById('currentDateHeader');  // 標題右邊的小日期

      if (elMain)   elMain.textContent   = dateText;
      if (elHeader) elHeader.textContent = dateText;
    });

    // 從日報表記錄沿用邏輯：載入登入者資訊與頭像
    async function loadLoggedInUser() {
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      const userId   = <?php echo json_encode($userId,   JSON_UNESCAPED_UNICODE); ?>;

      console.log('✅ 薪資管理 已登入:', userName, 'ID:', userId);

      // Sidenav footer「Logged in as」
      const loggedAsEl = document.getElementById('loggedAs');
      if (loggedAsEl) loggedAsEl.textContent = userName;

      // Navbar 使用者名稱
      const navName = document.getElementById('navUserName');
      if (navName) navName.textContent = userName;

      // 從 me.php 載入真實頭像
      try {
        const r = await fetch(API_BASE + '/me.php', { credentials: 'include' });
        if (r.ok) {
          const data = await r.json();
          if (data.avatar_url) {
            const avatarUrl = data.avatar_url + (data.avatar_url.includes('?') ? '&' : '?') + 'v=' + Date.now();
            const avatar = document.querySelector('.navbar .user-avatar');
            if (avatar) {
              avatar.src = avatarUrl;
              console.log('✅ 頭像已更新:', avatarUrl);
            }
          }
        }
      } catch (e) {
        console.warn('載入頭像失敗:', e);
      }
    }

    // 折起/展開側欄（跟日報表記錄同一套）
    document.getElementById('sidebarToggle')?.addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });
     // ✅ 匯出 Excel（只補這個功能，不動其他）
  (function bindExportExcel(){
    const btn = document.getElementById('btnExport');
    if(!btn) return;

    btn.addEventListener('click', () => {
      try {
        const table = document.querySelector('table'); // 你頁面只有一張薪資表
        const tbody = document.getElementById('salaryTableBody');
        if(!table || !tbody) return alert('找不到薪資表格，無法匯出');

        // 只匯出「有資料」的列（避開 noDataRow）
        const rows = Array.from(tbody.querySelectorAll('tr'))
          .filter(tr => tr.id !== 'noDataRow' && tr.querySelectorAll('td').length);

        if(rows.length === 0){
          return alert('目前沒有資料可以匯出');
        }

        // 建一個乾淨的表格來匯出（避免把「操作」按鈕一起帶出去）
        const tempTable = document.createElement('table');
        const thead = table.querySelector('thead')?.cloneNode(true);
        const tempTbody = document.createElement('tbody');

        // 複製列，且移除最後一欄「操作」
        rows.forEach(tr => {
          const clone = tr.cloneNode(true);
          const tds = clone.querySelectorAll('td');
          if(tds.length) tds[tds.length - 1].remove(); // 移除操作欄
          tempTbody.appendChild(clone);
        });

        // thead 也移除最後一欄「操作」
        if(thead){
          const ths = thead.querySelectorAll('th');
          if(ths.length) ths[ths.length - 1].remove();
          tempTable.appendChild(thead);
        }
        tempTable.appendChild(tempTbody);

        // 轉成工作簿
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(tempTable);

        // 自動欄寬（簡單版）
        const range = XLSX.utils.decode_range(ws['!ref']);
        const colWidths = [];
        for (let C = range.s.c; C <= range.e.c; C++) {
          let maxLen = 8;
          for (let R = range.s.r; R <= range.e.r; R++) {
            const cell = ws[XLSX.utils.encode_cell({ r: R, c: C })];
            const v = cell ? String(cell.v ?? '') : '';
            maxLen = Math.max(maxLen, v.length);
          }
          colWidths.push({ wch: Math.min(30, maxLen + 2) });
        }
        ws['!cols'] = colWidths;

        XLSX.utils.book_append_sheet(wb, ws, '薪資記錄');

        // 檔名：薪資管理_YYYY-MM 或 오늘
        const month = document.getElementById('monthPicker')?.value || '';
        const fname = '薪資管理_' + (month || new Date().toISOString().slice(0,10)) + '.xlsx';

        XLSX.writeFile(wb, fname);
      } catch (e) {
        console.error(e);
        alert('匯出失敗：' + (e?.message || e));
      }
    });
  })();

  
  </script>
</body>
</html>