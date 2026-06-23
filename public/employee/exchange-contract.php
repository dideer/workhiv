<?php
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Config/Database.php';
require_once __DIR__ . '/../../src/Models/ExchangeEmployeeContract.php';
require_once __DIR__ . '/../../src/Models/ExchangeRequest.php';
require_once __DIR__ . '/../../src/Models/User.php';

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
$contractModel = new ExchangeEmployeeContract($db);
$requestModel = new ExchangeRequest($db);
$userModel = new User($db);
$employeeId = (int) ($_SESSION['user_id'] ?? 0);
$contract = $contractModel->getPendingForEmployee($employeeId);
$error = '';
$reversed = isset($_GET['reversed']);

if (!$contract && !$reversed) {
    header('Location: ../employee-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $contract) {
    $decision = (string) ($_POST['decision'] ?? '');
    $requestId = (int) $contract['request_id'];
    $contractId = (int) $contract['contract_id'];

    if ($decision === 'agreed') {
        if ($contractModel->respond($contractId, 'agreed')) {
            $allContracts = $contractModel->getAllForRequest($requestId);
            $allAgreed = $allContracts !== [];
            foreach ($allContracts as $row) {
                if ((string) $row['status'] !== 'agreed') {
                    $allAgreed = false;
                    break;
                }
            }

            $message = $allAgreed
                ? 'Exchange agreement complete.'
                : 'Your exchange agreement response has been recorded.';
            header('Location: ../employee-dashboard.php?message=' . urlencode($message));
            exit;
        }

        $error = 'Your response could not be saved.';
    } elseif ($decision === 'disagreed') {
        try {
            $db->beginTransaction();
            $contracts = new ExchangeEmployeeContract($db);
            $requests = new ExchangeRequest($db);
            $users = new User($db);

            $request = $requests->getById($requestId);
            if (!$request) {
                throw new RuntimeException('Request missing.');
            }

            $allContracts = $contracts->getAllForRequest($requestId);
            if ($allContracts === []) {
                throw new RuntimeException('Contracts missing.');
            }

            if (!$contracts->disagreeAllForRequest($requestId)) {
                throw new RuntimeException('Contract response failed.');
            }

            foreach ($allContracts as $row) {
                $targetCompanyId = (int) $row['employee_id'] === (int) $request['employee_id']
                    ? (int) $request['company_b_id']
                    : (int) $request['company_a_id'];

                if (!$users->updateCurrentCompany((int) $row['employee_id'], $targetCompanyId)) {
                    throw new RuntimeException('Employee reversal failed.');
                }
            }

            if (!$requests->updateStatus($requestId, 'rejected')) {
                throw new RuntimeException('Request reversal failed.');
            }

            $db->commit();
            header('Location: exchange-contract.php?reversed=1');
            exit;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $error = 'The exchange could not be reversed.';
        }
    } else {
        $error = 'Please choose whether you agree or disagree.';
    }
}

$contractTitle = 'EMPLOYMENT TRANSFER AGREEMENT';
$contractBody = '';
if ($contract) {
    $contractLines = explode("\n", (string) $contract['contract_text']);
    $contractTitle = trim((string) ($contractLines[0] ?? $contractTitle));
    $contractBody = trim(implode("\n", array_slice($contractLines, 1)));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exchange Agreement | WorkHive</title>
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
        <section class="profile-card" aria-labelledby="exchange-contract-title">
            <?php if ($reversed): ?>
                <p class="section-kicker">Exchange reversed</p>
                <h1 id="exchange-contract-title">Exchange agreement declined</h1>
                <div class="profile-form-section">
                    <p style="color: var(--muted); line-height: 1.75;">The exchange was reversed because an employee did not agree to the new terms. The involved employees have been returned to their original companies and the exchange request is now closed.</p>
                </div>
                <a class="button-primary" href="../employee-dashboard.php">Return to dashboard</a>
            <?php else: ?>
                <p class="section-kicker">Exchange agreement</p>
                <h1 id="exchange-contract-title">Review your transfer agreement</h1>

                <?php if ($error !== ''): ?>
                    <div class="form-alert" role="alert"><?php echo e($error); ?></div>
                <?php endif; ?>

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
