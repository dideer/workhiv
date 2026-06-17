<?php
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Models/Admin.php';
require_once __DIR__ . '/../../src/Controllers/CompanyController.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function submittedDate(array $row): string
{
    $value = $row['created_at'] ?? $row['submitted_at'] ?? null;
    if (!$value) {
        return 'Submitted date unavailable';
    }

    return 'Submitted ' . date('M j, Y', strtotime((string) $value));
}

function rowValue(array $row, array $keys, string $fallback = 'Not provided'): string
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
            return (string) $row[$key];
        }
    }

    return $fallback;
}

$adminName = (string) ($_SESSION['full_name'] ?? 'Admin User');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_company') {
    $controller = new CompanyController();
    $result = $controller->approveCompany((int) ($_POST['company_id'] ?? 0), (int) ($_SESSION['user_id'] ?? 0));

    if ($result['success']) {
        header('Location: approvals.php?message=' . urlencode($result['message']));
        exit;
    }

    $error = $result['message'];
}

$message = (string) ($_GET['message'] ?? $message);
$adminModel = new Admin();
$pendingEmployers = $adminModel->pendingEmployers();
$pendingExchanges = $adminModel->pendingExchangeRequests();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Approvals | WorkHive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body" data-admin-page="approvals">
    <aside class="admin-sidebar" aria-label="Admin navigation">
        <div class="admin-brand">
            <a href="dashboard.php">WorkHive</a>
            <span>Admin</span>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link" href="dashboard.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"></path></svg>
                <span>Dashboard</span>
            </a>
            <a class="admin-nav-link is-active" href="approvals.php" aria-current="page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.6 16.2 5.8 12.4l1.4-1.4 2.4 2.4 7.2-7.2 1.4 1.4-8.6 8.6ZM4 4h10v2H6v12h12v-8h2v10H4V4Z"></path></svg>
                <span>Approvals</span>
            </a>
            <a class="admin-nav-link" href="reports.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 19h14v2H3V3h2v16Zm3-2V9h3v8H8Zm5 0V5h3v12h-3Zm5 0v-6h3v6h-3Z"></path></svg>
                <span>Reports</span>
            </a>
        </nav>

        <div class="admin-profile">
            <div class="admin-avatar" aria-hidden="true">AU</div>
            <div>
                <p><?php echo e($adminName); ?></p>
                <a href="../logout.php">Log out</a>
            </div>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-page-header">
            <h1>Approvals</h1>
            <p>Review and approve pending employer registrations and exchange requests.</p>
        </header>

        <?php if ($message !== ''): ?>
            <div class="form-alert"><?php echo e($message); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="form-alert" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <section class="admin-panel approvals-panel" aria-label="Pending approvals">
            <div class="tab-list" role="tablist" aria-label="Approval categories">
                <button class="tab-button is-active" type="button" role="tab" aria-selected="true" data-tab="employers">Employer registrations</button>
                <button class="tab-button" type="button" role="tab" aria-selected="false" data-tab="exchanges">Exchange requests</button>
            </div>

            <div class="approval-list tab-panel is-active" data-panel="employers">
                <?php if ($pendingEmployers === []): ?>
                    <div class="empty-state">
                        <h3>No pending employer registrations</h3>
                        <p>Employer registrations awaiting review will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingEmployers as $employer): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e(rowValue($employer, ['company_name', 'full_name'])); ?></p>
                                <span class="approval-meta"><?php echo e(rowValue($employer, ['sector'])); ?> - <?php echo e(submittedDate($employer)); ?></span>
                            </div>
                            <div class="approval-actions">
                                <form method="post">
                                    <input type="hidden" name="action" value="approve_company">
                                    <input type="hidden" name="company_id" value="<?php echo e((string) $employer['company_id']); ?>">
                                    <button class="button-primary" type="submit">Approve</button>
                                </form>
                                <button class="button-outline reject" type="button" data-action="rejected">Reject</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="approval-list tab-panel" data-panel="exchanges" hidden>
                <?php if ($pendingExchanges === []): ?>
                    <div class="empty-state">
                        <h3>No pending exchange requests</h3>
                        <p>Exchange requests awaiting review will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingExchanges as $exchange): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e(rowValue($exchange, ['source_company', 'from_company', 'company_a'], 'Company A')); ?> to <?php echo e(rowValue($exchange, ['target_company', 'to_company', 'company_b'], 'Company B')); ?></p>
                                <span class="approval-meta">Employee: <?php echo e(rowValue($exchange, ['employee_name', 'employee_full_name'], 'Not provided')); ?> - <?php echo e(submittedDate($exchange)); ?></span>
                                <span class="type-badge"><?php echo e(rowValue($exchange, ['exchange_type', 'type'], 'Exchange')); ?></span>
                            </div>
                            <div class="approval-actions">
                                <button class="button-primary" type="button" data-action="approved">Approve</button>
                                <button class="button-outline reject" type="button" data-action="rejected">Reject</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="assets/admin.js"></script>
</body>
</html>
