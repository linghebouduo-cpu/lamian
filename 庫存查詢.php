<?php
// 如果 api 在 /lamian-ukn/api，這行不用改
$API_BASE_URL = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>庫存查詢 - 員工管理系統</title>

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
      --border-radius: 20px;
      --transition: all .3s cubic-bezier(.4,0,.2,1);
    }
    *{ transition: var(--transition); }
    body{ background:#fff; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh; }

    .sb-topnav{ background: var(--dark-bg) !important; border:none; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand{ font-weight:700; font-size:1.5rem; background: linear-gradient(45deg,#ffffff,#ffffff);
      -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-fill-color:transparent; }

    .sb-sidenav{ background: linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color: rgba(255,255,255,.7) !important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px !important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{ content:''; position:absolute; left:0; top:0; height:100%; width:4px; background: linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform: scaleY(1); }
    .sb-sidenav .nav-link i{ width:20px; text-align:center; margin-right:10px; font-size:1rem; }
    .sb-sidenav-footer{ background: rgba(255,255,255,.1) !important; color:#fff !important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }

    .container-fluid{ padding:30px !important; }
    h1{ background: var(--primary-gradient); -webkit-background-clip:text; background-clip:text;
      color:transparent; -webkit-text-fill-color:transparent; font-weight:700; font-size:2.5rem; margin-bottom:30px; }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight:600; }

    .table{ border-radius: var(--border-radius); overflow:hidden; background:#fff; box-shadow: var(--card-shadow); }
    .table thead th{ background: var(--primary-gradient); color:#000; border:none; font-weight:600; padding:15px; }
    .table tbody td{ padding:15px; vertical-align:middle; border-color: rgba(0,0,0,.05); }
    .table-hover tbody tr:hover{ background: rgba(227,23,111,.05); transform: scale(1.01); }

    .sb-topnav .form-control{ border-radius:25px; border:2px solid transparent; background:rgba(255,255,255,.2); color:#fff; }
    .sb-topnav .form-control:focus{ background:rgba(255,255,255,.3); border-color:rgba(255,255,255,.5); box-shadow:0 0 20px rgba(255,255,255,.2); color:#fff; }

    .btn-primary{ background: var(--primary-gradient); border:none; border-radius:25px; }
    .btn-primary:hover{ transform:scale(1.05); box-shadow:0 10px 25px rgba(209,209,209,.976); }
    .form-select, .form-control{ border-radius:12px; }
  </style>
</head>

<body class="sb-nav-fixed">
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.html">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
      <div class="input-group">
        <input class="form-control" type="text" placeholder="Search for..." aria-label="Search" />
        <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
      </div>
    </form>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fas fa-user fa-fw"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
          <li><a class="dropdown-item" href="#!">Settings</a></li>
          <li><a class="dropdown-item" href="#!">Activity Log</a></li>
          <li><hr class="dropdown-divider" /></li>
          <li><a class="dropdown-item" href="#!">Logout</a></li>
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
            <a class="nav-link" href="charts.html"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
            <a class="nav-link" href="tables.html"><div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>Tables</a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:</div>
          Start Bootstrap
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>庫存查詢</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.html">首頁</a></li>
            <li class="breadcrumb-item active">庫存查詢</li>
          </ol>

          <div class="card mb-4">
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">關鍵字</label>
                  <input type="text" class="form-control" id="keywordInput" placeholder="輸入品名 / 編號 / 單位 / 類別 / 經手人 / 時間...">
                </div>
                <div class="col-md-3">
                  <label class="form-label">類別</label>
                  <select id="categorySelect" class="form-select">
                    <option value="">全部</option>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button class="btn btn-primary me-2" id="btnSearch"><i class="fas fa-search me-1"></i>查詢</button>
                  <button class="btn btn-outline-secondary" id="btnClear">清除</button>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><i class="fas fa-boxes-stacked me-2"></i>庫存列表</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>編號</th>
                      <th>品項名稱</th>
                      <th>類別</th>
                      <th>庫存數量</th>
                      <th>單位</th>
                      <th>進貨時間</th>
                      <th>進貨人</th>
                    </tr>
                  </thead>
                  <tbody id="inventoryTable">
                    <tr id="noDataRow" class="d-none">
                      <td colspan="7" class="text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>暫無資料
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
            <div class="text-muted">Copyright &copy; Xxing0625</div>
            <div><a href="#">Privacy Policy</a> &middot; <a href="#">Terms &amp; Conditions</a></div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- JS -->
  <script>
    const API_INVENTORY = <?php echo json_encode($API_BASE_URL . '/product_stock_list.php', JSON_UNESCAPED_SLASHES); ?>;
  </script>

  <script>
    // ===== 工具 =====
    const norm = s => String(s ?? '').normalize('NFKC').trim().toLowerCase();
    const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // 今日日期 / 側欄
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    // ===== 狀態 =====
    let rawInventory = [];   // { id, name, category, category_key, category_id, unit, quantity, purchase_time, purchaser }
    let filtered = [];

    // ===== 初始化 =====
    window.addEventListener('DOMContentLoaded', async () => {
      bindEvents();
      await loadInventory();
    });

    function bindEvents(){
      const run = () => applyFilter();
      document.getElementById('btnSearch').addEventListener('click', run);
      document.getElementById('btnClear').addEventListener('click', () => {
        document.getElementById('keywordInput').value = '';
        document.getElementById('categorySelect').value = ''; // 全部
        run();
      });
      document.getElementById('keywordInput').addEventListener('keydown', e => { if(e.key==='Enter') run(); });
      document.getElementById('categorySelect').addEventListener('change', run);
    }

    // ===== 載入（以 text 收、遇到非 JSON 直接顯示原文，方便找 php 錯誤） =====
    async function loadInventory(){
      const url = API_INVENTORY + '?limit=2000&t=' + Date.now();
      const tbody = document.getElementById('inventoryTable');
      try{
        const res  = await fetch(url, { credentials:'include' });
        const text = await res.text();

        let data;
        try { data = JSON.parse(text); }
        catch(e){
          tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-start p-3">
            <div class="fw-bold mb-2">API 回傳不是 JSON，可能是 PHP 錯誤訊息：</div>
            <pre class="small mb-0" style="white-space:pre-wrap">${escapeHtml(text)}</pre>
          </td></tr>`;
          return;
        }

        const rows = Array.isArray(data) ? data : (data.data || data.rows || []);
        if(!Array.isArray(rows)){
          tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-3">API 格式不符，預期為陣列/有 data 欄位。</td></tr>`;
          return;
        }

        // 映射 + 正規化
        rawInventory = rows.map(r => {
          const catLabel = (r.category ?? r.category_name ?? r.category_text ?? r.category_label ?? '');
          const catId    = (r.category_id ?? r.cat_id ?? r.cid ?? null);
          const lastAt   = (r.last_update_iso ?? r.last_update_at ?? r.last_update ?? '');
          return {
            id:            r.id ?? r.item_id ?? '',
            name:          r.name ?? r.item_name ?? '',
            category:      String(catLabel || '').trim(),
            category_key:  norm(catLabel || (catId ?? '')),
            category_id:   (catId === null || catId === undefined) ? '' : String(catId),
            unit:          r.unit ?? '',
            quantity:      Number(r.quantity ?? 0),
            purchase_time: String(lastAt),
            purchaser:     r.updated_by ?? r.purchaser ?? ''
          };
        });

        // 類別下拉：value 使用「標準化 key」，label 顯示中文；若只有 id 仍可用
        const sel = document.getElementById('categorySelect');
        const map = new Map(); // key -> label
        rawInventory.forEach(x=>{
          const key   = x.category_key;
          const label = x.category || (x.category_id ? ('分類 #' + x.category_id) : '');
          if(key) map.set(key, label);
        });
        sel.innerHTML = '';
        sel.appendChild(new Option('全部',''));
        [...map.entries()]
          .sort((a,b)=>a[1].localeCompare(b[1],'zh-Hant'))
          .forEach(([key,label])=> sel.appendChild(new Option(label, key)));

        filtered = [...rawInventory];
        renderTable();
      }catch(err){
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger p-3">載入失敗：${escapeHtml(err.message)}</td></tr>`;
      }
    }

    // ===== 過濾（關鍵字 + 類別） =====
    function applyFilter(){
      const kw     = norm(document.getElementById('keywordInput').value);
      const selKey = document.getElementById('categorySelect').value; // 標準化 key

      filtered = rawInventory.filter(item => {
        // 類別比對：同時支援 key（中文或數字標準化）、原始中文、純 id
        const inCat =
          !selKey ||
          item.category_key === selKey ||
          norm(item.category) === selKey ||
          (item.category_id && norm(item.category_id) === selKey);

        // 關鍵字：全欄位模糊
        const hay = [
          item.id, item.name, item.category, item.category_id,
          item.unit, item.purchaser, item.purchase_time, item.quantity
        ].map(x => String(x ?? '')).join(' ').toLowerCase();

        const inKw = !kw || hay.includes(kw);
        return inCat && inKw;
      });

      renderTable();
    }

    function renderTable(){
      const tbody = document.getElementById('inventoryTable');
      const noRow = document.getElementById('noDataRow');
      tbody.innerHTML = '';

      if(filtered.length === 0){
        noRow.classList.remove('d-none');
        tbody.appendChild(noRow);
        return;
      }
      noRow.classList.add('d-none');

      tbody.innerHTML = filtered.map(item => `
        <tr>
          <td>${escapeHtml(item.id)}</td>
          <td class="text-start">${escapeHtml(item.name)}</td>
          <td>${escapeHtml(item.category || (item.category_id ? ('分類 #' + item.category_id) : ''))}</td>
          <td>${escapeHtml(item.quantity)}</td>
          <td>${escapeHtml(item.unit)}</td>
          <td>${escapeHtml(item.purchase_time)}</td>
          <td>${escapeHtml(item.purchaser)}</td>
        </tr>
      `).join('');
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script src="js/scripts.js"></script>
</body>
</html>
