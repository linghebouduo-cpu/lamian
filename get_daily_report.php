<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB
$host='localhost'; $dbname='lamian'; $user='root'; $pass='';
try {
  $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'message'=>'資料庫連線失敗：'.$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}

$dayNames = ['日','一','二','三','四','五','六'];

// ---------- A) 指定月份 ----------
if (isset($_GET['month'])) {
  $month = $_GET['month']; // 2025-08
  if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    echo json_encode(['success'=>false,'message'=>'月份格式錯誤，請用 YYYY-MM'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $start = $month.'-01';
  $end   = date('Y-m-t', strtotime($start));

  try {
    // 用 DATE(report_date) 做彙總，避免 DATETIME 時分秒造成對不上
    $sql = "
      SELECT DATE(report_date) AS d,
             SUM(total_income)  AS total_income,
             SUM(total_expense) AS total_expense
      FROM daily_report
      WHERE DATE(report_date) BETWEEN :s AND :e
      GROUP BY DATE(report_date)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':s'=>$start, ':e'=>$end]);
    $rows = $stmt->fetchAll();

    // 逐日補 0
    $result = [];
    $daysInMonth = (int)date('t', strtotime($start));
    for ($i=1; $i <= $daysInMonth; $i++) {
      $d = sprintf('%s-%02d', $month, $i);
      $found = null;
      if ($rows) {
        foreach ($rows as $r) { if ($r['d'] === $d) { $found = $r; break; } }
      }
      $result[] = [
        'report_date'   => $d,
        'weekday'       => '星期'.$dayNames[(int)date('w', strtotime($d))],
        'total_income'  => (int)($found['total_income']  ?? 0),
        'total_expense' => (int)($found['total_expense'] ?? 0),
      ];
    }

    echo json_encode(['success'=>true,'data'=>$result], JSON_UNESCAPED_UNICODE);
    exit;
  } catch(PDOException $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// ---------- B) 預設：過去七日（不含今天或含今天都可；這裡含今天） ----------
try {
  $dates = [];
  for ($i=6; $i>=0; $i--) { $dates[] = date('Y-m-d', strtotime("-{$i} days")); }
  $start = $dates[0]; $end = $dates[6];

  $sql = "
    SELECT DATE(report_date) AS d,
           SUM(total_income)  AS total_income,
           SUM(total_expense) AS total_expense
    FROM daily_report
    WHERE DATE(report_date) BETWEEN :s AND :e
    GROUP BY DATE(report_date)
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':s'=>$start, ':e'=>$end]);
  $rows = $stmt->fetchAll();
  $map = [];
  foreach ($rows as $r) { $map[$r['d']] = $r; }

  $out = [];
  foreach ($dates as $d) {
    $out[] = [
      'report_date'   => $d,
      'weekday'       => '星期'.$dayNames[(int)date('w', strtotime($d))],
      'total_income'  => (int)($map[$d]['total_income']  ?? 0),
      'total_expense' => (int)($map[$d]['total_expense'] ?? 0),
    ];
  }

  echo json_encode(['success'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE);
} catch(PDOException $e) {
  echo json_encode(['success'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
