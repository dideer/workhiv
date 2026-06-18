<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Profile.php';
require_once __DIR__ . '/../src/Models/Education.php';
require_once __DIR__ . '/../src/Models/Experience.php';
require_once __DIR__ . '/../src/Models/Skill.php';

Session::start();

if (!in_array(($_SESSION['role'] ?? ''), ['job_seeker', 'employee'], true)) {
    header('Location: login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function uploadProfileFile(array $file, array $allowedExtensions, int $maxBytes = 5242880): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'One uploaded file could not be processed.'];
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return ['path' => null, 'error' => 'Uploaded files must be 5MB or smaller.'];
    }

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['path' => null, 'error' => 'Only ' . implode(', ', $allowedExtensions) . ' files are allowed.'];
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = uniqid('workhive_', true) . '.' . $extension;
    $target = $uploadDir . '/' . $filename;
    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['path' => null, 'error' => 'Uploaded file could not be saved.'];
    }

    return ['path' => 'uploads/' . $filename, 'error' => null];
}

function selected(string $actual, string $expected): string
{
    return $actual === $expected ? 'selected' : '';
}

function fieldLabel(array $education): string
{
    if (($education['field_of_study'] ?? '') === 'Other' && trim((string) ($education['field_of_study_other'] ?? '')) !== '') {
        return (string) $education['field_of_study_other'];
    }

    return (string) ($education['field_of_study'] ?? 'Not set');
}

function fileLink(?string $path, string $label): string
{
    if (!$path) {
        return '<span class="muted-text">Not uploaded</span>';
    }

    return '<a class="text-link" href="' . e($path) . '" target="_blank" rel="noopener">' . e($label . ' (' . basename($path) . ')') . '</a>';
}

$userId = (int) $_SESSION['user_id'];
$profileModel = new Profile();
$educationModel = new Education();
$experienceModel = new Experience();
$skillModel = new Skill();
$fieldOptions = ['Computer Science', 'Business', 'Engineering', 'Health Sciences', 'Education', 'Agriculture', 'Law', 'Arts & Social Sciences', 'Other'];
$educationLevels = ['Primary', 'Secondary', 'Certificate', 'Diploma', 'Bachelor', 'Masters', 'PhD'];
$skillLevels = ['Beginner', 'Intermediate', 'Advanced'];
$message = (string) ($_GET['message'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $profile = $profileModel->findByUserId($userId);
        $photo = uploadProfileFile($_FILES['profile_photo'] ?? [], ['jpg', 'jpeg', 'png']);
        $cv = uploadProfileFile($_FILES['cv_file'] ?? [], ['pdf']);
        $error = $photo['error'] ?: ($cv['error'] ?? '');

        if ($error === '') {
            $profileModel->update($userId, [
                'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')),
                'gender' => trim((string) ($_POST['gender'] ?? '')),
                'address' => trim((string) ($_POST['address'] ?? '')),
                'profile_photo' => $photo['path'] ?? ($profile['profile_photo'] ?? null),
                'cv_file' => $cv['path'] ?? ($profile['cv_file'] ?? null),
            ]);
            header('Location: my-profile.php?message=' . urlencode('Profile updated.'));
            exit;
        }
    }

    if ($action === 'update_education') {
        $field = trim((string) ($_POST['field_of_study'] ?? ''));
        $proof = uploadProfileFile($_FILES['proof_file'] ?? [], ['pdf', 'jpg', 'jpeg', 'png']);
        $error = $proof['error'] ?? '';
        if ($error === '') {
            $educationModel->update((int) ($_POST['education_id'] ?? 0), $userId, [
                'education_level' => trim((string) ($_POST['education_level'] ?? '')),
                'field_of_study' => $field,
                'field_of_study_other' => $field === 'Other' ? trim((string) ($_POST['field_of_study_other'] ?? '')) : null,
                'institution' => trim((string) ($_POST['institution'] ?? '')),
                'year_completed' => (int) ($_POST['year_completed'] ?? 0),
                'proof_file' => $proof['path'],
            ]);
            header('Location: my-profile.php?message=' . urlencode('Education updated.'));
            exit;
        }
    }

    if ($action === 'delete_education') {
        $educationModel->delete((int) ($_POST['education_id'] ?? 0), $userId);
        header('Location: my-profile.php?message=' . urlencode('Education deleted.'));
        exit;
    }

    if ($action === 'update_experience') {
        $proof = uploadProfileFile($_FILES['proof_file'] ?? [], ['pdf', 'jpg', 'jpeg', 'png']);
        $error = $proof['error'] ?? '';
        if ($error === '') {
            $experienceModel->update((int) ($_POST['experience_id'] ?? 0), $userId, [
                'company_name' => trim((string) ($_POST['company_name'] ?? '')),
                'job_title' => trim((string) ($_POST['job_title'] ?? '')),
                'start_date' => trim((string) ($_POST['start_date'] ?? '')),
                'end_date' => trim((string) ($_POST['end_date'] ?? '')),
                'is_current' => isset($_POST['is_current']),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'proof_file' => $proof['path'],
            ]);
            header('Location: my-profile.php?message=' . urlencode('Experience updated.'));
            exit;
        }
    }

    if ($action === 'delete_experience') {
        $experienceModel->delete((int) ($_POST['experience_id'] ?? 0), $userId);
        header('Location: my-profile.php?message=' . urlencode('Experience deleted.'));
        exit;
    }

    if ($action === 'update_skill') {
        $skillModel->update((int) ($_POST['skill_id'] ?? 0), $userId, [
            'skill_name' => trim((string) ($_POST['skill_name'] ?? '')),
            'skill_level' => trim((string) ($_POST['skill_level'] ?? '')),
            'is_custom' => 1,
        ]);
        header('Location: my-profile.php?message=' . urlencode('Skill updated.'));
        exit;
    }

    if ($action === 'delete_skill') {
        $skillModel->delete((int) ($_POST['skill_id'] ?? 0), $userId);
        header('Location: my-profile.php?message=' . urlencode('Skill deleted.'));
        exit;
    }
}

