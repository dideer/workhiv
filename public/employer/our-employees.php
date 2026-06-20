<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/ExchangeController.php';

$activePage = 'our-employees';
$controller = new ExchangeController();
$employees = $controller->listCompanyEmployees($companyId);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Our Employees | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <h1>Our employees</h1>
            <p>Employees currently assigned to your company.</p>
        </header>

        <section class="admin-stats" aria-label="Employee statistics">
            <article class="admin-stat-card">
                <strong><?php echo e((string) count($employees)); ?></strong>
                <span>Total employees</span>
            </article>
        </section>

        <section class="admin-panel report-preview" aria-labelledby="our-employees-title">
            <div class="admin-section-heading">
                <h2 id="our-employees-title">Employee list</h2>
                <p>Employees appear here after they are hired or moved to your company through an accepted exchange.</p>
            </div>

            <div class="approval-list">
                <?php if ($employees === []): ?>
                    <div class="empty-state">
                        <h3>No employees yet</h3>
                        <p>Hired and exchanged employees assigned to your company will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e((string) $employee['full_name']); ?></p>
                                <span class="approval-meta"><?php echo e((string) ($employee['vacancy_title'] ?? 'Position not set')); ?></span>
                            </div>
                            <span class="status-tag hired">Employee</span>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
