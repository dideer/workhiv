<?php

require_once __DIR__ . '/../Config/Database.php';

class Company
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO companies (user_id, company_name, sector, address, website, description, approved_by, approved_at)
             VALUES (:user_id, :company_name, :sector, :address, :website, :description, NULL, NULL)'
        );
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':company_name', $data['company_name'], PDO::PARAM_STR);
        $stmt->bindValue(':sector', $data['sector'], PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'] ?? null, empty($data['address']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':website', $data['website'] ?? null, empty($data['website']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, empty($data['description']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM companies WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $company = $stmt->fetch();
        return $company ?: null;
    }

    public function findById(int $companyId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM companies WHERE company_id = :company_id LIMIT 1'
        );
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        $company = $stmt->fetch();
        return $company ?: null;
    }

    public function updateByUserId(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE companies
             SET address = :address,
                 website = :website,
                 description = :description
             WHERE user_id = :user_id'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':address', $data['address'] ?? null, empty($data['address']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':website', $data['website'] ?? null, empty($data['website']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, empty($data['description']) ? PDO::PARAM_NULL : PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function hasCompletedDetails(int $userId): bool
    {
        $company = $this->findByUserId($userId);

        return $company !== null && trim((string) ($company['description'] ?? '')) !== '';
    }

    public function isApproved(int $companyId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT approved_by FROM companies WHERE company_id = :company_id LIMIT 1'
        );
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        $approvedBy = $stmt->fetchColumn();
        return $approvedBy !== false && $approvedBy !== null;
    }

    public function approve(int $companyId, int $adminUserId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE companies
             SET approved_by = :approved_by,
                 approved_at = NOW()
             WHERE company_id = :company_id'
        );
        $stmt->bindValue(':approved_by', $adminUserId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
