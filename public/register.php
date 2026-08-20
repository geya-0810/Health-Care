<?php
// public/register.php
// Patient self-registration only. Master admins create doctor/admin accounts in the backend.
require_once __DIR__ . '/../src/config/config.php';

AuthMiddleware::redirectIfLoggedIn();

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['phone']     = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirmPassword  = $_POST['confirm_password'] ?? '';

    if ($old['full_name'] === '' || $old['email'] === '' || $password === '') {
        $errors[] = 'Full name, email and password are required.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $auth = new AuthService(Database::getConnection());
            $auth->register($old['full_name'], $old['email'], $old['phone'], $password);
            // The role is fixed to 'patient' in AuthService::register; the form has no role option,
            // so doctor/admin accounts cannot be created through this form.

            header('Location: login.php?registered=1');
            exit;

        } catch (RuntimeException $e) {
            // Business errors such as an already-registered email.
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Registration failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}

$pageTitle  = 'Register';
$activePage = '';
require_once __DIR__ . '/header.php';
?>

<div class="auth-wrapper d-flex align-items-center py-5">
    <div class="container">
        <div class="row g-4 align-items-stretch justify-content-center">

            <div class="col-lg-5">
                <div class="auth-card h-100">
                    <h3 class="mb-1">Create your account</h3>
                    <p class="text-muted mb-4">For patients booking appointments. It only takes a minute.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2">
                            <?php foreach ($errors as $e): ?>
                                <div><?= htmlspecialchars($e) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" id="registerForm" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Full name</label>
                            <input type="text" name="full_name" class="form-control" placeholder="Tan Wei Ling"
                                   value="<?= htmlspecialchars($old['full_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" placeholder="you@example.com"
                                   value="<?= htmlspecialchars($old['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="012-3456789"
                                   value="<?= htmlspecialchars($old['phone']) ?>">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="min. 8 characters" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm password</label>
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="re-enter password" required>
                            </div>
                        </div>
                        <div id="passwordMismatch" class="text-danger small mb-3" style="display:none;">
                            Passwords do not match.
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label small text-muted" for="terms">
                                I agree to the Terms of Service and Privacy Policy
                            </label>
                        </div>
                        <button type="submit" class="btn btn-health w-100 py-2">Create account</button>
                    </form>

                    <p class="auth-switch text-center mt-4 mb-0">
                        Already have an account? <a href="login.php">Log in</a>
                    </p>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-flex">
                <div class="auth-visual w-100">
                    <div class="eyebrow">Join Health Center</div>
                    <h2>Book consultations in a few clicks.</h2>
                    <p class="mb-0" style="color:rgba(255,255,255,0.75)">
                        Create an account to view doctors, check availability, and manage all your appointments in one place.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function (e) {
    const pw = document.getElementById('password').value;
    const confirm = document.getElementById('confirmPassword').value;
    const warning = document.getElementById('passwordMismatch');
    if (pw !== confirm) {
        e.preventDefault();
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
