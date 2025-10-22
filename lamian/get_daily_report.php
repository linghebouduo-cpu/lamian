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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗：' . $e->getMessage()]);
    exit;
}

$dayNames = ['日','一','二','三','四','五','六'];

// 👉 月報查詢 (?month=YYYY-MM)
if (isset($_GET['month'])) {
    try {
        $month = $_GET['month']; // 格式：2025-08

        // 格式檢查
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            echo json_encode(['success' => false, 'message' => '月份格式錯誤，請使用 YYYY-MM']);
            exit;
        }

        // 當月天數
        $daysInMonth = date('t', strtotime($month . '-01'));

        // 生成日期陣列
        $dates = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dates[] = sprintf('%s-%02d', $month, $i);
        }

        // 查詢該月所有日報
        $stmt = $pdo->prepare("
            SELECT report_date, total_income, total_expense
            FROM daily_report
            WHERE DATE_FORMAT(report_date, '%Y-%m') = :month
        ");
        $stmt->execute(['month' => $month]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 建立 map
        $dataMap = [];
        foreach ($rows as $row) {
            $dataMap[$row['report_date']] = [
                'income'  => (int)$row['total_income'],
                'expense' => (int)$row['total_expense']
            ];
        }

        // 組合結果
        $result = [];
        foreach ($dates as $d) {
            $weekday = '星期' . $dayNames[date('w', strtotime($d))];
            $result[] = [
                'report_date'   => $d,
                'weekday'       => $weekday,
                'total_income'  => $dataMap[$d]['income'] ?? 0,
                'total_expense' => $dataMap[$d]['expense'] ?? 0,
            ];
        }

        echo json_encode(['success' => true, 'data' => $result]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 👉 七日報表 (預設)
try {
    // 先取得過去七天日期（含今天）
    $dates = [];
    for ($i = 6; $i >= 0; $i--) {
        $dates[] = date('Y-m-d', strtotime("-{$i} days"));
    }

    // 查詢這七天
    $stmt = $pdo->prepare("
        SELECT report_date, total_income, total_expense
        FROM daily_report
        WHERE report_date BETWEEN :start AND :end
    ");
    $stmt->execute([
        ':start' => $dates[0],
        ':end'   => $dates[6],
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 建立 map
    $dataMap = [];
    foreach ($rows as $row) {
        $dataMap[$row['report_date']] = [
            'income'  => (int)$row['total_income'],
            'expense' => (int)$row['total_expense']
        ];
    }

    // 組合結果
    $result = [];
    foreach ($dates as $d) {
        $weekday = '星期' . $dayNames[date('w', strtotime($d))];
        $result[] = [
            'report_date'   => $d,
            'weekday'       => $weekday,
            'total_income'  => $dataMap[$d]['income'] ?? 0,
            'total_expense' => $dataMap[$d]['expense'] ?? 0,
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
