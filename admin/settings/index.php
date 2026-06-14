<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../mailer.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_general') {
        setSetting('registration_open',  isset($_POST['registration_open']) ? '1' : '0');
        setSetting('site_name',          trim($_POST['site_name']        ?? APP_NAME));
        setSetting('site_description',   trim($_POST['site_description'] ?? APP_DESCRIPTION));
        flash('success', 'General settings saved.');
        redirect('admin/settings/?tab=general');
    }

    if ($action === 'save_analytics') {
        foreach (['analytics_ga4_id','analytics_gtm_id','analytics_clarity_id',
                  'analytics_fb_pixel_id','analytics_hotjar_id','analytics_plausible_domain',
                  'analytics_custom_head','analytics_custom_body'] as $k) {
            setSetting($k, trim($_POST[$k] ?? ''));
        }
        flash('success', 'Analytics settings saved.');
        redirect('admin/settings/?tab=analytics');
    }

    if ($action === 'save_smtp') {
        foreach (['smtp_host','smtp_port','smtp_username','smtp_password',
                  'smtp_encryption','smtp_from_email','smtp_from_name'] as $k) {
            setSetting($k, trim($_POST[$k] ?? ''));
        }
        flash('success', 'SMTP settings saved.');
        redirect('admin/settings/?tab=smtp');
    }

    if ($action === 'test_smtp') {
        $testEmail = trim($_POST['test_email'] ?? '');
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid test email address.');
        } else {
            try {
                $mailer = new Mailer();
                if (!$mailer->isConfigured()) {
                    flash('error', 'SMTP is not fully configured or PHPMailer is not installed.');
                } else {
                    $html  = Mailer::buildHtml(
                        'SMTP Test Email',
                        '<p>This is a test email from <strong>' . e(siteName()) . '</strong>.</p>
                         <p>If you received this, your SMTP configuration is working correctly.</p>'
                    );
                    $mailer->send($testEmail, $testEmail, 'SMTP Test — ' . siteName(), $html);
                    flash('success', 'Test email sent to ' . $testEmail . '.');
                }
            } catch (Exception $ex) {
                flash('error', 'SMTP Error: ' . $ex->getMessage());
            }
        }
        redirect('admin/settings/?tab=smtp');
    }
}

$registrationOpen = getSetting('registration_open', '1') === '1';
$siteName         = getSetting('site_name',        APP_NAME);
$siteDesc         = getSetting('site_description', APP_DESCRIPTION);

$analytics = [
    'ga4_id'           => getSetting('analytics_ga4_id'),
    'gtm_id'           => getSetting('analytics_gtm_id'),
    'clarity_id'       => getSetting('analytics_clarity_id'),
    'fb_pixel_id'      => getSetting('analytics_fb_pixel_id'),
    'hotjar_id'        => getSetting('analytics_hotjar_id'),
    'plausible_domain' => getSetting('analytics_plausible_domain'),
    'custom_head'      => getSetting('analytics_custom_head'),
    'custom_body'      => getSetting('analytics_custom_body'),
];

$smtp = [
    'host'       => getSetting('smtp_host'),
    'port'       => getSetting('smtp_port', '587'),
    'username'   => getSetting('smtp_username'),
    'password'   => getSetting('smtp_password'),
    'encryption' => getSetting('smtp_encryption', 'tls'),
    'from_email' => getSetting('smtp_from_email'),
    'from_name'  => getSetting('smtp_from_name', siteName()),
];

$smtpConfigured = (new Mailer())->isConfigured();
$activeTab      = $_GET['tab'] ?? 'general';
$dbVersion      = $pdo->query('SELECT VERSION()')->fetchColumn();
$totalUsers     = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalPages     = (int)$pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();

