<?php
// src/services/AuthService.php

class AuthService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @throws RuntimeException 如果email已被注册
     */
    public function register(string $fullName, string $email, string $phone, string $password): array
    {
        if (User::findByEmail($this->db, $email)) {
            throw new RuntimeException('This email is already registered.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::create($this->db, [
            'full_name'     => $fullName,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => $hash,
            'role'          => 'patient',
        ]);

        return User::findById($this->db, $userId);
    }

    /**
     * 登录成功返回用户数组，失败返回 null
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = User::findByEmail($this->db, $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    public function logAppointmentReminder(): void
    {
        // 占位：预留给 cron / scheduled task 发送预约提醒时调用
    }

    public function startSession(array $user): void
    {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
