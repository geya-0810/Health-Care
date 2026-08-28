<?php
// public/admin/dashboard.php
require_once __DIR__ . '/../../src/config/config.php';

AuthMiddleware::requireRole('admin');

$db = Database::getConnection();

$totalDoctors     = (int) $db->query("SELECT COUNT(*) FROM doctors d LEFT JOIN users u ON u.user_id = d.user_id WHERE u.is_active = 1")->fetchColumn();
$totalPatients    = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$todayAppointments = (int) $db->query(
    "SELECT COUNT(*) FROM appointments a JOIN schedules s ON s.schedule_id = a.schedule_id
     WHERE s.slot_date = CURDATE() AND a.status = 'confirmed'"
)->fetchColumn();

$pendingCount   = (int) $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
$confirmedCount = (int) $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'confirmed'")->fetchColumn();

$recentAppointments = Appointment::all($db);
$recentAppointments = array_slice($recentAppointments, 0, 8);

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/staff-header.php';
?>

    <div class="container">
        <h2 style="margin-bottom:30px;">Admin Dashboard</h2>

        <div class="row" style="margin-bottom:40px;">
            <div class="col-md-3 col-sm-6">
                <div style="background:#F5F5F5;border-radius:8px;padding:20px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#8BC63F;"><?= $totalDoctors ?></div>
                    <div style="color:#777;">Active Doctors</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div style="background:#F5F5F5;border-radius:8px;padding:20px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#8BC63F;"><?= $totalPatients ?></div>
                    <div style="color:#777;">Registered Patients</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div style="background:#F5F5F5;border-radius:8px;padding:20px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#8BC63F;"><?= $todayAppointments ?></div>
                    <div style="color:#777;">Appointments Today</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div style="background:#F5F5F5;border-radius:8px;padding:20px;text-align:center;">
                    <div style="font-size:28px;font-weight:700;color:#E0A800;"><?= $pendingCount ?></div>
                    <div style="color:#777;">Pending Doctor Confirmation</div>
                </div>
            </div>
        </div>

        <div class="row" style="margin-bottom:30px;">
            <div class="col-md-12">
                <a href="manage-accounts.php" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;margin-right:10px;">Manage Accounts</a>
                <a href="manage-schedules.php" class="btn btn-default" style="margin-right:10px;">Manage Schedules</a>
                <a href="reports.php" class="btn btn-default">Reports</a>
            </div>
        </div>

        <h4>Recent Appointments</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentAppointments as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['patient_name']) ?></td>
                    <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($a['slot_date']) ?></td>
                    <td><?= substr($a['start_time'], 0, 5) ?></td>
                    <td><?= ucfirst($a['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentAppointments)): ?>
                <tr><td colspan="5" class="text-muted">No appointments yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php require_once __DIR__ . '/staff-footer.php'; ?>
