<?php
// ✅ 穩定版 - 確認班表.php（修正多人成員 & 時間格式問題）

session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== 資料庫連線設定 =====
$host    = 'localhost';
$db      = 'lamian';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => '資料庫連線失敗: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 權限檢查 =====
if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '未登入'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userLevel = $_SESSION['user_level'] ?? $_SESSION['role'] ?? $_SESSION['role_code'] ?? 'C';

if (!in_array($userLevel, ['A', 'B'])) {
    http_response_code(403);
    echo json_encode([
        'success'       => false,
        'error'         => '權限不足 (僅限老闆或管理員)',
        'current_level' => $userLevel
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUserId   = $_SESSION['uid'];
$currentUserName = $_SESSION['name'] ?? '未知';

error_log("確認班表.php - 登入用戶: ID={$currentUserId}, Name={$currentUserName}, Level={$userLevel}");

// ===== 建立員工姓名 -> ID 對照表 =====
try {
    $stmtEmp = $pdo->query("SELECT id, name FROM 員工基本資料 ORDER BY id");
    $employeeMap = [];

    while ($row = $stmtEmp->fetch()) {
        $employeeMap[trim($row['name'])] = $row['id'];
    }

    error_log("確認班表.php - 員工對照表建立完成: " . count($employeeMap) . " 人");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => '無法載入員工清單: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// POST: 儲存本週班表
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['assignments'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要資料 (assignments)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $assignments = $input['assignments'];
    $weekStart   = $input['week_start'] ?? null;

    if (!$weekStart) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要資料 (week_start)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();
    $warnings = [];

    try {
        // 1. 清掉這一週舊的班表
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        $stmtDel = $pdo->prepare("
            DELETE FROM 確認班表
            WHERE work_date BETWEEN ? AND ?
        ");
        $stmtDel->execute([$weekStart, $weekEnd]);

        $deletedCount = $stmtDel->rowCount();
        error_log("確認班表.php - 刪除舊資料: {$deletedCount} 筆 (週 {$weekStart} ~ {$weekEnd})");

        // 2. 準備 INSERT
        $stmtInsert = $pdo->prepare("
            INSERT INTO 確認班表
                (user_id, work_date, start_time, end_time, shift_type, note, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $insertCount  = 0;
        $userSchedule = [];  // 用來檢查「同一人同一天的時段是否重疊」

        foreach ($assignments as $date => $periods) {
            // 日期格式檢查
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $warnings[] = "略過無效日期: {$date}";
                continue;
            }

            foreach ($periods as $shiftType => $list) {
                // 只接受「上午 / 晚上」
                if (!in_array($shiftType, ['上午', '晚上'], true)) {
                    $warnings[] = "略過無效班別: {$shiftType} ({$date})";
                    continue;
                }

                foreach ($list as $item) {
                    $name = trim($item['name'] ?? '');
                    $time = trim($item['time'] ?? '');

                    if (!$name || !$time) {
                        $warnings[] = "略過空白資料: date={$date}, type={$shiftType}";
                        continue;
                    }

                    // 解析 "HH:MM-HH:MM" 或 "HH:MM:SS-HH:MM:SS"
                    $parts = explode('-', $time);
                    if (count($parts) !== 2) {
                        $warnings[] = "時間格式錯誤: {$name} {$date} {$time}";
                        continue;
                    }

                    $start = trim($parts[0]);
                    $end   = trim($parts[1]);

                    // ✅ 若有秒數，先砍掉秒，只留 HH:MM
                    if (strlen($start) >= 5) $start = substr($start, 0, 5);
                    if (strlen($end)   >= 5) $end   = substr($end,   0, 5);

                    if (!preg_match('/^\d{2}:\d{2}$/', $start) ||
                        !preg_match('/^\d{2}:\d{2}$/', $end)) {
                        $warnings[] = "時間格式不正確: {$name} {$date} {$start}-{$end}";
                        continue;
                    }

                    // 找員工 ID
                    if (!isset($employeeMap[$name])) {
                        $warnings[] = "找不到員工: {$name}";
                        continue;
                    }

                    $user_id = $employeeMap[$name];

                    // 檢查這個人同一天時段是否重疊
                    $key = $user_id . '_' . $date;
                    if (!isset($userSchedule[$key])) {
                        $userSchedule[$key] = [];
                    }

                    $overlap = false;
                    foreach ($userSchedule[$key] as $existing) {
                        // 如果不是「完全在左邊」或「完全在右邊」，就是有重疊
                        if (!($end <= $existing['start'] || $start >= $existing['end'])) {
                            $overlap = true;
                            $warnings[] = "時段重疊，已略過：{$name} {$date} {$start}-{$end}";
                            break;
                        }
                    }

                    if ($overlap) {
                        // 不再整包 rollback，只略過這一筆
                        continue;
                    }

                    // 記錄這段時間
                    $userSchedule[$key][] = ['start' => $start, 'end' => $end];

                    // 寫入資料
                    $stmtInsert->execute([
                        $user_id,
                        $date,
                        $start,
                        $end,
                        $shiftType,
                        $item['note'] ?? ''
                    ]);

                    $insertCount++;
                }
            }
        }

        $pdo->commit();

        error_log("確認班表.php - 儲存成功: 週 {$weekStart}, 新增 {$insertCount} 筆, 刪除 {$deletedCount} 筆");

        echo json_encode([
            'success'      => true,
            'message'      => "班表已確認並儲存，共儲存 {$insertCount} 筆資料！",
            'inserted'     => $insertCount,
            'deleted'      => $deletedCount,
            'warnings'     => $warnings,
            'week_start'   => $weekStart,
            'week_end'     => $weekEnd
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("確認班表.php - 儲存失敗: " . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => '儲存失敗: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// =====================================================
// GET: 讀取本週班表（給「當前週班表(唯讀)」+ 編輯區初始化用）
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少 date 參數'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $date = $_GET['date'];

    $timestamp = strtotime($date);
    $dayOfWeek = date('N', $timestamp); // 1=Monday, 7=Sunday
    $monday    = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days', $timestamp));
    $sunday    = date('Y-m-d', strtotime('+6 days', strtotime($monday)));

    // 週一 ~ 週日
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime("+{$i} day", strtotime($monday)));
    }

    try {
        $stmt = $pdo->prepare("
            SELECT c.user_id, e.name, c.work_date, c.start_time, c.end_time, c.shift_type
            FROM 確認班表 c
            JOIN 員工基本資料 e ON c.user_id = e.id
            WHERE c.work_date BETWEEN ? AND ?
            ORDER BY c.work_date, c.start_time
        ");
        $stmt->execute([$dates[0], $dates[6]]);
        $rows = $stmt->fetchAll();

        // 依「上午 / 晚上」與日期重組
        $output = [];

        foreach (['上午', '晚上'] as $slot) {
            $weekData = [];

            // 先把每一天都設為空陣列
            foreach ($dates as $d) {
                $weekData[$d] = [];
            }

            foreach ($rows as $r) {
                if ($r['shift_type'] === $slot) {

                    // ✅ 把 TIME 砍掉秒數，只留 HH:MM
                    $start = substr($r['start_time'], 0, 5);
                    $end   = substr($r['end_time'],   0, 5);

                    $text = "{$r['name']} ({$start}-{$end})";
                    $weekData[$r['work_date']][] = $text;
                }
            }

            // 轉成前端要的格式：days[0..6] = "小王 (..)<br>小美 (..)" 或 "-"
            $days = [];
            foreach ($dates as $d) {
                $names = $weekData[$d];
                $days[] = empty($names) ? '-' : implode('<br>', $names); // ✅ 用 <br> 串起來
            }

            $output[] = [
                'timeSlot' => $slot,
                'days'     => $days
            ];
        }

        error_log("確認班表.php - GET 成功: 週 {$monday} ~ {$sunday}, 共 " . count($rows) . " 筆");

        echo json_encode($output, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("確認班表.php - GET 失敗: " . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => '查詢失敗: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// 其他 HTTP 方法不支援
http_response_code(405);
echo json_encode([
    'success' => false,
    'error'   => '不支援的請求方法'
], JSON_UNESCAPED_UNICODE);
?>
