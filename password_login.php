<?php
// /lamian-ukn/api/password_login.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/config.php';
    
    // 啟動 session
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => false,
            'path' => '/'
        ]);
        session_start();
    }
    
    // 讀取輸入
    $raw_input = file_get_contents('php://input');
    $in = json_decode($raw_input ?: '{}', true) ?: [];
    
    $account  = trim((string)($in['account'] ?? ''));
    $password = (string)($in['password'] ?? '');
    
    if ($account === '' || $password === '') {
        json_err('缺少帳號或密碼', 400);
    }
    
    // 連接資料庫
    $pdo = pdo();
    
    // 用 id 或 ID_card 登入
    $sql = "SELECT `id`, `name`, `password_hash`, `email`, `Position`, `ID_card`, `role`
            FROM `員工基本資料`
            WHERE `id` = ? OR `ID_card` = ?
            LIMIT 1";
    
    $st = $pdo->prepare($sql);
    $st->execute([$account, $account]);
    $u = $st->fetch();
    
    if (!$u) {
        json_err('帳號或密碼錯誤', 401);
    }
    
    // 驗證密碼
    $hash = (string)($u['password_hash'] ?? '');
    
    if (empty($hash)) {
        json_err('密碼未設定，請聯繫管理員', 401);
    }
    
    $ok = false;
    $isHashed = (bool)preg_match('/^\$2[aby]\$|^\$argon2(id)?\$/', $hash);
    
    if ($isHashed) {
        $ok = password_verify($password, $hash);
        
        if ($ok && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `員工基本資料` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$newHash, $u['id']]);
        }
    } else {
        $ok = hash_equals($hash, $password);
        
        if ($ok && $hash !== '') {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `員工基本資料` SET `password_hash` = ? WHERE `id` = ?")
                ->execute([$newHash, $u['id']]);
        }
    }
    
    if (!$ok) {
        json_err('帳號或密碼錯誤', 401);
    }
    
    // 判斷權限等級
    $db_role = strtoupper(trim($u['role'] ?? 'C'));
    
    $role_map = [
        'A' => ['code' => 'boss',     'name' => '老闆',   'level' => 3],
        'B' => ['code' => 'manager',  'name' => '管理員', 'level' => 2],
        'C' => ['code' => 'employee', 'name' => '員工',   'level' => 1],
    ];
    
    $role_info = $role_map[$db_role] ?? $role_map['C'];
    
    // 設定 session
    $_SESSION['uid']        = $u['id'];
    $_SESSION['name']       = $u['name'];
    $_SESSION['email']      = $u['email'];
    $_SESSION['position']   = $u['Position'] ?? '';
    $_SESSION['ID_card']    = $u['ID_card'] ?? '';
    $_SESSION['role']       = $role_info['code'];      // boss/manager/employee
    $_SESSION['role_level'] = $role_info['level'];     // 3/2/1
    $_SESSION['role_name']  = $role_info['name'];      // 老闆/管理員/員工
    $_SESSION['role_code']  = $db_role;                // A/B/C
    $_SESSION['login_at']   = date('Y-m-d H:i:s');
    
    // 回傳成功
    json_ok([
        'ok'   => true,
        'user' => [
            'id'         => $u['id'],
            'name'       => $u['name'],
            'email'      => $u['email'],
            'position'   => $u['Position'] ?? '',
            'ID_card'    => $u['ID_card'] ?? '',
            'role'       => $role_info['code'],
            'role_code'  => $db_role,
            'role_name'  => $role_info['name'],
            'role_level' => $role_info['level'],
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'DATABASE_ERROR',
        'message' => '資料庫錯誤：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Throwable $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'SERVER_ERROR',
        'message' => '伺服器錯誤：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}