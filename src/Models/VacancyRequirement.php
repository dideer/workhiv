<?php

require_once __DIR__ . '/../Config/Database.php';

class VacancyRequirement
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vacancy_requirements (vacancy_id, education_level, field_of_study, min_experience_years, skills_required, other_requirements)
             VALUES (:vacancy_id, :education_level, :field_of_study, :min_experience_years, :skills_required, :other_requirements)'
        );
        $stmt->bindValue(':vacancy_id', $data['vacancy_id'], PDO::PARAM_INT);
        $stmt->bindValue(':education_level', $data['education_level'], PDO::PARAM_STR);
        $stmt->bindValue(':field_of_study', $data['field_of_study'] ?? null, empty($data['field_of_study']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':min_experience_years', (int) $data['min_experience_years'], PDO::PARAM_INT);
        $stmt->bindValue(':skills_required', $data['skills_required'], PDO::PARAM_STR);
        $stmt->bindValue(':other_requirements', $data['other_requirements'] ?? null, empty($data['other_requirements']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $vacancyId, array $data): bool
    {
        if ($this->getByVacancyId($vacancyId) === null) {
            $data['vacancy_id'] = $vacancyId;
            $this->create($data);
            return true;
        }

        $stmt = $this->db->prepare(
            'UPDATE vacancy_requirements
             SET education_level = :education_level,
                 field_of_study = :field_of_study,
                 min_experience_years = :min_experience_years,
                 skills_required = :skills_required,
                 other_requirements = :other_requirements
             WHERE vacancy_id = :vacancy_id'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->bindValue(':education_level', $data['education_level'], PDO::PARAM_STR);
        $stmt->bindValue(':field_of_study', $data['field_of_study'] ?? null, empty($data['field_of_study']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':min_experience_years', (int) $data['min_experience_years'], PDO::PARAM_INT);
        $stmt->bindValue(':skills_required', $data['skills_required'], PDO::PARAM_STR);
        $stmt->bindValue(':other_requirements', $data['other_requirements'] ?? null, empty($data['other_requirements']) ? PDO::PARAM_NULL : PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function getByVacancyId(int $vacancyId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM vacancy_requirements WHERE vacancy_id = :vacancy_id LIMIT 1'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->execute();

        $requirement = $stmt->fetch();
        return $requirement ?: null;
    }
}
