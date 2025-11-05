<?php 
header('Content-Type: application/json; charset=utf-8');
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
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ==================== GET 員工資料 ====================
if ($method === 'GET') {
    $keyword = $_GET['keyword'] ?? '';
    $field = $_GET['searchField'] ?? 'name';
    $allowedFields = ['name', 'id', 'email'];
    if (!in_array($field, $allowedFields)) $field = 'name';

    try {
        if ($keyword) {
            $sql = "SELECT id, name, birth_date, position, role, base_salary, hourly_rate, 
                           telephone, email, address, id_card, password_hash, avatar_url, 
                           emergency_contact, emergency_phone, memo
                    FROM `員工基本資料` 
                    WHERE {$field} LIKE ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["%$keyword%"]);
        } else {
            $sql = "SELECT id, name, birth_date, position, role, base_salary, hourly_rate, 
                           telephone, email, address, id_card, password_hash, avatar_url, 
                           emergency_contact, emergency_phone, memo
                    FROM `員工基本資料`";
            $stmt = $pdo->query($sql);
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 統一欄位名稱 - 同時提供大小寫版本以相容前端
        $data = array_map(function($row) {
            $row['Position'] = $row['position'];
            $row['Telephone'] = $row['telephone'];
            $row['ID_card'] = $row['id_card'];
            return $row;
        }, $data);
        
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '查詢失敗：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ==================== PUT：修改員工 ====================
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'JSON 解析失敗'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 兼容大小寫欄位
    $id          = $data['id'] ?? null;
    $name        = trim($data['name'] ?? '');
    $birth_date  = trim($data['birth_date'] ?? '');
    $role        = trim($data['role'] ?? '');
    $position    = trim($data['position'] ?? $data['Position'] ?? '');
    $base_salary = isset($data['base_salary']) ? floatval($data['base_salary']) : null;
    $hourly_rate = isset($data['hourly_rate']) ? floatval($data['hourly_rate']) : null;
    $telephone   = trim($data['telephone'] ?? $data['Telephone'] ?? '');
    $email       = trim($data['email'] ?? '');
    $address     = trim($data['address'] ?? '');
    $id_card     = strtoupper(trim($data['id_card'] ?? $data['ID_card'] ?? ''));
    $password    = $data['password'] ?? '';

    // 檢查必填欄位
    $missing = [];
    foreach (['id'=>$id,'name'=>$name,'birth_date'=>$birth_date,'role'=>$role,'position'=>$position,'telephone'=>$telephone,'address'=>$address,'id_card'=>$id_card] as $key => $value) {
        if (empty($value)) $missing[] = $key;
    }
    if ($missing) {
        echo json_encode(['success' => false, 'message' => '資料不完整，缺少: ' . implode(', ', $missing)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Email格式驗證
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email格式不正確'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 檢查email是否被其他員工使用
    if (!empty($email)) {
        $checkEmailStmt = $pdo->prepare("SELECT id FROM `員工基本資料` WHERE email=? AND id!=?");
        $checkEmailStmt->execute([$email, $id]);
        if ($checkEmailStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email已被其他員工使用'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 格式驗證
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        echo json_encode(['success' => false, 'message' => '出生年月日格式錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    list($year, $month, $day) = explode('-', $birth_date);
    if (!checkdate($month, $day, $year)) {
        echo json_encode(['success' => false, 'message' => '日期無效'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!validateIdCard($id_card)) {
        echo json_encode(['success' => false, 'message' => '身分證格式或檢查碼錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!preg_match('/^09\d{8}$/', $telephone)) {
        echo json_encode(['success' => false, 'message' => '電話格式錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 薪資處理
    if ($role === '正職') {
        $hourly_rate = null;
        if (!$base_salary) $base_salary = 0;
    } elseif ($role === '臨時員工') {
        $base_salary = null;
        if (!$hourly_rate) $hourly_rate = 0;
    }

    try {
        // 更新SQL - 使用小寫欄位名稱
        $sql = "UPDATE `員工基本資料` 
                SET name=?, birth_date=?, role=?, position=?, base_salary=?, hourly_rate=?, 
                    telephone=?, email=?, address=?, id_card=?
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name, $birth_date, $role, $position, $base_salary, $hourly_rate, 
            $telephone, $email, $address, $id_card, $id
        ]);

        // 更新密碼
        if (!empty($password)) {
            $pwHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `員工基本資料` SET password_hash=? WHERE id=?")->execute([$pwHash,$id]);
        }

        echo json_encode(['success' => true, 'message' => '修改成功'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '修改失敗：'.$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ==================== POST：新增員工 ====================
if ($method === 'POST') {
    // 先嘗試讀 JSON
    $data = json_decode(file_get_contents('php://input'), true);

    // 如果 JSON 解析失敗或沒有資料，就從 $_POST 取
    if (!$data || !is_array($data)) {
        $data = $_POST;
    }

    // 兼容大小寫欄位
    $name        = trim($data['name'] ?? '');
    $birth_date  = trim($data['birth_date'] ?? '');
    $role        = trim($data['role'] ?? '');
    $position    = trim($data['position'] ?? $data['Position'] ?? '');
    $base_salary = isset($data['base_salary']) ? floatval($data['base_salary']) : null;
    $hourly_rate = isset($data['hourly_rate']) ? floatval($data['hourly_rate']) : null;
    $telephone   = trim($data['telephone'] ?? $data['Telephone'] ?? '');
    $email       = trim($data['email'] ?? '');
    $address     = trim($data['address'] ?? '');
    $id_card     = strtoupper(trim($data['id_card'] ?? $data['ID_card'] ?? ''));

    // 檢查必填欄位
    if (!$name || !$birth_date || !$role || !$position || !$telephone || !$address || !$id_card) {
        echo json_encode(['success' => false, 'message' => '資料不完整'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Email格式驗證
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email格式不正確'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 格式驗證
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        echo json_encode(['success' => false, 'message' => '出生年月日格式錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    list($year, $month, $day) = explode('-', $birth_date);
    if (!checkdate($month, $day, $year)) {
        echo json_encode(['success' => false, 'message' => '日期無效'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!validateIdCard($id_card)) {
        echo json_encode(['success' => false, 'message' => '身分證格式或檢查碼錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^09\d{8}$/', $telephone)) {
        echo json_encode(['success' => false, 'message' => '電話格式錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 薪資欄位檢查
    if ($role === '正職') {
        $hourly_rate = null;
        if (!$base_salary || $base_salary <= 0) {
            echo json_encode(['success' => false, 'message' => '正職員工須填寫有效的底薪'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } elseif ($role === '臨時員工') {
        $base_salary = null;
        if (!$hourly_rate || $hourly_rate <= 0) {
            echo json_encode(['success' => false, 'message' => '臨時員工須填寫有效的時薪'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => '角色選擇錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // 檢查身分證是否已存在
        $checkStmt = $pdo->prepare("SELECT id FROM `員工基本資料` WHERE id_card=?");
        $checkStmt->execute([$id_card]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '身分證號碼已存在'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 檢查email是否已存在
        if (!empty($email)) {
            $checkEmailStmt = $pdo->prepare("SELECT id FROM `員工基本資料` WHERE email=?");
            $checkEmailStmt->execute([$email]);
            if ($checkEmailStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email已被使用'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // 新增員工 - 使用小寫欄位名稱
        $stmt = $pdo->prepare("INSERT INTO `員工基本資料` 
            (name, birth_date, role, position, base_salary, hourly_rate, telephone, email, address, id_card)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $birth_date, $role, $position, $base_salary, $hourly_rate, 
            $telephone, $email, $address, $id_card
        ]);

        $employee_id = $pdo->lastInsertId();

        // 生成帳號與密碼 (預設密碼為身分證號碼)
        $password_hash = password_hash($id_card, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE `員工基本資料` SET password_hash=? WHERE id=?")
            ->execute([$password_hash, $employee_id]);

        echo json_encode([
            'success' => true,
            'message' => '新增成功',
            'data' => [
                'employee_id' => $employee_id
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => '新增失敗：'.$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ==================== DELETE：刪除員工 ====================
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => '缺少ID'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `員工基本資料` WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => '刪除成功'], JSON_UNESCAPED_UNICODE);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => '刪除失敗：'.$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ==================== 不支持的請求方式 ====================
echo json_encode(['success' => false, 'message' => '不支持的請求方式'], JSON_UNESCAPED_UNICODE);
exit;

// ==================== 台灣身分證驗證函式 ====================
function validateIdCard($id){
    if(!preg_match('/^[A-Z][12]\d{8}$/',$id)) return false;
    $letters = [
        'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,
        'I'=>34,'J'=>18,'K'=>19,'L'=>20,'M'=>21,'N'=>22,'O'=>35,'P'=>23,
        'Q'=>24,'R'=>25,'S'=>26,'T'=>27,'U'=>28,'V'=>29,'W'=>32,'X'=>30,
        'Y'=>31,'Z'=>33
    ];
    $first_value = $letters[$id[0]];
    $sum = intval($first_value/10) + ($first_value%10)*9;
    for($i=1; $i<=8; $i++) {
        $sum += intval($id[$i]) * (9-$i);
    }
    $check_digit = (10 - ($sum%10)) % 10;
    return $check_digit == intval($id[9]);
}
?>