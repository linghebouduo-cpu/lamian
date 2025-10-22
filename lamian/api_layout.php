<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Taipei');
$host = 'localhost';
$dbname = 'lamian';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// 處理查詢
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['date'])) {
    $date = $_GET['date'];
    $startOfWeek = date('Y-m-d', strtotime($date . ' -' . (date('w', strtotime($date)) ? date('w', strtotime($date)) - 1 : 6) . ' days'));
    
    $weekDates = [];
    for ($i = 0; $i < 7; $i++) {
        $weekDates[] = date('Y-m-d', strtotime($startOfWeek . " +$i days"));
    }

    $stmt = $pdo->prepare("SELECT * FROM schedule WHERE work_date IN (" . rtrim(str_repeat('?,', 7), ',') . ") ORDER BY work_date ASC, shift_type ASC");
    $stmt->execute($weekDates);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 初始化班表格式
    $days = ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日'];
    $dateMap = array_combine($weekDates, $days);

    // 時段分為中午、晚上、備註
    $scheduleData = [
        ['timeSlot' => '中午', 'days' => array_fill(0, 7, '')],
        ['timeSlot' => '晚上', 'days' => array_fill(0, 7, '')],
        ['timeSlot' => '備註', 'days' => array_fill(0, 7, '')],
    ];

    // 整理資料進表格
    foreach ($results as $row) {
        $index = array_search($row['work_date'], $weekDates);
        $user = $row['user_id'];
        $shift = $row['start_time'] . '-' . $row['end_time'];

        if ($row['shift_type'] === '午') {
            $scheduleData[0]['days'][$index] = "$user<br>$shift";
        } elseif ($row['shift_type'] === '晚') {
            $scheduleData[1]['days'][$index] = "$user<br>$shift";
        }

        if (!empty($row['note'])) {
            $scheduleData[2]['days'][$index] = $row['note'];
        }
    }

    echo json_encode($scheduleData);
    exit;
}

// 處理儲存（簡單範例，需前端支援 fetch POST 才會觸發）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // 假設傳入格式為陣列，每筆含 user_id, work_date, start_time, end_time, shift_type, note
    foreach ($data as $item) {
        $stmt = $pdo->prepare("INSERT INTO schedule (user_id, work_date, start_time, end_time, shift_type, note)
            VALUES (:user_id, :work_date, :start_time, :end_time, :shift_type, :note)
            ON DUPLICATE KEY UPDATE start_time = :start_time, end_time = :end_time, note = :note");
        $stmt->execute([
            ':user_id' => $item['user_id'],
            ':work_date' => $item['work_date'],
            ':start_time' => $item['start_time'],
            ':end_time' => $item['end_time'],
            ':shift_type' => $item['shift_type'],
            ':note' => $item['note']
        ]);
    }

    echo json_encode(['status' => 'success']);
    exit;
}
?>
