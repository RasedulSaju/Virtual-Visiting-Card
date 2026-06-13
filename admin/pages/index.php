<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$pages = getDB()->query(
    'SELECT id, slug, title, show_in_nav, nav_order, updated_at
     FROM pages ORDER BY show_in_nav DESC, nav_order ASC, title ASC'
)->fetchAll();

$pageTitle = 'Pages';
$activeNav = 'pages';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= BASE_URL ?>admin/pages/create.php" class="btn btn-success btn-sm">
        <i class="fas fa-plus me-1"></i> New Page
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Slug / URL</th>
                    <th>Nav</th>
                    <th>Order</th>
                    <th>Last Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pages)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No pages yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($pages as $p): ?>
            <tr>
                <td class="fw-semibold"><?= e($p['title']) ?></td>
                <td>
                    <a href="<?= BASE_URL . e($p['slug']) ?>" target="_blank"
                       class="text-decoration-none text-muted font-monospace small">
                        /<?= e($p['slug']) ?> <i class="fas fa-external-link-alt ms-1"></i>
                    </a>
                </td>
                <td>
                    <?php if ($p['show_in_nav']): ?>
                        <span class="badge bg-success-subtle text-success">
                            <i class="fas fa-eye me-1"></i>Visible
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary">
                            <i class="fas fa-eye-slash me-1"></i>Hidden
                        </span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small"><?= (int)$p['nav_order'] ?></td>
                <td class="small text-muted"><?= date('M j, Y H:i', strtotime($p['updated_at'])) ?></td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>admin/pages/edit.php?id=<?= (int)$p['id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" action="<?= BASE_URL ?>admin/pages/delete.php" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                data-confirm="Delete page '<?= e($p['title']) ?>'? This cannot be undone.">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
