<?php
declare(strict_types=1);

/** Stores PHP sessions in a shared SQL database for serverless PHP workers. */
final class FixNearDbSessionHandler implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo) {}

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare('SELECT payload FROM app_sessions WHERE session_id = ? AND expires_at > ?');
        $stmt->execute([$id, time()]);
        $value = $stmt->fetchColumn();
        if ($value === false) return '';
        return is_resource($value) ? (string)stream_get_contents($value) : (string)$value;
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare('REPLACE INTO app_sessions (session_id, payload, expires_at) VALUES (?, ?, ?)');
        $stmt->bindValue(1, $id, PDO::PARAM_STR);
        $stmt->bindValue(2, $data, PDO::PARAM_LOB);
        $stmt->bindValue(3, time() + max(1440, (int)ini_get('session.gc_maxlifetime')), PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM app_sessions WHERE session_id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM app_sessions WHERE expires_at <= ?');
        if (!$stmt->execute([time()])) return false;
        return $stmt->rowCount();
    }
}
