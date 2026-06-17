<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Controllers/ProfileController.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'job_seeker') {
    header('Location: login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function postValue(string $key): string
{
    return e((string) ($_POST[$key] ?? ''));
}

function uploadFile(array $file, array $allowedExtensions, int $maxBytes = 5242880): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'One of the uploaded files could not be processed.'];
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
        return ['path' => null, 'error' => 'Uploaded files could not be saved.'];
    }

    return ['path' => 'uploads/' . $filename, 'error' => null];
}

function uploadIndexed(string $field, int $index, array $allowedExtensions): array
{
    if (!isset($_FILES[$field]['name'][$index])) {
        return ['path' => null, 'error' => null];
    }

    return uploadFile([
        'name' => $_FILES[$field]['name'][$index],
        'type' => $_FILES[$field]['type'][$index],
        'tmp_name' => $_FILES[$field]['tmp_name'][$index],
        'error' => $_FILES[$field]['error'][$index],
        'size' => $_FILES[$field]['size'][$index],
    ], $allowedExtensions);
}

$error = '';
$gender = $_POST['gender'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['date_of_birth', 'gender', 'address'];
    foreach ($required as $field) {
        if (trim((string) ($_POST[$field] ?? '')) === '') {
            $error = 'Please complete the required profile fields.';
            break;
        }
    }

    $photoUpload = ['path' => null, 'error' => null];
    if ($error === '') {
        $photoUpload = uploadFile($_FILES['profile_photo'] ?? [], ['jpg', 'jpeg', 'png']);
        $error = $photoUpload['error'] ?? '';
    }

    $education = $_POST['education'] ?? [];
    $experience = $_POST['experience'] ?? [];
    $skills = $_POST['skills'] ?? [];

    if ($error === '') {
        foreach ($education as $index => $entry) {
            $proof = uploadIndexed('education_proof', (int) $index, ['pdf', 'jpg', 'jpeg', 'png']);
            if ($proof['error']) {
                $error = $proof['error'];
                break;
            }
            $education[$index]['proof_file'] = $proof['path'];
        }
    }

    if ($error === '') {
        foreach ($experience as $index => $entry) {
            $proof = uploadIndexed('experience_proof', (int) $index, ['pdf', 'jpg', 'jpeg', 'png']);
            if ($proof['error']) {
                $error = $proof['error'];
                break;
            }
            $experience[$index]['proof_file'] = $proof['path'];
            $experience[$index]['is_current'] = isset($entry['is_current']);
        }
    }

    if ($error === '') {
        $controller = new ProfileController();
        $result = $controller->saveJobSeekerProfile((int) $_SESSION['user_id'], [
            'date_of_birth' => trim((string) $_POST['date_of_birth']),
            'gender' => trim((string) $_POST['gender']),
            'address' => trim((string) $_POST['address']),
            'profile_photo' => $photoUpload['path'],
            'education' => $education,
            'experience' => $experience,
            'skills' => $skills,
        ]);

        if ($result['success']) {
            header('Location: seeker-dashboard.php');
            exit;
        }

        $error = $result['message'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete Profile | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Profile navigation">
            <a class="site-logo" href="index.html" aria-label="WorkHive home">WorkHive</a>
            <div class="nav-actions">
                <a class="nav-button nav-button-secondary" href="logout.php">Log out</a>
            </div>
        </nav>
    </header>

    <main class="profile-main">
        <section class="profile-card" aria-labelledby="profile-title">
            <p class="section-kicker">Required step</p>
            <h1 id="profile-title">Complete your profile</h1>
            <p>Provide your profile, education, experience, and skills before continuing.</p>

            <?php if ($error !== ''): ?>
                <div class="form-alert" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form class="profile-form" method="post" enctype="multipart/form-data">
                <section class="profile-form-section">
                    <h2>Profile</h2>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="date-of-birth">Date of birth</label>
                            <input type="date" id="date-of-birth" name="date_of_birth" value="<?php echo postValue('date_of_birth'); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select gender</option>
                                <?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?>
                                    <option value="<?php echo e($option); ?>" <?php echo $gender === $option ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field full-span">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo postValue('address'); ?>" required>
                        </div>
                        <div class="form-field full-span">
                            <label for="profile-photo">Profile photo</label>
                            <input type="file" id="profile-photo" name="profile_photo" accept=".jpg,.jpeg,.png">
                        </div>
                    </div>
                </section>

                <section class="profile-form-section">
                    <div class="repeat-heading">
                        <h2>Education</h2>
                        <button class="button-outline" type="button" data-add-block="education">Add another education</button>
                    </div>
                    <div class="repeat-list" id="education-list">
                        <div class="repeat-block" data-repeat-block="education">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Education level</label>
                                    <select name="education[0][education_level]">
                                        <option value="">Select level</option>
                                        <?php foreach (['Primary', 'Secondary', 'Certificate', 'Diploma', 'Bachelor', 'Masters', 'PhD'] as $level): ?>
                                            <option value="<?php echo e($level); ?>"><?php echo e($level); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Field of study</label>
                                    <input type="text" name="education[0][field_of_study]">
                                </div>
                                <div class="form-field">
                                    <label>Institution</label>
                                    <input type="text" name="education[0][institution]">
                                </div>
                                <div class="form-field">
                                    <label>Year completed</label>
                                    <input type="number" name="education[0][year_completed]" min="1950" max="2100">
                                </div>
                                <div class="form-field full-span">
                                    <label>Proof file</label>
                                    <input type="file" name="education_proof[]" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="profile-form-section">
                    <div class="repeat-heading">
                        <h2>Experience</h2>
                        <button class="button-outline" type="button" data-add-block="experience">Add another experience</button>
                    </div>
                    <div class="repeat-list" id="experience-list">
                        <div class="repeat-block" data-repeat-block="experience">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Company name</label>
                                    <input type="text" name="experience[0][company_name]">
                                </div>
                                <div class="form-field">
                                    <label>Job title</label>
                                    <input type="text" name="experience[0][job_title]">
                                </div>
                                <div class="form-field">
                                    <label>Start date</label>
                                    <input type="date" name="experience[0][start_date]">
                                </div>
                                <div class="form-field">
                                    <label>End date</label>
                                    <input type="date" name="experience[0][end_date]" data-end-date>
                                </div>
                                <label class="checkbox-field full-span">
                                    <input type="checkbox" name="experience[0][is_current]" data-current-role>
                                    <span>Currently working here</span>
                                </label>
                                <div class="form-field full-span">
                                    <label>Description</label>
                                    <textarea name="experience[0][description]" rows="4"></textarea>
                                </div>
                                <div class="form-field full-span">
                                    <label>Proof file</label>
                                    <input type="file" name="experience_proof[]" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="profile-form-section">
                    <div class="repeat-heading">
                        <h2>Skills</h2>
                        <button class="button-outline" type="button" data-add-block="skills">Add another skill</button>
                    </div>
                    <div class="repeat-list" id="skills-list">
                        <div class="repeat-block" data-repeat-block="skills">
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Skill name</label>
                                    <input type="text" name="skills[0][skill_name]">
                                </div>
                                <div class="form-field">
                                    <label>Skill level</label>
                                    <select name="skills[0][skill_level]">
                                        <option value="">Select level</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <button class="button-primary" type="submit">Save profile</button>
            </form>
        </section>
    </main>

    <script>
        function reindexBlock(block, type, index) {
            block.querySelectorAll("[name]").forEach((field) => {
                field.name = field.name.replace(new RegExp(type + "\\[\\d+\\]"), type + "[" + index + "]");
                if (field.type !== "file" && field.type !== "checkbox") field.value = "";
                if (field.type === "checkbox") field.checked = false;
                if (field.matches("[data-end-date]")) field.disabled = false;
            });
        }

        document.querySelectorAll("[data-add-block]").forEach((button) => {
            button.addEventListener("click", () => {
                const type = button.dataset.addBlock;
                const list = document.querySelector("#" + type + "-list");
                const clone = list.querySelector("[data-repeat-block]").cloneNode(true);
                reindexBlock(clone, type, list.children.length);
                list.append(clone);
            });
        });

        document.addEventListener("change", (event) => {
            if (!event.target.matches("[data-current-role]")) return;
            const block = event.target.closest(".repeat-block");
            const endDate = block.querySelector("[data-end-date]");
            endDate.disabled = event.target.checked;
            if (event.target.checked) endDate.value = "";
        });
    </script>
</body>
</html>
