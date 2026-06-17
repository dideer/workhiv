<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Controllers/ApplicationSeekerController.php';

Session::start();

if (!in_array(($_SESSION['role'] ?? ''), ['job_seeker', 'employee'], true)) {
    header('Location: login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatDateText(?string $value): string
{
    return $value ? date('M j, Y', strtotime($value)) : 'Not set';
}

function statusClass(string $status): string
{
    return strtolower(str_replace(' ', '-', $status));
}

$controller = new ApplicationSeekerController();
$applications = $controller->getMyApplications((int) $_SESSION['user_id']);
$message = (string) ($_GET['message'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Applications | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/partials/seeker-nav.php'; ?>

    <main class="job-listings-section seeker-page" id="applications">
        <header class="listings-header">
            <div class="section-heading">
                <p class="section-kicker">Progress</p>
                <h1>My Applications</h1>
                <p>Track the roles you have applied for and their current status.</p>
            </div>
        </header>

        <?php if ($message !== ''): ?>
            <div class="form-alert"><?php echo e($message); ?></div>
        <?php endif; ?>

        <section class="application-list">
            <?php if ($applications === []): ?>
                <div class="empty-state">
                    <h3>No applications yet</h3>
                    <p>Browse open vacancies and submit your first application.</p>
                    <a class="button-primary" href="index.php#jobs">Find jobs</a>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <article class="application-card">
                        <div>
                            <h2><?php echo e((string) $application['vacancy_title']); ?></h2>
                            <p class="job-company"><?php echo e((string) $application['company_name']); ?></p>
                        </div>
                        <dl class="job-meta">
                            <div>
                                <dt>Applied</dt>
                                <dd><?php echo e(formatDateText($application['applied_at'])); ?></dd>
                            </div>
                            <div>
                                <dt>Deadline</dt>
                                <dd><?php echo e(formatDateText($application['deadline'])); ?></dd>
                            </div>
                        </dl>
                        <span class="status-tag <?php echo e(statusClass((string) $application['status'])); ?>"><?php echo e(ucfirst((string) $application['status'])); ?></span>
                        <?php if (trim((string) ($application['feedback'] ?? '')) !== ''): ?>
                            <p class="feedback-note"><?php echo e((string) $application['feedback']); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
