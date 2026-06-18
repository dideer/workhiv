<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/ApplicationController.php';

$activePage = 'applications';
$controller = new ApplicationController();
$appId = (int) ($_GET['app_id'] ?? $_POST['app_id'] ?? 0);
$message = (string) ($_GET['message'] ?? '');
$error = '';

function detailDate(?string $value): string
{
    return $value ? date('M j, Y', strtotime($value)) : 'Not set';
}

function fieldLabel(array $education): string
{
    if (($education['field_of_study'] ?? '') === 'Other' && trim((string) ($education['field_of_study_other'] ?? '')) !== '') {
        return (string) $education['field_of_study_other'];
    }

    return (string) ($education['field_of_study'] ?? 'Not set');
}

function publicFileUrl(?string $path): string
{
    if (!$path) {
        return '';
    }

    return strpos($path, 'uploads/') === 0 ? '../' . $path : $path;
}

function fileLink(?string $path, string $label): string
{
    if (!$path) {
        return '<span class="muted-text">Not uploaded</span>';
    }

    return '<a class="text-link" href="' . e(publicFileUrl($path)) . '" target="_blank" rel="noopener">' . e($label) . '</a>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $result = $controller->updateStatus(
            $appId,
            $companyId,
            (string) ($_POST['status'] ?? ''),
            trim((string) ($_POST['feedback'] ?? '')) ?: null
        );
    } elseif ($action === 'record_score') {
        $scoreValue = $_POST['score'] ?? '';
        $result = is_numeric($scoreValue)
            ? $controller->recordScore($appId, $companyId, (float) $scoreValue, (int) $_SESSION['user_id'])
            : ['success' => false, 'message' => 'Score must be a number between 0 and 100.'];
    } elseif ($action === 'hire') {
        $result = $controller->hire($appId, $companyId, (int) $_SESSION['user_id']);
    } else {
        $result = ['success' => false, 'message' => 'Invalid application action.'];
    }

    if ($result['success']) {
        header('Location: application-detail.php?app_id=' . $appId . '&message=' . urlencode($result['message']));
        exit;
    }

    $error = $result['message'];
}

