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
                        <label class="form-label fw-semibold d-flex align-items-center justify-content-between" for="content">
                            <span>Content <small class="fw-normal text-muted">(HTML supported)</small></span>
                            <span class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<strong>|</strong>" title="Bold"><i class="fas fa-bold"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<em>|</em>" title="Italic"><i class="fas fa-italic"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<a href=&quot;&quot;>|</a>" title="Link"><i class="fas fa-link"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<h3>|</h3>" title="Heading"><i class="fas fa-heading"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<ul>&#10;  <li>|</li>&#10;</ul>" title="List"><i class="fas fa-list"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="uploadImageBtn" title="Upload & Insert Image">
                                    <i class="fas fa-image me-1"></i>Image
                                </button>
                            </span>
                        </label>
                        <div id="imageUploadPanel" class="card border mb-2 p-3 d-none">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <input type="file" id="pageImageFile" accept="image/jpeg,image/png,image/gif"
                                       class="form-control form-control-sm" style="max-width:260px;">
                                <button type="button" class="btn btn-sm btn-primary" id="doUploadImage">
                                    <i class="fas fa-upload me-1"></i>Upload &amp; Insert
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="closeImagePanel">
                                    <i class="fas fa-times"></i>
                                </button>
                                <span id="uploadStatus" class="small text-muted"></span>
                            </div>
                            <div id="uploadedImagesList" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>
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

<script>
(function () {
    const ta = document.getElementById('content');

    document.querySelectorAll('.insert-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tag   = btn.dataset.tag.replace('&quot;', '"');
            const parts = tag.split('|');
            const start = ta.selectionStart, end = ta.selectionEnd;
            const sel   = ta.value.substring(start, end);
            ta.value = ta.value.substring(0, start) + (parts[0]||'') + sel + (parts[1]||'') + ta.value.substring(end);
            ta.focus();
            ta.setSelectionRange(start + (parts[0]||'').length, start + (parts[0]||'').length + sel.length);
        });
    });

    const panel    = document.getElementById('imageUploadPanel');
    const status   = document.getElementById('uploadStatus');
    const imgList  = document.getElementById('uploadedImagesList');

    document.getElementById('uploadImageBtn').addEventListener('click', () => panel.classList.toggle('d-none'));
    document.getElementById('closeImagePanel').addEventListener('click', () => panel.classList.add('d-none'));

    function insertImageTag(url) {
        const tag = `<img src="${url}" alt="" style="max-width:100%;height:auto;border-radius:6px;">`;
        const pos = ta.selectionStart;
        ta.value  = ta.value.substring(0, pos) + tag + ta.value.substring(pos);
        ta.focus();
    }

    function addThumb(url) {
        const img = document.createElement('img');
        img.src   = url;
        img.title = 'Click to insert';
        img.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid #e2e8f0;';
        img.addEventListener('click', () => insertImageTag(url));
        imgList.appendChild(img);
    }

    document.getElementById('doUploadImage').addEventListener('click', async function () {
        const file = document.getElementById('pageImageFile').files[0];
        if (!file) { status.textContent = 'Please select a file.'; return; }
        status.textContent = 'Uploading…';
        this.disabled = true;
        const fd = new FormData();
        fd.append('image', file);
        try {
            const resp = await fetch('<?= BASE_URL ?>admin/media/upload.php', { method:'POST', body:fd });
            const data = await resp.json();
            if (data.error) { status.textContent = '✗ ' + data.error; }
            else { insertImageTag(data.url); addThumb(data.url); status.textContent = '✓ Inserted (' + data.size + ')'; }
        } catch (e) {
            status.textContent = '✗ Upload failed.';
        } finally { this.disabled = false; }
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
