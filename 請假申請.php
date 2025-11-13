<?php 
// 🔥 修正：使用您系統的標準 auth_check.php
require_once __DIR__ . '/includes/auth_check.php'; 

// 🔥 修正：檢查 A, B, C 任何一級登入即可 (true = 未登入會跳轉)
// 假設所有員工都能請假
if (!check_user_level('A', false) && !check_user_level('B', false) && !check_user_level('C', false)) {
    // 如果 '不是A' 而且 '也不是B' 而且 '也不是C' (即未登入)
    show_no_permission_page(); // 會 exit
}

// 🔥 修正：取得用戶資訊
$user = get_user_info();
$userName  = $user['name'];
$userId    = $user['uid'];
$userLevel = $user['level'];

$pageTitle = '請假申請 - 員工管理系統'; // 標題

// 🔥 修正：定義 API 基礎路徑 (給 JS 用)
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
    body{ background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%); font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh; }

    .sb-topnav{ background: var(--dark-bg) !important; border:none; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand{
      font-weight:700; font-size:1.5rem;
      background: linear-gradient(45deg,#ffffff,#ffffff);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
    }
    
    /* 🔥 修正：加入您其他頁面的搜尋框和頭像樣式 */
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

    .sb-sidenav{ background: linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color: rgba(255,255,255,.7) !important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px !important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{
      content:''; position:absolute; left:0; top:0; height:100%; width:4px;
      background: linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0;
    }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform: scaleY(1); }
    .sb-sidenav .nav-link i{ width:20px; text-align:center; margin-right:10px; font-size:1rem; }
    .sb-sidenav-footer{ background: rgba(255,255,255,.1) !important; color:#fff !important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }

    .container-fluid{ padding:30px !important; }
    h1{
      background: var(--primary-gradient);
      background-clip:text; -webkit-background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent;
      font-weight:700; font-size:2.5rem; margin-bottom:30px;
    }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight:600; }

    .table{ border-radius: var(--border-radius); overflow:hidden; background:#fff; box-shadow: var(--card-shadow); }
    .table thead th{ background: var(--primary-gradient); color:#000; border:none; font-weight:600; padding:15px; }
    .table tbody td{ padding:15px; vertical-align:middle; border-color: rgba(0,0,0,.05); }
    .table-hover tbody tr:hover{ background: rgba(227,23,111,.05); transform: scale(1.01); }

    .btn-primary{ background: var(--primary-gradient); border:none; border-radius:25px; }
    .btn-primary:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(209,209,209,.976); }

    .badge-status{ padding:.45rem .7rem; border-radius:999px; font-weight:600; }
    .status-pending{ background: rgba(255,193,7,.15); color:#8a6d00; border:1px solid rgba(255,193,7,.35); }
    .status-approved{ background: rgba(25,135,84,.15); color:#0f5e3c; border:1px solid rgba(25,135,84,.35); }
    .status-rejected{ background: rgba(220,53,69,.15); color:#7a1821; border:1px solid rgba(220,53,69,.35); }

    .upload-preview{ border:1px dashed rgba(0,0,0,.15); border-radius:12px; padding:10px; display:none; }
    .upload-preview img{ max-width:160px; border-radius:8px; }
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
      <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
      <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

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
                <a class="nav-link" href="員工資料表.php">員工資料表</a>
                <a class="nav-link" href="班表管理.php">班表管理</a>
                <a class="nav-link" href="日報表記錄.php">日報表記錄</a>
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
                    <a class="nav-link" href="商品管理.php">商品管理</a>
                  </nav>
                </div>
                <a class="nav-link" href="日報表.php"> <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>日報表</a>
                <a class="nav-link" href="薪資管理.html"><div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>薪資記錄</a>
                <a class="nav-link" href="班表.html"><div class="sb-nav-link-icon"><i class="fas fa-calendar-days"></i></div>班表</a>
              </nav>
            </div>
            
            <a class="nav-link active" href="請假申請.php"><div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>請假申請</a>

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
            <h1>請假申請</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">請假申請</li>
          </ol>

          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-file-signature me-2"></i>提交請假單</div>
            <div class="card-body">
              <form id="leaveForm">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">假別</label>
                    <select class="form-select" id="leaveType" required>
                      <option value="" disabled selected>請選擇</option>
                      <option>事假</option>
                      <option>病假</option>
                      <option>生理假</option>
                      <option>特休</option>
                      <option>婚假</option>
                      <option>喪假</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">開始日期</label>
                    <input type="date" class="form-control" id="startDate" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">結束日期</label>
                    <input type="date" class="form-control" id="endDate" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">請假原因</label>
                    <textarea class="form-control" id="reason" rows="3" placeholder="可簡述原因(選填)"></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">上傳證明(照片,選填)</label>
                    <input type="file" class="form-control" id="photo" accept="image/*">
                    <div class="form-text">支持 jpg / png / heic;大小建議 &lt; 5MB</div>
                    <div class="upload-preview mt-2" id="previewBox">
                      <img id="previewImg" alt="預覽" />
                    </div>
                  </div>
                </div>
                <div class="text-end mt-3">
                  <button type="button" class="btn btn-outline-secondary" id="btnClear">清除</button>
                  <button type="submit" class="btn btn-primary ms-2" id="btnSubmit">
                    <i class="fas fa-paper-plane me-1"></i>送出申請
                  </button>
                </div>
              </form>
              <div id="formMsg" class="mt-3"></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div><i class="fas fa-history me-2"></i>我的請假紀錄</div>
              <small class="text-muted">最新筆在最上方</small>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                  <thead>
                    <tr>
                      <th>假別</th>
                      <th>開始</th>
                      <th>結束</th>
                      <th>原因</th>
                      <th>狀態</th>
                    </tr>
                  </thead>
                  <tbody id="myLeaveTable">
                    <tr>
                      <td colspan="5" class="text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>載入中...
                      </td>
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
    // 初始化
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e => { e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled'); });

    // 🔥 修正：從 PHP 獲取 API 基礎路徑
    const API_BASE = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_SUBMIT = API_BASE + '/新增請假.php';
    const API_MYLIST = API_BASE + '/查詢請假紀錄.php'; // [注意] 這個 API 檔案您尚未提供

    // 圖片預覽
    const photoInput = document.getElementById('photo');
    const previewBox = document.getElementById('previewBox');
    const previewImg = document.getElementById('previewImg');
    
    photoInput.addEventListener('change', () => {
      const f = photoInput.files?.[0];
      if (!f) { 
        previewBox.style.display='none'; 
        previewImg.src=''; 
        return; 
      }
      if (!f.type.startsWith('image/')) { 
        alert('僅支持圖片檔'); 
        photoInput.value=''; 
        return; 
      }
      if (f.size > 5 * 1024 * 1024) { 
        alert('檔案大小請小於 5MB'); 
        photoInput.value=''; 
        return; 
      }
      const url = URL.createObjectURL(f);
      previewImg.src = url;
      previewBox.style.display = 'block';
    });

    // 清除按鈕
    document.getElementById('btnClear').addEventListener('click', () => {
      document.getElementById('leaveForm').reset();
      previewBox.style.display = 'none';
      previewImg.src='';
      showFormMsg('');
    });

    // 表單送出
    document.getElementById('leaveForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const type  = document.getElementById('leaveType').value || '';
      const start = document.getElementById('startDate').value || '';
      const end   = document.getElementById('endDate').value || '';
      
      if (!type || !start || !end) { 
        showFormMsg('請先完整選擇假別與起訖日期', 'danger'); 
        return; 
      }
      
      if (new Date(end) < new Date(start)) { 
        showFormMsg('結束日期不可早於開始日期', 'danger'); 
        return; 
      }

      const btn = document.getElementById('btnSubmit');
      btn.disabled = true; 
      const oldHtml = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>送出中...';

      try {
        const fd = new FormData();
        fd.append('leaveType', type);
        fd.append('startDate', start);
        fd.append('endDate', end);
        fd.append('reason', document.getElementById('reason').value || '');
        if (photoInput.files?.[0]) {
          fd.append('photo', photoInput.files[0]);
        }

        const res = await fetch(API_SUBMIT, { 
          method: 'POST', 
          body: fd,
          credentials: 'include' // 🔥 修正：確保送出 cookie (session)
        });
        
        const text = await res.text();
        
        if (!res.ok) {
          throw new Error(text || '送出失敗');
        }
        
        showFormMsg(text || '已送出申請!', 'success');

        // 重整列表
        await loadMyLeave();
        
        // 清空表單
        document.getElementById('leaveForm').reset();
        previewBox.style.display='none'; 
        previewImg.src='';
        
      } catch(err) {
        console.error('送出錯誤:', err);
        showFormMsg('送出失敗: ' + err.message, 'danger');
      } finally {
        btn.disabled = false; 
        btn.innerHTML = oldHtml;
      }
    });

    // 顯示訊息
    function showFormMsg(text, type='secondary') {
      const slot = document.getElementById('formMsg');
      slot.innerHTML = text ? `<div class="alert alert-${type} mb-0" role="alert">${text}</div>` : '';
    }

    // 載入請假紀錄
    async function loadMyLeave() {
      const tbody = document.getElementById('myLeaveTable');
      
      try {
        const res = await fetch(API_MYLIST, {credentials: 'include'}); // 🔥 修正：確保送出 cookie
        
        if (!res.ok) {
          throw new Error('HTTP ' + res.status);
        }
        
        const json = await res.json();
        // 假設 API 回傳 { data: [...] }
        const list = Array.isArray(json) ? json : (json.data || []);
        
        tbody.innerHTML = '';
        
        if (list.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="5" class="text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料
              </td>
            </tr>
          `;
          return;
        }
        
        // 渲染資料
        tbody.innerHTML = list.map(item => `
          <tr>
            <td>${escapeHtml(item.type || item.leave_type_name || '')}</td>
            <td>${escapeHtml(item.start || item.start_date || '')}</td>
            <td>${escapeHtml(item.end || item.end_date || '')}</td>
            <td class="text-start">${escapeHtml(item.reason || '')}</td>
            <td>${renderStatus(item.status)}</td>
          </tr>
        `).join('');
        
      } catch(err) {
        console.error('載入請假紀錄失敗:', err);
        tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-danger py-4">
              <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
              載入失敗: ${err.message} (API: ${API_MYLIST})
            </td>
          </tr>
        `;
      }
    }

    // 狀態渲染
    function renderStatus(s) {
      const status = parseInt(s);
      
      if (status === 2) {
        return `<span class="badge-status status-approved">已通過</span>`;
      }
      
      if (status === 3) {
        return `<span class="badge-status status-rejected">已駁回</span>`;
      }
      
      return `<span class="badge-status status-pending">審核中</span>`;
    }

    // HTML 跳脫
    function escapeHtml(str) {
      return String(str).replace(/[&<>"']/g, s => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      }[s]));
    }
    
    // 🔥 修正：加入 loadLoggedInUser 函數 (用於載入頭像)
    async function loadLoggedInUser(){
        try {
            const r = await fetch(API_BASE + '/me.php', {credentials:'include'});
            if(r.ok) {
            const data = await r.json();
            if(data.avatar_url) {
                const avatar = document.querySelector('.navbar .user-avatar');
                if(avatar) {
                    avatar.src = data.avatar_url + (data.avatar_url.includes('?')?'&':'?') + 'v=' + Date.now();
                }
            }
            }
        } catch(e) {
            console.warn('載入頭像失敗:', e);
        }
    }

    // 頁面載入時執行
    window.addEventListener('DOMContentLoaded', () => {
      loadMyLeave();
      loadLoggedInUser(); // 🔥 修正：同時載入用戶頭像
    });
  </script>
  </body>
</html>