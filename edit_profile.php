<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// Load full user record
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    flash('error', 'Session invalid. Please log in again.');
    redirect('login');
}

// Gate: only admin or users with permission
if (!isAdmin() && !(bool)$user['can_edit_profile']) {
    $pageTitle = 'Profile Editing Disabled';
    $metaRobots = 'noindex,nofollow';
    require __DIR__ . '/templates/layout_header.php';
    ?>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm text-center p-5 mt-4">
                <i class="fas fa-ban fa-3x text-danger mb-3"></i>
                <h4 class="fw-bold">Editing Disabled</h4>
                <p class="text-muted">An administrator has disabled profile editing for your account.</p>
                <a href="<?= BASE_URL . e($user['username']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-user me-1"></i> View Profile
                </a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/templates/layout_footer.php';
    exit;
}

// Load custom fields with current user values
$fieldStmt = $pdo->prepare(
    'SELECT pf.id, pf.field_name, pf.field_label, pf.field_type, pf.field_icon, pf.is_public,
            COALESCE(ufv.field_value, \'\') AS field_value
     FROM   profile_fields pf
     LEFT JOIN user_field_values ufv ON pf.id = ufv.field_id AND ufv.user_id = ?
     WHERE  pf.is_active = 1
     ORDER  BY pf.sort_order ASC'
);
$fieldStmt->execute([$userId]);
$profileFields = $fieldStmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $bio = trim($_POST['bio'] ?? '');

    // Handle profile image upload
    $newImage = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        try {
            $newImage = uploadProfileImage($_FILES['profile_image'], $userId);
            // Delete old image
            if ($user['profile_image'] !== DEFAULT_AVATAR) {
                deleteProfileImage($user['profile_image']);
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            // Update bio and image
            $pdo->prepare('UPDATE users SET bio = ?, profile_image = ? WHERE id = ?')
                ->execute([$bio, $newImage, $userId]);

            // Upsert custom field values
            $upsert = $pdo->prepare(
                'INSERT INTO user_field_values (user_id, field_id, field_value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)'
            );
            foreach ($profileFields as $field) {
                $val = trim($_POST['field_' . $field['id']] ?? '');
                $upsert->execute([$userId, $field['id'], $val]);
            }

            flash('success', 'Profile updated successfully.');
            redirect('edit-profile');
        } catch (PDOException $e) {
            error_log('[EditProfile] ' . $e->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }

    // Refresh user record after update
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    // Refresh field values
    $fieldStmt->execute([$userId]);
    $profileFields = $fieldStmt->fetchAll();
}

$pageTitle = 'Edit Profile';
$metaRobots = 'noindex,nofollow';
require __DIR__ . '/templates/layout_header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex align-items-center mb-4">
            <img src="<?= avatarUrl($user['profile_image']) ?>"
                 class="rounded-circle border me-3" width="56" height="56"
                 style="object-fit:cover;" alt="Avatar">
            <div>
                <h2 class="mb-0 fw-bold"><?= e($user['username']) ?></h2>
                <a href="<?= BASE_URL . e($user['username']) ?>" class="small text-muted text-decoration-none">
                    <i class="fas fa-external-link-alt me-1"></i>View public profile
                </a>
            </div>
        </div>

        <?= renderFlash() ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>

            <!-- Profile Picture -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-camera me-2 text-primary"></i>Profile Picture
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-4 flex-wrap">
                        <img src="<?= avatarUrl($user['profile_image']) ?>"
                             id="avatarPreview"
                             class="rounded-circle border" width="100" height="100"
                             style="object-fit:cover;" alt="Current avatar">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control mb-1" id="profile_image"
                                   name="profile_image" accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">JPG, PNG or GIF · Max 2 MB</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bio -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-align-left me-2 text-primary"></i>Bio
                </div>
                <div class="card-body p-4">
                    <div class="form-outline">
                        <textarea id="bio" name="bio" class="form-control" rows="4"><?= e($user['bio'] ?? '') ?></textarea>
                        <label class="form-label" for="bio">Tell the world about yourself</label>
                    </div>
                </div>
            </div>

            <!-- Custom Profile Fields -->
            <?php if ($profileFields): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-list-alt me-2 text-primary"></i>Profile Details
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php foreach ($profileFields as $field):
                            $isPrivateField = (int)($field['is_public'] ?? 1) === 0;
                        ?>
                        <div class="col-md-6">
                            <?php if ($field['field_type'] === 'textarea'): ?>
                                <div class="form-outline">
                                    <textarea id="field_<?= (int)$field['id'] ?>"
                                              name="field_<?= (int)$field['id'] ?>"
                                              class="form-control" rows="3"
                                              placeholder=" "><?= e($field['field_value']) ?></textarea>
                                    <label class="form-label" for="field_<?= (int)$field['id'] ?>">
                                        <i class="<?= e($field['field_icon']) ?> me-1"></i><?= e($field['field_label']) ?>
                                        <?php if ($isPrivateField): ?>
                                            <i class="fas fa-lock ms-1 text-warning" style="font-size:.7rem;" title="Private — not shown publicly"></i>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php elseif ($field['field_type'] === 'date'): ?>
                                <div class="form-outline">
                                    <input type="date"
                                           id="field_<?= (int)$field['id'] ?>"
                                           name="field_<?= (int)$field['id'] ?>"
                                           class="form-control"
                                           value="<?= e($field['field_value']) ?>"
                                           placeholder=" ">
                                    <label class="form-label" for="field_<?= (int)$field['id'] ?>">
                                        <i class="<?= e($field['field_icon']) ?> me-1"></i><?= e($field['field_label']) ?>
                                        <?php if ($isPrivateField): ?>
                                            <i class="fas fa-lock ms-1 text-warning" style="font-size:.7rem;" title="Private — not shown publicly"></i>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="form-outline">
                                    <input type="<?= $field['field_type'] === 'url' ? 'url' : 'text' ?>"
                                           id="field_<?= (int)$field['id'] ?>"
                                           name="field_<?= (int)$field['id'] ?>"
                                           class="form-control"
                                           value="<?= e($field['field_value']) ?>"
                                           placeholder="<?= $field['field_type'] === 'url' ? 'https://' : ' ' ?>">
                                    <label class="form-label" for="field_<?= (int)$field['id'] ?>">
                                        <i class="<?= e($field['field_icon']) ?> me-1"></i><?= e($field['field_label']) ?>
                                        <?php if ($isPrivateField): ?>
                                            <i class="fas fa-lock ms-1 text-warning" style="font-size:.7rem;" title="Private — not shown publicly"></i>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endif; ?>
                            <?php if ($isPrivateField): ?>
                                <div class="form-text">Private — only you and admins can see this.</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
                <a href="<?= BASE_URL . e($user['username']) ?>" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('profile_image').addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
        reader.readAsDataURL(file);
    }
});
</script>
<?php require __DIR__ . '/templates/layout_footer.php'; ?>
