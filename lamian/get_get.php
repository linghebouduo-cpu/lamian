<?php
//用於{日報表記錄}


header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB 設定：若你的設定不同請修改
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';


try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM daily_report WHERE id = :id");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) {
        echo json_encode(['success'=>true, 'data'=>$row], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success'=>false, 'message'=>'找不到資料']);
    }
    exit;
}


// 取得並清理輸入
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? trim($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? trim($_GET['end_date']) : null;
$filled_by = isset($_GET['filled_by']) && $_GET['filled_by'] !== '' ? trim($_GET['filled_by']) : null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 15;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'report_date';
$sort_dir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc' ? 'ASC' : 'DESC';

// 白名單欄位（避免 SQL injection）
$allowedSort = ['report_date', 'total_income', 'total_expense', 'filled_by', 'id'];
if (!in_array($sort_by, $allowedSort)) {
    $sort_by = 'report_date';
}

// 建構 WHERE 子句
$wheres = [];
$params = [];
if ($start_date) {
    $wheres[] = "report_date >= :start_date";
    $params['start_date'] = $start_date;
}
if ($end_date) {
    // 如果 report_date 是 DATE，這樣也能用
    // 如果是 DATETIME，這樣可以包含當天所有時段
    $wheres[] = "report_date <= :end_date";
    $params['end_date'] = $end_date . " 23:59:59";
}
if ($filled_by) {
    $wheres[] = "TRIM(filled_by) = :filled_by";
    $params['filled_by'] = trim($filled_by);
}

$whereSql = count($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';

// 先取得總筆數
try {
    $countSql = "SELECT COUNT(*) AS cnt FROM daily_report {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total_records = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '查詢失敗: ' . $e->getMessage()]);
    exit;
}

// 計算分頁
if ($per_page <= 0) {
    // per_page == 0 表示不分頁，回傳全部
    $total_pages = $total_records > 0 ? 1 : 0;
    $offset = 0;
} else {
    $total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;
    $offset = ($page - 1) * $per_page;
}

// 取資料（支援 per_page=0 => 不加 LIMIT）
try {
    $dataSql = "SELECT * FROM daily_report {$whereSql} ORDER BY {$sort_by} {$sort_dir}";
    if ($per_page > 0) {
        $dataSql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $pdo->prepare($dataSql);
    // 綁定 where 參數
    foreach ($params as $k => $v) {
        $stmt->bindValue(":{$k}", $v);
    }
    if ($per_page > 0) {
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '讀取資料失敗: ' . $e->getMessage()]);
    exit;
}

// 為了計算範圍內的總收入、總支出、淨收入，這裡我們抓取全範圍資料並在 PHP 計算（避免 DB 欄位不存在導致 SQL 聚合出錯）
$totals = [
    'total_income_sum' => 0,
    'total_expense_sum' => 0,
    'net_income' => 0,
    'total_records' => $total_records
];
try {
    // 取出所有符合條件的資料（不加 LIMIT）
    $allSql = "SELECT * FROM daily_report {$whereSql} ORDER BY report_date DESC";
    $allStmt = $pdo->prepare($allSql);
    foreach ($params as $k => $v) {
        $allStmt->bindValue(":{$k}", $v);
    }
    $allStmt->execute();
    $allRows = $allStmt->fetchAll();

    foreach ($allRows as $r) {
        // 若欄位不存在，使用 0
        $totals['total_income_sum'] += isset($r['total_income']) ? floatval($r['total_income']) : 0.0;
        $totals['total_expense_sum'] += isset($r['total_expense']) ? floatval($r['total_expense']) : 0.0;
    }
    $totals['net_income'] = $totals['total_income_sum'] - $totals['total_expense_sum'];
} catch (Exception $e) {
    // 如果抓 allRows 失敗，總和保持 0（但不終止）
}

// 填表人下拉選單（範圍內 distinct）
$filled_by_options = [];
try {
    $fbSql = "SELECT DISTINCT filled_by FROM daily_report {$whereSql} ORDER BY filled_by ASC";
    $fbStmt = $pdo->prepare($fbSql);
    foreach ($params as $k => $v) {
        $fbStmt->bindValue(":{$k}", $v);
    }
    $fbStmt->execute();
    $fbRows = $fbStmt->fetchAll();
    foreach ($fbRows as $r) {
        if (isset($r['filled_by'])) $filled_by_options[] = $r['filled_by'];
    }
} catch (Exception $e) {
    // 忽略
}

// 回傳
echo json_encode([
    'success' => true,
    'data' => $rows,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => $total_pages,
    'total_records' => $total_records,
    'totals' => $totals,
    'filled_by_options' => $filled_by_options
], JSON_UNESCAPED_UNICODE);
