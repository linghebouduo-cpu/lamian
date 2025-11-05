<?php
// 可選：登入保護
// require_once __DIR__ . '/../api/auth_guard.php';

/**
 * ==================================
 * 可調整參數
 * ==================================
 */
$PER_PAGE = 20;
$HAS_DB   = false;       // ← 要接資料庫改 true，並設定 PDO
$HAS_USERS_TABLE = false; // users(id,name) 可改 true 顯示人名

// 你的功能清單（下拉選單用）。key = 實際寫入資料庫的值，value = 顯示文字
$FEATURES = [
  ''          => '全部功能',
  'daily'     => '日報表',
  'attendance'=> '打卡管理',
  'payroll'   => '薪資管理',
  'profile'   => '員工資料',
  'inventory' => '庫存管理',
];

/**
 * ==================================
 * 讀取查詢參數（帶預設）
 * ==================================
 */
$from    = $_GET['from'] ?? date('Y-m-01');
$to      = $_GET['to']   ?? date('Y-m-d');
$feature = trim($_GET['feature'] ?? ''); // ← 改用 feature
$user    = trim($_GET['user']    ?? '');
$q       = trim($_GET['q']       ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));

/**
 * ==================================
 * 取得資料來源（DB 或 假資料）
 * 統一欄位：
 *  - feature (varchar)  ← 功能辨識
 *  - table_name 可留空或不使用
 * ==================================
 */
$rows = [];
$total = 0;

