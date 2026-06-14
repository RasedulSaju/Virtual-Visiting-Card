<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/auth_check.php';

$pdo = getDB();

$totalUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages  = (int)$pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$totalFields = (int)$pdo->query("SELECT COUNT(*) FROM profile_fields WHERE is_active = 1")->fetchColumn();
$newUsers7d  = (int)$pdo->query(
    "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

$recentUsers = $pdo->query(
    "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$recentPages = $pdo->query(
    "SELECT id, slug, title, show_in_nav, updated_at FROM pages ORDER BY updated_at DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/layout_header.php';
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['icon' => 'fas fa-users',    'color' => 'primary', 'value' => $totalUsers,  'label' => 'Total Users',      'link' => BASE_URL . 'admin/users/'],
        ['icon' => 'fas fa-file-alt', 'color' => 'success', 'value' => $totalPages,  'label' => 'Total Pages',      'link' => BASE_URL . 'admin/pages/'],
        ['icon' => 'fas fa-list-alt', 'color' => 'info',    'value' => $totalFields, 'label' => 'Active Fields',    'link' => BASE_URL . 'admin/fields/'],
        ['icon' => 'fas fa-user-plus','color' => 'warning', 'value' => $newUsers7d,  'label' => 'New (7 days)',     'link' => BASE_URL . 'admin/users/'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="col-6 col-xl-3">
        <a href="<?= $s['link'] ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm stat-card stat-card--<?= $s['color'] ?>">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-<?= $s['color'] ?> bg-opacity-10 text-<?= $s['color'] ?>">
                        <i class="<?= $s['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= $s['value'] ?></div>
                        <div class="stat-label"><?= $s['label'] ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick actions -->
<div class="d-flex gap-2 flex-wrap mb-4">
    <a href="<?= BASE_URL ?>admin/users/create.php" class="btn btn-primary btn-sm">
        <i class="fas fa-user-plus me-1"></i> New User
    </a>
    <a href="<?= BASE_URL ?>admin/pages/create.php" class="btn btn-success btn-sm">
        <i class="fas fa-plus me-1"></i> New Page
    </a>
    <a href="<?= BASE_URL ?>admin/invitations/" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-envelope me-1"></i> Send Invite
    </a>
    <a href="<?= BASE_URL ?>admin/fields/create.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-list-alt me-1"></i> Add Profile Field
    </a>
</div>

<div class="row g-4">
    <!-- Recent users -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="fas fa-users me-2 text-primary"></i>Recent Users</span>
                <a href="<?= BASE_URL ?>admin/users/" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>User</th><th>Role</th><th>Joined</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($u['username']) ?></div>
                            <small class="text-muted"><?= e($u['email']) ?></small>
                        </td>
                        <td><span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                            <?= e($u['role']) ?></span></td>
                        <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>admin/users/edit.php?id=<?= (int)$u['id'] ?>"
                               class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2">
                                <i class="fas fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent pages -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="fas fa-file-alt me-2 text-success"></i>Recent Pages</span>
                <a href="<?= BASE_URL ?>admin/pages/" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Title</th><th>Slug</th><th>Nav</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentPages as $p): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($p['title']) ?></td>
                        <td><code class="small"><?= e($p['slug']) ?></code></td>
                        <td>
                            <?php if ($p['show_in_nav']): ?>
                                <span class="badge bg-success-subtle text-success">Visible</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>admin/pages/edit.php?id=<?= (int)$p['id'] ?>"
                               class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2">
                                <i class="fas fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
