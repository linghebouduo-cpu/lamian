<?php
// /lamian-ukn/api/clock_admin_save.php
require __DIR__.'/config.php';

function hhmm_or_null($t){
  $t = trim((string)$t);
  if($t==='' || !preg_match('/^\d{2}:\d{2}$/',$t)) return null;
  return $t;
}
function ymd_or_fail($d){
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d??'')) err('date format must be YYYY-MM-DD', 400);
  return $d;
}

try{
  $pdo  = pdo();
  $body = json_decode(file_get_contents('php://input'), true) ?? [];

  $id        = isset($body['id']) && $body['id']!=='' ? (int)$body['id'] : null;
  $date      = ymd_or_fail($body['date'] ?? '');
  $emp_code  = trim((string)($body['emp_id'] ?? '')); // 這裡我們直接用員工表主鍵 id 當作 code
  $cin       = hhmm_or_null($body['clock_in'] ?? null);
  $cout      = hhmm_or_null($body['clock_out'] ?? null);
  $statusIn  = trim((string)($body['status'] ?? ''));
  $note      = trim((string)($body['note'] ?? ''));

  if($emp_code==='') err('emp_id required',400);

  // 取得員工（用 EMP_PK_COL / EMP_CODE_COL 任一皆可符合）
  $sqlEmp = "SELECT `".EMP_PK_COL."` AS id, `".EMP_NAME_COL."` AS name
             FROM `".EMP_TABLE."`
             WHERE CAST(`".EMP_PK_COL."` AS CHAR)=:c OR CAST(`".EMP_CODE_COL."` AS CHAR)=:c
             LIMIT 1";
  $st = $pdo->prepare($sqlEmp); $st->execute([':c'=>$emp_code]);
  $emp = $st->fetch();
  if(!$emp){ err('找不到員工：'.$emp_code, 404); }

  // 組合 datetime
  $cin_dt  = $cin  ? "$date $cin:00"  : null;
  $cout_dt = $cout ? "$date $cout:00" : null;

  // 計算工時 & 狀態（若未指定）
  $hours = null; $status = $statusIn !== '' ? $statusIn : null;
  if($cin_dt && $cout_dt){
    $stH = $pdo->query("SELECT TIMESTAMPDIFF(MINUTE, '$cin_dt', '$cout_dt') AS m");
    $m = (int)$stH->fetchColumn();
    if($m < 0) $m += 1440; // 跨日簡單處理
    $hours = round($m/60, 2);
    if($status === null) $status = ($m > 480) ? '加班' : '正常';
  }else{
    if($status === null) $status = '缺卡';
  }

  if($id){ // UPDATE
    $sql = "UPDATE `".ATT_TABLE."`
            SET user_id=:uid,
                clock_in=:cin,
                clock_out=:cout,
                hours=:hrs,
                status=:st,
                note=:note
            WHERE id=:id";
    $pdo->prepare($sql)->execute([
      ':uid'=>$emp['id'],
      ':cin'=>$cin_dt,
      ':cout'=>$cout_dt,
      ':hrs'=>$hours,
      ':st'=>$status,
      ':note'=>($note!=='')?$note:null,
      ':id'=>$id
    ]);
  }else{ // INSERT
    $sql = "INSERT INTO `".ATT_TABLE."` (user_id, clock_in, clock_out, hours, status, note)
            VALUES (:uid,:cin,:cout,:hrs,:st,:note)";
    $pdo->prepare($sql)->execute([
      ':uid'=>$emp['id'],
      ':cin'=>$cin_dt,
      ':cout'=>$cout_dt,
      ':hrs'=>$hours,
      ':st'=>$status,
      ':note'=>($note!=='')?$note:null
    ]);
    $id = (int)$pdo->lastInsertId();
  }

  ok(['ok'=>true,'id'=>$id,'emp'=>$emp,'status'=>$status,'hours'=>$hours]);
}catch(Throwable $ex){
  err('admin save failed', 500, ['detail'=>$ex->getMessage()]);
}
