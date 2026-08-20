<?php
// src/models/Doctor.php

class Doctor
{
    public static function all(PDO $db, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM doctors';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY full_name ASC';
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByUserId(PDO $db, int $userId): ?array
    {
        $stmt = $db->prepare('SELECT * FROM doctors WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM doctors WHERE doctor_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            'INSERT INTO doctors (user_id, full_name, specialty, email, phone, bio, profile_image_url, consultation_fee)
             VALUES (:user_id, :full_name, :specialty, :email, :phone, :bio, :profile_image_url, :consultation_fee)'
        );
        $stmt->execute([
            'user_id'            => $data['user_id'] ?? null,
            'full_name'          => $data['full_name'],
            'specialty'          => $data['specialty'],
            'email'              => $data['email'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'bio'                => $data['bio'] ?? null,
            'profile_image_url'  => $data['profile_image_url'] ?? null,
            'consultation_fee'   => $data['consultation_fee'] ?? 0,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $data): bool
    {
        $stmt = $db->prepare(
            'UPDATE doctors SET full_name=:full_name, specialty=:specialty, email=:email,
             phone=:phone, bio=:bio, consultation_fee=:consultation_fee, is_active=:is_active
             WHERE doctor_id=:id'
        );
        return $stmt->execute([
            'full_name'        => $data['full_name'],
            'specialty'        => $data['specialty'],
            'email'            => $data['email'] ?? null,
            'phone'            => $data['phone'] ?? null,
            'bio'              => $data['bio'] ?? null,
            'consultation_fee' => $data['consultation_fee'] ?? 0,
            'is_active'        => $data['is_active'] ?? 1,
            'id'               => $id,
        ]);
    }

    public static function updateImage(PDO $db, int $id, string $imageUrl): bool
    {
        $stmt = $db->prepare('UPDATE doctors SET profile_image_url = :url WHERE doctor_id = :id');
        return $stmt->execute(['url' => $imageUrl, 'id' => $id]);
    }

    public static function delete(PDO $db, int $id): bool
    {
        // Soft delete: keep the record to preserve historical appointment data.
        $stmt = $db->prepare('UPDATE doctors SET is_active = 0 WHERE doctor_id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
