<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mailer.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/' : $_SESSION['username']);
}

$errors      = [];
$resetLink   = null;
$submitted   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $upd = $pdo->prepare(
                    'UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?'
                );
                $upd->execute([$token, $expires, $user['id']]);

                $resetLink = BASE_URL . 'reset-password?token=' . $token;

                // Attempt real email via SMTP
                try {
                    $mailer  = new Mailer();
                    $html    = Mailer::buildHtml(
                        'Reset Your Password',
                        '<p>We received a request to reset the password for your account.</p>
                         <p>This link expires in <strong>1 hour</strong>.</p>',
                        $resetLink,
                        'Reset My Password'
                    );
                    $sent = $mailer->send($email, $email, 'Reset your ' . APP_NAME . ' password', $html);
                    if ($sent) $resetLink = null; // hide link — real email was sent
                } catch (Exception $ex) {
                    error_log('[ForgotPassword/Mailer] ' . $ex->getMessage());
                    // SMTP failed — fall through to dev-mode link display
                }
            }

            // Always show the same "submitted" state to prevent email enumeration
            $submitted = true;
        } catch (PDOException $e) {
            error_log('[ForgotPassword] ' . $e->getMessage());
            $errors[] = 'A server error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/templates/layout_header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="auth-card card border-0 shadow-lg mt-4">
            <div class="auth-card-header text-center p-4">
                <div class="auth-icon-wrap mb-3">
                    <i class="fas fa-key fa-2x"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">Forgot Password</h1>
                <p class="text-muted small mb-0">We'll generate a reset link for you</p>
            </div>
            <div class="card-body p-4 pt-3">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <div><i class="fas fa-exclamation-circle me-1"></i><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($submitted): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-1"></i>
                        <?php if ($resetLink === null): ?>
                            A password reset link has been sent to your email address.
                        <?php else: ?>
                            If that email exists in our system, a reset link has been generated.
                        <?php endif; ?>
                    </div>

                    <?php if ($resetLink): ?>
                        <!-- ── DEVELOPMENT ONLY: remove SMTP simulation in production ── -->
                        <div class="alert alert-warning">
                            <p class="fw-bold mb-1">
                                <i class="fas fa-flask me-1"></i> Dev Mode — Simulated Email
                            </p>
                            <p class="small mb-2">In production this would be sent via SMTP. Your reset link:</p>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm font-monospace"
                                       id="resetLinkInput"
                                       value="<?= e($resetLink) ?>" readonly>
                                <button class="btn btn-outline-secondary btn-sm"
                                        onclick="copyResetLink()" type="button">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <p class="text-muted small mt-1 mb-0">Expires in 1 hour.</p>
                        </div>
                        <script>
                            function copyResetLink() {
                                document.getElementById('resetLinkInput').select();
                                document.execCommand('copy');
                            }
                        </script>
                    <?php endif; ?>

                    <div class="text-center">
                        <a href="<?= BASE_URL ?>login" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Login
                        </a>
                    </div>

                <?php else: ?>
                    <form method="POST" novalidate>
                        <?= csrfField() ?>
                        <div class="form-outline mb-4">
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?= e($_POST['email'] ?? '') ?>"
                                   autocomplete="email" required>
                            <label class="form-label" for="email">Email Address</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-paper-plane me-1"></i> Generate Reset Link
                        </button>
                        <div class="text-center small">
                            <a href="<?= BASE_URL ?>login" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i> Back to Login
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/layout_footer.php'; ?>
