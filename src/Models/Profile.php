<?php

require_once __DIR__ . '/../Config/Database.php';

class Profile
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO profiles (user_id, date_of_birth, gender, address, profile_photo)
             VALUES (:user_id, :date_of_birth, :gender, :address, :profile_photo)'
        );
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':date_of_birth', $data['date_of_birth'], PDO::PARAM_STR);
        $stmt->bindValue(':gender', $data['gender'], PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
        $stmt->bindValue(':profile_photo', $data['profile_photo'] ?? null, empty($data['profile_photo']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM profiles WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $profile = $stmt->fetch();
        return $profile ?: null;
    }

    public function update(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE profiles
             SET date_of_birth = :date_of_birth,
                 gender = :gender,
                 address = :address,
                 profile_photo = :profile_photo
             WHERE user_id = :user_id'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':date_of_birth', $data['date_of_birth'], PDO::PARAM_STR);
        $stmt->bindValue(':gender', $data['gender'], PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
        $stmt->bindValue(':profile_photo', $data['profile_photo'] ?? null, empty($data['profile_photo']) ? PDO::PARAM_NULL : PDO::PARAM_STR);

        return $stmt->execute();
    }
}
