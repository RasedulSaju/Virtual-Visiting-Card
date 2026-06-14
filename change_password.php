<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

$userStmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    flash('error', 'Session invalid.');
    redirect('login');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    if ($current === '') {
        $errors[] = 'Current password is required.';
    } elseif (!password_verify($current, $user['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }

    if (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    } elseif ($current === $new) {
        $errors[] = 'New password must be different from your current password.';
    }

    if (empty($errors)) {
        try {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                'UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?'
            )->execute([$hash, $userId]);

            flash('success', 'Password changed successfully.');
            redirect('change-password');
        } catch (PDOException $e) {
            error_log('[ChangePassword] ' . $e->getMessage());
            $errors[] = 'Server error. Please try again.';
        }
    }
}

$pageTitle = 'Change Password';
$metaRobots = 'noindex,nofollow';
require __DIR__ . '/templates/layout_header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="auth-card card border-0 shadow-lg mt-4">
            <div class="auth-card-header text-center p-4">
                <div class="auth-icon-wrap mb-3">
                    <i class="fas fa-lock fa-2x"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">Change Password</h1>
                <p class="text-muted small mb-0">Signed in as <strong><?= e($user['username']) ?></strong></p>
            </div>
            <div class="card-body p-4 pt-3">
                <?= renderFlash() ?>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" novalidate>
                    <?= csrfField() ?>
                    <div class="form-outline mb-4">
                        <input type="password" id="current_password" name="current_password"
                               class="form-control" autocomplete="current-password" required>
                        <label class="form-label" for="current_password">Current Password</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="password" id="new_password" name="new_password"
                               class="form-control" autocomplete="new-password"
                               minlength="8" required>
                        <label class="form-label" for="new_password">New Password</label>
                    </div>
                    <div id="passwordStrength" class="mb-2"></div>
                    <div class="form-outline mb-4">
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control" autocomplete="new-password"
                               minlength="8" required>
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-save me-1"></i> Update Password
                    </button>
                    <div class="text-center small">
                        <a href="<?= BASE_URL . e($user['username']) ?>" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Back to Profile
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/layout_footer.php'; ?>
