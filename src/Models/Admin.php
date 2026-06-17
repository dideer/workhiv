<?php

require_once __DIR__ . '/../Config/Database.php';

class Admin
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function countUsersByRoleAndStatus(string $role, string $status): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE role = :role AND status = :status'
        );
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function countVacanciesByStatus(string $status): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM vacancies WHERE status = :status'
        );
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function countExchangeRequestsByStatus(string $status): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM exchange_requests WHERE status = :status'
        );
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function pendingEmployers(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.user_id, u.full_name, u.email, u.created_at, c.company_name, c.sector
             FROM users u
             LEFT JOIN companies c ON c.user_id = u.user_id
             WHERE u.role = :role AND u.status = :status
             ORDER BY u.user_id DESC'
        );
        $stmt->bindValue(':role', 'employer', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function pendingVacancies(): array
    {
        $stmt = $this->db->prepare(
            'SELECT v.vacancy_id, v.title, v.created_at, c.company_name
             FROM vacancies v
             LEFT JOIN companies c ON c.company_id = v.company_id
             WHERE v.status = :status
             ORDER BY v.vacancy_id DESC'
        );
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function pendingExchangeRequests(): array
    {
        $stmt = $this->db->prepare(
            'SELECT er.request_id, er.employee_id, er.exchange_type, er.created_at,
                    company_a.company_name AS source_company,
                    company_b.company_name AS target_company,
                    employee.full_name AS employee_name
             FROM exchange_requests er
             LEFT JOIN companies company_a ON company_a.company_id = er.company_a_id
             LEFT JOIN companies company_b ON company_b.company_id = er.company_b_id
             LEFT JOIN users employee ON employee.user_id = er.employee_id
             WHERE er.status = :status
             ORDER BY er.request_id DESC'
        );
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
