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

try {
  $sql = "
    SELECT
      COALESCE(SUM(cash_income),   0) AS cash_income_total,
      COALESCE(SUM(linepay_income),0) AS linepay_income_total,
      COALESCE(SUM(uber_income),   0) AS uber_income_total
    FROM daily_report
    WHERE YEAR(report_date)=:y AND MONTH(report_date)=:m
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':y'=>$year, ':m'=>$month]);
  $row = $st->fetch() ?: [];

  echo json_encode([
    'success'=>true,
    'year'=>$year,
    'month'=>$month,
    'data'=>[
      'cash_income'    => (float)($row['cash_income_total']    ?? 0),
      'linepay_income' => (float)($row['linepay_income_total'] ?? 0),
      'uber_income'    => (float)($row['uber_income_total']    ?? 0),
    ]
  ], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
  echo json_encode(['success'=>false,'message'=>'查詢失敗: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
