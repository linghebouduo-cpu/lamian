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

$in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$email = trim($in['email'] ?? '');
$token = trim($in['reset_token'] ?? '');
$new   = (string)($in['new_password'] ?? '');
if ($email==='' || $token==='' || strlen($new)<6) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'參數錯誤（密碼至少 6 碼）'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 驗證 token
$stmt = pdo_auth()->prepare(
  "SELECT `id`,`expires_at`,`used`
   FROM `password`
   WHERE `email`=? AND `token`=?
   ORDER BY `id` DESC
   LIMIT 1"
);
$stmt->execute([$email, $token]);
$row = $stmt->fetch();

if (!$row) { echo json_encode(['ok'=>false,'error'=>'重設連結無效']); exit; }
if ((int)$row['used'] === 1) { echo json_encode(['ok'=>false,'error'=>'此重設已使用過']); exit; }
if ((new DateTime($row['expires_at'])) < (new DateTime())) { echo json_encode(['ok'=>false,'error'=>'重設已過期']); exit; }

// 寫回 lamian.員工基本資料（以雜湊存新密碼）
$hash = password_hash($new, PASSWORD_DEFAULT);
pdo_auth()->prepare(
  "UPDATE `".EMP_DB."`.`員工基本資料` 
   SET `password_hash`=?, `updated_at`=NOW()
   WHERE `email`=?"
)->execute([$hash, $email]);

// 標記使用並作廢其他
pdo_auth()->prepare("UPDATE `password` SET `used`=1 WHERE `id`=?")->execute([(int)$row['id']]);
pdo_auth()->prepare("UPDATE `password` SET `used`=1 WHERE `email`=? AND `id`<>?")
          ->execute([$email, (int)$row['id']]);

echo json_encode(['ok'=>true,'message'=>'密碼已重設'], JSON_UNESCAPED_UNICODE);
