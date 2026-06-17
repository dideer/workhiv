<?php

require_once __DIR__ . '/../Models/Application.php';
require_once __DIR__ . '/../Models/Vacancy.php';

class ApplicationController
{
    private Application $applications;
    private Vacancy $vacancies;

    public function __construct()
    {
        $this->applications = new Application();
        $this->vacancies = new Vacancy();
    }

    public function listForCompany(int $companyId, ?string $status = null): array
    {
        return $this->applications->getByCompany($companyId, $status);
    }

    public function listForVacancy(int $vacancyId, int $companyId): array
    {
        if (!$this->vacancies->belongsToCompany($vacancyId, $companyId)) {
            return [];
        }

        return $this->applications->getByVacancyId($vacancyId);
    }

    public function updateStatus(int $appId, int $companyId, string $status, ?string $feedback): array
    {
        $allowed = ['shortlisted', 'rejected', 'hired'];
        if (!in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Invalid application status.'];
        }

        if (!$this->applications->belongsToCompany($appId, $companyId)) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        return $this->applications->updateStatus($appId, $status, $feedback)
            ? ['success' => true, 'message' => 'Application updated.']
            : ['success' => false, 'message' => 'Application could not be updated.'];
    }
}
