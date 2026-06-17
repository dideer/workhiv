<?php
require_once __DIR__ . '/../src/Controllers/AuthController.php';

Session::start();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function old(string $key): string
{
    return e((string) ($_POST[$key] ?? ''));
}

$error = '';
$role = $_POST['role'] ?? 'job_seeker';
$role = $role === 'employer' ? 'employer' : 'job_seeker';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $payload = [
        'full_name' => trim($_POST['fullName'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirmPassword'] ?? '',
        'company_name' => trim($_POST['companyName'] ?? ''),
        'sector' => trim($_POST['companySector'] ?? ''),
    ];

    $result = $role === 'employer'
        ? $auth->registerEmployer($payload)
        : $auth->registerJobSeeker($payload);

    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    }

    $error = $result['message'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Authentication navigation">
            <a class="site-logo" href="index.html" aria-label="WorkHive home">WorkHive</a>
        </nav>
    </header>

    <main class="auth-main auth-split">
        <section class="auth-shell" aria-labelledby="register-title">
            <div class="auth-card">
                <p class="section-kicker">Join WorkHive</p>
                <h1 id="register-title">Create your account</h1>

                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form class="auth-form" action="register.php" method="post">
                    <fieldset class="role-picker" aria-label="Choose account role">
                        <label class="role-option <?php echo $role === 'job_seeker' ? 'is-selected' : ''; ?>" data-role-option>
                            <input type="radio" name="role" value="job_seeker" <?php echo $role === 'job_seeker' ? 'checked' : ''; ?>>
                            <span>Job Seeker</span>
                            <small>Search jobs and prepare applications.</small>
                        </label>

                        <label class="role-option <?php echo $role === 'employer' ? 'is-selected' : ''; ?>" data-role-option>
                            <input type="radio" name="role" value="employer" <?php echo $role === 'employer' ? 'checked' : ''; ?>>
                            <span>Employer</span>
                            <small>Prepare to publish opportunities later.</small>
                        </label>
                    </fieldset>

                    <div class="form-field">
                        <label for="full-name">Full name</label>
                        <input type="text" id="full-name" name="fullName" value="<?php echo old('fullName'); ?>" autocomplete="name" required>
                    </div>

                    <div class="form-field">
                        <label for="register-email">Email address</label>
                        <input type="email" id="register-email" name="email" value="<?php echo old('email'); ?>" autocomplete="email" required>
                    </div>

                    <div class="form-field">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo old('phone'); ?>" autocomplete="tel" required>
                    </div>

                    <div class="employer-fields" id="employer-fields" <?php echo $role === 'employer' ? '' : 'hidden'; ?>>
                        <div class="form-field">
                            <label for="company-name">Company name</label>
                            <input type="text" id="company-name" name="companyName" value="<?php echo old('companyName'); ?>">
                        </div>

                        <div class="form-field">
                            <label for="company-sector">Sector</label>
                            <input type="text" id="company-sector" name="companySector" value="<?php echo old('companySector'); ?>">
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" autocomplete="new-password" required>
                    </div>

                    <div class="form-field">
                        <label for="confirm-password">Confirm password</label>
                        <input type="password" id="confirm-password" name="confirmPassword" autocomplete="new-password" required>
                    </div>

                    <button class="button-primary" type="submit">Create account</button>
                </form>

                <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </section>
        <aside class="auth-photo auth-photo-register" aria-label="Rwandan professional registration setting"></aside>
    </main>

    <script>
        const roleOptions = document.querySelectorAll("[data-role-option]");
        const employerFields = document.querySelector("#employer-fields");

        roleOptions.forEach((option) => {
            option.addEventListener("change", () => {
                roleOptions.forEach((item) => item.classList.remove("is-selected"));
                option.classList.add("is-selected");
                employerFields.hidden = option.querySelector("input").value !== "employer";
            });
        });
    </script>
</body>
</html>
