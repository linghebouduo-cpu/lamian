<?php
declare(strict_types=1);
require_once __DIR__ . '/config_auth.php';

function pdo_auth(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(AUTH_DB_DSN, AUTH_DB_USER, AUTH_DB_PASS, [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
  }
  return $pdo;
}
