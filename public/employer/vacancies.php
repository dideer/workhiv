<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/VacancyController.php';
require_once __DIR__ . '/../../src/Models/Vacancy.php';

$activePage = 'vacancies';
$vacancyModel = new Vacancy();
$controller = new VacancyController();
$message = '';
$error = '';
$editVacancy = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $controller->postVacancy($companyId, $_POST);
    } elseif ($action === 'update') {
        $result = $controller->updateVacancy((int) ($_POST['vacancy_id'] ?? 0), $companyId, $_POST);
    } elseif ($action === 'close') {
        $result = $controller->closeVacancy((int) ($_POST['vacancy_id'] ?? 0), $companyId);
    } else {
        $result = ['success' => false, 'message' => 'Invalid vacancy action.'];
    }

    if ($result['success']) {
        header('Location: vacancies.php?message=' . urlencode($result['message']));
        exit;
    }

    $error = $result['message'];
}

if (isset($_GET['edit'])) {
    $candidate = $vacancyModel->getById((int) $_GET['edit']);
    if ($candidate && (int) $candidate['company_id'] === $companyId) {
        $editVacancy = $candidate;
    }
}

$message = (string) ($_GET['message'] ?? '');
$vacancies = $vacancyModel->getByCompany($companyId);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Vacancies | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header panel-header">
            <div>
                <h1>My Vacancies</h1>
                <p>Post vacancies, update requirements, and monitor applications.</p>
            </div>
            <a class="button-primary" href="#vacancy-form"><?php echo $editVacancy ? 'Editing vacancy' : 'Post a vacancy'; ?></a>
        </header>

        <?php if ($message !== ''): ?>
            <div class="form-alert"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="form-alert" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <section class="admin-panel" id="vacancy-form" aria-labelledby="vacancy-form-title">
            <div class="admin-section-heading">
                <h2 id="vacancy-form-title"><?php echo $editVacancy ? 'Edit vacancy' : 'Post a vacancy'; ?></h2>
                <p>New vacancies are published immediately after submission.</p>
            </div>

            <form class="report-filter-form vacancy-form" method="post">
                <input type="hidden" name="action" value="<?php echo $editVacancy ? 'update' : 'create'; ?>">
                <?php if ($editVacancy): ?>
                    <input type="hidden" name="vacancy_id" value="<?php echo e((string) $editVacancy['vacancy_id']); ?>">
                <?php endif; ?>

                <div class="form-field">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo e((string) ($editVacancy['title'] ?? '')); ?>" required>
                </div>
                <div class="form-field">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo e((string) ($editVacancy['location'] ?? '')); ?>" required>
                </div>
                <div class="form-field">
                    <label for="salary-range">Salary range</label>
                    <input type="text" id="salary-range" name="salary_range" value="<?php echo e((string) ($editVacancy['salary_range'] ?? '')); ?>" required>
                </div>
                <div class="form-field">
                    <label for="deadline">Deadline</label>
                    <input type="date" id="deadline" name="deadline" value="<?php echo e((string) ($editVacancy['deadline'] ?? '')); ?>" required>
                </div>
                <div class="form-field full-span">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required><?php echo e((string) ($editVacancy['description'] ?? '')); ?></textarea>
                </div>
                <div class="form-field">
                    <label for="education-level">Education level</label>
                    <select id="education-level" name="education_level" required>
                        <option value="">Select level</option>
                        <?php foreach (['Primary', 'Secondary', 'Certificate', 'Diploma', 'Bachelor', 'Masters', 'PhD'] as $level): ?>
                            <option value="<?php echo e($level); ?>" <?php echo (($editVacancy['education_level'] ?? '') === $level) ? 'selected' : ''; ?>><?php echo e($level); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="field-of-study">Field of study</label>
                    <select id="field-of-study" name="field_of_study">
                        <option value="">Select field</option>
                        <?php foreach (['Computer Science', 'Business', 'Engineering', 'Health Sciences', 'Education', 'Agriculture', 'Law', 'Arts & Social Sciences', 'Other'] as $field): ?>
                            <option value="<?php echo e($field); ?>" <?php echo (($editVacancy['field_of_study'] ?? '') === $field) ? 'selected' : ''; ?>><?php echo e($field); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="min-experience">Minimum experience years</label>
                    <input type="number" id="min-experience" name="min_experience_years" min="0" value="<?php echo e((string) ($editVacancy['min_experience_years'] ?? '0')); ?>" required>
                </div>
                <div class="form-field full-span">
                    <label for="skills-required">Skills required</label>
                    <input type="text" id="skills-required" name="skills_required" value="<?php echo e((string) ($editVacancy['skills_required'] ?? '')); ?>" placeholder="Excel, reporting, customer service" required>
                </div>
                <div class="form-field full-span">
                    <label for="other-requirements">Other requirements</label>
                    <textarea id="other-requirements" name="other_requirements" rows="4"><?php echo e((string) ($editVacancy['other_requirements'] ?? '')); ?></textarea>
                </div>
                <button class="button-primary" type="submit"><?php echo $editVacancy ? 'Update vacancy' : 'Submit vacancy'; ?></button>
            </form>
        </section>

        <section class="admin-panel report-preview" aria-labelledby="vacancies-title">
            <div class="admin-section-heading">
                <h2 id="vacancies-title">Vacancies</h2>
                <p>Your company vacancies and their current review status.</p>
            </div>

            <div class="approval-list">
                <?php if ($vacancies === []): ?>
                    <div class="empty-state">
                        <h3>No vacancies yet</h3>
                        <p>Post your first vacancy to start receiving applications.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vacancies as $vacancy): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e($vacancy['title']); ?></p>
                                <span class="approval-meta">Deadline <?php echo e(formatDate($vacancy['deadline'])); ?> · <?php echo e((string) $vacancy['application_count']); ?> applications</span>
                                <span class="status-tag <?php echo e(statusClass($vacancy['status'])); ?>"><?php echo e(ucfirst($vacancy['status'])); ?></span>
                            </div>
                            <div class="approval-actions">
                                <a class="button-outline" href="vacancies.php?edit=<?php echo e((string) $vacancy['vacancy_id']); ?>#vacancy-form">Edit</a>
                                <?php if ($vacancy['status'] === 'active'): ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="close">
                                        <input type="hidden" name="vacancy_id" value="<?php echo e((string) $vacancy['vacancy_id']); ?>">
                                        <button class="button-outline reject" type="submit">Close</button>
                                    </form>
                                <?php endif; ?>
                                <a class="button-primary" href="applications.php?vacancy_id=<?php echo e((string) $vacancy['vacancy_id']); ?>">View applications</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
