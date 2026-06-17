<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Controllers/ProfileController.php';
require_once __DIR__ . '/../src/Models/Company.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'employer') {
    header('Location: login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$companyModel = new Company();
$company = $companyModel->findByUserId((int) $_SESSION['user_id']) ?? [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim((string) ($_POST['description'] ?? '')) === '' || trim((string) ($_POST['address'] ?? '')) === '') {
        $error = 'Please complete the company description and full address.';
    } else {
        $controller = new ProfileController();
        $result = $controller->saveEmployerCompanyDetails((int) $_SESSION['user_id'], [
            'description' => trim((string) $_POST['description']),
            'website' => trim((string) ($_POST['website'] ?? '')),
            'address' => trim((string) $_POST['address']),
        ]);

        if ($result['success']) {
            header('Location: employer-dashboard.php');
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
    <title>Complete Company | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Company profile navigation">
            <a class="site-logo" href="index.html" aria-label="WorkHive home">WorkHive</a>
            <div class="nav-actions">
                <a class="nav-button nav-button-secondary" href="logout.php">Log out</a>
            </div>
        </nav>
    </header>

    <main class="profile-main">
        <section class="profile-card" aria-labelledby="company-title">
            <p class="section-kicker">Required step</p>
            <h1 id="company-title">Complete company details</h1>
            <p>Add your company information before continuing to the employer dashboard.</p>

            <?php if ($error !== ''): ?>
                <div class="form-alert" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="company-summary">
                <div>
                    <span>Company name</span>
                    <strong><?php echo e((string) ($company['company_name'] ?? 'Not provided')); ?></strong>
                </div>
                <div>
                    <span>Sector</span>
                    <strong><?php echo e((string) ($company['sector'] ?? 'Not provided')); ?></strong>
                </div>
            </div>

            <form class="profile-form" method="post">
                <section class="profile-form-section">
                    <h2>Company profile</h2>
                    <div class="form-grid">
                        <div class="form-field full-span">
                            <label for="description">Company description</label>
                            <textarea id="description" name="description" rows="6" required><?php echo e((string) ($_POST['description'] ?? $company['description'] ?? '')); ?></textarea>
                        </div>
                        <div class="form-field">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website" value="<?php echo e((string) ($_POST['website'] ?? $company['website'] ?? '')); ?>">
                        </div>
                        <div class="form-field">
                            <label for="address">Full address</label>
                            <input type="text" id="address" name="address" value="<?php echo e((string) ($_POST['address'] ?? $company['address'] ?? '')); ?>" required>
                        </div>
                    </div>
                </section>

                <button class="button-primary" type="submit">Save company details</button>
            </form>
        </section>
    </main>
</body>
</html>
