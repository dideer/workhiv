<?php

require_once __DIR__ . '/../Config/Database.php';

class ExamScore
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(int $appId, float $score, int $recordedBy): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO exam_scores (app_id, score, recorded_by)
             VALUES (:app_id, :score, :recorded_by)'
        );
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
        $stmt->bindValue(':score', (string) $score, PDO::PARAM_STR);
        $stmt->bindValue(':recorded_by', $recordedBy, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $appId, float $score): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE exam_scores SET score = :score WHERE app_id = :app_id'
        );
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
        $stmt->bindValue(':score', (string) $score, PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function getByAppId(int $appId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM exam_scores WHERE app_id = :app_id LIMIT 1'
        );
        $stmt->bindValue(':app_id', $appId, PDO::PARAM_INT);
        $stmt->execute();

        $score = $stmt->fetch();
        return $score ?: null;
    }

    public function getByVacancyId(int $vacancyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT es.*, a.vacancy_id, a.user_id, a.status, u.full_name AS applicant_name
             FROM exam_scores es
             INNER JOIN applications a ON a.app_id = es.app_id
             INNER JOIN users u ON u.user_id = a.user_id
             WHERE a.vacancy_id = :vacancy_id
             ORDER BY es.score DESC, a.applied_at ASC'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