$pageTitle = 'Settings';
$activeNav = 'settings';
require_once __DIR__ . '/../layout_header.php';
?>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'general'   ? 'active' : '' ?>" href="?tab=general">
            <i class="fas fa-sliders-h me-1"></i>General
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'smtp'      ? 'active' : '' ?>" href="?tab=smtp">
            <i class="fas fa-envelope me-1"></i>SMTP / Email
            <?php if ($smtpConfigured): ?>
                <span class="badge bg-success ms-1">On</span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'analytics' ? 'active' : '' ?>" href="?tab=analytics">
            <i class="fas fa-chart-line me-1"></i>Analytics
            <?php $ac = count(array_filter(array_slice($analytics,0,6),'strlen')); ?>
            <?php if ($ac): ?><span class="badge bg-success ms-1"><?= $ac ?></span><?php endif; ?>
        </a>
    </li>
</ul>

<?php if ($activeTab === 'general'): ?>
<!-- ── General ──────────────────────────────────────────────── -->
<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_general">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-globe me-2 text-primary"></i>Site Identity</div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="site_name" name="site_name" class="form-control"
                                       value="<?= e($siteName) ?>" required>
                                <label class="form-label" for="site_name">Site Name</label>
                            </div>
                            <div class="form-text">Shown in navbar, browser tab, emails and meta tags.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="site_description" name="site_description" class="form-control"
                                       value="<?= e($siteDesc) ?>">
                                <label class="form-label" for="site_description">Site Description</label>
                            </div>
                            <div class="form-text">Used in Open Graph and meta description tags.</div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                Current URL: <code><?= e(BASE_URL) ?></code> — auto-detected from server environment.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <p class="fw-semibold mb-1">Public Registration</p>
                            <p class="text-muted small mb-0">When <strong>OFF</strong>, the Sign Up link is hidden. Invited users can still register.</p>
                        </div>
                        <div class="form-check form-switch ms-3 flex-shrink-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="registration_open" name="registration_open"
                                   <?= $registrationOpen ? 'checked' : '' ?>
                                   style="width:3rem;height:1.5rem;">
                        </div>
                    </div>
                    <div class="alert mt-3 mb-0 py-2 <?= $registrationOpen ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center">
                        <i class="fas fa-<?= $registrationOpen ? 'lock-open text-success' : 'lock text-warning' ?> me-2"></i>
                        <span class="small">Registration is currently <strong><?= $registrationOpen ? 'OPEN' : 'CLOSED' ?></strong>.</span>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Settings</button>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold"><i class="fas fa-server me-2 text-secondary"></i>System Info</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td class="text-muted ps-4">PHP</td><td class="fw-semibold pe-4"><code><?= e(PHP_VERSION) ?></code></td></tr>
                        <tr><td class="text-muted ps-4">MySQL</td><td class="fw-semibold pe-4"><code><?= e($dbVersion) ?></code></td></tr>
                        <tr><td class="text-muted ps-4">Site Name</td><td class="fw-semibold pe-4"><?= e(siteName()) ?></td></tr>
                        <tr><td class="text-muted ps-4">Base URL</td><td class="fw-semibold pe-4 small"><a href="<?= BASE_URL ?>" target="_blank"><?= e(BASE_URL) ?></a></td></tr>
                        <tr><td class="text-muted ps-4">Users</td><td class="fw-semibold pe-4"><?= $totalUsers ?></td></tr>
                        <tr><td class="text-muted ps-4">Pages</td><td class="fw-semibold pe-4"><?= $totalPages ?></td></tr>
                        <tr><td class="text-muted ps-4">Upload Limit</td><td class="fw-semibold pe-4"><?= number_format(MAX_UPLOAD_SIZE/1048576,0) ?> MB</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php elseif ($activeTab === 'smtp'): ?>
