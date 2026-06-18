<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/ApplicationController.php';
require_once __DIR__ . '/../../src/Models/Vacancy.php';

$activePage = 'applications';
$controller = new ApplicationController();
$vacancyModel = new Vacancy();
$vacancyId = isset($_GET['vacancy_id']) ? (int) $_GET['vacancy_id'] : 0;
$status = (string) ($_GET['status'] ?? '');
$message = (string) ($_GET['message'] ?? '');
$selectedVacancy = null;
$rankedSections = [];
$filledBanner = '';

if ($vacancyId > 0) {
    $selectedVacancy = $vacancyModel->getById($vacancyId);
    if (!$selectedVacancy || (int) $selectedVacancy['company_id'] !== $companyId) {
        $applications = [];
        $selectedVacancy = null;
    } else {
        $applications = $controller->listForVacancy($vacancyId, $companyId);
        if ($status !== '') {
            $applications = array_values(array_filter($applications, fn($app) => $app['status'] === $status));
        }
        $ranked = $controller->listShortlistedRanked($vacancyId, $companyId);
        if ($ranked !== []) {
            $rankedSections[] = ['vacancy' => $selectedVacancy, 'candidates' => $ranked];
        }
        $numberOfPosts = max(1, (int) ($selectedVacancy['number_of_posts'] ?? 1));
        if (($selectedVacancy['status'] ?? '') === 'closed' && $vacancyModel->getHiredCount($vacancyId) >= $numberOfPosts) {
            $filledBanner = 'This vacancy is closed - all ' . $numberOfPosts . ' position(s) have been filled.';
        }
    }
} else {
    $applications = $controller->listForCompany($companyId, $status !== '' ? $status : null);
    foreach ($vacancyModel->getByCompany($companyId) as $vacancy) {
        $ranked = $controller->listShortlistedRanked((int) $vacancy['vacancy_id'], $companyId);
        if ($ranked !== []) {
            $rankedSections[] = ['vacancy' => $vacancy, 'candidates' => $ranked];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Applications | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <h1>Applications</h1>
            <p>Review applications submitted to your vacancies.</p>
        </header>

        <?php if ($message !== ''): ?>
            <div class="form-alert"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($filledBanner !== ''): ?>
            <div class="form-alert"><?php echo e($filledBanner); ?></div>
        <?php endif; ?>

        <section class="admin-panel" aria-labelledby="applications-filter-title">
            <div class="admin-section-heading">
                <h2 id="applications-filter-title"><?php echo $selectedVacancy ? e($selectedVacancy['title']) : 'All applications'; ?></h2>
                <?php if ($selectedVacancy): ?>
                    <p><a href="applications.php">All applications</a></p>
                <?php else: ?>
                    <p>Filter applications by review status.</p>
                <?php endif; ?>
            </div>

            <form class="report-filter-form" method="get">
                <?php if ($vacancyId > 0): ?>
                    <input type="hidden" name="vacancy_id" value="<?php echo e((string) $vacancyId); ?>">
                <?php endif; ?>
                <div class="form-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <?php foreach (['applied' => 'Applied', 'shortlisted' => 'Shortlisted', 'rejected' => 'Rejected', 'hired' => 'Hired'] as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $status === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="button-primary" type="submit">Apply filter</button>
            </form>
        </section>

        <section class="admin-panel report-preview" aria-labelledby="applications-list-title">
            <div class="admin-section-heading">
                <h2 id="applications-list-title">Application list</h2>
            </div>

            <div class="approval-list">
                <?php if ($applications === []): ?>
                    <div class="empty-state">
                        <h3>No applications found</h3>
                        <p>Applications matching this view will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($applications as $application): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e($application['applicant_name']); ?></p>
                                <span class="approval-meta">
                                    <?php if (!$selectedVacancy): ?>
                                        <?php echo e($application['vacancy_title']); ?> -
                                    <?php endif; ?>
                                    Applied <?php echo e(formatDate($application['applied_at'])); ?>
                                </span>
                                <span class="status-tag <?php echo e(statusClass($application['status'])); ?>"><?php echo e(ucfirst($application['status'])); ?></span>
                            </div>
                            <div class="approval-actions">
                                <a class="button-primary" href="application-detail.php?app_id=<?php echo e((string) $application['app_id']); ?>">View application</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($rankedSections !== []): ?>
            <section class="admin-panel report-preview" aria-labelledby="shortlisted-title">
                <div class="admin-section-heading">
                    <h2 id="shortlisted-title">Shortlisted candidates</h2>
                    <p>Ranked by exam score. Candidates without scores remain visible and must be scored before hiring.</p>
                </div>

                <?php foreach ($rankedSections as $section): ?>
                    <div class="admin-section-heading">
                        <h3><?php echo e((string) $section['vacancy']['title']); ?></h3>
                    </div>
                    <div class="approval-list">
                        <?php foreach ($section['candidates'] as $index => $candidate): ?>
                            <article class="approval-item">
                                <div class="approval-primary">
                                    <p>#<?php echo e((string) ($index + 1)); ?> <?php echo e((string) $candidate['applicant_name']); ?></p>
                                    <?php if ((int) $candidate['has_score'] === 1): ?>
                                        <span class="status-tag shortlisted"><?php echo e(number_format((float) $candidate['score'], 2)); ?>/100</span>
                                    <?php else: ?>
                                        <span class="status-tag closed">Not scored yet</span>
                                    <?php endif; ?>
                                </div>
                                <div class="approval-actions">
                                    <a class="button-primary" href="application-detail.php?app_id=<?php echo e((string) $candidate['app_id']); ?>">View / Score / Hire</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
