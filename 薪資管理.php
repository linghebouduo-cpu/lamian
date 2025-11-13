<?php
// 🔥 整合：加入權限檢查 (!! 依據您的範本修改 !!)
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

// [!! 新增 !!] 依照您的 庫存查詢.php 範本，在這裡定義 API 路徑
$API_BASE_URL  = '/lamian-ukn/api';

// 權限檢查通過，繼續載入 HTML 內容
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <title>薪資管理 - 員工管理系統</title>

 
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root{
      --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%);
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --success-gradient: linear-gradient(135deg, #4facfe 0%, #54bcc1 100%);
      --warning-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --dark-bg: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%);
      --card-shadow: 0 15px 35px rgba(0,0,0,.1);
      --hover-shadow: 0 25px 50px rgba(0,0,0,.15);
      --border-radius: 20px;
      --transition: all .3s cubic-bezier(.4,0,.2,1);
    }
    *{ transition: var(--transition); }
    body{
      background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }
    .sb-topnav{ background: var(--dark-bg)!important; border:none; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand{
      font-weight:700; font-size:1.5rem;
      background: linear-gradient(45deg,#fff,#fff);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent; text-shadow:none;
    }
    .sb-sidenav{ background: linear-gradient(180deg, #fbb97ce4 0%, #ff00006a 100%)!important; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color:rgba(255,255,255,.7)!important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px!important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter:blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{ content:''; position:absolute; left:0; top:0; height:100%; width:4px; background:linear-gradient(45deg,#fff,#fff); transform:scaleY(0); transition:var(--transition); border-radius:0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform:scaleY(1); }
    .sb-sidenav .nav-link i{ width:20px; text-align:center; margin-right:10px; font-size:1rem; }
    .sb-sidenav-footer{ background:rgba(255,255,255,.1)!important; color:#fff!important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }

    .container-fluid{ padding:30px!important; }
    h1{
      background: var(--primary-gradient);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
      font-weight:700; font-size:2.5rem; margin-bottom:30px;
    }
    .breadcrumb{ background:rgba(255,255,255,.8); border-radius:var(--border-radius); padding:15px 20px; box-shadow:var(--card-shadow); backdrop-filter:blur(10px); }

    .table{ border-radius:var(--border-radius); overflow:hidden; background:#fff; box-shadow:var(--card-shadow); }
    .table thead th{ background:var(--primary-gradient); color:#000; border:none; font-weight:600; padding:15px; }
    .table tbody td{ padding:15px; vertical-align:middle; border-color:rgba(0,0,0,.05); }
    .table tbody tr:hover{ background:rgba(227,23,111,.05); transform:scale(1.01); }

    .btn-primary{ background:var(--primary-gradient); border:none; border-radius:25px; }
    .btn-primary:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(209,209,209,.976); }

    .badge-paytype{ font-size:.75rem; }
    .diff-pill{ font-size:.75rem; }
    .readonly-like{ background:#f8fafc; }

/* 統計摘要(漸層卡片) */
.stat-card{
  border: none; color:#fff; border-radius: var(--border-radius);
  background: #999; box-shadow: var(--card-shadow);
  position: relative; overflow: hidden;
}
.stat-card .card-body{ padding: 1.1rem 1.25rem; }
.stat-label{ font-size:.85rem; opacity:.9; }
.stat-value{ font-size:1.6rem; font-weight:700; line-height:1.2; }
.stat-icon{ font-size:2.2rem; opacity:.35; }
.stat-glow{
  position:absolute; right:-30px; top:-30px; width:120px; height:120px;
  border-radius:50%; background: rgba(255,255,255,.15); filter: blur(12px);
}
.stat-primary{  background: var(--primary-gradient);  }
.stat-success{  background: var(--success-gradient);  }
.stat-warning{  background: var(--warning-gradient);  }
.stat-secondary{ background: var(--secondary-gradient); }
  
    /* 新版搜尋列與頭像的 CSS */
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
    .search-btn i { color: #ff6b6b; font-size: 16px; }
    .user-avatar{border:2px solid rgba(255,255,255,.5)}
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <a class="navbar-brand ps-3" href="index.html">員工管理系統</a>
      <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

      <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
          <div class="search-container-wrapper">
              <div class="search-container">
                  <input class="search-input" type="text" placeholder="搜尋員工、班表、薪資..." aria-label="Search" />
                  <button class="search-btn" id="btnNavbarSearch" type="button">
                      <i class="fas fa-search"></i>
                  </button>
              </div>
          </div>
      </form>

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
            <a class="nav-link" href="index.html">
              <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁
            </a>

           <div class="sb-sidenav-menu-heading">Pages</div>
            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>人事管理
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseLayouts" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav">
                <a class="nav-link" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.html">日報表記錄</a>
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
                    <a class="nav-link" href="庫存管理.html">當前庫存</a>
                    <a class="nav-link" href="進貨管理.html">進貨紀錄</a>
                  </nav>
                </div>

                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#operationCollapseSales" aria-expanded="false">
                  銷售管理
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="operationCollapseSales" data-bs-parent="#sidenavAccordionOperation">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="#">銷售統計</a>
                  </nav>
                </div>
              </nav>
            </div>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false">
              <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>其他
              <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapsePages" data-bs-parent="#sidenavAccordion">
              <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseAuth" aria-expanded="false">
                  Authentication
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="pagesCollapseAuth" data-bs-parent="#sidenavAccordionPages">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="login.html">Login</a>
                    <a class="nav-link" href="register.html">Register</a>
                    <a class="nav-link" href="password.html">Forgot Password</a>
                  </nav>
                </div>

                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseError" aria-expanded="false">
                  Error
                  <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="pagesCollapseError" data-bs-parent="#sidenavAccordionPages">
                  <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="401.html">401 Page</a>
                    <a class="nav-link" href="404.html">404 Page</a>
                    <a class="nav-link" href="500.html">500 Page</a>
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
          <?php echo htmlspecialchars($userName ?? 'User'); ?>
        </div>
      </nav>
    </div>

    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid px-4">
          <h1 class="mt-4">薪資管理</h1>
          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.html">首頁</a></li>
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

<div class="row g-3 mb-4">
  <div class="col-lg-3 col-md-6">
    <div class="card stat-card stat-primary">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="stat-label">員工數</div>
          <div class="stat-value" id="summary_employees">0</div>
        </div>
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-glow"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="card stat-card stat-success">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="stat-label">總薪資</div>
          <div class="stat-value" id="summary_total_payroll">0</div>
        </div>
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-glow"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="card stat-card stat-warning">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="stat-label">總獎金</div>
          <div class="stat-value" id="summary_total_bonus">0</div>
        </div>
        <div class="stat-icon"><i class="fas fa-gift"></i></div>
        <div class="stat-glow"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="card stat-card stat-secondary">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="stat-label">總扣款</div>
          <div class="stat-value" id="summary_total_deductions">0</div>
        </div>
        <div class="stat-icon"><i class="fas fa-minus-circle"></i></div>
        <div class="stat-glow"></div>
      </div>
    </div>
  </div>
</div>

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
                <div class="col-md-5 d-flex align-items-end gap-2">
                  <button class="btn btn-primary" onclick="filterSalaries()"><i class="fas fa-search me-2"></i>查詢</button>
                  <button class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-redo me-2"></i>清除</button>
                  <button class="btn btn-success" onclick="exportToExcel()"><i class="fas fa-file-excel me-2"></i>匯出</button>
                </div>
              </div>
              <div class="mt-2 small text-muted">
                <i class="fas fa-info-circle me-1"></i>今日日期：<span id="currentDate"></span>
              </div>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-2"></i>薪資記錄</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                  <thead class="table-light">
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

      <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>薪資詳情</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody">
              </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
          </div>
        </div>
      </div>

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
              <div><strong>實領(自動):</strong><span id="edit_total_salary">0</span></div>
              <small class="text-muted">公式:實領 = 計算底薪 + 獎金 - 扣款;計算底薪 =(月薪制:底薪)/(時薪制:時薪 × 工時)</small>
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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>

<script>
    const API_BASE = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
</script>

<script src="薪資管理.js?v=20241109002"></script>

<script>
    // 頁面載入完成後，執行頭像載入
    document.addEventListener('DOMContentLoaded', () => {
        loadLoggedInUser();
    });

    // 來自 庫存查詢.php 的函式
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('✅ 薪資管理 已登入:', userName, 'ID:', userId);
        
        try {
            // 確保 API_BASE 變數存在 (已在上方定義)
            const r = await fetch(API_BASE + '/me.php', {credentials:'include'});
            if(r.ok) {
               const data = await r.json();
               if(data.avatar_url) {
                    // 加上時間戳強制更新
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