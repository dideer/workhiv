<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Application.php';
require_once __DIR__ . '/../src/Models/EmploymentContract.php';
require_once __DIR__ . '/../src/Models/ExchangeEmployeeContract.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'employee') {
    header('Location: login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$applicationModel = new Application();
$contractModel = new EmploymentContract();
$exchangeContractModel = new ExchangeEmployeeContract();
$application = $applicationModel->getLatestHiredByUserId((int) $_SESSION['user_id']);
$contract = $application ? $contractModel->getByAppId((int) $application['app_id']) : null;
$message = (string) ($_GET['message'] ?? '');

if (!$application || !$contract || $contract['status'] !== 'agreed') {
    header('Location: employee/contract.php');
    exit;
}

if ($exchangeContractModel->getPendingForEmployee((int) $_SESSION['user_id'])) {
    header('Location: employee/exchange-contract.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Dashboard | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Employee navigation">
            <a class="site-logo" href="index.php" aria-label="WorkHive home">WorkHive</a>
            <div class="nav-actions">
                <span>Hi, <?php echo e((string) ($_SESSION['full_name'] ?? 'there')); ?></span>
                <?php require __DIR__ . '/partials/notification-bell.php'; ?>
                <a class="nav-button nav-button-secondary" href="logout.php">Log out</a>
            </div>
        </nav>
    </header>

    <main class="profile-main">
        <section class="profile-card" aria-labelledby="dashboard-title">
            <p class="section-kicker">Employee dashboard</p>
            <h1 id="dashboard-title">Welcome, <?php echo e((string) ($_SESSION['full_name'] ?? 'employee')); ?></h1>
            <p>Your employment contract is agreed and your employee workspace is active.</p>

            <?php if ($message !== ''): ?>
                <div class="form-alert"><?php echo e($message); ?></div>
            <?php endif; ?>

            <div class="company-summary">
                <div>
                    <span>Current position</span>
                    <strong><?php echo e((string) $application['vacancy_title']); ?></strong>
                </div>
                <div>
                    <span>Company</span>
                    <strong><?php echo e((string) $application['company_name']); ?></strong>
                </div>
            </div>

            <div class="empty-state">
                <h3>Employee features are coming</h3>
                <p>Future modules such as employee exchange participation will appear here.</p>
            </div>

            <div class="empty-state" style="background: var(--paper-deep);">
                <h3>Employee Exchange - Coming Soon</h3>
                <p>In the future, you'll be able to see and participate more directly in exchange activity involving you.</p>
            </div>
        </section>
    </main>
</body>
</html>
