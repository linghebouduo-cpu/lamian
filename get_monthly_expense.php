<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host='localhost'; $dbname='lamian'; $user='root'; $pass='';
try {
  $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  ]);
} catch(PDOException $e) {
  echo json_encode(['success'=>false,'message'=>'資料庫連線失敗: '.$e->getMessage()], JSON_UNESCAPED_UNICODE); exit;
}

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

$start = sprintf('%04d-%02d-01', $year, $month);
$end   = date('Y-m-t', strtotime($start));

try {
  $sql = "
    SELECT
      COALESCE(SUM(expense_food),      0) AS expense_food,
      COALESCE(SUM(expense_salary),    0) AS expense_salary,
      COALESCE(SUM(expense_utilities), 0) AS expense_utilities,
      COALESCE(SUM(expense_delivery),  0) AS expense_delivery,
      COALESCE(SUM(expense_rent),      0) AS expense_rent,
      COALESCE(SUM(expense_misc),      0) AS expense_misc
    FROM daily_report
    WHERE DATE(report_date) BETWEEN :s AND :e
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':s'=>$start, ':e'=>$end]);
  $r = $st->fetch() ?: [];

  $data = [
    ['category'=>'食材成本',     'amount'=>(float)($r['expense_food']      ?? 0)],
    ['category'=>'人力成本',     'amount'=>(float)($r['expense_salary']    ?? 0)],
    ['category'=>'水電瓦斯',     'amount'=>(float)($r['expense_utilities'] ?? 0)],
    ['category'=>'外送平台抽成', 'amount'=>(float)($r['expense_delivery']  ?? 0)],
    ['category'=>'租金',         'amount'=>(float)($r['expense_rent']      ?? 0)],
    ['category'=>'雜項',         'amount'=>(float)($r['expense_misc']      ?? 0)],
  ];

  echo json_encode(['success'=>true,'year'=>$year,'month'=>$month,'data'=>$data], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
  echo json_encode(['success'=>false,'message'=>'查詢失敗: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