if ($HAS_DB) {
  try {
    // 設定你的 PDO
    $dsn = 'mysql:host=127.0.0.1;dbname=lamian;charset=utf8mb4';
    $pdo = new PDO($dsn, 'root', '', [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 動態 WHERE
    $where = [];
    $bind  = [];

    if ($from !== '') { $where[] = 'el.created_at >= :from'; $bind[':from'] = $from . ' 00:00:00'; }
    if ($to   !== '') { $where[] = 'el.created_at <= :to';   $bind[':to']   = $to   . ' 23:59:59'; }
    if ($feature !== '') { $where[] = 'el.feature = :feature'; $bind[':feature'] = $feature; }
    if ($user  !== '') { $where[] = 'el.user_id = :user_id';   $bind[':user_id'] = $user; }
    if ($q     !== '') { $where[] = 'el.summary LIKE :q';      $bind[':q'] = "%$q%"; }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // 計數
    $sqlCount = "SELECT COUNT(*) FROM edit_logs el $whereSql";
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute($bind);
    $total = (int)$stmt->fetchColumn();

    // 取資料
    $offset = ($page - 1) * $PER_PAGE;
    if ($HAS_USERS_TABLE) {
      $sql = "SELECT el.id, el.user_id, u.name AS user_name, el.feature, el.table_name, el.record_id, el.action,
                     el.summary, el.old_data, el.new_data, el.ip, el.created_at
              FROM edit_logs el
              LEFT JOIN users u ON u.id = el.user_id
              $whereSql
              ORDER BY el.created_at DESC
              LIMIT :limit OFFSET :offset";
    } else {
      $sql = "SELECT el.id, el.user_id, NULL AS user_name, el.feature, el.table_name, el.record_id, el.action,
                     el.summary, el.old_data, el.new_data, el.ip, el.created_at
              FROM edit_logs el
              $whereSql
              ORDER BY el.created_at DESC
              LIMIT :limit OFFSET :offset";
    }
    $stmt = $pdo->prepare($sql);
    foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit',  $PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    // DB 有問題就退回假資料路徑
    $HAS_DB = false;
  }
}

if (!$HAS_DB) {
  // ======= 假資料（已改成 feature 欄位） =======
  $fake = [
    [
      'id'=>1,'user_id'=>110534105,'user_name'=>'王小明',
      'feature'=>'profile','table_name'=>'員工基本資料','record_id'=>7,'action'=>'UPDATE',
      'summary'=>'base_salary：35000 → 38000',
      'old_data'=>json_encode(['base_salary'=>35000,'role'=>'店員','email'=>'demo@example.com'], JSON_UNESCAPED_UNICODE),
      'new_data'=>json_encode(['base_salary'=>38000,'role'=>'儲備幹部','email'=>'demo_new@example.com'], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-27 17:30:45'
    ],
    [
      'id'=>2,'user_id'=>1,'user_name'=>'管理者',
      'feature'=>'attendance','table_name'=>'attendance','record_id'=>1012,'action'=>'INSERT',
      'summary'=>'新增打卡紀錄（王小明 09:00）',
      'old_data'=>null,
      'new_data'=>json_encode(['employee'=>'王小明','check_in'=>'09:00'], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-25 09:10:03'
    ],
    [
      'id'=>3,'user_id'=>110534101,'user_name'=>'林宜伶',
      'feature'=>'profile','table_name'=>'員工基本資料','record_id'=>12,'action'=>'DELETE',
      'summary'=>'刪除離職員工紀錄',
      'old_data'=>json_encode(['name'=>'李小華','status'=>'離職'], JSON_UNESCAPED_UNICODE),
      'new_data'=>null,
      'ip'=>'127.0.0.1','created_at'=>'2025-10-22 14:52:11'
    ],
    [
      'id'=>4,'user_id'=>1,'user_name'=>'管理者',
      'feature'=>'payroll','table_name'=>'薪資表','record_id'=>202510,'action'=>'UPDATE',
      'summary'=>'bonus：2000 → 2500',
      'old_data'=>json_encode(['bonus'=>2000], JSON_UNESCAPED_UNICODE),
      'new_data'=>json_encode(['bonus'=>2500], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-20 11:03:29'
    ],
    [
      'id'=>5,'user_id'=>110534105,'user_name'=>'王小明',
      'feature'=>'daily','table_name'=>'daily_reports','record_id'=>889,'action'=>'INSERT',
      'summary'=>'新增日報表（2025-10-27 白班）',
      'old_data'=>null,
      'new_data'=>json_encode(['date'=>'2025-10-27','shift'=>'day','sales'=>15230], JSON_UNESCAPED_UNICODE),
      'ip'=>'127.0.0.1','created_at'=>'2025-10-27 18:02:11'
    ],
  ];

  // 在假資料上做篩選
  $rows = array_values(array_filter($fake, function($r) use($from,$to,$feature,$user,$q){
    $ok = true;
    if ($from)    $ok = $ok && (substr($r['created_at'],0,10) >= $from);
    if ($to)      $ok = $ok && (substr($r['created_at'],0,10) <= $to);
    if ($feature!=='') $ok = $ok && ($r['feature']===$feature);
    if ($user!=='')    $ok = $ok && ((string)$r['user_id']===(string)$user || ($r['user_name']??'')===$user);
    if ($q!=='')       $ok = $ok && (mb_stripos($r['summary']??'', $q)!==false);
    return $ok;
  }));

  // 假分頁
  $total = count($rows);
  $rows  = array_slice($rows, ($page-1)*$PER_PAGE, $PER_PAGE);
}

/**
 * ==================================
 * 小工具
 * ==================================
 */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function action_badge_class($action){
  switch (strtoupper($action)){
    case 'INSERT': return 'badge-insert';
    case 'DELETE': return 'badge-delete';
    default: return 'badge-update';
  }
}
function user_label($row){
  if (!empty($row['user_name'])) return $row['user_name'];
  return 'User #' . ($row['user_id'] ?? '?');
}
$lastPage = max(1, (int)ceil($total / $PER_PAGE));
$queryBase = function($override=[]) use($from,$to,$feature,$user,$q){
  $p = array_merge(['from'=>$from,'to'=>$to,'feature'=>$feature,'user'=>$user,'q'=>$q], $override);
  return '?' . http_build_query($p);
};
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>修改紀錄</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f8f9fa;}
    .table td,.table th{vertical-align:middle;}
    .action-badge{font-size:.85rem;padding:4px 8px;border-radius:6px;}
    .badge-insert{background:#198754;color:#fff;}
    .badge-update{background:#0d6efd;color:#fff;}
    .badge-delete{background:#dc3545;color:#fff;}
    .log-summary{color:#495057;font-size:.95rem;}
    .shadow-card{box-shadow:0 2px 10px rgba(0,0,0,.06);}
    pre{white-space:pre-wrap; word-break:break-word;}
    .feature-pill{font-size:.75rem;border-radius:999px;background:#eef2ff;color:#3730a3;padding:.25rem .5rem;}
  </style>
</head>
<body>
<div class="container py-4">
  <h4 class="fw-bold mb-3">📝 修改紀錄</h4>

  <!-- 篩選區：改成「功能」 -->
  <div class="card mb-4 border-0 shadow-card">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="get">
        <div class="col-md-3">
          <label class="form-label">日期區間（起）</label>
          <input type="date" class="form-control" name="from" value="<?=h($from)?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">日期區間（迄）</label>
          <input type="date" class="form-control" name="to" value="<?=h($to)?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">功能</label>
          <select class="form-select" name="feature">
            <?php foreach($FEATURES as $val=>$label): ?>
              <option value="<?=h($val)?>" <?=$feature===$val?'selected':''?>><?=h($label)?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">使用者</label>
          <input type="text" class="form-control" name="user" placeholder="user_id 或 名稱" value="<?=h($user)?>">
        </div>
        <div class="col-12 d-flex justify-content-end" style="gap:.5rem;">
          <input type="search" class="form-control" style="max-width:240px" name="q" placeholder="關鍵字（摘要）" value="<?=h($q)?>">
          <button class="btn btn-dark">查詢</button>
          <a class="btn btn-outline-secondary" href="<?=h($queryBase(['from'=>date('Y-m-01'),'to'=>date('Y-m-d'),'feature'=>'','user'=>'','q'=>'','page'=>1]))?>">重置</a>
        </div>
      </form>
    </div>
  </div>

  <!-- 紀錄表 -->
  <div class="card border-0 shadow-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:180px;">時間</th>
            <th style="width:160px;">使用者</th>
            <th style="width:220px;">功能 / 對象</th>
            <th style="width:110px;">動作</th>
            <th>摘要</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="text-center text-muted py-5">沒有符合條件的記錄</td></tr>
        <?php else: ?>
          <?php foreach($rows as $r): ?>
            <?php
              $oldJson = $r['old_data'] ?? null;
              $newJson = $r['new_data'] ?? null;
              $featKey = $r['feature'] ?? '';
              $featLabel = $FEATURES[$featKey] ?? $featKey ?: '（未標示）';
              $targetText = trim(($r['table_name'] ?? '') . ' #' . ($r['record_id'] ?? ''));
            ?>
            <tr class="log-row" role="button"
                data-bs-toggle="modal" data-bs-target="#logDetail"
                data-id="<?=h($r['id'])?>"
                data-user="<?=h(user_label($r))?>"
                data-feature="<?=h($featLabel)?>"
                data-record="<?=h($targetText)?>"
                data-action="<?=h($r['action'])?>"
                data-time="<?=h($r['created_at'])?>"
                data-ip="<?=h($r['ip'])?>"
                data-old='<?=h((string)$oldJson)?>'
                data-new='<?=h((string)$newJson)?>'>
              <td><?=h($r['created_at'])?></td>
              <td><?=h(user_label($r))?></td>
              <td>
                <span class="feature-pill"><?=h($featLabel)?></span>
                <div class="text-muted small"><?=h($targetText)?></div>
              </td>
              <td><span class="action-badge <?=action_badge_class($r['action'])?>"><?=h(strtoupper($r['action']))?></span></td>
              <td class="log-summary"><?=h($r['summary'] ?? '')?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 分頁 -->
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">共 <?=h($total)?> 筆，頁 <?=h($page)?> / <?=h($lastPage)?>（每頁 <?=$PER_PAGE?> 筆）</div>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?=($page<=1?'disabled':'')?>">
          <a class="page-link" href="<?=h($queryBase(['page'=>$page-1]))?>">‹</a>
        </li>
        <?php
          $start = max(1, $page-3);
          $end   = min($lastPage, $start+6);
          for($i=$start; $i<=$end; $i++):
        ?>
        <li class="page-item <?=($i==$page?'active':'')?>"><a class="page-link" href="<?=h($queryBase(['page'=>$i]))?>"><?=$i?></a></li>
        <?php endfor; ?>
        <li class="page-item <?=($page>=$lastPage?'disabled':'')?>">
          <a class="page-link" href="<?=h($queryBase(['page'=>$page+1]))?>">›</a>
        </li>
      </ul>
    </nav>
  </div>
</div>

<!-- 詳細紀錄 Modal -->
<div class="modal fade" id="logDetail" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">修改詳細內容</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="detailMeta" class="mb-2"></div>
        <hr>
        <div class="mb-2 fw-semibold">變動欄位</div>
        <pre id="diffBox" class="bg-light p-3 rounded small"></pre>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JSON 差異（只列不同鍵）
function buildDiff(oldObj, newObj){
  const lines = [];
  const keys = new Set([...(oldObj?Object.keys(oldObj):[]), ...(newObj?Object.keys(newObj):[])]);
  [...keys].sort().forEach(k=>{
    const ov = oldObj ? oldObj[k] : undefined;
    const nv = newObj ? newObj[k] : undefined;
    if (JSON.stringify(ov) === JSON.stringify(nv)) return;
    const maskKeys = ['password','password_hash','id_card','ID_card','national_id','email_token'];
    const isMasked = maskKeys.includes(String(k).toLowerCase());
    const fmt = (v)=> v===undefined ? '∅' : (v===null ? 'null' : (typeof v==='object' ? JSON.stringify(v) : String(v)));
    const left = isMasked ? '******' : fmt(ov);
    const right= isMasked ? '******' : fmt(nv);
    lines.push(`${k}：${left} → ${right}`);
  });
  return lines.length ? lines.join('\n') : '（此筆未偵測到欄位差異或為新增/刪除）';
}

const detailModal = document.getElementById('logDetail');
detailModal.addEventListener('show.bs.modal', evt=>{
  const tr = evt.relatedTarget;
  const meta = `
    <div><strong>時間：</strong>${tr.dataset.time}</div>
    <div><strong>使用者：</strong>${tr.dataset.user}</div>
    <div><strong>功能：</strong>${tr.dataset.feature}</div>
    <div><strong>對象：</strong>${tr.dataset.record || '-'}</div>
    <div><strong>動作：</strong>${tr.dataset.action}</div>
    <div class="text-muted"><strong>IP：</strong>${tr.dataset.ip||'-'}</div>
  `;
  document.getElementById('detailMeta').innerHTML = meta;

  let oldData = null, newData = null;
  try { oldData = tr.dataset.old ? JSON.parse(tr.dataset.old) : null; } catch(e){}
  try { newData = tr.dataset.new ? JSON.parse(tr.dataset.new) : null; } catch(e){}
  document.getElementById('diffBox').textContent = buildDiff(oldData, newData);
});
</script>
</body>
</html>
