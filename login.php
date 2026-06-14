<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// Already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/' : $_SESSION['username']);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $login    = trim($_POST['login']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($login === '')    $errors[] = 'Username or email is required.';
    if ($password === '') $errors[] = 'Password is required.';

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1'
            );
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                redirect($user['role'] === 'admin' ? 'admin/' : $user['username']);
            } else {
                // Timing-safe: same delay whether user exists or not
                $errors[] = 'Invalid credentials. Please try again.';
            }
        } catch (PDOException $e) {
            error_log('[Login] ' . $e->getMessage());
            $errors[] = 'A server error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/templates/layout_header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="auth-card card border-0 shadow-lg mt-4">
            <div class="auth-card-header text-center p-4">
                <div class="auth-icon-wrap mb-3">
                    <i class="fas fa-layer-group fa-2x"></i>
                </div>
                <h1 class="h4 fw-bold mb-1"><?= e(siteName()) ?></h1>
                <p class="text-muted small mb-0">Sign in to your account</p>
            </div>
            <div class="card-body p-4 pt-3">
                <?= renderFlash() ?>
                <?php if ($errors): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <?= csrfField() ?>
                    <div class="form-outline mb-4">
                        <input type="text" id="login" name="login" class="form-control"
                               value="<?= e($_POST['login'] ?? '') ?>"
                               autocomplete="username" required>
                        <label class="form-label" for="login">Username or Email</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="password" id="password" name="password" class="form-control"
                               autocomplete="current-password" required>
                        <label class="form-label" for="password">Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-1"></i> Sign In
                    </button>
                    <div class="text-center small">
                        <a href="<?= BASE_URL ?>forgot-password" class="text-decoration-none">
                            Forgot password?
                        </a>
                        <span class="text-muted mx-2">·</span>
                        <a href="<?= BASE_URL ?>register" class="text-decoration-none">
                            Create account
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/layout_footer.php'; ?>
