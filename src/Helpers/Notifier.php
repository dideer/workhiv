<?php

require_once __DIR__ . '/../Models/Notification.php';

class Notifier
{
    public static function send(int $userId, string $message, string $type, ?string $linkUrl = null): void
    {
        $model = new Notification();
        $model->create($userId, $message, $type, $linkUrl);
    }
}
