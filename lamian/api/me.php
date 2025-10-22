<?php
// /lamian-ukn/api/me.php
header('Content-Type: application/json; charset=utf-8');
session_start();

/** 取得 PDO（沿用你專案的連線檔名；失敗則用環境變數/預設） */
$pdo = null;
foreach ([__DIR__.'/db.php', __DIR__.'/config.php', __DIR__.'/_db.php', __DIR__.'/connect.php'] as $f) {
  if (is_file($f)) { require_once $f; }
}
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
  $pdo = $GLOBALS['pdo'];
}
if (!$pdo) {
  $host = getenv('DB_HOST') ?: '127.0.0.1';
  $port = getenv('DB_PORT') ?: '3306';
  $db   = getenv('DB_NAME') ?: 'lamian_ukn';
  $user = getenv('DB_USER') ?: 'root';
  $pass = getenv('DB_PASS') ?: '';
  $dsn  = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}

/** 取得登入者 ID（沒有登入時允許用 ?id= for 測試） */
$empId = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? null;
if (!$empId && isset($_GET['id'])) $empId = intval($_GET['id']);

/** 找出「員工表」名稱：需同時具備 name / Telephone / Position 欄位 */
$findTableSql = "
SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME IN ('name','Telephone','Position')
GROUP BY TABLE_NAME
HAVING COUNT(DISTINCT COLUMN_NAME)=3
LIMIT 1";
$empTable = $pdo->query($findTableSql)->fetchColumn();
if (!$empTable) {
  http_response_code(500);
  echo json_encode(['error'=>'找不到員工基本資料表（需有 name / Telephone / Position 欄位）'], JSON_UNESCAPED_UNICODE);
  exit;
}

/** 若尚未登入，用員工表的第一筆當示範 */
if (!$empId) {
  $empId = $pdo->query("SELECT id FROM `{$empTable}` ORDER BY id LIMIT 1")->fetchColumn();
}

/** 員工資料 */
$st = $pdo->prepare("SELECT * FROM `{$empTable}` WHERE id = :id LIMIT 1");
$st->execute([':id'=>$empId]);
$emp = $st->fetch();
if (!$emp) {
  http_response_code(404);
  echo json_encode(['error'=>'找不到員工'], JSON_UNESCAPED_UNICODE);
  exit;
}

/** 建立/讀取個人附加資料表 user_profile */
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

$st2 = $pdo->prepare("SELECT * FROM `user_profile` WHERE employee_id = :id LIMIT 1");
$st2->execute([':id'=>$empId]);
$prof = $st2->fetch() ?: [];

/** 合併輸出（欄位名稱照你提供） */
$out = [
  'id'        => $emp['id'],
  'name'      => $emp['name'],
  'Telephone' => $emp['Telephone'] ?? $emp['telephone'] ?? null,
  'Position'  => $emp['Position']  ?? $emp['position']  ?? null,

  // 可編輯區
  'email'              => $prof['email'] ?? null,
  'address'            => $prof['address'] ?? ($emp['address'] ?? null),
  'emergency_contact'  => $prof['emergency_contact'] ?? null,
  'emergency_phone'    => $prof['emergency_phone'] ?? null,
  'memo'               => $prof['memo'] ?? null,
  'avatar_url'         => $prof['avatar_url'] ?? null,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE);
