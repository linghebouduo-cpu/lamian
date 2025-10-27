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

// GET 員工資料
if ($method === 'GET') {
    $keyword = $_GET['keyword'] ?? '';
    $field = $_GET['searchField'] ?? 'name';
    $allowedFields = ['name', 'id'];
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

// POST：修改
if ($method === 'POST' && $_GET['action'] === 'edit'){
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $birth_date = $data['birth_date'] ?? '';
    $role = $data['role'] ?? '';
    $position = $data['Position'] ?? '';
    $base_salary = $data['base_salary'] ?? null;
    $hourly_rate = $data['hourly_rate'] ?? null;
    $telephone = $data['Telephone'] ?? '';
    $address = $data['address'] ?? '';
    $id_card = $data['ID_card'] ?? '';
    $password_hash = $data['password_hash'] ?? '';

    if (!$id || !$name || !$birth_date || !$role || !$position || !$telephone || !$address || !$id_card) {
        echo json_encode(['success' => false, 'message' => '資料不完整']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        echo json_encode(['success' => false, 'message' => '出生年月日格式錯誤']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z][0-9]{9}$/', $id_card)) {
        echo json_encode(['success' => false, 'message' => '身分證格式錯誤']);
        exit;
    }

    if (!preg_match('/^09\d{8}$/', $telephone)) {
        echo json_encode(['success' => false, 'message' => '電話格式錯誤']);
        exit;
    }

    if ($role === '正職') {
        $hourly_rate = $hourly_rate !== null ? $hourly_rate : null;
        $base_salary = $base_salary ?? 0;
    } elseif ($role === '臨時員工') {
        $base_salary = $base_salary !== null ? $base_salary : null;
        $hourly_rate = $hourly_rate ?? 0;
    }

    try {
        $sql = "UPDATE `員工基本資料` 
                SET name = ?, birth_date = ?, role = ?, Position = ?, base_salary = ?, hourly_rate = ?, Telephone = ?, address = ?, id_card = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name, $birth_date, $role, $position,
            $base_salary ?: null, $hourly_rate ?: null,
            $telephone, $address, $id_card, $id
        ]);

        if (!empty($password_hash)) {
            $hashedpassword_hash = password_hash($password_hash, PASSWORD_DEFAULT);
            $pwStmt = $pdo->prepare("UPDATE `員工基本資料` SET password_hash = ? WHERE id = ?");
            $pwStmt->execute([$hashedpassword_hash, $id]);
        }

        echo json_encode(['success' => true, 'message' => '修改成功']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '修改失敗：' . $e->getMessage()]);
    }
    exit;
}

// POST：新增
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $birth_date = $data['birth_date'] ?? '';
    $role = $data['role'] ?? '';
    $position = $data['Position'] ?? '';
    $base_salary = $data['base_salary'] ?? null;
    $hourly_rate = $data['hourly_rate'] ?? null;
    $telephone = $data['Telephone'] ?? '';
    $address = $data['address'] ?? '';
    $id_card = $data['ID_card'] ?? '';

    if (!$name || !$birth_date || !$role || !$position || !$telephone || !$address || !$id_card) {
        echo json_encode(['success' => false, 'message' => '資料不完整']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        echo json_encode(['success' => false, 'message' => '出生年月日格式錯誤']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z][0-9]{9}$/', $id_card)) {
        echo json_encode(['success' => false, 'message' => '身分證格式錯誤']);
        exit;
    }

    if (!preg_match('/^09\d{8}$/', $telephone)) {
        echo json_encode(['success' => false, 'message' => '電話格式錯誤']);
        exit;
    }

    if ($role === '正職') $hourly_rate = null;
    elseif ($role === '臨時員工') $base_salary = null;

    try {
        $sql = "INSERT INTO `員工基本資料` 
                (name, birth_date, role, Position, base_salary, hourly_rate, Telephone, address, id_card)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name, $birth_date, $role, $position,
            $base_salary ?: null, $hourly_rate ?: null,
            $telephone, $address, $id_card
        ]);

        $employee_id = $pdo->lastInsertId();
        $account = "staff@" . $employee_id;
        $password_hash = password_hash($id_card, PASSWORD_DEFAULT);

        $updateSql = "UPDATE `員工基本資料` SET account = ?, password_hash = ? WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$account, $password_hash, $employee_id]);

        echo json_encode(['success' => true, 'message' => '新增成功']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '新增失敗：' . $e->getMessage()]);
    }
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => '缺少ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `員工基本資料` WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => '刪除成功']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '刪除失敗：' . $e->getMessage()]);
    }
    exit;
}

// 若無法匹配 method
echo json_encode(['success' => false, 'message' => '不支援的請求方式']);
exit;
