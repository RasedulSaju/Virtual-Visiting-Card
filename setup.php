<?php
/**
 * setup.php — One-Time Admin Account Creator
 *
 * Run this script ONCE in your browser immediately after importing schema.sql.
 * DELETE this file from your server after successful setup.
 *
 * URL: http://yourdomain.com/setup.php
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$message = '';
$error   = '';
$done    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDB();
            // Check for existing admin or any user
            $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($count > 0) {
                $error = 'An admin account already exists. Delete this file immediately.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, email, password_hash, role, can_edit_profile, bio)
                     VALUES (?, ?, ?, 'admin', 1, 'Site administrator.')"
                );
                $stmt->execute([$username, $email, $hash]);
                $done    = true;
                $message = 'Admin account created successfully! Please delete this file now.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Setup — Create Admin Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .setup-card { width: 100%; max-width: 440px; }
        .setup-header { background: linear-gradient(135deg, #4f46e5, #7c3aed); border-radius: 12px 12px 0 0; padding: 32px; color: white; text-align: center; }
        .warning-banner { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px; font-size: 0.85rem; }

        /* Floating labels — CSS only, no JS required */
        .form-outline { position: relative; }
        .form-outline > .form-control {
            height: calc(3.5rem + 2px);
            padding: 1rem .75rem;
        }
        .form-outline > .form-control::placeholder { color: transparent; }
        .form-outline > .form-control:focus,
        .form-outline > .form-control:not(:placeholder-shown) {
            padding-top: 1.625rem;
            padding-bottom: .625rem;
        }
        .form-outline > .form-label {
            position: absolute; top: 0; left: 0; height: 100%;
            padding: 1rem .75rem; margin-bottom: 0;
            pointer-events: none; border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out, transform .1s ease-in-out;
            color: #6b7280;
        }
        .form-outline > .form-control:focus ~ .form-label,
        .form-outline > .form-control:not(:placeholder-shown) ~ .form-label {
            opacity: .85;
            transform: scale(.82) translateY(-.55rem) translateX(.15rem);
            color: #4f46e5;
            background: #fff;
            padding-left: .35rem; padding-right: .35rem;
        }
        .form-outline > .form-control:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 .15rem rgba(79,70,229,.15) !important;
        }
    </style>
</head>
<body>
<div class="setup-card">
    <div class="setup-header">
        <i class="fas fa-layer-group fa-2x mb-3"></i>
        <h4 class="mb-1 fw-bold"><?= htmlspecialchars(siteName()) ?></h4>
        <p class="mb-0 opacity-75">Initial Setup — Create Admin Account</p>
    </div>
    <div class="card shadow-sm border-0 rounded-0" style="border-radius: 0 0 12px 12px !important;">
        <div class="card-body p-4">
            <?php if ($done): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-2x d-block mb-2"></i>
                    <strong><?= htmlspecialchars($message) ?></strong>
                </div>
                <div class="warning-banner mb-3">
                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                    <strong>Security Warning:</strong> Delete <code>setup.php</code> from your server right now. Leaving it accessible is a critical security risk.
                </div>
                <div class="text-center">
                    <a href="<?= BASE_URL ?>login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-1"></i> Go to Login
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <div class="warning-banner mb-4">
                    <i class="fas fa-shield-alt text-warning me-1"></i>
                    <strong>One-time setup.</strong> This script creates the first admin account. Delete it after use.
                </div>
                <form method="POST">
                    <div class="form-outline mb-3">
                        <input type="text" id="username" name="username" class="form-control"
                               value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" required placeholder=" ">
                        <label class="form-label" for="username">Username</label>
                    </div>
                    <div class="form-outline mb-3">
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder=" ">
                        <label class="form-label" for="email">Email Address</label>
                    </div>
                    <div class="form-outline mb-3">
                        <input type="password" id="password" name="password" class="form-control"
                               minlength="8" required placeholder=" ">
                        <label class="form-label" for="password">Password (min 8 chars)</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="password" id="password2" name="password2" class="form-control"
                               minlength="8" required placeholder=" ">
                        <label class="form-label" for="password2">Confirm Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-user-shield me-1"></i> Create Admin Account
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
