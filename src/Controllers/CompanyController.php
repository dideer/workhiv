<?php

require_once __DIR__ . '/../Helpers/Session.php';
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

        return $this->companies->approve($companyId, $adminUserId)
            ? ['success' => true, 'message' => 'Company approved.']
            : ['success' => false, 'message' => 'Company could not be approved.'];
    }
}
