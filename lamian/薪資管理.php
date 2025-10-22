<?php
// ===== 薪資管理.php =====
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ===== 連線資料庫 =====
$host = 'localhost';
$db   = 'lamian';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// 取得請求方法和動作
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// ===== 路由處理 =====
try {
    switch ($action) {
        case 'list':
            getSalaryList($pdo);
            break;
        
        case 'detail':
            getSalaryDetail($pdo);
            break;
        
        case 'update':
            updateSalary($pdo);
            break;
        
        case 'recalculate':
            recalculateSalary($pdo);
            break;
        
        case 'export':
            exportToExcel($pdo);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => '未知的操作']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '錯誤: ' . $e->getMessage()]);
}

// ===== 1. 取得薪資列表 =====
function getSalaryList($pdo) {
    $month = $_GET['month'] ?? date('Y-m');
    $keyword = $_GET['q'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    // 建立查詢
    $sql = "SELECT 
                id,
                CONCAT(id) as user_id,
                name,
                salary_month,
                base_salary,
                hourly_rate,
                working_hours,
                bonus,
                deductions,
                total_salary
            FROM salaries 
            WHERE salary_month = :month";
    
    $params = ['month' => $month];
    
    // 關鍵字搜尋（姓名或員工編號）
    if (!empty($keyword)) {
        $sql .= " AND (name LIKE :keyword OR CONCAT(id) LIKE :keyword)";
        $params['keyword'] = "%$keyword%";
    }
    
    $sql .= " ORDER BY id ASC";
    
    // 計算總筆數
    $countStmt = $pdo->prepare(str_replace('SELECT id, CONCAT(id) as user_id, name, salary_month, base_salary, hourly_rate, working_hours, bonus, deductions, total_salary', 'SELECT COUNT(*)', $sql));
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // 加入分頁
    $sql .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    // 計算實領薪水（若資料庫未存）
    foreach ($data as &$row) {
        // 計算底薪
        if (!empty($row['hourly_rate']) && $row['hourly_rate'] > 0) {
            $calculated_base = round($row['hourly_rate'] * ($row['working_hours'] ?? 0));
        } else {
            $calculated_base = $row['base_salary'] ?? 0;
        }
        
        // 計算實領
        $calculated_total = $calculated_base + ($row['bonus'] ?? 0) - ($row['deductions'] ?? 0);
        
        // 若資料庫沒有 total_salary，則使用計算值
        if (empty($row['total_salary'])) {
            $row['total_salary'] = $calculated_total;
        }
        
        // 轉換數值格式
        $row['base_salary'] = (int)($row['base_salary'] ?? 0);
        $row['hourly_rate'] = !empty($row['hourly_rate']) ? (int)$row['hourly_rate'] : null;
        $row['working_hours'] = (float)($row['working_hours'] ?? 0);
        $row['bonus'] = (int)($row['bonus'] ?? 0);
        $row['deductions'] = (int)($row['deductions'] ?? 0);
        $row['total_salary'] = (int)$row['total_salary'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit
    ], JSON_UNESCAPED_UNICODE);
}

// ===== 2. 取得薪資詳情 =====
function getSalaryDetail($pdo) {
    $user_id = $_GET['user_id'] ?? 0;
    $month = $_GET['month'] ?? date('Y-m');
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => '缺少員工ID']);
        return;
    }
    
    // 取得基本薪資資料
    $stmt = $pdo->prepare("
        SELECT 
            id,
            CONCAT(id) as user_id,
            name,
            salary_month,
            base_salary,
            hourly_rate,
            working_hours,
            bonus,
            deductions,
            total_salary
        FROM salaries 
        WHERE id = :user_id AND salary_month = :month
    ");
    $stmt->execute(['user_id' => $user_id, 'month' => $month]);
    $salary = $stmt->fetch();
    
    if (!$salary) {
        echo json_encode(['success' => false, 'message' => '找不到該筆薪資記錄']);
        return;
    }
    
    // 計算實領薪水
    if (!empty($salary['hourly_rate']) && $salary['hourly_rate'] > 0) {
        $calculated_base = round($salary['hourly_rate'] * ($salary['working_hours'] ?? 0));
    } else {
        $calculated_base = $salary['base_salary'] ?? 0;
    }
    $calculated_total = $calculated_base + ($salary['bonus'] ?? 0) - ($salary['deductions'] ?? 0);
    
    if (empty($salary['total_salary'])) {
        $salary['total_salary'] = $calculated_total;
    }
    
    // 轉換數值格式
    $salary['base_salary'] = (int)($salary['base_salary'] ?? 0);
    $salary['hourly_rate'] = !empty($salary['hourly_rate']) ? (int)$salary['hourly_rate'] : null;
    $salary['working_hours'] = (float)($salary['working_hours'] ?? 0);
    $salary['bonus'] = (int)($salary['bonus'] ?? 0);
    $salary['deductions'] = (int)($salary['deductions'] ?? 0);
    $salary['total_salary'] = (int)$salary['total_salary'];
    
    // ===== 取得詳細彙整資料 =====
    $breakdown = [
        'from_daily_reports' => [],
        'from_clock' => [],
        'notes' => ''
    ];
    
    // 從日報表取得獎金/津貼來源（如果有相關表格）
    try {
        $dailyStmt = $pdo->prepare("
            SELECT 
                report_date as title,
                total_income as amount
            FROM daily_reports 
            WHERE filled_by = (SELECT name FROM salaries WHERE id = :user_id LIMIT 1)
            AND DATE_FORMAT(report_date, '%Y-%m') = :month
            ORDER BY report_date
        ");
        $dailyStmt->execute(['user_id' => $user_id, 'month' => $month]);
        $breakdown['from_daily_reports'] = $dailyStmt->fetchAll();
    } catch (PDOException $e) {
        // 如果沒有 daily_reports 表，跳過
    }
    
    // 從打卡記錄取得工時明細（如果有相關表格）
    try {
        $clockStmt = $pdo->prepare("
            SELECT 
                DATE(clock_in) as date,
                ROUND(TIMESTAMPDIFF(SECOND, clock_in, clock_out) / 3600, 2) as hours
            FROM attendance 
            WHERE user_id = :user_id
            AND DATE_FORMAT(clock_in, '%Y-%m') = :month
            AND clock_out IS NOT NULL
            ORDER BY clock_in
        ");
        $clockStmt->execute(['user_id' => $user_id, 'month' => $month]);
        $breakdown['from_clock'] = $clockStmt->fetchAll();
    } catch (PDOException $e) {
        // 如果沒有 attendance 表，跳過
    }
    
    $breakdown['notes'] = "本月薪資計算已完成，如有疑問請洽人事部門。";
    
    echo json_encode([
        'success' => true,
        'data' => [
            'salary' => $salary,
            'breakdown' => $breakdown
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// ===== 3. 更新薪資 =====
function updateSalary($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $input['user_id'] ?? 0;
    $salary_month = $input['salary_month'] ?? '';
    $base_salary = $input['base_salary'] ?? 0;
    $hourly_rate = $input['hourly_rate'] ?? null;
    $bonus = $input['bonus'] ?? 0;
    $deductions = $input['deductions'] ?? 0;
    
    if (empty($user_id) || empty($salary_month)) {
        echo json_encode(['success' => false, 'message' => '缺少必要參數']);
        return;
    }
    
    // 取得目前工時（不允許修改工時，由系統計算）
    $stmt = $pdo->prepare("SELECT working_hours FROM salaries WHERE id = :user_id AND salary_month = :month");
    $stmt->execute(['user_id' => $user_id, 'month' => $salary_month]);
    $current = $stmt->fetch();
    $working_hours = $current['working_hours'] ?? 0;
    
    // 計算實領薪水
    if (!empty($hourly_rate) && $hourly_rate > 0) {
        $calculated_base = round($hourly_rate * $working_hours);
        $base_salary = 0; // 時薪制時底薪設為 0
    } else {
        $calculated_base = $base_salary;
        $hourly_rate = null; // 月薪制時時薪設為 null
    }
    $total_salary = $calculated_base + $bonus - $deductions;
    
    // 更新資料
    $updateStmt = $pdo->prepare("
        UPDATE salaries 
        SET 
            base_salary = :base_salary,
            hourly_rate = :hourly_rate,
            bonus = :bonus,
            deductions = :deductions,
            total_salary = :total_salary
        WHERE id = :user_id AND salary_month = :salary_month
    ");
    
    $result = $updateStmt->execute([
        'base_salary' => $base_salary,
        'hourly_rate' => $hourly_rate,
        'bonus' => $bonus,
        'deductions' => $deductions,
        'total_salary' => $total_salary,
        'user_id' => $user_id,
        'salary_month' => $salary_month
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '薪資更新成功',
            'data' => [
                'total_salary' => $total_salary
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => '更新失敗']);
    }
}

// ===== 4. 重新計算薪資（結合日報表/打卡） =====
function recalculateSalary($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $month = $input['month'] ?? date('Y-m');
    
    // 開始交易
    $pdo->beginTransaction();
    
    try {
        // 取得該月所有員工
        $stmt = $pdo->prepare("SELECT id FROM salaries WHERE salary_month = :month");
        $stmt->execute(['month' => $month]);
        $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $updated = 0;
        
        foreach ($employees as $user_id) {
            // 從打卡記錄計算工時
            try {
                $hoursStmt = $pdo->prepare("
                    SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, clock_in, clock_out) / 3600), 0) as total_hours
                    FROM attendance 
                    WHERE user_id = :user_id
                    AND DATE_FORMAT(clock_in, '%Y-%m') = :month
                    AND clock_out IS NOT NULL
                ");
                $hoursStmt->execute(['user_id' => $user_id, 'month' => $month]);
                $hoursData = $hoursStmt->fetch();
                $working_hours = round($hoursData['total_hours'], 2);
            } catch (PDOException $e) {
                $working_hours = 0;
            }
            
            // 更新工時
            $updateStmt = $pdo->prepare("
                UPDATE salaries 
                SET working_hours = :working_hours
                WHERE id = :user_id AND salary_month = :month
            ");
            $updateStmt->execute([
                'working_hours' => $working_hours,
                'user_id' => $user_id,
                'month' => $month
            ]);
            
            // 重新計算實領薪水
            $salaryStmt = $pdo->prepare("
                SELECT base_salary, hourly_rate, bonus, deductions 
                FROM salaries 
                WHERE id = :user_id AND salary_month = :month
            ");
            $salaryStmt->execute(['user_id' => $user_id, 'month' => $month]);
            $salary = $salaryStmt->fetch();
            
            if ($salary) {
                if (!empty($salary['hourly_rate']) && $salary['hourly_rate'] > 0) {
                    $calculated_base = round($salary['hourly_rate'] * $working_hours);
                } else {
                    $calculated_base = $salary['base_salary'];
                }
                $total_salary = $calculated_base + $salary['bonus'] - $salary['deductions'];
                
                $totalStmt = $pdo->prepare("
                    UPDATE salaries 
                    SET total_salary = :total_salary 
                    WHERE id = :user_id AND salary_month = :month
                ");
                $totalStmt->execute([
                    'total_salary' => $total_salary,
                    'user_id' => $user_id,
                    'month' => $month
                ]);
                
                $updated++;
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "已重新計算 $updated 筆薪資記錄",
            'updated' => $updated
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => '重新計算失敗: ' . $e->getMessage()]);
    }
}

// ===== 5. 匯出 Excel =====
function exportToExcel($pdo) {
    $month = $_GET['month'] ?? date('Y-m');
    $keyword = $_GET['q'] ?? '';
    
    // 建立查詢
    $sql = "SELECT 
                id as '員工ID',
                name as '姓名',
                salary_month as '發薪月份',
                CASE 
                    WHEN hourly_rate IS NOT NULL AND hourly_rate > 0 THEN CONCAT(hourly_rate, '元/時')
                    ELSE CONCAT(base_salary, '元/月')
                END as '薪資類型',
                CASE 
                    WHEN hourly_rate IS NOT NULL AND hourly_rate > 0 THEN hourly_rate
                    ELSE base_salary
                END as '底薪或時薪',
                working_hours as '本月工時',
                bonus as '獎金',
                deductions as '扣款',
                total_salary as '實領薪水'
            FROM salaries 
            WHERE salary_month = :month";
    
    $params = ['month' => $month];
    
    if (!empty($keyword)) {
        $sql .= " AND (name LIKE :keyword OR CONCAT(id) LIKE :keyword)";
        $params['keyword'] = "%$keyword%";
    }
    
    $sql .= " ORDER BY id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    // 設定 Excel 標頭
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="薪資管理_' . $month . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // 使用 PhpSpreadsheet 或簡單的 CSV 格式
    // 這裡使用 CSV 方式（簡單實作）
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="薪資管理_' . $month . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    // 寫入標題列
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // 寫入資料
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}
?>