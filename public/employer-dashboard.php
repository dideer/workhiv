<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Company.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'employer') {
    header('Location: login.php');
    exit;
}

$companyModel = new Company();
if (!$companyModel->hasCompletedDetails((int) $_SESSION['user_id'])) {
    header('Location: complete-company.php');
    exit;
}

if (!$companyModel->isApproved((int) $companyModel->findByUserId((int) $_SESSION['user_id'])['company_id'])) {
    header('Location: employer/company-pending.php');
    exit;
}

header('Location: employer/dashboard.php');
exit;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employer Dashboard | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="placeholder-main">
        <section class="placeholder-card">
            <p class="section-kicker">Employer dashboard</p>
            <h1>Welcome, <?php echo e((string) ($_SESSION['full_name'] ?? 'employer')); ?></h1>
            <p>Your employer dashboard will be built in the next backend step.</p>
            <a class="button-primary" href="logout.php">Log out</a>
        </section>
    </main>
</body>
</html>