$profile = $profileModel->findByUserId($userId);
$educationRows = $educationModel->getByUserId($userId);
$experienceRows = $experienceModel->getByUserId($userId);
$skillRows = $skillModel->getByUserId($userId);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/partials/seeker-nav.php'; ?>

    <main class="job-listings-section seeker-page">
        <header class="listings-header">
            <div class="section-heading">
                <p class="section-kicker">Profile</p>
                <h1>My Profile</h1>
                <p>Review and update your employment profile details.</p>
            </div>
        </header>

        <?php if ($message !== ''): ?>
            <div class="form-alert"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="form-alert" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <section class="profile-card profile-section-card">
            <div class="panel-header">
                <h2>Profile</h2>
                <button class="button-outline" type="button" data-toggle-edit="profile-edit">Edit</button>
            </div>
            <?php if (!$profile): ?>
                <div class="empty-state">
                    <h3>No profile details yet</h3>
                    <p>Complete your profile before applying for jobs.</p>
                    <a class="button-primary" href="complete-profile.php">Complete profile</a>
                </div>
            <?php else: ?>
                <div class="profile-detail-grid">
                    <div><span>Date of birth</span><strong><?php echo e((string) $profile['date_of_birth']); ?></strong></div>
                    <div><span>Gender</span><strong><?php echo e((string) $profile['gender']); ?></strong></div>
                    <div><span>Address</span><strong><?php echo e((string) $profile['address']); ?></strong></div>
                    <div><span>CV</span><?php echo fileLink($profile['cv_file'] ?? null, 'View CV'); ?></div>
                </div>
                <?php if (!empty($profile['profile_photo'])): ?>
                    <img class="profile-thumb" src="<?php echo e((string) $profile['profile_photo']); ?>" alt="Profile photo">
                <?php endif; ?>
                <form class="profile-form edit-panel" id="profile-edit" method="post" enctype="multipart/form-data" hidden>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Date of birth</label>
                            <input type="date" name="date_of_birth" value="<?php echo e((string) $profile['date_of_birth']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label>Gender</label>
                            <select name="gender" required>
                                <?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?>
                                    <option value="<?php echo e($option); ?>" <?php echo selected((string) $profile['gender'], $option); ?>><?php echo e($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field full-span">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo e((string) $profile['address']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label>Profile photo</label>
                            <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png">
                        </div>
                        <div class="form-field">
                            <label>CV file</label>
                            <input type="file" name="cv_file" accept=".pdf">
                        </div>
                    </div>
                    <button class="button-primary" type="submit">Save profile</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="profile-card profile-section-card">
            <h2>Education</h2>
            <?php if ($educationRows === []): ?>
                <div class="empty-state"><h3>No education records yet</h3><p>Education entries will appear here after you complete your profile.</p></div>
            <?php else: ?>
                <?php foreach ($educationRows as $row): ?>
                    <article class="profile-entry">
                        <div class="panel-header">
                            <div>
                                <h3><?php echo e((string) $row['education_level']); ?></h3>
                                <p><?php echo e(fieldLabel($row)); ?> at <?php echo e((string) $row['institution']); ?>, <?php echo e((string) $row['year_completed']); ?></p>
                                <p><?php echo fileLink($row['proof_file'] ?? null, 'View proof'); ?></p>
                            </div>
                            <button class="button-outline" type="button" data-toggle-edit="education-<?php echo e((string) $row['education_id']); ?>">Edit</button>
                        </div>
                        <form class="profile-form edit-panel" id="education-<?php echo e((string) $row['education_id']); ?>" method="post" enctype="multipart/form-data" hidden>
                            <input type="hidden" name="action" value="update_education">
                            <input type="hidden" name="education_id" value="<?php echo e((string) $row['education_id']); ?>">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Education level</label>
                                    <select name="education_level" required>
                                        <?php foreach ($educationLevels as $level): ?>
                                            <option value="<?php echo e($level); ?>" <?php echo selected((string) $row['education_level'], $level); ?>><?php echo e($level); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Field of study</label>
                                    <select name="field_of_study" data-field-of-study required>
                                        <?php foreach ($fieldOptions as $field): ?>
                                            <option value="<?php echo e($field); ?>" <?php echo selected((string) $row['field_of_study'], $field); ?>><?php echo e($field); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field" data-field-other-wrap <?php echo ($row['field_of_study'] ?? '') === 'Other' ? '' : 'hidden'; ?>>
                                    <label>Please specify</label>
                                    <input type="text" name="field_of_study_other" value="<?php echo e((string) ($row['field_of_study_other'] ?? '')); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Institution</label>
                                    <input type="text" name="institution" value="<?php echo e((string) $row['institution']); ?>" required>
                                </div>
                                <div class="form-field">
                                    <label>Year completed</label>
                                    <input type="number" name="year_completed" min="1950" max="2100" value="<?php echo e((string) $row['year_completed']); ?>" required>
                                </div>
                                <div class="form-field full-span">
                                    <label>Proof file</label>
                                    <input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button class="button-primary" type="submit">Save education</button>
                            </div>
                        </form>
                        <form method="post" onsubmit="return confirm('Delete this education entry?');">
                            <input type="hidden" name="action" value="delete_education">
                            <input type="hidden" name="education_id" value="<?php echo e((string) $row['education_id']); ?>">
                            <button class="button-outline reject" type="submit">Delete</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="profile-card profile-section-card">
            <h2>Experience</h2>
            <?php if ($experienceRows === []): ?>
                <div class="empty-state"><h3>No experience records yet</h3><p>Experience entries will appear here after you complete your profile.</p></div>
            <?php else: ?>
                <?php foreach ($experienceRows as $row): ?>
                    <article class="profile-entry">
                        <div class="panel-header">
                            <div>
                                <h3><?php echo e((string) $row['job_title']); ?></h3>
                                <p><?php echo e((string) $row['company_name']); ?>, <?php echo e((string) $row['start_date']); ?> to <?php echo !empty($row['is_current']) ? 'Present' : e((string) $row['end_date']); ?></p>
                                <p><?php echo e((string) ($row['description'] ?? '')); ?></p>
                                <p><?php echo fileLink($row['proof_file'] ?? null, 'View proof'); ?></p>
                            </div>
                            <button class="button-outline" type="button" data-toggle-edit="experience-<?php echo e((string) $row['experience_id']); ?>">Edit</button>
                        </div>
                        <form class="profile-form edit-panel" id="experience-<?php echo e((string) $row['experience_id']); ?>" method="post" enctype="multipart/form-data" hidden>
                            <input type="hidden" name="action" value="update_experience">
                            <input type="hidden" name="experience_id" value="<?php echo e((string) $row['experience_id']); ?>">
                            <div class="form-grid">
                                <div class="form-field"><label>Company</label><input type="text" name="company_name" value="<?php echo e((string) $row['company_name']); ?>" required></div>
                                <div class="form-field"><label>Job title</label><input type="text" name="job_title" value="<?php echo e((string) $row['job_title']); ?>" required></div>
                                <div class="form-field"><label>Start date</label><input type="date" name="start_date" value="<?php echo e((string) $row['start_date']); ?>" required></div>
                                <div class="form-field"><label>End date</label><input type="date" name="end_date" value="<?php echo e((string) ($row['end_date'] ?? '')); ?>" data-end-date></div>
                                <label class="checkbox-field full-span"><input type="checkbox" name="is_current" data-current-role <?php echo !empty($row['is_current']) ? 'checked' : ''; ?>><span>Currently working here</span></label>
                                <div class="form-field full-span"><label>Description</label><textarea name="description" rows="4"><?php echo e((string) ($row['description'] ?? '')); ?></textarea></div>
                                <div class="form-field full-span"><label>Proof file</label><input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                            </div>
                            <button class="button-primary" type="submit">Save experience</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Delete this experience entry?');">
                            <input type="hidden" name="action" value="delete_experience">
                            <input type="hidden" name="experience_id" value="<?php echo e((string) $row['experience_id']); ?>">
                            <button class="button-outline reject" type="submit">Delete</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="profile-card profile-section-card">
            <h2>Skills</h2>
            <?php if ($skillRows === []): ?>
                <div class="empty-state"><h3>No skills yet</h3><p>Skills will appear here after you complete your profile.</p></div>
            <?php else: ?>
                <div class="skills-group">
                    <?php foreach ($skillRows as $row): ?>
                        <article class="profile-entry skill-entry">
                            <span class="skill-tag"><?php echo e((string) $row['skill_name']); ?> - <?php echo e((string) $row['skill_level']); ?></span>
                            <button class="button-outline" type="button" data-toggle-edit="skill-<?php echo e((string) $row['skill_id']); ?>">Edit</button>
                            <form class="profile-form edit-panel" id="skill-<?php echo e((string) $row['skill_id']); ?>" method="post" hidden>
                                <input type="hidden" name="action" value="update_skill">
                                <input type="hidden" name="skill_id" value="<?php echo e((string) $row['skill_id']); ?>">
                                <div class="form-grid">
                                    <div class="form-field"><label>Skill</label><input type="text" name="skill_name" value="<?php echo e((string) $row['skill_name']); ?>" required></div>
                                    <div class="form-field"><label>Level</label><select name="skill_level" required><?php foreach ($skillLevels as $level): ?><option value="<?php echo e($level); ?>" <?php echo selected((string) $row['skill_level'], $level); ?>><?php echo e($level); ?></option><?php endforeach; ?></select></div>
                                </div>
                                <button class="button-primary" type="submit">Save skill</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Delete this skill?');">
                                <input type="hidden" name="action" value="delete_skill">
                                <input type="hidden" name="skill_id" value="<?php echo e((string) $row['skill_id']); ?>">
                                <button class="button-outline reject" type="submit">Delete</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        document.querySelectorAll("[data-toggle-edit]").forEach((button) => {
            button.addEventListener("click", () => {
                const panel = document.getElementById(button.dataset.toggleEdit);
                if (panel) panel.hidden = !panel.hidden;
            });
        });

        document.addEventListener("change", (event) => {
            if (event.target.matches("[data-field-of-study]")) {
                const form = event.target.closest("form");
                const wrap = form.querySelector("[data-field-other-wrap]");
                if (!wrap) return;
                wrap.hidden = event.target.value !== "Other";
                if (wrap.hidden) wrap.querySelector("input").value = "";
            }

            if (event.target.matches("[data-current-role]")) {
                const form = event.target.closest("form");
                const endDate = form.querySelector("[data-end-date]");
                if (!endDate) return;
                endDate.disabled = event.target.checked;
                if (event.target.checked) endDate.value = "";
            }
        });
    </script>
</body>
</html>
