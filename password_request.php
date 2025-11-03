<?php
declare(strict_types=1);
require_once __DIR__ . '/db_auth.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// 啟用錯誤顯示（除錯用）
error_reporting(E_ALL);
ini_set('display_errors', '1');

set_exception_handler(function(Throwable $e){
  error_log("Password Request Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
  err('伺服器處理時發生錯誤', 500);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  err('請求方法錯誤', 405);
}

$in    = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$email = trim($in['email'] ?? '');

error_log("========== Password Request Start ==========");
error_log("Received email: {$email}");

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  error_log("❌ Invalid email format");
  err('請輸入有效的 Email 地址');
}

$employee_name = '使用者';
$employee_id = null;
$known = false;

// 1. 檢查 Email 是否存在於員工資料表
try {
    $pdo_lamian = pdo();
    error_log("✅ PDO connection successful");
    
    $sql = "SELECT `id`, `name` FROM `員工基本資料` WHERE `email` = ? LIMIT 1";
    error_log("SQL: {$sql}");
    error_log("Email parameter: {$email}");
    
    $chk = $pdo_lamian->prepare($sql);
    $chk->execute([$email]);
    $employee = $chk->fetch();
    
    if ($employee) {
        $known = true;
        $employee_name = $employee['name'] ?? '使用者';
        $employee_id = $employee['id'] ?? null;
        error_log("✅ Employee found - ID: {$employee_id}, Name: {$employee_name}");
    } else {
        error_log("❌ Employee NOT found for email: {$email}");
    }
    
} catch (PDOException $e) {
    error_log("❌ Database error: " . $e->getMessage());
    $known = false;
}

$code  = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$token = bin2hex(random_bytes(32));
$mail_sent_successfully = false;

error_log("Known: " . ($known ? 'YES' : 'NO'));
error_log("Employee ID: " . ($employee_id ?? 'NULL'));

if ($known && $employee_id !== null) {
  error_log("📝 Proceeding with password reset...");
  
  try {
      $pdo_auth = pdo_auth();
      error_log("✅ Auth DB connection successful");

      // 作廢舊請求
      $stmt_invalidate = $pdo_auth->prepare("UPDATE `password` SET `used` = 1 WHERE `email` = ? AND `used` = 0");
      $stmt_invalidate->execute([$email]);
      $invalidated = $stmt_invalidate->rowCount();
      error_log("✅ Invalidated {$invalidated} old requests");

      // 插入新請求
      $ins = $pdo_auth->prepare(
          "INSERT INTO `password` (`email`, `user_id`, `code`, `token`, `expires_at`, `created_at`)
           VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())"
      );
      $ins->execute([$email, $employee_id, $code, $token, RESET_CODE_TTL_MIN]);
      $insert_id = $pdo_auth->lastInsertId();
      error_log("✅ Password reset record inserted - ID: {$insert_id}, Code: {$code}");

      // 準備郵件內容
      $subject = '=?UTF-8?B?' . base64_encode('密碼重設驗證碼 - ' . MAIL_FROM_NAME) . '?=';
      $html_body = "
      <html>
      <body style='font-family: Arial, sans-serif;'>
      <p>您好 <strong>{$employee_name}</strong>,</p>
      <p>您正在請求重設員工管理系統的密碼。</p>
      <p>您的驗證碼是： <strong style='font-size: 1.5em; color: #dc3545; background: #f8f9fa; padding: 10px; display: inline-block;'>{$code}</strong></p>
      <p>此驗證碼將在 <strong>" . RESET_CODE_TTL_MIN . " 分鐘</strong>後失效。</p>
      <p>如果您沒有請求重設密碼，請忽略此郵件。</p>
      <hr style='border: 1px solid #eee; margin: 20px 0;'>
      <p style='color: #666; font-size: 0.9em;'>--<br>" . MAIL_FROM_NAME . "</p>
      </body>
      </html>";
      
      $text_body = "您好 {$employee_name},\n\n您正在請求重設員工管理系統的密碼。\n\n您的驗證碼是：{$code}\n\n此驗證碼將在 " . RESET_CODE_TTL_MIN . " 分鐘後失效。\n\n如果您沒有請求重設密碼，請忽略此郵件。\n\n--\n" . MAIL_FROM_NAME;

      // 發送郵件
      error_log("📧 Sending email to: {$email}");
      error_log("📧 Employee name: {$employee_name}");
      error_log("📧 Subject: " . base64_decode(str_replace('=?UTF-8?B?', '', str_replace('?=', '', $subject))));
      error_log("📧 Code in email: {$code}");
      
      $mail_sent_successfully = send_email($email, $employee_name, $subject, $html_body, $text_body);

      if ($mail_sent_successfully) {
          error_log("✅✅✅ EMAIL SENT SUCCESSFULLY to {$email}");
      } else {
          error_log("❌❌❌ EMAIL SENDING FAILED to {$email}");
      }
      
  } catch (PDOException $e) {
      error_log("❌ Database error: " . $e->getMessage());
      error_log("Stack trace: " . $e->getTraceAsString());
  } catch (Throwable $e) {
      error_log("❌ Unexpected error: " . $e->getMessage());
      error_log("Stack trace: " . $e->getTraceAsString());
  }
} else {
  error_log("⚠️ Skipping email send - known: " . ($known ? 'true' : 'false') . ", employee_id: " . ($employee_id ?? 'null'));
}

error_log("========== Password Request End ==========");

ok(['ok'=>true, 'message'=>'如果您的 Email 地址存在於我們的系統中，您將會收到一封包含驗證碼的郵件。']);
?>

