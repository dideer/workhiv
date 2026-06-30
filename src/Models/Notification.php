<?php

require_once __DIR__ . '/../Config/Database.php';

class Notification
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(int $userId, string $message, string $type, ?string $linkUrl = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, message, type, link_url, is_read)
             VALUES (:user_id, :message, :type, :link_url, 0)'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':link_url', $linkUrl, $linkUrl === null || $linkUrl === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function getRecentByUser(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getByIdForUser(int $notifId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE notif_id = :notif_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->bindValue(':notif_id', $notifId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $notification = $stmt->fetch();
        return $notification ?: null;
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function markAsRead(int $notifId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE notif_id = :notif_id AND user_id = :user_id'
        );
        $stmt->bindValue(':notif_id', $notifId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
