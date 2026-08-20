<?php
// public/admin/manage-schedules.php
require_once __DIR__ . '/../../src/config/config.php';

AuthMiddleware::requireRole(['admin', 'assist']);

$db      = Database::getConnection();
$errors  = [];
$notices = [];

$doctors = Doctor::all($db);

$selectedDoctorId = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : ($doctors[0]['doctor_id'] ?? 0);
$selectedDate     = $_GET['date'] ?? date('Y-m-d');

// ---------- Add slot ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_slot') {
    $doctorId  = (int) $_POST['doctor_id'];
    $date      = $_POST['slot_date'];
    $startTime = $_POST['start_time'];
    $endTime   = $_POST['end_time'];

    if ($endTime <= $startTime) {
        $errors[] = 'End time must be after start time.';
    } else {
        try {
            Schedule::create($db, $doctorId, $date, $startTime, $endTime);
            $notices[] = 'Time slot added.';
        } catch (PDOException $e) {
            // The UNIQUE constraint was hit: this doctor already has a slot at this date and time.
            $errors[] = 'This exact time slot already exists for this doctor on this date.';
        }
    }
    $selectedDoctorId = $doctorId;
    $selectedDate     = $date;
}

// ---------- Delete slot (only slots without bookings can be deleted) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_slot') {
    $scheduleId = (int) $_POST['schedule_id'];
    if (Schedule::delete($db, $scheduleId)) {
        $notices[] = 'Time slot removed.';
    } else {
        $errors[] = 'Cannot remove a slot that has already been booked. Cancel the appointment first.';
    }
    $selectedDoctorId = (int) $_POST['doctor_id'];
    $selectedDate     = $_POST['slot_date'];
}

$slots = $selectedDoctorId ? Schedule::forDoctorOnDate($db, $selectedDoctorId, $selectedDate) : [];

$pageTitle = 'Manage Schedules';
require_once __DIR__ . '/staff-header.php';
?>

<section style="padding:60px 0; min-height:70vh;">
    <div class="container">
        <h2 style="margin-bottom:30px;">Manage Schedules</h2>
        <p class="text-muted">As admin, you can view and manage every doctor's schedule.</p>

        <?php foreach ($notices as $n): ?><div class="alert alert-success"><?= htmlspecialchars($n) ?></div><?php endforeach; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

        <!-- Doctor + date filter -->
        <form method="get" action="manage-schedules.php" class="form-inline" style="margin-bottom:24px;">
            <div class="form-group" style="margin-right:10px;">
                <label style="margin-right:6px;">Doctor</label>
                <select name="doctor_id" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= (int) $d['doctor_id'] ?>" <?= $selectedDoctorId === (int) $d['doctor_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['full_name']) ?> — <?= htmlspecialchars($d['specialty']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-right:10px;">
                <label style="margin-right:6px;">Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
            </div>
        </form>

        <div class="row">
            <!-- Add slot -->
            <div class="col-md-4">
                <h4>Add Time Slot</h4>
                <form method="post" action="manage-schedules.php">
                    <input type="hidden" name="action" value="add_slot">
                    <input type="hidden" name="doctor_id" value="<?= $selectedDoctorId ?>">

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="slot_date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Start time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">Add Slot</button>
                </form>
            </div>

            <!-- Slot list for that doctor/date -->
            <div class="col-md-8">
                <h4>Slots on <?= htmlspecialchars($selectedDate) ?></h4>
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Start</th><th>End</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($slots as $s): ?>
                        <tr>
                            <td><?= substr($s['start_time'], 0, 5) ?></td>
                            <td><?= substr($s['end_time'], 0, 5) ?></td>
                            <td>
                                <?php
                                $labelClass = ['available' => 'label-success', 'booked' => 'label-warning', 'blocked' => 'label-default'][$s['status']] ?? 'label-default';
                                ?>
                                <span class="label <?= $labelClass ?>"><?= ucfirst($s['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'available'): ?>
                                    <form method="post" action="manage-schedules.php" style="margin:0;"
                                          onsubmit="return confirm('Remove this slot?');">
                                        <input type="hidden" name="action" value="delete_slot">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $s['schedule_id'] ?>">
                                        <input type="hidden" name="doctor_id" value="<?= $selectedDoctorId ?>">
                                        <input type="hidden" name="slot_date" value="<?= htmlspecialchars($selectedDate) ?>">
                                        <button type="submit" class="btn btn-xs btn-danger">Remove</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($slots)): ?>
                        <tr><td colspan="4" class="text-muted">No slots for this date yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/staff-footer.php'; ?>
