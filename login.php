<?php
// 依您的實際路徑修改（這裡假設 /lamian-ukn/api）
$API_BASE = '/lamian-ukn/api';
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>員工管理系統 - 登入</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    * { box-sizing: border-box; }

    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #fbb97c 0%, #ff5a5a 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      position: relative;
      overflow: hidden;
    }

    body::before { content: ''; position: absolute; width: 500px; height: 500px; background: rgba(255, 255, 255, 0.08); border-radius: 50%; top: -150px; right: -100px; pointer-events: none;}
    body::after { content: ''; position: absolute; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; bottom: -80px; left: -50px; pointer-events: none;}

    .login-container { z-index: 10; position: relative; }
    .card { width: 100%; max-width: 600px; border: none; border-radius: 24px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 1px rgba(0, 0, 0, 0.1); overflow: hidden; backdrop-filter: blur(10px); animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .card-header { background: linear-gradient(135deg, #fbb97c 0%, #ff5a5a 100%); color: #fff; padding: 32px 28px; text-align: center; position: relative; }
    .card-header::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent); }
    .card-header h1 { font-size: 24px; margin: 0; font-weight: 700; display: flex; gap: 12px; align-items: center; justify-content: center; letter-spacing: -0.5px; }
    .card-header i { font-size: 28px; }
    .card-header p { margin: 10px 0 0; opacity: 0.95; font-size: 14px; font-weight: 500; }
    .card-body { padding: 40px 36px; background: #fff; }
    .form-label { font-weight: 700; color: #2d3748; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 4px; }
    .input-group-icon { position: relative; margin-bottom: 16px; }
    .input-group-icon i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 16px; transition: color 0.3s ease; }
    .input-group-icon .form-control { padding: 14px 48px; border-radius: 12px; border: 2px solid #e2e8f0; background: #f7fafc; font-size: 14px; font-weight: 500; transition: all 0.3s ease; color: #2d3748; }
    .input-group-icon .form-control::placeholder { color: #cbd5e0; font-weight: 400; }
    .input-group-icon .form-control:focus { border-color: #ff5a5a; background: #fff; box-shadow: 0 0 0 4px rgba(255, 90, 90, 0.1); outline: none; }
    .input-group-icon:focus-within i { color: #ff5a5a; }
    .forgot-container { display: flex; justify-content: flex-end; margin-bottom: 24px; }
    .forgot { color: #ff5a5a; font-weight: 600; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 4px; transition: all 0.3s ease; }
    .forgot:hover { color: #fbb97c; transform: translateX(2px); }
    .btn-grad { background: linear-gradient(135deg, #fbb97c 0%, #ff5a5a 100%); border: none; color: #fff; border-radius: 12px; padding: 12px 20px; font-weight: 700; letter-spacing: 0.5px; font-size: 15px; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 4px 15px rgba(255, 90, 90, 0.3); cursor: pointer; position: relative; overflow: hidden; }
    .btn-grad::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.2); transition: left 0.3s ease; }
    .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(255, 90, 90, 0.4); }
    .btn-grad:hover::before { left: 100%; }
    .btn-grad:active { transform: translateY(0); }
    .btn-grad:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
    .alert { border: none; border-radius: 12px; font-size: 14px; font-weight: 500; padding: 12px 16px; margin-bottom: 20px; animation: slideDown 0.3s ease; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
    .alert-danger { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
    .spinner-border { width: 1em; height: 1em; }
  </style>
</head>
<body>

<div class="login-container">
  <div class="card">
    <div class="card-header">
      <h1><i class="bi bi-shield-lock"></i>員工管理系統</h1>
      <p>歡迎回來，請登入您的帳號</p>
    </div>
    <div class="card-body">
      <div id="loginMsg" class="alert d-none"></div>

      <div class="mb-3">
        <label class="form-label"><i class="bi bi-person"></i>帳號</label>
        <div class="input-group-icon">
          <i class="bi bi-person"></i>
          <input id="acc" class="form-control" placeholder="輸入員工ID或身分證字號">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label"><i class="bi bi-lock"></i>密碼</label>
        <div class="input-group-icon">
          <i class="bi bi-lock"></i>
          <input id="pwd" type="password" class="form-control" placeholder="輸入密碼">
        </div>
      </div>
      <div class="forgot-container">
        <a class="forgot" href="#" data-bs-toggle="modal" data-bs-target="#fpModal">
          <i class="bi bi-question-circle"></i>忘記密碼？
        </a>
      </div>
      <button id="btnLogin" class="btn btn-grad w-100">
        <i class="bi bi-box-arrow-in-right me-2"></i>立即登入
      </button>
    </div>
  </div>
</div>

<!-- 忘記密碼 Modal (保持原樣) -->
<div class="modal fade" id="fpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">重設密碼</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-center">忘記密碼功能維護中...</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- API Endpoints ---
const API_LOGIN = <?php echo json_encode($API_BASE . '/password_login.php'); ?>;

// --- Helper Functions ---
const $ = s => document.querySelector(s);

function showMsg(el, text, ok=false){
  if (!el) {
      console.error("showMsg Error: Target element is null. Message:", text);
      return;
  }
  el.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
  el.textContent = text;
  el.classList.remove('d-none');
  setTimeout(() => el.classList.add('d-none'), ok ? 2200 : 4200);
}

// --- Login Logic ---
$('#btnLogin').addEventListener('click', async () => {
  const account = $('#acc').value.trim();
  const password = $('#pwd').value;
  
  if(!account || !password){ 
    showMsg($('#loginMsg'),'請輸入帳號與密碼'); 
    return; 
  }

  const btn = $('#btnLogin'); 
  const old = btn.innerHTML;
  btn.disabled = true; 
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>登入中...';

  try{
    const r = await fetch(API_LOGIN, {
      method:'POST', 
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({account, password}), 
      credentials:'include'
    });
    
    const t = await r.text(); 
    let resp; 
    
    try{ 
      resp = JSON.parse(t);
    }catch{ 
      throw new Error('登入 API 非 JSON：'+t.slice(0,80)); 
    }
    
    if(!r.ok || resp.error) {
      throw new Error(resp.error || resp.message || ('HTTP '+r.status));
    }
    
    // 🔥 登入成功！檢查是否有 redirect 欄位
    console.log('✅ 登入成功！', resp);
    
    if (resp.ok && resp.redirect) {
      // 有 redirect 欄位，根據後端指示跳轉
      const userLevel = resp.user?.level || resp.user?.role_code || 'C';
      const levelName = userLevel === 'A' ? '老闆' : userLevel === 'B' ? '管理員' : '員工';
      
      console.log('👤 用戶:', resp.user);
      console.log('📊 等級:', userLevel, '(' + levelName + ')');
      console.log('🚀 跳轉到:', resp.redirect);
      
      showMsg($('#loginMsg'), `✓ ${levelName}登入成功！正在跳轉...`, true);
      
      // 延遲一下再跳轉，讓用戶看到成功訊息
      setTimeout(() => {
        window.location.href = resp.redirect;
      }, 800);
      
    } else {
      // 沒有 redirect 欄位（向下兼容舊版API）
      showMsg($('#loginMsg'),'登入成功，前往首頁...', true);
      setTimeout(() => window.location.href = 'index.php', 700);
    }
    
  }catch(e){
    console.error('❌ 登入錯誤:', e);
    showMsg($('#loginMsg'), String(e.message||e));
  }finally{
    btn.disabled = false; 
    btn.innerHTML = old;
  }
});

// Enter 鍵登入
['#acc','#pwd'].forEach(s => $(s).addEventListener('keypress',e => { 
  if(e.key==='Enter') $('#btnLogin').click(); 
}));
</script>
</body>
</html>