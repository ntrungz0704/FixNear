<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$pdo = FixNearDB::getInstance()->getPdo();
if (!$pdo) {
    fwrite(STDERR, "MySQL is not enabled or connection failed.\n");
    exit(1);
}

$mysqlRows = $pdo->query("SELECT * FROM shops ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$jsonFile = __DIR__ . '/../data/shops.json';
$shops = json_decode(file_get_contents($jsonFile), true);

$mysqlMap = [];
foreach ($mysqlRows as $row) {
    $mysqlMap[(int)$row['id']] = $row;
}

$updatedCount = 0;
foreach ($shops as &$shop) {
    $id = (int)($shop['id'] ?? 0);
    if (isset($mysqlMap[$id])) {
        $my = $mysqlMap[$id];
        $myImg = trim((string)($my['image'] ?? ''));
        $curImg = trim((string)($shop['image'] ?? ''));
        if ($myImg !== $curImg) {
            $shop['image'] = $myImg;
            $updatedCount++;
        }
        $myPhone = trim((string)($my['phone'] ?? ''));
        if ($myPhone !== '' && $myPhone !== trim((string)($shop['phone'] ?? ''))) {
            $shop['phone'] = $myPhone;
        }
    }
}
unset($shop);

file_put_contents(
    $jsonFile,
    json_encode($shops, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);
echo "Successfully synced {$updatedCount} updated shop images to data/shops.json\n";

// Update data/fixnear_db.sql
$sqlFile = __DIR__ . '/../data/fixnear_db.sql';
$sqlLines = explode("\n", file_get_contents($sqlFile));
$updatedSqlLines = 0;

foreach ($sqlLines as &$line) {
    if (preg_match('/^\((\d+),\s*\'([^\']+)\',\s*\'([^\']+)\'/', $line, $matches)) {
        $id = (int)$matches[1];
        if (isset($mysqlMap[$id])) {
            $my = $mysqlMap[$id];
            $newImg = $my['image'] ?? '';
            // Match the image field in SQL insert row: 18th column
            // Format: (id, 'name', 'address', 'ward', 'district', 'phone', 'opening_hours', 'map_url', lat, lng, 'desc', rating, count, 'devices', is_verified, onsite, signing, 'discount', 'image', ...)
            $pattern = '/(\'(?:[^\'\\\\]|\\\\.)*\')(?=,\s*\'(?:https?:\/\/[^\']*|)\',\s*\'(?:20\d\d-[^\']*)\')/';
            if ($newImg !== '') {
                $escapedImg = str_replace("'", "\\'", $newImg);
                $newLine = preg_replace(
                    '/,\s*\'(?:https?:\/\/[^\']*|)\',\s*(\'(?:https?:\/\/[^\']*|)\',\s*\'20\d\d-)/',
                    ", '{$escapedImg}', $1",
                    $line
                );
                if ($newLine && $newLine !== $line) {
                    $line = $newLine;
                    $updatedSqlLines++;
                }
            }
        }
    }
}
unset($line);

file_put_contents($sqlFile, implode("\n", $sqlLines));
echo "Successfully synced {$updatedSqlLines} rows in data/fixnear_db.sql\n";
