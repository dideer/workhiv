<?php

require_once __DIR__ . '/../Config/Database.php';

class ExchangeContract
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(int $requestId, ?float $finalAmount, ?int $swapEmployeeId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exchange_contracts (request_id, final_amount, swap_employee_id, contract_file, generated_at)
             VALUES (:request_id, :final_amount, :swap_employee_id, NULL, NOW())'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->bindValue(':final_amount', $finalAmount, $finalAmount === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':swap_employee_id', $swapEmployeeId, $swapEmployeeId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByRequestId(int $requestId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ec.*, swap_employee.full_name AS swap_employee_name
             FROM exchange_contracts ec
             LEFT JOIN users swap_employee ON swap_employee.user_id = ec.swap_employee_id
             WHERE ec.request_id = :request_id
             LIMIT 1'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        $contract = $stmt->fetch();
        return $contract ?: null;
    }
}
