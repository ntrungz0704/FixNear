<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/db_session_handler.php';

$pdo = FixNearDB::getInstance()->getPdo();
if (!$pdo) throw new RuntimeException('MySQL is required for this test.');
$handler = new FixNearDbSessionHandler($pdo);
$sessionId = 'qa-' . bin2hex(random_bytes(12));
if (!$handler->open('', 'fixnear_session')) throw new RuntimeException('Session open failed');
try {
    if ($handler->read($sessionId) !== '') throw new RuntimeException('Empty session should be blank');
    if (!$handler->write($sessionId, 'user_id|i:2;')) throw new RuntimeException('Session write failed');
    if ($handler->read($sessionId) !== 'user_id|i:2;') throw new RuntimeException('Session read failed');
    if (!$handler->destroy($sessionId) || $handler->read($sessionId) !== '') throw new RuntimeException('Session destroy failed');
    echo "Database session handler checks passed.\n";
} finally {
    $handler->destroy($sessionId);
}
