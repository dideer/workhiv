<?php

require_once __DIR__ . '/../Config/Database.php';

class ExchangeEmployeeContract
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(int $requestId, int $employeeId, int $newCompanyId, string $contractText): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exchange_employee_contracts
                (request_id, employee_id, new_company_id, contract_text, status)
             VALUES
                (:request_id, :employee_id, :new_company_id, :contract_text, :status)'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':new_company_id', $newCompanyId, PDO::PARAM_INT);
        $stmt->bindValue(':contract_text', $contractText, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByRequestAndEmployee(int $requestId, int $employeeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT eec.*, employee.full_name AS employee_name, new_company.company_name AS new_company_name
             FROM exchange_employee_contracts eec
             INNER JOIN users employee ON employee.user_id = eec.employee_id
             INNER JOIN companies new_company ON new_company.company_id = eec.new_company_id
             WHERE eec.request_id = :request_id
               AND eec.employee_id = :employee_id
             LIMIT 1'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $contract = $stmt->fetch();
        return $contract ?: null;
    }

    public function getPendingForEmployee(int $employeeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT eec.*,
                    employee.full_name AS employee_name,
                    new_company.company_name AS new_company_name,
                    er.company_a_id,
                    er.company_b_id,
                    er.employee_id AS request_employee_id,
                    company_a.company_name AS company_a_name,
                    company_b.company_name AS company_b_name
             FROM exchange_employee_contracts eec
             INNER JOIN exchange_requests er ON er.request_id = eec.request_id
             INNER JOIN users employee ON employee.user_id = eec.employee_id
             INNER JOIN companies new_company ON new_company.company_id = eec.new_company_id
             INNER JOIN companies company_a ON company_a.company_id = er.company_a_id
             INNER JOIN companies company_b ON company_b.company_id = er.company_b_id
             WHERE eec.employee_id = :employee_id
               AND eec.status = :status
             ORDER BY eec.generated_at DESC, eec.contract_id DESC
             LIMIT 1'
        );
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        $contract = $stmt->fetch();
        return $contract ?: null;
    }

    public function getAllForRequest(int $requestId): array
    {
        $stmt = $this->db->prepare(
            'SELECT eec.*, employee.full_name AS employee_name, new_company.company_name AS new_company_name
             FROM exchange_employee_contracts eec
             INNER JOIN users employee ON employee.user_id = eec.employee_id
             INNER JOIN companies new_company ON new_company.company_id = eec.new_company_id
             WHERE eec.request_id = :request_id
             ORDER BY eec.contract_id ASC'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function respond(int $contractId, string $decision): bool
    {
        if (!in_array($decision, ['agreed', 'disagreed'], true)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE exchange_employee_contracts
             SET status = :status,
                 responded_at = NOW()
             WHERE contract_id = :contract_id'
        );
        $stmt->bindValue(':status', $decision, PDO::PARAM_STR);
        $stmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function disagreeAllForRequest(int $requestId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE exchange_employee_contracts
             SET status = :status,
                 responded_at = NOW()
             WHERE request_id = :request_id'
        );
        $stmt->bindValue(':status', 'disagreed', PDO::PARAM_STR);
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
