<?php
// public/admin/manage-schedules.php
//    用法： $embedded = true; require __DIR__ . '/admin/manage-schedules.php';

$embedded = $embedded ?? false;

// require_once __DIR__ . '/../../src/config/config.php';

$role = $_SESSION['role'] ?? '';

if (!$embedded) {
    require_once __DIR__ . '/../../src/config/config.php';
    // 独立访问时才做权限检查——嵌入模式下profile.php已经AuthMiddleware::requireLogin()过了
    AuthMiddleware::requireRole(['admin', 'assist']);
}

$db      = Database::getConnection();
$errors  = $errors ?? [];
$notices = $notices ?? [];

// 嵌入模式 + doctor角色 = 只能管自己的排班，不能选别的医生
$isDoctorEmbedded = $embedded && $role === 'doctor';

if ($isDoctorEmbedded) {
    $myDoctor =  $myDoctor ?? Doctor::findByUserId($db, $_SESSION['user_id']);
    $lockedDoctorId = $myDoctor['doctor_id'] ?? 0;
    $doctors = []; // 不需要下拉选择器
} else {
    $lockedDoctorId = null;
    $doctors = Doctor::all($db);
}

$selectedDoctorId = $isDoctorEmbedded
    ? $lockedDoctorId
    : (isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : ($doctors[0]['doctor_id'] ?? 0));

$selectedDate = $_GET['schedule_date'] ?? $_GET['date'] ?? date('Y-m-d');

// ---------- 新增slot ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_slot') {
    // doctor嵌入模式下，doctor_id永远用锁定的自己的id，不信任POST传来的值
    $doctorId  = $isDoctorEmbedded ? $lockedDoctorId : (int) $_POST['doctor_id'];
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
            // UNIQUE约束撞上了：同一医生同一天同一时间已经开过slot
            $errors[] = 'This exact time slot already exists on this date.';
        }
    }
    $selectedDoctorId = $doctorId;
    $selectedDate     = $date;
}

// ---------- 删除slot（只能删还没被约的） ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_slot') {
    $scheduleId = (int) $_POST['schedule_id'];
    $slot = Schedule::findById($db, $scheduleId);

    // doctor嵌入模式下多一层检查：这个slot必须是自己的，不能删别的医生的
    $ownsThisSlot = !$isDoctorEmbedded || ($slot && (int) $slot['doctor_id'] === $lockedDoctorId);

    if ($slot && $ownsThisSlot && Schedule::delete($db, $scheduleId)) {
        $notices[] = 'Time slot removed.';
    } else {
        $errors[] = 'Cannot remove this slot — it may already be booked, or does not belong to you.';
    }
    $selectedDoctorId = $isDoctorEmbedded ? $lockedDoctorId : (int) $_POST['doctor_id'];
    $selectedDate     = $_POST['slot_date'];
}

$slots = $selectedDoctorId ? Schedule::forDoctorOnDate($db, $selectedDoctorId, $selectedDate) : [];

$formAction = $embedded ? '../profile.php' : 'manage-schedules.php';

if (!$embedded) {
    $pageTitle = 'Manage Schedules';
    require_once __DIR__ . '/staff-header.php';
}
?>

<?php if (!$embedded): ?>
<section style="padding:60px 0; min-height:70vh;">
    <div class="container">
        <h2 style="margin-bottom:30px;">Manage Schedules</h2>
        <p class="text-muted">As <?= htmlspecialchars($role) ?>, you can view and manage every doctor's schedule.</p>
<?php endif; ?>

        <?php if (!$embedded): ?>
        <?php foreach ($notices as $n): ?><div class="alert alert-success"><?= htmlspecialchars($n) ?></div><?php endforeach; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        <?php endif; ?>

        <!-- Doctor + date filter：doctor嵌入模式不需要选医生，直接跳过这块 -->
        <?php if (!$isDoctorEmbedded): ?>
        <form method="get" action="<?= $formAction ?>" class="form-inline" style="margin-bottom:24px;">
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
        <?php else: ?>
        <form method="get" action="<?= $formAction ?>" class="form-inline" style="margin-bottom:20px;">
            <label style="margin-right:8px;">Date</label>
            <input type="date" name="schedule_date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
            <?php if ($embedded): ?><input type="hidden" name="tab" value="schedule"><?php endif; ?>
        </form>
        <?php endif; ?>

        <div class="row">
            <!-- Add slot -->
            <div class="col-md-4">
                <h4>Add Time Slot</h4>
                <form method="post" action="<?= $formAction ?>">
                    <input type="hidden" name="action" value="add_slot">
                    <?php if (!$isDoctorEmbedded): ?>
                        <input type="hidden" name="doctor_id" value="<?= $selectedDoctorId ?>">
                    <?php endif; ?>

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
                                    <form method="post" action="<?= $formAction ?>" style="margin:0;"
                                          onsubmit="return confirm('Remove this slot?');">
                                        <input type="hidden" name="action" value="delete_slot">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $s['schedule_id'] ?>">
                                        <?php if (!$isDoctorEmbedded): ?>
                                            <input type="hidden" name="doctor_id" value="<?= $selectedDoctorId ?>">
                                        <?php endif; ?>
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

<?php if (!$embedded): ?>
    </div>
</section>

<?php require_once __DIR__ . '/staff-footer.php'; ?>
<?php endif; ?>