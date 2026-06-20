<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../src/Controllers/ExchangeController.php';

$activePage = 'exchange-requests';
$controller = new ExchangeController();
$requestId = (int) ($_GET['request_id'] ?? $_POST['request_id'] ?? 0);
$message = (string) ($_GET['message'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $result = $controller->respondToRequest($requestId, $companyId, $action, [
        'offered_amount' => $_POST['offered_amount'] ?? null,
        'swap_employee_id' => $_POST['swap_employee_id'] ?? null,
        'message' => $_POST['message'] ?? '',
    ]);

    if ($result['success']) {
        header('Location: exchange-detail.php?request_id=' . $requestId . '&message=' . urlencode($result['message']));
        exit;
    }

    $error = $result['message'];
}

$detail = $controller->getRequestDetail($requestId, $companyId);
if ($detail && (int) $detail['company_b_id'] === $companyId && (string) $detail['status'] === 'awaiting_approval') {
    header('Location: exchange-requests.php?message=' . urlencode('This request is not available yet.'));
    exit;
}
$ownEmployees = $controller->listCompanyEmployees($companyId);
$isClosed = $detail && in_array((string) $detail['status'], ['accepted', 'rejected'], true);
$isActionable = $detail && !$isClosed && !empty($detail['is_my_turn']);
$waitingForCompanyName = $detail ? (string) ($detail['waiting_for_company_name'] ?? '') : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exchange Detail | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="admin-body">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header panel-header">
            <div>
                <h1>Exchange detail</h1>
                <p>Review terms, negotiation history, and final contract details.</p>
            </div>
            <a class="button-outline" href="exchange-requests.php">Back to requests</a>
        </header>

        <?php if ($message !== ''): ?><div class="form-alert"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="form-alert" role="alert"><?php echo e($error); ?></div><?php endif; ?>

        <?php if (!$detail): ?>
            <section class="admin-panel"><div class="empty-state"><h3>Request not found</h3><p>This exchange request is unavailable.</p></div></section>
        <?php else: ?>
            <section class="admin-panel" aria-labelledby="request-title">
                <div class="admin-section-heading">
                    <h2 id="request-title"><?php echo e((string) $detail['employee_name']); ?></h2>
                    <p><?php echo e((string) $detail['company_a_name']); ?> requesting from <?php echo e((string) $detail['company_b_name']); ?></p>
                    <span class="status-tag <?php echo e(exchangeStatusClass((string) $detail['status'])); ?>"><?php echo e(exchangeStatusLabel((string) $detail['status'])); ?></span>
                </div>
                <div class="company-summary">
                    <div><span>Exchange type</span><strong><?php echo e(ucfirst((string) $detail['exchange_type'])); ?></strong></div>
                    <div><span>Offered amount</span><strong><?php echo $detail['offered_amount'] !== null ? e(number_format((float) $detail['offered_amount'], 2)) : 'Not applicable'; ?></strong></div>
                    <div><span>Swap employee</span><strong><?php echo e((string) ($detail['swap_employee_name'] ?? 'Not applicable')); ?></strong></div>
                    <div><span>Message</span><strong><?php echo e((string) ($detail['message'] ?? 'No message')); ?></strong></div>
                </div>
            </section>

            <section class="admin-panel report-preview" aria-labelledby="history-title">
                <div class="admin-section-heading"><h2 id="history-title">Negotiation history</h2></div>
                <div class="activity-list">
                    <?php if ($detail['negotiations'] === []): ?>
                        <div class="empty-state"><h3>No negotiation yet</h3><p>The original request terms are still current.</p></div>
                    <?php else: ?>
                        <?php foreach ($detail['negotiations'] as $round): ?>
                            <article class="activity-row">
                                <p><?php echo e((string) $round['proposed_by_name']); ?> proposed <?php echo $round['proposed_amount'] !== null ? e(number_format((float) $round['proposed_amount'], 2)) : e((string) ($round['swap_employee_name'] ?? 'swap terms')); ?></p>
                                <time><?php echo e(formatDate($round['created_at'] ?? null)); ?></time>
                                <span><?php echo e((string) ($round['message'] ?? '')); ?></span>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($isActionable): ?>
                <section class="admin-panel report-preview" aria-labelledby="actions-title">
                    <div class="admin-section-heading"><h2 id="actions-title">Actions</h2></div>
                    <div class="approval-actions">
                        <form method="post">
                            <input type="hidden" name="request_id" value="<?php echo e((string) $requestId); ?>">
                            <input type="hidden" name="action" value="accept">
                            <button class="button-primary" type="submit">Accept</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="request_id" value="<?php echo e((string) $requestId); ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="button-outline reject" type="submit">Reject</button>
                        </form>
                    </div>
                    <form class="profile-form edit-panel" method="post">
                        <input type="hidden" name="request_id" value="<?php echo e((string) $requestId); ?>">
                        <input type="hidden" name="action" value="negotiate">
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Counter amount</label>
                                <input type="number" name="offered_amount" min="0" step="0.01">
                            </div>
                            <div class="form-field">
                                <label>Counter swap employee</label>
                                <select name="swap_employee_id">
                                    <option value="">Choose employee</option>
                                    <?php foreach ($ownEmployees as $employee): ?>
                                        <option value="<?php echo e((string) $employee['user_id']); ?>"><?php echo e((string) $employee['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field full-span">
                                <label>Message</label>
                                <textarea name="message" rows="4"></textarea>
                            </div>
                        </div>
                        <button class="button-primary" type="submit">Negotiate</button>
                    </form>
                </section>
            <?php elseif (($detail['status'] ?? '') === 'awaiting_approval'): ?>
                <section class="admin-panel report-preview turn-banner" aria-labelledby="approval-title">
                    <div>
                        <h2 id="approval-title">Awaiting administrator approval</h2>
                        <p>This request is awaiting administrator approval before proceeding.</p>
                    </div>
                </section>
            <?php elseif (!$isClosed && $waitingForCompanyName !== ''): ?>
                <section class="admin-panel report-preview turn-banner" aria-labelledby="waiting-title">
                    <div>
                        <h2 id="waiting-title">Waiting for response</h2>
                        <p>Waiting for response from <?php echo e($waitingForCompanyName); ?>.</p>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (($detail['status'] ?? '') === 'accepted' && $detail['contract']): ?>
                <section class="admin-panel report-preview" aria-labelledby="contract-title">
                    <div class="admin-section-heading">
                        <h2 id="contract-title">Exchange contract</h2>
                        <p>This record documents the accepted terms. Any actual payment happens outside WorkHive.</p>
                    </div>
                    <div class="company-summary">
                        <div><span>Companies</span><strong><?php echo e((string) $detail['company_a_name']); ?> and <?php echo e((string) $detail['company_b_name']); ?></strong></div>
                        <div><span>Employee</span><strong><?php echo e((string) $detail['employee_name']); ?></strong></div>
                        <div><span>Final amount</span><strong><?php echo $detail['contract']['final_amount'] !== null ? e(number_format((float) $detail['contract']['final_amount'], 2)) : 'Not applicable'; ?></strong></div>
                        <div><span>Swap employee</span><strong><?php echo e((string) ($detail['contract']['swap_employee_name'] ?? 'Not applicable')); ?></strong></div>
                        <div><span>Generated</span><strong><?php echo e(formatDate($detail['contract']['generated_at'] ?? null)); ?></strong></div>
                        <?php if ($detail['payment']): ?>
                            <div><span>Payment record</span><strong><?php echo e(number_format((float) $detail['payment']['amount'], 2)); ?> recorded from <?php echo e((string) $detail['payment']['paid_by_name']); ?> to <?php echo e((string) $detail['payment']['paid_to_name']); ?></strong></div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php elseif (($detail['status'] ?? '') === 'rejected'): ?>
                <section class="admin-panel report-preview" aria-labelledby="rejected-title">
                    <div class="admin-section-heading">
                        <h2 id="rejected-title">Exchange rejected</h2>
                        <p>This exchange request is closed. No further action is available.</p>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
