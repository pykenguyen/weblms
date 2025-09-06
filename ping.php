<?php
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

try {
  db()->query('SELECT 1')->fetchColumn();
  json([
    'ok'=>true,
    'db_ok'=>true,
    'session'=>[
      'started'=>session_status()===PHP_SESSION_ACTIVE,
      'user'=>$_SESSION['user']??null
    ]
  ]);
} catch (Throwable $e) {
  json(['ok'=>false,'message'=>'DB error','error'=>DEBUG?$e->getMessage():null], 500);
}
