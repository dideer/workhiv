<?php

require_once __DIR__ . '/../Models/Application.php';
require_once __DIR__ . '/../Models/ExamScore.php';
require_once __DIR__ . '/../Models/Vacancy.php';
require_once __DIR__ . '/../Helpers/Notifier.php';

class ApplicationController
{
    private Application $applications;
    private ExamScore $examScores;
    private Vacancy $vacancies;

    public function __construct()
    {
        $this->applications = new Application();
        $this->examScores = new ExamScore();
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

    public function listShortlistedRanked(int $vacancyId, int $companyId): array
    {
        if (!$this->vacancies->belongsToCompany($vacancyId, $companyId)) {
            return [];
        }

        return $this->applications->getShortlistedRanked($vacancyId);
    }

    public function viewDetail(int $appId, int $companyId): ?array
    {
        return $this->applications->getDetailById($appId, $companyId);
    }

    public function updateStatus(int $appId, int $companyId, string $status, ?string $feedback): array
    {
        $allowed = ['shortlisted', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Invalid application status.'];
        }

        if (!$this->applications->belongsToCompany($appId, $companyId)) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        if (!$this->applications->updateStatus($appId, $status, $feedback)) {
            return ['success' => false, 'message' => 'Application could not be updated.'];
        }

        try {
            $context = $this->applications->getNotificationContextByAppId($appId);
            if ($context) {
                Notifier::send(
                    (int) $context['user_id'],
                    'Your application for ' . (string) $context['vacancy_title'] . ' was ' . $status . '.',
                    'application',
                    'my-applications.php'
                );
            }
        } catch (Throwable $exception) {
            error_log('Notification failed: ' . $exception->getMessage());
        }

        return ['success' => true, 'message' => 'Application updated.'];
    }

    public function recordScore(int $appId, int $companyId, float $score, int $employerId): array
    {
        $application = $this->applications->getDetailById($appId, $companyId);
        if (!$application) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        if ($application['status'] !== 'shortlisted') {
            return ['success' => false, 'message' => 'Only shortlisted applications can be scored.'];
        }

        if ($score < 0 || $score > 100) {
            return ['success' => false, 'message' => 'Score must be between 0 and 100.'];
        }

        $existing = $this->examScores->getByAppId($appId);
        $saved = $existing
            ? $this->examScores->update($appId, $score)
            : $this->examScores->create($appId, $score, $employerId) > 0;

        return $saved
            ? ['success' => true, 'message' => 'Exam score saved.']
            : ['success' => false, 'message' => 'Exam score could not be saved.'];
    }

    public function generateContractText(int $appId): string
    {
        $context = $this->applications->getContractContextByAppId($appId);
        if (!$context) {
            return '';
        }

        $today = date('F j, Y');
        $deadline = !empty($context['deadline']) ? date('F j, Y', strtotime((string) $context['deadline'])) : 'the expected start date';

        return implode("\n\n", [
            'EMPLOYMENT CONTRACT',
            'This agreement is made between ' . $context['company_name'] . ' ("the Employer") and ' . $context['full_name'] . ' ("the Employee") on ' . $today . '.',
            'Position: ' . $context['vacancy_title'],
            'Compensation: ' . $context['salary_range'],
            'Start expectation: The parties expect onboarding to proceed after the recruitment deadline of ' . $deadline . ', subject to final communication from the Employer.',
            'Terms: The Employee agrees to fulfill the duties of the above position in accordance with the standards and expectations of the Employer, as outlined during the recruitment process.',
            'By accepting this contract, both parties agree to proceed under the terms described above, subject to Rwandan labor law and any further written agreements between the parties.',
        ]);
    }

    public function hire(int $appId, int $companyId, int $employerId): array
    {
        $application = $this->applications->getDetailById($appId, $companyId);
        if (!$application) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        if ($application['status'] !== 'shortlisted') {
            return ['success' => false, 'message' => 'Only shortlisted applications can be hired through ranking.'];
        }

        $vacancy = $this->vacancies->getById((int) $application['vacancy_id']);
        if (!$vacancy) {
            return ['success' => false, 'message' => 'Vacancy not found.'];
        }

        $numberOfPosts = max(1, (int) ($vacancy['number_of_posts'] ?? 1));
        if ($this->vacancies->getHiredCount((int) $application['vacancy_id']) >= $numberOfPosts) {
            return ['success' => false, 'message' => 'All ' . $numberOfPosts . ' position(s) for this vacancy have already been filled.'];
        }

        $ranked = $this->applications->getShortlistedRanked((int) $application['vacancy_id']);
        if ($ranked === []) {
            return ['success' => false, 'message' => 'No shortlisted candidates are available for hiring.'];
        }

        foreach ($ranked as $candidate) {
            if ((int) $candidate['has_score'] !== 1) {
                return ['success' => false, 'message' => 'All shortlisted candidates must be scored before you can hire.'];
            }
        }

        $top = $ranked[0];
        if ((int) $top['app_id'] !== $appId) {
            return [
                'success' => false,
                'message' => $top['applicant_name'] . ' has the highest score (' . number_format((float) $top['score'], 2) . '/100) among shortlisted candidates. You must hire or reject them before hiring someone ranked lower.',
            ];
        }

        $contractText = $this->generateContractText($appId);
        if ($contractText === '') {
            return ['success' => false, 'message' => 'Employment contract could not be generated.'];
        }

        if (!$this->applications->hireWithRoleUpdate($appId, (int) $application['vacancy_id'], $contractText)) {
            return ['success' => false, 'message' => 'Applicant could not be hired.'];
        }

        try {
            Notifier::send(
                (int) $application['user_id'],
                "Congratulations! You've been hired for " . (string) $application['vacancy_title'] . '.',
                'application',
                'my-applications.php'
            );
            Notifier::send(
                (int) $application['user_id'],
                'Your employment contract for ' . (string) $application['vacancy_title'] . ' is ready to review.',
                'contract',
                'employee/contract.php'
            );
        } catch (Throwable $exception) {
            error_log('Notification failed: ' . $exception->getMessage());
        }

        return ['success' => true, 'message' => 'Applicant hired.'];
    }
}
