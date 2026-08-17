<?php
// public/profile.php
session_start();
$pageTitle  = 'My Profile';
$activePage = 'profile';
require_once __DIR__ . '/header.php';

    // if (!isset($_SESSION['user_id'])) {
    //     header('Location: login.php');
    //     exit;
    // }

// TODO: 用真实数据替换以下部分
// $userId = $_SESSION['user_id'];
// $user = (new UserService())->find($userId);
// $appointments = (new BookingService())->getAppointmentsByPatient($userId);

$user = [
    'full_name' => 'Tan Wei Ling',
    'email'     => 'weiling.tan@example.com',
    'phone'     => '012-987 6543',
];

$appointments = [
    ['doctor' => 'Dr. Lim Chee Keong', 'specialty' => 'General Practice', 'date' => '20 Aug 2026', 'time' => '9:00 AM', 'status' => 'confirmed'],
    ['doctor' => 'Dr. Nurul Huda',     'specialty' => 'Pediatrics',       'date' => '25 Aug 2026', 'time' => '10:30 AM', 'status' => 'pending'],
    ['doctor' => 'Dr. Lim Chee Keong', 'specialty' => 'General Practice', 'date' => '02 Jul 2026', 'time' => '9:30 AM', 'status' => 'completed'],
    ['doctor' => 'Dr. Nurul Huda',     'specialty' => 'Pediatrics',       'date' => '18 Jun 2026', 'time' => '11:00 AM', 'status' => 'cancelled'],
];

$upcoming = array_filter($appointments, fn($a) => in_array($a['status'], ['confirmed', 'pending']));
$past     = array_filter($appointments, fn($a) => in_array($a['status'], ['completed', 'cancelled']));

function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

function statusBadge(string $status): string {
    $map = [
        'confirmed' => 'badge-status-confirmed',
        'pending'   => 'badge-status-pending',
        'completed' => 'badge-status-completed',
        'cancelled' => 'badge-status-cancelled',
    ];
    $class = $map[$status] ?? 'badge-status-pending';
    return '<span class="badge rounded-pill ' . $class . ' px-3 py-2">' . ucfirst($status) . '</span>';
}

?>

<!-- Profile header -->
<div class="profile-header">
    <div class="container d-flex align-items-center gap-3">
        <div class="profile-avatar"><?= htmlspecialchars(initials($user['full_name'])) ?></div>
        <div>
            <h4 class="mb-1"><?= htmlspecialchars($user['full_name']) ?></h4>
            <div style="color:rgba(255,255,255,0.7)"><?= htmlspecialchars($user['email']) ?></div>
        </div>
    </div>
</div>

<div class="container py-5">

    <ul class="nav profile-tabs mb-4" id="profileTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button">
                <i class="ti ti-calendar-event me-1"></i>My Appointments
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">
                <i class="ti ti-user me-1"></i>Profile Info
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-security" type="button">
                <i class="ti ti-lock me-1"></i>Security
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ===================== APPOINTMENTS ===================== -->
        <div class="tab-pane fade show active" id="tab-appointments">

            <ul class="nav nav-pills mb-4 small" id="apptSubTab">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#appt-upcoming" type="button">
                        Upcoming <span class="badge bg-secondary ms-1"><?= count($upcoming) ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#appt-past" type="button">
                        Past <span class="badge bg-secondary ms-1"><?= count($past) ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Upcoming -->
                <div class="tab-pane fade show active" id="appt-upcoming">
                    <?php if (empty($upcoming)): ?>
                        <p class="text-muted">You have no upcoming appointments.</p>
                    <?php else: foreach ($upcoming as $a): ?>
                        <div class="appointment-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="appointment-doctor-avatar"><?= htmlspecialchars(initials($a['doctor'])) ?></div>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($a['doctor']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($a['specialty']) ?></div>
                                </div>
                            </div>
                            <div class="text-center d-none d-md-block">
                                <div class="fw-semibold"><?= htmlspecialchars($a['date']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($a['time']) ?></div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <?= statusBadge($a['status']) ?>
                                <button class="btn btn-sm btn-outline-danger" type="button">Cancel</button>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Past -->
                <div class="tab-pane fade" id="appt-past">
                    <?php if (empty($past)): ?>
                        <p class="text-muted">No past appointments yet.</p>
                    <?php else: foreach ($past as $a): ?>
                        <div class="appointment-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="appointment-doctor-avatar"><?= htmlspecialchars(initials($a['doctor'])) ?></div>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($a['doctor']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($a['specialty']) ?></div>
                                </div>
                            </div>
                            <div class="text-center d-none d-md-block">
                                <div class="fw-semibold"><?= htmlspecialchars($a['date']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($a['time']) ?></div>
                            </div>
                            <div><?= statusBadge($a['status']) ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================== PROFILE INFO ===================== -->
        <div class="tab-pane fade" id="tab-info">
            <div class="card-health p-4" style="max-width:560px">
                <form method="POST" action="update-profile.php">
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Phone number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>
                    <button type="submit" class="btn btn-health px-4">Save changes</button>
                </form>
            </div>
        </div>

        <!-- ===================== SECURITY ===================== -->
        <div class="tab-pane fade" id="tab-security">
            <div class="card-health p-4" style="max-width:560px">
                <form method="POST" action="change-password.php">
                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm new password</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-health px-4">Update password</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>