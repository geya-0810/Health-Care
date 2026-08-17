<?php
// src/middleware/AuthMiddleware.php
// 在需要登录的页面顶部（config.php之后）调用：
//   AuthMiddleware::requireLogin();          // patient/admin都可以
//   AuthMiddleware::requireRole('admin');    // 只允许admin

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
     * @param string|string[] $roles 单个角色字符串，或允许的角色数组，例如 ['admin','doctor']
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
                'admin'   => 'profile.php', // TODO: admin/dashboard.php 做好后改这里
                'doctor'  => 'profile.php', // TODO: doctor/dashboard.php 做好后改这里
                'patient' => 'profile.php',
            ];
            $target = $targetMap[$_SESSION['role'] ?? ''] ?? 'profile.php';
            header('Location: ' . APP_URL . '/' . $target);
            exit;
        }
    }
}
