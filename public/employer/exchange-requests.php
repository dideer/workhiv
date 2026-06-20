<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Models/ExchangeRequest.php';

$activePage = 'exchange-requests';
$exchangeRequestModel = new ExchangeRequest();
$message = (string) ($_GET['message'] ?? '');
$sentRequests = $exchangeRequestModel->getSentByCompany($companyId);
$receivedRequests = $exchangeRequestModel->getReceivedByCompany($companyId);
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
                <button class="tab-button is-active" id="tab-sent-button" type="button" role="tab" aria-selected="true" aria-controls="tab-sent" data-tab="sent" data-tab-target="tab-sent">Sent</button>
                <button class="tab-button" id="tab-received-button" type="button" role="tab" aria-selected="false" aria-controls="tab-received" data-tab="received" data-tab-target="tab-received">Received</button>
            </div>

            <div class="approval-list tab-panel is-active" id="tab-sent" role="tabpanel" aria-labelledby="tab-sent-button" data-panel="sent">
                <?php if ($sentRequests === []): ?>
                    <div class="empty-state"><h3>No sent requests</h3><p>Requests you send will appear here.</p></div>
                <?php else: ?>
                    <?php foreach ($sentRequests as $request): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e((string) $request['employee_name']); ?></p>
                                <span class="approval-meta"><?php echo e((string) $request['company_b_name']); ?> - <?php echo e(formatDate($request['created_at'] ?? null)); ?></span>
                                <span class="type-badge"><?php echo e(ucfirst((string) $request['exchange_type'])); ?></span>
                                <span class="status-tag <?php echo e(exchangeStatusClass((string) $request['status'])); ?>"><?php echo e(exchangeStatusLabel((string) $request['status'])); ?></span>
                            </div>
                            <div class="approval-actions">
                                <a class="button-primary" href="exchange-detail.php?request_id=<?php echo e((string) $request['request_id']); ?>">View detail</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="approval-list tab-panel" id="tab-received" role="tabpanel" aria-labelledby="tab-received-button" data-panel="received" hidden>
                <?php if ($receivedRequests === []): ?>
                    <div class="empty-state"><h3>No exchange requests received yet.</h3><p>Approved requests from other companies will appear here.</p></div>
                <?php else: ?>
                    <?php foreach ($receivedRequests as $request): ?>
                        <article class="approval-item">
                            <div class="approval-primary">
                                <p><?php echo e((string) $request['company_a_name']); ?></p>
                                <span class="approval-meta">Employee: <?php echo e((string) $request['employee_name']); ?> - <?php echo e(formatDate($request['created_at'] ?? null)); ?></span>
                                <span class="type-badge"><?php echo e(ucfirst((string) $request['exchange_type'])); ?></span>
                                <span class="status-tag <?php echo e(exchangeStatusClass((string) $request['status'])); ?>"><?php echo e(exchangeStatusLabel((string) $request['status'])); ?></span>
                            </div>
                            <div class="approval-actions">
                                <a class="button-primary" href="exchange-detail.php?request_id=<?php echo e((string) $request['request_id']); ?>">View &amp; respond</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script src="../admin/assets/admin.js?v=20260619"></script>
</body>
</html>
