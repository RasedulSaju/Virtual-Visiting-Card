<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

// Reserved slugs that cannot be used as page slugs
$RESERVED = ['login', 'logout', 'register', 'forgot-password', 'reset-password',
             'edit-profile', 'admin', 'setup', '404'];

$errors = [];
$old    = ['title' => '', 'slug' => '', 'content' => '', 'show_in_nav' => '0', 'nav_order' => '0', 'meta_robots' => 'index,follow'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old['title']      = trim($_POST['title']      ?? '');
    $old['slug']       = slugify(trim($_POST['slug'] ?? ''));
    $old['content']    = $_POST['content']         ?? '';
    $old['show_in_nav']= isset($_POST['show_in_nav']) ? '1' : '0';
    $old['nav_order']  = (string)(int)($_POST['nav_order'] ?? 0);
    $old['meta_robots']= $_POST['meta_robots'] ?? 'index,follow';

    if (!in_array($old['meta_robots'], ['index,follow','noindex,follow','index,nofollow','noindex,nofollow'], true)) {
        $old['meta_robots'] = 'index,follow';
    }

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
                'INSERT INTO pages (slug, title, content, show_in_nav, nav_order, meta_robots)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $old['slug'], $old['title'], $old['content'],
                (int)$old['show_in_nav'], (int)$old['nav_order'], $old['meta_robots'],
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
                        <label class="form-label fw-semibold d-flex align-items-center justify-content-between" for="content">
                            <span>Content <small class="fw-normal text-muted">(HTML supported)</small></span>
                            <span class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<strong>|</strong>" title="Bold">
                                    <i class="fas fa-bold"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<em>|</em>" title="Italic">
                                    <i class="fas fa-italic"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<a href=&quot;&quot;>|</a>" title="Link">
                                    <i class="fas fa-link"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<h3>|</h3>" title="Heading">
                                    <i class="fas fa-heading"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary insert-btn" data-tag="<ul>&#10;  <li>|</li>&#10;</ul>" title="List">
                                    <i class="fas fa-list"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="uploadImageBtn" title="Upload & Insert Image">
                                    <i class="fas fa-image me-1"></i>Image
                                </button>
                            </span>
                        </label>

                        <!-- Image upload panel -->
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
                            <!-- Previously uploaded images -->
                            <div id="uploadedImagesList" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>

                        <textarea id="content" name="content" class="form-control font-monospace"
                                  rows="16" style="font-size:.85rem;"><?= e($old['content']) ?></textarea>
                        <div class="form-text">Write HTML directly. Use the toolbar buttons for quick inserts.</div>
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
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-search me-2 text-secondary"></i>SEO
                </div>
                <div class="card-body p-4">
                    <label class="form-label fw-semibold">Search Engine Visibility</label>
                    <select name="meta_robots" class="form-select">
                        <option value="index,follow"     <?= $old['meta_robots'] === 'index,follow'     ? 'selected' : '' ?>>Indexed (default)</option>
                        <option value="noindex,follow"   <?= $old['meta_robots'] === 'noindex,follow'   ? 'selected' : '' ?>>Noindex (links still followed)</option>
                        <option value="index,nofollow"   <?= $old['meta_robots'] === 'index,nofollow'   ? 'selected' : '' ?>>Indexed (links not followed)</option>
                        <option value="noindex,nofollow" <?= $old['meta_robots'] === 'noindex,nofollow' ? 'selected' : '' ?>>Hidden (noindex, nofollow)</option>
                    </select>
                    <div class="form-text">Controls this page's <code>&lt;meta name="robots"&gt;</code> tag and sitemap inclusion.</div>
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

<script>
// ── Page editor toolbar ───────────────────────────────────────
(function () {
    const ta = document.getElementById('content');

    // Insert tag around selection
    document.querySelectorAll('.insert-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tag   = btn.dataset.tag.replace('&quot;', '"');
            const parts = tag.split('|');
            const start = ta.selectionStart;
            const end   = ta.selectionEnd;
            const sel   = ta.value.substring(start, end);
            const before = parts[0] || '';
            const after  = parts[1] || '';
            ta.value = ta.value.substring(0, start) + before + sel + after + ta.value.substring(end);
            ta.focus();
            ta.setSelectionRange(start + before.length, start + before.length + sel.length);
        });
    });

    // Image upload panel toggle
    const panel     = document.getElementById('imageUploadPanel');
    const uploadBtn = document.getElementById('uploadImageBtn');
    const closeBtn  = document.getElementById('closeImagePanel');
    const doUpload  = document.getElementById('doUploadImage');
    const fileInput = document.getElementById('pageImageFile');
    const status    = document.getElementById('uploadStatus');
    const imgList   = document.getElementById('uploadedImagesList');

    uploadBtn.addEventListener('click', () => panel.classList.toggle('d-none'));
    closeBtn.addEventListener('click',  () => panel.classList.add('d-none'));

    function insertImageTag(url) {
        const tag = `<img src="${url}" alt="" style="max-width:100%;height:auto;border-radius:6px;">`;
        const pos = ta.selectionStart;
        ta.value  = ta.value.substring(0, pos) + tag + ta.value.substring(pos);
        ta.focus();
        ta.setSelectionRange(pos + tag.length, pos + tag.length);
    }

    function addThumb(url) {
        const img = document.createElement('img');
        img.src   = url;
        img.title = 'Click to insert';
        img.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid #e2e8f0;';
        img.addEventListener('click', () => insertImageTag(url));
        imgList.appendChild(img);
    }

    doUpload.addEventListener('click', async () => {
        const file = fileInput.files[0];
        if (!file) { status.textContent = 'Please select a file.'; return; }

        status.textContent = 'Uploading…';
        doUpload.disabled  = true;

        const fd = new FormData();
        fd.append('image', file);

        try {
            const resp = await fetch('<?= BASE_URL ?>admin/media/upload.php', {
                method: 'POST', body: fd
            });
            const data = await resp.json();

            if (data.error) {
                status.textContent = '✗ ' + data.error;
            } else {
                insertImageTag(data.url);
                addThumb(data.url);
                status.textContent = '✓ Inserted (' + data.size + ')';
                fileInput.value = '';
            }
        } catch (e) {
            status.textContent = '✗ Upload failed.';
        } finally {
            doUpload.disabled = false;
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
