<?php
// /lamian-ukn/員工資料表.php
// ✅ 只有 A 級（老闆）可以訪問此頁，版型與 index.php 統一

require_once __DIR__ . '/includes/auth_check.php';

// 無權限就顯示禁止頁
if (!check_user_level('A', false)) {
    show_no_permission_page();
}

// 取得用戶資訊
$user      = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '員工資料表 - 員工管理系統';

// 統一路徑（與 index.php 相同）
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

  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    /* ====== 直接沿用 index.php 的整體風格 ====== */
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
      padding: 26px 28px;
    }

    /* ====== Sidebar：與 index 同一套藍色漸層 ====== */
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

    /* 表格樣式（頭部藍條、內容置中、可橫向捲動） */
    .table thead th {
      background: linear-gradient(135deg, #4f8bff, #7b6dff);
      color: #fff;
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

    /* 頁面頂部「重新載入 / 新增 / 搜尋」按鈕區 */
    .top-action-btn {
      border-radius: 999px;
      padding-inline: 18px;
      font-weight: 600;
    }

    .top-action-btn.btn-primary {
      background: linear-gradient(135deg, #2563eb 0%, #7b6dff 100%);
      border: none;
      box-shadow: 0 10px 24px rgba(37, 99, 235, 0.35);
    }
    .top-action-btn.btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 30px rgba(37, 99, 235, 0.45);
    }

    .top-action-search input {
      border-radius: 999px 0 0 999px;
    }
    .top-action-search button {
      border-radius: 0 999px 999px 0;
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

    /* ✅ 新增：隱藏 simple-datatables 內建搜尋（避免全欄位搜尋 UI 出現） */
    .dataTable-top .dataTable-search { display: none !important; }
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- 🔹 上方 Navbar：結構與 index.php 完全相同 -->
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
                <a class="nav-link active" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
                <a class="nav-link" href="假別管理.php">假別管理</a>
                <a class="nav-link" href="打卡管理.php">打卡管理</a>
                <a class="nav-link" href="薪資管理.php">薪資管理</a>
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

    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>員工資料表</h1>
            <div class="text-muted">
              <i class="fas fa-calendar-alt me-2"></i>
              <span id="currentDate"></span>
            </div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">員工資料表</li>
          </ol>

          <!-- 上方操作列：重新載入 / 新增 / 搜尋 -->
          <div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
            <button class="btn btn-primary top-action-btn" onclick="loadEmployees()">重新載入</button>
            <button class="btn btn-primary top-action-btn" onclick="openAddEmployeeModal()">新增</button>
            <div class="input-group top-action-search" style="width:300px;">
              <input
                type="text"
                class="form-control"
                placeholder="搜尋員工編號 / 姓名"
                id="searchInput"
                maxlength="30"
                inputmode="text"
                pattern="^[0-9A-Za-z\u4e00-\u9fa5\s]+$"
                title="只允許輸入：員工編號(英文/數字) 或 姓名(中文)"
                onkeydown="if(event.key==='Enter'){ event.preventDefault(); searchEmployees(); }"
              >
              <button class="btn btn-outline-secondary" type="button" onclick="searchEmployees()">搜尋</button>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-header">
              <i class="fas fa-table me-1"></i>
              員工清單
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>員工編號</th>
                      <th>姓名</th>
                      <th>出生年月日</th>
                      <th>電話</th>
                      <th>Email</th>
                      <th>地址</th>
                      <th>身份證</th>
                      <th>雇用類別</th>
                      <th>職位</th>
                      <th>底薪</th>
                      <th>時薪</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody id="employeeTable"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 新增員工 Modal -->
        <div class="modal fade" id="addEmployeeModal" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">新增員工</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form id="addEmployeeForm">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">姓名 *</label>
                      <input type="text" class="form-control" id="addName" required>
                      <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">出生年月日 *</label>
                      <input type="date" class="form-control" id="addBirthDate" required>
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">身份證字號 *</label>
                      <input type="text" class="form-control" id="addIdCard" required maxlength="10" placeholder="A123456789">
                      <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">電話 *</label>
                      <input type="tel" class="form-control" id="addTelephone" required placeholder="0912345678">
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="addEmail" placeholder="example@email.com">
                    <div class="invalid-feedback"></div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">地址 *</label>
                    <input type="text" class="form-control" id="addAddress" required>
                    <div class="invalid-feedback"></div>
                  </div>

                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">雇用類別 * (控制薪資)</label>
                      <select class="form-select" id="addRole" required>
                        <option value="">請選擇</option>
                        <option value="正職">正職</option>
                        <option value="臨時員工">臨時員工</option>
                      </select>
                      <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">權限等級 * (產生ID)</label>
                      <select class="form-select" id="addPermissionLevel" required>
                        <option value="">請選擇</option>
                        <option value="A">A (老闆)</option>
                        <option value="B">B (管理員)</option>
                        <option value="C">C (員工)</option>
                      </select>
                      <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">職位 *</label>
                      <input type="text" class="form-control" id="addPosition" required placeholder="例：店長、服務員">
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div id="addSalaryHint" class="alert alert-info">
                    請先選擇雇用類別
                  </div>

                  <div class="row" id="addBaseSalaryGroup" style="display:none;">
                    <div class="col-md-12 mb-3">
                      <label class="form-label">底薪 *</label>
                      <input type="number" class="form-control" id="addBaseSalary" min="0" step="1000" placeholder="28000">
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row" id="addHourlyRateGroup" style="display:none;">
                    <div class="col-md-12 mb-3">
                      <label class="form-label">時薪 *</label>
                      <input type="number" class="form-control" id="addHourlyRate" min="0" step="10" placeholder="180">
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitAddEmployee()">新增</button>
              </div>
            </div>
          </div>
        </div>

        <!-- 編輯員工 Modal -->
        <div class="modal fade" id="editEmployeeModal" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-warning">
                <h5 class="modal-title">編輯員工資料</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form id="editEmployeeForm">
                  <input type="hidden" id="editId">

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">姓名 *</label>
                      <input type="text" class="form-control" id="editName" required>
                      <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">出生年月日 *</label>
                      <input type="date" class="form-control" id="editBirthDate" required>
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">身份證字號 *</label>
                      <input type="text" class="form-control" id="editIdCard" required maxlength="10">
                      <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">電話 *</label>
                      <input type="tel" class="form-control" id="editTelephone" required>
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="editEmail">
                    <div class="invalid-feedback"></div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">地址 *</label>
                    <input type="text" class="form-control" id="editAddress" required>
                    <div class="invalid-feedback"></div>
                  </div>

                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">雇用類別 *</label>
                      <select class="form-select" id="editRole" required>
                        <option value="正職">正職</option>
                        <option value="臨時員工">臨時員工</option>
                      </select>
                      <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">權限等級 *</label>
                      <select class="form-select" id="editPermissionLevel" required>
                        <option value="">請選擇</option>
                        <option value="A">A (老闆)</option>
                        <option value="B">B (管理員)</option>
                        <option value="C">C (員工)</option>
                      </select>
                      <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-4 mb-3">
                      <label class="form-label">職位 *</label>
                      <input type="text" class="form-control" id="editPosition" required>
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row" id="editBaseSalaryGroup">
                    <div class="col-md-12 mb-3">
                      <label class="form-label">底薪</label>
                      <input type="number" class="form-control" id="editBaseSalary" min="0" step="1000">
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>

                  <div class="row" id="editHourlyRateGroup">
                    <div class="col-md-12 mb-3">
                      <label class="form-label">時薪</label>
                      <input type="number" class="form-control" id="editHourlyRate" min="0" step="10">
                      <div class="invalid-feedback"></div>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <div>
                  <?php if ($userLevel === 'A'): ?>
                    <button type="button" class="btn btn-danger" id="resetTokenBtn">重製 Token</button>
                  <?php endif; ?>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitEdit()">儲存</button>
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
  <script src="員工資料表.js"></script>

  <script>
    const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

    const $  = s => document.querySelector(s);
    const el = id => document.getElementById(id);

    const dateEl = el('currentDate');
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString('zh-TW', {
        year: 'numeric', month: 'long', day: 'numeric', weekday: 'long'
      });
    }

    el('sidebarToggle')?.addEventListener('click', e => {
      e.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
    });

    async function loadLoggedInUser() {
      const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
      const userId   = <?php echo json_encode($userId,   JSON_UNESCAPED_UNICODE); ?>;

      console.log('✅ 員工資料表 已登入:', userName, 'ID:', userId);

      const loggedAsEl = el('loggedAs');
      if (loggedAsEl) loggedAsEl.textContent = userName;

      const navName = el('navUserName');
      if (navName) navName.textContent = userName;

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

    window.addEventListener('DOMContentLoaded', async () => {
      await loadLoggedInUser();

      if (typeof loadEmployees === 'function') {
        loadEmployees();
      } else {
        console.error('loadEmployees 函數未在 員工資料表.js 中定義');
      }
    });
  </script>
</body>
</html>
