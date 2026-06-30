<?php

require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Helpers/Notifier.php';
require_once __DIR__ . '/../Models/Company.php';

class CompanyController
{
    private Company $companies;

    public function __construct()
    {
        $this->companies = new Company();
    }

    public function approveCompany(int $companyId, int $adminUserId): array
    {
        Session::start();

        if (($_SESSION['role'] ?? '') !== 'admin') {
            return ['success' => false, 'message' => 'You are not allowed to approve companies.'];
        }

        if ($companyId <= 0 || $adminUserId <= 0) {
            return ['success' => false, 'message' => 'Invalid approval request.'];
        }

        if (!$this->companies->approve($companyId, $adminUserId)) {
            return ['success' => false, 'message' => 'Company could not be approved.'];
        }

        try {
            $company = $this->companies->findById($companyId);
            if ($company) {
                Notifier::send(
                    (int) $company['user_id'],
                    'Your company ' . (string) $company['company_name'] . ' has been approved. You can now post vacancies.',
                    'approval',
                    'employer/dashboard.php'
                );
            }
        } catch (Throwable $exception) {
            error_log('Notification failed: ' . $exception->getMessage());
        }

        return ['success' => true, 'message' => 'Company approved.'];
    }
}
