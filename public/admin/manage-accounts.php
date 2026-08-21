<?php
// public/admin/manage-accounts.php
require_once __DIR__ . '/../../src/config/config.php';

AuthMiddleware::requireRole(['admin', 'assist']);

$db      = Database::getConnection();
$role    = $_SESSION['role'];
$isAdmin = $role === 'admin';

$errors  = [];
$notices = [];

if (isset($_GET['created'])) {
    $notices[] = 'Account created and credentials emailed.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_staff' && $isAdmin) {
    $userId   = (int) $_POST['user_id'];
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid name and email.';
    } else {
        try {
            User::adminUpdate($db, $userId, [
                'full_name' => $fullName, 'email' => $email, 'phone' => $phone, 'is_active' => $isActive,
            ]);
            $notices[] = 'Staff account updated.';
        } catch (Throwable $e) {
            error_log('Update staff failed: ' . $e->getMessage());
            $errors[] = 'Could not update — email might already be in use.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_doctor' && $isAdmin) {
    $doctorId  = (int) $_POST['doctor_id'];
    $userId    = (int) $_POST['user_id'];
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $fee       = (float) ($_POST['consultation_fee'] ?? 0);
    $bio       = trim($_POST['bio'] ?? '');
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    if ($fullName === '' || $specialty === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid name, specialty and email.';
    } else {
        $db->beginTransaction();
        try {
            Doctor::update($db, $doctorId, [
                'specialty' => $specialty, 'bio' => $bio, 'consultation_fee' => $fee, 'is_active' => $isActive,
            ]);
            if ($userId) {
                User::adminUpdate($db, $userId, [
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'is_active' => $isActive,
                ]);
            }
            $db->commit();
            $notices[] = 'Doctor account updated.';
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('Update doctor failed: ' . $e->getMessage());
            $errors[] = 'Could not update — email might already be in use.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_patient') {
    $userId   = (int) $_POST['user_id'];
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid name and email.';
    } else {
        try {
            User::adminUpdate($db, $userId, [
                'full_name' => $fullName, 'email' => $email, 'phone' => $phone, 'is_active' => $isActive,
            ]);
            $notices[] = 'Patient account updated.';
        } catch (Throwable $e) {
            error_log('Update patient failed: ' . $e->getMessage());
            $errors[] = 'Could not update — email might already be in use.';
        }
    }
}

// ================= Search =================
$staffQ   = trim($_GET['staff_q'] ?? '');
$doctorQ  = trim($_GET['doctor_q'] ?? '');
$patientQ = trim($_GET['patient_q'] ?? '');

$staff = $doctors = $patients = [];

if ($isAdmin) {
    $stmt = $db->prepare(
        "SELECT * FROM users WHERE role IN ('admin','assist') AND (full_name LIKE :q OR email LIKE :q)
         ORDER BY role, full_name"
    );
    $stmt->execute(['q' => "%$staffQ%"]);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare(
        "SELECT d.*, u.full_name, u.email, u.phone, u.is_active AS account_active FROM doctors d
         LEFT JOIN users u ON u.user_id = d.user_id
         WHERE u.full_name LIKE :q OR d.specialty LIKE :q OR u.email LIKE :q
         ORDER BY u.full_name"
    );
    $stmt->execute(['q' => "%$doctorQ%"]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = $db->prepare(
    "SELECT * FROM users WHERE role = 'patient' AND (full_name LIKE :q OR email LIKE :q)
     ORDER BY created_at DESC LIMIT 50"
);
$stmt->execute(['q' => "%$patientQ%"]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================= Determine which row the edit panel should display =================
$editType = $_GET['edit'] ?? '';
$editId   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$editingStaff = ($editType === 'staff' && $isAdmin) ? User::findById($db, $editId) : null;
$editingDoctor = null;
if ($editType === 'doctor' && $isAdmin) {
    $stmt = $db->prepare(
        'SELECT d.*, u.full_name, u.email, u.phone
         FROM doctors d LEFT JOIN users u ON u.user_id = d.user_id
         WHERE d.doctor_id = :id'
    );
    $stmt->execute(['id' => $editId]);
    $editingDoctor = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$editingPatient = ($editType === 'patient') ? User::findById($db, $editId) : null;

$pageTitle = 'Manage Accounts';
require_once __DIR__ . '/staff-header.php';
?>

<section style="padding:60px 0; min-height:70vh;">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
            <h2 style="margin:0;">Manage Accounts</h2>
            <a href="register-account.php" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">
                + Add Account
            </a>
        </div>

        <?php foreach ($notices as $n): ?><div class="alert alert-success"><?= htmlspecialchars($n) ?></div><?php endforeach; ?>
        <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>

        <?php if ($isAdmin): ?>
        <!-- ===================== STAFF TABLE (admin + assist) ===================== -->
        <h4>Staff (Admin &amp; Assistant)</h4>

        <?php if ($editingStaff): ?>
            <form method="post" action="manage-accounts.php" class="well" style="max-width:600px;">
                <input type="hidden" name="action" value="update_staff">
                <input type="hidden" name="user_id" value="<?= (int) $editingStaff['user_id'] ?>">
                <div class="row">
                    <div class="col-sm-6 form-group"><label>Full name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($editingStaff['full_name']) ?>" required></div>
                    <div class="col-sm-6 form-group"><label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editingStaff['email']) ?>" required></div>
                    <div class="col-sm-6 form-group"><label>Phone</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($editingStaff['phone'] ?? '') ?>"></div>
                    <div class="col-sm-6 form-group"><label>Role</label>
                        <input type="text" class="form-control" value="<?= ucfirst($editingStaff['role']) ?>" disabled>
                    </div>
                    <div class="col-sm-12 form-group">
                        <label><input type="checkbox" name="is_active" <?= $editingStaff['is_active'] ? 'checked' : '' ?>> Active</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">Save</button>
                <a href="manage-accounts.php" class="btn btn-default">Cancel</a>
            </form>
        <?php endif; ?>

        <form method="get" action="manage-accounts.php" class="form-inline" style="margin-bottom:12px;">
            <input type="text" name="staff_q" class="form-control" placeholder="Search staff by name/email" value="<?= htmlspecialchars($staffQ) ?>">
            <button type="submit" class="btn btn-default">Search</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($staff as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                    <td><span class="label label-info"><?= ucfirst($s['role']) ?></span></td>
                    <td><?= $s['is_active'] ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>' ?></td>
                    <td><a href="manage-accounts.php?edit=staff&id=<?= (int) $s['user_id'] ?>#top" class="btn btn-xs btn-default">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($staff)): ?><tr><td colspan="6" class="text-muted">No staff found.</td></tr><?php endif; ?>
            </tbody>
        </table>

        <!-- ===================== DOCTORS TABLE ===================== -->
        <h4 style="margin-top:40px;">Doctors</h4>

        <?php if ($editingDoctor): ?>
            <form method="post" action="manage-accounts.php" class="well" style="max-width:600px;">
                <input type="hidden" name="action" value="update_doctor">
                <input type="hidden" name="doctor_id" value="<?= (int) $editingDoctor['doctor_id'] ?>">
                <input type="hidden" name="user_id" value="<?= (int) $editingDoctor['user_id'] ?>">
                <div class="row">
                    <div class="col-sm-6 form-group"><label>Full name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($editingDoctor['full_name']) ?>" required></div>
                    <div class="col-sm-6 form-group"><label>Specialty</label>
                        <input type="text" name="specialty" class="form-control" value="<?= htmlspecialchars($editingDoctor['specialty']) ?>" required></div>
                    <div class="col-sm-6 form-group"><label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editingDoctor['email']) ?>" required></div>
                    <div class="col-sm-6 form-group"><label>Phone</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($editingDoctor['phone'] ?? '') ?>"></div>
                    <div class="col-sm-6 form-group"><label>Consultation fee (RM)</label>
                        <input type="number" step="0.01" name="consultation_fee" class="form-control" value="<?= htmlspecialchars($editingDoctor['consultation_fee']) ?>"></div>
                    <div class="col-sm-6 form-group" style="padding-top:24px;">
                        <label><input type="checkbox" name="is_active" <?= $editingDoctor['is_active'] ? 'checked' : '' ?>> Active</label>
                    </div>
                    <div class="col-sm-12 form-group"><label>Bio</label>
                        <textarea name="bio" class="form-control" rows="2"><?= htmlspecialchars($editingDoctor['bio'] ?? '') ?></textarea></div>
                </div>
                <button type="submit" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">Save</button>
                <a href="manage-accounts.php" class="btn btn-default">Cancel</a>
            </form>
        <?php endif; ?>

        <form method="get" action="manage-accounts.php" class="form-inline" style="margin-bottom:12px;">
            <input type="text" name="doctor_q" class="form-control" placeholder="Search doctors by name/specialty" value="<?= htmlspecialchars($doctorQ) ?>">
            <button type="submit" class="btn btn-default">Search</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Specialty</th><th>Email</th><th>Fee</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($doctors as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['full_name']) ?></td>
                    <td><?= htmlspecialchars($d['specialty']) ?></td>
                    <td><?= htmlspecialchars($d['email']) ?></td>
                    <td>RM <?= number_format((float) $d['consultation_fee'], 2) ?></td>
                    <td><?= $d['is_active'] ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>' ?></td>
                    <td><a href="manage-accounts.php?edit=doctor&id=<?= (int) $d['doctor_id'] ?>#top" class="btn btn-xs btn-default">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($doctors)): ?><tr><td colspan="6" class="text-muted">No doctors found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <?php endif; // isAdmin ?>

        <!-- ===================== PATIENTS TABLE (admin + assist) ===================== -->
        <h4 style="margin-top:40px;">Patients</h4>

        <?php if ($editingPatient): ?>
            <form method="post" action="manage-accounts.php" class="well" style="max-width:600px;">
                <input type="hidden" name="action" value="update_patient">
                <input type="hidden" name="user_id" value="<?= (int) $editingPatient['user_id'] ?>">
                <div class="row">
                    <div class="col-sm-6 form-group"><label>Full name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($editingPatient['full_name']) ?>" required></div>
                    <div class="col-sm-6 form-group"><label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editingPatient['email']) ?>" required></div>
                    <div class="col-sm-6 form-group"><label>Phone</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($editingPatient['phone'] ?? '') ?>"></div>
                    <div class="col-sm-6 form-group" style="padding-top:24px;">
                        <label><input type="checkbox" name="is_active" <?= $editingPatient['is_active'] ? 'checked' : '' ?>> Active</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="background:#8BC63F;border-color:#8BC63F;">Save</button>
                <a href="manage-accounts.php" class="btn btn-default">Cancel</a>
            </form>
        <?php endif; ?>

        <form method="get" action="manage-accounts.php" class="form-inline" style="margin-bottom:12px;">
            <input type="text" name="patient_q" class="form-control" placeholder="Search patients by name/email" value="<?= htmlspecialchars($patientQ) ?>">
            <button type="submit" class="btn btn-default">Search</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars($p['phone'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['created_at']) ?></td>
                    <td><?= $p['is_active'] ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>' ?></td>
                    <td><a href="manage-accounts.php?edit=patient&id=<?= (int) $p['user_id'] ?>#top" class="btn btn-xs btn-default">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($patients)): ?><tr><td colspan="6" class="text-muted">No patients found.</td></tr><?php endif; ?>
            </tbody>
        </table>

    </div>
</section>

<?php require_once __DIR__ . '/staff-footer.php'; ?>
