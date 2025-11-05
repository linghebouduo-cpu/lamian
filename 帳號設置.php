<?php
// /lamian-ukn/帳號設置.php
// 🔥 啟用登入保護
session_start();

// 檢查是否已登入
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// 取得用戶資訊
$userName = $_SESSION['name'] ?? '用戶';
$userId = $_SESSION['uid'] ?? '';
$userLevel = $_SESSION['user_level'] ?? $_SESSION['role_code'] ?? 'C';

$API_BASE_URL = '/lamian-ukn/api';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>賬號設置 - 員工管理系統</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
  <link href="css/styles.css" rel="stylesheet" />
  <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

  <style>
    :root{ --primary-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff0000cb 100%); --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); --success-gradient: linear-gradient(135deg, #4facfe 0%, #54bcc1 100%); --warning-gradient: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%); --dark-bg: linear-gradient(135deg, #fbb97ce4 0%, #ff00006a 100%); --card-shadow: 0 15px 35px rgba(0,0,0,.1); --hover-shadow: 0 25px 50px rgba(0,0,0,.15); --border-radius: 20px; --transition: all .3s cubic-bezier(.4,0,.2,1); }
    *{ transition: var(--transition); }
    body{ background:#fff; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height:100vh; }
    .sb-topnav{ background: var(--dark-bg) !important; border:none; box-shadow:var(--card-shadow); backdrop-filter: blur(10px); }
    .navbar-brand{ font-weight:700; font-size:1.5rem; background: linear-gradient(45deg,#ffffff,#ffffff); -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-fill-color:transparent; }
    .sb-sidenav{ background: linear-gradient(180deg,#fbb97ce4 0%, #ff00006a 100%) !important; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .sb-sidenav-menu-heading{ color: rgba(255,255,255,.7) !important; font-weight:600; font-size:.85rem; text-transform:uppercase; letter-spacing:1px; padding:20px 15px 10px 15px !important; margin-top:15px; }
    .sb-sidenav .nav-link{ border-radius:15px; margin:5px 15px; padding:12px 15px; position:relative; overflow:hidden; color:rgba(255,255,255,.9)!important; font-weight:500; backdrop-filter: blur(10px); }
    .sb-sidenav .nav-link:hover{ background:rgba(255,255,255,.15)!important; transform:translateX(8px); box-shadow:0 8px 25px rgba(0,0,0,.2); color:#fff!important; }
    .sb-sidenav .nav-link.active{ background:rgba(255,255,255,.2)!important; color:#fff!important; font-weight:600; box-shadow:0 8px 25px rgba(0,0,0,.15); }
    .sb-sidenav .nav-link::before{ content:''; position:absolute; left:0; top:0; height:100%; width:4px; background: linear-gradient(45deg,#ffffff,#ffffff); transform:scaleY(0); border-radius:0 10px 10px 0; }
    .sb-sidenav .nav-link:hover::before, .sb-sidenav .nav-link.active::before{ transform: scaleY(1); }
    .sb-sidenav-footer{ background: rgba(255,255,255,.1) !important; color:#fff !important; border-top:1px solid rgba(255,255,255,.2); padding:20px 15px; margin-top:20px; }

    .container-fluid{ padding:30px !important; }
    h1{ background: var(--primary-gradient); -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-fill-color:transparent; font-weight:700; font-size:2.2rem; margin-bottom:30px; }
    .breadcrumb{ background: rgba(255,255,255,.8); border-radius: var(--border-radius); padding: 12px 16px; box-shadow: var(--card-shadow); backdrop-filter: blur(10px); }
    .card{ border:none; border-radius: var(--border-radius); box-shadow: var(--card-shadow); background:#fff; overflow:hidden; }
    .card-header{ background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.7)); font-weight:600; }
    .form-control, .form-select{ border-radius:12px; }
    .avatar-wrap{ width:140px; height:140px; border-radius:50%; overflow:hidden; position:relative; box-shadow:0 12px 30px rgba(0,0,0,.1); }
    .avatar-wrap img{ width:100%; height:100%; object-fit:cover; }
    .avatar-wrap:hover .avatar-mask{ opacity:1; }
    .avatar-mask{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; background:rgba(0,0,0,.25); color:#fff; opacity:0; cursor:pointer; transition:opacity .2s; }
    .btn-primary{ background: var(--primary-gradient); border:none; border-radius:25px; }
    .btn-primary:hover{ transform:scale(1.03); box-shadow:0 10px 25px rgba(209,209,209,.9); }
  </style>
</head>

<body class="sb-nav-fixed">
  <!-- Navbar -->
  <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">員工管理系統</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button"><i class="fas fa-bars"></i></button>

    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img class="user-avatar rounded-circle me-2" src="https://i.pravatar.cc/40?u=<?php echo urlencode($userName); ?>" width="28" height="28" alt="">
          <span id="navUserName"><?php echo htmlspecialchars($userName); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
          <li><a class="dropdown-item" href="賬號設置.php">賬號設置</a></li>
          <li><hr class="dropdown-divider" /></li>
          <li><a class="dropdown-item" href="logout.php">Logout</a></li>
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
            <a class="nav-link" href="index.php"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>首頁</a>

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
            <a class="nav-link" href="charts.php"><div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>Charts</a>
          </div>
        </div>

        <div class="sb-sidenav-footer">
          <div class="small">Logged in as:</div>
          <span id="loggedAs"><?php echo htmlspecialchars($userName); ?></span>
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div id="layoutSidenav_content">
      <main>
        <div class="container-fluid">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>賬號設置</h1>
            <div class="text-muted"><i class="fas fa-calendar-alt me-2"></i><span id="currentDate"></span></div>
          </div>

          <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="index.php">首頁</a></li>
            <li class="breadcrumb-item active">賬號設置</li>
          </ol>

          <div id="msgOk" class="alert alert-success d-none"></div>
          <div id="msgErr" class="alert alert-danger d-none"></div>

          <!-- 個人資料 -->
          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user me-2"></i>個人資料</div>
            <div class="card-body">
              <div class="row g-4 align-items-center">
                <div class="col-auto">
                  <div class="avatar-wrap">
                    <img id="avatarImg" src="https://i.pravatar.cc/240?img=12" alt="avatar">
                    <div id="avatarMask" class="avatar-mask">
                      <i class="fas fa-camera mb-1"></i>
                      <small>更換頭像</small>
                    </div>
                  </div>
                  <div class="text-muted small mt-2">建議 512×512，JPG/PNG，&lt; 3MB</div>
                  <input id="avatarFile" type="file" accept="image/png,image/jpeg" class="d-none">
                </div>

                <div class="col">
                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">員工編號</label>
                      <input id="empNo" class="form-control" type="text" readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">員工姓名</label>
                      <input id="empName" class="form-control" type="text" readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">手機</label>
                      <input id="empPhone" class="form-control" type="text" readonly>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">職稱</label>
                      <input id="empTitle" class="form-control" type="text" readonly>
                    </div>
                  </div>
                  <div class="text-muted small mt-2">如需修改以上資訊，請聯繫經理或老闆。</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 可編輯項目 -->
          <div class="card mb-4">
            <div class="card-header"><i class="fas fa-pen-to-square me-2"></i>可編輯資訊</div>
            <div class="card-body">
              <form id="profileForm" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input id="email" class="form-control" type="email" placeholder="name@example.com">
                </div>
                <div class="col-md-6">
                  <label class="form-label">通訊地址</label>
                  <input id="addr" class="form-control" type="text" placeholder="例：台北市…">
                </div>
                <div class="col-md-6">
  <label class="form-label">緊急聯絡人</label>
  <input id="emgName" class="form-control" type="text">
</div>
<div class="col-md-6">
  <label class="form-label">緊急聯絡電話</label>
  <input id="emgPhone" class="form-control" type="text">
</div>

                <div class="col-12">
                  <label class="form-label">備註</label>
                  <textarea id="memo" class="form-control" rows="3" placeholder="選填"></textarea>
                </div>

                <div class="col-12"><hr></div>

                <div class="col-md-4">
                  <label class="form-label">新密碼（可留白）</label>
                  <input id="newPwd" class="form-control" type="password" autocomplete="new-password">
                </div>
                <div class="col-md-4">
                  <label class="form-label">確認新密碼</label>
                  <input id="newPwd2" class="form-control" type="password" autocomplete="new-password">
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                  <button class="btn btn-outline-secondary" type="button" id="btnReset"><i class="fas fa-rotate-left me-1"></i>還原</button>
                  <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>儲存</button>
                </div>
              </form>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
    document.getElementById('currentDate').textContent =
      new Date().toLocaleDateString('zh-TW',{year:'numeric',month:'long',day:'numeric',weekday:'long'});
    document.getElementById('sidebarToggle').addEventListener('click', e=>{
      e.preventDefault(); document.body.classList.toggle('sb-sidenav-toggled');
    });

    const API_BASE   = <?php echo json_encode($API_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
    const API_ME     = API_BASE + '/me.php';
    const API_UPDATE = API_BASE + '/me_update.php';
    const API_AVATAR = API_BASE + '/me_avatar.php';

    const el = id => document.getElementById(id);
    function showOk(msg){ const a=el('msgOk'); a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 2000); }
    function showErr(msg){ const a=el('msgErr'); a.textContent=msg; a.classList.remove('d-none'); setTimeout(()=>a.classList.add('d-none'), 3500); }

    async function loadMe(){
      try{
        // 🔥 優先使用 PHP 傳遞的用戶資訊
        const userName = <?php echo json_encode($userName, JSON_UNESCAPED_UNICODE); ?>;
        const userId = <?php echo json_encode($userId, JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('✅ 已登入用戶:', userName, 'ID:', userId);
        
        // 設定用戶名顯示
        el('navUserName').textContent = userName;
        const logged = document.getElementById('loggedAs');
        if (logged) logged.textContent = userName;
        
        // 然後才從 API 載入詳細資料
        const r = await fetch(API_ME, {credentials:'include'});
        if(!r.ok) {
          // 如果 API 失敗，至少設定基本資料
          el('empNo').value = userId;
          el('empName').value = userName;
          throw new Error('HTTP '+r.status);
        }
        const d = await r.json();

        // 更新頭像
        if (d.avatar_url) {
          const url = d.avatar_url + (d.avatar_url.includes('?')?'&':'?') + 'v=' + Date.now();
          el('avatarImg').src = url;
          const navA = document.querySelector('.navbar .user-avatar'); 
          if(navA) navA.src = url;
        }

        // 填入表單資料
        el('empNo').value    = d.id ?? userId;
        el('empName').value  = d.name ?? userName;
        el('empPhone').value = d.Telephone ?? d.phone ?? '';
        el('empTitle').value = d.Position ?? d.title ?? '';

        el('email').value    = d.email ?? '';
        el('addr').value     = d.address ?? '';
        el('emgName').value  = d.emergency_contact ?? '';
        el('emgPhone').value = d.emergency_phone ?? '';
        el('memo').value     = d.memo ?? '';
        
      }catch(e){
        console.error('載入詳細資料失敗:', e);
        // 即使 API 失敗，也不顯示錯誤，因為基本資料已經設定了
        // showErr('載入賬號資訊失敗');
      }
    }

    el('avatarMask').addEventListener('click', ()=> el('avatarFile').click());
    el('avatarFile').addEventListener('change', async (e)=>{
      const f = e.target.files?.[0]; if(!f) return;
      if(!['image/jpeg','image/png'].includes(f.type)) return showErr('只接受 JPG / PNG');
      if(f.size > 3*1024*1024) return showErr('檔案太大（上限 3MB）');
      try{
        const fd = new FormData(); fd.append('avatar', f);
        const r = await fetch(API_AVATAR, {method:'POST', body:fd, credentials:'include'});
        const resp = await r.json();
        if(!r.ok || resp.error) throw new Error(resp.error || ('HTTP '+r.status));
        await loadMe(); showOk('已更新頭像');
      }catch(err){ showErr('上傳失敗：'+err.message); }
      finally{ e.target.value=''; }
    });

    el('profileForm').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const body = {
        email: el('email').value.trim(),
        address: el('addr').value.trim(),
        emergency_contact: el('emgName').value.trim(),
        emergency_phone: el('emgPhone').value.trim(),
        memo: el('memo').value.trim()
      };
      const p1 = el('newPwd').value.trim(), p2 = el('newPwd2').value.trim();
      if(p1 || p2){
        if(p1.length < 6) return showErr('新密碼至少 6 碼');
        if(p1 !== p2)    return showErr('兩次新密碼不一致');
        body.new_password = p1;
      }
      try{
        const r = await fetch(API_UPDATE, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(body),
          credentials:'include'
        });
        const resp = await r.json();
        if(!r.ok || resp.error) throw new Error(resp.error || ('HTTP '+r.status));
        el('newPwd').value = el('newPwd2').value = '';
        showOk('已儲存'); await loadMe();
      }catch(err){ showErr('儲存失敗：'+err.message); }
    });

    el('btnReset').addEventListener('click', loadMe);
    window.addEventListener('DOMContentLoaded', loadMe);
  </script>
  <script src="js/scripts.js"></script>
</body>
</html>