<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/ExchangeRequest.php';
require_once __DIR__ . '/../Models/ExchangeNegotiation.php';
require_once __DIR__ . '/../Models/ExchangeContract.php';
require_once __DIR__ . '/../Models/PaymentRecord.php';
require_once __DIR__ . '/../Models/User.php';

class ExchangeController
{
    private PDO $db;
    private ExchangeRequest $requests;
    private ExchangeNegotiation $negotiations;
    private ExchangeContract $contracts;
    private PaymentRecord $payments;
    private User $users;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->requests = new ExchangeRequest($this->db);
        $this->negotiations = new ExchangeNegotiation($this->db);
        $this->contracts = new ExchangeContract($this->db);
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
        $validation = $this->validateTerms($companyAId, $exchangeType, $offeredAmount, $swapEmployeeId);
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
            'offered_amount' => $exchangeType === 'paid' ? $offeredAmount : null,
            'swap_employee_id' => $exchangeType === 'swap' ? $swapEmployeeId : null,
            'status' => 'pending',
            'message' => trim($message),
        ]);

        return $requestId > 0
            ? ['success' => true, 'message' => 'Exchange request sent.']
            : ['success' => false, 'message' => 'Exchange request could not be sent.'];
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
            return $this->requests->updateStatus($requestId, 'rejected')
                ? ['success' => true, 'message' => 'Exchange request rejected.']
                : ['success' => false, 'message' => 'Exchange request could not be rejected.'];
        }

        if ($action === 'negotiate') {
            $exchangeType = (string) $request['exchange_type'];
            $amount = $exchangeType === 'paid' ? (float) ($counterData['offered_amount'] ?? 0) : null;
            $swapEmployeeId = $exchangeType === 'swap' ? (int) ($counterData['swap_employee_id'] ?? 0) : null;
            $validation = $this->validateTerms($companyId, $exchangeType, $amount, $swapEmployeeId);
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

            return ['success' => true, 'message' => 'Counter-proposal sent.'];
        }

        if ($action !== 'accept') {
            return ['success' => false, 'message' => 'Invalid exchange action.'];
        }

        try {
            $this->db->beginTransaction();

            $requests = new ExchangeRequest($this->db);
            $negotiations = new ExchangeNegotiation($this->db);
            $contracts = new ExchangeContract($this->db);
            $payments = new PaymentRecord($this->db);
            $users = new User($this->db);

            $freshRequest = $requests->getById($requestId);
            if (!$freshRequest) {
                throw new RuntimeException('Request missing.');
            }

            $history = $negotiations->getByRequestId($requestId);
            $latest = $history === [] ? null : $history[count($history) - 1];
            $finalAmount = $latest && $latest['proposed_amount'] !== null ? (float) $latest['proposed_amount'] : ($freshRequest['offered_amount'] !== null ? (float) $freshRequest['offered_amount'] : null);
            $swapEmployeeId = $latest && $latest['swap_employee_id'] !== null ? (int) $latest['swap_employee_id'] : (!empty($freshRequest['swap_employee_id']) ? (int) $freshRequest['swap_employee_id'] : null);

            if (!$requests->updateStatus($requestId, 'accepted')) {
                throw new RuntimeException('Status update failed.');
            }

            $contractId = $contracts->create($requestId, $finalAmount, $swapEmployeeId);
            if ($contractId <= 0) {
                throw new RuntimeException('Contract failed.');
            }

            if ($freshRequest['exchange_type'] === 'paid' && $finalAmount !== null) {
                if ($payments->create($contractId, $finalAmount, (int) $freshRequest['company_a_id'], (int) $freshRequest['company_b_id']) <= 0) {
                    throw new RuntimeException('Payment record failed.');
                }
            }

            if (!$users->updateCurrentCompany((int) $freshRequest['employee_id'], (int) $freshRequest['company_a_id'])) {
                throw new RuntimeException('Employee move failed.');
            }

            if ($freshRequest['exchange_type'] === 'swap') {
                if ($swapEmployeeId === null || !$this->employeeBelongsToCompany($swapEmployeeId, (int) $freshRequest['company_a_id'])) {
                    throw new RuntimeException('Swap employee invalid.');
                }
                if (!$users->updateCurrentCompany($swapEmployeeId, (int) $freshRequest['company_b_id'])) {
                    throw new RuntimeException('Swap move failed.');
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Exchange request accepted.'];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
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
        $request['negotiations'] = $this->negotiations->getByRequestId($requestId);
        $request['contract'] = $contract;
        $request['payment'] = $contract ? $this->payments->getByContractId((int) $contract['contract_id']) : null;
        $request['turn'] = $this->resolveTurn($request, $this->negotiations->getLatestByRequestId($requestId), $companyId);
        $request['is_my_turn'] = $request['turn']['is_my_turn'];
        $request['waiting_for_company_name'] = $request['turn']['waiting_for_company_name'];

        return $request;
    }

    private function validateTerms(int $companyId, string $exchangeType, ?float $offeredAmount, ?int $swapEmployeeId): array
    {
        if (!in_array($exchangeType, ['paid', 'swap'], true)) {
            return ['success' => false, 'message' => 'Invalid exchange type.'];
        }

        if ($exchangeType === 'paid' && ($offeredAmount === null || $offeredAmount <= 0)) {
            return ['success' => false, 'message' => 'Please enter a valid offered amount.'];
        }

        if ($exchangeType === 'swap') {
            if ($swapEmployeeId === null || $swapEmployeeId <= 0) {
                return ['success' => false, 'message' => 'Please choose an employee to offer for the swap.'];
            }

            if (!$this->employeeBelongsToCompany($swapEmployeeId, $companyId)) {
                return ['success' => false, 'message' => 'The swap employee must belong to your company.'];
            }
        }

        return ['success' => true, 'message' => 'Valid.'];
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

        if ($status === 'pending') {
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
