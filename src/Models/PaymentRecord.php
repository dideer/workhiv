<?php

require_once __DIR__ . '/../Config/Database.php';

class PaymentRecord
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(int $contractId, float $amount, int $paidBy, int $paidTo): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payment_records (contract_id, amount, paid_by, paid_to, payment_status)
             VALUES (:contract_id, :amount, :paid_by, :paid_to, :payment_status)'
        );
        $stmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);
        $stmt->bindValue(':amount', (string) $amount, PDO::PARAM_STR);
        $stmt->bindValue(':paid_by', $paidBy, PDO::PARAM_INT);
        $stmt->bindValue(':paid_to', $paidTo, PDO::PARAM_INT);
        $stmt->bindValue(':payment_status', 'recorded', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByContractId(int $contractId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT pr.*, payer.company_name AS paid_by_name, payee.company_name AS paid_to_name
             FROM payment_records pr
             INNER JOIN companies payer ON payer.company_id = pr.paid_by
             INNER JOIN companies payee ON payee.company_id = pr.paid_to
             WHERE pr.contract_id = :contract_id
             LIMIT 1'
        );
        $stmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);
        $stmt->execute();

        $payment = $stmt->fetch();
        return $payment ?: null;
    }
}
