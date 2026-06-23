<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ── Session bootstrap (called once on every request) ─────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── XSS ──────────────────────────────────────────────────────
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ── Flash messages ───────────────────────────────────────────
function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function renderFlash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f     = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $map   = ['success' => 'success', 'error' => 'danger',
               'warning' => 'warning', 'info'  => 'info'];
    $color = $map[$f['type']] ?? 'info';
    $icon  = ['success' => 'check-circle', 'danger'  => 'exclamation-circle',
               'warning' => 'exclamation-triangle', 'info' => 'info-circle'][$color] ?? 'info-circle';
    return '<div class="alert alert-' . $color . ' alert-dismissible fade show d-flex align-items-center" role="alert">'
        . '<i class="fas fa-' . $icon . ' me-2"></i>'
        . e($f['msg'])
        . '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>'
        . '</div>';
}

// ── Redirect ─────────────────────────────────────────────────
function redirect(string $path): never
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

// ── Auth helpers ─────────────────────────────────────────────
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['role'])
        && in_array($_SESSION['role'], ['admin', 'superadmin'], true);
}

function isSuperAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Please log in to continue.');
        redirect('login');
    }
}

function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        flash('error', 'Access denied.');
        redirect('login');
    }
}

function requireSuperAdmin(): void
{
    if (!isLoggedIn() || !isSuperAdmin()) {
        flash('error', 'Superadmin access required.');
        redirect(isAdmin() ? 'admin/' : 'login');
    }
}

// ── CSRF ─────────────────────────────────────────────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(403);
        exit('CSRF token mismatch. Go back and try again.');
    }
    unset($_SESSION['csrf_token']);
}

// ── Slug generator ───────────────────────────────────────────
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ── Text helper ──────────────────────────────────────────────
function truncate(string $str, int $length = 60): string
{
    return mb_strlen($str) > $length
        ? mb_substr($str, 0, $length) . '…'
        : $str;
}

// ── Avatar URL ───────────────────────────────────────────────
function avatarUrl(string $filename): string
{
    if ($filename === '' || $filename === DEFAULT_AVATAR
        || !file_exists(UPLOAD_DIR . $filename)) {
        return UPLOAD_URL . DEFAULT_AVATAR;
    }
    return UPLOAD_URL . rawurlencode($filename);
}

// ── File upload ──────────────────────────────────────────────
/**
 * Validates and moves an uploaded profile image.
 *
 * @param  array $file     $_FILES['field'] entry
 * @param  int   $userId   Owner user ID (used in filename)
 * @return string          New filename stored in DB
 * @throws RuntimeException on any validation or move failure
 */
function uploadProfileImage(array $file, int $userId): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension.',
        ];
        throw new RuntimeException($errMap[$file['error']] ?? 'Unknown upload error.');
    }

    // Use admin-configured upload limit (falls back to MAX_UPLOAD_SIZE constant)
    $limitMb    = (int)getSetting('upload_limit_mb', '2');
    $limitBytes = $limitMb > 0 ? $limitMb * 1024 * 1024 : MAX_UPLOAD_SIZE;

    if ($file['size'] > $limitBytes) {
        throw new RuntimeException("File too large. Maximum size is {$limitMb} MB.");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXT, true)) {
        throw new RuntimeException('Invalid file type. Allowed: JPG, PNG, GIF.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MIME, true)) {
        throw new RuntimeException('File content does not match an allowed image type.');
    }

    // Ensure directory exists (Windows XAMPP compatibility)
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException('Upload directory could not be created: ' . UPLOAD_DIR);
    }

    $newName = sprintf('user_%d_%d.%s', $userId, time(), $ext);
    $dest    = UPLOAD_DIR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file. Check directory permissions.');
    }

    return $newName;
}

