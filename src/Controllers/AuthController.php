<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Helpers/Session.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Company.php';
require_once __DIR__ . '/../Models/Profile.php';

class AuthController
{
    private PDO $db;
    private User $users;
    private Company $companies;
    private Profile $profiles;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->users = new User($this->db);
        $this->companies = new Company($this->db);
        $this->profiles = new Profile($this->db);
    }

    public function login(string $email, string $password): array
    {
        $email = trim($email);
        $user = $this->users->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if (($user['status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'Your account is not active yet.'];
        }

        $this->setSession($user);

        return [
            'success' => true,
            'message' => 'Login successful.',
            'user' => $user,
            'redirect' => $this->redirectPathForUser($user, false),
        ];
    }

    public function registerJobSeeker(array $data): array
    {
        $validation = $this->validateRegistration($data);
        if (!$validation['success']) {
            return $validation;
        }

        if ($this->users->emailExists(trim($data['email']))) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        $userId = $this->users->create([
            'full_name' => trim($data['full_name']),
            'email' => trim($data['email']),
            'phone' => trim($data['phone']),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'job_seeker',
            'status' => 'active',
        ]);

        $user = $this->users->findByEmail(trim($data['email']));
        $this->setSession($user);

        return [
            'success' => true,
            'message' => 'Registration successful.',
            'user_id' => $userId,
            'redirect' => 'complete-profile.php',
        ];
    }

    public function registerEmployer(array $data): array
    {
        $validation = $this->validateRegistration($data, ['company_name', 'sector']);
        if (!$validation['success']) {
            return $validation;
        }

        if ($this->users->emailExists(trim($data['email']))) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        try {
            $this->db->beginTransaction();

            $userId = $this->users->create([
                'full_name' => trim($data['full_name']),
                'email' => trim($data['email']),
                'phone' => trim($data['phone']),
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => 'employer',
                'status' => 'active',
            ]);

            $this->companies->create([
                'user_id' => $userId,
                'company_name' => trim($data['company_name']),
                'sector' => trim($data['sector']),
                'address' => $data['address'] ?? null,
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => 'Registration could not be completed.'];
        }

        $user = $this->users->findByEmail(trim($data['email']));
        $this->setSession($user);

        return [
            'success' => true,
            'message' => 'Registration successful.',
            'user_id' => $userId,
            'redirect' => 'complete-company.php',
        ];
    }

    private function validateRegistration(array $data, array $extraRequired = []): array
    {
        $required = array_merge(['full_name', 'email', 'phone', 'password', 'confirm_password'], $extraRequired);

        foreach ($required as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                return ['success' => false, 'message' => 'Please complete all required fields.'];
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        if ($data['password'] !== $data['confirm_password']) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        return ['success' => true, 'message' => 'Validation passed.'];
    }

    private function setSession(array $user): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
    }

    public function redirectPathForCurrentUser(): string
    {
        Session::start();

        $user = [
            'user_id' => (int) ($_SESSION['user_id'] ?? 0),
            'role' => (string) ($_SESSION['role'] ?? ''),
        ];

        return $this->redirectPathForUser($user, false);
    }

    private function redirectPathForUser(array $user, bool $afterRegistration): string
    {
        $role = (string) ($user['role'] ?? '');
        $userId = (int) ($user['user_id'] ?? 0);

        if ($role === 'admin') {
            return 'admin/dashboard.php';
        }

        if ($role === 'job_seeker') {
            if ($afterRegistration || !$this->profiles->findByUserId($userId)) {
                return 'complete-profile.php';
            }

            return 'seeker-dashboard.php';
        }

        if ($role === 'employer') {
            if ($afterRegistration || !$this->companies->hasCompletedDetails($userId)) {
                return 'complete-company.php';
            }

            $company = $this->companies->findByUserId($userId);
            if (!$company || !$this->companies->isApproved((int) $company['company_id'])) {
                return 'employer/company-pending.php';
            }

            return 'employer/dashboard.php';
        }

        return 'index.php';
    }
}
