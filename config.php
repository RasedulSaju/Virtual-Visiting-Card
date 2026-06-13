<?php
declare(strict_types=1);

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'vvcard_db');       // ← change to your DB name
define('DB_USER',    'root');         // ← change to your DB user
define('DB_PASS',    '');             // ← change to your DB password
define('DB_CHARSET', 'utf8mb4');

// ── Application ──────────────────────────────────────────────
define('APP_NAME',   'Virtual Visiting Card');
define('BASE_URL',   'http://localhost/vvcard/'); // ← must match your install path, trailing slash required

// ── Debug (set to false on production) ───────────────────────
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// ── File Uploads ─────────────────────────────────────────────
define('UPLOAD_DIR',         __DIR__ . '/uploads/profiles/');
define('UPLOAD_URL',         BASE_URL . 'uploads/profiles/');
define('MAX_UPLOAD_SIZE',    2 * 1024 * 1024); // 2 MB
define('ALLOWED_EXT',        ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_MIME',       ['image/jpeg', 'image/png', 'image/gif']);
define('DEFAULT_AVATAR',     'default-avatar.svg');

// ── Session ──────────────────────────────────────────────────
define('SESSION_LIFETIME',   0);      // 0 = until browser closes
