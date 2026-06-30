<?php
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Models/Company.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'employer') {
    header('Location: ../login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$companyModel = new Company();
$company = $companyModel->findByUserId((int) $_SESSION['user_id']);

if ($company === null || trim((string) ($company['description'] ?? '')) === '') {
    header('Location: ../complete-company.php');
    exit;
}

if ($companyModel->isApproved((int) $company['company_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company Pending Approval | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Company approval navigation">
            <a class="site-logo" href="../index.php" aria-label="WorkHive home">WorkHive</a>
            <div class="nav-actions">
                <span>Hi, <?php echo e((string) ($_SESSION['full_name'] ?? 'there')); ?></span>
                <?php require __DIR__ . '/../partials/notification-bell.php'; ?>
                <a class="nav-button nav-button-secondary" href="../logout.php">Log out</a>
            </div>
        </nav>
    </header>

    <main class="placeholder-main">
        <section class="placeholder-card">
            <p class="section-kicker">Company approval</p>
            <h1>Your company is awaiting approval</h1>
            <p>Your company is awaiting approval from an administrator. You'll be notified once it's approved and can start posting vacancies.</p>

            <div class="company-summary">
                <div>
                    <span>Company name</span>
                    <strong><?php echo e((string) ($company['company_name'] ?? 'Not provided')); ?></strong>
                </div>
                <div>
                    <span>Sector</span>
                    <strong><?php echo e((string) ($company['sector'] ?? 'Not provided')); ?></strong>
                </div>
                <div>
                    <span>Address</span>
                    <strong><?php echo e((string) ($company['address'] ?? 'Not provided')); ?></strong>
                </div>
                <div>
                    <span>Website</span>
                    <strong><?php echo e((string) ($company['website'] ?? 'Not provided')); ?></strong>
                </div>
                <div class="full-span">
                    <span>Description</span>
                    <strong><?php echo e((string) ($company['description'] ?? 'Not provided')); ?></strong>
                </div>
            </div>

            <a class="button-primary" href="../index.php">Browse WorkHive</a>
        </section>
    </main>
</body>
</html>
