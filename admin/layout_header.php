<?php
declare(strict_types=1);
/**
 * Each admin page sets:
 *   $pageTitle  (string)  — page heading
 *   $activeNav  (string)  — sidebar active key:
 *                           dashboard | users | pages | fields | nav | invitations | settings
 */
$activeNav  = $activeNav  ?? 'dashboard';
$pageTitle  = $pageTitle  ?? 'Admin';
$_adminUser = $_SESSION['username'] ?? 'Admin';

function _adminNavLink(string $href, string $icon, string $label, string $key, string $active): string
{
    $cls = $key === $active ? 'active' : '';
    return '<li class="nav-item mb-1">
        <a class="admin-nav-link ' . $cls . '" href="' . $href . '">
            <i class="' . $icon . ' fa-fw me-2"></i>' . $label . '
        </a>
    </li>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Admin · <?= e(siteName()) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
<?php
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

            /* Admin sidebar accent follows brand primary color */
            --adm-sidebar-active: <?= e($_theme['primary_color']) ?>;
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
if ($_theme['font_heading'] !== 'Space Grotesk' && $_theme['font_heading'] !== ''):
    $_fontParam = str_replace(' ', '+', $_theme['font_heading']);
?>
    <link href="https://fonts.googleapis.com/css2?family=<?= e($_fontParam) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php endif; ?>
</head>
<body class="admin-body">

<!-- ── Top Navbar ─────────────────────────────────────────────── -->
<nav class="admin-topbar navbar navbar-dark fixed-top">
    <div class="container-fluid px-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-link text-white p-1" id="sidebarToggle" title="Toggle sidebar">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            <a class="navbar-brand admin-brand mb-0" href="<?= BASE_URL ?>admin/">
                <i class="fas fa-layer-group me-2"></i><?= e(siteName()) ?>
                <span class="admin-badge ms-2">Admin</span>
            </a>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-sm btn-outline-light">
                <i class="fas fa-external-link-alt me-1"></i>
                <span class="d-none d-md-inline">View Site</span>
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-link text-white d-flex align-items-center gap-2 text-decoration-none"
                        type="button" id="adminUserDrop">
                    <i class="fas fa-user-circle fa-lg"></i>
                    <span class="d-none d-md-inline"><?= e($_adminUser) ?></span>
                    <i class="fas fa-chevron-down fa-xs"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" id="adminUserMenu">
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL . e($_adminUser) ?>">
                            <i class="fas fa-id-card me-2 text-muted"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>edit-profile">
                            <i class="fas fa-pen me-2 text-muted"></i>Edit Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>change-password">
                            <i class="fas fa-lock me-2 text-muted"></i>Change Password
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>" target="_blank">
                            <i class="fas fa-external-link-alt me-2 text-muted"></i>View Site
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger fw-semibold" href="<?= BASE_URL ?>logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="admin-wrapper">
    <!-- ── Sidebar ──────────────────────────────────────────── -->
    <aside class="admin-sidebar" id="adminSidebar">
        <nav class="admin-nav p-3">

            <div class="admin-nav-section-label">Overview</div>
            <ul class="nav flex-column mb-2">
                <?= _adminNavLink(BASE_URL . 'admin/', 'fas fa-tachometer-alt', 'Dashboard', 'dashboard', $activeNav) ?>
            </ul>

            <div class="admin-nav-section-label">Content</div>
            <ul class="nav flex-column mb-2">
                <?= _adminNavLink(BASE_URL . 'admin/pages/', 'fas fa-file-alt', 'Pages', 'pages', $activeNav) ?>
                <?= _adminNavLink(BASE_URL . 'admin/nav/', 'fas fa-bars', 'Navigation', 'nav', $activeNav) ?>
            </ul>

            <div class="admin-nav-section-label">Users</div>
            <ul class="nav flex-column mb-2">
                <?= _adminNavLink(BASE_URL . 'admin/users/', 'fas fa-users', 'All Users', 'users', $activeNav) ?>
                <?= _adminNavLink(BASE_URL . 'admin/fields/', 'fas fa-list-alt', 'Profile Fields', 'fields', $activeNav) ?>
                <?= _adminNavLink(BASE_URL . 'admin/invitations/', 'fas fa-envelope-open-text', 'Invitations', 'invitations', $activeNav) ?>
            </ul>

            <div class="admin-nav-section-label">System</div>
            <ul class="nav flex-column">
                <?= _adminNavLink(BASE_URL . 'admin/settings/', 'fas fa-sliders-h', 'Settings', 'settings', $activeNav) ?>
                <?php if (!file_exists(dirname(__DIR__) . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')): ?>
                <?= _adminNavLink(BASE_URL . 'install_phpmailer.php', 'fas fa-envelope-open-text', 'Install Mailer', 'mailer', $activeNav) ?>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- ── Content Area ─────────────────────────────────────── -->
    <main class="admin-content" id="adminContent">
        <div class="admin-content-inner">
            <!-- Flash message -->
            <?= renderFlash() ?>
            <!-- Page title bar -->
            <div class="admin-page-header mb-4">
                <h1 class="admin-page-title"><?= e($pageTitle) ?></h1>
            </div>
