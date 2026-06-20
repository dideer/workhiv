<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/VacancyController.php';
require_once __DIR__ . '/../../src/Models/Vacancy.php';

$activePage = 'vacancies';
$vacancyModel = new Vacancy();
$controller = new VacancyController();
$message = '';
$error = '';
$educationLevels = ['Primary', 'Secondary', 'Certificate', 'Diploma', 'Bachelor', 'Masters', 'PhD'];
$fieldsOfStudy = ['Computer Science', 'Business', 'Engineering', 'Health Sciences', 'Education', 'Agriculture', 'Law', 'Arts & Social Sciences', 'Other'];

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
    <style>
        #vacancy-modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            z-index: 1000 !important;
            display: none;
            width: 100% !important;
            height: 100% !important;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(21, 35, 30, 0.6);
            overflow-y: auto;
        }

        #vacancy-modal.is-open {
            display: flex !important;
        }

        #vacancy-modal .vacancy-modal-backdrop {
            position: absolute;
            inset: 0;
        }

        #vacancy-modal .vacancy-modal-card {
            position: relative;
            z-index: 1;
            width: min(600px, 100%);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            padding: 40px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--white);
            box-shadow: 0 24px 70px rgba(21, 35, 30, 0.28);
        }
    </style>
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header panel-header">
            <div>
                <h1>My Vacancies</h1>
                <p>Post vacancies, update requirements, and monitor applications.</p>
            </div>
            <button class="button-primary" type="button" data-open-vacancy-modal>+ Post a vacancy</button>
        </header>

        <?php if ($message !== ''): ?>
            <div class="form-alert"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="form-alert" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <section class="admin-panel report-preview" aria-labelledby="vacancies-title">
            <div class="admin-section-heading">
                <h2 id="vacancies-title">Vacancies</h2>
                <p>Your company vacancies and their current status.</p>
            </div>

            <div class="approval-list">
                <?php if ($vacancies === []): ?>
                    <div class="empty-state">
                        <h3>No vacancies yet</h3>
                        <p>Post your first vacancy to start receiving applications.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vacancies as $vacancy): ?>
                        <?php
                        $hiredCount = (int) ($vacancy['hired_count'] ?? 0);
                        $numberOfPosts = max(1, (int) ($vacancy['number_of_posts'] ?? 1));
                        ?>
                        <article class="approval-item"
                            data-vacancy-card
                            data-vacancy-id="<?php echo e((string) $vacancy['vacancy_id']); ?>"
                            data-title="<?php echo e((string) $vacancy['title']); ?>"
                            data-number-of-posts="<?php echo e((string) $numberOfPosts); ?>"
                            data-description="<?php echo e((string) ($vacancy['description'] ?? '')); ?>"
                            data-location="<?php echo e((string) ($vacancy['location'] ?? '')); ?>"
                            data-salary-range="<?php echo e((string) ($vacancy['salary_range'] ?? '')); ?>"
                            data-deadline="<?php echo e((string) ($vacancy['deadline'] ?? '')); ?>"
                            data-education-level="<?php echo e((string) ($vacancy['education_level'] ?? '')); ?>"
                            data-field-of-study="<?php echo e((string) ($vacancy['field_of_study'] ?? '')); ?>"
                            data-min-experience-years="<?php echo e((string) ($vacancy['min_experience_years'] ?? '0')); ?>"
                            data-skills-required="<?php echo e((string) ($vacancy['skills_required'] ?? '')); ?>"
                            data-other-requirements="<?php echo e((string) ($vacancy['other_requirements'] ?? '')); ?>">
                            <div class="approval-primary">
                                <p><?php echo e((string) $vacancy['title']); ?></p>
                                <span class="approval-meta"><?php echo e((string) $numberOfPosts); ?> position<?php echo $numberOfPosts === 1 ? '' : 's'; ?> - Deadline <?php echo e(formatDate($vacancy['deadline'])); ?></span>
                                <span class="approval-meta"><?php echo e((string) $hiredCount); ?> of <?php echo e((string) $numberOfPosts); ?> filled</span>
                                <span class="status-tag <?php echo e(statusClass((string) $vacancy['status'])); ?>"><?php echo e(ucfirst((string) $vacancy['status'])); ?></span>
                            </div>
                            <div class="approval-actions">
                                <button class="button-outline" type="button" data-edit-vacancy>Edit</button>
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

    <div class="vacancy-modal" id="vacancy-modal" aria-hidden="true">
        <div class="vacancy-modal-backdrop" data-close-vacancy-modal></div>
        <section class="vacancy-modal-card" role="dialog" aria-modal="true" aria-labelledby="vacancy-modal-title">
            <button class="vacancy-modal-close" type="button" aria-label="Close modal" data-close-vacancy-modal>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6.4 5 12.6 12.6-1.4 1.4L5 6.4 6.4 5Zm12.6 1.4L6.4 19 5 17.6 17.6 5 19 6.4Z"></path></svg>
            </button>
            <h2 id="vacancy-modal-title">Post a vacancy</h2>
            <form class="profile-form vacancy-modal-form" method="post">
                <input type="hidden" name="action" value="create" data-vacancy-action>
                <input type="hidden" name="vacancy_id" value="" data-vacancy-id-field>

                <div class="form-grid">
                    <div class="form-field full-span">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-field">
                        <label for="number-of-posts">Number of positions</label>
                        <input type="number" id="number-of-posts" name="number_of_posts" min="1" value="1" required>
                    </div>
                    <div class="form-field">
                        <label for="deadline">Deadline</label>
                        <input type="date" id="deadline" name="deadline" required>
                    </div>
                    <div class="form-field full-span">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>
                    <div class="form-field">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    <div class="form-field">
                        <label for="salary-range">Salary range</label>
                        <input type="text" id="salary-range" name="salary_range" required>
                    </div>
                </div>

                <div class="profile-form-section">
                    <h3>Requirements</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="education-level">Education level</label>
                            <select id="education-level" name="education_level" required>
                                <option value="">Select level</option>
                                <?php foreach ($educationLevels as $level): ?>
                                    <option value="<?php echo e($level); ?>"><?php echo e($level); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="field-of-study">Field of study</label>
                            <select id="field-of-study" name="field_of_study">
                                <option value="">Select field</option>
                                <?php foreach ($fieldsOfStudy as $field): ?>
                                    <option value="<?php echo e($field); ?>"><?php echo e($field); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="min-experience">Minimum experience years</label>
                            <input type="number" id="min-experience" name="min_experience_years" min="0" value="0" required>
                        </div>
                        <div class="form-field full-span">
                            <label for="skills-required">Skills required</label>
                            <input type="text" id="skills-required" name="skills_required" placeholder="Excel, reporting, customer service" required>
                        </div>
                        <div class="form-field full-span">
                            <label for="other-requirements">Other requirements</label>
                            <textarea id="other-requirements" name="other_requirements" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <button class="button-primary vacancy-submit" type="submit">Post vacancy</button>
            </form>
        </section>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('vacancy-modal');
            const form = modal.querySelector('form');
            const title = document.getElementById('vacancy-modal-title');
            const action = form.querySelector('[data-vacancy-action]');
            const vacancyId = form.querySelector('[data-vacancy-id-field]');
            const submit = form.querySelector('.vacancy-submit');

            const fields = {
                title: form.elements.title,
                numberOfPosts: form.elements.number_of_posts,
                description: form.elements.description,
                location: form.elements.location,
                salaryRange: form.elements.salary_range,
                deadline: form.elements.deadline,
                educationLevel: form.elements.education_level,
                fieldOfStudy: form.elements.field_of_study,
                minExperienceYears: form.elements.min_experience_years,
                skillsRequired: form.elements.skills_required,
                otherRequirements: form.elements.other_requirements,
            };

            function openModal(mode, data = {}) {
                form.reset();
                action.value = mode === 'edit' ? 'update' : 'create';
                vacancyId.value = data.vacancyId || '';
                title.textContent = mode === 'edit' ? 'Edit vacancy' : 'Post a vacancy';
                submit.textContent = mode === 'edit' ? 'Update vacancy' : 'Post vacancy';

                if (mode === 'edit') {
                    Object.entries(fields).forEach(([key, field]) => {
                        field.value = data[key] || (key === 'numberOfPosts' ? '1' : key === 'minExperienceYears' ? '0' : '');
                    });
                } else {
                    fields.numberOfPosts.value = '1';
                    fields.minExperienceYears.value = '0';
                }

                modal.classList.add('is-open');
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                fields.title.focus();
            }

            function closeModal() {
                modal.classList.remove('is-open');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            }

            document.querySelector('[data-open-vacancy-modal]').addEventListener('click', () => openModal('create'));
            document.querySelectorAll('[data-edit-vacancy]').forEach((button) => {
                button.addEventListener('click', () => {
                    openModal('edit', button.closest('[data-vacancy-card]').dataset);
                });
            });
            modal.querySelectorAll('[data-close-vacancy-modal]').forEach((item) => item.addEventListener('click', closeModal));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
