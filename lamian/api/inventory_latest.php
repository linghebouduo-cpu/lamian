<?php
// 取最近異動清單（預設 20 筆）
// 回傳欄位：id, item_id, name, category, quantity, unit, updated_by, last_update, last_update_iso
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

try {
  $pdo   = pdo();
  $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 20;

  $sql = "
    SELECT
      i.id,
      i.item_id,
      i.quantity,
      i.last_update,
      i.updated_by,
      p.name,
      p.unit,
      c.name AS category
    FROM `庫存管理` AS i
    LEFT JOIN `庫存商品` AS p ON p.id = i.item_id
    LEFT JOIN `商品分類` AS c ON c.id = p.category_id
    ORDER BY i.id DESC
    LIMIT :lim
  ";
  $st = $pdo->prepare($sql);
  $st->bindValue(':lim', $limit, PDO::PARAM_INT);
  $st->execute();

  $rows = [];
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $lu = $r['last_update'];
    $r['last_update_iso'] = $lu ? (date('Y-m-d H:i:s', strtotime($lu))) : null;
    $rows[] = $r;
  }

  echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'inventory_latest failed','detail'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
