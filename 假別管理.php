<?php
// 🔥 新的 假別管理.php (頁面)
// 🔥 已套用您系統的版型 (包含權限檢查)

require_once __DIR__ . '/includes/auth_check.php';

// 只有 A 級（老闆）可以訪問
// 2. 檢查權限:A 級(老闆)或 B 級(管理員)
    check_user_level(['A', 'B'], true);

// 取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '假別管理 - 員工管理系統'; // 標題

// 統一路徑
$API_BASE_URL  = '/lamian-ukn/api';
$DATA_BASE_URL = '/lamian-ukn/首頁';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

<style>
  /* ====== 跟 index.php 相同的整體風格 ====== */
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

  /* ====== Top navbar（與 index 一樣的藍色漸層） ====== */
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
    padding: 26px 28px !important;
  }

  /* 🔍 頂欄搜尋框（沿用你現在的 search-container，配藍色 Navbar） */
  .search-container-wrapper {
    position: relative;
    width: 100%;
    max-width: 400px;
  }
  .search-container {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50px;
    padding: 4px 4px 4px 20px;
    backdrop-filter: blur(10px);
    border: 2px solid transparent;
  }
  .search-container:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
  }
  .search-container:focus-within {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
  }
  .search-input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    padding: 10px 12px;
    font-size: 14px;
    color: #ffffff;
    font-weight: 500;
  }
  .search-input::placeholder {
    color: rgba(255, 255, 255, 0.75);
    font-weight: 400;
  }
  .search-btn {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.8) 100%);
    border: none;
    border-radius: 40px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
  }
  .search-btn i {
    color: #2563eb;
    font-size: 16px;
  }
  .user-avatar {
    border: 2px solid rgba(255,255,255,.5);
  }

  /* ====== Sidebar：淡藍漸層延伸（與 index 相同） ====== */
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

  /* 修正側邊欄箭頭、SVG 顏色（與 index 相同） */
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
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(10px);
  }

  .breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    color: #9ca3af;
  }

  /* ====== 卡片、表格（讓兩張卡看起來跟 index 一樣） ====== */
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
    border-radius: 18px;
    overflow: hidden;
    background: #ffffff;
  }

  .table thead th {
    background: linear-gradient(135deg, #4f8bff, #7b6dff);
    color: #ffffff;
    border: none;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
  }

  .table tbody td {
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    border-color: rgba(148, 163, 184, 0.16);
  }

  .table tbody tr:hover {
    background: rgba(59, 130, 246, 0.06);
  }

  /* alert 區塊微調，讓它跟整體一致 */
  #errorAlert,
  #successAlert {
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
    border: none;
  }

  /* 主要按鈕改成藍色漸層 */
  .btn-primary {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border: none;
    border-radius: 25px;
  }
  .btn-primary:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
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
      padding: 20px 16px !important;
    }
  }

  @media (max-width: 768px) {
    .container-fluid {
      padding: 16px 12px !important;
    }
    h1 {
      font-size: 1.6rem;
    }
  }
</style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img class="user-avatar rounded-circle me-1" src="https://i.pravatar.cc/40?u=<?php echo urlencode($userName); ?>" width="28" height="28" alt="User Avatar" style="vertical-align:middle;">
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
    <!-- 側欄 -->
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
               data-bs-target="#collapseLayouts" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav">
    <?php if ($userLevel === 'A'): ?>
      <!-- 只有 A 級（老闆）可以看到 -->
      <a class="nav-link" href="員工資料表.php">員工資料表</a>
    <?php endif; ?>

    <a class="nav-link" href="班表管理.php">班表管理</a>
     <?php if ($userLevel === 'A'): ?>
      <!-- 只有 A 級（老闆）可以看到 -->
      <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
    <?php endif; ?>   
    <a class="nav-link" href="假別管理.php">假別管理</a>
    <a class="nav-link" href="打卡管理.php">打卡管理</a>

    <?php if ($userLevel === 'A'): ?>
      <!-- 只有 A 級（老闆）可以看到 -->
      <a class="nav-link" href="薪資管理.php">薪資管理</a>
    <?php endif; ?>

  </nav>
