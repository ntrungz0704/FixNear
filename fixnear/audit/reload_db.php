<?php
require 'fixnear/config/app.php';

echo "Dang ket noi MySQL...\n";
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
$pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$sqlFile = dirname(__DIR__) . '/fixnear_db.sql';
echo "Dang doc file SQL tu: $sqlFile ...\n";
$sql = file_get_contents($sqlFile);

echo "Dang thuc thi tao va nap lai database fixnear_db...\n";
$pdo->exec($sql);
echo "THANH CONG! Da nap hoan tat fixnear_db.sql vao MySQL.\n";
