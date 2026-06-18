<?php
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Config/Database.php';
require_once __DIR__ . '/../../src/Models/Application.php';
require_once __DIR__ . '/../../src/Models/EmploymentContract.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Models/Vacancy.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'employee') {
    header('Location: ../login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$db = Database::getConnection();
$applicationModel = new Application($db);
$contractModel = new EmploymentContract($db);
$application = $applicationModel->getLatestHiredByUserId((int) $_SESSION['user_id']);
$contract = $application ? $contractModel->getByAppId((int) $application['app_id']) : null;
$contractTitle = '';
$contractBody = '';
$error = '';

if (!$application || !$contract) {
    $error = 'No employment contract is available yet.';
} elseif ($contract['status'] === 'agreed') {
    header('Location: ../employee-dashboard.php');
    exit;
} elseif ($contract['status'] === 'disagreed') {
    header('Location: ../login.php?message=' . urlencode('Your contract response has already been recorded.'));
    exit;
}

if ($contract) {
    $contractLines = explode("\n", (string) $contract['contract_text']);
    $contractTitle = trim((string) ($contractLines[0] ?? 'EMPLOYMENT CONTRACT'));
    $contractBody = trim(implode("\n", array_slice($contractLines, 1)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $application && $contract) {
    $decision = (string) ($_POST['decision'] ?? '');

    if ($decision === 'agreed') {
        if ($contractModel->respond((int) $application['app_id'], 'agreed')) {
            header('Location: ../employee-dashboard.php');
            exit;
        }

        $error = 'Contract response could not be saved.';
    } elseif ($decision === 'disagreed') {
        try {
            $db->beginTransaction();
            $contracts = new EmploymentContract($db);
            $users = new User($db);
            $vacancies = new Vacancy($db);

            if (!$contracts->respond((int) $application['app_id'], 'disagreed')) {
                throw new RuntimeException('Contract response failed.');
            }

            $stmt = $db->prepare(
                'UPDATE applications
                 SET status = :status,
                     updated_at = NOW()
                 WHERE app_id = :app_id AND user_id = :user_id'
            );
            $stmt->bindValue(':status', 'rejected', PDO::PARAM_STR);
            $stmt->bindValue(':app_id', (int) $application['app_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_id', (int) $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();

            if (!$users->updateRole((int) $_SESSION['user_id'], 'job_seeker')) {
                throw new RuntimeException('Role update failed.');
            }

            if (!$users->updateCurrentCompany((int) $_SESSION['user_id'], null)) {
                throw new RuntimeException('Company update failed.');
            }

            if (!$vacancies->reopenIfBelowCapacity((int) $application['vacancy_id'])) {
                throw new RuntimeException('Vacancy update failed.');
            }

            $db->commit();
            $_SESSION['role'] = 'job_seeker';
            header('Location: ../login.php?message=' . urlencode('You disagreed with the contract. Your account has reverted to job seeker status.'));
            exit;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $error = 'Your response could not be completed.';
        }
    } else {
        $error = 'Please choose whether you agree or disagree.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employment Contract | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Employee navigation">
            <a class="site-logo" href="../index.php" aria-label="WorkHive home">WorkHive</a>
            <div class="nav-actions">
                <span>Hi, <?php echo e((string) ($_SESSION['full_name'] ?? 'there')); ?></span>
                <a class="nav-button nav-button-secondary" href="../logout.php">Log out</a>
            </div>
        </nav>
    </header>

    <main class="profile-main">
        <section class="profile-card" aria-labelledby="contract-title">
            <p class="section-kicker">Employment contract</p>
            <h1 id="contract-title">Review your contract</h1>

            <?php if ($error !== ''): ?>
                <div class="form-alert" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($contract && $contract['status'] === 'pending'): ?>
                <article class="profile-form-section">
                    <h2><?php echo e($contractTitle); ?></h2>
                    <p style="white-space: pre-line; color: var(--ink); line-height: 1.75;"><?php echo e($contractBody); ?></p>
                </article>

                <div class="modal-actions">
                    <form method="post">
                        <input type="hidden" name="decision" value="agreed">
                        <button class="button-primary" type="submit">I Agree</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="decision" value="disagreed">
                        <button class="button-outline" type="submit">I Disagree</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
