<?php

require_once __DIR__ . '/../Config/Database.php';

class EmploymentContract
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(int $appId, string $contractText): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO employment_contracts (app_id, contract_text, status)
             VALUES (:app_id, :contract_text, :status)'
        );
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
        $stmt->bindValue(':contract_text', $contractText, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByAppId(int $appId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM employment_contracts WHERE app_id = :app_id LIMIT 1'
        );
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
        $stmt->execute();

        $contract = $stmt->fetch();
        return $contract ?: null;
    }

    public function respond(int $appId, string $decision): bool
    {
        if (!in_array($decision, ['agreed', 'disagreed'], true)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE employment_contracts
             SET status = :status,
                 responded_at = NOW()
             WHERE app_id = :app_id'
        );
        $stmt->bindValue(':status', $decision, PDO::PARAM_STR);
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
