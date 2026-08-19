<?php
// public/login.php
require_once __DIR__ . '/../src/config/config.php';

// 已登录就不用再看登录页
AuthMiddleware::redirectIfLoggedIn();

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldEmail = $email;

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $auth = new AuthService(Database::getConnection());
        $user = $auth->attemptLogin($email, $password);

        if ($user) {
            $auth->startSession($user);

            // 按角色跳转到各自的首页
            // doctor/admin dashboard做好之前先统一导去profile.php，避免404
            $redirectMap = [
                'patient' => 'profile.php',
                'doctor'  => 'profile.php',   // TODO: 做好 doctor/dashboard.php 后改成 'doctor/dashboard.php'
                'admin'   => 'profile.php',   // TODO: 做好 admin/dashboard.php 后改成 'admin/dashboard.php'
            ];
            header('Location: ' . ($redirectMap[$user['role']] ?? 'profile.php'));
            exit;
        } else {
            $errors[] = 'Incorrect email or password.';
        }
    }
}

$pageTitle  = 'Log In';
$activePage = '';
require_once __DIR__ . '/header.php';
?>

<div class="auth-wrapper d-flex align-items-center py-5">
    <div class="container">
        <div class="row g-4 align-items-stretch justify-content-center">

            <div class="col-lg-5 d-none d-lg-flex">
                <div class="auth-visual w-100">
                    <div class="eyebrow">Welcome back</div>
                    <h2>Your health journey continues here.</h2>
                    <p class="mb-0" style="color:rgba(255,255,255,0.75)">
                        Log in to book appointments, view your history, and manage your consultations.
                    </p>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="auth-card h-100">
                    <h3 class="mb-1">Log in to your account</h3>
                    <p class="text-muted mb-4">Patients, doctors and admin all log in here.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2">
                            <?php foreach ($errors as $e): ?>
                                <div><?= htmlspecialchars($e) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert alert-success py-2">Account created — please log in.</div>
                    <?php endif; ?>

                    <?php if (isset($_GET['logged_out'])): ?>
                        <div class="alert alert-success py-2">You have been logged out.</div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" placeholder="you@example.com"
                                   value="<?= htmlspecialchars($oldEmail) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label small text-muted" for="remember">Remember me</label>
                            </div>
                            <a href="forgot-password.php" class="small">Forgot password?</a>
                        </div>
                        <button type="submit" class="btn btn-health w-100 py-2">Log in</button>
                    </form>

                    <p class="auth-switch text-center mt-4 mb-0">
                        Don't have an account? <a href="register.php">Create one</a>
                    </p>
                    <p class="auth-switch text-center mt-2 mb-0" style="font-size:0.8rem">
                        Doctor or admin? Use the account created for you by the clinic.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
