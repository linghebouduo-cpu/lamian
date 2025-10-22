<?php
// /lamian-ukn/api/me_update.php
header('Content-Type: application/json; charset=utf-8');
session_start();

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];

// 允許更新的欄位
$allow = ['email','address','emergency_contact','emergency_phone','memo'];
$data = array_intersect_key($body, array_flip($allow));

if (empty($data)) { echo json_encode(['error'=>'沒有可更新欄位']); exit; }

// 取得登入 id（或 ?id=）
$empId = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$empId && isset($_GET['id'])) $empId = intval($_GET['id']);
if (!$empId) { http_response_code(401); echo json_encode(['error'=>'未登入']); exit; }

// 連線
$pdo = null;
foreach ([__DIR__.'/db.php', __DIR__.'/config.php', __DIR__.'/_db.php', __DIR__.'/connect.php'] as $f) {
  if (is_file($f)) { require_once $f; }
}
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) $pdo = $GLOBALS['pdo'];
if (!$pdo) {
  $dsn  = "mysql:host=". (getenv('DB_HOST')?:'127.0.0.1') .";port=". (getenv('DB_PORT')?:'3306') .";dbname=". (getenv('DB_NAME')?:'lamian_ukn') .";charset=utf8mb4";
  $pdo = new PDO($dsn, getenv('DB_USER')?:'root', getenv('DB_PASS')?:'', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}

// 確保 profile 表存在
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

// upsert
$cols = array_keys($data);
$params = array_map(fn($c)=>":$c", $cols);
$updates = implode(',', array_map(fn($c)=>"`$c`=VALUES(`$c`)", $cols));
$sql = "INSERT INTO `user_profile` (`employee_id`, `".implode('`,`',$cols)."`)
        VALUES (:emp, ".implode(',', $params).")
        ON DUPLICATE KEY UPDATE $updates";
$st = $pdo->prepare($sql);
$st->bindValue(':emp', $empId, PDO::PARAM_INT);
foreach ($data as $k=>$v) { $st->bindValue(":$k", $v); }
$st->execute();

echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
