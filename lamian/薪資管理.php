<?php
// 薪資管理.php
$db_host = '127.0.0.1';
$db_name = 'lamian';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

header('Content-Type: application/json; charset=utf-8');

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗：' . $e->getMessage()]);
    exit;
}

// 解析 JSON 請求
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : '';

function normalize_record($row) {
    $row['id'] = $row['id'] ?? null;
    $row['name'] = $row['name'] ?? '';
    $row['salary_month'] = $row['salary_month'] ?? '';
    $row['base_salary'] = is_numeric($row['base_salary']) ? (float)$row['base_salary'] : null;
    $row['hourly_rate'] = is_numeric($row['hourly_rate']) ? (float)$row['hourly_rate'] : null;
    $row['working_hours'] = is_numeric($row['working_hours']) ? (float)$row['working_hours'] : 0.0;
    $row['bonus'] = is_numeric($row['bonus']) ? (float)$row['bonus'] : 0.0;
    $row['deductions'] = is_numeric($row['deductions']) ? (float)$row['deductions'] : 0.0;

    if (isset($row['total_salary']) && is_numeric($row['total_salary'])) {
        $row['total_salary'] = (float)$row['total_salary'];
    } else {
        // 計算總薪資
        $base = $row['base_salary'] ?? 0.0;
        if ($base === 0.0 && isset($row['hourly_rate'])) {
            $base = $row['hourly_rate'] * $row['working_hours'];
        }
        $row['total_salary'] = $base + $row['bonus'] - $row['deductions'];
    }

    // 判斷薪資類型
    $row['salary_type'] = $row['base_salary'] ? "月薪" : "時薪";
    return $row;
}

try {
    // ===== fetch 薪資列表 =====
    if ($action === 'fetch') {
        $month = $input['month'] ?? date('Y-m');
        $keyword = isset($input['keyword']) ? trim($input['keyword']) : '';

        // SQL 查詢，包括員工基本資料和出勤表
        $sql = "SELECT s.id, e.name, s.salary_month, s.base_salary, s.hourly_rate, a.working_hours, s.bonus, s.deductions, s.total_salary
                FROM `薪資管理` s
                JOIN `員工基本資料` e ON s.id = e.id
                LEFT JOIN `attendance` a ON s.id = a.emp_id AND a.date LIKE :month
                WHERE s.salary_month = :month";

        $params = ['month' => "$month%"];

        if ($keyword !== '') {
            $sql .= " AND (e.name LIKE :kw OR CAST(s.id AS CHAR) LIKE :kw)";
            $params['kw'] = "%$keyword%";
        }

        $sql .= " ORDER BY e.name ASC, s.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $records = array_map('normalize_record', $rows);
        echo json_encode(['success' => true, 'records' => $records]);
        exit;
    }

    // ===== detail 薪資資料 =====
    if ($action === 'detail') {
        $id = $input['id'] ?? null;
        $month = $input['month'] ?? null;

        if (!$id || !$month) {
            echo json_encode(['success' => false, 'message' => '缺少 id 或 month 參數']);
            exit;
        }

        // SQL 查詢薪資資料及員工基本資料
        $sql = "SELECT s.id, e.name, s.salary_month, s.base_salary, s.hourly_rate, a.working_hours, s.bonus, s.deductions, s.total_salary
                FROM `薪資管理` s
                JOIN `員工基本資料` e ON s.id = e.id
                LEFT JOIN `attendance` a ON s.id = a.emp_id AND a.date LIKE :month
                WHERE s.id = :id AND s.salary_month = :month
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'month' => "$month%"]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => '找不到此筆薪資資料']);
            exit;
        }

        $record = normalize_record($row);
        echo json_encode(['success' => true, 'record' => $record]);
        exit;
    }

    // ===== update 修改薪資 =====
    if ($action === 'update') {
        $user_id = $input['user_id'] ?? null;
        $month = $input['month'] ?? null;
        $paytype = $input['paytype'] ?? 'monthly';
        $base_salary = floatval($input['base_salary'] ?? 0);
        $hourly_rate = floatval($input['hourly_rate'] ?? 0);
        $bonus = floatval($input['bonus'] ?? 0);
        $deductions = floatval($input['deductions'] ?? 0);

        if (!$user_id || !$month) {
            echo json_encode(['success' => false, 'message' => '缺少 user_id 或 month']);
            exit;
        }

        // 取得該員工當月工時
        $stmt = $pdo->prepare("SELECT working_hours FROM `attendance` WHERE emp_id = :id AND date LIKE :month");
        $stmt->execute(['id' => $user_id, 'month' => "$month%"]);
        $row = $stmt->fetch();
        $working_hours = $row ? floatval($row['working_hours']) : 0;

        // 根據薪資類型自動清空另一個欄位
        if ($paytype === 'monthly') {
            $hourly_rate = 0;
        } else { // 時薪
            $base_salary = 0;
        }

        // 計算底薪 / 總薪資
        $calc_base = ($paytype === 'monthly') ? $base_salary : ($hourly_rate * $working_hours);
        $total_salary = $calc_base + $bonus - $deductions;

        // 更新資料
        $sql = "UPDATE `薪資管理` 
                SET base_salary = :base, hourly_rate = :hourly, bonus = :bonus, deductions = :deductions, total_salary = :total
                WHERE id = :id AND salary_month = :month";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            'base' => $base_salary,
            'hourly' => $hourly_rate,
            'bonus' => $bonus,
            'deductions' => $deductions,
            'total' => $total_salary,
            'id' => $user_id,
            'month' => $month
        ]);

        if ($success) {
            echo json_encode(['success' => true, 'message' => '薪資資料已更新']);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失敗']);
        }
        exit;
    }

    // ===== 未知 action =====
    echo json_encode(['success' => false, 'message' => '未知的 action']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '伺服器錯誤：' . $e->getMessage()]);
    exit;
}