$application = $controller->viewDetail($appId, $companyId);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Detail | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header panel-header">
            <div>
                <h1>Application Detail</h1>
                <p>Review applicant information, exam score, and hiring status.</p>
            </div>
            <a class="button-outline" href="applications.php<?php echo $application ? '?vacancy_id=' . e((string) $application['vacancy_id']) : ''; ?>">Back to applications</a>
        </header>

        <?php if ($message !== ''): ?>
            <div class="form-alert"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="form-alert" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if (!$application): ?>
            <section class="admin-panel">
                <div class="empty-state">
                    <h3>Application not found</h3>
                    <p>This application is unavailable or does not belong to your company.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="admin-panel" aria-labelledby="applicant-title">
                <div class="admin-section-heading">
                    <h2 id="applicant-title"><?php echo e((string) $application['applicant_name']); ?></h2>
                    <p><?php echo e((string) $application['applicant_email']); ?> - <?php echo e((string) ($application['applicant_phone'] ?? 'No phone provided')); ?></p>
                    <span class="status-tag <?php echo e(statusClass((string) $application['status'])); ?>"><?php echo e(ucfirst((string) $application['status'])); ?></span>
                </div>

                <div class="company-summary">
                    <div><span>Vacancy</span><strong><?php echo e((string) $application['vacancy_title']); ?></strong></div>
                    <div><span>Date of birth</span><strong><?php echo e(detailDate($application['date_of_birth'] ?? null)); ?></strong></div>
                    <div><span>Gender</span><strong><?php echo e((string) ($application['gender'] ?? 'Not set')); ?></strong></div>
                    <div><span>Address</span><strong><?php echo e((string) ($application['address'] ?? 'Not set')); ?></strong></div>
                    <div><span>CV</span><strong><?php echo fileLink($application['cv_file'] ?? null, 'View CV'); ?></strong></div>
                </div>
            </section>

            <section class="admin-panel report-preview" aria-labelledby="cover-title">
                <div class="admin-section-heading">
                    <h2 id="cover-title">Cover Letter</h2>
                </div>
                <p><?php echo nl2br(e((string) $application['cover_letter'])); ?></p>
            </section>

            <section class="admin-panel report-preview" aria-labelledby="education-title">
                <div class="admin-section-heading">
                    <h2 id="education-title">Education</h2>
                </div>
                <div class="approval-list">
                    <?php if ($application['education'] === []): ?>
                        <div class="empty-state"><h3>No education records</h3><p>This applicant has not added education records.</p></div>
                    <?php else: ?>
                        <?php foreach ($application['education'] as $education): ?>
                            <article class="approval-item">
                                <div class="approval-primary">
                                    <p><?php echo e((string) $education['education_level']); ?> - <?php echo e(fieldLabel($education)); ?></p>
                                    <span class="approval-meta"><?php echo e((string) $education['institution']); ?>, <?php echo e((string) $education['year_completed']); ?></span>
                                    <span><?php echo fileLink($education['proof_file'] ?? null, 'View proof'); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-panel report-preview" aria-labelledby="experience-title">
                <div class="admin-section-heading">
                    <h2 id="experience-title">Experience</h2>
                </div>
                <div class="approval-list">
                    <?php if ($application['experience'] === []): ?>
                        <div class="empty-state"><h3>No experience records</h3><p>This applicant has not added experience records.</p></div>
                    <?php else: ?>
                        <?php foreach ($application['experience'] as $experience): ?>
                            <article class="approval-item">
                                <div class="approval-primary">
                                    <p><?php echo e((string) $experience['job_title']); ?> at <?php echo e((string) $experience['company_name']); ?></p>
                                    <span class="approval-meta"><?php echo e(detailDate($experience['start_date'] ?? null)); ?> to <?php echo !empty($experience['is_current']) ? 'Present' : e(detailDate($experience['end_date'] ?? null)); ?></span>
                                    <span><?php echo e((string) ($experience['description'] ?? '')); ?></span>
                                    <span><?php echo fileLink($experience['proof_file'] ?? null, 'View proof'); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-panel report-preview" aria-labelledby="skills-title">
                <div class="admin-section-heading">
                    <h2 id="skills-title">Skills</h2>
                </div>
                <?php if ($application['skills'] === []): ?>
                    <div class="empty-state"><h3>No skills listed</h3><p>This applicant has not added skills.</p></div>
                <?php else: ?>
                    <div class="skills-group">
                        <?php foreach ($application['skills'] as $skill): ?>
                            <span class="skill-tag"><?php echo e((string) $skill['skill_name']); ?> - <?php echo e((string) $skill['skill_level']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="admin-panel report-preview" aria-labelledby="actions-title">
                <div class="admin-section-heading">
                    <h2 id="actions-title">Actions</h2>
                    <p>Current status: <span class="status-tag <?php echo e(statusClass((string) $application['status'])); ?>"><?php echo e(ucfirst((string) $application['status'])); ?></span></p>
                </div>

                <?php if ($application['status'] === 'applied'): ?>
                    <div class="approval-actions">
                        <form method="post">
                            <input type="hidden" name="app_id" value="<?php echo e((string) $appId); ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="status" value="shortlisted">
                            <button class="button-primary" type="submit">Shortlist</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="app_id" value="<?php echo e((string) $appId); ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="status" value="rejected">
                            <button class="button-outline reject" type="submit">Reject</button>
                        </form>
                    </div>
                <?php elseif ($application['status'] === 'shortlisted'): ?>
                    <form class="report-filter-form" method="post">
                        <input type="hidden" name="app_id" value="<?php echo e((string) $appId); ?>">
                        <input type="hidden" name="action" value="record_score">
                        <div class="form-field">
                            <label for="score">Exam score out of 100</label>
                            <input type="number" id="score" name="score" min="0" max="100" step="0.01" value="<?php echo $application['exam_score'] !== null ? e((string) $application['exam_score']) : ''; ?>" required>
                        </div>
                        <button class="button-primary" type="submit">Save score</button>
                    </form>
                    <div class="approval-actions report-actions">
                        <form method="post">
                            <input type="hidden" name="app_id" value="<?php echo e((string) $appId); ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="status" value="rejected">
                            <button class="button-outline reject" type="submit">Reject</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="app_id" value="<?php echo e((string) $appId); ?>">
                            <input type="hidden" name="action" value="hire">
                            <button class="button-primary" type="submit">Hire</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Closed record</h3>
                        <p>This application is already <?php echo e((string) $application['status']); ?>.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
