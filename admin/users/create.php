<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$validRoles = isSuperAdmin() ? ['user', 'admin', 'superadmin'] : ['user', 'admin'];

$errors = [];
$old    = [
    'username' => '', 'email' => '', 'role' => 'user',
    'can_edit_profile' => '1', 'show_in_directory' => '1',
    'account_status' => 'active', 'bio' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old['username']          = trim($_POST['username']         ?? '');
    $old['email']             = trim($_POST['email']            ?? '');
    $old['role']              = $_POST['role']                  ?? 'user';
    $old['can_edit_profile']  = isset($_POST['can_edit_profile']) ? '1' : '0';
    $old['show_in_directory'] = isset($_POST['show_in_directory']) ? '1' : '0';
    $old['account_status']    = $_POST['account_status']        ?? 'active';
    $old['bio']                = trim($_POST['bio']              ?? '');
    $password                  = $_POST['password']              ?? '';
    $password2                 = $_POST['password2']             ?? '';

    if ($old['username'] === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $old['username'])) {
        $errors[] = 'Username: 3–50 chars, letters/numbers/underscores.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (!in_array($old['role'], $validRoles, true)) {
        $errors[] = 'Invalid role.';
    }

    if (!in_array($old['account_status'], ['active', 'resigned'], true)) {
        $old['account_status'] = 'active';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $pdo = getDB();
            $dup = $pdo->prepare(
                'SELECT SUM(LOWER(username)=LOWER(?)) AS u, SUM(LOWER(email)=LOWER(?)) AS e FROM users'
            );
            $dup->execute([$old['username'], $old['email']]);
            $d = $dup->fetch();
            if ((int)$d['u']) $errors[] = 'Username already taken.';
            if ((int)$d['e']) $errors[] = 'Email already registered.';
        } catch (PDOException $ex) {
            error_log('[AdminCreateUser] ' . $ex->getMessage());
            $errors[] = 'Database error.';
        }
    }

    // Handle optional profile image
    $profileImage = DEFAULT_AVATAR;
    if (!empty($_FILES['profile_image']['name']) && empty($errors)) {
        try {
            $profileImage = uploadProfileImage($_FILES['profile_image'], 0); // 0 = temp, renamed below
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare(
                'INSERT INTO users
                    (username, email, password_hash, role, account_status,
                     can_edit_profile, show_in_directory, bio, profile_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $old['username'], $old['email'], $hash,
                $old['role'], $old['account_status'],
                (int)$old['can_edit_profile'], (int)$old['show_in_directory'],
                $old['bio'], $profileImage,
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Rename temp image to correct user_id
            if ($profileImage !== DEFAULT_AVATAR && str_starts_with($profileImage, 'user_0_')) {
                $newName = str_replace('user_0_', "user_{$newId}_", $profileImage);
                @rename(UPLOAD_DIR . $profileImage, UPLOAD_DIR . $newName);
                $pdo->prepare('UPDATE users SET profile_image = ? WHERE id = ?')
                    ->execute([$newName, $newId]);
            }

            flash('success', 'User "' . $old['username'] . '" created successfully.');
            redirect('admin/users/');
        } catch (PDOException $ex) {
            error_log('[AdminCreateUser] ' . $ex->getMessage());
            $errors[] = 'Failed to create user.';
        }
    }
}

$pageTitle = 'Create User';
$activeNav = 'users';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>admin/users/" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="h5 mb-0 fw-bold">Create New User</h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0 ps-3">
    <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
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
                                       value="<?= e($old['username']) ?>" required>
                                <label class="form-label" for="username">Username *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?= e($old['email']) ?>" required>
                                <label class="form-label" for="email">Email *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="password" id="password" name="password" class="form-control"
                                       minlength="8" required>
                                <label class="form-label" for="password">Password *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="password" id="password2" name="password2" class="form-control"
                                       minlength="8" required>
                                <label class="form-label" for="password2">Confirm Password *</label>
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
                            <select name="role" class="form-select">
                                <option value="user"  <?= $old['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $old['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <?php if (isSuperAdmin()): ?>
                                <option value="superadmin" <?= $old['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Account Status</label>
                            <select name="account_status" class="form-select">
                                <option value="active"   <?= $old['account_status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="resigned" <?= $old['account_status'] === 'resigned' ? 'selected' : '' ?>>Resigned</option>
                            </select>
                            <div class="form-text">Resigned shows a watermark on their public profile.</div>
                        </div>
                        <div class="col-md-4 d-flex flex-column justify-content-end gap-2 pb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="can_edit_profile" name="can_edit_profile"
                                       <?= $old['can_edit_profile'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="can_edit_profile">Allow profile editing</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="show_in_directory" name="show_in_directory"
                                       <?= $old['show_in_directory'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="show_in_directory">Show in Members directory</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-outline">
                                <textarea id="bio" name="bio" class="form-control" rows="3"><?= e($old['bio']) ?></textarea>
                                <label class="form-label" for="bio">Bio</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Profile Picture <small class="text-muted fw-normal">(optional, max 2 MB)</small></label>
                            <input type="file" class="form-control" name="profile_image"
                                   accept="image/jpeg,image/png,image/gif">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i> Create User
                </button>
                <a href="<?= BASE_URL ?>admin/users/" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
