<?php
declare(strict_types=1);
require_once __DIR__ . '/db_auth.php';

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function(Throwable $e){
  http_response_code(200);
  echo json_encode(['ok'=>false,'error'=>'SERVER_ERROR: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

$in    = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$email = trim($in['email'] ?? '');
$code  = trim($in['code'] ?? '');
if ($email==='' || strlen($code)!==6) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'驗證碼格式錯誤'], JSON_UNESCAPED_UNICODE); exit; }

// 取最新一筆驗證碼
$stmt = pdo_auth()->prepare(
  "SELECT `id`,`token`,`expires_at`,`attempts`,`used`
   FROM `password`
   WHERE `email`=?
   ORDER BY `id` DESC
   LIMIT 1"
);
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row)                         { echo json_encode(['ok'=>false,'error'=>'驗證碼錯誤']); exit; }
if ((int)$row['used'] === 1)       { echo json_encode(['ok'=>false,'error'=>'此驗證碼已使用']); exit; }
if ((int)$row['attempts'] >= RESET_MAX_ATTEMPTS) { echo json_encode(['ok'=>false,'error'=>'嘗試過多，請重新申請']); exit; }
if ((new DateTime($row['expires_at'])) < (new DateTime())) { echo json_encode(['ok'=>false,'error'=>'驗證碼已過期']); exit; }

// 僅比對最新那筆
$hit = pdo_auth()->prepare("SELECT 1 FROM `password` WHERE `id`=? AND `email`=? AND `code`=? LIMIT 1");
$hit->execute([(int)$row['id'], $email, $code]);
if (!$hit->fetchColumn()) {
  pdo_auth()->prepare("UPDATE `password` SET `attempts`=`attempts`+1 WHERE `id`=?")->execute([(int)$row['id']]);
  echo json_encode(['ok'=>false,'error'=>'驗證碼錯誤']); exit;
}

echo json_encode(['ok'=>true,'reset_token'=>$row['token']], JSON_UNESCAPED_UNICODE);
