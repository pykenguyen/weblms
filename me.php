<?php
declare(strict_types=1);
require __DIR__ . '/api/config.php';
require __DIR__ . '/api/helpers.php';

$u = current_user();
if (!$u) json(['message'=>'Unauthorized'], 401);
json(['user'=>$u]);
