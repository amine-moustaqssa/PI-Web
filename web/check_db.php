<?php
$env = file_get_contents(__DIR__.'/.env');
preg_match('/DATABASE_URL="mysql:\/\/(.*?):(.*?)@(.*?):(\d+)\/(.*?)\?/', $env, $m);
$user=$m[1]; $pass=$m[2]; $host=$m[3]; $port=$m[4]; $db=$m[5];
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass);
$stmt = $pdo->query("DESCRIBE Disponibilite");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
