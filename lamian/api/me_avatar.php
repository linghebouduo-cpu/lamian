<?php
// /lamian-ukn/api/me_avatar.php
header('Content-Type: application/json; charset=utf-8');
session_start();

$empId = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$empId && isset($_GET['id'])) $empId = intval($_GET['id']);
if (!$empId) { http_response_code(401); echo json_encode(['error'=>'未登入']); exit; }

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); echo json_encode(['error'=>'沒有檔案']); exit;
}
$f = $_FILES['avatar'];
$mime = mime_content_type($f['tmp_name']);
if (!in_array($mime, ['image/jpeg','image/png'])) { http_response_code(400); echo json_encode(['error'=>'只接受 JPG/PNG']); exit; }
if ($f['size'] > 3*1024*1024) { http_response_code(400); echo json_encode(['error'=>'上限 3MB']); exit; }

// 檔案儲存
$ext = ($mime==='image/png')?'.png':'.jpg';
$dir = __DIR__ . '/uploads/avatar';
if (!is_dir($dir)) mkdir($dir, 0777, true);
$fname = 'emp_'.$empId.$ext;
$path = $dir . '/' . $fname;
move_uploaded_file($f['tmp_name'], $path);

// 生成對外 URL（依你站台路徑調整）
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // /lamian-ukn/api
$url = $baseUrl . '/uploads/avatar/' . $fname;

// DB
$pdo = null;
foreach ([__DIR__.'/db.php', __DIR__.'/config.php', __DIR__.'/_db.php', __DIR__.'/connect.php'] as $p) { if (is_file($p)) require_once $p; }
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) $pdo = $GLOBALS['pdo'];
if (!$pdo) {
  $dsn  = "mysql:host=". (getenv('DB_HOST')?:'127.0.0.1') .";port=". (getenv('DB_PORT')?:'3306') .";dbname=". (getenv('DB_NAME')?:'lamian_ukn') .";charset=utf8mb4";
  $pdo = new PDO($dsn, getenv('DB_USER')?:'root', getenv('DB_PASS')?:'', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}
$pdo->exec("CREATE TABLE IF NOT EXISTS `user_profile` (
  `employee_id` INT PRIMARY KEY,
  `email` VARCHAR(150) NULL,
  `address` VARCHAR(255) NULL,
  `emergency_contact` VARCHAR(100) NULL,
  `emergency_phone` VARCHAR(50) NULL,
  `memo` TEXT NULL,
  `avatar_url` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$st = $pdo->prepare("INSERT INTO `user_profile` (employee_id, avatar_url) VALUES (:id, :u)
                     ON DUPLICATE KEY UPDATE avatar_url = VALUES(avatar_url)");
$st->execute([':id'=>$empId, ':u'=>$url]);

echo json_encode(['url'=>$url], JSON_UNESCAPED_UNICODE);
