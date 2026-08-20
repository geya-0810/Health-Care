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
    * For admins/assistants: create doctor/assistant/patient accounts with an automatically generated 8-character password.
    * The caller (the page's requireRole check) handles permissions; this method only creates the account.
     *
    * @return array{user: array, password: string} The password is returned in plain text only once;
    *                                                the caller must not store it after sending the email.
    * @throws RuntimeException If the email is already registered.
     */
    public function createAccountByAdmin(string $fullName, string $email, string $phone, string $role): array
    {
        if (!in_array($role, ['doctor', 'assist', 'patient'], true)) {
            throw new InvalidArgumentException('Invalid role.');
        }
        if (User::findByEmail($this->db, $email)) {
            throw new RuntimeException('This email is already registered.');
        }

        $plainPassword = self::generateRandomPassword();
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $userId = User::create($this->db, [
            'full_name'     => $fullName,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => $hash,
            'role'          => $role,
        ]);

        return [
            'user'     => User::findById($this->db, $userId),
            'password' => $plainPassword,
        ];
    }

    /**
    * Generate an 8-character random password, excluding easily confused characters (0/O, 1/l/I).
     */
    public static function generateRandomPassword(int $length = 8): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
    * @throws RuntimeException If the email is already registered.
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
    * Returns the user array on successful login, or null on failure.
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = User::findByEmail($this->db, $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        if (isset($user['is_active']) && !$user['is_active']) {
            throw new RuntimeException('This account has been deactivated. Please contact the clinic.');
        }
        return $user;
    }

    public function logAppointmentReminder(): void
    {
        // Placeholder reserved for cron/scheduled tasks that send appointment reminders.
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
