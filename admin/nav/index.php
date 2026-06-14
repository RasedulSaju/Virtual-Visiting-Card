<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    try {
        $allPages = $pdo->query('SELECT id FROM pages')->fetchAll(PDO::FETCH_COLUMN);
        $upd = $pdo->prepare(
            'UPDATE pages SET show_in_nav = ?, nav_order = ? WHERE id = ?'
        );

        foreach ($allPages as $pid) {
            $show  = isset($_POST['show_in_nav'][$pid]) ? 1 : 0;
            $order = (int)($_POST['nav_order'][$pid] ?? 99);
            $upd->execute([$show, $order, $pid]);
        }

        flash('success', 'Navigation menu updated.');
        redirect('admin/nav/');
    } catch (PDOException $ex) {
        error_log('[AdminNav] ' . $ex->getMessage());
        flash('error', 'Failed to save changes.');
    }
}

$pages = $pdo->query(
    'SELECT id, slug, title, show_in_nav, nav_order
     FROM pages ORDER BY nav_order ASC, title ASC'
)->fetchAll();

$pageTitle = 'Navigation Menu';
$activeNav = 'nav';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="row g-4">
    <!-- Editor -->
    <div class="col-lg-8">
        <form method="POST" id="navForm">
            <?= csrfField() ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        <i class="fas fa-bars me-2 text-primary"></i>Page Visibility &amp; Order
                    </span>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>

                <?php if (empty($pages)): ?>
                <div class="card-body text-center text-muted py-5">
                    No pages found. <a href="<?= BASE_URL ?>admin/pages/create.php">Create one first.</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="navTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Page</th>
                                <th>Slug</th>
                                <th class="text-center">Show in Nav</th>
                                <th style="width:100px;">Order</th>
                            </tr>
                        </thead>
                        <tbody id="navSortable">
                        <?php foreach ($pages as $p): ?>
                        <tr data-id="<?= (int)$p['id'] ?>">
                            <td class="text-muted drag-handle" style="cursor:grab;" title="Drag to reorder">
                                <i class="fas fa-grip-vertical"></i>
                            </td>
                            <td class="fw-semibold"><?= e($p['title']) ?></td>
                            <td>
                                <a href="<?= BASE_URL . e($p['slug']) ?>" target="_blank"
                                   class="text-muted text-decoration-none font-monospace small">
                                    /<?= e($p['slug']) ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input nav-toggle" type="checkbox" role="switch"
                                           name="show_in_nav[<?= (int)$p['id'] ?>]"
                                           id="nav_<?= (int)$p['id'] ?>"
                                           <?= $p['show_in_nav'] ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-center nav-order-input"
                                       name="nav_order[<?= (int)$p['id'] ?>]"
                                       value="<?= (int)$p['nav_order'] ?>"
                                       min="0" style="width:72px;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent text-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i> Save Navigation
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Live Preview -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom fw-semibold">
                <i class="fas fa-eye me-2 text-info"></i>Live Preview
            </div>
            <div class="card-body p-3">
                <p class="text-muted small mb-2">Visible nav items (updates on toggle):</p>
                <nav class="nav flex-column" id="navPreview">
                    <?php foreach ($pages as $p): ?>
                        <?php if ($p['show_in_nav']): ?>
                        <a class="nav-link py-1 ps-2 text-primary preview-item" href="#"
                           data-id="<?= (int)$p['id'] ?>" data-title="<?= e($p['title']) ?>">
                            <i class="fas fa-file-alt me-2 text-muted small"></i><?= e($p['title']) ?>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <p class="text-muted small mt-2 mb-0" id="noNavMsg"
                   <?= array_sum(array_column($pages, 'show_in_nav')) > 0 ? 'style="display:none"' : '' ?>>
                    No pages are set to show in nav.
                </p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-transparent border-bottom fw-semibold">
                <i class="fas fa-plus me-2 text-success"></i>Add Pages
            </div>
            <div class="card-body p-3">
                <a href="<?= BASE_URL ?>admin/pages/create.php" class="btn btn-success btn-sm w-100 mb-2">
                    <i class="fas fa-plus me-1"></i> Create New Page
                </a>
                <a href="<?= BASE_URL ?>admin/pages/" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-list me-1"></i> Manage All Pages
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Live preview toggle
    document.querySelectorAll('.nav-toggle').forEach(chk => {
        chk.addEventListener('change', () => {
            const id    = chk.name.match(/\[(\d+)\]/)[1];
            const title = chk.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
            const prev  = document.getElementById('navPreview');
            const noMsg = document.getElementById('noNavMsg');

            if (chk.checked) {
                const a = document.createElement('a');
                a.className     = 'nav-link py-1 ps-2 text-primary preview-item';
                a.href          = '#';
                a.dataset.id    = id;
                a.dataset.title = title;
                a.innerHTML     = '<i class="fas fa-file-alt me-2 text-muted small"></i>' + title;
                prev.appendChild(a);
            } else {
                prev.querySelector('[data-id="' + id + '"]')?.remove();
            }

            noMsg.style.display = prev.querySelectorAll('.preview-item').length ? 'none' : '';
        });
    });

    // Auto-update order inputs when rows are reordered (simple drag via mouse events)
    const tbody = document.getElementById('navSortable');
    let dragRow = null;

    tbody.querySelectorAll('tr').forEach(row => {
        row.setAttribute('draggable', true);

        row.addEventListener('dragstart', () => {
            dragRow = row;
            row.style.opacity = '0.5';
        });
        row.addEventListener('dragend', () => {
            row.style.opacity = '';
            dragRow = null;
            // Renumber order inputs sequentially
            tbody.querySelectorAll('tr').forEach((r, i) => {
                const inp = r.querySelector('.nav-order-input');
                if (inp) inp.value = i;
            });
        });
        row.addEventListener('dragover', e => {
            e.preventDefault();
            if (dragRow && dragRow !== row) {
                const rect = row.getBoundingClientRect();
                const mid  = rect.top + rect.height / 2;
                if (e.clientY < mid) {
                    tbody.insertBefore(dragRow, row);
                } else {
                    tbody.insertBefore(dragRow, row.nextSibling);
                }
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
