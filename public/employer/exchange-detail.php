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
        'add_amount' => $_POST['add_amount'] ?? null,
        'add_swap' => $_POST['add_swap'] ?? null,
        'payment_proof' => $_FILES['payment_proof'] ?? null,
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
$companyAEmployees = $detail ? $controller->listCompanyEmployees((int) $detail['company_a_id']) : [];
$isClosed = $detail && in_array((string) $detail['status'], ['accepted', 'rejected'], true);
$isActionable = $detail && !$isClosed && !empty($detail['is_my_turn']);
$waitingForCompanyName = $detail ? (string) ($detail['waiting_for_company_name'] ?? '') : '';
$currentSwapEmployeeId = $detail && !empty($detail['current_swap_employee_id']) ? (int) $detail['current_swap_employee_id'] : 0;
$currentAmount = $detail && $detail['current_amount'] !== null ? (float) $detail['current_amount'] : null;
$hasCurrentSwap = $currentSwapEmployeeId > 0;
$hasCurrentAmount = $currentAmount !== null;
$finalTermsSummary = [];
if ($hasCurrentAmount) {
    $finalTermsSummary[] = 'cash amount: ' . number_format($currentAmount, 2);
}
if ($hasCurrentSwap) {
    $finalTermsSummary[] = 'swap employee: ' . (string) ($detail['current_swap_employee_name'] ?: 'selected employee');
}
$finalTermsText = $finalTermsSummary !== [] ? implode(' + ', $finalTermsSummary) : 'no active exchange terms';
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
                            <?php
                            $roundTerms = [];
                            if ($round['swap_employee_name'] !== null) {
                                $roundTerms[] = 'swap employee: ' . (string) $round['swap_employee_name'];
                            }
                            if ($round['proposed_amount'] !== null) {
                                $roundTerms[] = 'cash amount: ' . number_format((float) $round['proposed_amount'], 2);
                            }
                            ?>
                            <article class="activity-row">
                                <p><?php echo e((string) $round['proposed_by_name']); ?> proposed <?php echo e($roundTerms !== [] ? implode(' + ', $roundTerms) : 'new terms'); ?></p>
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
                        <form method="post" enctype="multipart/form-data" data-confirm-form data-confirm-action="Accept" data-confirm-title="Accept this exchange request?" data-confirm-summary="<?php echo e($finalTermsText); ?>">
                            <input type="hidden" name="request_id" value="<?php echo e((string) $requestId); ?>">
                            <input type="hidden" name="action" value="accept">
                            <?php if ($hasCurrentAmount): ?>
                                <div class="form-field" style="margin-bottom: 12px;">
                                    <label>Upload proof of payment (required before accepting)</label>
                                    <input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-payment-proof required>
                                    <span style="display: block; margin-top: 6px; color: var(--muted); font-size: 0.85rem;">PDF, JPG, or PNG. Maximum 5MB.</span>
                                </div>
                            <?php endif; ?>
                            <button class="button-primary" type="submit" data-accept-button <?php echo $hasCurrentAmount ? 'disabled' : ''; ?>>Accept</button>
                        </form>
                        <form method="post" data-confirm-form data-confirm-action="Reject" data-confirm-title="Reject this exchange request?" data-confirm-summary="<?php echo e($finalTermsText); ?>">
                            <input type="hidden" name="request_id" value="<?php echo e((string) $requestId); ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="button-outline reject" type="submit">Reject</button>
                        </form>
                    </div>
                    <form class="profile-form edit-panel" method="post">
                        <input type="hidden" name="request_id" value="<?php echo e((string) $requestId); ?>">
                        <input type="hidden" name="action" value="negotiate">
                        <p style="color: var(--muted); font-size: 0.9rem;">Propose new terms &mdash; you can offer a swap employee, a cash amount, or both.</p>
                        <div class="form-grid">
                            <?php if ($hasCurrentSwap): ?>
                                <label class="checkbox-field full-span">
                                    <input type="checkbox" value="1" data-remove-swap-toggle>
                                    <span>Remove swap from this offer</span>
                                </label>
                            <?php else: ?>
                                <label class="checkbox-field full-span">
                                    <input type="checkbox" name="add_swap" value="1" data-add-swap-toggle>
                                    <span>Add a swap employee to this offer</span>
                                </label>
                            <?php endif; ?>
                            <div class="form-field full-span" data-swap-field <?php echo $hasCurrentSwap ? '' : 'hidden'; ?>>
                                <label>Swap employee</label>
                                <select name="swap_employee_id" data-swap-input <?php echo $hasCurrentSwap ? '' : 'disabled'; ?>>
                                    <option value="">Choose employee from <?php echo e((string) $detail['company_a_name']); ?></option>
                                    <?php foreach ($companyAEmployees as $employee): ?>
                                        <?php $employeeId = (int) $employee['user_id']; ?>
                                        <option value="<?php echo e((string) $employeeId); ?>" <?php echo $employeeId === $currentSwapEmployeeId ? 'selected' : ''; ?>><?php echo e((string) $employee['full_name']); ?> - <?php echo e((string) ($employee['vacancy_title'] ?? 'Employee')); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <label class="checkbox-field full-span">
                                <input type="checkbox" name="add_amount" value="1" data-add-amount-toggle <?php echo $hasCurrentAmount ? 'checked' : ''; ?>>
                                <span>Add cash amount</span>
                            </label>
                            <div class="form-field full-span" data-add-amount-field <?php echo $hasCurrentAmount ? '' : 'hidden'; ?>>
                                <label>Counter amount</label>
                                <input type="number" name="offered_amount" min="0.01" step="0.01" value="<?php echo $hasCurrentAmount ? e((string) $currentAmount) : ''; ?>" data-add-amount-input <?php echo $hasCurrentAmount ? '' : 'disabled'; ?>>
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
                            <?php if (!empty($detail['payment']['proof_file'])): ?>
                                <div><span>Proof of payment</span><strong><a href="../<?php echo e((string) $detail['payment']['proof_file']); ?>" target="_blank" rel="noopener">View uploaded proof</a></strong></div>
                            <?php endif; ?>
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
    <div class="vacancy-modal" id="exchange-confirm-modal" aria-hidden="true">
        <div class="vacancy-modal-backdrop" data-close-confirm-modal></div>
        <section class="vacancy-modal-card" role="dialog" aria-modal="true" aria-labelledby="exchange-confirm-title">
            <button class="vacancy-modal-close" type="button" aria-label="Close modal" data-close-confirm-modal>
                <span aria-hidden="true">X</span>
            </button>
            <h2 id="exchange-confirm-title">Confirm action</h2>
            <div class="modal-section">
                <p id="exchange-confirm-summary" style="color: var(--muted);"></p>
            </div>
            <div class="modal-actions">
                <button class="button-primary" type="button" data-confirm-submit>Yes, confirm</button>
                <button class="button-outline" type="button" data-close-confirm-modal>Cancel</button>
            </div>
        </section>
    </div>
    <script>
        (() => {
            const form = document.querySelector('form input[name="action"][value="negotiate"]')?.form;

            if (!form) {
                return;
            }

            const addAmountToggle = form.querySelector('[data-add-amount-toggle]');
            const amountField = form.querySelector('[data-add-amount-field]');
            const amountInput = form.querySelector('[data-add-amount-input]');
            const addSwapToggle = form.querySelector('[data-add-swap-toggle]');
            const removeSwapToggle = form.querySelector('[data-remove-swap-toggle]');
            const swapField = form.querySelector('[data-swap-field]');
            const swapInput = form.querySelector('[data-swap-input]');

            function syncAmountField() {
                if (!addAmountToggle || !amountField || !amountInput) {
                    return;
                }

                amountField.hidden = !addAmountToggle.checked;
                amountInput.disabled = !addAmountToggle.checked;
                if (!addAmountToggle.checked) {
                    amountInput.value = '';
                }
            }

            function syncSwapField() {
                if (!swapField || !swapInput) {
                    return;
                }

                const shouldShow = removeSwapToggle ? !removeSwapToggle.checked : Boolean(addSwapToggle && addSwapToggle.checked);
                swapField.hidden = !shouldShow;
                swapInput.disabled = !shouldShow;
                if (!shouldShow) {
                    swapInput.value = '';
                }
            }

            if (addAmountToggle) {
                addAmountToggle.addEventListener('change', syncAmountField);
            }
            if (addSwapToggle) {
                addSwapToggle.addEventListener('change', syncSwapField);
            }
            if (removeSwapToggle) {
                removeSwapToggle.addEventListener('change', syncSwapField);
            }
            form.addEventListener('submit', () => {
                syncAmountField();
                syncSwapField();
            });
            syncAmountField();
            syncSwapField();
        })();

        (() => {
            const proofInput = document.querySelector('[data-payment-proof]');
            const acceptButton = document.querySelector('[data-accept-button]');

            if (!proofInput || !acceptButton) {
                return;
            }

            function syncAcceptButton() {
                acceptButton.disabled = proofInput.files.length === 0;
            }

            proofInput.addEventListener('change', syncAcceptButton);
            syncAcceptButton();
        })();

        (() => {
            const modal = document.getElementById('exchange-confirm-modal');
            if (!modal) {
                return;
            }

            const title = document.getElementById('exchange-confirm-title');
            const summary = document.getElementById('exchange-confirm-summary');
            const confirmButton = modal.querySelector('[data-confirm-submit]');
            let pendingForm = null;

            function openModal(form) {
                pendingForm = form;
                const action = form.dataset.confirmAction || 'Confirm';
                title.textContent = form.dataset.confirmTitle || 'Confirm this action?';
                summary.textContent = 'Final terms: ' + (form.dataset.confirmSummary || 'not applicable');
                confirmButton.textContent = 'Yes, ' + action;
                modal.classList.add('is-open');
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            }

            function closeModal() {
                pendingForm = null;
                modal.classList.remove('is-open');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            }

            document.querySelectorAll('[data-confirm-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    openModal(form);
                });
            });

            confirmButton.addEventListener('click', () => {
                if (pendingForm) {
                    pendingForm.submit();
                }
            });

            modal.querySelectorAll('[data-close-confirm-modal]').forEach((item) => item.addEventListener('click', closeModal));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
