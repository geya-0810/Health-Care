<?php
// public/profile-tabs/info.php
// Required by profile.php; shares its prepared $user / $formPrefix variables.
?>
<form method="post" action="<?= $formPrefix ?>profile.php" class="form-horizontal profile-form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update_profile">

    <div class="form-group">
        <label>Profile photo</label>
        <div style="display:flex;align-items:center;gap:16px;">
            <?php if (!empty($user['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Current avatar"
                     style="width:50px;height:50px;border-radius:50%;object-fit:cover;">
            <?php endif; ?>
            <input type="file" name="avatar" accept="image/png,image/jpeg,image/jpg,image/webp">
        </div>
        <p class="help-block">JPG, PNG or WEBP, max 5MB. Leave empty to keep your current photo.</p>
    </div>

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
    <button type="submit" class="btn btn-primary profile-form-btn">Save changes</button>
</form>
