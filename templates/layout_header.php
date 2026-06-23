<?php
declare(strict_types=1);

if (!function_exists('getNavPages')) {
    require_once dirname(__DIR__) . '/helpers.php';
}

$_navPages   = getNavPages();
$_pageTitle  = isset($pageTitle) ? $pageTitle . ' — ' . siteName() : siteName();
$_activeUser = null;

if (isLoggedIn()) {
    try {
        $s = getDB()->prepare('SELECT id, username, role, profile_image FROM users WHERE id = ? LIMIT 1');
        $s->execute([$_SESSION['user_id']]);
        $_activeUser = $s->fetch();
    } catch (PDOException) {}
}

$_currentSlug = trim($_GET['url'] ?? '', '/');

$_og = array_merge([
    'type'        => 'website',
    'title'       => $_pageTitle,
    'description' => siteDescription(),
    'image'       => UPLOAD_URL . DEFAULT_AVATAR,
    'url'         => BASE_URL . ltrim($_currentSlug, '/'),
], $ogData ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($_pageTitle) ?></title>
    <meta name="description" content="<?= e($_og['description']) ?>">
    <meta name="robots" content="<?= e($metaRobots ?? resolveMetaRobots()) ?>">
    <meta property="og:type"         content="<?= e($_og['type']) ?>">
    <meta property="og:title"        content="<?= e($_og['title']) ?>">
    <meta property="og:description"  content="<?= e($_og['description']) ?>">
    <meta property="og:image"        content="<?= e($_og['image']) ?>">
    <meta property="og:url"          content="<?= e($_og['url']) ?>">
    <meta property="og:site_name"    content="<?= e(siteName()) ?>">
    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="<?= e($_og['title']) ?>">
    <meta name="twitter:description" content="<?= e($_og['description']) ?>">
    <meta name="twitter:image"       content="<?= e($_og['image']) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css">
<?php
// ── MDB Pro CSS (optional) ───────────────────────────────────
// Upload your MDB Pro files to assets/mdb-pro/ to enable Pro animations.
// The free MDB build is always loaded above as a base.
$_mdbProCss = __DIR__ . '/../assets/mdb-pro/mdb.pro.min.css';
$_mdbProJs  = __DIR__ . '/../assets/mdb-pro/mdb.pro.min.js';
if (file_exists($_mdbProCss)):
?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/mdb-pro/mdb.pro.min.css">
<?php endif; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
<?php
// ── Dynamic theme variables (Admin → Settings → Appearance) ──
$_theme = getTheme();
?>
    <style>
        :root {
            --cms-primary:      <?= e($_theme['primary_color']) ?>;
            --cms-primary-dark: <?= e(darkenColor($_theme['primary_color'], 15)) ?>;
            --cms-accent:       <?= e($_theme['accent_color']) ?>;
            --cms-ink:          <?= e($_theme['heading_color']) ?>;
            --cms-body:         <?= e($_theme['text_color']) ?>;
            --cms-bg:           <?= e($_theme['bg_color']) ?>;
            --cms-surface:      <?= e($_theme['surface_color']) ?>;
            --cms-radius:       <?= (int)$_theme['border_radius'] ?>px;
            --cms-font-display: '<?= e($_theme['font_heading']) ?>', system-ui, sans-serif;
            --cms-font-body:    <?= e($_theme['font_body']) ?>, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        <?php if ($_theme['enable_animations'] === '0'): ?>
        *, *::before, *::after {
            animation-duration: 0.001ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.001ms !important;
            scroll-behavior: auto !important;
        }
        <?php endif; ?>
    </style>
<?php
// Load custom font from Google Fonts if not the default
if ($_theme['font_heading'] !== 'Space Grotesk' && $_theme['font_heading'] !== ''):
    $_fontParam = str_replace(' ', '+', $_theme['font_heading']);
?>
    <link href="https://fonts.googleapis.com/css2?family=<?= e($_fontParam) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php endif; ?>
<?php
$_a_ga4     = getSetting('analytics_ga4_id');
$_a_gtm     = getSetting('analytics_gtm_id');
$_a_clarity = getSetting('analytics_clarity_id');
$_a_fbpx    = getSetting('analytics_fb_pixel_id');
$_a_hotjar  = getSetting('analytics_hotjar_id');
$_a_plaus   = getSetting('analytics_plausible_domain');
$_a_cHead   = getSetting('analytics_custom_head');
$_a_cBody   = getSetting('analytics_custom_body');
if ($_a_gtm !== ''): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($_a_gtm) ?>');</script>
<?php endif;
if ($_a_ga4 !== '' && $_a_gtm === ''): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($_a_ga4) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($_a_ga4) ?>');</script>
<?php endif;
if ($_a_clarity !== ''): ?>
    <script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","<?= e($_a_clarity) ?>");</script>
<?php endif;
if ($_a_fbpx !== ''): ?>
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($_a_fbpx) ?>');fbq('track','PageView');</script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= e($_a_fbpx) ?>&ev=PageView&noscript=1" alt=""></noscript>
<?php endif;
if ($_a_hotjar !== ''): ?>
    <script>(function(h,o,t,j,a,r){h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};h._hjSettings={hjid:<?= (int)$_a_hotjar ?>,hjsv:6};a=o.getElementsByTagName('head')[0];r=o.createElement('script');r.async=1;r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;a.appendChild(r);})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');</script>
<?php endif;
if ($_a_plaus !== ''): ?>
    <script defer data-domain="<?= e($_a_plaus) ?>" src="https://plausible.io/js/script.js"></script>
<?php endif;
if ($_a_cHead !== ''): echo $_a_cHead; endif; ?>
</head>
<body>
<?php if (!empty($_a_gtm)): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($_a_gtm) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif;
if (!empty($_a_cBody)): echo $_a_cBody; endif; ?>

<!-- ── Navbar ──────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg cms-navbar sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand cms-brand" href="<?= BASE_URL ?>">
            <i class="fas fa-layer-group me-2"></i><?= e(siteName()) ?>
        </a>

        <!-- Mobile: show logout directly if logged in -->
        <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
            <?php if ($_activeUser): ?>
                <a href="<?= BASE_URL ?>logout"
                   class="btn btn-sm btn-outline-danger"
                   title="Sign Out">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php endif; ?>
            <button class="navbar-toggler border-0" id="navbarTogglerBtn" type="button"
                    aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navMain">
            <!-- Dynamic page links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($_navPages as $_np): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $_currentSlug === $_np['slug'] ? 'active' : '' ?>"
                       href="<?= BASE_URL . e($_np['slug']) ?>">
                        <?= e($_np['title']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $_currentSlug === 'members' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>members">
                        <i class="fas fa-users me-1"></i>Members
                    </a>
                </li>
            </ul>

            <!-- Auth section -->
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <?php if ($_activeUser): ?>
                    <?php if ($_activeUser['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link cms-admin-link" href="<?= BASE_URL ?>admin/">
                            <i class="fas fa-cog me-1"></i>Admin
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Desktop dropdown -->
                    <li class="nav-item cms-user-wrap d-none d-lg-block">
                        <a class="nav-link cms-user-toggle d-flex align-items-center gap-2"
                           href="#" id="userDropDesktop" role="button">
                            <img src="<?= avatarUrl($_activeUser['profile_image']) ?>"
                                 class="rounded-circle cms-nav-avatar" width="32" height="32"
                                 style="object-fit:cover;" alt="">
                            <span><?= e($_activeUser['username']) ?></span>
                        </a>
                        <ul class="cms-user-menu shadow border-0" id="userDropMenu">
                            <li class="px-3 py-1">
                                <small class="text-muted">Signed in as <strong><?= e($_activeUser['username']) ?></strong></small>
                            </li>
                            <li><hr class="cms-menu-divider my-1"></li>
                            <li><a class="cms-menu-item" href="<?= BASE_URL . e($_activeUser['username']) ?>">
                                <i class="fas fa-id-card me-2 text-muted"></i>My Profile</a></li>
                            <li><a class="cms-menu-item" href="<?= BASE_URL ?>edit-profile">
                                <i class="fas fa-pen me-2 text-muted"></i>Edit Profile</a></li>
                            <li><a class="cms-menu-item" href="<?= BASE_URL ?>change-password">
                                <i class="fas fa-lock me-2 text-muted"></i>Change Password</a></li>
                            <li><hr class="cms-menu-divider my-1"></li>
                            <li><a class="cms-menu-item cms-menu-item--danger" href="<?= BASE_URL ?>logout">
                                <i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                        </ul>
                    </li>

                    <!-- Mobile: expanded links (no dropdown needed) -->
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="<?= BASE_URL . e($_activeUser['username']) ?>">
                            <i class="fas fa-id-card me-2"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="<?= BASE_URL ?>edit-profile">
                            <i class="fas fa-pen me-2"></i>Edit Profile
                        </a>
                    </li>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="<?= BASE_URL ?>change-password">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </a>
                    </li>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link text-danger fw-semibold" href="<?= BASE_URL ?>logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Sign Out
                        </a>
                    </li>

                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>login">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <?php if (getSetting('registration_open', '1') === '1'): ?>
                    <li class="nav-item ms-1">
                        <a class="btn btn-primary btn-sm px-3" href="<?= BASE_URL ?>register">
                            Sign Up
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
