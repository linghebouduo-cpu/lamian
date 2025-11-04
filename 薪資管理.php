<?php  
// ===== 薪資管理.php =====
$db_host = '127.0.0.1';
$db_name = 'lamian';
$db_user = 'root';
$db_pass = '';
$charset = 'utf8mb4';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

function normalize_record($row)
{
    $base_salary = (float)($row['base_salary'] ?? 0);
    $hourly_rate = (float)($row['hourly_rate'] ?? 0);
    $working_hours = (float)($row['working_hours'] ?? 0);
    $bonus = (float)($row['bonus'] ?? 0);
    $deductions = (float)($row['deductions'] ?? 0);

    // 判斷薪資類型
    $salary_type = $base_salary > 0 ? '月薪' : '時薪';

    // 計算實領
    $total_salary = ($base_salary > 0)
        ? $base_salary + $bonus - $deductions
        : ($hourly_rate * $working_hours + $bonus - $deductions);

    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'salary_month' => $row['salary_month'] ?? '',
        'salary_type' => $salary_type,
        'base_salary' => $base_salary,
        'hourly_rate' => $hourly_rate,
        'working_hours' => $working_hours,
        'bonus' => $bonus,
        'deductions' => $deductions,
        'total_salary' => $total_salary,
    ];
}

try {
    // ===============================
    // 1️⃣ 取得薪資列表
    // ===============================
    if ($action === 'fetch') {
        $month = $input['month'] ?? date('Y-m');
        $keyword = trim($input['keyword'] ?? '');

        $sql = "
            SELECT 
                e.id,
                e.name,
                e.base_salary,
                e.hourly_rate,
                SUM(a.hours) AS working_hours,
                COALESCE(s.bonus, 0) AS bonus,
                COALESCE(s.deductions, 0) AS deductions,
                :month AS salary_month
            FROM `員工基本資料` e
            INNER JOIN `attendance` a 
                ON e.id = a.user_id  
                AND DATE_FORMAT(a.clock_in, '%Y-%m') = :month
            LEFT JOIN `薪資管理` s 
                ON e.id = s.id 
                AND s.salary_month = :month
            WHERE 1
        ";

        $params = ['month' => $month];

        if ($keyword !== '') {
            $sql .= " AND (e.name LIKE :kw OR CAST(e.id AS CHAR) LIKE :kw)";
            $params['kw'] = "%$keyword%";
        }

        $sql .= " 
            GROUP BY e.id, e.name, e.base_salary, e.hourly_rate, s.bonus, s.deductions
            HAVING working_hours > 0
            ORDER BY e.name ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $records = array_map('normalize_record', $rows);

        echo json_encode(['success' => true, 'records' => $records]);
        exit;
    }

    // ===============================
    // 2️⃣ 取得單一員工薪資詳細資料
    // ===============================
    if ($action === 'detail') {
        $id = $input['id'] ?? null;
        $month = $input['month'] ?? date('Y-m');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少 id']);
            exit;
        }

        $sql = "
            SELECT 
                e.id,
                e.name,
                e.base_salary,
                e.hourly_rate,
                SUM(a.hours) AS working_hours,
                COALESCE(s.bonus, 0) AS bonus,
                COALESCE(s.deductions, 0) AS deductions,
                :month AS salary_month
            FROM `員工基本資料` e
            INNER JOIN `attendance` a 
                ON e.id = a.user_id 
                AND DATE_FORMAT(a.clock_in, '%Y-%m') = :month
            LEFT JOIN `薪資管理` s 
                ON e.id = s.id 
                AND s.salary_month = :month
            WHERE e.id = :id
            GROUP BY e.id, e.name, e.base_salary, e.hourly_rate, s.bonus, s.deductions
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'month' => $month]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => '查無資料']);
            exit;
        }

        $record = normalize_record($row);
        echo json_encode(['success' => true, 'record' => $record]);
        exit;
    }

    // ===============================
// 3️⃣ 更新或新增薪資（本月工時、獎金、扣款）
// ===============================
if ($action === 'update') {
    $id = $input['user_id'] ?? null;
    $month = $input['month'] ?? date('Y-m');
    $working_hours = isset($input['working_hours']) ? (float)$input['working_hours'] : 0;
    $bonus = isset($input['bonus']) ? (float)$input['bonus'] : 0;
    $deductions = isset($input['deductions']) ? (float)$input['deductions'] : 0;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => '缺少 user_id']);
        exit;
    }

    try {
        $sql = "
            INSERT INTO `薪資管理` (id, salary_month, working_hours, bonus, deductions)
            VALUES (:id, :month, :working_hours, :bonus, :deductions)
            ON DUPLICATE KEY UPDATE
                working_hours = VALUES(working_hours),
                bonus = VALUES(bonus),
                deductions = VALUES(deductions)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'month' => $month,
            'working_hours' => $working_hours,
            'bonus' => $bonus,
            'deductions' => $deductions
        ]);

        echo json_encode(['success' => true, 'message' => '薪資資料已更新']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '伺服器錯誤：' . $e->getMessage()]);
        exit;
    }
}

    // ===============================
    // 4️⃣ 恢復薪資資料（restore）
    // ===============================
    if ($action === 'restore') {
        $id = $input['user_id'] ?? null;
        $month = $input['month'] ?? date('Y-m');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => '缺少 user_id']);
            exit;
        }

        // 刪除該月該員工薪資資料，恢復原始狀態
        $sql = "DELETE FROM `薪資管理` WHERE id = :id AND salary_month = :month";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'month' => $month]);

        echo json_encode(['success' => true, 'message' => '薪資資料已恢復']);
        exit;
    }

    // ===============================
    // 5️⃣ 未知 action
    // ===============================
    echo json_encode(['success' => false, 'message' => '未知的 action']);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '伺服器錯誤：' . $e->getMessage()]);
    exit;
}