</div>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
               data-bs-target="#collapseOperation" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseOperation" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                   data-bs-target="#operationCollapseInventory" aria-expanded="false">
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
            
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
               data-bs-target="#collapseWebsite" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>會員管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseWebsite" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionWebsite">
                <a class="nav-link" href="member-list.php">會員清單</a>
                <a class="nav-link" href="member-detail.php">詳細資料頁</a>
                <a class="nav-link" href="point-manage.php">點數管理</a>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                   data-bs-target="#websiteCollapseMember" aria-expanded="false">
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
            <a class="nav-link" href="charts.html">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts
            </a>
            <a class="nav-link" href="tables.html">
              <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>Tables
            </a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:</div>
          <span id="loggedAs"><?= htmlspecialchars($userName); ?></span>
        </div>
      </nav>
    </div>


    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid px-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>假別管理</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">假別管理</li>
          </ol>

          <div id="errorAlert" class="alert alert-danger d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span id="errorMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <div id="successAlert" class="alert alert-success d-none" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <span id="successMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>

          <div class="card p-3 mb-4">
            <div class="card-header"><i class="fas fa-list me-2"></i>員工請假紀錄</div>
            <div class="card-body">
              <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-hover text-center align-middle">
                  <thead>
                    <tr>
                      <th>員工</th>
                      <th>假別</th>
                      <th>開始</th>
                      <th>結束</th>
                      <th>原因</th>
                      <th>狀態</th>
                    </tr>
                  </thead>
                  <tbody id="allLeaveTable">
                    <tr><td colspan="6" class="text-muted">載入中…</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="card p-3">
            <div class="card-header"><i class="fas fa-clipboard-check me-2"></i>請假審核管理</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                  <thead>
                    <tr>
                      <th>員工</th>
                      <th>假別</th>
                      <th>開始</th>
                      <th>結束</th>
                      <th>原因</th>
                      <th>照片</th>
                      <th>狀態</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody id="leaveReviewTable">
                    <tr><td colspan="8" class="text-muted">載入中…</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </main>

      <footer class="py-4 bg-light mt-auto">
        <div class="container-fluid px-4">
          <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">© 2024 令和博多餐廳管理系統 - Xxing0625</div>
            <div>
              <a href="#" class="text-decoration-none">隱私政策</a>
              <span class="mx-2">•</span>
              <a href="#" class="text-decoration-none">使用條款</a>
              <span class="mx-2">•</span>
              <a href="#" class="text-decoration-none">技術支援</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  
  <script>
    // 🔥 PHP 變數注入 (給 JS 使用)
    const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    
    // 日期顯示與側欄收合
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    // 提示函數
    function showError(msg){
      const a = document.getElementById('errorAlert');
      document.getElementById('errorMessage').textContent = msg;
      a.classList.remove('d-none');
      setTimeout(() => a.classList.add('d-none'), 5000);
    }

    function showSuccess(msg){
      const a = document.getElementById('successAlert');
      document.getElementById('successMessage').textContent = msg;
      a.classList.remove('d-none');
      setTimeout(() => a.classList.add('d-none'), 3000);
    }

    // 狀態徽章
    function statusBadge(s){
      const status = parseInt(s);
      if(status === 2) return `<span class="badge bg-success">已通過</span>`;
      if(status === 3) return `<span class="badge bg-danger">已駁回</span>`;
      return `<span class="badge bg-warning text-dark">未審核</span>`;
    }

    // 載入員工請假紀錄
    async function loadAllLeave(){
      const tbody = document.getElementById('allLeaveTable');
      try{
        // 🔥 修正：API 路徑
        const res = await fetch(API_BASE + '/取得請假紀錄.php');
        if(!res.ok) throw new Error(res.status + ' ' + res.statusText);
        const data = await res.json();
        tbody.innerHTML = (data || []).map(item => `
          <tr>
            <td>${item.employee ?? ''}</td>
            <td>${item.type ?? ''}</td>
            <td>${item.start ?? ''}</td>
            <td>${item.end ?? ''}</td>
            <td class="text-start">${item.reason ?? ''}</td>
            <td>${statusBadge(item.status)}</td>
          </tr>`).join('') || `<tr><td colspan="6" class="text-muted">目前沒有資料</td></tr>`;
      }catch(e){
        console.warn(e); 
        tbody.innerHTML = `<tr><td colspan="6" class="text-danger">載入失敗</td></tr>`; 
        showError('無法載入員工請假紀錄');
      }
    }

    // 載入請假審核列表
    async function loadLeaveReview(){
      const tbody = document.getElementById('leaveReviewTable');
      try{
        // 🔥 修正：API 路徑
        const res = await fetch(API_BASE + '/取得審核列表.php');
        if(!res.ok) throw new Error(res.status + ' ' + res.statusText);
        const data = await res.json();
        tbody.innerHTML = (data || []).map(item => `
          <tr>
            <td>${item.employee ?? ''}</td>
            <td>${item.type ?? ''}</td>
            <td>${item.start ?? ''}</td>
            <td>${item.end ?? ''}</td>
            <td class="text-start">${item.reason ?? ''}</td>
            <td>${item.photo ? `<a href="${item.photo}" target="_blank">查看</a>` : '無'}</td>
            <td>${statusBadge(item.status)}</td>
            <td>
              <button class="btn btn-success btn-sm me-1" onclick="confirmReview(${item.id}, 'approve')"><i class="fas fa-check"></i> 通過</button>
              <button class="btn btn-danger btn-sm" onclick="confirmReview(${item.id}, 'reject')"><i class="fas fa-times"></i> 駁回</button>
            </td>
          </tr>`).join('') || `<tr><td colspan="8" class="text-muted">目前沒有待審核項目</td></tr>`;
      }catch(e){
        console.warn(e); 
        tbody.innerHTML = `<tr><td colspan="8" class="text-danger">載入失敗</td></tr>`; 
        showError('無法載入審核列表');
      }
    }

    // 審核確認對話框
    function confirmReview(id, action){
      let message = '';
      if(action === 'approve') {
        message = '確定通過這筆請假嗎？';
      } else if(action === 'reject') {
        message = '確定駁回這筆請假嗎？';
      }
      
      const ok = confirm(message);
      if(!ok) return;
      
      reviewLeave(id, action);
    }

    // 審核 API 呼叫
    async function reviewLeave(id, action){
      try{
        // 🔥 修正：API 路徑
        const res = await fetch(API_BASE + '/review_leave.php', {
          method: 'POST', 
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ 
            leaveId: id,
            action: action
          })
        });
        
        const text = await res.text();
        console.log('Response:', text);
        
        if(!res.ok) {
          try {
            const errorData = JSON.parse(text);
            console.error('Error details:', errorData);
            showError(errorData.error || '審核操作失敗');
          } catch(e) {
            console.error('Raw error:', text);
            showError('審核操作失敗: ' + text);
          }
          return;
        }
        
        try {
          const result = JSON.parse(text);
          console.log('Success result:', result);
          
          let message = result.message || '操作成功';
          
          if(result.emailSent === false) {
            message += ' ⚠️ (Email 通知發送失敗: ' + result.emailMessage + ')';
            console.warn('Email failed:', result.emailMessage);
          } else if(result.emailSent === true) {
            message += ' ✅ (已發送通知信)';
          }
          
          showSuccess(message);
        } catch(e) {
          showSuccess(text || '操作成功');
        }
        
        await Promise.all([loadLeaveReview(), loadAllLeave()]);
        
      }catch(e){
        console.error('Fetch error:', e);
        showError('審核操作失敗: ' + e.message);
      }
    }

    // 🔥 PHP 頁尾注入 (取代 HTML 的 DOMContentLoaded)
    const el = id => document.getElementById(id);

    // 取得登入者資訊（已從 PHP Session 取得）
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('✅ 假別管理 已登入:', userName, 'ID:', userId);
        
        // 設定用戶名 (Sidenav footer)
        const loggedAsEl = el('loggedAs');
        if (loggedAsEl) loggedAsEl.textContent = userName;

        // 設定用戶名 (Navbar)
        const navName = el('navUserName');
        if(navName) navName.textContent = userName;
        
        // 從 me.php 載入真實頭像
        try {
            const r = await fetch(API_BASE + '/me.php', {credentials:'include'});
            if(r.ok) {
            const data = await r.json();
            if(data.avatar_url) {
                const avatarUrl = data.avatar_url + (data.avatar_url.includes('?')?'&':'?') + 'v=' + Date.now();
                const avatar = document.querySelector('.navbar .user-avatar');
                if(avatar) {
                    avatar.src = avatarUrl;
                    console.log('✅ 頭像已更新:', avatarUrl);
                }
            }
            }
        } catch(e) {
            console.warn('載入頭像失敗:', e);
        }
    }

    // 初始化
    window.addEventListener('DOMContentLoaded', async () => {
      await loadLoggedInUser();
      loadAllLeave();
      loadLeaveReview();
    });
  </script>
  </body>
</html>
