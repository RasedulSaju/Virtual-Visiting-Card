<?php
declare(strict_types=1);

// ============================================================
// app-defaults.php — VERSIONED APPLICATION CONSTANTS
//
// This file IS tracked by git. New features add their
// constants here, so a `git pull` keeps your install up to
// date automatically — you never need to edit this file.
//
// Every constant is wrapped in defined() checks so it's safe
// to define overrides in config.php if you ever need to.
// ============================================================

// ── App identity (fallbacks — actual values editable in
//    Admin → Settings → General, via siteName()/siteDescription()) ──
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Virtual Visiting Card');
}
if (!defined('APP_DESCRIPTION')) {
    define('APP_DESCRIPTION', 'Create and share your digital visiting card.');
}

// ── Debug mode (set to false in config.php for production) ───
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// ── Session ──────────────────────────────────────────────────
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 0); // 0 = until browser closes
}

// ── File Uploads ─────────────────────────────────────────────
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', rtrim(str_replace('\\', '/', __DIR__), '/') . '/uploads/profiles/');
}
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', BASE_URL . 'uploads/profiles/');
}
if (!defined('MAX_UPLOAD_SIZE')) {
    define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
}
if (!defined('ALLOWED_EXT')) {
    define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'gif']);
}
if (!defined('ALLOWED_MIME')) {
    define('ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/gif']);
}
if (!defined('DEFAULT_AVATAR')) {
    define('DEFAULT_AVATAR', 'default-avatar.svg');
}

// Auto-create uploads directory if missing
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
