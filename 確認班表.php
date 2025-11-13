<?php
// 確認班表.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false, 'message' => '未知錯誤'];

try {
    // === 連線資料庫 ===
    $pdo = new PDO(
        'mysql:host=localhost;dbname=lamian;charset=utf8mb4',
        'root', '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // === 建立 employeeMap ===
    $stmtEmp = $pdo->query("SELECT id, name FROM 員工基本資料");
    $employeeMap = [];
    while ($row = $stmtEmp->fetch()) {
        $employeeMap[trim($row['name'])] = $row['id'];
    }

   // === POST：儲存班表 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['assignments'])) {
        throw new Exception('缺少必要資料');
    }

    $assignments = $input['assignments'];
    $weekStart = $input['week_start'] ?? null;

    $pdo->beginTransaction();

   if ($weekStart) {
        // 【修正】先刪除「確認班表」資料表的該週資料
        $stmtDel = $pdo->prepare("
            DELETE FROM 確認班表
            WHERE work_date >= ?
              AND work_date < DATE_ADD(?, INTERVAL 7 DAY)
        ");
        $stmtDel->execute([$weekStart, $weekStart]);
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO 確認班表
        (user_id, work_date, start_time, end_time, shift_type, note, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $count = 0;
    $userSchedule = []; // 檢查重複用

    foreach ($assignments as $date => $periods) {
        foreach ($periods as $shiftType => $list) {
            foreach ($list as $item) {
                $name = trim($item['name'] ?? '');
                $time = trim($item['time'] ?? '');
                if (!$name || !$time) continue;

                $parts = explode('-', $time);
                $start = trim($parts[0] ?? '');
                $end   = trim($parts[1] ?? '');
                if (!$start || !$end) continue;

                if (!isset($employeeMap[$name])) continue;
                $user_id = $employeeMap[$name];

                // 建立 key 方便檢查同一天是否同時段重複
                $key = $user_id . '_' . $date;
                if (!isset($userSchedule[$key])) $userSchedule[$key] = [];

                // 檢查是否與已有班表重疊
                $overlap = false;
                foreach ($userSchedule[$key] as $existing) {
                    if (!($end <= $existing['start'] || $start >= $existing['end'])) {
                        $overlap = true;
                        break;
                    }
                }
                if ($overlap) {
                    $pdo->rollBack();
                    echo json_encode([
                        'success' => false,
                        'message' => "錯誤：{$name} 在 {$date} 時段 {$time} 與其他班表重疊！"
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // 沒重複就加入檢查陣列
                $userSchedule[$key][] = ['start' => $start, 'end' => $end];

                // 寫入確認班表
                $stmtInsert->execute([$user_id, $date, $start, $end, $shiftType, '']);
                $count++;
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "班表已確認並儲存，共儲存 {$count} 筆資料！"], JSON_UNESCAPED_UNICODE);
    exit;
}


    // === GET：讀取本週班表 ===
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['date'])) {
        $date = $_GET['date'];
        $monday = date('Y-m-d', strtotime('monday this week', strtotime($date)));

        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = date('Y-m-d', strtotime("+$i day", strtotime($monday)));
        }

        $stmt = $pdo->prepare("
    SELECT c.user_id, e.name, c.work_date, c.start_time, c.end_time, c.shift_type
    FROM 確認班表 c
    JOIN 員工基本資料 e ON c.user_id = e.id
    WHERE c.work_date BETWEEN ? AND ?
    ORDER BY c.work_date, c.start_time
");

        $stmt->execute([$dates[0], $dates[6]]);
        $rows = $stmt->fetchAll();

        $output = [];
        foreach (['上午','晚上'] as $slot) {
            $weekData = [];
            foreach ($dates as $d) $weekData[$d] = [];

            foreach ($rows as $r) {
                if ($r['shift_type'] === $slot) {
                    $text = "{$r['name']} ({$r['start_time']}-{$r['end_time']})";
                    $weekData[$r['work_date']][] = $text;
                }
            }

            $output[] = [
    'timeSlot' => $slot,
    'days' => array_values(array_map(fn($names) => implode('<br>', $names), $weekData))
];

        }

        echo json_encode($output, JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '伺服器錯誤：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
