<?php

require_once __DIR__ . '/../Config/Database.php';

class Education
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO education (user_id, education_level, field_of_study, institution, year_completed, proof_file)
             VALUES (:user_id, :education_level, :field_of_study, :institution, :year_completed, :proof_file)'
        );
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':education_level', $data['education_level'], PDO::PARAM_STR);
        $stmt->bindValue(':field_of_study', $data['field_of_study'], PDO::PARAM_STR);
        $stmt->bindValue(':institution', $data['institution'], PDO::PARAM_STR);
        $stmt->bindValue(':year_completed', $data['year_completed'], PDO::PARAM_INT);
        $stmt->bindValue(':proof_file', $data['proof_file'] ?? null, empty($data['proof_file']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM education WHERE user_id = :user_id ORDER BY education_id DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
