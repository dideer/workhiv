<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Profile.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'job_seeker') {
    header('Location: login.php');
    exit;
}

$profileModel = new Profile();
if (!$profileModel->findByUserId((int) $_SESSION['user_id'])) {
    header('Location: complete-profile.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
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
    <main class="placeholder-main">
        <section class="placeholder-card">
            <p class="section-kicker">Job seeker dashboard</p>
            <h1>Welcome, <?php echo e((string) ($_SESSION['full_name'] ?? 'job seeker')); ?></h1>
            <p>Your job seeker dashboard will be built in the next backend step.</p>
            <a class="button-primary" href="logout.php">Log out</a>
        </section>
    </main>
</body>
</html>
