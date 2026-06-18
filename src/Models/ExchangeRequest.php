<?php

require_once __DIR__ . '/../Config/Database.php';

class ExchangeRequest
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exchange_requests
                (company_a_id, company_b_id, employee_id, exchange_type, offered_amount, swap_employee_id, status, message)
             VALUES
                (:company_a_id, :company_b_id, :employee_id, :exchange_type, :offered_amount, :swap_employee_id, :status, :message)'
        );
        $stmt->bindValue(':company_a_id', $data['company_a_id'], PDO::PARAM_INT);
        $stmt->bindValue(':company_b_id', $data['company_b_id'], PDO::PARAM_INT);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':exchange_type', $data['exchange_type'], PDO::PARAM_STR);
        $stmt->bindValue(':offered_amount', $data['offered_amount'] ?? null, $data['offered_amount'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':swap_employee_id', $data['swap_employee_id'] ?? null, empty($data['swap_employee_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);
        $stmt->bindValue(':message', $data['message'] ?? null, trim((string) ($data['message'] ?? '')) === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getSentByCompany(int $companyId): array
    {
        return $this->listByCompanyColumn('er.company_a_id', $companyId);
    }

    public function getReceivedByCompany(int $companyId): array
    {
        return $this->listByCompanyColumn('er.company_b_id', $companyId);
    }

    public function getById(int $requestId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT er.*,
                    company_a.company_name AS company_a_name,
                    company_b.company_name AS company_b_name,
                    employee.full_name AS employee_name,
                    swap_employee.full_name AS swap_employee_name
             FROM exchange_requests er
             INNER JOIN companies company_a ON company_a.company_id = er.company_a_id
             INNER JOIN companies company_b ON company_b.company_id = er.company_b_id
             INNER JOIN users employee ON employee.user_id = er.employee_id
             LEFT JOIN users swap_employee ON swap_employee.user_id = er.swap_employee_id
             WHERE er.request_id = :request_id
             LIMIT 1'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        $request = $stmt->fetch();
        return $request ?: null;
    }

    public function updateStatus(int $requestId, string $status): bool
    {
        if (!in_array($status, ['negotiating', 'accepted', 'rejected'], true)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE exchange_requests SET status = :status WHERE request_id = :request_id'
        );
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function verifyOwnership(int $requestId, int $companyId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM exchange_requests
             WHERE request_id = :request_id
               AND (company_a_id = :company_id_a OR company_b_id = :company_id_b)
             LIMIT 1'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id_a', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id_b', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    private function listByCompanyColumn(string $column, int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT er.*,
                    company_b.company_name AS company_b_name,
                    company_a.company_name AS company_a_name,
                    employee.full_name AS employee_name,
                    swap_employee.full_name AS swap_employee_name
             FROM exchange_requests er
             INNER JOIN companies company_a ON company_a.company_id = er.company_a_id
             INNER JOIN companies company_b ON company_b.company_id = er.company_b_id
             INNER JOIN users employee ON employee.user_id = er.employee_id
             LEFT JOIN users swap_employee ON swap_employee.user_id = er.swap_employee_id
             WHERE ' . $column . ' = :company_id
             ORDER BY er.request_id DESC'
        );
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
