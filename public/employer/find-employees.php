<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/ExchangeController.php';

$activePage = 'find-employees';
$controller = new ExchangeController();
$message = (string) ($_GET['message'] ?? '');
$error = '';
$search = trim((string) ($_GET['search'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exchangeType = (string) ($_POST['exchange_type'] ?? '');
    $result = $controller->sendRequest(
        $companyId,
        (int) ($_POST['employee_id'] ?? 0),
        (int) ($_POST['company_b_id'] ?? 0),
        $exchangeType,
        $exchangeType === 'paid' ? (float) ($_POST['offered_amount'] ?? 0) : null,
        $exchangeType === 'swap' ? (int) ($_POST['swap_employee_id'] ?? 0) : null,
        trim((string) ($_POST['message'] ?? ''))
    );

    if ($result['success']) {
        header('Location: find-employees.php?message=' . urlencode($result['message']));
        exit;
    }

    $error = $result['message'];
}

$employees = $controller->listEmployees($companyId, $search !== '' ? $search : null);
$ownEmployees = $controller->listCompanyEmployees($companyId);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Find Employees | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <h1>Find employees</h1>
            <p>Browse employees at other companies to request an exchange.</p>
        </header>

        <?php if ($message !== ''): ?><div class="form-alert"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="form-alert" role="alert"><?php echo e($error); ?></div><?php endif; ?>

        <section class="admin-panel" aria-labelledby="employee-search-title">
            <div class="admin-section-heading">
                <h2 id="employee-search-title">Search employees</h2>
            </div>
            <form class="report-filter-form" method="get">
                <div class="form-field">
                    <label for="search">Employee name</label>
                    <input type="search" id="search" name="search" value="<?php echo e($search); ?>" placeholder="Search by name">
                </div>
                <button class="button-primary" type="submit">Search</button>
            </form>
        </section>

        <section class="admin-panel report-preview" aria-labelledby="employee-list-title">
            <div class="admin-section-heading">
                <h2 id="employee-list-title">Employees</h2>
            </div>

            <div class="approval-list">
                <?php if ($employees === []): ?>
                    <div class="empty-state">
                        <h3>No employees found</h3>
                        <p>Employees at other companies will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e((string) $employee['full_name']); ?></p>
                                <span class="approval-meta"><?php echo e((string) $employee['current_company_name']); ?> - <?php echo e((string) ($employee['vacancy_title'] ?? 'Position not set')); ?></span>
                            </div>
                            <details>
                                <summary class="button-primary">Request exchange</summary>
                                <form class="profile-form edit-panel" method="post">
                                    <input type="hidden" name="employee_id" value="<?php echo e((string) $employee['user_id']); ?>">
                                    <input type="hidden" name="company_b_id" value="<?php echo e((string) $employee['current_company_id']); ?>">
                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label>Exchange type</label>
                                            <select name="exchange_type" required>
                                                <option value="paid">Paid</option>
                                                <option value="swap">Swap</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label>Offered amount</label>
                                            <input type="number" name="offered_amount" min="0" step="0.01">
                                        </div>
                                        <div class="form-field full-span">
                                            <label>Swap employee</label>
                                            <select name="swap_employee_id">
                                                <option value="">Choose one of your employees</option>
                                                <?php foreach ($ownEmployees as $ownEmployee): ?>
                                                    <option value="<?php echo e((string) $ownEmployee['user_id']); ?>"><?php echo e((string) $ownEmployee['full_name']); ?> - <?php echo e((string) ($ownEmployee['vacancy_title'] ?? 'Employee')); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field full-span">
                                            <label>Message</label>
                                            <textarea name="message" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <button class="button-primary" type="submit">Send request</button>
                                </form>
                            </details>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
