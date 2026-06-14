<?php
declare(strict_types=1);

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'vvcard_db');       // ← your database name
define('DB_USER',    'root');         // ← your database user
define('DB_PASS',    '');             // ← your database password
define('DB_CHARSET', 'utf8mb4');

// ── Auto-detect BASE_URL ──────────────────────────────────────
// No manual configuration needed — works for root and subfolder installs.
(function () {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appRoot  = rtrim(str_replace('\\', '/', __DIR__), '/');
    $subPath  = $docRoot !== '' ? str_replace($docRoot, '', $appRoot) : '';
    define('BASE_URL', $protocol . '://' . $host . $subPath . '/');
})();

// ── App defaults (overridden by Admin → Settings → General) ──
define('APP_NAME',       'Virtual Visiting Card'); // fallback only
define('APP_DESCRIPTION','Create and share your digital visiting card.');

// ── File Uploads ─────────────────────────────────────────────
$_uploadBase = rtrim(str_replace('\\', '/', __DIR__), '/');
define('UPLOAD_DIR',     $_uploadBase . '/uploads/profiles/');
define('UPLOAD_URL',     BASE_URL . 'uploads/profiles/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
define('ALLOWED_EXT',    ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_MIME',   ['image/jpeg', 'image/png', 'image/gif']);
define('DEFAULT_AVATAR', 'default-avatar.svg');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ── Debug (set false on production) ──────────────────────────
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
