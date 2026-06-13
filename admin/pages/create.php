<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

// Reserved slugs that cannot be used as page slugs
$RESERVED = ['login', 'logout', 'register', 'forgot-password', 'reset-password',
             'edit-profile', 'admin', 'setup', '404'];

$errors = [];
$old    = ['title' => '', 'slug' => '', 'content' => '', 'show_in_nav' => '0', 'nav_order' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old['title']      = trim($_POST['title']      ?? '');
    $old['slug']       = slugify(trim($_POST['slug'] ?? ''));
    $old['content']    = $_POST['content']         ?? '';
    $old['show_in_nav']= isset($_POST['show_in_nav']) ? '1' : '0';
    $old['nav_order']  = (string)(int)($_POST['nav_order'] ?? 0);

    if ($old['title'] === '') $errors[] = 'Title is required.';

    if ($old['slug'] === '') {
        $errors[] = 'Slug is required.';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $old['slug'])) {
        $errors[] = 'Slug may only contain lowercase letters, numbers and hyphens.';
    } elseif (in_array($old['slug'], $RESERVED, true)) {
        $errors[] = '"' . $old['slug'] . '" is a reserved system slug.';
    }

    if (empty($errors)) {
        $dup = getDB()->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
        $dup->execute([$old['slug']]);
        if ((int)$dup->fetchColumn() > 0) {
            $errors[] = 'A page with this slug already exists.';
        }
    }

    if (empty($errors)) {
        try {
            getDB()->prepare(
                'INSERT INTO pages (slug, title, content, show_in_nav, nav_order)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $old['slug'], $old['title'], $old['content'],
                (int)$old['show_in_nav'], (int)$old['nav_order'],
            ]);

            flash('success', 'Page "' . $old['title'] . '" created.');
            redirect('admin/pages/');
        } catch (PDOException $ex) {
            error_log('[AdminCreatePage] ' . $ex->getMessage());
            $errors[] = 'Database error.';
        }
    }
}

$pageTitle = 'Create Page';
$activeNav = 'pages';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>admin/pages/" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="h5 mb-0 fw-bold">Create New Page</h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0 ps-3">
    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>

<form method="POST" novalidate>
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">Content</div>
                <div class="card-body p-4">
                    <div class="form-outline mb-4">
                        <input type="text" id="title" name="title" class="form-control"
                               value="<?= e($old['title']) ?>" required>
                        <label class="form-label" for="title">Page Title *</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="text" id="slug" name="slug" class="form-control font-monospace"
                               value="<?= e($old['slug']) ?>" required>
                        <label class="form-label" for="slug">URL Slug * <small class="fw-normal">(e.g. about-us)</small></label>
                        <div class="form-text">
                            Preview: <code id="slugPreview"><?= BASE_URL . e($old['slug']) ?></code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="content">Content (HTML allowed)</label>
                        <textarea id="content" name="content" class="form-control font-monospace"
                                  rows="16" style="font-size:.85rem;"><?= e($old['content']) ?></textarea>
                        <div class="form-text">You can write plain HTML. This content is rendered on the public page.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">Navigation</div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="show_in_nav" name="show_in_nav"
                               <?= $old['show_in_nav'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_in_nav">
                            Show in navigation menu
                        </label>
                    </div>
                    <div class="form-outline">
                        <input type="number" id="nav_order" name="nav_order" class="form-control"
                               value="<?= e($old['nav_order']) ?>" min="0">
                        <label class="form-label" for="nav_order">Nav Order</label>
                        <div class="form-text">Lower = appears first.</div>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Create Page
                </button>
                <a href="<?= BASE_URL ?>admin/pages/" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    const titleEl  = document.getElementById('title');
    const slugEl   = document.getElementById('slug');
    const previewEl = document.getElementById('slugPreview');
    const base     = '<?= BASE_URL ?>';

    function toSlug(str) {
        return str.toLowerCase().trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    titleEl.addEventListener('input', () => {
        if (slugEl.dataset.manual !== '1') {
            slugEl.value = toSlug(titleEl.value);
            previewEl.textContent = base + slugEl.value;
        }
    });

    slugEl.addEventListener('input', () => {
        slugEl.dataset.manual = '1';
        slugEl.value = toSlug(slugEl.value);
        previewEl.textContent = base + slugEl.value;
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