<!-- ── SMTP ──────────────────────────────────────────────────── -->
<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_smtp">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-server me-2 text-primary"></i>SMTP Server</span>
                    <?php if ($smtpConfigured): ?>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Configured</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Not Configured</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="form-outline">
                                <input type="text" id="smtp_host" name="smtp_host" class="form-control"
                                       value="<?= e($smtp['host']) ?>" placeholder="smtp.gmail.com">
                                <label class="form-label" for="smtp_host">SMTP Host</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="number" id="smtp_port" name="smtp_port" class="form-control"
                                       value="<?= e($smtp['port']) ?>">
                                <label class="form-label" for="smtp_port">Port</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="smtp_username" name="smtp_username" class="form-control"
                                       value="<?= e($smtp['username']) ?>" autocomplete="off">
                                <label class="form-label" for="smtp_username">Username</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="password" id="smtp_password" name="smtp_password" class="form-control"
                                       value="<?= e($smtp['password']) ?>" autocomplete="new-password">
                                <label class="form-label" for="smtp_password">Password</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Encryption</label>
                            <select name="smtp_encryption" class="form-select">
                                <option value="tls"  <?= $smtp['encryption'] === 'tls'  ? 'selected' : '' ?>>TLS (STARTTLS)</option>
                                <option value="ssl"  <?= $smtp['encryption'] === 'ssl'  ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= $smtp['encryption'] === 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <div class="form-outline">
                                <input type="email" id="smtp_from_email" name="smtp_from_email" class="form-control"
                                       value="<?= e($smtp['from_email']) ?>">
                                <label class="form-label" for="smtp_from_email">From Email</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-outline">
                                <input type="text" id="smtp_from_name" name="smtp_from_name" class="form-control"
                                       value="<?= e($smtp['from_name']) ?>">
                                <label class="form-label" for="smtp_from_name">From Name</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-save me-1"></i> Save SMTP Settings
            </button>
        </form>

        <!-- Test Email -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header fw-semibold">
                <i class="fas fa-vial me-2 text-warning"></i>Send Test Email
            </div>
            <div class="card-body p-4">
                <?php if (!$smtpConfigured): ?>
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Save your SMTP credentials and install PHPMailer before testing.
                    </div>
                <?php endif; ?>
                <form method="POST" class="d-flex gap-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="test_smtp">
                    <div class="form-outline flex-grow-1">
                        <input type="email" id="test_email" name="test_email" class="form-control"
                               value="<?= e($_SESSION['user_id'] ? '' : '') ?>"
                               placeholder="you@example.com" <?= !$smtpConfigured ? 'disabled' : '' ?>>
                        <label class="form-label" for="test_email">Recipient</label>
                    </div>
                    <button type="submit" class="btn btn-warning" <?= !$smtpConfigured ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane me-1"></i> Send
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-info"></i>Setup Guide</div>
            <div class="card-body p-4 small text-muted">
                <p class="fw-semibold text-dark mb-2">1. Install PHPMailer</p>
                <pre class="bg-light rounded p-2 small">composer require phpmailer/phpmailer</pre>
                <p class="fw-semibold text-dark mb-2 mt-3">2. Common providers</p>
                <table class="table table-sm table-bordered small">
                    <thead class="table-light"><tr><th>Provider</th><th>Host</th><th>Port</th></tr></thead>
                    <tbody>
                        <tr><td>Gmail</td><td>smtp.gmail.com</td><td>587</td></tr>
                        <tr><td>Outlook</td><td>smtp.office365.com</td><td>587</td></tr>
                        <tr><td>Mailgun</td><td>smtp.mailgun.org</td><td>587</td></tr>
                        <tr><td>SendGrid</td><td>smtp.sendgrid.net</td><td>587</td></tr>
                        <tr><td>Brevo</td><td>smtp-relay.brevo.com</td><td>587</td></tr>
                    </tbody>
                </table>
                <p class="fw-semibold text-dark mb-1 mt-2">3. What uses email</p>
                <ul class="ps-3 mb-0">
                    <li>Password reset links</li>
                    <li>User invitations</li>
                </ul>
                <p class="mt-2 mb-0">When SMTP is <strong>not configured</strong>, links are shown on-screen (dev mode).</p>
            </div>
        </div>
    </div>
</div>

