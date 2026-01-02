<?php
// 🔥 整合：加入權限檢查
require_once __DIR__ . '/includes/auth_check.php';

// 🔥 修正：A 級(老闆) 或 B 級(管理員) 可以訪問
if (!check_user_level('A', false) && !check_user_level('B', false)) {
    // 如果 '不是A' 而且 '也不是B'，就顯示無權限
    show_no_permission_page(); // 會 exit
}

// 🔥 整合：取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '庫存調整 - 員工管理系統'; // 標題

// 統一路徑 (JS 會用到)
$API_BASE_URL  = '/lamian-ukn/api';
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
    /* ====== 整體風格：跟 日報表記錄 / 薪資管理 / 庫存查詢 一樣 ====== */
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

    /* ====== Top navbar：藍色漸層 ====== */
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

    /* ====== Sidebar：藍紫玻璃感 ====== */
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

    /* 修正側欄箭頭 / icon 顏色 */
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
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
      backdrop-filter: blur(10px);
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
      overflow: hidden;
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

    .table-hover tbody tr:hover {
      background: rgba(59, 130, 246, 0.06);
    }

    /* 最近異動裡負數紅色已在 JS 加 class，這邊就維持預設 */

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

    /* ====== 按鈕：送出 / 清除 做成 pill ====== */
    .btn-primary {
      background: linear-gradient(135deg, #4f8bff 0%, #7b6dff 100%) !important;
      color: #fff;
      border-color: rgba(59, 130, 246, .25) !important;
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    }
    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary:active {
      filter: brightness(1.03);
      box-shadow: 0 8px 18px rgba(59, 130, 246, 0.5);
      transform: translateY(-1px);
      color: #fff;
    }

    .btn-outline-secondary {
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: .02em;
      border-color: rgba(148, 163, 184, 0.7);
      color: #1d4ed8;
      background-color: #ffffff;
      box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
    }
    .btn-outline-secondary:hover {
      background-color: #eff6ff;
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, .6);
      box-shadow: 0 6px 16px rgba(15, 23, 42, .12);
      transform: translateY(-1px);
    }

    /* ====== Navbar 搜尋列（沿用原本設計，顏色配藍色 navbar） ====== */
    .search-container-wrapper { position: relative; width: 100%; max-width: 400px; }
    .search-container {
        position: relative; display: flex; align-items: center;
        background: rgba(255, 255, 255, 0.15); border-radius: 50px;
        padding: 4px 4px 4px 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px); border: 2px solid transparent;
    }
    .search-container:hover { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); }
    .search-container:focus-within { background: rgba(255, 255, 255, 0.25); border-color: rgba(255, 255, 255, 0.5); }
    .search-input {
        flex: 1; border: none; outline: none; background: transparent;
        padding: 10px 12px; font-size: 14px; color: #fff; font-weight: 500;
    }
    .search-input::placeholder { color: rgba(255, 255, 255, 0.7); font-weight: 400; }
    .search-btn {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
        border: none; border-radius: 40px; width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .search-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25); }
    .search-btn i { color: #2563eb; font-size: 16px; }

    .user-avatar {
      border: 2px solid rgba(255, 255, 255, .5);
    }

    .form-select, .form-control{
      border-radius: 12px;
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
    <div id="layoutSidenav_nav">
      <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
          <div class="nav">
            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link" href="index.php">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

            <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false">
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

            <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperation" aria-expanded="true">
              <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>營運管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse show" id="collapseOperation" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionOperation">
                <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseInventory" aria-expanded="true">
                  庫存管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse show" id="operationCollapseInventory" data-bs-parent="#sidenavAccordionOperation">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="庫存查詢.php">庫存查詢</a>
                    <a class="nav-link active" href="庫存調整.php">庫存調整</a>
                    <a class="nav-link" href="商品管理.php">商品管理</a>
                  </nav>
                </div>
                <a class="nav-link" href="日報表.php"> <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
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
            <a class="nav-link" href="charts.html"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <span id="loggedAs"><?php echo htmlspecialchars($userName); ?></span>
        </div>
      </nav>
    </div>

    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>庫存調整</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">庫存調整</li>
          </ol>

          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>

          <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-plus-circle me-2"></i>新增庫存異動</div>
            <div class="card-body">
              <form id="adjustForm" class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">品項</label>
                  <select id="itemSelect" class="form-select" required>
                    <option value="">請選擇品項</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">類別</label>
                  <input id="itemCategory" class="form-control" type="text" readonly>
                </div>
                <div class="col-md-2">
                  <label class="form-label">單位</label>
                  <input id="itemUnit" class="form-control" type="text" readonly>
                </div>
                <div class="col-md-2">
                  <label class="form-label">數量</label>
                  <input id="qty" class="form-control" type="number" step="1" min="1" placeholder="例如 10" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label d-block">方向</label>
                  <div class="d-flex gap-3">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="io" id="io_in" value="in" checked>
                      <label class="form-check-label" for="io_in">入庫</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="io" id="io_out" value="out">
                      <label class="form-check-label" for="io_out">出庫</label>
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <label class="form-label">進貨/異動時間（可留白=現在）</label>
                  <input id="when" class="form-control" type="datetime-local">
                </div>
                <div class="col-md-3">
                  <label class="form-label">進貨人 / 經手人</label>
                  <input id="who" class="form-control" type="text" placeholder="輸入姓名" required>
                </div>
                <div class="col-md-3">
                  <button id="btnSubmit" class="btn btn-primary w-100" type="submit"><i class="fas fa-save me-1"></i><span class="txt">送出</span></button>
                </div>
                <div class="col-md-3">
                  <button class="btn btn-outline-secondary w-100" type="button" id="btnClear"><i class="fas fa-eraser me-1"></i>清除</button>
                </div>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-header fw-semibold"><i class="fas fa-clock-rotate-left me-2"></i>最近異動</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center">
                  <thead class="table-light">
                    <tr>
                      <th>編號</th>
                      <th>品項名稱</th>
                      <th>類別</th>
                      <th>数量</th>
                      <th>單位</th>
                      <th>時間</th>
                      <th>經手人</th>
                    </tr>
                  </thead>
                  <tbody id="recentBody">
                    <tr id="recentEmpty" class="d-none">
                      <td colspan="7" class="text-muted py-4"><i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料</td>
                    </tr>
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
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    const API_BASE    = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_PRODUCTS= API_BASE + '/product_list.php';
    const API_ADJUST  = API_BASE + '/inventory_adjust.php';
    const API_RECENT  = API_BASE + '/inventory_latest.php?limit=20';
    const recentUrl   = () => API_RECENT + '&t=' + Date.now();
    const currentUserName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;

    const qs   = id => document.getElementById(id);
    const escapeHtml = str => String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    const setBusy = (b)=>{ const btn=qs('btnSubmit'); if(!btn) return; btn.disabled=b; const t=btn.querySelector('.txt'); if(t) t.textContent=b?'處理中…':'送出'; };
    function showOk(msg){ const a=qs('msgOk'); if(!a) return; a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 2500); }
    function showErr(msg){ const a=qs('msgErr'); if(!a) return; a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 5000); }
    function hideMsg(){ qs('msgOk')?.classList.add('d-none'); qs('msgErr')?.classList.add('d-none'); }
    function localInputToIso(val){ if(!val) return ''; return val.replace('T',' ') + (val.length === 16 ? ':00' : ''); }

    let products = [];

    window.addEventListener('DOMContentLoaded', async ()=>{
      await loadLoggedInUser();
      if (qs('who')) {
          qs('who').value = currentUserName;
      }
      await loadProducts();
      await loadRecent();
      bind();
    });

    function bind(){
      qs('itemSelect')?.addEventListener('change', onItemChange);
      qs('adjustForm')?.addEventListener('submit', submitAdjust);
      qs('btnClear')?.addEventListener('click', resetForm);
    }

    function onItemChange(){
      const id = Number(qs('itemSelect')?.value||0);
      const p = products.find(x=> Number(x.id) === id);
      if(qs('itemCategory')) qs('itemCategory').value = p ? (p.category||'') : '';
      if(qs('itemUnit'))     qs('itemUnit').value      = p ? (p.unit||'')     : '';
    }

    async function loadProducts(){
      const sel = qs('itemSelect');
      try{
        const r = await fetch(API_PRODUCTS + '?t=' + Date.now(), {credentials:'include'});
        const text = await r.text();
        let data;
        try { data = JSON.parse(text); }
        catch(e){ console.error('product_list 非 JSON：', text); throw new Error('品項清單格式錯誤'); }
        if(!r.ok || data.error){ throw new Error(data?.error || ('HTTP '+r.status)); }
        const list = Array.isArray(data) ? data : (data.data||[]);
        if(!list.length){
          if(sel){ sel.innerHTML = '<option value="">（尚無品項）</option>'; sel.disabled = true; }
          showErr('品項清單為空，請先在「商品管理」頁面建立品項');
          return;
        }
        products = list;
        if(sel){
          sel.disabled = false;
          sel.innerHTML = '<option value="">請選擇品項</option>' +
            products.map(p => `<option value="${p.id}">${escapeHtml(p.name || ('品項#'+p.id))}${p.unit?'（'+escapeHtml(p.unit)+'）':''}</option>`).join('');
        }
      }catch(e){
        if(sel){ sel.innerHTML = '<option value="">無法載入品項</option>'; sel.disabled = true; }
        showErr('載入品項失敗：' + e.message);
      }
    }

    async function loadRecent(silent=false){
      try{
        const r = await fetch(recentUrl(), {credentials:'include'});
        if(!r.ok) throw new Error('HTTP '+r.status);
        const rows = await r.json();
        const tb = qs('recentBody');
        const empty = qs('recentEmpty');
        if(!tb) return;
        tb.innerHTML='';
        if(!rows.length){
          if(empty){ empty.classList.remove('d-none'); tb.appendChild(empty); }
          return;
        }
        if(empty) empty.classList.add('d-none');
        tb.innerHTML = rows.map(x=>`
          <tr>
            <td>${escapeHtml(x.id)}</td>
            <td class="text-start">${escapeHtml(x.name||'')}</td>
            <td>${escapeHtml(x.category||'')}</td>
            <td class="${Number(x.quantity)<0?'text-danger fw-bold':''}">${escapeHtml(Math.abs(Number(x.quantity)||0))}</td>
            <td>${escapeHtml(x.unit||'')}</td>
            <td>${escapeHtml(x.last_update_iso||x.last_update||'')}</td>
            <td>${escapeHtml(x.updated_by||'')}</td>
          </tr>
        `).join('');
      }catch(e){
        console.error(e);
        if(!silent) showErr('載入最近異動失敗：' + e.message);
      }
    }

    async function submitAdjust(e){
      e.preventDefault();
      hideMsg();
      setBusy(true);

      const item_id   = Number(qs('itemSelect')?.value||0);
      const qty_raw   = Number(qs('qty')?.value||0);
      const io        = document.querySelector('input[name="io"]:checked')?.value || 'in';
      const updated_by= (qs('who')?.value||'').trim();
      const whenInput = qs('when')?.value || '';

      if(!item_id){ setBusy(false); return showErr('請選擇品項'); }
      if(!qty_raw || !Number.isFinite(qty_raw)){ setBusy(false); return showErr('請輸入正確數量'); }
      if(!updated_by){ setBusy(false); return showErr('請輸入經手人'); }

     const displayQty     = Math.abs(qty_raw);                    // ✅ 畫面用：永遠正數
const signedQuantity = (io === 'out') ? -displayQty : displayQty; // ✅ 後端用：出庫負數、入庫正數
const body = { item_id, quantity: signedQuantity, updated_by };
      if(whenInput){ body.when = whenInput; }

      try{
        const r = await fetch(API_ADJUST, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(body),
          credentials:'include'
        });

        const rawText = await r.text();
        let resp;
        try { resp = JSON.parse(rawText); }
        catch { throw new Error('伺服器回應非 JSON：\n' + rawText); }
        if(!r.ok || resp.error){ throw new Error(resp.error || ('HTTP '+r.status)); }

        const p = products.find(x => Number(x.id) === item_id) || {};
        const whenText = whenInput ? localInputToIso(whenInput)
                                   : new Date().toLocaleString('zh-TW',{ hour12:false });
        const rowHtml = `
          <tr>
            <td>${escapeHtml(resp.id ?? '')}</td>
            <td class="text-start">${escapeHtml(p.name||'')}</td>
            <td>${escapeHtml(p.category||'')}</td>
            <td class="${io==='out'?'text-danger fw-bold':''}">${escapeHtml(displayQty)}</td>
            <td>${escapeHtml(p.unit||'')}</td>
            <td>${escapeHtml(whenText || '')}</td>
            <td>${escapeHtml(updated_by)}</td>
          </tr>`;

        const tb = qs('recentBody');
        const empty = qs('recentEmpty');
        if(empty) empty.classList.add('d-none');
        if(tb) tb.insertAdjacentHTML('afterbegin', rowHtml);

        showOk('已新增庫存異動（編號 ' + (resp.id ?? '') + '）');
        resetForm();
        await loadRecent(true);
      }catch(e){
        console.error(e);
        showErr('新增失敗：' + e.message);
      }finally{
        setBusy(false);
      }
    }

    function resetForm(){
      if(qs('itemSelect'))   qs('itemSelect').value='';
      if(qs('itemCategory')) qs('itemCategory').value='';
      if(qs('itemUnit'))     qs('itemUnit').value='';
      if(qs('qty'))          qs('qty').value='';
      if(qs('when'))         qs('when').value='';
      if(qs('who'))          qs('who').value = currentUserName;
      const ioIn = qs('io_in'); if(ioIn) ioIn.checked = true;
    }
    
    const el = id => document.getElementById(id);
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        console.log('✅ 庫存調整 已登入:', userName, 'ID:', userId);
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
  </script>
</body>
</html>