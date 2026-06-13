<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/' : $_SESSION['username']);
}

$token  = trim($_GET['token'] ?? '');
$errors = [];
$done   = false;

// Validate token exists and is not expired
$tokenUser = null;
if ($token !== '') {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'SELECT id, username FROM users
             WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        $tokenUser = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('[ResetPassword] ' . $e->getMessage());
    }
}

if ($token === '' || $tokenUser === null) {
    $pageTitle = 'Invalid Reset Link';
    require __DIR__ . '/templates/layout_header.php';
    ?>
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-6 col-lg-4">
            <div class="auth-card card border-0 shadow-lg mt-4 text-center">
                <div class="card-body p-5">
                    <i class="fas fa-unlink fa-3x text-danger mb-3"></i>
                    <h3 class="fw-bold">Link Expired or Invalid</h3>
                    <p class="text-muted">This password reset link is invalid or has expired.<br>Reset links are valid for 1 hour.</p>
                    <a href="<?= BASE_URL ?>forgot-password" class="btn btn-primary btn-sm">
                        <i class="fas fa-redo me-1"></i> Request New Link
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/templates/layout_footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                'UPDATE users
                 SET password_hash = ?, reset_token = NULL, reset_expires = NULL
                 WHERE id = ?'
            )->execute([$hash, $tokenUser['id']]);

            $done = true;
        } catch (PDOException $e) {
            error_log('[ResetPassword] ' . $e->getMessage());
            $errors[] = 'Server error. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/templates/layout_header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="auth-card card border-0 shadow-lg mt-4">
            <div class="auth-card-header text-center p-4">
                <div class="auth-icon-wrap mb-3">
                    <i class="fas fa-lock-open fa-2x"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">Set New Password</h1>
                <p class="text-muted small mb-0">
                    For <strong><?= e($tokenUser['username']) ?></strong>
                </p>
            </div>
            <div class="card-body p-4 pt-3">
                <?php if ($done): ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x d-block mb-2"></i>
                        <strong>Password updated successfully!</strong>
                    </div>
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>login" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-1"></i> Sign In
                        </a>
                    </div>
                <?php else: ?>
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
                            <input type="password" id="password" name="password" class="form-control"
                                   autocomplete="new-password" minlength="8" required>
                            <label class="form-label" for="password">New Password</label>
                        </div>
                        <div class="form-outline mb-4">
                            <input type="password" id="password2" name="password2" class="form-control"
                                   autocomplete="new-password" minlength="8" required>
                            <label class="form-label" for="password2">Confirm New Password</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Update Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/layout_footer.php'; ?>
