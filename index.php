<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// ── Parse slug from REQUEST_URI (no hardcoded GET param needed) ──
$_reqPath  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$_basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
$url       = trim(substr($_reqPath, strlen($_basePath)), '/');
$url       = strtolower(filter_var($url, FILTER_SANITIZE_URL));

// Allow lowercase slugs, dots (sitemap.xml), hyphens, underscores
if (!preg_match('/^[a-z0-9._-]*$/', $url)) {
    $url = '404';
}

// ─── 1. SYSTEM ROUTES ────────────────────────────────────────
$systemRoutes = [
    ''                => __DIR__ . '/login.php',
    'login'           => __DIR__ . '/login.php',
    'logout'          => __DIR__ . '/logout.php',
    'register'        => __DIR__ . '/register.php',
    'forgot-password' => __DIR__ . '/forgot_password.php',
    'reset-password'  => __DIR__ . '/reset_password.php',
    'edit-profile'    => __DIR__ . '/edit_profile.php',
    'change-password' => __DIR__ . '/change_password.php',
    'members'         => __DIR__ . '/members.php',
    'sitemap.xml'     => __DIR__ . '/sitemap.php',
    'robots.txt'      => __DIR__ . '/robots.php',
];

if (array_key_exists($url, $systemRoutes)) {
    require $systemRoutes[$url];
    exit;
}

// ─── 2. PAGES TABLE ──────────────────────────────────────────
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
    $stmt->execute([$url]);
    $page = $stmt->fetch();

    if ($page) {
        $pageTitle = $page['title'];
        $ogData = [
            'type'        => 'article',
            'title'       => $page['title'] . ' — ' . siteName(),
            'description' => truncate(strip_tags((string)$page['content']), 160),
            'image'       => BASE_URL . 'assets/img/default-avatar.svg',
            'url'         => BASE_URL . $page['slug'],
        ];
        require __DIR__ . '/templates/layout_header.php';
        require __DIR__ . '/templates/page.php';
        require __DIR__ . '/templates/layout_footer.php';
        exit;
    }

    // ─── 3. USERS TABLE ──────────────────────────────────────
    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = ? LIMIT 1');
    $stmt->execute([$url]);
    $profileUser = $stmt->fetch();

    if ($profileUser) {
        $fStmt = $pdo->prepare(
            'SELECT pf.id, pf.field_label, pf.field_type, pf.field_icon,
                    COALESCE(ufv.field_value, \'\') AS field_value
             FROM   profile_fields pf
             LEFT JOIN user_field_values ufv
                    ON pf.id = ufv.field_id AND ufv.user_id = ?
             WHERE  pf.is_active = 1
             ORDER  BY pf.sort_order ASC'
        );
        $fStmt->execute([$profileUser['id']]);
        $profileFields = $fStmt->fetchAll();

        $pageTitle = $profileUser['username'] . ' — ' . siteName();
        $ogData = [
            'type'        => 'profile',
            'title'       => $profileUser['username'] . ' — ' . siteName(),
            'description' => $profileUser['bio']
                ? truncate(strip_tags((string)$profileUser['bio']), 160)
                : $profileUser['username'] . ' on ' . siteName(),
            'image'       => avatarUrl($profileUser['profile_image']),
            'url'         => BASE_URL . $profileUser['username'],
        ];
        require __DIR__ . '/templates/layout_header.php';
        require __DIR__ . '/templates/profile.php';
        require __DIR__ . '/templates/layout_footer.php';
        exit;
    }
} catch (PDOException $e) {
    error_log('[Router] ' . $e->getMessage());
}

// ─── 4. 404 ──────────────────────────────────────────────────
http_response_code(404);
$pageTitle = '404 — Page Not Found';
require __DIR__ . '/templates/layout_header.php';
require __DIR__ . '/templates/404.php';
require __DIR__ . '/templates/layout_footer.php';
