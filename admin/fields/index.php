<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$pdo    = getDB();
$fields = $pdo->query(
    'SELECT * FROM profile_fields ORDER BY sort_order ASC, id ASC'
)->fetchAll();

$pageTitle = 'Profile Fields';
$activeNav = 'fields';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="row g-4">
    <!-- Field List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="fas fa-list-alt me-2 text-primary"></i>
                    Custom Profile Fields
                </span>
                <span class="badge bg-secondary"><?= count($fields) ?> field<?= count($fields) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Label</th>
                            <th>Machine Name</th>
                            <th>Type</th>
                            <th>Icon</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($fields)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No fields yet. Add one →</td></tr>
                    <?php endif; ?>
                    <?php foreach ($fields as $f): ?>
                    <tr class="<?= !$f['is_active'] ? 'table-secondary opacity-60' : '' ?>">
                        <td>
                            <i class="<?= e($f['field_icon']) ?> me-2 text-muted"></i>
                            <strong><?= e($f['field_label']) ?></strong>
                        </td>
                        <td><code class="small"><?= e($f['field_name']) ?></code></td>
                        <td>
                            <span class="badge bg-info-subtle text-info"><?= e($f['field_type']) ?></span>
                        </td>
                        <td><code class="small"><?= e($f['field_icon']) ?></code></td>
                        <td class="text-muted small"><?= (int)$f['sort_order'] ?></td>
                        <td>
                            <?php if ($f['is_active']): ?>
                                <span class="badge bg-success-subtle text-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>admin/fields/edit.php?id=<?= (int)$f['id'] ?>"
                               class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>admin/fields/delete.php" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        data-confirm="Delete field '<?= e($f['field_label']) ?>'? All user values for this field will also be deleted.">
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
    </div>

    <!-- Quick-add sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom fw-semibold">
                <i class="fas fa-plus me-2 text-success"></i>Add New Field
            </div>
            <div class="card-body p-4">
                <a href="<?= BASE_URL ?>admin/fields/create.php" class="btn btn-success w-100 mb-3">
                    <i class="fas fa-plus me-1"></i> Open Field Creator
                </a>
                <hr>
                <p class="text-muted small mb-2"><strong>Tips:</strong></p>
                <ul class="small text-muted ps-3">
                    <li class="mb-1">Use <code>fab fa-twitter</code> for social icons (Font Awesome Brands).</li>
                    <li class="mb-1">Use <code>fas fa-globe</code> for web links.</li>
                    <li class="mb-1">Lower sort order = appears first on profiles.</li>
                    <li>Inactive fields are hidden on public profiles but values are preserved.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
