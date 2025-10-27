<?php  
// /lamian-ukn/api/clock_save.php
require __DIR__.'/config.php';

try{
    $pdo = pdo();

    // 先讀 POST JSON，再 fallback GET 參數
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = intval($body['id'] ?? $_GET['id'] ?? 0);
    $action = strtolower(trim($body['action'] ?? $_GET['action'] ?? ''));
    $note   = trim($body['note'] ?? $_GET['note'] ?? '');

    if($id <= 0){ err('員工id必填', 400); }
    if(!in_array($action, ['in','out'], true)){ err('action must be in|out', 400); }

    // 找員工
    $sqlEmp = "SELECT `".EMP_PK_COL."` AS id, `".EMP_NAME_COL."` AS name
               FROM `".EMP_TABLE."` 
               WHERE `".EMP_PK_COL."` = :id LIMIT 1";
    $st = $pdo->prepare($sqlEmp);
    $st->execute([':id'=>$id]);
    $emp = $st->fetch();
    if(!$emp){ err('找不到此員工id', 404, ['id'=>$id]); }

    // 查有無未下班紀錄
    $sqlOpen = "SELECT * FROM `".ATT_TABLE."`
                WHERE user_id=:uid AND clock_out IS NULL
                ORDER BY clock_in DESC LIMIT 1";
    $st2 = $pdo->prepare($sqlOpen);
    $st2->execute([':uid'=>$emp['id']]);
    $open = $st2->fetch();

    if($action==='in'){
        if($open){ err('已有未下班紀錄，請先下班', 409); }
        // 插入上班打卡
        $sqlIns = "INSERT INTO `".ATT_TABLE."` (user_id, clock_in, note)
                   VALUES (:uid, NOW(), :note)";
        $pdo->prepare($sqlIns)->execute([':uid'=>$emp['id'], ':note'=>$note]);
        ok(['ok'=>true,'message'=>'上班打卡成功','emp'=>$emp]);

    } else { // 下班
        if(!$open){ err('找不到未下班紀錄，請先上班', 409); }
        $sqlUpd = "UPDATE `".ATT_TABLE."`
                   SET clock_out=NOW(),
                       hours = ROUND(TIMESTAMPDIFF(MINUTE, clock_in, NOW())/60, 2),
                       status = CASE
                         WHEN TIMESTAMPDIFF(MINUTE, clock_in, NOW()) > 480 THEN '加班'
                         ELSE '正常'
                       END,
                       note = COALESCE(NULLIF(:note,''), note)
                   WHERE id=:id";
        $pdo->prepare($sqlUpd)->execute([':note'=>$note, ':id'=>$open['id']]);
        ok(['ok'=>true,'message'=>'下班打卡成功','emp'=>$emp]);
    }

}catch(Throwable $ex){
    err('save failed', 500, ['detail'=>$ex->getMessage()]);
}
