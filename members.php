<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$pdo     = getDB();
$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$where  = "WHERE role = 'user'";
$params = [];
if ($search !== '') {
    $where   .= ' AND (username LIKE ? OR bio LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = (int)ceil($total / $perPage);

$listStmt = $pdo->prepare(
    "SELECT id, username, profile_image, bio, created_at
     FROM users $where
     ORDER BY created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$listStmt->execute($params);
$members = $listStmt->fetchAll();

$pageTitle = 'Members';
$ogData = [
    'type'        => 'website',
    'title'       => 'Members — ' . APP_NAME,
    'description' => 'Browse all members of ' . APP_NAME,
    'url'         => BASE_URL . 'members',
];

require __DIR__ . '/templates/layout_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-0">Members</h1>
        <p class="text-muted small mb-0"><?= $total ?> member<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Search members…" value="<?= e($search) ?>" style="width:200px;">
        <button class="btn btn-sm btn-outline-secondary" type="submit">
            <i class="fas fa-search"></i>
        </button>
        <?php if ($search): ?>
            <a href="<?= BASE_URL ?>members" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($members)): ?>
    <div class="card border-0 shadow-sm text-center py-5">
        <i class="fas fa-users fa-3x text-muted mb-3"></i>
        <p class="text-muted mb-0">
            <?= $search ? 'No members match your search.' : 'No members yet.' ?>
        </p>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ($members as $m): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= BASE_URL . e($m['username']) ?>"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm text-center member-card h-100">
                    <div class="member-card-banner"></div>
                    <div class="card-body pt-0 pb-3">
                        <div class="member-avatar-wrap">
                            <img src="<?= avatarUrl($m['profile_image']) ?>"
                                 class="rounded-circle border-3 border-white"
                                 width="72" height="72"
                                 style="object-fit:cover;border:3px solid white;"
                                 alt="<?= e($m['username']) ?>">
                        </div>
                        <h6 class="fw-bold mb-1 mt-1 text-dark"><?= e($m['username']) ?></h6>
                        <?php if ($m['bio']): ?>
                            <p class="text-muted small mb-0" style="
                                display:-webkit-box;-webkit-line-clamp:2;
                                -webkit-box-orient:vertical;overflow:hidden;">
                                <?= e(truncate($m['bio'], 80)) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted small mb-0 fst-italic">No bio yet</p>
                        <?php endif; ?>
                        <div class="text-muted" style="font-size:.72rem;margin-top:.4rem;">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= date('M Y', strtotime($m['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="d-flex justify-content-center">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?p=<?= $page-1 ?><?= $search ? '&q='.urlencode($search) : '' ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?p=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?p=<?= $page+1 ?><?= $search ? '&q='.urlencode($search) : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<style>
.member-card { transition: transform .15s, box-shadow .15s; overflow: hidden; }
.member-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(15,23,42,.12) !important; }
.member-card-banner {
    height: 48px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
}
.member-avatar-wrap {
    margin-top: -36px;
    margin-bottom: .25rem;
}
</style>

<?php require __DIR__ . '/templates/layout_footer.php'; ?>
