<?php

require_once __DIR__ . '/../Models/Application.php';
require_once __DIR__ . '/../Models/Vacancy.php';

class ApplicationSeekerController
{
    private Application $applications;
    private Vacancy $vacancies;

    public function __construct()
    {
        $this->applications = new Application();
        $this->vacancies = new Vacancy();
    }

    public function apply(int $userId, int $vacancyId, string $coverLetter): array
    {
        $vacancy = $this->vacancies->getById($vacancyId);
        if (!$vacancy || $vacancy['status'] !== 'active') {
            return ['success' => false, 'message' => 'This vacancy is closed.'];
        }

        if (strtotime((string) $vacancy['deadline']) < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'message' => 'This vacancy has expired.'];
        }

        if ($this->applications->hasApplied($userId, $vacancyId)) {
            return ['success' => false, 'message' => 'You have already applied to this role.'];
        }

        if (trim($coverLetter) === '') {
            return ['success' => false, 'message' => 'Please enter a cover letter.'];
        }

        $this->applications->create([
            'user_id' => $userId,
            'vacancy_id' => $vacancyId,
            'cover_letter' => trim($coverLetter),
            'status' => 'applied',
        ]);

        return ['success' => true, 'message' => 'Application submitted.'];
    }

    public function getMyApplications(int $userId): array
    {
        return $this->applications->getByUserId($userId);
    }
}
