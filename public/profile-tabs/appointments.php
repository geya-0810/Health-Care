<?php
// public/profile-tabs/appointments.php
// Required by profile.php; shares its prepared $role / $pending / $upcoming / $past / $formPrefix variables.
// Do not open this file directly in a browser; it is not a standalone page and has no header/footer.

if ($role === 'doctor'): ?>
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
                        <form method="post" action="<?= $formPrefix ?>profile.php" style="display:inline-block;margin:0;">
                            <input type="hidden" name="action" value="confirm_appointment">
                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                            <button type="submit" class="btn btn-xs btn-success">Confirm</button>
                        </form>
                        <form method="post" action="<?= $formPrefix ?>profile.php" style="display:inline-block;margin:0;"
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
                        <form method="post" action="<?= $formPrefix ?>profile.php" style="margin:0;"
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
