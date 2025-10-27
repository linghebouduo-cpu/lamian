<?php 
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 資料庫連線設定
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 查詢資料，欄位名稱保持和前端一致
    $stmt = $pdo->query("
        SELECT 
            id,
            report_date,
            filled_by,
            cash_income,
            linepay_income,
            uber_income,
            other_income,
            total_income,
            expense_food,
            expense_salary,
            expense_rent,
            '' AS monthly_rent,      -- 月租金固定空字串
            expense_utilities,
            expense_delivery,
            expense_misc,
            total_expense
        FROM daily_report
        ORDER BY report_date DESC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
