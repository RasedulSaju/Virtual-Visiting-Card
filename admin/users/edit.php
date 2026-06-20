<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$pdo    = getDB();
$userId = (int)($_GET['id'] ?? 0);

if (!$userId) {
    flash('error', 'Invalid user ID.');
    redirect('admin/users/');
}

$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    flash('error', 'User not found.');
    redirect('admin/users/');
}

// Superadmin accounts are completely invisible/inaccessible to regular admins —
// blocking direct URL access too, not just hiding from the list.
if ($user['role'] === 'superadmin' && !isSuperAdmin()) {
    flash('error', 'User not found.');
    redirect('admin/users/');
}

$validRoles = isSuperAdmin() ? ['user', 'admin', 'superadmin'] : ['user', 'admin'];
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username         = trim($_POST['username']          ?? '');
    $email            = trim($_POST['email']             ?? '');
    $role             = $_POST['role']                   ?? 'user';
    $accountStatus    = $_POST['account_status']          ?? 'active';
    $canEdit          = isset($_POST['can_edit_profile'])   ? 1 : 0;
    $showInDirectory  = isset($_POST['show_in_directory'])  ? 1 : 0;
    $bio              = trim($_POST['bio']               ?? '');
    $password         = $_POST['password']               ?? '';
    $password2        = $_POST['password2']              ?? '';
    $metaRobots       = $_POST['meta_robots']            ?? 'index,follow';

    if (!in_array($metaRobots, ['index,follow','noindex,follow','index,nofollow','noindex,nofollow'], true)) {
        $metaRobots = 'index,follow';
    }
    if (!in_array($accountStatus, ['active', 'resigned'], true)) {
        $accountStatus = 'active';
    }

    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username: 3–50 chars, letters/numbers/underscores.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    // Never let a non-superadmin grant/keep superadmin via tampered POST
    if (!in_array($role, $validRoles, true)) {
        $errors[] = 'Invalid role.';
    }

    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        }
    }

    if (empty($errors)) {
        $dup = $pdo->prepare(
            'SELECT SUM(LOWER(username)=LOWER(?) AND id != ?) AS u,
                    SUM(LOWER(email)=LOWER(?)    AND id != ?) AS e
             FROM users'
        );
        $dup->execute([$username, $userId, $email, $userId]);
        $d = $dup->fetch();
        if ((int)$d['u']) $errors[] = 'Username already taken.';
        if ((int)$d['e']) $errors[] = 'Email already registered.';
    }

    // Handle image upload
    $profileImage = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name']) && empty($errors)) {
        try {
            $newImg = uploadProfileImage($_FILES['profile_image'], $userId);
            deleteProfileImage($user['profile_image']);
            $profileImage = $newImg;
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    // Handle image removal
    if (isset($_POST['remove_image']) && $profileImage !== DEFAULT_AVATAR) {
        deleteProfileImage($profileImage);
        $profileImage = DEFAULT_AVATAR;
    }

    if (empty($errors)) {
        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare(
                    'UPDATE users SET username=?, email=?, password_hash=?, role=?, account_status=?,
                     can_edit_profile=?, show_in_directory=?, bio=?, profile_image=?, meta_robots=? WHERE id=?'
                )->execute([$username, $email, $hash, $role, $accountStatus,
                    $canEdit, $showInDirectory, $bio, $profileImage, $metaRobots, $userId]);
            } else {
                $pdo->prepare(
                    'UPDATE users SET username=?, email=?, role=?, account_status=?,
                     can_edit_profile=?, show_in_directory=?, bio=?, profile_image=?, meta_robots=? WHERE id=?'
                )->execute([$username, $email, $role, $accountStatus,
                    $canEdit, $showInDirectory, $bio, $profileImage, $metaRobots, $userId]);
            }

            // Update session if editing self
            if ((int)$_SESSION['user_id'] === $userId) {
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $role;
            }

            flash('success', 'User updated successfully.');
            redirect('admin/users/edit.php?id=' . $userId);
        } catch (PDOException $ex) {
            error_log('[AdminEditUser] ' . $ex->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }

    // Refresh for re-render
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
}

