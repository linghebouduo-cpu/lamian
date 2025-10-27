<?php
// 新增一筆庫存異動（入庫/出庫）
// body: { item_id:number, quantity:number(可負數), updated_by:string, when?: 'YYYY-MM-DDTHH:mm' or 'YYYY-MM-DD HH:mm[:ss]' }
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $pdo  = pdo();
  $body = json_decode(file_get_contents('php://input'), true) ?? [];

  $item_id    = isset($body['item_id']) ? (int)$body['item_id'] : 0;
  $quantity   = isset($body['quantity']) ? (int)$body['quantity'] : 0; // 正=入庫 負=出庫
  $updated_by = trim((string)($body['updated_by'] ?? ''));

  if ($item_id <= 0) { http_response_code(422); echo json_encode(['error'=>'item_id required'], JSON_UNESCAPED_UNICODE); exit; }
  if ($quantity === 0) { http_response_code(422); echo json_encode(['error'=>'quantity must be non-zero'], JSON_UNESCAPED_UNICODE); exit; }
  if ($updated_by === '') { http_response_code(422); echo json_encode(['error'=>'updated_by required'], JSON_UNESCAPED_UNICODE); exit; }

  // 轉換時間（給 DATETIME 欄位）
  $when = trim((string)($body['when'] ?? ''));
  if ($when !== '') {
    $when = str_replace('T', ' ', $when);
    if (strlen($when) === 16) $when .= ':00';
    $dt = date('Y-m-d H:i:s', strtotime($when));
  } else {
    $dt = date('Y-m-d H:i:s');
  }

  // 確認品項存在
  $chk = $pdo->prepare("SELECT 1 FROM `庫存商品` WHERE id=:id LIMIT 1");
  $chk->execute([':id'=>$item_id]);
  if (!$chk->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['error'=>'product not found: '.$item_id], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 寫入一筆異動
  $ins = $pdo->prepare("
    INSERT INTO `庫存管理` (item_id, quantity, last_update, updated_by)
    VALUES (:iid, :q, :lu, :u)
  ");
  $ins->execute([':iid'=>$item_id, ':q'=>$quantity, ':lu'=>$dt, ':u'=>$updated_by]);
  $id = (int)$pdo->lastInsertId();

  echo json_encode(['ok'=>true, 'id'=>$id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'inventory_adjust failed','detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
