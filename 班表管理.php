<?php
// /lamian-ukn/班表管理.php
// ✅ 只有 A 級（老闆）和 B 級（管理員）可以訪問此頁
// 🔥 參照 員工資料表.php 的版型與權限邏輯
// 🔥 預設載入「下週」的日期

// 1. 載入權限檢查
require_once __DIR__ . '/includes/auth_check.php';

// 2. 檢查權限：A 級(老闆) 或 B 級(管理員)
// 假設 check_user_level() 會檢查當前 session 用戶
if (!check_user_level('A', false) && !check_user_level('B', false)) {
    // 如果 *既不是A* *也不是B*，導向回首頁 (index.php)
    header('Location: index.php'); 
    exit;
}

// 3. 取得用戶資訊 (既然通過了檢查，表示已登入且有權限)
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

// 4. 統一路徑 (從 員工資料表.php 複製)
$API_BASE_URL  = '/lamian-ukn/api';
$DATA_BASE_URL = '/lamian-ukn/首頁';

$pageTitle = '班表管理 - 員工管理系統'; // 頁面標題
?>
<!DOCTYPE html>
<html lang="en">
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
    :root {
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
    *{transition:var(--transition)}
    body{background:linear-gradient(135deg,#fff 0%,#fff 100%);font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;min-height:100vh}
    .sb-topnav{background:var(--dark-bg)!important;border:none;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    .navbar-brand{font-weight:700;font-size:1.5rem;background:linear-gradient(45deg,#fff,#fff);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}
    
    /* 搜尋框 (from 員工資料表.php) */
    .search-container-wrapper { position: relative; width: 100%; max-width: 400px; }
    .search-container { position: relative; display: flex; align-items: center; background: rgba(255, 255, 255, 0.15); border-radius: 50px; padding: 4px 4px 4px 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); backdrop-filter: blur(10px); border: 2px solid transparent; }
    .search-container:hover { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.3); transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
    .search-container:focus-within { background: rgba(255, 255, 255, 0.25); border-color: rgba(255, 255, 255, 0.5); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
    .search-input { flex: 1; border: none; outline: none; background: transparent; padding: 10px 12px; font-size: 14px; color: #fff; font-weight: 500; }
    .search-input::placeholder { color: rgba(255, 255, 255, 0.7); font-weight: 400; }
    .search-btn { background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%); border: none; border-radius: 40px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); position: relative; overflow: hidden; }
    .search-btn::before { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; border-radius: 50%; background: rgba(251, 185, 124, 0.3); transform: translate(-50%, -50%); transition: width 0.6s, height 0.6s; }
    .search-btn:hover::before { width: 80px; height: 80px; }
    .search-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25); }
    .search-btn:active { transform: scale(0.95); }
    .search-btn i { color: #ff6b6b; font-size: 16px; position: relative; z-index: 1; }

    /* Sidenav (from 員工資料表.php) */
    .sb-sidenav{background:linear-gradient(180deg,#fbb97ce4 0%,#ff00006a 100%)!important;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    .sb-sidenav-menu-heading{color:rgba(255,255,255,.7)!important;font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;padding:20px 15px 10px!important;margin-top:15px}
    .sb-sidenav .nav-link{border-radius:15px;margin:5px 15px;padding:12px 15px;position:relative;overflow:hidden;color:rgba(255,255,255,.9)!important;font-weight:500;backdrop-filter:blur(10px)}
    .sb-sidenav .nav-link:hover{background:rgba(255,255,255,.15)!important;transform:translateX(8px);box-shadow:0 8px 25px rgba(0,0,0,.2);color:#fff!important}
    .sb-sidenav .nav-link.active{background:rgba(255,255,255,.2)!important;color:#fff!important;font-weight:600;box-shadow:0 8px 25px rgba(0,0,0,.15)}
    .sb-sidenav .nav-link::before{content:'';position:absolute;left:0;top:0;height:100%;width:4px;background:linear-gradient(45deg,#fff,#fff);transform:scaleY(0);border-radius:0 10px 10px 0}
    .sb-sidenav .nav-link:hover::before,.sb-sidenav .nav-link.active::before{transform:scaleY(1)}
    .sb-sidenav .nav-link i{width:20px;text-align:center;margin-right:10px;font-size:1rem}
    .sb-sidenav-menu-nested .nav-link{padding-left:45px;font-size:.9rem;background:rgba(255,255,255,.05)!important;margin:2px 15px;border-radius:10px}
    .sb-sidenav-menu-nested .nav-link:hover{background:rgba(255,255,255,.1)!important;transform:translateX(5px);padding-left:50px}
    .sb-sidenav-footer{background:rgba(255,255,255,.1)!important;color:#fff!important;border-top:1px solid rgba(255,255,255,.2);padding:20px 15px;margin-top:20px}
    .sb-sidenav-footer .small{color:rgba(255,255,255,.7)!important;font-size:.8rem}
    .user-avatar{border:2px solid rgba(255,255,255,.5)}

    /* 內容區 */
    .container-fluid{padding:30px!important}
    h1{background:var(--primary-gradient);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;font-size:2.5rem;margin-bottom:30px}
    .card{border:none;border-radius:var(--border-radius);box-shadow:var(--card-shadow);backdrop-filter:blur(10px);background:rgba(255,255,255,.9);overflow:hidden;position:relative}
    .card:hover{transform:translateY(-10px);box-shadow:var(--hover-shadow)}
    .card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--primary-gradient)}
    .card-header{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7));border:none;padding:20px;font-weight:600;border-radius:var(--border-radius) var(--border-radius) 0 0!important}
    .card-body{padding:25px}
    
    .table{border-radius:var(--border-radius);overflow:hidden;background:#fff}
    .table thead th{background:var(--primary-gradient);color:#000;border:none;font-weight:600;padding:15px;text-align:center;vertical-align:middle;white-space:nowrap}
    .table tbody td{padding:15px;vertical-align:middle;border-color:rgba(0,0,0,.05);text-align:center;white-space:nowrap}
    .table tbody tr:hover{background:rgba(227,23,111,.05)}
    
    .breadcrumb{background:rgba(255,255,255,.8);border-radius:var(--border-radius);padding:15px 20px;box-shadow:var(--card-shadow);backdrop-filter:blur(10px)}
    footer{background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.7))!important;border-top:1px solid rgba(0,0,0,.1);backdrop-filter:blur(10px)}
    
    /* 按鈕 (from 員工資料表.php) */
    .btn-primary { background: var(--primary-gradient); border: none; border-radius: 25px; padding: 0.5rem 1.25rem; color: #fff; }
    .btn-primary:hover { transform: scale(1.05); box-shadow: 0 10px 25px rgba(209, 209, 209, 0.976); background: var(--primary-gradient); color: #fff; }
    .btn-outline-secondary { border-radius: 25px; padding: 0.5rem 1.25rem; }
    .form-control { border-radius: 12px; }

    /* ====== Gantt（日檢視）====== */
    .gantt-toolbar { gap: .5rem; flex-wrap: wrap; }
    .gantt-toolbar .btn-day { min-width: 96px; }
    .gantt-legend { font-size: .9rem; opacity: .75; }
    .gantt { background:#fff; border:1px solid rgba(0,0,0,.06); border-radius:12px; box-shadow: var(--card-shadow); overflow:hidden; }
    .gantt-header, .gantt-row { display:grid; grid-template-columns: 140px 1fr; }
    .gantt-header { background:#f8f9fa; border-bottom:1px solid rgba(0,0,0,.06); }
    .gantt-header .times { position:relative; padding:10px 8px; border-left:1px solid rgba(0,0,0,.06); }
    .gantt-header .scale { display:grid; grid-template-columns: repeat(15, 1fr); font-size:.85rem; text-align:center; }
    .gantt-header .scale div { border-left:1px dashed rgba(0,0,0,.07); padding:2px 0; }
    .gantt-row + .gantt-row { border-top:1px solid rgba(0,0,0,.06); }
    .gantt-row .name { padding:10px 12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; background:#fff; }
    .gantt-row .track { position:relative; padding:12px 8px; border-left:1px solid rgba(0,0,0,.06); background:linear-gradient(180deg,#fff,#fff); }
    .gantt-grid { position:absolute; inset:12px 8px; display:grid; grid-template-columns: repeat(15, 1fr); }
    .gantt-grid div { border-left:1px dashed rgba(0,0,0,.06); }
    .gantt-bar { position:absolute; height:28px; border-radius:8px; background: var(--success-gradient); display:flex; align-items:center; padding:0 10px; box-shadow: 0 6px 16px rgba(0,0,0,.12); font-size:.9rem; color:#fff; white-space:nowrap; cursor:pointer; }

    /* 點擊後捲到編輯格子時的「亮一下」 */
    .pulse-highlight { animation: pulseBg 1.4s ease-out 1; }
    @keyframes pulseBg {
      0% { box-shadow: 0 0 0 0 rgba(79,172,254,.6); }
      100% { box-shadow: 0 0 0 18px rgba(79,172,254,0); }
    }
    
    /* 編輯區的 Badge (Chip) */
    .assign-chip { font-size: 0.9rem; padding: 6px 6px 6px 10px; }
    .assign-chip .chip-btn {
        padding: 0;
        margin: 0;
        width: 18px;
        height: 18px;
        font-size: 11px;
        line-height: 18px;
        border-radius: 50%;
        opacity: 0.7;
    }
    .assign-chip .chip-btn:hover { opacity: 1; }
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <div class="search-container-wrapper">
            <div class="search-container">
                <input class="search-input" type="text" placeholder="搜尋..." aria-label="Search" />
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
                <a class="nav-link active" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.html">日報表記錄</a>
                <a class="nav-link" href="假別管理.php">假別管理</a>
                <a class="nav-link" href="打卡管理.php">打卡管理</a>
                <a class="nav-link" href="薪資管理.html">薪資管理</a>
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
                  </nav>
                </div>
                <a class="nav-link" href="日報表.html"><div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
                <a class="nav-link" href="薪資管理.html"><div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>薪資記錄</a>
                <a class="nav-link" href="班表.html"><div class="sb-nav-link-icon"><i class="fas fa-calendar-days"></i></div>班表</a>
              </nav>
            </div>

            <a class="nav-link" href="請假申請.php"><div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請</a>

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
            <a class="nav-link" href="charts.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
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
            <h1>班表管理</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">班表管理</li>
          </ol>

          <div class="d-flex justify-content-start align-items-center gap-2 mb-4">
            <select id="yearSelect" class="form-select" style="width: 100px;"></select>
            <select id="monthSelect" class="form-select" style="width: 100px;"></select>
            <select id="daySelect" class="form-select" style="width: 100px;"></select>
            <button class="btn btn-primary" id="btnQuery">查詢</button>
          </div>

          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-calendar-alt me-2"></i>本週班表（唯讀）</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                  <thead id="weekHeader"></thead>
                  <tbody id="currentScheduleTable"></tbody>
                </table>
              </div>
            </div>
          </div>
          
          <div class="card mb-4" id="ganttCard">
            <div class="card-header d-flex align-items-center justify-content-between">
              <div><i class="fas fa-user-clock me-2"></i>可排人員甘特圖（按日檢視）</div>
              <div class="gantt-legend">
                <i class="fas fa-square me-1" style="color:#54bcc1;"></i> 想排時段（點一下即可加入下方編輯）
              </div>
            </div>
            <div class="card-body">
              <div class="d-flex gantt-toolbar mb-3" id="dayBtnGroup"></div>
              <div class="gantt" id="ganttContainer"></div>
            </div>
          </div>

          <div class="card" id="editorCard">
            <div class="card-header d-flex align-items-center justify-content-between">
              <div><i class="fas fa-edit me-2"></i>編輯班表（草稿｜可新增/修改）</div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="btnClearDraft"><i class="fas fa-eraser me-1"></i>清空草稿</button>
                <button class="btn btn-primary btn-sm" id="btnSaveDraft"><i class="fas fa-save me-1"></i>儲存班表</button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered align-middle" id="editorTable">
                  <thead class="table-light">
                    <tr id="editorHeaderRow"><th style="width:100px">時段</th></tr>
                  </thead>
                  <tbody id="editorBody"></tbody>
                </table>
              </div>
              <div class="small text-muted">每格可「+ 新增」或點名字旁的✎修改、×移除；儲存後會同步更新上方唯讀班表。</div>
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

  <div class="modal fade" id="slotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><span id="modalDate"></span>・<span id="modalPeriod"></span> 的可排名單</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-light border">點「姓名＋時段」按鈕即可加入/移除【編輯班表】草稿。</div>
          <div id="candidateArea" class="d-flex flex-wrap"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">完成</button></div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="assignForm">
        <div class="modal-header">
          <h5 class="modal-title" id="assignModalTitle">新增人員</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="assignDs">
          <input type="hidden" id="assignPeriod">
          <input type="hidden" id="assignOriginalName">

          <div class="mb-3">
            <label class="form-label">姓名</label>
            <select class="form-select" id="assignNameSelect" required>
                </select>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">開始時間</label>
              <input type="time" class="form-control" id="assignStart" required>
            </div>
            <div class="col-6">
              <label class="form-label">結束時間</label>
              <input type="time" class="form-control" id="assignEnd" required>
            </div>
          </div>

          <div class="form-text mt-2">儲存後此人會出現在該日「編輯班表」欄位。</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">取消</button>
          <button class="btn btn-primary" type="submit">儲存</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <script>
    /* ========= 基本設定 (此頁面JS用) ========= */
    const PERIODS = ['上午','晚上'];
    // API 檔案 (甘特圖.php, 確認班表.php) 應與此頁面在同一層
    const BASE_URL = ''; 
    const DEFAULT_HEADERS = { 'Content-Type':'application/json' };

    async function fetchJSON(path, options = {}) {
      try {
        // 呼叫同層的 API
        const res = await fetch(BASE_URL + path, { headers: DEFAULT_HEADERS, credentials:'include', ...options });
        if (!res.ok) throw new Error(res.status + ' ' + res.statusText);
        return await res.json();
      } catch (err) { console.warn('[API ERROR]', path, err); return null; }
    }

    /* ========= 日期 util ========= */
    function getMonday(d=new Date()){ const x=new Date(d); const dow=(x.getDay()+6)%7; x.setHours(0,0,0,0); x.setDate(x.getDate()-dow); return x; }
    function addDays(d,n){ const x=new Date(d); x.setDate(x.getDate()+n); return x; }
    function fmt(d){ return d.toISOString().slice(0,10); }
    function daysOfWeek(monday){ const a=[]; for(let i=0;i<7;i++) a.push(addDays(monday,i)); return a; }

    /* ========= 狀態 ========= */
    let scheduleAssignedMap = {}; // 已發布 (來自 確認班表.php)
    let draftAssignedMap = {};    // 草稿 (本地編輯用)
    let availabilityDetail = {};  // 可排 (來自 甘特圖.php)
    let employeeList = [];        // 🔥【問題一修正】全體員工清單 (來自 api_get_employees.php)

    function ensureDraftKey(ds){ draftAssignedMap[ds] = draftAssignedMap[ds] || { '上午':[], '晚上':[] }; }
    function inDraft(ds, period, name){ return (draftAssignedMap[ds]?.[period] || []).some(x => x.name === name); }

    function addToDraft(ds, period, name, time){
      ensureDraftKey(ds);
      if (!inDraft(ds, period, name)) {
        draftAssignedMap[ds][period].push({name, time});
        renderEditorCell(ds, period);
      }
    }
    function removeFromDraft(ds, period, name){
      ensureDraftKey(ds);
      draftAssignedMap[ds][period] = draftAssignedMap[ds][period].filter(x => x.name !== name);
      renderEditorCell(ds, period);
    }
    function upsertDraft(ds, period, name, time, originalName=null){
      ensureDraftKey(ds);
      const list = draftAssignedMap[ds][period];
      const targetIdx = list.findIndex(x => x.name === (originalName || name));
      if (targetIdx === -1) list.push({name, time});
      else {
        list[targetIdx] = {name, time};
        for (let i=list.length-1;i>=0;i--){ if (i!==targetIdx && list[i].name===name) list.splice(i,1); }
      }
      renderEditorCell(ds, period);
    }

    /* ========= UI 初始化 (日期) ========= */
    const yearSelect = document.getElementById('yearSelect');
    const monthSelect = document.getElementById('monthSelect');
    const daySelect   = document.getElementById('daySelect');
    
    // 🔥【已修改】預設日期為 7 天後 (下週)
    function initDateSelectors(){
      const defaultDate = new Date();
      defaultDate.setDate(defaultDate.getDate() + 7);
      
      const y0 = defaultDate.getFullYear();
      const m0 = defaultDate.getMonth() + 1;
      const d0 = defaultDate.getDate();

      for(let y=y0-3;y<=y0+3;y++) yearSelect.insertAdjacentHTML('beforeend', `<option value="${y}" ${y===y0?'selected':''}>${y}</option>`);
      for(let m=1;m<=12;m++) monthSelect.insertAdjacentHTML('beforeend', `<option value="${String(m).padStart(2,'0')}" ${m===m0?'selected':''}>${m}</option>`);
      for(let d=1;d<=31;d++) daySelect.insertAdjacentHTML('beforeend', `<option value="${String(d).padStart(2,'0')}" ${d===d0?'selected':''}>${d}</option>`);
    }
    function selectedDate(){ return new Date(+yearSelect.value, +monthSelect.value-1, +daySelect.value); }

    /* ========= 🔥【問題一修正】載入全體員工清單 ========= */
    async function loadEmployeeList() {
        const result = await fetchJSON(`api_get_employees.php`);
        if (result && result.success) {
            employeeList = result.data; // 儲存全域 [ {id: 1, name: "王小明"}, ... ]
            console.log('✅ 員工清單載入成功:', employeeList.length, '人');
        } else {
            console.error('載入員工清單失敗');
            alert('無法載入員工下拉選單，請檢查 api_get_employees.php');
        }
    }


   /* ========= 上方：已發布（唯讀） ========= */ 
    function renderWeekHeader(monday){
      const weekHeader = document.getElementById('weekHeader');
      const weekday = ['星期一','星期二','星期三','星期四','星期五','星期六','星期日'];
      const cells = daysOfWeek(monday).map((d,i)=>`<th>${weekday[i]}<br>${String(d.getMonth()+1).padStart(2,'0')}/${String(d.getDate()).padStart(2,'0')}</th>`);
      weekHeader.innerHTML = `<tr><th style="width:100px">時段</th>${cells.join('')}</tr>`;
    }

    async function loadSchedulePreview(monday) {
      const y = yearSelect.value, m = monthSelect.value, d = daySelect.value;
      const date = `${y}-${m}-${d}`;
      
      scheduleAssignedMap = {};
      const tbody = document.getElementById('currentScheduleTable');
      tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">載入中...</td></tr>`;

      const dates = Array.from({ length: 7 }, (_, i) => {
        const d = new Date(monday);
        d.setDate(d.getDate() + i);
        return fmt(d);
      });

      try {
        // 🔥【問題二修正】加入 cache-buster (時間戳)
        const cacheBuster = `&_=${new Date().getTime()}`;
        const data = await fetchJSON(`確認班表.php?date=${date}${cacheBuster}`); 
        
        if (!Array.isArray(data) || data.length === 0) {
          tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">沒有資料</td></tr>`;
          return;
        }

        const rowHtmls = [];
        data.forEach(apiRow => {
          const period = apiRow.timeSlot; 
          if (!PERIODS.includes(period)) return; 
          let cellsHtml = '';
          (apiRow.days || []).forEach((cellContent, dayIndex) => {
            const ds = dates[dayIndex]; 
            if (!scheduleAssignedMap[ds]) {
                scheduleAssignedMap[ds] = { '上午': [], '晚上': [] };
            }
            const items = (cellContent || '').split('<br>').filter(Boolean);
            items.forEach(item => {
              const match = item.match(/^(.*?)\s*\((.*?)\)$/); 
              if (match) {
                const name = match[1].trim();
                const time = match[2].trim();
                scheduleAssignedMap[ds][period].push({ name, time });
              }
            });
            cellsHtml += `<td style="white-space:pre-line">${cellContent || ''}</td>`;
          });
          rowHtmls.push(`<tr><th class="bg-light">${period}</th>${cellsHtml}</tr>`);
        });
        tbody.innerHTML = rowHtmls.join('');
      } catch (e) {
        console.error('載入班表發生錯誤:', e);
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">載入失敗：${e.message}</td></tr>`;
      }
    }

    /* ========= 甘特圖 (可排) ========= */
    async function loadAvailability(monday){
      availabilityDetail = {};
      // 載入可排時，不再需要順便抓員工清單 (datalist)
      
      for (const d of daysOfWeek(monday)) {
        const ds = fmt(d);
        try {
          const data = await fetchJSON(`甘特圖.php?date=${ds}`);
          if(!data || Array.isArray(data.error)) {
             console.warn('載入可排班資料失敗', ds, data?.error);
             continue;
          }
          for (const item of data) {
            const key = `${ds}::${item.period}`; 
            if (!availabilityDetail[key]) availabilityDetail[key] = [];
            availabilityDetail[key].push({ name: item.name, time: item.time });
            // allNames.add(item.name.trim()); // 🔥 已移除
          }
        } catch (err) {
          console.warn('載入可排班資料失敗', ds, err);
        }
      }
      
      // 🔥 已移除 (改由 loadEmployeeList 處理)
      // const nameOptions = document.getElementById('nameOptions');
      // nameOptions.innerHTML = Array.from(allNames).sort().map(n=>`<option value="${n}">`).join('');
    }
    
    const GANTT_START = "09:00";
    const GANTT_END   = "23:00";
    function toMin(t){ const [H,M]=t.split(':').map(Number); return H*60+M; }
    const MIN0 = toMin(GANTT_START), MIN1 = toMin(GANTT_END), RANGE = MIN1 - MIN0;

    function rangeToPos(range){
      const [a,b] = (range || '00:00-00:00').split('-');
      const s = Math.max(MIN0, toMin(a));
      const e = Math.min(MIN1, toMin(b));
      if(e<=s) return null;
      return { left: ((s - MIN0) / RANGE) * 100, width: ((e - s) / RANGE) * 100, label: `${a}-${b}` };
    }
    function collectDailyAvailability(ds){
      const am = availabilityDetail[`${ds}::上午`] || [];
      const pm = availabilityDetail[`${ds}::晚上`] || [];
      const all = [...am, ...pm];
      const map = new Map();
      all.forEach(({name, time})=>{
        if(!time) return;
        if(!map.has(name)) map.set(name, []);
        map.get(name).push(time);
      });
      return map; 
    }
    function guessPeriodByRange(range){
      const h = parseInt(range.slice(0,2), 10);
      return (h < 16) ? '上午' : '晚上';
    }

    function renderDayButtons(monday){
      const wrap = document.getElementById('dayBtnGroup');
      wrap.innerHTML = '';
      const labels = ['一','二','三','四','五','六','日'];
      daysOfWeek(monday).forEach((d,i)=>{
        const ds = fmt(d);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-day';
        btn.dataset.ds = ds;
        btn.innerHTML = `<div class="fw-semibold">星期${labels[i]}</div><div class="small">${d.getMonth()+1}/${d.getDate()}</div>`;
        btn.addEventListener('click', ()=> {
          wrap.querySelectorAll('button').forEach(b=> b.classList.remove('active','btn-secondary'));
          btn.classList.add('active','btn-secondary');
          renderGanttForDay(ds);
        });
        wrap.appendChild(btn);
      });
      const firstBtn = wrap.querySelector('button');
      if(firstBtn){ firstBtn.classList.add('active','btn-secondary'); renderGanttForDay(firstBtn.dataset.ds); }
    }
    function renderGanttHeader(container){
      const header = document.createElement('div');
      header.className = 'gantt-header';
      header.innerHTML = `
        <div class="name px-3 py-2 fw-semibold">可排人員</div>
        <div class="times">
          <div class="scale">
            ${Array.from({length:15}, (_,i)=> {
              const hour = 9 + i;
              return `<div>${String(hour).padStart(2,'0')}:00</div>`;
            }).join('')}
          </div>
        </div>`;
      container.appendChild(header);
    }

    function scrollToEditorCell(ds, period){
      const td = document.querySelector(`#editorBody td[data-ds="${ds}"][data-period="${period}"]`);
      if(!td) return;
      td.scrollIntoView({ behavior:'smooth', block:'center' });
      td.classList.add('pulse-highlight');
      setTimeout(()=> td.classList.remove('pulse-highlight'), 1400);
    }

    function renderGanttForDay(ds){
      const container = document.getElementById('ganttContainer');
      container.innerHTML = '';
      renderGanttHeader(container);
      const daily = collectDailyAvailability(ds);
      if(daily.size === 0){
        container.innerHTML += '<div class="p-4 text-muted">此日目前沒有想排的時段資料。</div>';
        return;
      }
      for(const [name, ranges] of daily){
        const row = document.createElement('div');
        row.className = 'gantt-row';
        row.innerHTML = `
          <div class="name">${name}</div>
          <div class="track">
            <div class="gantt-grid">
              ${Array.from({length:15}, ()=> '<div></div>').join('')}
            </div>
          </div>`;
        const track = row.querySelector('.track');
        (ranges || []).forEach(r=>{
          const pos = rangeToPos(r);
          if(!pos) return;
          const bar = document.createElement('div');
          bar.className = 'gantt-bar';
          bar.style.left  = pos.left + '%';
          bar.style.width = pos.width + '%';
          bar.textContent = pos.label;
          bar.title = `${name}｜${pos.label}（點一下加入下方編輯）`;
          bar.addEventListener('click', ()=>{
            const period = guessPeriodByRange(r); 
            addToDraft(ds, period, name, r);
            scrollToEditorCell(ds, period);
          });
          track.appendChild(bar);
        });
        container.appendChild(row);
      }
    }

    /* ========= 編輯班表（草稿） ========= */
    function renderEditorHeader(monday){
      const headRow = document.getElementById('editorHeaderRow');
      headRow.querySelectorAll('th:nth-child(n+2)').forEach(th => th.remove());
      const labels = ['一','二','三','四','五','六','日'];
      daysOfWeek(monday).forEach((d,i)=>{
        const th = document.createElement('th');
        th.innerHTML = `${d.getMonth()+1}/${d.getDate()}<br>星期${labels[i]}`;
        headRow.appendChild(th);
      });
    }
    function renderEditorGrid(monday){
      const tbody = document.getElementById('editorBody');
      tbody.innerHTML = '';
      PERIODS.forEach(period=>{
        const tr = document.createElement('tr');
        const th = document.createElement('th'); th.className='bg-light'; th.textContent = period; tr.appendChild(th);
        daysOfWeek(monday).forEach(d=>{
          const ds = fmt(d);
          ensureDraftKey(ds);
          const td = document.createElement('td');
          td.dataset.ds = ds; td.dataset.period = period;
          td.innerHTML = `
            <div class="d-flex flex-wrap gap-2 mb-2"></div>
            <button type="button" class="btn btn-sm btn-outline-primary add-assign-btn">
              <i class="fas fa-plus me-1"></i>新增
            </button>`;
          tr.appendChild(td);
          td.querySelector('.add-assign-btn').addEventListener('click', ()=> openAssignModal({ds, period}));
          renderEditorCell(ds, period); 
        });
        tbody.appendChild(tr);
      });
    }
    function renderEditorCell(ds, period){
      const td = document.querySelector(`#editorBody td[data-ds="${ds}"][data-period="${period}"]`);
      if(!td) return;
      const wrap = td.querySelector('div');
      wrap.innerHTML = '';
      (draftAssignedMap[ds]?.[period] || []).forEach(({name, time})=>{
        const chip = document.createElement('span');
        chip.className = 'badge text-bg-primary assign-chip d-inline-flex align-items-center';
        chip.innerHTML = `
          <i class="fas fa-user me-1"></i>${name}
          <small class="opacity-75 ms-1">${time || ''}</small>
          <button type="button" class="btn btn-light btn-sm chip-btn ms-2" title="修改"><i class="fas fa-pen"></i></button>
          <button type="button" class="btn btn-light btn-sm chip-btn" title="移除">×</button>`;
        const [btnEdit, btnDel] = chip.querySelectorAll('button');
        btnEdit.addEventListener('click', ()=> openAssignModal({ds, period, name, time}));
        btnDel .addEventListener('click', ()=> removeFromDraft(ds, period, name));
        wrap.appendChild(chip);
      });
    }

    const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
    const assignForm  = document.getElementById('assignForm');
    const assignNameSelect = document.getElementById('assignNameSelect'); // 🔥【問題一修正】抓 select

    function openAssignModal({ds, period, name='', time=''}) {
      document.getElementById('assignDs').value = ds;
      document.getElementById('assignPeriod').value = period;
      document.getElementById('assignOriginalName').value = name || '';
      document.getElementById('assignModalTitle').textContent = name ? '修改人員' : '新增人員';
      
      // 🔥【問題一修正】動態填入 <select>
      assignNameSelect.innerHTML = '<option value="">請選擇員工...</option>';
      employeeList.forEach(emp => {
          // value 存 "姓名"，因為你後端的 API (確認班表.php) 是用 "姓名" 去比對的
          assignNameSelect.insertAdjacentHTML('beforeend', 
              `<option value="${emp.name}">${emp.name}</option>`
          );
      });
      
      // 試圖選中
      assignNameSelect.value = name || '';

      let start = '', end = '';
      if (time && time.includes('-')) { [start, end] = time.split('-'); }
      document.getElementById('assignStart').value = start || '';
      document.getElementById('assignEnd').value   = end || '';
      assignModal.show();
    }
    
    assignForm.addEventListener('submit', (e)=>{
      e.preventDefault();
      const ds     = document.getElementById('assignDs').value;
      const period = document.getElementById('assignPeriod').value;
      const originalName = document.getElementById('assignOriginalName').value || null;
      
      // 🔥【問題一修正】從 <select> 讀取姓名
      const name   = assignNameSelect.value;
      
      const start  = document.getElementById('assignStart').value;
      const end    = document.getElementById('assignEnd').value;
      if(!name || !start || !end){ 
          if(!name) alert('請選擇姓名');
          return; 
      }
      const time = `${start}-${end}`;
      upsertDraft(ds, period, name, time, originalName);
      assignModal.hide();
    });

   /* ========= 儲存至確認班表 ========= */
    async function saveDraft(monday) {
      const payload = { week_start: fmt(monday), assignments: {} };
      daysOfWeek(monday).forEach(d => {
        const ds = fmt(d);
        payload.assignments[ds] = {};
        PERIODS.forEach(period => {
          payload.assignments[ds][period] = (draftAssignedMap[ds]?.[period] || []).map(x => {
            return { name: x.name, time: x.time, note: x.note || '' };
          });
        });
      });

      try {
        const result = await fetchJSON('確認班表.php', {
          method: 'POST',
          body: JSON.stringify(payload)
        });
        if (result && result.success) {
          // 🔥【問題二修正】
          // 呼叫 loadSchedulePreview，因為它現在有 cache-buster，會抓到最新資料
          await loadSchedulePreview(currentMonday);
          alert('班表已確認並儲存！');
        } else {
          alert('儲存失敗: ' + (result.message || '未知錯誤'));
        }
      } catch (err) {
        console.error('儲存班表錯誤', err);
        alert('儲存班表失敗，請稍後再試');
      }
    }

    /* ========= 刷新流程 ========= */
    
    // 🔥【已修改】預設日期為 7 天後 (下週)
    const defaultDateForLoad = new Date();
    defaultDateForLoad.setDate(defaultDateForLoad.getDate() + 7);
    
    let currentMonday = getMonday(defaultDateForLoad);

    async function refreshAll(){
      renderWeekHeader(currentMonday);
      renderEditorHeader(currentMonday);
      await loadSchedulePreview(currentMonday);
      // 🔥 用「已確認班表」的資料，初始化「編輯草稿區」
      draftAssignedMap = JSON.parse(JSON.stringify(scheduleAssignedMap));
      await loadAvailability(currentMonday);
      renderDayButtons(currentMonday);
      renderEditorGrid(currentMonday);
    }

    /* ========= 綁定按鈕事件 ========= */
    document.getElementById('btnQuery').addEventListener('click', async ()=>{
      currentMonday = getMonday(selectedDate());
      await refreshAll();
    });
    document.getElementById('btnSaveDraft').addEventListener('click', ()=> saveDraft(currentMonday));
    document.getElementById('btnClearDraft').addEventListener('click', ()=>{
      if(!confirm('確定要清空本週的草稿嗎？(此動作不會儲存，需手動儲存)')) return;
      draftAssignedMap = {}; 
      renderEditorGrid(currentMonday); 
    });


    // ---- 修正：頁尾 JS (參照 員工資料表.php) ----
    
    // 注入 PHP 變數 (供 loadLoggedInUser 讀取頭像)
    const API_BASE  = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const DATA_BASE = <?php echo json_encode($DATA_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;

    const $  = s => document.querySelector(s);
    const el = id => document.getElementById(id);

    // 今日日期
    const dateEl = el('currentDate');
    if(dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('zh-TW', {year:'numeric',month:'long',day:'numeric',weekday:'long'});
    }

    // 側欄開關 (已在頂部 navbar 綁定)
    el('sidebarToggle')?.addEventListener('click', e => { 
        e.preventDefault(); 
        document.body.classList.toggle('sb-sidenav-toggled'); 
    });

    // 取得登入者資訊 (from 員工資料表.php)
    async function loadLoggedInUser(){
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        console.log('✅ 班表管理 已登入:', userName, 'ID:', userId);
        
        // 載入真實頭像 (使用 API_BASE)
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

   // 頁面初始化
    window.addEventListener('DOMContentLoaded', async ()=>{
        // 1. 載入版型共用資訊 (頭像/名稱)
        await loadLoggedInUser();
        
        // 🔥【問題一修正】先載入全體員工清單
        await loadEmployeeList();
        
        // 2. 執行此頁面的核心邏輯 (班表)
        initDateSelectors(); // 🔥 會預設選取下週
        await refreshAll();  // 🔥 會預設載入下週
    });
  </script>
</body>
</html>