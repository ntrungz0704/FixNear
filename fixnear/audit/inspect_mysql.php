<?php
require 'fixnear/config/db.php';
$pdo = db()->getPdo();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES IN fixnear_db:\n";
foreach ($tables as $t) {
    echo "--- TABLE: $t ---\n";
    $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }
}
