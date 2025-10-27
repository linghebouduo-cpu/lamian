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
if ($email === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'請輸入 email'], JSON_UNESCAPED_UNICODE); exit; }

// ★ 先到 lamian.員工基本資料 確認 email 是否存在（不洩漏存在與否）
$chk = pdo_auth()->prepare("SELECT 1 FROM `".EMP_DB."`.`員工基本資料` WHERE `email`=? LIMIT 1");
$chk->execute([$email]);
$known = (bool)$chk->fetchColumn();

$code  = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$token = bin2hex(random_bytes(32));

if ($known) {
  // 寫入 password 資料庫的 password 表（你的要求）
  $ins = pdo_auth()->prepare(
    "INSERT INTO `password` (`email`,`code`,`token`,`expires_at`)
     VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
  );
  $ins->execute([$email, $code, $token, RESET_CODE_TTL_MIN]);

  // 可換 SMTP：這裡先用 mail()
  @mail(
    $email,
    '重設密碼驗證碼',
    "您的驗證碼：{$code}（".RESET_CODE_TTL_MIN." 分鐘內有效）",
    "Content-Type: text/plain; charset=UTF-8\r\nFrom: ".MAIL_FROM_NAME." <".MAIL_FROM_EMAIL.">\r\n"
  );
}

echo json_encode(['ok'=>true,'message'=>'驗證碼已寄出（若信箱存在）'], JSON_UNESCAPED_UNICODE);
