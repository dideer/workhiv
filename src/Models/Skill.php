<?php

require_once __DIR__ . '/../Config/Database.php';

class Skill
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO skills (user_id, skill_name, skill_level, is_custom)
             VALUES (:user_id, :skill_name, :skill_level, :is_custom)'
        );
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':skill_name', $data['skill_name'], PDO::PARAM_STR);
        $stmt->bindValue(':skill_level', $data['skill_level'], PDO::PARAM_STR);
        $stmt->bindValue(':is_custom', !empty($data['is_custom']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM skills WHERE user_id = :user_id ORDER BY skill_id DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
