<?php
declare(strict_types=1);

/** One-time import into an EMPTY MySQL/TiDB database. Never overwrites live data. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$local = is_file($root . '/config/local.php') ? require $root . '/config/local.php' : [];
$setting = static function (string $env, string $key, mixed $default) use ($local): mixed {
    $value = getenv($env);
    return $value !== false && $value !== '' ? $value : ($local[$key] ?? $default);
};
$host = (string)$setting('DB_HOST', 'db_host', '127.0.0.1');
$port = (int)$setting('DB_PORT', 'db_port', 3306);
$name = (string)$setting('DB_NAME', 'db_name', 'fixnear_db');
$user = (string)$setting('DB_USER', 'db_user', 'root');
$password = (string)$setting('DB_PASS', 'db_pass', '');
$ca = (string)$setting('DB_SSL_CA', 'db_ssl_ca', str_ends_with($host, '.tidbcloud.com') ? $root . '/config/certs/isrgrootx1.pem' : '');
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
    throw new RuntimeException('Invalid database name.');
}

$tables = ['users', 'shops', 'services', 'shop_services', 'repair_requests', 'reviews', 'wrong_info_reports', 'contact_messages', 'favorites', 'app_sessions'];
$sources = [
    'users' => 'users.json',
    'shops' => 'shops.json',
    'services' => 'services.json',
    'shop_services' => 'shop_services.json',
    'repair_requests' => 'repair_requests.json',
    'reviews' => 'reviews.json',
    'wrong_info_reports' => 'reports.json',
    'contact_messages' => 'contact_messages.json',
    'favorites' => 'favorites.json',
];
$rows = [];
foreach ($sources as $table => $file) {
    $data = json_decode((string)file_get_contents($root . '/data/' . $file), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) throw new RuntimeException("Invalid source: $file");
    $rows[$table] = $data;
}
if (count($rows['shops']) !== 68 || count(array_unique(array_column($rows['shops'], 'district'))) !== 17) {
    throw new RuntimeException('Catalog does not contain the expected 68 shops in 17 districts.');
}

$schema = (string)file_get_contents($root . '/data/fixnear_db.sql');
preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+`([a-z_]+)`\s*\(.*?\)\s*ENGINE=InnoDB[^;]*;/s', $schema, $matches, PREG_SET_ORDER);
$definitions = [];
foreach ($matches as $match) $definitions[$match[1]] = $match[0];
foreach ($tables as $table) {
    if (!isset($definitions[$table])) throw new RuntimeException("Missing schema for $table");
}

if (in_array('--dry-run', $argv, true)) {
    foreach ($rows as $table => $items) echo "$table: " . count($items) . "\n";
    echo "Schema: " . count($definitions) . " tables; no database changes made.\n";
    exit(0);
}
if (!in_array('--apply', $argv, true)) {
    fwrite(STDERR, "Use --dry-run to inspect or --apply to import into an empty database.\n");
    exit(2);
}
if ($password === '') throw new RuntimeException('DB_PASS is required.');
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 10];
if ($ca !== '') {
    if (!is_readable($ca)) throw new RuntimeException('Database CA certificate is unavailable.');
    $caOption = defined('Pdo\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\Mysql::ATTR_SSL_CA') : constant('PDO::MYSQL_ATTR_SSL_CA');
    $verifyOption = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') ? constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') : constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT');
    $options[$caOption] = $ca;
    $options[$verifyOption] = true;
}
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $password, $options);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");
$existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if ($existing !== []) throw new RuntimeException('Target database is not empty; refusing to overwrite it.');

foreach ($tables as $table) $pdo->exec($definitions[$table]);
foreach ($rows as $table => $items) {
    $columns = array_column($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    foreach ($items as $item) {
        if (!is_array($item)) throw new RuntimeException("Malformed row in $table");
        $item = array_intersect_key($item, array_flip($columns));
        if (!$item) continue;
        foreach ($item as &$value) {
            if (is_array($value)) $value = implode(',', $value);
            if (is_bool($value)) $value = (int)$value;
        }
        unset($value);
        $keys = array_keys($item);
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $keys) . '`) VALUES (' . implode(',', array_fill(0, count($keys), '?')) . ')';
        $pdo->prepare($sql)->execute(array_values($item));
    }
    echo "$table: " . count($items) . " imported\n";
}
echo "Import complete.\n";