$pageTitle = 'Edit User: ' . $user['username'];
$activeNav = 'users';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>admin/users/" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div>
        <h2 class="h5 mb-0 fw-bold">Edit User</h2>
        <small class="text-muted">ID #<?= (int)$user['id'] ?> · <?= e($user['email']) ?></small>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0 ps-3">
    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">Account Details</div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?= e($user['username']) ?>" required>
                                <label class="form-label" for="username">Username *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?= e($user['email']) ?>" required>
                                <label class="form-label" for="email">Email *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="password" id="password" name="password" class="form-control"
                                       autocomplete="new-password" placeholder="Leave blank to keep current">
                                <label class="form-label" for="password">New Password</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="password" id="password2" name="password2" class="form-control"
                                       autocomplete="new-password">
                                <label class="form-label" for="password2">Confirm New Password</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">Profile &amp; Access</div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Role</label>
                            <select name="role" class="form-select"
                                    <?= (int)$user['id'] === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="user"  <?= $user['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <?php if (isSuperAdmin()): ?>
                                <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                <?php endif; ?>
                            </select>
                            <?php if ((int)$user['id'] === (int)$_SESSION['user_id']): ?>
                                <input type="hidden" name="role" value="<?= e($user['role']) ?>">
                                <div class="form-text">Cannot change your own role.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Account Status</label>
                            <select name="account_status" class="form-select">
                                <option value="active"   <?= $user['account_status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="resigned" <?= $user['account_status'] === 'resigned' ? 'selected' : '' ?>>Resigned</option>
                            </select>
                            <div class="form-text">Resigned shows a watermark on their public profile.</div>
                        </div>
                        <div class="col-md-4 d-flex flex-column justify-content-center gap-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="can_edit_profile" name="can_edit_profile"
                                       <?= $user['can_edit_profile'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="can_edit_profile">Allow profile editing</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="show_in_directory" name="show_in_directory"
                                       <?= $user['show_in_directory'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="show_in_directory">Show in Members directory</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-search me-1 text-secondary"></i>Search Engine Visibility
                            </label>
                            <select name="meta_robots" class="form-select">
                                <option value="index,follow"     <?= $user['meta_robots'] === 'index,follow'     ? 'selected' : '' ?>>Indexed (default)</option>
                                <option value="noindex,follow"   <?= $user['meta_robots'] === 'noindex,follow'   ? 'selected' : '' ?>>Noindex (links followed)</option>
                                <option value="index,nofollow"   <?= $user['meta_robots'] === 'index,nofollow'   ? 'selected' : '' ?>>Indexed (links not followed)</option>
                                <option value="noindex,nofollow" <?= $user['meta_robots'] === 'noindex,nofollow' ? 'selected' : '' ?>>Hidden (noindex, nofollow)</option>
                            </select>
                            <div class="form-text">Controls Google/Bing indexing. Separate from the Members directory toggle.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-outline">
                                <textarea id="bio" name="bio" class="form-control" rows="3"><?= e($user['bio'] ?? '') ?></textarea>
                                <label class="form-label" for="bio">Bio</label>
                            </div>
                        </div>
                        <!-- Profile image -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Profile Picture</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img src="<?= avatarUrl($user['profile_image']) ?>"
                                     id="editAvatarPreview" class="rounded-circle border"
                                     width="72" height="72" style="object-fit:cover;" alt="">
                                <div>
                                    <input type="file" class="form-control form-control-sm mb-1"
                                           name="profile_image" id="profile_image"
                                           accept="image/jpeg,image/png,image/gif">
                                    <div class="form-text">JPG, PNG, GIF · Max 2 MB</div>
                                    <?php if ($user['profile_image'] !== DEFAULT_AVATAR): ?>
                                    <div class="form-check mt-1">
                                        <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image">
                                        <label class="form-check-label small text-danger" for="remove_image">Remove current image</label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
                <a href="<?= BASE_URL . e($user['username']) ?>" target="_blank"
                   class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-1"></i> View Profile
                </a>
                <a href="<?= BASE_URL ?>admin/users/" class="btn btn-outline-secondary ms-auto">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('profile_image')?.addEventListener('change', function () {
    if (this.files[0]) {
        const r = new FileReader();
        r.onload = e => document.getElementById('editAvatarPreview').src = e.target.result;
        r.readAsDataURL(this.files[0]);
    }
});
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
