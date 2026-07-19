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
        $pageTitle  = $page['title'];
        $metaRobots = resolveMetaRobots($page['meta_robots'] ?? 'index,follow');
        $ogData = [
            'type'        => 'article',
            'title'       => $page['title'] . ' — ' . siteName(),
            'description' => truncate(strip_tags((string)$page['content']), 160),
            'image'       => UPLOAD_URL . DEFAULT_AVATAR,
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
        // Fetch field definitions
        $fStmt = $pdo->prepare(
            'SELECT id, field_label, field_type, field_icon, field_options, is_public,
                    is_repeatable, group_key, group_label, sort_order
             FROM   profile_fields
             WHERE  is_active = 1
             ORDER  BY sort_order ASC'
        );
        $fStmt->execute();
        $fieldDefs = $fStmt->fetchAll();

        // Fetch ALL values (all instances) for this user in one query
        $vStmt = $pdo->prepare(
            'SELECT field_id, instance, field_value FROM user_field_values
             WHERE user_id = ? ORDER BY field_id ASC, instance ASC'
        );
        $vStmt->execute([$profileUser['id']]);
        $valuesByField = [];
        foreach ($vStmt->fetchAll() as $v) {
            $valuesByField[(int)$v['field_id']][(int)$v['instance']] = $v['field_value'];
        }

        // Attach values array to each field definition
        foreach ($fieldDefs as &$fd) {
            $fd['values'] = $valuesByField[(int)$fd['id']] ?? [];
        }
        unset($fd);
        $profileFields = $fieldDefs;

        $displayName = displayName($profileUser);
        $pageTitle  = $displayName . ' — ' . siteName();
        $metaRobots = resolveMetaRobots($profileUser['meta_robots'] ?? 'index,follow');
        $ogData = [
            'type'        => 'profile',
            'title'       => $displayName . ' — ' . siteName(),
            'description' => $profileUser['bio']
                ? truncate(strip_tags((string)$profileUser['bio']), 160)
                : $displayName . ' on ' . siteName(),
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
