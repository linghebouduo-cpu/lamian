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
    
    /* [!! 新增 !!] Modal 中的 Input 樣式 */
    .modal-body .input-group-icon .form-control { padding-left: 48px; }
    /* [!! 新增 !!] 多步驟 Modal 樣式 */
    .step-pane { display: none; }
    .step-pane.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
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

<div class="modal fade" id="fpModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fpModalTitle">重設密碼 (步驟 1/3)</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="fpMsg" class="alert d-none"></div>

        <div id="step1" class="step-pane active">
          <p class="text-muted small mb-3">請輸入您的員工帳號（ID或身分證字號）。系統將會發送一封密碼重設郵件到您登記的電子信箱。</p>
          <div class="mb-3">
            <label class="form-label"><i class="bi bi-person"></i> 您的帳號</label>
            <div class="input-group-icon">
              <i class="bi bi-person"></i>
              <input id="fpAccount" class="form-control" placeholder="輸入員工ID或身分證字號">
            </div>
          </div>
        </div>

        <div id="step2" class="step-pane">
          <p class="text-muted small mb-3">我們已發送一組 6 位數驗證碼到您的信箱，請檢查並輸入：</p>
          <div class="mb-3">
            <label class="form-label"><i class="bi bi-shield-check"></i> 6 位數驗證碼</label>
            <div class="input-group-icon">
              <i class="bi bi-shield-check"></i>
              <input id="fpCode" class="form-control" placeholder="輸入 6 位數驗證碼" maxlength="6">
            </div>
          </div>
        </div>

        <div id="step3" class="step-pane">
           <p class="text-muted small mb-3">驗證成功！請設定您的新密碼（至少 6 碼）。</p>
           <div class="mb-3">
            <label class="form-label"><i class="bi bi-lock-fill"></i> 新密碼</label>
            <div class="input-group-icon">
              <i class="bi bi-lock-fill"></i>
              <input id="fpNewPass" type="password" class="form-control" placeholder="輸入新密碼 (至少 6 碼)">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><i class="bi bi-lock-fill"></i> 確認新密碼</label>
            <div class="input-group-icon">
              <i class="bi bi-lock-fill"></i>
              <input id="fpConfirmPass" type="password" class="form-control" placeholder="再次輸入新密碼">
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <div id="footerStep1" class="w-100">
          <button type="button" class="btn btn-primary w-100" id="btnSendCode">
            <i class="bi bi-send me-2"></i>發送驗證碼
          </button>
        </div>
        <div id="footerStep2" class="w-100 d-none">
          <button type="button" class="btn btn-secondary" id="btnBackToStep1">返回</button>
          <button type="button" class="btn btn-primary" id="btnVerifyCode">
            <i class="bi bi-check-circle me-2"></i>驗證
          </button>
        </div>
        <div id="footerStep3" class="w-100 d-none">
          <button type="button" class="btn btn-success w-100" id="btnDoReset">
            <i class="bi bi-save me-2"></i>儲存新密碼
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- API Endpoints ---
const API_LOGIN = <?php echo json_encode($API_BASE . '/password_login.php'); ?>;
// [!! 新增 !!] 忘記密碼 API 端點
const API_FORGOT_REQUEST = <?php echo json_encode($API_BASE . '/password_request.php'); ?>;
const API_FORGOT_VERIFY = <?php echo json_encode($API_BASE . '/password_verify.php'); ?>;
const API_FORGOT_RESET = <?php echo json_encode($API_BASE . '/password_reset.php'); ?>;


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
  setTimeout(() => el.classList.add('d-none'), ok ? 3000 : 4200);
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

// [!! 新增 !!] --- 忘記密碼邏輯 ---

// --- 狀態變數 ---
let currentEmail = ''; // 用於步驟 2 和 3
let currentResetToken = ''; // 用於步驟 3
const fpModalEl = $('#fpModal');
const fpModal = new bootstrap.Modal(fpModalEl);

// --- DOM 元素 ---
const fpMsg = $('#fpMsg');
const fpTitle = $('#fpModalTitle');
const stepPanes = {
    1: $('#step1'),
    2: $('#step2'),
    3: $('#step3')
};
const footerPanes = {
    1: $('#footerStep1'),
    2: $('#footerStep2'),
    3: $('#footerStep3')
};

// --- 步驟控制 ---
function showFpStep(step) {
  // 隱藏所有
  Object.values(stepPanes).forEach(p => p.classList.remove('active'));
  Object.values(footerPanes).forEach(p => p.classList.add('d-none'));
  
  // 顯示當前
  if (stepPanes[step]) stepPanes[step].classList.add('active');
  if (footerPanes[step]) footerPanes[step].classList.remove('d-none');
  
  fpTitle.textContent = `重設密碼 (步驟 ${step}/3)`;
  fpMsg.classList.add('d-none'); // 切換步驟時隱藏訊息
}

// --- 事件綁定: Modal 顯示時 ---
fpModalEl.addEventListener('show.bs.modal', () => {
    showFpStep(1);
    currentEmail = '';
    currentResetToken = '';
    $('#fpAccount').value = '';
    $('#fpCode').value = '';
    $('#fpNewPass').value = '';
    $('#fpConfirmPass').value = '';
});

