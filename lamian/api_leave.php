<?php
// 資料庫連線設定
$servername = "127.0.0.1";
$username = "root";
$password = ""; // XAMPP 預設沒密碼
$dbname = "lamian";

// 連線資料庫
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

// 檢查是否是 POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $leaveType = $_POST["leaveType"];
    $startDate = $_POST["startDate"];
    $endDate = $_POST["endDate"];
    $reason = $_POST["reason"];

    // 上傳檔案處理
    $proofFileName = "";
    if (isset($_FILES["photo"])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $proofFileName = uniqid() . "_" . $_FILES["photo"]["name"];
        move_uploaded_file($_FILES["photo"]["tmp_name"], $uploadDir . $proofFileName);
    }

    // 取得請假天數
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $totalDays = $interval->days + 1;

    // 插入資料
    $sql = "INSERT INTO leave_system 
        (name, leave_type_id, start_date, end_date, total_days, reason, proof, status)
        VALUES 
        ('測試員工', '$leaveType', '$startDate', '$endDate', '$totalDays', '$reason', '$proofFileName', 1)";

    if ($conn->query($sql) === TRUE) {
        echo "請假申請成功！";
    } else {
        echo "錯誤: " . $conn->error;
    }

    $conn->close();

} else {
    echo "請使用 POST 方法送出資料";
}
?>
