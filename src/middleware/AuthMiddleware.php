<?php
// src/middleware/AuthMiddleware.php
// Call this at the top of pages that require authentication, after config.php:
//   AuthMiddleware::requireLogin();          // patients and admins are allowed
//   AuthMiddleware::requireRole('admin');    // admins only

class AuthMiddleware
{
    public static function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /**
    * @param string|string[] $roles A single role string or an array of allowed roles, e.g. ['admin','doctor']
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array($_SESSION['role'] ?? '', $allowed, true)) {
            http_response_code(403);
            echo '403 Forbidden — you do not have permission to view this page.';
            exit;
        }
    }

    public static function redirectIfLoggedIn(): void
    {
        if (isset($_SESSION['user_id'])) {
            $targetMap = [
                'admin'   => 'admin/dashboard.php',
                'assist'  => 'admin/manage-schedules.php',
                'doctor'  => 'profile.php',
                'patient' => 'profile.php',
            ];
            $target = $targetMap[$_SESSION['role'] ?? ''] ?? 'profile.php';
            header('Location: ' . APP_URL . '/' . $target);
            exit;
        }
    }
}
