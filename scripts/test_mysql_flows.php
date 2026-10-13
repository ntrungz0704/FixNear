<?php
declare(strict_types=1);

/** Exercises shared user/admin data on MySQL and rolls every test write back. */
if (PHP_SAPI !== 'cli') exit(1);
require_once dirname(__DIR__) . '/config/db.php';
$db = FixNearDB::getInstance();
if (!$db->isUsingMySQL()) throw new RuntimeException('MySQL is required for this test.');
$pdo = $db->getPdo();
$before = [];
foreach (['users', 'repair_requests', 'reviews', 'wrong_info_reports', 'contact_messages', 'favorites'] as $table) {
    $before[$table] = (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}
$pdo->beginTransaction();
try {
    $email = 'flow-' . bin2hex(random_bytes(6)) . '@example.invalid';
    $user = $db->createUser(['name' => 'Flow Test', 'email' => $email, 'password' => password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]);
    if (!$user || $db->getUserByEmail($email)['role'] !== 'user') throw new RuntimeException('Registration did not persist.');
    $userId = (int)$user['id'];
    $requestId = 'FN-TEST-' . bin2hex(random_bytes(4));
    $db->addRepairRequest(['id' => $requestId, 'user_id' => $userId, 'customer_name' => 'Flow Test', 'customer_email' => $email, 'customer_phone' => '0000000000', 'device_type' => 'phone', 'issue_type' => 'battery', 'symptom' => 'Test', 'status' => 'pending']);
    if (!$db->decidePendingRepairRequest($requestId, 'approve')) throw new RuntimeException('Admin approval failed.');
    if ($db->decidePendingRepairRequest($requestId, 'reject')) throw new RuntimeException('Stale approval was accepted.');
    $request = $pdo->query("SELECT status FROM repair_requests WHERE id = " . $pdo->quote($requestId))->fetchColumn();
    if ($request !== 'contacted') throw new RuntimeException('User request status is not synchronized.');
    $fav = $db->toggleFavorite($userId, 38);
    if (empty($fav['favorited']) || !$db->isFavorite($userId, 38)) throw new RuntimeException('Favorite was not stored.');
    $review = $db->addReview(['shop_id' => 38, 'user_id' => $userId, 'user_name' => 'Flow Test', 'rating' => 5, 'comment' => 'Temporary test review']);
    if (!$review || !$db->toggleReviewVisibility($review['id'])) throw new RuntimeException('Review moderation failed.');
    if (!array_filter($db->getReviewsByShop(38), static fn($item) => (int)$item['id'] === (int)$review['id'])) throw new RuntimeException('Moderated review is not visible.');
    $db->addReport(['shop_id' => 38, 'user_id' => $userId, 'user_name' => 'Flow Test', 'reason' => 'Test', 'details' => 'Temporary test report']);
    $contact = $db->addContactMessage(['name' => 'Flow Test', 'email' => $email, 'subject' => 'Test', 'message' => 'Temporary test contact']);
    if (!$contact || !array_filter($db->getContactMessages(), static fn($item) => (int)$item['id'] === (int)$contact['id'])) throw new RuntimeException('Contact did not reach admin.');
    echo "MySQL user/admin flow checks passed.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
foreach ($before as $table => $count) {
    if ((int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() !== $count) throw new RuntimeException("Test data remained in $table");
}
echo "All test writes rolled back.\n";
