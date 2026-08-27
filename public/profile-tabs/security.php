<?php
// public/profile-tabs/security.php
// Required by profile.php; shares its prepared $formPrefix variable.
?>
<form method="post" action="<?= $formPrefix ?>profile.php" class="form-horizontal profile-form">
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
    <button type="submit" class="btn btn-primary profile-form-btn">Update password</button>
</form>