<?php elseif ($activeTab === 'analytics'): ?>
<!-- ── Analytics ─────────────────────────────────────────────── -->
<form method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_analytics">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-plug me-2 text-primary"></i>Platform IDs</div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php
                        $platforms = [
                            ['key'=>'analytics_ga4_id',          'label'=>'Google Analytics 4',  'val'=>$analytics['ga4_id'],           'ph'=>'G-XXXXXXXXXX',     'hint'=>'GA4 → Admin → Data Streams'],
                            ['key'=>'analytics_gtm_id',          'label'=>'Google Tag Manager',   'val'=>$analytics['gtm_id'],           'ph'=>'GTM-XXXXXXX',      'hint'=>'GTM → Admin → Container'],
                            ['key'=>'analytics_clarity_id',      'label'=>'Microsoft Clarity',    'val'=>$analytics['clarity_id'],       'ph'=>'xxxxxxxxxx',       'hint'=>'Clarity → Settings → Overview'],
                            ['key'=>'analytics_fb_pixel_id',     'label'=>'Meta (Facebook) Pixel','val'=>$analytics['fb_pixel_id'],      'ph'=>'000000000000000',  'hint'=>'Meta Events Manager'],
                            ['key'=>'analytics_hotjar_id',       'label'=>'Hotjar',               'val'=>$analytics['hotjar_id'],        'ph'=>'0000000',          'hint'=>'Hotjar → Settings → Sites'],
                            ['key'=>'analytics_plausible_domain','label'=>'Plausible Analytics',  'val'=>$analytics['plausible_domain'], 'ph'=>'yourdomain.com',   'hint'=>'Your domain registered in Plausible'],
                        ];
                        foreach ($platforms as $p):
                        ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded border <?= $p['val'] ? 'border-success bg-success bg-opacity-10' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold small"><?= e($p['label']) ?></span>
                                    <?php if ($p['val']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-outline">
                                    <input type="text" id="<?= e($p['key']) ?>" name="<?= e($p['key']) ?>"
                                           class="form-control form-control-sm font-monospace"
                                           value="<?= e($p['val']) ?>" placeholder="<?= e($p['ph']) ?>">
                                    <label class="form-label" for="<?= e($p['key']) ?>"><?= e($p['label']) ?></label>
                                </div>
                                <div class="form-text"><?= e($p['hint']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-code me-2 text-warning"></i>Custom Code Injection</div>
                <div class="card-body p-4">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>Only paste code from trusted sources.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><span class="badge bg-dark font-monospace me-1">&lt;head&gt;</span>Custom Head Code</label>
                        <textarea name="analytics_custom_head" class="form-control font-monospace" rows="4" style="font-size:.8rem;"><?= e($analytics['custom_head']) ?></textarea>
                        <div class="form-text">Injected before <code>&lt;/head&gt;</code>.</div>
                    </div>
                    <div>
                        <label class="form-label fw-semibold"><span class="badge bg-dark font-monospace me-1">&lt;body&gt;</span>Custom Body Code</label>
                        <textarea name="analytics_custom_body" class="form-control font-monospace" rows="4" style="font-size:.8rem;"><?= e($analytics['custom_body']) ?></textarea>
                        <div class="form-text">Injected after <code>&lt;body&gt;</code> open.</div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Analytics</button>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-semibold"><i class="fas fa-signal me-2 text-success"></i>Active</div>
                <div class="card-body p-3">
                    <?php $names = ['Google Analytics 4'=>$analytics['ga4_id'],'Google Tag Manager'=>$analytics['gtm_id'],'Microsoft Clarity'=>$analytics['clarity_id'],'Meta Pixel'=>$analytics['fb_pixel_id'],'Hotjar'=>$analytics['hotjar_id'],'Plausible'=>$analytics['plausible_domain'],'Custom Head'=>$analytics['custom_head'],'Custom Body'=>$analytics['custom_body']];
                    $anyActive = false;
                    foreach ($names as $n => $v): if (!$v) continue; $anyActive = true; ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-circle text-success" style="font-size:.45rem;"></i>
                            <span class="small"><?= e($n) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$anyActive): ?><p class="text-muted small mb-0">None active.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<style>
.nav-tabs .nav-link { border-radius: 8px 8px 0 0; font-weight: 500; }
.nav-tabs .nav-link.active { color: #4f46e5; }
</style>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
