<?php
// public/profile.php
require_once __DIR__ . '/../src/config/config.php';

AuthMiddleware::requireLogin();

$db      = Database::getConnection();
$userId  = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'patient';
$booking = new BookingService($db);

// Doctor accounts must first resolve their doctor_id through doctors.user_id.
$myDoctor   = $role === 'doctor' ? Doctor::findByUserId($db, $userId) : null;
$myDoctorId = $myDoctor['doctor_id'] ?? null;

$errors  = [];
$notices = [];

if (isset($_GET['booked'])) {
    $notices[] = 'Your appointment request has been sent. The doctor will confirm it shortly.';
}

// ---------- Handle form actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Doctor confirms an appointment; only the doctor role can confirm their own appointments.
    if ($action === 'confirm_appointment' && $role === 'doctor' && $myDoctorId) {
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        try {
            $booking->confirmAppointment($appointmentId, $myDoctorId);
            $notices[] = 'Appointment confirmed. The patient has been notified.';
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Confirm appointment failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }

    // Cancel appointment; patients cancel their own, while doctors cancel appointments assigned to them.
    if ($action === 'cancel_appointment') {
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        try {
            $ownerPatientId = $role === 'patient' ? $userId : null; // Doctors do not need a patient ownership check.
            $booking->cancelAppointment($appointmentId, $ownerPatientId);
            $notices[] = 'Appointment cancelled.';
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Cancel appointment failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }

    // Update profile.
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

    // Change password.
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

// ---------- Load the latest data ----------
$user = User::findById($db, $userId);

if ($role === 'doctor' && $myDoctorId) {
    $appointments = $booking->getAppointmentsByDoctor($myDoctorId);
    $pending      = $appointments['pending'];
    $upcoming     = $appointments['upcoming'];
    $past         = $appointments['past'];
} else {
    $appointments = $booking->getAppointmentsByPatient($userId);
    $pending      = [];
    $upcoming     = $appointments['upcoming'];
    $past         = $appointments['past'];
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

function statusLabel(string $status): string {
    $map = [
        'pending'   => 'label-warning',
        'confirmed' => 'label-success',
        'cancelled' => 'label-danger',
        'completed' => 'label-default',
        'no_show'   => 'label-warning',
    ];
    $class = $map[$status] ?? 'label-default';
    return '<span class="label ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

$pageTitle = 'My Profile';
if ($role === 'doctor') {
    require_once __DIR__ . '/admin/staff-header.php';
} else {
    require_once __DIR__ . '/header.php';
}
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

                <?php if ($role === 'doctor'): ?>
                    <h4>Pending Requests (<?= count($pending) ?>)</h4>
                    <?php if (empty($pending)): ?>
                        <p class="text-muted">No pending requests.</p>
                    <?php else: ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr><th>Patient</th><th>Date</th><th>Time</th><th>Visit Type</th><th>Reason</th><th></th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pending as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($a['slot_date']) ?></td>
                                    <td><?= substr($a['start_time'], 0, 5) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $a['visit_type'])) ?></td>
                                    <td><?= htmlspecialchars($a['reason'] ?: '—') ?></td>
                                    <td style="white-space:nowrap;">
                                        <form method="post" action="profile.php" style="display:inline-block;margin:0;">
                                            <input type="hidden" name="action" value="confirm_appointment">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-success">Confirm</button>
                                        </form>
                                        <form method="post" action="profile.php" style="display:inline-block;margin:0;"
                                              onsubmit="return confirm('Decline this request?');">
                                            <input type="hidden" name="action" value="cancel_appointment">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger">Decline</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>

                <h4>Upcoming (<?= count($upcoming) ?>)</h4>
                <?php if (empty($upcoming)): ?>
                    <p class="text-muted">
                        <?= $role === 'doctor' ? 'No confirmed upcoming appointments.' : 'No upcoming appointments. <a href="appointment.php">Book one now</a>.' ?>
                    </p>
                <?php else: ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?= $role === 'doctor' ? 'Patient' : 'Doctor' ?></th>
                                <th><?= $role === 'doctor' ? 'Contact' : 'Specialty' ?></th>
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
                                <?php if ($role === 'doctor'): ?>
                                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($a['patient_email']) ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                                    <td><?= htmlspecialchars($a['specialty']) ?></td>
                                <?php endif; ?>
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
                                <th><?= $role === 'doctor' ? 'Patient' : 'Doctor' ?></th>
                                <th><?= $role === 'doctor' ? 'Contact' : 'Specialty' ?></th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Visit Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($past as $a): ?>
                            <tr>
                                <?php if ($role === 'doctor'): ?>
                                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($a['patient_email']) ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                                    <td><?= htmlspecialchars($a['specialty']) ?></td>
                                <?php endif; ?>
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
                <form method="post" action="<?= ($role === 'doctor' ? '../' : '');?>profile.php" class="form-horizontal" style="max-width:500px;">
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

<?php require_once __DIR__ . '/' . ($role === 'doctor' ? 'admin/staff-footer.php' : 'footer.php'); ?>
