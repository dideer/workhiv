<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Vacancy.php';
require_once __DIR__ . '/../Models/VacancyRequirement.php';

class VacancyController
{
    private PDO $db;
    private Vacancy $vacancies;
    private VacancyRequirement $requirements;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->vacancies = new Vacancy($this->db);
        $this->requirements = new VacancyRequirement($this->db);
    }

    public function postVacancy(int $companyId, array $data): array
    {
        $validation = $this->validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        try {
            $this->db->beginTransaction();
            $vacancyId = $this->vacancies->create([
                'company_id' => $companyId,
                'title' => trim($data['title']),
                'number_of_posts' => (int) ($data['number_of_posts'] ?? 1),
                'description' => trim($data['description']),
                'location' => trim($data['location']),
                'salary_range' => trim($data['salary_range']),
                'deadline' => trim($data['deadline']),
                'status' => 'active',
            ]);

            $this->requirements->create($this->requirementPayload($vacancyId, $data));
            $this->db->commit();

            return ['success' => true, 'message' => 'Vacancy posted.'];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => 'Vacancy could not be saved.'];
        }
    }

    public function updateVacancy(int $vacancyId, int $companyId, array $data): array
    {
        if (!$this->vacancies->belongsToCompany($vacancyId, $companyId)) {
            return ['success' => false, 'message' => 'Vacancy not found.'];
        }

        $validation = $this->validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        try {
            $this->db->beginTransaction();
            $this->vacancies->update($vacancyId, [
                'company_id' => $companyId,
                'title' => trim($data['title']),
                'number_of_posts' => (int) ($data['number_of_posts'] ?? 1),
                'description' => trim($data['description']),
                'location' => trim($data['location']),
                'salary_range' => trim($data['salary_range']),
                'deadline' => trim($data['deadline']),
            ]);
            $this->requirements->update($vacancyId, $this->requirementPayload($vacancyId, $data));
            $this->db->commit();

            return ['success' => true, 'message' => 'Vacancy updated.'];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => 'Vacancy could not be updated.'];
        }
    }

    public function closeVacancy(int $vacancyId, int $companyId): array
    {
        if (!$this->vacancies->belongsToCompany($vacancyId, $companyId)) {
            return ['success' => false, 'message' => 'Vacancy not found.'];
        }

        return $this->vacancies->close($vacancyId)
            ? ['success' => true, 'message' => 'Vacancy closed.']
            : ['success' => false, 'message' => 'Vacancy could not be closed.'];
    }

    private function validate(array $data): array
    {
        foreach (['title', 'description', 'location', 'salary_range', 'deadline', 'education_level', 'skills_required'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                return ['success' => false, 'message' => 'Please complete all required vacancy fields.'];
            }
        }

        if ((int) ($data['min_experience_years'] ?? -1) < 0) {
            return ['success' => false, 'message' => 'Minimum experience must be zero or greater.'];
        }

        if ((int) ($data['number_of_posts'] ?? 0) < 1) {
            return ['success' => false, 'message' => 'Number of positions must be at least 1.'];
        }

        return ['success' => true, 'message' => 'Valid.'];
    }

    private function requirementPayload(int $vacancyId, array $data): array
    {
        return [
            'vacancy_id' => $vacancyId,
            'education_level' => trim($data['education_level']),
            'field_of_study' => trim((string) ($data['field_of_study'] ?? '')),
            'min_experience_years' => (int) $data['min_experience_years'],
            'skills_required' => trim($data['skills_required']),
            'other_requirements' => trim((string) ($data['other_requirements'] ?? '')),
        ];
    }
}
