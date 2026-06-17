<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Vacancy.php';
require_once __DIR__ . '/../src/Models/Application.php';
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

$vacancyId = (int) ($_GET['vacancy_id'] ?? $_POST['vacancy_id'] ?? 0);
$vacancyModel = new Vacancy();
$applicationModel = new Application();
$controller = new ApplicationSeekerController();
$vacancy = $vacancyModel->getById($vacancyId);
$error = '';
$alreadyApplied = $vacancyId > 0 && $applicationModel->hasApplied((int) $_SESSION['user_id'], $vacancyId);
$coverLetter = (string) ($_POST['cover_letter'] ?? '');

if (!$vacancy || $vacancy['status'] !== 'active' || strtotime((string) $vacancy['deadline']) < strtotime(date('Y-m-d'))) {
    $error = 'This vacancy is not available for applications.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '' && !$alreadyApplied) {
    $result = $controller->apply((int) $_SESSION['user_id'], $vacancyId, $coverLetter);

    if ($result['success']) {
        header('Location: my-applications.php?message=' . urlencode($result['message']));
        exit;
    }

    $error = $result['message'];
    $alreadyApplied = $applicationModel->hasApplied((int) $_SESSION['user_id'], $vacancyId);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/partials/seeker-nav.php'; ?>

    <main class="profile-main">
        <section class="profile-card seeker-card" aria-labelledby="apply-title">
            <p class="section-kicker">Application</p>
            <h1 id="apply-title">Apply for this role</h1>

            <?php if ($vacancy): ?>
                <div class="company-summary">
                    <div>
                        <span>Role</span>
                        <strong><?php echo e((string) $vacancy['title']); ?></strong>
                    </div>
                    <div>
                        <span>Company</span>
                        <strong><?php echo e((string) $vacancy['company_name']); ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="form-alert" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($alreadyApplied): ?>
                <div class="empty-state">
                    <h3>You've already applied to this role</h3>
                    <p>You can track this application from your applications page.</p>
                    <a class="button-primary" href="my-applications.php">View my applications</a>
                </div>
            <?php elseif ($error === '' && $vacancy): ?>
                <form class="profile-form" method="post">
                    <input type="hidden" name="vacancy_id" value="<?php echo e((string) $vacancyId); ?>">
                    <div class="form-field">
                        <label for="cover-letter">Cover letter</label>
                        <textarea id="cover-letter" name="cover_letter" rows="8" required><?php echo e($coverLetter); ?></textarea>
                    </div>
                    <button class="button-primary" type="submit">Submit application</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
