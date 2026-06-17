<?php

require_once __DIR__ . '/../Config/Database.php';

class User
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $phone = $data['phone'] ?? null;
        $stmt = $this->db->prepare(
            'INSERT INTO users (full_name, email, password, phone, role, status)
             VALUES (:full_name, :email, :password, :phone, :role, :status)'
        );
        $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $data['password'], PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, $phone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':role', $data['role'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'active', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
