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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫連線失敗：' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ==================== GET 員工資料 ====================
if ($method === 'GET') {
    $keyword = $_GET['keyword'] ?? '';
    $field = $_GET['searchField'] ?? 'name';
    $allowedFields = ['name', 'id', 'email']; // 新增 email 可搜尋
    if (!in_array($field, $allowedFields)) $field = 'name';

    if ($keyword) {
        $sql = "SELECT *, CONCAT('staff@', id) AS account FROM `員工基本資料` WHERE {$field} LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$keyword%"]);
    } else {
        $stmt = $pdo->query("SELECT *, CONCAT('staff@', id) AS account FROM `員工基本資料`");
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ==================== PUT：修改員工 ====================
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data === null) {
        echo json_encode(['success' => false, 'message' => 'JSON 解析失敗']);
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
    $email       = trim($data['email'] ?? ''); // 新增 email
    $address     = trim($data['address'] ?? '');
    $id_card     = strtoupper(trim($data['id_card'] ?? $data['ID_card'] ?? ''));
    $password    = $data['password'] ?? '';

    // 檢查必填欄位
    $missing = [];
    foreach (['id'=>'id','name'=>'name','birth_date'=>'birth_date','role'=>'role','position'=>'position','telephone'=>'telephone','address'=>'address','id_card'=>'id_card'] as $var => $key) {
        if (empty($$var)) $missing[] = $key;
    }
    if ($missing) {
        echo json_encode(['success' => false, 'message' => '資料不完整，缺少: ' . implode(', ', $missing)]);
        exit;
    }

    // Email格式驗證 (選填,但若填寫則需驗證)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email格式不正確']);
        exit;
    }

    // 檢查email是否被其他員工使用
    if (!empty($email)) {
        $checkEmailStmt = $pdo->prepare("SELECT id FROM `員工基本資料` WHERE email=? AND id!=?");
        $checkEmailStmt->execute([$email, $id]);
        if ($checkEmailStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email已被其他員工使用']);
            exit;
        }
    }

    // 格式驗證
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        echo json_encode(['success' => false, 'message' => '出生年月日格式錯誤']);
        exit;
    }
    list($year, $month, $day) = explode('-', $birth_date);
    if (!checkdate($month, $day, $year)) {
        echo json_encode(['success' => false, 'message' => '日期無效']);
        exit;
    }
    
    if (!validateIdCard($id_card)) {
        echo json_encode(['success' => false, 'message' => '身分證格式或檢查碼錯誤']);
        exit;
    }
    
    if (!preg_match('/^09\d{8}$/', $telephone)) {
        echo json_encode(['success' => false, 'message' => '電話格式錯誤']);
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
        // 更新SQL - 加入email欄位
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

        echo json_encode(['success' => true, 'message' => '修改成功']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '修改失敗：'.$e->getMessage()]);
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
    $email       = trim($data['email'] ?? ''); // 新增 email
    $address     = trim($data['address'] ?? '');
    $id_card     = strtoupper(trim($data['id_card'] ?? $data['ID_card'] ?? ''));

    // 檢查必填欄位
    if (!$name || !$birth_date || !$role || !$position || !$telephone || !$address || !$id_card) {
        echo json_encode(['success' => false, 'message' => '資料不完整']);
        exit;
    }

    // Email格式驗證 (選填,但若填寫則需驗證)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email格式不正確']);
        exit;
    }

    // 格式驗證
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        echo json_encode(['success' => false, 'message' => '出生年月日格式錯誤']);
        exit;
    }
    list($year, $month, $day) = explode('-', $birth_date);
    if (!checkdate($month, $day, $year)) {
        echo json_encode(['success' => false, 'message' => '日期無效']);
        exit;
    }

    if (!validateIdCard($id_card)) {
        echo json_encode(['success' => false, 'message' => '身分證格式或檢查碼錯誤']);
        exit;
    }

    if (!preg_match('/^09\d{8}$/', $telephone)) {
        echo json_encode(['success' => false, 'message' => '電話格式錯誤']);
        exit;
    }

    // 薪資欄位檢查
    if ($role === '正職') {
        $hourly_rate = null;
        if (!$base_salary || $base_salary <= 0) {
            echo json_encode(['success' => false, 'message' => '正職員工須填寫有效的底薪']);
            exit;
        }
    } elseif ($role === '臨時員工') {
        $base_salary = null;
        if (!$hourly_rate || $hourly_rate <= 0) {
            echo json_encode(['success' => false, 'message' => '臨時員工須填寫有效的時薪']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => '角色選擇錯誤']);
        exit;
    }

    try {
        // 檢查身分證是否已存在
        $checkStmt = $pdo->prepare("SELECT id FROM `員工基本資料` WHERE id_card=?");
        $checkStmt->execute([$id_card]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '身分證號碼已存在']);
            exit;
        }

        // 檢查email是否已存在 (若有填寫email)
        if (!empty($email)) {
            $checkEmailStmt = $pdo->prepare("SELECT id FROM `員工基本資料` WHERE email=?");
            $checkEmailStmt->execute([$email]);
            if ($checkEmailStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email已被使用']);
                exit;
            }
        }

        // 新增員工 - 加入email欄位
        $stmt = $pdo->prepare("INSERT INTO `員工基本資料` 
            (name, birth_date, role, position, base_salary, hourly_rate, telephone, email, address, id_card, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $name, $birth_date, $role, $position, $base_salary, $hourly_rate, 
            $telephone, $email, $address, $id_card
        ]);

        $employee_id = $pdo->lastInsertId();

        // 生成帳號與密碼
        $account = "staff".str_pad($employee_id,4,'0',STR_PAD_LEFT);
        $password_hash = password_hash($id_card, PASSWORD_DEFAULT);

        $pdo->prepare("UPDATE `員工基本資料` SET account=?, password_hash=? WHERE id=?")
            ->execute([$account,$password_hash,$employee_id]);

        echo json_encode([
            'success' => true,
            'message' => '新增成功',
            'data' => [
                'employee_id' => $employee_id,
                'account' => $account
            ]
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => '新增失敗：'.$e->getMessage()]);
    }
    exit;
}

// ==================== DELETE：刪除員工 ====================
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => '缺少ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `員工基本資料` WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => '刪除成功']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => '刪除失敗：'.$e->getMessage()]);
    }
    exit;
}

// ==================== 不支援的請求方式 ====================
echo json_encode(['success' => false, 'message' => '不支援的請求方式']);
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