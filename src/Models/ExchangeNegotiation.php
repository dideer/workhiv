<?php

require_once __DIR__ . '/../Config/Database.php';

class ExchangeNegotiation
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exchange_negotiations
                (request_id, proposed_by, proposed_amount, swap_employee_id, message)
             VALUES
                (:request_id, :proposed_by, :proposed_amount, :swap_employee_id, :message)'
        );
        $stmt->bindValue(':request_id', $data['request_id'], PDO::PARAM_INT);
        $stmt->bindValue(':proposed_by', $data['proposed_by'], PDO::PARAM_INT);
        $stmt->bindValue(':proposed_amount', $data['proposed_amount'] ?? null, $data['proposed_amount'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':swap_employee_id', $data['swap_employee_id'] ?? null, empty($data['swap_employee_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':message', $data['message'] ?? null, trim((string) ($data['message'] ?? '')) === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByRequestId(int $requestId): array
    {
        $stmt = $this->db->prepare(
            'SELECT en.*, c.company_name AS proposed_by_name, swap_employee.full_name AS swap_employee_name
             FROM exchange_negotiations en
             INNER JOIN companies c ON c.company_id = en.proposed_by
             LEFT JOIN users swap_employee ON swap_employee.user_id = en.swap_employee_id
             WHERE en.request_id = :request_id
             ORDER BY en.negotiation_id ASC'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getLatestByRequestId(int $requestId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT en.*, c.company_name AS proposed_by_name, swap_employee.full_name AS swap_employee_name
             FROM exchange_negotiations en
             INNER JOIN companies c ON c.company_id = en.proposed_by
             LEFT JOIN users swap_employee ON swap_employee.user_id = en.swap_employee_id
             WHERE en.request_id = :request_id
             ORDER BY en.created_at DESC, en.negotiation_id DESC
             LIMIT 1'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        $latest = $stmt->fetch();
        return $latest ?: null;
    }
}
