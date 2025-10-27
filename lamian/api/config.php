<?php
// /lamian-ukn/api/config.php
header('Content-Type: application/json; charset=utf-8');

// === DB 連線設定 ===
const DB_HOST = '127.0.0.1';
const DB_NAME = 'lamian';
const DB_USER = 'root';
const DB_PASS = '';

// === 員工 / 打卡表對應（依你的實際資料表） ===
// 你的員工表是中文表名「員工基本資料」，attendance.user_id 對這張表的 id
const EMP_TABLE    = '員工基本資料';
const EMP_PK_COL   = 'id';      // 對 attendance.user_id
const EMP_NAME_COL = 'name';    // 員工姓名
// 打卡機輸入的「員工編號」欄位（預設用同一個 id；如要用 account/ID_card 請改）
const EMP_CODE_COL = 'id';

const ATT_TABLE    = 'attendance'; // 打卡表

// === 小工具 ===
function pdo(){
  static $pdo = null;
  if ($pdo) return $pdo;
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
function ok($data){ echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function err($msg,$code=500,$ext=[]){ http_response_code($code); echo json_encode(['error'=>$msg]+$ext, JSON_UNESCAPED_UNICODE); exit; }
function g($k,$d=null){ return isset($_GET[$k]) ? trim($_GET[$k]) : $d; }

if (!defined('DB_DSN'))  define('DB_DSN',  '...');
if (!defined('DB_USER')) define('DB_USER', '...');
if (!defined('DB_PASS')) define('DB_PASS', '...');

