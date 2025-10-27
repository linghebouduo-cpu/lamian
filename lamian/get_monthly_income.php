<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. 資料庫連線
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫連線失敗: ' . $e->getMessage()
    ]);
    exit;
}

// 2. 接收前端傳來的 year / month，沒傳就用當前年月
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// 3. 查詢當月的收入總和
try {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(cash_income) AS cash_income_total,
            SUM(linepay_income) AS linepay_income_total,
            SUM(uber_income) AS uber_income_total
        FROM daily_report
        WHERE YEAR(report_date) = :year AND MONTH(report_date) = :month
    ");
    $stmt->execute([':year' => $year, ':month' => $month]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 即使沒有資料，也要回傳 0
    $result = [
        'cash_income'   => floatval($row['cash_income_total'] ?? 0),
        'linepay_income'=> floatval($row['linepay_income_total'] ?? 0),
        'uber_income'   => floatval($row['uber_income_total'] ?? 0)
    ];

    echo json_encode([
        'success' => true,
        'year' => $year,
        'month' => $month,
        'data' => $result
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '查詢失敗: ' . $e->getMessage()
    ]);
}
