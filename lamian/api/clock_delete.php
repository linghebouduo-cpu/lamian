<?php
// /lamian-ukn/api/clock_delete.php
require __DIR__.'/config.php';

try {
    $pdo = pdo();

    // 從 GET 或 POST 拿 id
    $id = intval($_GET['id'] ?? ($_POST['id'] ?? 0));
    if($id <= 0){
        err('id 必填', 400);
    }

    // 檢查該打卡紀錄是否存在
    $sqlCheck = "SELECT id FROM `".ATT_TABLE."` WHERE id=:id LIMIT 1";
    $st = $pdo->prepare($sqlCheck);
    $st->execute([':id'=>$id]);
    $row = $st->fetch();
    if(!$row){
        err('找不到該筆打卡紀錄', 404);
    }

    // 執行刪除
    $sqlDel = "DELETE FROM `".ATT_TABLE."` WHERE id=:id";
    $pdo->prepare($sqlDel)->execute([':id'=>$id]);

    ok(['ok'=>true, 'message'=>'刪除成功']);

} catch(Throwable $ex){
    err('刪除失敗', 500, ['detail'=>$ex->getMessage()]);
}
