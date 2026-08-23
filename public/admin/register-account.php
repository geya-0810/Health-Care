<?php
// public/admin/register-account.php
require_once __DIR__ . '/../../src/config/config.php';

AuthMiddleware::requireRole(['admin', 'assist']);

$db      = Database::getConnection();
$role    = $_SESSION['role'];
$isAdmin = $role === 'admin';

// Admins can create all three types; assistants can create patients only.
$allowedTypes = $isAdmin ? ['doctor', 'assist', 'patient'] : ['patient'];

$errors  = [];
$notices = [];
$old     = ['type' => $allowedTypes[0], 'full_name' => '', 'email' => '', 'phone' => '', 'specialty' => '', 'consultation_fee' => '', 'bio' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $old  = array_merge($old, array_intersect_key($_POST, $old));
    $old['type'] = $type;

    if (!in_array($type, $allowedTypes, true)) {
        // Not in the server allowlist: either the option was tampered with or an assistant tried to create a doctor/assistant account.
        $errors[] = 'You are not allowed to create this type of account.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid name and email.';
        } elseif ($type === 'doctor' && trim($_POST['specialty'] ?? '') === '') {
            $errors[] = 'Specialty is required for a doctor account.';
        } else {
            $db->beginTransaction();
            try {
                $auth   = new AuthService($db);
                $result = $auth->createAccountByAdmin($fullName, $email, $phone, $type);
                $user   = $result['user'];

                if ($type === 'doctor') {
                    Doctor::create($db, [
                        'user_id'          => $user['user_id'],
                        'full_name'        => $fullName,
                        'specialty'        => trim($_POST['specialty']),
                        'email'            => $email,
                        'phone'            => $phone,
                        'bio'              => trim($_POST['bio'] ?? ''),
                        'consultation_fee' => (float) ($_POST['consultation_fee'] ?? 0),
                    ]);
                }

                $db->commit();
                
                $roleLabel = ['doctor' => 'Doctor', 'assist' => 'Assistant', 'patient' => 'Patient'][$type];

                // Troubleshooting errors when email sending fails (e.g., SMTP misconfiguration)
                if (APP_DEBUG) {
                    $mailSent = (new MailService())->sendAccountCredentials($user, $result['password'], $roleLabel);
                    if ($mailSent) {
                        header('Location: manage-accounts.php?created=1');
                        exit;
                    } else {
                        $notices[] = "Account created, but the email could not be sent. "
                            . "Please share these credentials with {$fullName} manually — "
                            . "Email: {$email} / Temporary password: <strong>{$result['password']}</strong>";
                    }
                } else {
                    (new MailService())->sendAccountCredentials($user, $result['password'], $roleLabel);

                    header('Location: manage-accounts.php?created=1');
                }
                exit;

            } catch (RuntimeException $e) {
                $db->rollBack();
                error_log('Register account failed: ' . $e->getMessage());
                $errors[] = appErrorMessage($e, 'Account creation failed. Please report this issue to 24035081@imail.sunway.edu.my with screenshot.');
            } catch (Throwable $e) {
                $db->rollBack();
                    error_log('Register account failed: ' . $e->getMessage());
                    $errors[] = appErrorMessage($e, 'Something went wrong while creating the account. Please report this issue to 24035081@imail.sunway.edu.my with screenshot.');
            }
        }
    }
}

$pageTitle = 'Add Account';
require_once __DIR__ . '/staff-header.php';
?>

<section style="padding:60px 0; min-height:70vh;">
    <div class="container">
        <h2 style="margin-bottom:30px;">Add Account</h2>
        
        <?php if (APP_DEBUG == 1): 
            foreach ($notices as $n): ?>
                <div class="alert alert-warning"><?= $n ?></div>
            <?php endforeach; 
        endif; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

        <form method="post" action="register-account.php" style="max-width:600px;" id="register-form">

            <div class="form-group">
                <label>Account type</label>
                <?php if (count($allowedTypes) > 1): ?>
                    <select name="type" id="type" class="form-control" onchange="toggleFields()">
                        <?php foreach ($allowedTypes as $t): ?>
                            <option value="<?= $t ?>" <?= $old['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="type" value="<?= $allowedTypes[0] ?>">
                    <input type="text" class="form-control" value="<?= ucfirst($allowedTypes[0]) ?>" disabled>
                    <p class="help-block">Your role can only register patient accounts.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Full name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($old['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Login email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($old['phone']) ?>">
            </div>

            <!-- Only doctors need to complete these fields. -->
            <div id="doctor-fields" style="display:none;">
                <div class="form-group">
                    <label>Specialty</label>
                    <input type="text" name="specialty" class="form-control" placeholder="e.g. Dermatology" value="<?= htmlspecialchars($old['specialty']) ?>">
                </div>
                <div class="form-group">
                    <label>Consultation fee per slot (RM)</label>
                    <input type="number" step="0.01" name="consultation_fee" class="form-control" value="<?= htmlspecialchars($old['consultation_fee']) ?>">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($old['bio']) ?></textarea>
                </div>
            </div>

            <p class="text-muted">A random 8-character password will be generated and emailed to this address.</p>

            <button type="submit" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">Create Account</button>
            <a href="manage-accounts.php" class="btn btn-default">Cancel</a>
        </form>

    </div>
</section>

<script>
function toggleFields() {
    var typeEl = document.getElementById('type');
    var type = typeEl ? typeEl.value : '<?= $allowedTypes[0] ?>';
    var doctorFields = document.getElementById('doctor-fields');
    var specialtyInput = doctorFields.querySelector('[name=specialty]');

    if (type === 'doctor') {
        doctorFields.style.display = 'block';
        specialtyInput.setAttribute('required', 'required');
    } else {
        doctorFields.style.display = 'none';
        specialtyInput.removeAttribute('required');
    }
}
toggleFields();
</script>

<?php require_once __DIR__ . '/staff-footer.php'; ?>
