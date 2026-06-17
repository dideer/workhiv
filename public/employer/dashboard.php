<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Models/Vacancy.php';
require_once __DIR__ . '/../../src/Models/Application.php';

$activePage = 'dashboard';
$vacancyModel = new Vacancy();
$applicationModel = new Application();
$vacancies = $vacancyModel->getByCompany($companyId);
$applications = $applicationModel->getByCompany($companyId);
$recentApplications = array_slice($applications, 0, 8);

$activeVacancies = 0;
foreach ($vacancies as $vacancy) {
    if (($vacancy['status'] ?? '') === 'active') {
        $activeVacancies++;
    }
}

$stats = [
    'activeVacancies' => $activeVacancies,
    'totalApplications' => count($applications),
    'pendingReview' => $applicationModel->countByCompany($companyId, 'applied'),
    'hiredThisMonth' => $applicationModel->countHiredThisMonthByCompany($companyId),
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employer Dashboard | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <h1>Dashboard</h1>
            <p>An overview of your vacancies and applications.</p>
        </header>

        <section class="admin-stats" aria-label="Employer statistics">
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['activeVacancies']); ?></strong>
                <span>Active vacancies</span>
            </article>
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['totalApplications']); ?></strong>
                <span>Total applications received</span>
            </article>
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['pendingReview']); ?></strong>
                <span>Pending review</span>
            </article>
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['hiredThisMonth']); ?></strong>
                <span>Hired this month</span>
            </article>
        </section>

        <section class="admin-panel" aria-labelledby="recent-applications-title">
            <div class="admin-section-heading">
                <h2 id="recent-applications-title">Recent applications</h2>
                <p>The latest applications across your vacancies.</p>
            </div>

            <div class="activity-list">
                <?php if ($recentApplications === []): ?>
                    <div class="empty-state">
                        <h3>No applications yet</h3>
                        <p>Applications submitted to your vacancies will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentApplications as $application): ?>
                        <article class="activity-row">
                            <p><?php echo e($application['applicant_name']); ?> applied for <?php echo e($application['vacancy_title']); ?></p>
                            <time><?php echo e(formatDate($application['applied_at'])); ?></time>
                            <span class="status-tag <?php echo e(statusClass($application['status'])); ?>"><?php echo e(ucfirst($application['status'])); ?></span>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
