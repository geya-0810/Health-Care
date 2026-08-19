<?php
// public/profile.php
require_once __DIR__ . '/../src/config/config.php';

AuthMiddleware::requireLogin();

$db        = Database::getConnection();
$userId    = $_SESSION['user_id'];
$booking   = new BookingService($db);

$errors  = [];
$notices = [];

if (isset($_GET['booked'])) {
    $notices[] = 'Your appointment has been confirmed.';
}

// ---------- 处理表单动作 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 取消预约
    if ($action === 'cancel_appointment') {
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        try {
            $booking->cancelAppointment($appointmentId, $userId);
            $notices[] = 'Appointment cancelled.';
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Cancel appointment failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }

    // 更新个人资料
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid name and email.';
        } else {
            try {
                User::update($db, $userId, ['full_name' => $fullName, 'email' => $email, 'phone' => $phone]);
                $_SESSION['full_name'] = $fullName;
                $notices[] = 'Profile updated.';
            } catch (Throwable $e) {
                error_log('Profile update failed: ' . $e->getMessage());
                $errors[] = 'Could not update profile. The email might already be in use.';
            }
        }
    }

    // 修改密码
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = User::findById($db, $userId);

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            User::updatePassword($db, $userId, password_hash($new, PASSWORD_DEFAULT));
            $notices[] = 'Password updated.';
        }
    }
}

// ---------- 拉取最新数据 ----------
$user         = User::findById($db, $userId);
$appointments = $booking->getAppointmentsByPatient($userId);
$upcoming     = $appointments['upcoming'];
$past         = $appointments['past'];

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

function statusLabel(string $status): string {
    $map = [
        'confirmed' => 'label-success',
        'cancelled' => 'label-danger',
        'completed' => 'label-default',
        'no_show'   => 'label-warning',
    ];
    $class = $map[$status] ?? 'label-default';
    return '<span class="label ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

require_once __DIR__ . '/header.php';
?>

<section id="profile-page" style="padding:60px 0; min-height:60vh;">
    <div class="container">

        <!-- Profile header -->
        <div class="row" style="margin-bottom:30px;">
            <div class="col-md-12" style="display:flex; align-items:center; gap:20px;">
                <div style="width:70px;height:70px;border-radius:50%;background:#8BC63F;color:#fff;
                            display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:600;">
                    <?= htmlspecialchars(initials($user['full_name'])) ?>
                </div>
                <div>
                    <h3 style="margin:0;"><?= htmlspecialchars($user['full_name']) ?></h3>
                    <p style="margin:0;color:#999;"><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>
        </div>

        <?php foreach ($notices as $n): ?>
            <div class="alert alert-success"><?= htmlspecialchars($n) ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs" role="tablist" style="margin-bottom:24px;">
            <li role="presentation" class="active"><a href="#tab-appointments" aria-controls="tab-appointments" role="tab" data-toggle="tab">My Appointments</a></li>
            <li role="presentation"><a href="#tab-info" aria-controls="tab-info" role="tab" data-toggle="tab">Profile Info</a></li>
            <li role="presentation"><a href="#tab-security" aria-controls="tab-security" role="tab" data-toggle="tab">Security</a></li>
        </ul>

        <div class="tab-content">

            <!-- ===== My Appointments ===== -->
            <div role="tabpanel" class="tab-pane active" id="tab-appointments">

                <h4>Upcoming (<?= count($upcoming) ?>)</h4>
                <?php if (empty($upcoming)): ?>
                    <p class="text-muted">No upcoming appointments. <a href="appointment.php">Book one now</a>.</p>
                <?php else: ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialty</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Visit Type</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($upcoming as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($a['specialty']) ?></td>
                                <td><?= htmlspecialchars($a['slot_date']) ?></td>
                                <td><?= substr($a['start_time'], 0, 5) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $a['visit_type'])) ?></td>
                                <td><?= statusLabel($a['status']) ?></td>
                                <td>
                                    <?php if ($a['status'] === 'confirmed'): ?>
                                        <form method="post" action="profile.php" style="margin:0;"
                                              onsubmit="return confirm('Cancel this appointment?');">
                                            <input type="hidden" name="action" value="cancel_appointment">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-xs">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h4 style="margin-top:30px;">Past (<?= count($past) ?>)</h4>
                <?php if (empty($past)): ?>
                    <p class="text-muted">No past appointments yet.</p>
                <?php else: ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialty</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Visit Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($past as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($a['specialty']) ?></td>
                                <td><?= htmlspecialchars($a['slot_date']) ?></td>
                                <td><?= substr($a['start_time'], 0, 5) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $a['visit_type'])) ?></td>
                                <td><?= statusLabel($a['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- ===== Profile Info ===== -->
            <div role="tabpanel" class="tab-pane" id="tab-info">
                <form method="post" action="profile.php" class="form-horizontal" style="max-width:500px;">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label>Full name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">Save changes</button>
                </form>
            </div>

            <!-- ===== Security ===== -->
            <div role="tabpanel" class="tab-pane" id="tab-security">
                <form method="post" action="profile.php" class="form-horizontal" style="max-width:500px;">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label>Current password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm new password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">Update password</button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>