// --- 事件綁定: 步驟 1 (請求驗證碼) ---
$('#btnSendCode').addEventListener('click', async () => {
  const account = $('#fpAccount').value.trim();
  // [!! 修正 !!] 這裡是用 account 登入，但 password_request.php 是用 email
  // [!! 假設 !!] password_request.php 應該也要能接受 account (ID/身分證)
  // [!! 重要 !!] 檢查 password_request.php，它目前是用 'email' 欄位。
  // [!! 暫時 !!] 我先假設使用者會輸入 Email (如果後端只支援 Email)
  // [!! 修正 !!] 您的 password_request.php (後端) *只* 接受 Email。
  // 我們必須要求使用者輸入 Email，而不是帳號。
  
  // [!! 修正 !!] 為了匹配您的後端 (password_request.php)
  // 我們將欄位改成輸入 Email
  
  const email = $('#fpAccount').value.trim(); 
  if (!email || !email.includes('@')) {
    showMsg(fpMsg, '請輸入有效的 Email 地址');
    return;
  }

  const btn = $('#btnSendCode');
  const old = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>發送中...';
  
  try {
    const r = await fetch(API_FORGOT_REQUEST, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ email: email }), // 後端接收 email
      credentials: 'include'
    });
    const resp = await r.json();
    if (!r.ok || resp.error) throw new Error(resp.error || resp.message);

    showMsg(fpMsg, resp.message || '驗證碼已發送', true);
    currentEmail = email; // 儲存 Email
    showFpStep(2);

  } catch (e) {
    console.error('❌ 請求驗證碼錯誤:', e);
    showMsg(fpMsg, String(e.message || e));
  } finally {
    btn.disabled = false;
    btn.innerHTML = old;
  }
});

// --- 事件綁定: 步驟 2 (返回) ---
$('#btnBackToStep1').addEventListener('click', () => {
  showFpStep(1);
});

// --- 事件綁定: 步驟 2 (驗證驗證碼) ---
$('#btnVerifyCode').addEventListener('click', async () => {
  const code = $('#fpCode').value.trim();
  if (!/^\d{6}$/.test(code)) {
    showMsg(fpMsg, '請輸入 6 位數驗證碼');
    return;
  }

  const btn = $('#btnVerifyCode');
  const old = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>驗證中...';

  try {
    const r = await fetch(API_FORGOT_VERIFY, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ email: currentEmail, code: code }),
      credentials: 'include'
    });
    const resp = await r.json();
    if (!r.ok || resp.error) throw new Error(resp.error || resp.message);

    if (!resp.reset_token) {
        throw new Error('API 未返回 Token，驗證失敗');
    }
    
    showMsg(fpMsg, '✓ 驗證成功！請設定新密碼。', true);
    currentResetToken = resp.reset_token; // 儲存 Token
    showFpStep(3);

  } catch (e) {
    console.error('❌ 驗證碼錯誤:', e);
    showMsg(fpMsg, String(e.message || e));
  } finally {
    btn.disabled = false;
    btn.innerHTML = old;
  }
});


// --- 事件綁定: 步驟 3 (重設密碼) ---
$('#btnDoReset').addEventListener('click', async () => {
  const newPass = $('#fpNewPass').value;
  const confirmPass = $('#fpConfirmPass').value;

  if (newPass.length < 6) {
    showMsg(fpMsg, '密碼長度不可少於 6 碼');
    return;
  }
  if (newPass !== confirmPass) {
    showMsg(fpMsg, '兩次輸入的新密碼不相符');
    return;
  }

  const btn = $('#btnDoReset');
  const old = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>儲存中...';

  try {
    const r = await fetch('api/password_reset.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ 
        email: currentEmail, 
        reset_token: currentResetToken,
        new_password: newPass
      }),
      credentials: 'include'
    });
    const resp = await r.json();
    if (!r.ok || resp.error) throw new Error(resp.error || resp.message);
    
    showMsg(fpMsg, '✓ 密碼已成功重設！請重新登入。', true);
    setTimeout(() => {
      fpModal.hide();
    }, 2500);

  } catch (e) {
    console.error('❌ 重設密碼錯誤:', e);
    showMsg(fpMsg, String(e.message || e));
  } finally {
    btn.disabled = false;
    btn.innerHTML = old;
  }
});


// [!! 關鍵修正 !!] 
// 您的後端 (password_request.php) 是 *依據 Email* 查詢員工的
// 但您的登入 (password_login.php) 是 *依據 Account (ID/身分證)*
// 這表示「忘記密碼」功能 *必須* 要求使用者輸入 Email
// 我已經將 Modal 中的提示文字和 JS 邏輯修改為使用 Email
//
// 修正 1: 修改 <label> 和 <placeholder>
const fpAccountLabel = document.querySelector('label[for="fpAccount"]');
if (fpAccountLabel) {
    fpAccountLabel.innerHTML = '<i class="bi bi-envelope"></i> 您的 Email';
}
const fpAccountInput = $('#fpAccount');
if (fpAccountInput) {
    fpAccountInput.placeholder = '輸入您註冊的電子信箱';
}
// 修正 2: 修改 <p> 提示文字
const step1_p = document.querySelector('#step1 p');
if (step1_p) {
    step1_p.textContent = '請輸入您註冊時使用的 Email。系統將會發送一封密碼重設郵件到該信箱。';
}
</script>
</body>
</html>