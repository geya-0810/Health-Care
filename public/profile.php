<?php
// public/profile.php

require_once __DIR__ . '/../src/config/config.php';

AuthMiddleware::requireLogin();

$db      = Database::getConnection();
$userId  = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'patient';
$booking = new BookingService($db);

$myDoctor   = $role === 'doctor' ? Doctor::findByUserId($db, $userId) : null;
$myDoctorId = $myDoctor['doctor_id'] ?? null;

$errors  = [];
$notices = [];

if (isset($_GET['booked'])) {
    $notices[] = 'Your appointment request has been sent. The doctor will confirm it shortly.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Doctor confirms an appointment; only the doctor role can confirm their own appointments.
    if ($action === 'confirm_appointment' && $role === 'doctor' && $myDoctorId) {
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        try {
            $booking->confirmAppointment($appointmentId, $myDoctorId);
            $notices[] = 'Appointment confirmed. The patient has been notified.';
        } catch (RuntimeException $e) {
            error_log('Confirm appointment failed: ' . $e->getMessage());
            $errors[] = appErrorMessage($e, 'The appointment could not be confirmed. Please report this issue to 24035081@imail.sunway.edu.my with screenshot.');
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
            error_log('Cancel appointment failed: ' . $e->getMessage());
            $errors[] = appErrorMessage($e, 'The appointment could not be cancelled. Please report this issue to 24035081@imail.sunway.edu.my with screenshot.');
        } catch (Throwable $e) {
            error_log('Cancel appointment failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }

    // Update profile, including avatar upload.
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

                // Avatar is optional; skip it without an error when no file is selected.
                if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    try {
                        // Doctors/admins/assistants use avatar/staff/; patients use avatar/user/.
                        // These correspond to the storage/avatar/staff and storage/avatar/user folders.
                        $avatarFolder = in_array($role, ['doctor', 'admin', 'assist'], true) ? 'avatar/staff' : 'avatar/user';

                        $key = StorageFactory::generateKey($avatarFolder, $_FILES['avatar']['name']);
                        $url = StorageFactory::make()->upload($_FILES['avatar']['tmp_name'], $key);
                        User::updateAvatar($db, $userId, $url);
                        $notices[] = 'Profile and avatar updated.';
                    } catch (Throwable $e) {
                        error_log('Avatar upload failed: ' . $e->getMessage());
                        $errors[] = appErrorMessage($e, 'Profile saved, but the avatar upload failed. Please report this issue to 24035081@imail.sunway.edu.my with screenshot.');
                    }
                } else {
                    $notices[] = 'Profile updated.';
                }
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

        $currentUser = User::findById($db, $userId);

        if (!password_verify($current, $currentUser['password_hash'])) {
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

$user = User::findById($db, $userId);

$showAppointmentsTab = in_array($role, ['patient', 'doctor'], true);
$showScheduleTab     = $role === 'doctor';

$scheduleTabHtml = '';
if ($showScheduleTab) {
    ob_start();
    $embedded = true;
    require __DIR__ . '/admin/manage-schedules.php';
    $scheduleTabHtml = ob_get_clean();
}

if ($role === 'doctor' && $myDoctorId) {
    $appointments = $booking->getAppointmentsByDoctor($myDoctorId);
    $pending      = $appointments['pending'];
    $upcoming     = $appointments['upcoming'];
    $past         = $appointments['past'];
} elseif ($role === 'patient') {
    $appointments = $booking->getAppointmentsByPatient($userId);
    $pending      = [];
    $upcoming     = $appointments['upcoming'];
    $past         = $appointments['past'];
} else {
    $pending = $upcoming = $past = [];
}

$activeTab = $showAppointmentsTab ? 'tab-appointments' : 'tab-info';
if (in_array($_POST['action'] ?? '', ['add_slot', 'delete_slot'], true)) {
    $activeTab = 'tab-schedule';
} elseif (($_POST['action'] ?? '') === 'update_profile') {
    $activeTab = 'tab-info';
} elseif (($_POST['action'] ?? '') === 'change_password') {
    $activeTab = 'tab-security';
} elseif (($_GET['tab'] ?? '') === 'schedule' && $showScheduleTab) {
    $activeTab = 'tab-schedule';
}

$usesStaffHeader = in_array($role, ['doctor', 'admin', 'assist'], true);
$formPrefix      = $usesStaffHeader ? '../' : '';

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
require_once __DIR__ . ($usesStaffHeader ? '/admin/staff-header.php' : '/header.php');
?>

<section id="profile-page" style="padding:60px 0; min-height:60vh;">
    <div class="container">

        <!-- Profile header -->
        <div class="row" style="margin-bottom:30px;">
            <div class="col-md-12" style="display:flex; align-items:center; gap:20px;">
                <?php if (!empty($user['avatar_url'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar"
                         style="width:70px;height:70px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <div style="width:70px;height:70px;border-radius:50%;background:#8BC63F;color:#fff;
                                display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:600;">
                        <?= htmlspecialchars(initials($user['full_name'])) ?>
                    </div>
                <?php endif; ?>
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
            <?php if ($showAppointmentsTab): ?>
                <li role="presentation" class="<?= $activeTab === 'tab-appointments' ? 'active' : '' ?>"><a href="#tab-appointments" aria-controls="tab-appointments" role="tab" data-toggle="tab">My Appointments</a></li>
            <?php endif; ?>
            <?php if ($showScheduleTab): ?>
                <li role="presentation" class="<?= $activeTab === 'tab-schedule' ? 'active' : '' ?>"><a href="#tab-schedule" aria-controls="tab-schedule" role="tab" data-toggle="tab">My Schedule</a></li>
            <?php endif; ?>
            <li role="presentation" class="<?= $activeTab === 'tab-info' ? 'active' : '' ?>"><a href="#tab-info" aria-controls="tab-info" role="tab" data-toggle="tab">Profile Info</a></li>
            <li role="presentation" class="<?= $activeTab === 'tab-security' ? 'active' : '' ?>"><a href="#tab-security" aria-controls="tab-security" role="tab" data-toggle="tab">Security</a></li>
        </ul>

        <div class="tab-content">

            <?php if ($showAppointmentsTab): ?>
                <div role="tabpanel" class="tab-pane <?= $activeTab === 'tab-appointments' ? 'active' : '' ?>" id="tab-appointments">
                    <?php require __DIR__ . '/profile-tabs/appointments.php'; ?>
                </div>
            <?php endif; ?>

            <?php if ($showScheduleTab): ?>
                <div role="tabpanel" class="tab-pane <?= $activeTab === 'tab-schedule' ? 'active' : '' ?>" id="tab-schedule">
                    <?= $scheduleTabHtml ?>
                </div>
            <?php endif; ?>

            <div role="tabpanel" class="tab-pane <?= $activeTab === 'tab-info' ? 'active' : '' ?>" id="tab-info">
                <?php require __DIR__ . '/profile-tabs/info.php'; ?>
            </div>

            <div role="tabpanel" class="tab-pane <?= $activeTab === 'tab-security' ? 'active' : '' ?>" id="tab-security">
                <?php require __DIR__ . '/profile-tabs/security.php'; ?>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/' . ($usesStaffHeader ? 'admin/staff-footer.php' : 'footer.php'); ?>
