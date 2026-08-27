<?php
// public/admin/reports.php
require_once __DIR__ . '/../../src/config/config.php';

AuthMiddleware::requireRole('admin');

$db = Database::getConnection();

// CSV export: ?export=csv downloads the report directly without rendering the page.
if (($_GET['export'] ?? '') === 'csv') {
    $rows = Appointment::all($db);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="appointments_report_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Patient', 'Doctor', 'Date', 'Time', 'Status', 'Booked At']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['patient_name'], $r['doctor_name'], $r['slot_date'], $r['start_time'], $r['status'], $r['booked_at']]);
    }
    fclose($out);
    exit;
}

// ---------- Report statistics ----------
$byStatus = $db->query(
    "SELECT status, COUNT(*) AS total FROM appointments GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$byVisitType = $db->query(
    "SELECT visit_type, COUNT(*) AS total FROM appointments GROUP BY visit_type"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$byDoctor = $db->query(
    "SELECT u.full_name, COUNT(*) AS total
     FROM appointments a
     JOIN doctors d ON d.doctor_id = a.doctor_id
     JOIN users u ON u.user_id = d.user_id
     GROUP BY d.doctor_id ORDER BY total DESC LIMIT 10"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$totalAppointments = array_sum($byStatus);
$cancelledCount     = $byStatus['cancelled'] ?? 0;
$cancellationRate   = $totalAppointments > 0 ? round($cancelledCount / $totalAppointments * 100, 1) : 0;

$totalRevenue = (float) $db->query(
    "SELECT COALESCE(SUM(d.consultation_fee), 0)
     FROM appointments a JOIN doctors d ON d.doctor_id = a.doctor_id
     WHERE a.status = 'completed'"
)->fetchColumn();

$pageTitle = 'Reports';
require_once __DIR__ . '/staff-header.php';
?>

    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
            <h2 style="margin:0;">Reports</h2>
            <a href="reports.php?export=csv" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">
                Export CSV
            </a>
        </div>

        <p>Total appointments recorded: <strong><?= $totalAppointments ?></strong>
           &nbsp;|&nbsp; Cancellation rate: <strong><?= $cancellationRate ?>%</strong>
           &nbsp;|&nbsp; Revenue (completed visits): <strong>RM <?= number_format($totalRevenue, 2) ?></strong></p>

        <div class="row">
            <div class="col-md-4">
                <h4>By Status</h4>
                <table class="table table-bordered">
                    <?php foreach ($byStatus as $status => $count): ?>
                        <tr><td><?= ucfirst($status) ?></td><td><?= $count ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="col-md-4">
                <h4>By Visit Type</h4>
                <table class="table table-bordered">
                    <?php foreach ($byVisitType as $type => $count): ?>
                        <tr><td><?= ucfirst(str_replace('_', ' ', $type)) ?></td><td><?= $count ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="col-md-4">
                <h4>By Doctor (Top 10)</h4>
                <table class="table table-bordered">
                    <?php foreach ($byDoctor as $name => $count): ?>
                        <tr><td><?= htmlspecialchars($name) ?></td><td><?= $count ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/staff-footer.php'; ?>
