<?php
// /lamian-ukn/test_session.php
session_start();

echo "<h2>Session 資料檢查</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (empty($_SESSION)) {
    echo "<p style='color: red;'>❌ Session 是空的！沒有登入資料。</p>";
} else {
    echo "<p style='color: green;'>✅ Session 有資料</p>";
    
    if (isset($_SESSION['uid'])) {
        echo "<h3>使用者資訊：</h3>";
        echo "員工 ID: " . ($_SESSION['uid'] ?? '無') . "<br>";
        echo "姓名: " . ($_SESSION['name'] ?? '無') . "<br>";
        echo "Email: " . ($_SESSION['email'] ?? '無') . "<br>";
        echo "權限代碼: " . ($_SESSION['role_code'] ?? '無') . "<br>";
        echo "權限名稱: " . ($_SESSION['role_name'] ?? '無') . "<br>";
        echo "權限等級: " . ($_SESSION['role_level'] ?? '無') . "<br>";
    } else {
        echo "<p style='color: red;'>❌ 沒有 uid，未正確登入</p>";
    }
}

echo "<hr>";
echo "<a href='login.php'>回到登入頁</a> | ";
echo "<a href='index.php'>回到首頁</a>";
?>