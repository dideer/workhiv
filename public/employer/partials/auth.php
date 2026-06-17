<?php
require_once __DIR__ . '/../../../src/Helpers/Session.php';
require_once __DIR__ . '/../../../src/Models/Company.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'employer') {
    header('Location: ../login.php');
    exit;
}

$companyModel = new Company();
$employerCompany = $companyModel->findByUserId((int) $_SESSION['user_id']);

if ($employerCompany === null || trim((string) ($employerCompany['description'] ?? '')) === '') {
    header('Location: ../complete-company.php');
    exit;
}

$companyId = (int) $employerCompany['company_id'];
if (!$companyModel->isApproved($companyId)) {
    header('Location: company-pending.php');
    exit;
}

$employerName = (string) ($_SESSION['full_name'] ?? 'Employer');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function statusClass(string $status): string
{
    return strtolower(str_replace(' ', '-', $status));
}

function formatDate(?string $value): string
{
    if (!$value) {
        return 'Not set';
    }

    return date('M j, Y', strtotime($value));
}
