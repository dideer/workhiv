<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/User.php';

class Application
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getByVacancyId(int $vacancyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.full_name AS applicant_name, u.email AS applicant_email, v.title AS vacancy_title
             FROM applications a
             INNER JOIN users u ON u.user_id = a.user_id
             INNER JOIN vacancies v ON v.vacancy_id = a.vacancy_id
             WHERE a.vacancy_id = :vacancy_id
             ORDER BY a.app_id DESC'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO applications (user_id, vacancy_id, cover_letter, status)
             VALUES (:user_id, :vacancy_id, :cover_letter, :status)'
        );
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':vacancy_id', $data['vacancy_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cover_letter', $data['cover_letter'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'applied', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function hasApplied(int $userId, int $vacancyId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM applications WHERE user_id = :user_id AND vacancy_id = :vacancy_id LIMIT 1'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, v.title AS vacancy_title, v.deadline, c.company_name
             FROM applications a
             INNER JOIN vacancies v ON v.vacancy_id = a.vacancy_id
             INNER JOIN companies c ON c.company_id = v.company_id
             WHERE a.user_id = :user_id
             ORDER BY a.app_id DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countByUserId(int $userId, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM applications WHERE user_id = :user_id';
        if ($status !== null && $status !== '') {
            $sql .= ' AND status = :status';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($status !== null && $status !== '') {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getByCompany(int $companyId, ?string $status = null): array
    {
        $sql = 'SELECT a.*, u.full_name AS applicant_name, u.email AS applicant_email, v.title AS vacancy_title
                FROM applications a
                INNER JOIN users u ON u.user_id = a.user_id
                INNER JOIN vacancies v ON v.vacancy_id = a.vacancy_id
                WHERE v.company_id = :company_id';

        if ($status !== null && $status !== '') {
            $sql .= ' AND a.status = :status';
        }

        $sql .= ' ORDER BY a.app_id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        if ($status !== null && $status !== '') {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function belongsToCompany(int $appId, int $companyId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM applications a
             INNER JOIN vacancies v ON v.vacancy_id = a.vacancy_id
             WHERE a.app_id = :app_id AND v.company_id = :company_id
             LIMIT 1'
        );
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function updateStatus(int $appId, string $status, ?string $feedback = null): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'UPDATE applications
                 SET status = :status,
                     feedback = :feedback,
                     updated_at = NOW()
                 WHERE app_id = :app_id'
            );
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':feedback', $feedback, $feedback === null || $feedback === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
            $stmt->execute();

            if ($status === 'hired') {
                $userId = $this->getApplicantId($appId);
                if ($userId !== null) {
                    $users = new User($this->db);
                    $users->updateRole($userId, 'employee');
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return false;
        }
    }

    public function countByCompany(int $companyId, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*)
                FROM applications a
                INNER JOIN vacancies v ON v.vacancy_id = a.vacancy_id
                WHERE v.company_id = :company_id';

        if ($status !== null && $status !== '') {
            $sql .= ' AND a.status = :status';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        if ($status !== null && $status !== '') {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function countHiredThisMonthByCompany(int $companyId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM applications a
             INNER JOIN vacancies v ON v.vacancy_id = a.vacancy_id
             WHERE v.company_id = :company_id
               AND a.status = :status
               AND YEAR(a.updated_at) = YEAR(CURRENT_DATE())
               AND MONTH(a.updated_at) = MONTH(CURRENT_DATE())'
        );
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'hired', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function getApplicantId(int $appId): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT user_id FROM applications WHERE app_id = :app_id LIMIT 1'
        );
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
        $stmt->execute();

        $userId = $stmt->fetchColumn();
        return $userId === false ? null : (int) $userId;
    }
}
