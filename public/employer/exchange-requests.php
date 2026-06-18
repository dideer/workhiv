<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/ExchangeController.php';

$activePage = 'exchange-requests';
$controller = new ExchangeController();
$message = (string) ($_GET['message'] ?? '');
$sent = $controller->getSentRequests($companyId);
$received = $controller->getReceivedRequests($companyId);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exchange Requests | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <h1>Exchange requests</h1>
            <p>Manage employee exchange requests sent and received by your company.</p>
        </header>

        <?php if ($message !== ''): ?><div class="form-alert"><?php echo e($message); ?></div><?php endif; ?>

        <section class="admin-panel approvals-panel" aria-label="Exchange requests">
            <div class="tab-list" role="tablist" aria-label="Exchange request tabs">
                <button class="tab-button is-active" type="button" role="tab" aria-selected="true" data-tab="sent">Sent</button>
                <button class="tab-button" type="button" role="tab" aria-selected="false" data-tab="received">Received</button>
            </div>

            <div class="approval-list tab-panel is-active" data-panel="sent">
                <?php if ($sent === []): ?>
                    <div class="empty-state"><h3>No sent requests</h3><p>Requests you send will appear here.</p></div>
                <?php else: ?>
                    <?php foreach ($sent as $request): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e((string) $request['employee_name']); ?></p>
                                <span class="approval-meta"><?php echo e((string) $request['company_b_name']); ?> - <?php echo e(formatDate($request['created_at'] ?? null)); ?></span>
                                <span class="type-badge"><?php echo e(ucfirst((string) $request['exchange_type'])); ?></span>
                                <span class="status-tag <?php echo e(statusClass((string) $request['status'])); ?>"><?php echo e(ucfirst((string) $request['status'])); ?></span>
                                <?php if (in_array((string) $request['status'], ['pending', 'negotiating'], true)): ?>
                                    <?php if (!empty($request['is_my_turn'])): ?>
                                        <span class="turn-indicator is-actionable">Action needed</span>
                                    <?php else: ?>
                                        <span class="turn-indicator">Waiting for <?php echo e((string) ($request['waiting_for_company_name'] ?? 'response')); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="approval-actions">
                                <a class="button-primary" href="exchange-detail.php?request_id=<?php echo e((string) $request['request_id']); ?>">View</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="approval-list tab-panel" data-panel="received" hidden>
                <?php if ($received === []): ?>
                    <div class="empty-state"><h3>No received requests</h3><p>Requests from other companies will appear here.</p></div>
                <?php else: ?>
                    <?php foreach ($received as $request): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e((string) $request['employee_name']); ?></p>
                                <span class="approval-meta"><?php echo e((string) $request['company_a_name']); ?> - <?php echo e(formatDate($request['created_at'] ?? null)); ?></span>
                                <span class="type-badge"><?php echo e(ucfirst((string) $request['exchange_type'])); ?></span>
                                <span class="status-tag <?php echo e(statusClass((string) $request['status'])); ?>"><?php echo e(ucfirst((string) $request['status'])); ?></span>
                                <?php if (in_array((string) $request['status'], ['pending', 'negotiating'], true)): ?>
                                    <?php if (!empty($request['is_my_turn'])): ?>
                                        <span class="turn-indicator is-actionable">Action needed</span>
                                    <?php else: ?>
                                        <span class="turn-indicator">Waiting for <?php echo e((string) ($request['waiting_for_company_name'] ?? 'response')); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="approval-actions">
                                <a class="button-primary" href="exchange-detail.php?request_id=<?php echo e((string) $request['request_id']); ?>">View</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script src="../admin/assets/admin.js"></script>
</body>
</html>
