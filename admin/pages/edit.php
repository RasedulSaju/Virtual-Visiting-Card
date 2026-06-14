<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$RESERVED = ['login', 'logout', 'register', 'forgot-password', 'reset-password',
             'edit-profile', 'admin', 'setup', '404'];

$pdo    = getDB();
$pageId = (int)($_GET['id'] ?? 0);

if (!$pageId) {
    flash('error', 'Invalid page ID.');
    redirect('admin/pages/');
}

$pageStmt = $pdo->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
$pageStmt->execute([$pageId]);
$cmsPage = $pageStmt->fetch();

if (!$cmsPage) {
    flash('error', 'Page not found.');
    redirect('admin/pages/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title      = trim($_POST['title']      ?? '');
    $slug       = slugify(trim($_POST['slug'] ?? ''));
    $content    = $_POST['content']         ?? '';
    $showInNav  = isset($_POST['show_in_nav']) ? 1 : 0;
    $navOrder   = (int)($_POST['nav_order'] ?? 0);
    $metaRobots = $_POST['meta_robots'] ?? 'index,follow';

    if (!in_array($metaRobots, ['index,follow','noindex,follow','index,nofollow','noindex,nofollow'], true)) {
        $metaRobots = 'index,follow';
    }

    if ($title === '') $errors[] = 'Title is required.';

    if ($slug === '') {
        $errors[] = 'Slug is required.';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = 'Slug may only contain lowercase letters, numbers and hyphens.';
    } elseif (in_array($slug, $RESERVED, true)) {
        $errors[] = '"' . $slug . '" is a reserved system slug.';
    }

    // Check slug collision with other pages
    if (empty($errors) && $slug !== $cmsPage['slug']) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = ? AND id != ?');
        $dup->execute([$slug, $pageId]);
        if ((int)$dup->fetchColumn() > 0) {
            $errors[] = 'Another page already uses this slug.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare(
                'UPDATE pages SET title=?, slug=?, content=?, show_in_nav=?, nav_order=?, meta_robots=?
                 WHERE id=?'
            )->execute([$title, $slug, $content, $showInNav, $navOrder, $metaRobots, $pageId]);

            flash('success', 'Page updated.');
            redirect('admin/pages/edit.php?id=' . $pageId);
        } catch (PDOException $ex) {
            error_log('[AdminEditPage] ' . $ex->getMessage());
            $errors[] = 'Database error.';
        }
    }

    // Rebuild display object from POST for re-render
    $cmsPage = array_merge($cmsPage, [
        'title' => $title, 'slug' => $slug, 'content' => $content,
        'show_in_nav' => $showInNav, 'nav_order' => $navOrder, 'meta_robots' => $metaRobots,
    ]);
}

$pageTitle = 'Edit Page: ' . $cmsPage['title'];
$activeNav = 'pages';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>admin/pages/" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div>
        <h2 class="h5 mb-0 fw-bold">Edit Page</h2>
        <small class="text-muted">
            <a href="<?= BASE_URL . e($cmsPage['slug']) ?>" target="_blank" class="text-muted">
                /<?= e($cmsPage['slug']) ?> <i class="fas fa-external-link-alt"></i>
            </a>
        </small>
    </div>
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
                               value="<?= e($cmsPage['title']) ?>" required>
                        <label class="form-label" for="title">Page Title *</label>
                    </div>
                    <div class="form-outline mb-4">
                        <input type="text" id="slug" name="slug" class="form-control font-monospace"
                               value="<?= e($cmsPage['slug']) ?>" required>
                        <label class="form-label" for="slug">URL Slug *</label>
                        <div class="form-text">
                            <code><?= BASE_URL . e($cmsPage['slug']) ?></code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="content">Content (HTML)</label>
                        <textarea id="content" name="content" class="form-control font-monospace"
                                  rows="18" style="font-size:.85rem;"><?= e($cmsPage['content']) ?></textarea>
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
                               <?= $cmsPage['show_in_nav'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_in_nav">Show in navigation</label>
                    </div>
                    <div class="form-outline">
                        <input type="number" id="nav_order" name="nav_order" class="form-control"
                               value="<?= (int)$cmsPage['nav_order'] ?>" min="0">
                        <label class="form-label" for="nav_order">Nav Order</label>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-search me-2 text-secondary"></i>SEO
                </div>
                <div class="card-body p-4">
                    <label class="form-label fw-semibold">Search Engine Visibility</label>
                    <select name="meta_robots" class="form-select">
                        <option value="index,follow"     <?= $cmsPage['meta_robots'] === 'index,follow'     ? 'selected' : '' ?>>Indexed (default)</option>
                        <option value="noindex,follow"   <?= $cmsPage['meta_robots'] === 'noindex,follow'   ? 'selected' : '' ?>>Noindex (links still followed)</option>
                        <option value="index,nofollow"   <?= $cmsPage['meta_robots'] === 'index,nofollow'   ? 'selected' : '' ?>>Indexed (links not followed)</option>
                        <option value="noindex,nofollow" <?= $cmsPage['meta_robots'] === 'noindex,nofollow' ? 'selected' : '' ?>>Hidden (noindex, nofollow)</option>
                    </select>
                    <div class="form-text">Controls this page's <code>&lt;meta name="robots"&gt;</code> tag and sitemap inclusion.</div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
                <a href="<?= BASE_URL . e($cmsPage['slug']) ?>" target="_blank"
                   class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-1"></i> View Page
                </a>
                <a href="<?= BASE_URL ?>admin/pages/" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
