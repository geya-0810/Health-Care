<?php
// src/models/User.php

class User
{
    public static function findByEmail(PDO $db, string $email): ?array
    {
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            'INSERT INTO users (full_name, email, password_hash, phone, role)
             VALUES (:full_name, :email, :password_hash, :phone, :role)'
        );
        $stmt->execute([
            'full_name'      => $data['full_name'],
            'email'          => $data['email'],
            'password_hash'  => $data['password_hash'],
            'phone'          => $data['phone'] ?? null,
            'role'           => $data['role'] ?? 'patient',
        ]);
        return (int) $db->lastInsertId();
    }
    
    // Used by admin/assistant account editing in manage-accounts.php; includes is_active.
    public static function adminUpdate(PDO $db, int $id, array $data): bool
    {
        $stmt = $db->prepare(
            'UPDATE users SET full_name = :full_name, email = :email, phone = :phone, is_active = :is_active
             WHERE user_id = :id'
        );
        return $stmt->execute([
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'id'        => $id,
        ]);
    }



    public static function update(PDO $db, int $id, array $data): bool
    {
        $stmt = $db->prepare(
            'UPDATE users SET full_name = :full_name, email = :email, phone = :phone
             WHERE user_id = :id'
        );
        return $stmt->execute([
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'id'        => $id,
        ]);
    }

    public static function updatePassword(PDO $db, int $id, string $passwordHash): bool
    {
        $stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
        return $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }
}
