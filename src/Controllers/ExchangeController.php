<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/ExchangeRequest.php';
require_once __DIR__ . '/../Models/ExchangeNegotiation.php';
require_once __DIR__ . '/../Models/ExchangeContract.php';
require_once __DIR__ . '/../Models/ExchangeEmployeeContract.php';
require_once __DIR__ . '/../Models/PaymentRecord.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Notifier.php';

class ExchangeController
{
    private PDO $db;
    private ExchangeRequest $requests;
    private ExchangeNegotiation $negotiations;
    private ExchangeContract $contracts;
    private ExchangeEmployeeContract $employeeContracts;
    private PaymentRecord $payments;
    private User $users;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->requests = new ExchangeRequest($this->db);
        $this->negotiations = new ExchangeNegotiation($this->db);
        $this->contracts = new ExchangeContract($this->db);
        $this->employeeContracts = new ExchangeEmployeeContract($this->db);
        $this->payments = new PaymentRecord($this->db);
        $this->users = new User($this->db);
    }

    public function listEmployees(int $excludeCompanyId, ?string $search = null): array
    {
        $sql = 'SELECT u.user_id, u.full_name, u.email,
                       COALESCE(current_company.company_id, derived_company.company_id) AS current_company_id,
                       COALESCE(current_company.company_name, derived_company.company_name) AS current_company_name,
                       latest_vacancy.title AS vacancy_title
                FROM users u
                LEFT JOIN companies current_company ON current_company.company_id = u.current_company_id
                LEFT JOIN (
                    SELECT a.user_id, MAX(a.app_id) AS latest_app_id
                    FROM applications a
                    WHERE a.status = :hired_status
                    GROUP BY a.user_id
                ) latest ON latest.user_id = u.user_id
                LEFT JOIN applications latest_app ON latest_app.app_id = latest.latest_app_id
                LEFT JOIN vacancies latest_vacancy ON latest_vacancy.vacancy_id = latest_app.vacancy_id
                LEFT JOIN companies derived_company ON derived_company.company_id = latest_vacancy.company_id
                WHERE u.role = :role
                  AND COALESCE(u.current_company_id, latest_vacancy.company_id) IS NOT NULL
                  AND COALESCE(u.current_company_id, latest_vacancy.company_id) <> :exclude_company_id';

        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND u.full_name LIKE :search';
        }

        $sql .= ' ORDER BY u.full_name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':hired_status', 'hired', PDO::PARAM_STR);
        $stmt->bindValue(':role', 'employee', PDO::PARAM_STR);
        $stmt->bindValue(':exclude_company_id', $excludeCompanyId, PDO::PARAM_INT);
        if ($search !== null && trim($search) !== '') {
            $stmt->bindValue(':search', '%' . trim($search) . '%', PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listCompanyEmployees(int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.user_id, u.full_name, latest_vacancy.title AS vacancy_title
             FROM users u
             LEFT JOIN (
                SELECT a.user_id, MAX(a.app_id) AS latest_app_id
                FROM applications a
                WHERE a.status = :hired_status
                GROUP BY a.user_id
             ) latest ON latest.user_id = u.user_id
             LEFT JOIN applications latest_app ON latest_app.app_id = latest.latest_app_id
             LEFT JOIN vacancies latest_vacancy ON latest_vacancy.vacancy_id = latest_app.vacancy_id
             WHERE u.role = :role
               AND COALESCE(u.current_company_id, latest_vacancy.company_id) = :company_id
             ORDER BY u.full_name ASC'
        );
        $stmt->bindValue(':hired_status', 'hired', PDO::PARAM_STR);
        $stmt->bindValue(':role', 'employee', PDO::PARAM_STR);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function sendRequest(int $companyAId, int $employeeId, int $companyBId, string $exchangeType, ?float $offeredAmount, ?int $swapEmployeeId, string $message): array
    {
        if (!in_array($exchangeType, ['paid', 'swap'], true)) {
            return ['success' => false, 'message' => 'Invalid exchange type.'];
        }

        if ($exchangeType === 'paid') {
            if ($swapEmployeeId !== null) {
                return ['success' => false, 'message' => 'Paid requests cannot include a swap employee.'];
            }

            if ($offeredAmount === null || $offeredAmount <= 0) {
                return ['success' => false, 'message' => 'Please enter a valid offered amount.'];
            }
        }

        if ($exchangeType === 'swap') {
            if ($swapEmployeeId === null || $swapEmployeeId <= 0) {
                return ['success' => false, 'message' => 'Please choose an employee to offer for the swap.'];
            }

            $validation = $this->validateTerms($companyAId, $offeredAmount, $swapEmployeeId);
            if (!$validation['success']) {
                return $validation;
            }
        }

        if ($exchangeType === 'paid') {
            $validation = $this->validateTerms($companyAId, $offeredAmount, null);
        }
        if (!$validation['success']) {
            return $validation;
        }

        if (!$this->employeeBelongsToCompany($employeeId, $companyBId)) {
            return ['success' => false, 'message' => 'Selected employee does not belong to the target company.'];
        }

        $requestId = $this->requests->create([
            'company_a_id' => $companyAId,
            'company_b_id' => $companyBId,
            'employee_id' => $employeeId,
            'exchange_type' => $exchangeType,
            'offered_amount' => $offeredAmount,
            'swap_employee_id' => $exchangeType === 'swap' ? $swapEmployeeId : null,
            'status' => 'awaiting_approval',
            'message' => trim($message),
        ]);

        if ($requestId <= 0) {
            return ['success' => false, 'message' => 'Exchange request could not be sent.'];
        }

        try {
            $createdRequest = $this->requests->getById($requestId);
            foreach ($this->users->getByRole('admin') as $admin) {
                Notifier::send(
                    (int) $admin['user_id'],
                    'New exchange request from ' . (string) ($createdRequest['company_a_name'] ?? 'a company') . ' is awaiting approval.',
                    'exchange',
                    'admin/approvals.php'
                );
            }
        } catch (Throwable $exception) {
            error_log('Notification failed: ' . $exception->getMessage());
        }

        return ['success' => true, 'message' => 'Exchange request sent.'];
    }

    public function respondToRequest(int $requestId, int $companyId, string $action, ?array $counterData = null): array
    {
        $request = $this->requests->getById($requestId);
        if (!$request || !$this->requests->verifyOwnership($requestId, $companyId)) {
            return ['success' => false, 'message' => 'Exchange request not found.'];
        }

        if (!$this->canActOnRequest($request, $companyId)) {
            return ['success' => false, 'message' => 'You cannot act on this request right now.'];
        }

        if ($action === 'reject') {
            if (!$this->requests->updateStatus($requestId, 'rejected')) {
                return ['success' => false, 'message' => 'Exchange request could not be rejected.'];
            }

            try {
                $targetUserId = (int) $companyId === (int) $request['company_a_id']
                    ? (int) ($request['company_b_user_id'] ?? 0)
                    : (int) ($request['company_a_user_id'] ?? 0);
                if ($targetUserId > 0) {
                    Notifier::send(
                        $targetUserId,
                        'Your exchange request for ' . (string) $request['employee_name'] . ' was declined.',
                        'exchange',
                        'employer/exchange-detail.php?request_id=' . $requestId
                    );
                }
            } catch (Throwable $exception) {
                error_log('Notification failed: ' . $exception->getMessage());
            }

            return ['success' => true, 'message' => 'Exchange request rejected.'];
        }

        if ($action === 'negotiate') {
            $addAmount = !empty($counterData['add_amount']);
            $amount = $addAmount ? (float) ($counterData['offered_amount'] ?? 0) : null;

            $addSwap = !empty($counterData['add_swap']) || !empty($counterData['swap_employee_id']);
            $swapEmployeeId = $addSwap ? (int) ($counterData['swap_employee_id'] ?? 0) : null;

            $validation = $this->validateTerms((int) $request['company_a_id'], $amount, $swapEmployeeId);
            if (!$validation['success']) {
                return $validation;
            }

            $created = $this->negotiations->create([
                'request_id' => $requestId,
                'proposed_by' => $companyId,
                'proposed_amount' => $amount,
                'swap_employee_id' => $swapEmployeeId,
                'message' => trim((string) ($counterData['message'] ?? '')),
            ]);

            if ($created <= 0 || !$this->requests->updateStatus($requestId, 'negotiating')) {
                return ['success' => false, 'message' => 'Counter-proposal could not be saved.'];
            }

            try {
                $targetUserId = (int) $companyId === (int) $request['company_a_id']
                    ? (int) ($request['company_b_user_id'] ?? 0)
                    : (int) ($request['company_a_user_id'] ?? 0);
                $proposerName = (int) $companyId === (int) $request['company_a_id']
                    ? (string) $request['company_a_name']
                    : (string) $request['company_b_name'];
                if ($targetUserId > 0) {
                    Notifier::send(
                        $targetUserId,
                        $proposerName . ' has countered the exchange terms for ' . (string) $request['employee_name'] . '.',
                        'exchange',
                        'employer/exchange-detail.php?request_id=' . $requestId
                    );
                }
            } catch (Throwable $exception) {
                error_log('Notification failed: ' . $exception->getMessage());
            }

            return ['success' => true, 'message' => 'Counter-proposal sent.'];
        }

        if ($action !== 'accept') {
            return ['success' => false, 'message' => 'Invalid exchange action.'];
        }

        $freshRequest = $this->requests->getById($requestId);
        if (!$freshRequest) {
            return ['success' => false, 'message' => 'Exchange request not found.'];
        }

        $history = $this->negotiations->getByRequestId($requestId);
        $latest = $history === [] ? null : $history[count($history) - 1];
        $finalAmount = $latest !== null
            ? ($latest['proposed_amount'] !== null ? (float) $latest['proposed_amount'] : null)
            : ($freshRequest['offered_amount'] !== null ? (float) $freshRequest['offered_amount'] : null);
        $swapEmployeeId = $latest !== null
            ? ($latest['swap_employee_id'] !== null ? (int) $latest['swap_employee_id'] : null)
            : (!empty($freshRequest['swap_employee_id']) ? (int) $freshRequest['swap_employee_id'] : null);
        $swapEmployeeName = $latest !== null
            ? ($latest['swap_employee_id'] !== null ? (string) ($latest['swap_employee_name'] ?? '') : null)
            : (!empty($freshRequest['swap_employee_name']) ? (string) $freshRequest['swap_employee_name'] : null);

        if ($finalAmount === null && $swapEmployeeId === null) {
            return ['success' => false, 'message' => 'Accepted terms must include an amount, a swap employee, or both.'];
        }

        $proofFile = is_array($counterData['payment_proof'] ?? null) ? $counterData['payment_proof'] : null;
        if ($finalAmount !== null) {
            $proofValidation = $this->validatePaymentProofUpload($proofFile);
            if (!$proofValidation['success']) {
                return $proofValidation;
            }
        }

        $storedProofPath = null;
        try {
            $this->db->beginTransaction();

            $requests = new ExchangeRequest($this->db);
            $contracts = new ExchangeContract($this->db);
            $employeeContracts = new ExchangeEmployeeContract($this->db);
            $payments = new PaymentRecord($this->db);
            $users = new User($this->db);

            if (!$requests->updateStatus($requestId, 'accepted')) {
                throw new RuntimeException('Status update failed.');
            }

            $contractId = $contracts->create($requestId, $finalAmount, $swapEmployeeId);
            if ($contractId <= 0) {
                throw new RuntimeException('Contract failed.');
            }

            if ($finalAmount !== null) {
                $paymentId = $payments->create($contractId, $finalAmount, (int) $freshRequest['company_a_id'], (int) $freshRequest['company_b_id']);
                if ($paymentId <= 0) {
                    throw new RuntimeException('Payment record failed.');
                }

                $storedProof = $this->storePaymentProofUpload($proofFile);
                $storedProofPath = $storedProof['absolute'];
                if (!$payments->updateProof($paymentId, $storedProof['relative'])) {
                    throw new RuntimeException('Payment proof update failed.');
                }
            }

            if (!$users->updateCurrentCompany((int) $freshRequest['employee_id'], (int) $freshRequest['company_a_id'])) {
                throw new RuntimeException('Employee move failed.');
            }

            if ($swapEmployeeId !== null) {
                if (!$this->employeeBelongsToCompany($swapEmployeeId, (int) $freshRequest['company_a_id'])) {
                    throw new RuntimeException('Swap employee invalid.');
                }
                if (!$users->updateCurrentCompany($swapEmployeeId, (int) $freshRequest['company_b_id'])) {
                    throw new RuntimeException('Swap move failed.');
                }
            }

            $primaryContractText = $this->generateExchangeContractText(
                (string) $freshRequest['employee_name'],
                (string) $freshRequest['company_a_name'],
                (string) $freshRequest['exchange_type'],
                $finalAmount,
                $swapEmployeeName
            );

            if ($employeeContracts->create($requestId, (int) $freshRequest['employee_id'], (int) $freshRequest['company_a_id'], $primaryContractText) <= 0) {
                throw new RuntimeException('Primary employee contract failed.');
            }

            if ($swapEmployeeId !== null) {
                $swapContractText = $this->generateExchangeContractText(
                    (string) ($swapEmployeeName ?: 'Employee'),
                    (string) $freshRequest['company_b_name'],
                    (string) $freshRequest['exchange_type'],
                    $finalAmount,
                    (string) $freshRequest['employee_name']
                );

                if ($employeeContracts->create($requestId, $swapEmployeeId, (int) $freshRequest['company_b_id'], $swapContractText) <= 0) {
                    throw new RuntimeException('Swap employee contract failed.');
                }
            }

            $this->db->commit();
            try {
                foreach ([(int) ($freshRequest['company_a_user_id'] ?? 0), (int) ($freshRequest['company_b_user_id'] ?? 0)] as $companyUserId) {
                    if ($companyUserId > 0) {
                        Notifier::send(
                            $companyUserId,
                            'Exchange request for ' . (string) $freshRequest['employee_name'] . ' has been accepted.',
                            'exchange',
                            'employer/exchange-detail.php?request_id=' . $requestId
                        );
                    }
                }

                Notifier::send(
                    (int) $freshRequest['employee_id'],
                    'You have a new employment transfer contract to review.',
                    'exchange',
                    'employee/exchange-contract.php'
                );

                if ($swapEmployeeId !== null) {
                    Notifier::send(
                        $swapEmployeeId,
                        'You have a new employment transfer contract to review.',
                        'exchange',
                        'employee/exchange-contract.php'
                    );
                }
            } catch (Throwable $exception) {
                error_log('Notification failed: ' . $exception->getMessage());
            }
            return ['success' => true, 'message' => 'Exchange request accepted.'];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($storedProofPath !== null && is_file($storedProofPath)) {
                unlink($storedProofPath);
            }

            return ['success' => false, 'message' => 'Exchange request could not be accepted.'];
        }
    }

    public function getSentRequests(int $companyId): array
    {
        return $this->appendTurnMetadata($this->requests->getSentByCompany($companyId), $companyId);
    }

    public function getReceivedRequests(int $companyId): array
    {
        return $this->appendTurnMetadata($this->requests->getReceivedByCompany($companyId), $companyId);
    }

    public function getRequestDetail(int $requestId, int $companyId): ?array
    {
        if (!$this->requests->verifyOwnership($requestId, $companyId)) {
            return null;
        }

        $request = $this->requests->getById($requestId);
        if (!$request) {
            return null;
        }

        $contract = $this->contracts->getByRequestId($requestId);
        $negotiations = $this->negotiations->getByRequestId($requestId);
        $latest = $negotiations === [] ? null : $negotiations[count($negotiations) - 1];
        $request['negotiations'] = $negotiations;
        $request['contract'] = $contract;
        $request['payment'] = $contract ? $this->payments->getByContractId((int) $contract['contract_id']) : null;
        $request['current_swap_employee_id'] = $latest !== null
            ? ($latest['swap_employee_id'] !== null ? (int) $latest['swap_employee_id'] : null)
            : (!empty($request['swap_employee_id']) ? (int) $request['swap_employee_id'] : null);
        $request['current_swap_employee_name'] = $latest !== null
            ? ($latest['swap_employee_id'] !== null ? (string) ($latest['swap_employee_name'] ?? '') : '')
            : (string) ($request['swap_employee_name'] ?? '');
        $request['current_amount'] = $latest !== null
            ? ($latest['proposed_amount'] !== null ? (float) $latest['proposed_amount'] : null)
            : ($request['offered_amount'] !== null ? (float) $request['offered_amount'] : null);
        $request['turn'] = $this->resolveTurn($request, $latest, $companyId);
        $request['is_my_turn'] = $request['turn']['is_my_turn'];
        $request['waiting_for_company_name'] = $request['turn']['waiting_for_company_name'];

        return $request;
    }

    public function adminApprove(int $requestId, int $adminUserId): array
    {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            return ['success' => false, 'message' => 'Only administrators can approve exchange requests.'];
        }

        if (!$this->requests->approve($requestId, $adminUserId)) {
            return ['success' => false, 'message' => 'Exchange request could not be approved.'];
        }

        try {
            $request = $this->requests->getById($requestId);
            if ($request && !empty($request['company_b_user_id'])) {
                Notifier::send(
                    (int) $request['company_b_user_id'],
                    "You've received an exchange request from " . (string) $request['company_a_name'] . '.',
                    'exchange',
                    'employer/exchange-requests.php'
                );
            }
        } catch (Throwable $exception) {
            error_log('Notification failed: ' . $exception->getMessage());
        }

        return ['success' => true, 'message' => 'Exchange request approved.'];
    }

    public function adminReject(int $requestId, int $adminUserId): array
    {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            return ['success' => false, 'message' => 'Only administrators can reject exchange requests.'];
        }

        return $this->requests->adminReject($requestId, $adminUserId)
            ? ['success' => true, 'message' => 'Exchange request rejected.']
            : ['success' => false, 'message' => 'Exchange request could not be rejected.'];
    }

    private function validateTerms(int $companyAId, ?float $offeredAmount, ?int $swapEmployeeId): array
    {
        if ($offeredAmount === null && $swapEmployeeId === null) {
            return ['success' => false, 'message' => 'A negotiation must include an amount, a swap employee, or both.'];
        }

        if ($offeredAmount !== null && $offeredAmount <= 0) {
            return ['success' => false, 'message' => 'Please enter a valid offered amount.'];
        }

        if ($swapEmployeeId !== null && $swapEmployeeId <= 0) {
            return ['success' => false, 'message' => 'Please choose an employee to offer for the swap.'];
        }

        if ($swapEmployeeId !== null && !$this->employeeBelongsToCompany($swapEmployeeId, $companyAId)) {
            return ['success' => false, 'message' => 'Swap employee must belong to the requesting company.'];
        }

        return ['success' => true, 'message' => 'Valid.'];
    }

    private function validatePaymentProofUpload(?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'Please upload proof of payment before accepting this exchange.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Payment proof could not be uploaded.'];
        }

        if ((int) ($file['size'] ?? 0) <= 0 || (int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Payment proof must be a PDF, JPG, or PNG file up to 5MB.'];
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            return ['success' => false, 'message' => 'Payment proof must be a PDF, JPG, or PNG file.'];
        }

        $mimeType = '';
        if (is_file((string) ($file['tmp_name'] ?? ''))) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = (string) $finfo->file((string) $file['tmp_name']);
        }

        if (!in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return ['success' => false, 'message' => 'Payment proof must be a PDF, JPG, or PNG file.'];
        }

        return ['success' => true, 'message' => 'Valid.'];
    }

    private function storePaymentProofUpload(array $file): array
    {
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            throw new RuntimeException('Upload directory missing.');
        }

        $filename = 'exchange_proof_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $absolutePath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('Payment proof save failed.');
        }

        return [
            'relative' => 'uploads/' . $filename,
            'absolute' => $absolutePath,
        ];
    }

    private function generateExchangeContractText(string $employeeName, string $newCompanyName, string $exchangeType, ?float $amount, ?string $swapPartnerName): string
    {
        $terms = [];
        if ($amount !== null) {
            $terms[] = 'cash amount of ' . number_format($amount, 2);
        }
        if ($swapPartnerName !== null && trim($swapPartnerName) !== '') {
            $terms[] = 'employee swap involving ' . trim($swapPartnerName);
        }

        $termText = $terms === [] ? 'approved employee transfer' : implode(' and ', $terms);

        return "EMPLOYMENT TRANSFER AGREEMENT\n\n"
            . 'This agreement confirms the transfer of ' . $employeeName . ' to ' . $newCompanyName . ' as part of an approved employee exchange dated ' . date('F j, Y') . ".\n\n"
            . 'Exchange terms: ' . $termText . ".\n\n"
            . 'By accepting this agreement, the Employee confirms understanding of this transfer and agrees to continue their employment under ' . $newCompanyName . ', subject to the same terms of employment previously established, unless otherwise renegotiated.';
    }

    private function employeeBelongsToCompany(int $employeeId, int $companyId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM users u
             LEFT JOIN (
                SELECT a.user_id, MAX(a.app_id) AS latest_app_id
                FROM applications a
                WHERE a.status = :hired_status
                GROUP BY a.user_id
             ) latest ON latest.user_id = u.user_id
             LEFT JOIN applications latest_app ON latest_app.app_id = latest.latest_app_id
             LEFT JOIN vacancies latest_vacancy ON latest_vacancy.vacancy_id = latest_app.vacancy_id
             WHERE u.user_id = :user_id
               AND u.role = :role
               AND COALESCE(u.current_company_id, latest_vacancy.company_id) = :company_id
             LIMIT 1'
        );
        $stmt->bindValue(':hired_status', 'hired', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'employee', PDO::PARAM_STR);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    private function canActOnRequest(array $request, int $companyId): bool
    {
        $latest = $this->negotiations->getLatestByRequestId((int) $request['request_id']);
        return $this->resolveTurn($request, $latest, $companyId)['is_my_turn'];
    }

    private function appendTurnMetadata(array $requests, int $companyId): array
    {
        foreach ($requests as $index => $request) {
            $turn = $this->resolveTurn($request, $this->negotiations->getLatestByRequestId((int) $request['request_id']), $companyId);
            $requests[$index]['turn'] = $turn;
            $requests[$index]['is_my_turn'] = $turn['is_my_turn'];
            $requests[$index]['waiting_for_company_name'] = $turn['waiting_for_company_name'];
        }

        return $requests;
    }

    private function resolveTurn(array $request, ?array $latest, int $companyId): array
    {
        $status = (string) $request['status'];
        $companyAId = (int) $request['company_a_id'];
        $companyBId = (int) $request['company_b_id'];
        $turnCompanyId = null;

        if ($status === 'awaiting_approval') {
            $turnCompanyId = null;
        } elseif ($status === 'pending') {
            $turnCompanyId = $companyBId;
        } elseif ($status === 'negotiating' && $latest !== null) {
            $latestProposedBy = (int) $latest['proposed_by'];

            if ($latestProposedBy === $companyAId) {
                $turnCompanyId = $companyBId;
            } elseif ($latestProposedBy === $companyBId) {
                $turnCompanyId = $companyAId;
            }
        }

        $waitingForCompanyName = null;
        if ($turnCompanyId !== null) {
            $waitingForCompanyName = $turnCompanyId === $companyAId
                ? (string) $request['company_a_name']
                : (string) $request['company_b_name'];
        }

        return [
            'turn_company_id' => $turnCompanyId,
            'is_my_turn' => $turnCompanyId !== null && $turnCompanyId === $companyId,
            'waiting_for_company_name' => $waitingForCompanyName,
        ];
    }
}
