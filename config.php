<?php
declare(strict_types=1);

// ============================================================
// config.php — YOUR LOCAL SETTINGS ONLY
//
// This file is NOT tracked by git (see .gitignore) so your
// database credentials are never committed or overwritten.
//
// All OTHER application constants (uploads, debug mode, app
// name, etc.) live in app-defaults.php, which IS tracked by
// git and updates automatically when you pull new versions —
// you never need to edit it.
// ============================================================

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'vvcarddb');       // ← your database name
define('DB_USER',    'root');         // ← your database user
define('DB_PASS',    '');             // ← your database password
define('DB_CHARSET', 'utf8mb4');

// ── Auto-detect BASE_URL ──────────────────────────────────────
// Works for root and subfolder installs — no manual config needed.
(function () {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appRoot  = rtrim(str_replace('\\', '/', __DIR__), '/');
    $subPath  = $docRoot !== '' ? str_replace($docRoot, '', $appRoot) : '';
    define('BASE_URL', $protocol . '://' . $host . $subPath . '/');
})();

// ── Everything else (versioned, always in sync) ───────────────
require_once __DIR__ . '/app-defaults.php';
