<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 連線設定
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "lamian";

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

// SQL 查詢，JOIN 假別資料表
$sql = "
    SELECT 
        假別.name AS leave_type_name,
        leave_system.start_date,
        leave_system.end_date,
        leave_system.reason,
        leave_system.status
    FROM leave_system
    JOIN 假別 ON leave_system.leave_type_id = 假別.id
    WHERE leave_system.name = '測試員工'
";

$result = $conn->query($sql);
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "type" => $row["leave_type_name"],
            "start" => $row["start_date"],
            "end" => $row["end_date"],
            "reason" => $row["reason"],
            "status" => $row["status"]
        ];
    }
} else {
    // 這裡可以幫你除錯：沒有資料也回傳空陣列
    $data = [];
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($data);
