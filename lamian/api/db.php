<?php
// /lamian-ukn/api/db.php
declare(strict_types=1);

const DB_DSN  = 'mysql:host=127.0.0.1;port=3306;dbname=lamian;charset=utf8mb4';
// XAMPP 預設：root 帳號、空密碼
const DB_USER = 'root';
const DB_PASS = '';

function pdo(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
