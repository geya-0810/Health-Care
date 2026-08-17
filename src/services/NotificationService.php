<?php
// src/services/NotificationService.php

class NotificationService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function notify(int $userId, string $message, ?int $appointmentId = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, appointment_id, message) VALUES (:user_id, :appointment_id, :message)'
        );
        $stmt->execute([
            'user_id'        => $userId,
            'appointment_id' => $appointmentId,
            'message'        => $message,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getForUser(int $userId, bool $unreadOnly = false): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = :user_id';
        if ($unreadOnly) {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND user_id = :user_id'
        );
        return $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
