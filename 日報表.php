<?php  
// 日報表.php -->  日報表.html
//  儲存日報表資料到資料庫

header('Content-Type: application/json');
date_default_timezone_set('Asia/Taipei');

// 資料庫設定
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// 取得前端傳來的 JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => '資料格式錯誤']);
    exit;
}

// 解析租金設定
$rentSetting = $data['rent_setting'] ?? [];
$rent_period = $rentSetting['period'] ?? '月';
$rent_start  = $rentSetting['start'] ?? null;
$rent_end    = $rentSetting['end'] ?? null;


$sql = "INSERT INTO daily_report (
    report_date, weekday, filled_by,
    cash_income, linepay_income, uber_income, other_income, total_income,
    expense_salary, expense_utilities, expense_rent,
    expense_food, expense_delivery, expense_misc,
    cash_1000, cash_500, cash_100, cash_50, cash_10, cash_5, cash_1, cash_total,
    deposit_to_bank, rent_period, rent_start, rent_end, created_at
) VALUES (
    :report_date, :weekday, :filled_by,
    :cash_income, :linepay_income, :uber_income, :other_income, :total_income,
    :expense_salary, :expense_utilities, :expense_rent,
    :expense_food, :expense_delivery, :expense_misc,
    :cash_1000, :cash_500, :cash_100, :cash_50, :cash_10, :cash_5, :cash_1, :cash_total,
    :deposit_to_bank, :rent_period, :rent_start, :rent_end, NOW()
)";

try {
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':report_date' => $data['report_date'] ?? null,
        ':weekday' => $data['weekday'] ?? null,
        ':filled_by' => $data['filled_by'] ?? null,
        ':cash_income' => $data['cash_income'] ?? 0,
        ':linepay_income' => $data['linepay_income'] ?? 0,
        ':uber_income' => $data['uber_income'] ?? 0,
        ':other_income' => $data['other_income'] ?? 0,
        ':total_income' => $data['total_income'] ?? 0,
        ':expense_salary' => $data['expense_salary'] ?? 0,
        ':expense_utilities' => $data['expense_utilities'] ?? 0,
        ':expense_rent' => $data['expense_rent'] ?? 0,
        ':expense_food' => $data['expense_food'] ?? 0,
        ':expense_delivery' => $data['expense_delivery'] ?? 0,
        ':expense_misc' => $data['expense_misc'] ?? 0,
        ':cash_1000' => $data['cash_1000'] ?? 0,
        ':cash_500' => $data['cash_500'] ?? 0,
        ':cash_100' => $data['cash_100'] ?? 0,
        ':cash_50' => $data['cash_50'] ?? 0,
        ':cash_10' => $data['cash_10'] ?? 0,
        ':cash_5' => $data['cash_5'] ?? 0,
        ':cash_1' => $data['cash_1'] ?? 0,
        ':cash_total' => $data['cash_total'] ?? 0,
        ':deposit_to_bank' => $data['deposit_to_bank'] ?? 0,
        ':rent_period' => $rent_period,
        ':rent_start' => $rent_start,
        ':rent_end' => $rent_end
    ]);

    echo json_encode(['success' => true, 'message' => '日報表已成功儲存']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '儲存失敗: ' . $e->getMessage()]);
}
?>
