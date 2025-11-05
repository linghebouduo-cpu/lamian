<?php /* 目前無後端邏輯。此檔案以 PHP 副檔名供伺服器解析。*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>班表管理 - 員工管理系統</title>

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
    * { transition: var(--transition); }
    body {
      background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }

    .sb-topnav { background: var(--dark-bg) !important; border: none; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand {
      font-weight: 700; font-size: 1.5rem;
      background: linear-gradient(45deg, #ffffff, #ffffff);
      background-clip: text; -webkit-background-clip: text;
      color: transparent; -webkit-text-fill-color: transparent; text-shadow: none;
    }

    .sb-sidenav { background: linear-gradient(180deg, #fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading { color: rgba(255,255,255,.7) !important; font-weight: 600; font-size: .85rem; text-transform: uppercase; letter-spacing: 1px; padding: 20px 15px 10px 15px !important; margin-top: 15px; }
    .sb-sidenav .nav-link { border-radius: 15px; margin: 5px 15px; padding: 12px 15px; position: relative; overflow: hidden; color: rgba(255,255,255,.9) !important; font-weight: 500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover { background: rgba(255,255,255,.15) !important; transform: translateX(8px); box-shadow: 0 8px 25px rgba(0,0,0,.2); color: #fff !important; }
    .sb-sidenav .nav-link.active { background: rgba(255,255,255,.2) !important; color: #fff !important; font-weight: 600; box-shadow: 0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: linear-gradient(45deg, #ffffff, #ffffff); transform: scaleY(0); transition: var(--transition); border-radius: 0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before { transform: scaleY(1); }
    .sb-sidenav .nav-link i { width: 20px; text-align: center; margin-right: 10px; font-size: 1rem; }
    .sb-sidenav-footer { background: rgba(255,255,255,.1) !important; color: #fff !important; border-top: 1px solid rgba(255,255,255,.2); padding: 20px 15px; margin-top: 20px; }

    .container-fluid { padding: 30px !important; }
    h1 {
      background: var(--primary-gradient);
      background-clip: text; -webkit-background-clip: text;
      color: transparent; -webkit-text-fill-color: transparent;
      font-weight: 700; font-size: 2.5rem; margin-bottom: 30px;
    }

    .breadcrumb { background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 15px 20px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }

    .table { border-radius: var(--border-radius); overflow: hidden; background: #fff; box-shadow: var(--card-shadow); }
    .table thead th { background: var(--primary-gradient); color: #000; border: none; font-weight: 600; padding: 15px; }
    .table tbody td { padding: 15px; vertical-align: middle; border-color: rgba(0,0,0,.05); }
    .table tbody tr:hover { background: rgba(227,23,111,.05); transform: scale(1.01); }

    .sb-topnav .form-control { border-radius: 25px; border: 2px solid transparent; background: rgba(255,255,255,.2); color: #fff; }
    .sb-topnav .form-control:focus { background: rgba(255,255,255,.3); border-color: rgba(255,255,255,.5); box-shadow: 0 0 20px rgba(255,255,255,.2); color: #fff; }
    .btn-primary { background: var(--primary-gradient); border: none; border-radius: 25px; }
    .btn-primary:hover { transform: scale(1.05); box-shadow: 0 10px 25px rgba(209,209,209,.976); }

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
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
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
    <!-- Side Nav -->
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

    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>班表管理</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.html" class="text-decoration-none">首頁</a></li>
            <li class="breadcrumb-item active">班表管理</li>
          </ol>

          <!-- 查詢日期 -->
          <div class="d-flex justify-content-start align-items-center gap-2 mb-4">
            <select id="yearSelect" class="form-select" style="width: 100px;"></select>
            <select id="monthSelect" class="form-select" style="width: 100px;"></select>
            <select id="daySelect" class="form-select" style="width: 100px;"></select>
            <button class="btn btn-primary" id="btnQuery">查詢</button>
          </div>

          <!-- 本週班表（唯讀） -->
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

          <!-- 可排人員甘特圖（按日檢視） -->
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

          <!-- 編輯班表（新增 / 修改 / 刪除） -->
          <div class="card">
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
            <div class="text-muted">Copyright &copy; Xxing0625</div>
            <div>
              <a href="#">Privacy Policy</a> &middot;
              <a href="#">Terms &amp; Conditions</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>

  <!-- Modal：可排名單（保留） -->
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

  <!-- Modal：新增/修改 指定人員 -->
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
            <input class="form-control" id="assignName" list="nameOptions" placeholder="輸入或選擇姓名" required>
            <datalist id="nameOptions"></datalist>
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

          <div class="form-text mt-2">儲存後此人會出現在該日「編輯班表」欄位，也會影響下方可排人員的計數。</div>
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
    /* ========= 基本設定 ========= */
    const PERIODS = ['上午','晚上'];
    const BASE_URL = '/api';   // TODO: 換你的 API
    const DEFAULT_HEADERS = { 'Content-Type':'application/json' };

    async function fetchJSON(path, options = {}) {
      try {
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
    let scheduleAssignedMap = {}; // 已發布
    let draftAssignedMap = {};    // 草稿
    let availabilityDetail = {};  // 可排

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

    /* ========= UI 初始化 ========= */
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW', {year:'numeric', month:'long', day:'numeric', weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e => {
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    const yearSelect = document.getElementById('yearSelect');
    const monthSelect = document.getElementById('monthSelect');
    const daySelect   = document.getElementById('daySelect');
    function initDateSelectors(){
      const today = new Date(); const y0=today.getFullYear();
      for(let y=y0-3;y<=y0+3;y++) yearSelect.insertAdjacentHTML('beforeend', `<option value="${y}" ${y===y0?'selected':''}>${y}</option>`);
      for(let m=1;m<=12;m++) monthSelect.insertAdjacentHTML('beforeend', `<option value="${String(m).padStart(2,'0')}" ${m===today.getMonth()+1?'selected':''}>${m}</option>`);
      for(let d=1;d<=31;d++) daySelect.insertAdjacentHTML('beforeend', `<option value="${String(d).padStart(2,'0')}" ${d===today.getDate()?'selected':''}>${d}</option>`);
    }
    function selectedDate(){ return new Date(+yearSelect.value, +monthSelect.value-1, +daySelect.value); }

    /* ========= 上方：已發布（唯讀） ========= */
    function renderWeekHeader(monday){
      const weekHeader = document.getElementById('weekHeader');
      const weekday = ['星期一','星期二','星期三','星期四','星期五','星期六','星期日'];
      const cells = daysOfWeek(monday).map((d,i)=>`<th>${weekday[i]}<br>${String(d.getMonth()+1).padStart(2,'0')}/${String(d.getDate()).padStart(2,'0')}</th>`);
      weekHeader.innerHTML = `<tr><th style="width:100px"></th>${cells.join('')}</tr>`;
    }
    function normalizePeriod(s){ if(!s) return ''; s=String(s); if(s.includes('早')||s.includes('上')) return '上午'; if(s.includes('晚')) return '晚上'; return s; }
    function parseNames(cell){ return String(cell||'').split(/[\s,，、/|\n]+/).map(s=>s.trim()).filter(Boolean); }

    async function loadSchedulePreview(monday){
      const y=yearSelect.value, m=monthSelect.value, d=daySelect.value;
      const date = `${y}-${m}-${d}`;
      scheduleAssignedMap = {};
      const tbody = document.getElementById('currentScheduleTable');

      try{
        const res = await fetch(`api_schedule.php?date=${date}`); // TODO 換你的 API
        const data = await res.json();

        if(!Array.isArray(data) || data.length===0){
          tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">沒有資料</td></tr>`;
        }else{
          tbody.innerHTML = data.map(row => `
            <tr>
              <th class="bg-light">${row.timeSlot ?? ''}</th>
              ${(row.days ?? Array(7).fill('')).map(v => `<td>${v || ''}</td>`).join('')}
            </tr>
          `).join('');
        }

        daysOfWeek(monday).forEach(d => { const ds=fmt(d); scheduleAssignedMap[ds] = { '上午':[], '晚上':[] }; });
        (Array.isArray(data)?data:[]).forEach(row=>{
          const p = normalizePeriod(row.timeSlot);
          if(!PERIODS.includes(p)) return;
          (row.days || []).forEach((cell, idx)=>{
            const ds = fmt(addDays(monday, idx));
            scheduleAssignedMap[ds][p] = parseNames(cell).map(n=>({name:n}));
          });
        });
      }catch(e){
        console.warn(e);
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">載入失敗</td></tr>`;
      }
    }

    /* ========= 可排（Demo or 之後接 API） ========= */
    function demoAvailability(monday){
      const names = ["小王","小明","小火","小樹","小舖","33"];
      const timesAM = ["10:00-14:00","11:00-15:00","12:00-16:00"];
      const timesPM = ["16:00-20:00","17:00-22:00","18:00-22:00"];
      availabilityDetail = {};
      daysOfWeek(monday).forEach(d=>{
        const ds = fmt(d);
        availabilityDetail[`${ds}::上午`] = names.map((n,i)=>({name:n, time: timesAM[i%timesAM.length]}));
        availabilityDetail[`${ds}::晚上`] = names.map((n,i)=>({name:n, time: timesPM[(i+1)%timesPM.length]}));
      });
    }
    async function loadAvailability(monday){
      // const data = await fetchJSON(`/availability/weekly?start=${fmt(monday)}`);
      // availabilityDetail = data || {};
      demoAvailability(monday);
    }

    /* ====== 甘特圖工具 ====== */
    const GANTT_START = "09:00";
    const GANTT_END   = "23:00";
    function toMin(t){ const [H,M]=t.split(':').map(Number); return H*60+M; }
    const MIN0 = toMin(GANTT_START), MIN1 = toMin(GANTT_END), RANGE = MIN1 - MIN0;

    function rangeToPos(range){
      const [a,b] = range.split('-');
      const s = Math.max(MIN0, toMin(a));
      const e = Math.min(MIN1, toMin(b));
      if(e<=s) return null;
      return { left: ((s - MIN0) / RANGE) * 100, width: ((e - s) / RANGE) * 100, label: `${a}-${b}`, startH: a.slice(0,2) };
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
      return map; // Map<name, string[]>
    }
    function guessPeriodByRange(range){
      // 16:00 以前 => 上午；之後 => 晚上（簡單切分，跨時段時以開始時間為準）
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
        <div class="name px-3 py-2 fw-semibold">時段</div>
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

    // 點甘特條 → 直接加入下面草稿，並捲到對應格子
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
        const empty = document.createElement('div');
        empty.className = 'p-4 text-muted';
        empty.textContent = '此日目前沒有想排的時段資料。';
        container.appendChild(empty);
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
          // 點擊：加入草稿 → 捲到編輯表
          bar.addEventListener('click', ()=>{
            const period = guessPeriodByRange(r); // 上午/晚上
            addToDraft(ds, period, name, r);
            scrollToEditorCell(ds, period);
          });
          track.appendChild(bar);
        });

        container.appendChild(row);
      }
    }

    /* ========= 編輯班表（新增/修改/刪除） ========= */
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
    const nameOptions = document.getElementById('nameOptions');

    function buildNameDatalist(){
      const set = new Set();
      Object.values(availabilityDetail).forEach(list => (list||[]).forEach(x=> set.add(x.name)));
      Object.values(scheduleAssignedMap).forEach(p=> PERIODS.forEach(k=> (p[k]||[]).forEach(x=> set.add(x.name))));
      Object.values(draftAssignedMap).forEach(p=> PERIODS.forEach(k=> (p[k]||[]).forEach(x=> set.add(x.name))));
      nameOptions.innerHTML = Array.from(set).sort().map(n=>`<option value="${n}">`).join('');
    }
    function openAssignModal({ds, period, name='', time=''}) {
      document.getElementById('assignDs').value = ds;
      document.getElementById('assignPeriod').value = period;
      document.getElementById('assignOriginalName').value = name || '';
      document.getElementById('assignModalTitle').textContent = name ? '修改人員' : '新增人員';

      let start = '', end = '';
      if (time && time.includes('-')) { [start, end] = time.split('-'); }
      document.getElementById('assignName').value  = name || '';
      document.getElementById('assignStart').value = start || '';
      document.getElementById('assignEnd').value   = end || '';

      buildNameDatalist();
      assignModal.show();
    }
    assignForm.addEventListener('submit', (e)=>{
      e.preventDefault();
      const ds     = document.getElementById('assignDs').value;
      const period = document.getElementById('assignPeriod').value;
      const originalName = document.getElementById('assignOriginalName').value || null;
      const name   = (document.getElementById('assignName').value || '').trim();
      const start  = document.getElementById('assignStart').value;
      const end    = document.getElementById('assignEnd').value;

      if(!name || !start || !end){ return; }
      const time = `${start}-${end}`;
      upsertDraft(ds, period, name, time, originalName);
      assignModal.hide();
    });

    /* ========= 儲存 ========= */
    async function saveDraft(monday){
      const payload = { week_start: fmt(monday), assignments: {} };
      daysOfWeek(monday).forEach(d=>{
        const ds = fmt(d);
        payload.assignments[ds] = {
          '上午': (draftAssignedMap[ds]?.['上午'] || []).map(x=>({name:x.name, time:x.time})),
          '晚上': (draftAssignedMap[ds]?.['晚上'] || []).map(x=>({name:x.name, time:x.time}))
        };
      });
      // const ok = await fetchJSON('/schedule/week', { method:'POST', body: JSON.stringify(payload) });
      const ok = true; // demo
      if(ok){ await loadSchedulePreview(currentMonday); alert('已儲存班表！'); }
      else { alert('儲存失敗，請稍後再試'); }
    }

    /* ========= 刷新流程 ========= */
    let currentMonday = getMonday(new Date());

    async function refreshAll(){
      renderWeekHeader(currentMonday);
      await loadSchedulePreview(currentMonday);

      // 用已發布初始化草稿
      draftAssignedMap = JSON.parse(JSON.stringify(scheduleAssignedMap));

      await loadAvailability(currentMonday);

      // 甘特圖：建立星期按鈕 + 預設渲染
      renderDayButtons(currentMonday);

      renderEditorHeader(currentMonday);
      renderEditorGrid(currentMonday);
    }

    document.getElementById('btnQuery').addEventListener('click', async ()=>{
      currentMonday = getMonday(selectedDate());
      await refreshAll();
    });
    document.getElementById('btnSaveDraft').addEventListener('click', ()=> saveDraft(currentMonday));
    document.getElementById('btnClearDraft').addEventListener('click', ()=>{
      if(!confirm('確定要清空本週的草稿嗎？')) return;
      draftAssignedMap = {};
      renderEditorGrid(currentMonday);
    });

    window.addEventListener('DOMContentLoaded', async ()=>{
      initDateSelectors();
      await refreshAll();
    });
  </script>
</body>
</html>
