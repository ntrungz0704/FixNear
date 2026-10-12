<?php
require_once __DIR__ . '/../config/db.php';

$pdo = FixNearDB::getInstance()->getPdo();
if (!$pdo) {
    echo "MySQL is not enabled or not connected.\n";
    exit(0);
}

$candidatesFile = __DIR__ . '/../data/shop-image-candidates.json';
if (!is_file($candidatesFile)) {
    echo "Candidate file not found: {$candidatesFile}\n";
    exit(1);
}

$candidates = json_decode(file_get_contents($candidatesFile), true);
$stmt = $pdo->prepare("UPDATE shops SET image = :image WHERE id = :id");

$count = 0;
foreach ($candidates as $cand) {
    $img = trim($cand['image_url'] ?? '');
    if ($img !== '') {
        $stmt->execute([
            ':image' => $img,
            ':id' => (int) $cand['shop_id'],
        ]);
        $count++;
    }
}

echo "Successfully updated MySQL `shops` table: {$count} shops.\n";

$query = $pdo->query("SELECT id, name, image FROM shops WHERE image IS NOT NULL AND image != '' LIMIT 5");
echo "Sample updated rows in MySQL:\n";
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo "  - Shop #{$row['id']}: {$row['name']}\n    URL: {$row['image']}\n";
}
