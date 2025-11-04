<?php
session_start();  // 啟用 session

header('Content-Type: application/json');
$host = 'localhost';
$db   = 'lamian';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// PDO 連線
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    exit;
}

// 確認登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false, 'error'=>'未登入']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

// Helper: 取得 JSON POST
function getJsonPost() {
    $data = file_get_contents('php://input');
    return json_decode($data, true);
}

// GET: 取得某週班表（同一天多個時段，保持陣列格式）
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start = $_GET['start'] ?? null;
    if (!$start) {
        echo json_encode(['rows'=>[]]);
        exit;
    }
    $end = date('Y-m-d', strtotime($start . ' +6 days'));

    $stmt = $pdo->prepare("
        SELECT b.*, u.name 
        FROM 班表 b
        LEFT JOIN 員工基本資料 u ON b.user_id = u.id
        WHERE work_date BETWEEN ? AND ?
        ORDER BY u.id, work_date, start_time
    ");
    $stmt->execute([$start, $end]);
    $rows = $stmt->fetchAll();

    $data = [];
    foreach ($rows as $r) {
        $uid = $r['user_id'];
        $date = $r['work_date'];
        $shiftStr = $r['start_time'] && $r['end_time'] ? "{$r['start_time']}~{$r['end_time']} {$r['note']}" : '';
        if (!isset($data[$uid])) {
            $data[$uid] = [
                'name' => $r['name'],
                'shifts' => array_fill(0, 7, []), // 每天保持陣列，可放多個時段
            ];
        }
        $dayIndex = (date('N', strtotime($date)) + 6) % 7;
        if ($shiftStr) {
            $data[$uid]['shifts'][$dayIndex][] = $shiftStr; // 每個時段保留為單獨元素
        }
    }

    echo json_encode(['rows'=>array_values($data)]);
    exit;
}


// POST: 儲存填報班表
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post = getJsonPost();
    $weekStart = $post['week_start'] ?? null;
    $availability = $post['availability'] ?? null;

    if (!$weekStart || !$availability) {
        echo json_encode(['success'=>false, 'error'=>'缺少 week_start 或 availability']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        foreach ($availability as $date => $ranges) {
            foreach ($ranges as $r) {
                $stmt = $pdo->prepare("
                    INSERT INTO 班表 (user_id, work_date, start_time, end_time, note)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $currentUserId,       // 使用登入者
                    $date,
                    $r['start'],
                    $r['end'],
                    $r['note'] ?? ''
                ]);
            }
        }
        $pdo->commit();
        echo json_encode(['success'=>true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}
?>
