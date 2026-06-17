<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Profile.php';
require_once __DIR__ . '/../src/Models/Application.php';

Session::start();

if (!in_array(($_SESSION['role'] ?? ''), ['job_seeker', 'employee'], true)) {
    header('Location: login.php');
    exit;
}

$profileModel = new Profile();
if (($_SESSION['role'] ?? '') === 'job_seeker' && !$profileModel->findByUserId((int) $_SESSION['user_id'])) {
    header('Location: complete-profile.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$applicationModel = new Application();
$totalApplications = $applicationModel->countByUserId((int) $_SESSION['user_id']);
$shortlistedApplications = $applicationModel->countByUserId((int) $_SESSION['user_id'], 'shortlisted');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Job Seeker Dashboard | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/partials/seeker-nav.php'; ?>

    <main class="job-listings-section seeker-page">
        <header class="listings-header">
            <div class="section-heading">
                <p class="section-kicker">Dashboard</p>
                <h1>Welcome, <?php echo e((string) ($_SESSION['full_name'] ?? 'job seeker')); ?></h1>
                <p>Track your applications and continue exploring verified opportunities.</p>
            </div>
        </header>

        <section class="admin-stats seeker-stats" aria-label="Application statistics">
            <article class="admin-stat-card">
                <strong><?php echo e((string) $totalApplications); ?></strong>
                <span>Applications submitted</span>
            </article>
            <article class="admin-stat-card">
                <strong><?php echo e((string) $shortlistedApplications); ?></strong>
                <span>Shortlisted applications</span>
            </article>
        </section>

        <section class="profile-card seeker-card">
            <h2>Next steps</h2>
            <p>Review your application progress or browse open vacancies.</p>
            <div class="modal-actions">
                <a class="button-primary" href="my-applications.php">My applications</a>
                <a class="button-outline" href="index.php#jobs">Find jobs</a>
            </div>
        </section>
    </main>
</body>
</html>
