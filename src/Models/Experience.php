<?php

require_once __DIR__ . '/../Config/Database.php';

class Experience
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO experience (user_id, company_name, job_title, start_date, end_date, is_current, description, proof_file)
             VALUES (:user_id, :company_name, :job_title, :start_date, :end_date, :is_current, :description, :proof_file)'
        );
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':company_name', $data['company_name'], PDO::PARAM_STR);
        $stmt->bindValue(':job_title', $data['job_title'], PDO::PARAM_STR);
        $stmt->bindValue(':start_date', $data['start_date'], PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null, empty($data['end_date']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':is_current', !empty($data['is_current']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'] ?? null, empty($data['description']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':proof_file', $data['proof_file'] ?? null, empty($data['proof_file']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM experience WHERE user_id = :user_id ORDER BY experience_id DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function update(int $experienceId, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE experience
             SET company_name = :company_name,
                 job_title = :job_title,
                 start_date = :start_date,
                 end_date = :end_date,
                 is_current = :is_current,
                 description = :description,
                 proof_file = COALESCE(:proof_file, proof_file)
             WHERE experience_id = :experience_id AND user_id = :user_id'
        );
        $stmt->bindValue(':experience_id', $experienceId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':company_name', $data['company_name'], PDO::PARAM_STR);
        $stmt->bindValue(':job_title', $data['job_title'], PDO::PARAM_STR);
        $stmt->bindValue(':start_date', $data['start_date'], PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null, empty($data['end_date']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':is_current', !empty($data['is_current']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'] ?? null, empty($data['description']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':proof_file', $data['proof_file'] ?? null, empty($data['proof_file']) ? PDO::PARAM_NULL : PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function delete(int $experienceId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM experience WHERE experience_id = :experience_id AND user_id = :user_id'
        );
        $stmt->bindValue(':experience_id', $experienceId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