// ── Delete old profile image (skip default) ──────────────────
function deleteProfileImage(string $filename): void
{
    if ($filename && $filename !== DEFAULT_AVATAR) {
        $path = UPLOAD_DIR . $filename;
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}

// ── Nav pages (used by public header) ────────────────────────
function getNavPages(): array
{
    try {
        $stmt = getDB()->query(
            "SELECT slug, title FROM pages
             WHERE show_in_nav = 1 ORDER BY nav_order ASC, title ASC"
        );
        return $stmt->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

// ── Settings key/value store ──────────────────────────────────
function getSetting(string $key, string $default = ''): string
{
    try {
        $stmt = getDB()->prepare('SELECT value FROM settings WHERE `skey` = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (PDOException) {
        return $default;
    }
}

function setSetting(string $key, string $value): void
{
    getDB()->prepare(
        'INSERT INTO settings (`skey`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
    )->execute([$key, $value]);
}

// ── Dynamic site identity (reads from settings, falls back to defaults) ──
const _SITE_NAME_FALLBACK        = 'Virtual Visiting Card';
const _SITE_DESC_FALLBACK        = 'Create and share your digital visiting card.';

function siteName(): string
{
    static $_cached = null;
    if ($_cached === null) {
        $fallback = defined('APP_NAME') ? APP_NAME : _SITE_NAME_FALLBACK;
        try { $_cached = getSetting('site_name') ?: $fallback; }
        catch (Exception) { $_cached = $fallback; }
    }
    return $_cached;
}

function siteDescription(): string
{
    static $_cached = null;
    if ($_cached === null) {
        $fallback = defined('APP_DESCRIPTION') ? APP_DESCRIPTION : _SITE_DESC_FALLBACK;
        try { $_cached = getSetting('site_description') ?: $fallback; }
        catch (Exception) { $_cached = $fallback; }
    }
    return $_cached;
}

function siteUrl(): string
{
    return BASE_URL;
}

// ── Theme settings (Admin → Settings → Appearance) ────────────
function getTheme(): array
{
    static $_cached = null;
    if ($_cached === null) {
        $defaults = [
            'primary_color'    => '#4f46e5',
            'accent_color'     => '#7c3aed',
            'text_color'       => '#374151',
            'heading_color'    => '#0f172a',
            'bg_color'         => '#f8fafc',
            'surface_color'    => '#ffffff',
            'border_radius'    => '12',
            'font_heading'     => 'Space Grotesk',
            'font_body'        => 'system-ui',
            'enable_animations'=> '1',
        ];
        $_cached = $defaults;
        try {
            foreach ($defaults as $k => $v) {
                $_cached[$k] = getSetting('theme_' . $k, $v) ?: $v;
            }
        } catch (Exception) {
            // keep defaults
        }
    }
    return $_cached;
}

/**
 * Returns a hex color darkened by the given percentage (0-100).
 * Used to generate hover/active states from the primary color.
 */
function darkenColor(string $hex, int $percent = 15): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return '#' . $hex;

    $r = max(0, (int)hexdec(substr($hex, 0, 2)) * (100 - $percent) / 100);
    $g = max(0, (int)hexdec(substr($hex, 2, 2)) * (100 - $percent) / 100);
    $b = max(0, (int)hexdec(substr($hex, 4, 2)) * (100 - $percent) / 100);

    return sprintf('#%02x%02x%02x', (int)$r, (int)$g, (int)$b);
}

/**
 * Returns a hex color lightened/mixed toward white by the given percentage.
 */
function lightenColor(string $hex, int $percent = 90): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return '#' . $hex;

    $r = (int)hexdec(substr($hex, 0, 2));
    $g = (int)hexdec(substr($hex, 2, 2));
    $b = (int)hexdec(substr($hex, 4, 2));

    $r = (int)($r + (255 - $r) * $percent / 100);
    $g = (int)($g + (255 - $g) * $percent / 100);
    $b = (int)($b + (255 - $b) * $percent / 100);

    return sprintf('#%02x%02x%02x', min(255,$r), min(255,$g), min(255,$b));
}

// ── SEO: meta robots tag content ──────────────────────────────
/**
 * Resolves the effective robots directive for a page/profile,
 * combining the per-item setting with the site-wide noindex toggle.
 *
 * @param string $itemDirective e.g. 'index,follow', 'noindex,nofollow'
 * @return string Final directive for <meta name="robots">
 */
function resolveMetaRobots(string $itemDirective = 'index,follow'): string
{
    $allowed = ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'];
    if (!in_array($itemDirective, $allowed, true)) {
        $itemDirective = 'index,follow';
    }

    // Site-wide noindex overrides everything to noindex,nofollow
    if (getSetting('seo_global_noindex', '0') === '1') {
        return 'noindex,nofollow';
    }

    return $itemDirective;
}

function metaRobotsLabel(string $directive): string
{
    return match ($directive) {
        'noindex,nofollow' => 'Hidden (noindex, nofollow)',
        'noindex,follow'   => 'Noindex (links still followed)',
        'index,nofollow'   => 'Indexed (links not followed)',
        default            => 'Indexed (default)',
    };
}

// ── SEO: build robots.txt content ─────────────────────────────
function buildRobotsTxt(): string
{
    $custom = getSetting('robots_txt_custom', '');

    if (trim($custom) !== '') {
        $out = rtrim($custom);
        // Ensure a Sitemap directive is present
        if (stripos($out, 'sitemap:') === false) {
            $out .= "\n\nSitemap: " . BASE_URL . "sitemap.xml";
        }
        return $out . "\n";
    }

    // Site-wide noindex → block everything
    if (getSetting('seo_global_noindex', '0') === '1') {
        return "User-agent: *\nDisallow: /\n";
    }

    // Default ruleset
    $lines = [
        'User-agent: *',
        'Allow: /',
        '',
        '# Admin panel',
        'Disallow: /admin/',
        '',
        '# Auth & account pages',
        'Disallow: /login',
        'Disallow: /logout',
        'Disallow: /register',
        'Disallow: /forgot-password',
        'Disallow: /reset-password',
        'Disallow: /change-password',
        'Disallow: /edit-profile',
        '',
        '# Setup',
        'Disallow: /setup.php',
        '',
        'Sitemap: ' . BASE_URL . 'sitemap.xml',
    ];

    return implode("\n", $lines) . "\n";
}
