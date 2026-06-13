<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/' : $_SESSION['username']);
}

// ── Invitation token handling ─────────────────────────────────
$inviteToken   = trim($_GET['invite'] ?? '');
$inviteEmail   = '';
$inviteValid   = false;
$inviteId      = null;

if ($inviteToken !== '') {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'SELECT id, email FROM invitations
             WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$inviteToken]);
        $inv = $stmt->fetch();
        if ($inv) {
            $inviteValid = true;
            $inviteEmail = $inv['email'];
            $inviteId    = (int)$inv['id'];
        }
    } catch (PDOException $e) {
        error_log('[Register/Invite] ' . $e->getMessage());
    }
}

// ── Registration gate ─────────────────────────────────────────
$registrationOpen = getSetting('registration_open', '1') === '1';

if (!$registrationOpen && !$inviteValid) {
    $pageTitle = 'Registration Closed';
    require __DIR__ . '/templates/layout_header.php';
    ?>
    <div class="row justify-content-center">
        <div class="col-sm-10 col-md-6 col-lg-4">
            <div class="auth-card card border-0 shadow-lg mt-4 text-center">
                <div class="card-body p-5">
                    <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                    <h3 class="fw-bold">Registration Closed</h3>
                    <p class="text-muted">Public registration is currently disabled.<br>Contact an administrator for access.</p>
                    <a href="<?= BASE_URL ?>login" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/templates/layout_footer.php';
    exit;
}

$errors = [];
$old    = ['username' => '', 'email' => $inviteEmail];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old['username']   = trim($_POST['username'] ?? '');
    $old['email']      = trim($_POST['email']    ?? '');
    $password          = $_POST['password']      ?? '';
    $password2         = $_POST['password2']     ?? '';
    $postInviteToken   = trim($_POST['invite_token'] ?? '');

    // Re-validate invite on POST
    $postInviteValid = false;
    $postInviteId    = null;
    if ($postInviteToken !== '') {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                'SELECT id FROM invitations
                 WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1'
            );
            $stmt->execute([$postInviteToken]);
            $inv = $stmt->fetch();
            if ($inv) {
                $postInviteValid = true;
                $postInviteId    = (int)$inv['id'];
            }
        } catch (PDOException $e) {
            error_log('[Register/POST Invite] ' . $e->getMessage());
        }
    }

    // Gate check on POST
    $registrationOpenPost = getSetting('registration_open', '1') === '1';
    if (!$registrationOpenPost && !$postInviteValid) {
        $errors[] = 'Registration is currently closed.';
    }

    // Field validation
    if ($old['username'] === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $old['username'])) {
        $errors[] = 'Username: 3–50 chars, letters, numbers, underscores only.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
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
                'SELECT
                    SUM(LOWER(username) = LOWER(?)) AS uname_taken,
                    SUM(LOWER(email)    = LOWER(?)) AS email_taken
                 FROM users'
            );
            $dup->execute([$old['username'], $old['email']]);
            $row = $dup->fetch();
            if ((int)$row['uname_taken'] > 0) $errors[] = 'Username already taken.';
            if ((int)$row['email_taken']  > 0) $errors[] = 'Email already registered.';
        } catch (PDOException $e) {
            error_log('[Register] ' . $e->getMessage());
            $errors[] = 'Server error. Please try again.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role, can_edit_profile)
                 VALUES (?, ?, ?, \'user\', 1)'
            );
            $stmt->execute([$old['username'], $old['email'], $hash]);

            // Mark invite as used
            if ($postInviteId !== null) {
                $pdo->prepare('UPDATE invitations SET used = 1 WHERE id = ?')
                    ->execute([$postInviteId]);
            }

            flash('success', 'Account created! You can now sign in.');
            redirect('login');
        } catch (PDOException $e) {
            error_log('[Register] ' . $e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Create Account';
require __DIR__ . '/templates/layout_header.php';
?>
<div class="row justify-content-center">
    <div class="col-sm-10 col-md-7 col-lg-5">
        <div class="auth-card card border-0 shadow-lg mt-4">
            <div class="auth-card-header text-center p-4">
                <div class="auth-icon-wrap mb-3">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">Create Account</h1>
                <p class="text-muted small mb-0">
                    <?= $inviteValid ? '<i class="fas fa-envelope-open-text me-1 text-success"></i>You were invited to join ' . e(APP_NAME) : 'Join ' . e(APP_NAME) ?>
                </p>
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
                    <input type="hidden" name="invite_token" value="<?= e($inviteToken ?: ($_POST['invite_token'] ?? '')) ?>">
                    <div class="form-outline mb-4">
                        <input type="text" id="username" name="username" class="form-control"
                               value="<?= e($old['username']) ?>" autocomplete="username" required>
                        <label class="form-label" for="username">Username</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($old['email']) ?>"
                               autocomplete="email" required
                               <?= $inviteValid ? 'readonly' : '' ?>>
                        <label class="form-label" for="email">Email Address</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="password" id="password" name="password" class="form-control"
                               autocomplete="new-password" minlength="8" required>
                        <label class="form-label" for="password">Password (min 8 chars)</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="password" id="password2" name="password2" class="form-control"
                               autocomplete="new-password" minlength="8" required>
                        <label class="form-label" for="password2">Confirm Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-user-plus me-1"></i> Create Account
                    </button>
                    <div class="text-center small">
                        Already have an account?
                        <a href="<?= BASE_URL ?>login" class="text-decoration-none">Sign in</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/layout_footer.php'; ?>
