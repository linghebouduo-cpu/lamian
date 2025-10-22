<?php 
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. 資料庫連線設定
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫連線失敗: ' . $e->getMessage()
    ]);
    exit;
}

// 2. 取得 GET 參數
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// 驗證年份和月份
if ($year <= 0 || $month <= 0 || $month > 12) {
    echo json_encode([
        'success' => false,
        'message' => '年份或月份錯誤'
    ]);
    exit;
}

// 3. 計算該月的起訖日期
$start_date = sprintf("%04d-%02d-01", $year, $month);
$end_date = date("Y-m-t", strtotime($start_date)); // 當月最後一天

try {
    // 4. 從 daily_report 加總各項成本
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(expense_food), 0) AS expense_food,
            COALESCE(SUM(expense_salary), 0) AS expense_salary,
            COALESCE(SUM(expense_utilities), 0) AS expense_utilities,
            COALESCE(SUM(expense_delivery), 0) AS expense_delivery,
            COALESCE(SUM(expense_rent), 0) AS expense_rent,
            COALESCE(SUM(expense_misc), 0) AS expense_misc
        FROM daily_report
        WHERE report_date BETWEEN :start_date AND :end_date
    ");
    $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. 整理成前端需要的格式
    $data = [
        ['category' => '食材成本', 'amount' => floatval($row['expense_food'])],
        ['category' => '人力成本', 'amount' => floatval($row['expense_salary'])],
        ['category' => '水電瓦斯', 'amount' => floatval($row['expense_utilities'])],
        ['category' => '外送平台抽成', 'amount' => floatval($row['expense_delivery'])],
        ['category' => '租金', 'amount' => floatval($row['expense_rent'])],
        ['category' => '雜項', 'amount' => floatval($row['expense_misc'])]
    ];

    echo json_encode([
        'success' => true,
        'year' => $year,
        'month' => $month,
        'data' => $data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '查詢失敗: ' . $e->getMessage()
    ]);
    exit;
}
?>
