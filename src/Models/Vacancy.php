<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/VacancyRequirement.php';

class Vacancy
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO vacancies (company_id, title, number_of_posts, description, location, salary_range, deadline, status)
             VALUES (:company_id, :title, :number_of_posts, :description, :location, :salary_range, :deadline, :status)'
        );
        $stmt->bindValue(':company_id', $data['company_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':number_of_posts', (int) ($data['number_of_posts'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':location', $data['location'], PDO::PARAM_STR);
        $stmt->bindValue(':salary_range', $data['salary_range'], PDO::PARAM_STR);
        $stmt->bindValue(':deadline', $data['deadline'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'active', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $vacancyId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vacancies
             SET title = :title,
                 number_of_posts = :number_of_posts,
                 description = :description,
                 location = :location,
                 salary_range = :salary_range,
                 deadline = :deadline
             WHERE vacancy_id = :vacancy_id AND company_id = :company_id'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $data['company_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':number_of_posts', (int) ($data['number_of_posts'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':location', $data['location'], PDO::PARAM_STR);
        $stmt->bindValue(':salary_range', $data['salary_range'], PDO::PARAM_STR);
        $stmt->bindValue(':deadline', $data['deadline'], PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT v.*,
                    r.education_level,
                    r.field_of_study,
                    r.min_experience_years,
                    r.skills_required,
                    r.other_requirements,
                    COUNT(a.app_id) AS application_count,
                    SUM(CASE WHEN a.status = :hired_status THEN 1 ELSE 0 END) AS hired_count
             FROM vacancies v
             LEFT JOIN vacancy_requirements r ON r.vacancy_id = v.vacancy_id
             LEFT JOIN applications a ON a.vacancy_id = v.vacancy_id
             WHERE v.company_id = :company_id
             GROUP BY v.vacancy_id,
                      r.education_level,
                      r.field_of_study,
                      r.min_experience_years,
                      r.skills_required,
                      r.other_requirements
             ORDER BY v.vacancy_id DESC'
        );
        $stmt->bindValue(':hired_status', 'hired', PDO::PARAM_STR);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getById(int $vacancyId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT v.*, c.company_name, c.sector, c.description AS company_about,
                    r.education_level, r.field_of_study, r.min_experience_years, r.skills_required, r.other_requirements
             FROM vacancies v
             INNER JOIN companies c ON c.company_id = v.company_id
             LEFT JOIN vacancy_requirements r ON r.vacancy_id = v.vacancy_id
             WHERE v.vacancy_id = :vacancy_id
             LIMIT 1'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->execute();

        $vacancy = $stmt->fetch();
        return $vacancy ?: null;
    }

    public function getByIdForUpdate(int $vacancyId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM vacancies WHERE vacancy_id = :vacancy_id LIMIT 1 FOR UPDATE'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->execute();

        $vacancy = $stmt->fetch();
        return $vacancy ?: null;
    }

    public function getActivePublic(array $filters = [], int $limit = 12, int $offset = 0): array
    {
        $params = [];
        $where = $this->publicWhereClause($filters, $params);

        $stmt = $this->db->prepare(
            'SELECT v.*, c.company_name, c.sector, c.description AS company_about,
                    r.education_level, r.field_of_study, r.min_experience_years, r.skills_required, r.other_requirements
             FROM vacancies v
             INNER JOIN companies c ON c.company_id = v.company_id
             LEFT JOIN vacancy_requirements r ON r.vacancy_id = v.vacancy_id
             ' . $where . '
             ORDER BY v.created_at DESC, v.vacancy_id DESC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countActivePublic(array $filters = []): int
    {
        $params = [];
        $where = $this->publicWhereClause($filters, $params);
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM vacancies v
             INNER JOIN companies c ON c.company_id = v.company_id
             LEFT JOIN vacancy_requirements r ON r.vacancy_id = v.vacancy_id
             ' . $where
        );

        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getPublicFilterOptions(): array
    {
        $sectorStmt = $this->db->prepare(
            'SELECT DISTINCT c.sector
             FROM vacancies v
             INNER JOIN companies c ON c.company_id = v.company_id
             WHERE v.status = :status AND v.deadline >= CURDATE() AND c.sector IS NOT NULL AND c.sector <> :empty
             ORDER BY c.sector'
        );
        $sectorStmt->bindValue(':status', 'active', PDO::PARAM_STR);
        $sectorStmt->bindValue(':empty', '', PDO::PARAM_STR);
        $sectorStmt->execute();

        $educationStmt = $this->db->prepare(
            'SELECT DISTINCT r.education_level
             FROM vacancies v
             INNER JOIN vacancy_requirements r ON r.vacancy_id = v.vacancy_id
             WHERE v.status = :status AND v.deadline >= CURDATE() AND r.education_level IS NOT NULL AND r.education_level <> :empty
             ORDER BY r.education_level'
        );
        $educationStmt->bindValue(':status', 'active', PDO::PARAM_STR);
        $educationStmt->bindValue(':empty', '', PDO::PARAM_STR);
        $educationStmt->execute();

        return [
            'sectors' => array_column($sectorStmt->fetchAll(), 'sector'),
            'education' => array_column($educationStmt->fetchAll(), 'education_level'),
        ];
    }

    private function publicWhereClause(array $filters, array &$params): string
    {
        $clauses = ['v.status = :status', 'v.deadline >= CURDATE()'];
        $params[':status'] = 'active';

        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $clauses[] = '(v.title LIKE :search OR v.location LIKE :search)';
            $params[':search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (trim((string) ($filters['sector'] ?? '')) !== '') {
            $clauses[] = 'c.sector = :sector';
            $params[':sector'] = trim((string) $filters['sector']);
        }

        if (trim((string) ($filters['education'] ?? '')) !== '') {
            $clauses[] = 'r.education_level = :education';
            $params[':education'] = trim((string) $filters['education']);
        }

        return 'WHERE ' . implode(' AND ', $clauses);
    }

    public function belongsToCompany(int $vacancyId, int $companyId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM vacancies WHERE vacancy_id = :vacancy_id AND company_id = :company_id LIMIT 1'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function close(int $vacancyId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE vacancies SET status = :status WHERE vacancy_id = :vacancy_id'
        );
        $stmt->bindValue(':status', 'closed', PDO::PARAM_STR);
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function getHiredCount(int $vacancyId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM applications WHERE vacancy_id = :vacancy_id AND status = :status'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'hired', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function closeIfFull(int $vacancyId): bool
    {
        $vacancy = $this->getByIdForUpdate($vacancyId);
        if (!$vacancy) {
            return false;
        }

        $numberOfPosts = max(1, (int) ($vacancy['number_of_posts'] ?? 1));
        if ($this->getHiredCount($vacancyId) < $numberOfPosts) {
            return true;
        }

        return $this->close($vacancyId);
    }

    public function reopenIfBelowCapacity(int $vacancyId): bool
    {
        $vacancy = $this->getByIdForUpdate($vacancyId);
        if (!$vacancy) {
            return false;
        }

        $numberOfPosts = max(1, (int) ($vacancy['number_of_posts'] ?? 1));
        if (($vacancy['status'] ?? '') !== 'closed' || $this->getHiredCount($vacancyId) >= $numberOfPosts) {
            return true;
        }

        $stmt = $this->db->prepare(
            'UPDATE vacancies SET status = :status WHERE vacancy_id = :vacancy_id'
        );
        $stmt->bindValue(':status', 'active', PDO::PARAM_STR);
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $vacancyId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM vacancies WHERE vacancy_id = :vacancy_id AND status = :status'
        );
        $stmt->bindValue(':vacancy_id', $vacancyId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'active', PDO::PARAM_STR);

        return $stmt->execute();
    }
}
