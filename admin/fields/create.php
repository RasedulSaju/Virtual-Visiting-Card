<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$errors = [];
$old    = [
    'field_name'  => '',
    'field_label' => '',
    'field_type'  => 'text',
    'field_icon'  => 'fas fa-tag',
    'sort_order'  => '0',
    'is_active'   => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old['field_name']  = trim(strtolower(preg_replace('/\s+/', '_', $_POST['field_name']  ?? '')));
    $old['field_label'] = trim($_POST['field_label'] ?? '');
    $old['field_type']  = $_POST['field_type']       ?? 'text';
    $old['field_icon']  = trim($_POST['field_icon']  ?? 'fas fa-tag');
    $old['sort_order']  = (string)(int)($_POST['sort_order'] ?? 0);
    $old['is_active']   = isset($_POST['is_active']) ? '1' : '0';

    if ($old['field_name'] === '') {
        $errors[] = 'Machine name is required.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $old['field_name'])) {
        $errors[] = 'Machine name: lowercase letters, numbers and underscores only.';
    }

    if ($old['field_label'] === '') $errors[] = 'Label is required.';

    if (!in_array($old['field_type'], ['text', 'url', 'textarea'], true)) {
        $errors[] = 'Invalid field type.';
    }

    if ($old['field_icon'] === '') $old['field_icon'] = 'fas fa-tag';

    if (empty($errors)) {
        $dup = getDB()->prepare('SELECT COUNT(*) FROM profile_fields WHERE field_name = ?');
        $dup->execute([$old['field_name']]);
        if ((int)$dup->fetchColumn() > 0) {
            $errors[] = 'A field with this machine name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            getDB()->prepare(
                'INSERT INTO profile_fields (field_name, field_label, field_type, field_icon, sort_order, is_active)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $old['field_name'], $old['field_label'], $old['field_type'],
                $old['field_icon'], (int)$old['sort_order'], (int)$old['is_active'],
            ]);

            flash('success', 'Field "' . $old['field_label'] . '" created.');
            redirect('admin/fields/');
        } catch (PDOException $ex) {
            error_log('[AdminCreateField] ' . $ex->getMessage());
            $errors[] = 'Database error.';
        }
    }
}

$pageTitle = 'Create Profile Field';
$activeNav = 'fields';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>admin/fields/" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h2 class="h5 mb-0 fw-bold">Create Profile Field</h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0 ps-3">
    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <form method="POST" novalidate>
            <?= csrfField() ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">Field Definition</div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="field_label" name="field_label" class="form-control"
                                       value="<?= e($old['field_label']) ?>" required>
                                <label class="form-label" for="field_label">Display Label *</label>
                                <div class="form-text">Shown on user profiles, e.g. "Twitter Handle"</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="field_name" name="field_name" class="form-control font-monospace"
                                       value="<?= e($old['field_name']) ?>" required
                                       pattern="[a-z0-9_]+" placeholder="e.g. twitter_handle">
                                <label class="form-label" for="field_name">Machine Name *</label>
                                <div class="form-text">Unique key — lowercase, underscores only</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Field Type</label>
                            <select name="field_type" class="form-select">
                                <option value="text"     <?= $old['field_type'] === 'text'     ? 'selected' : '' ?>>Text (single line)</option>
                                <option value="url"      <?= $old['field_type'] === 'url'      ? 'selected' : '' ?>>URL (validated link)</option>
                                <option value="textarea" <?= $old['field_type'] === 'textarea' ? 'selected' : '' ?>>Textarea (multiline)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="number" id="sort_order" name="sort_order" class="form-control"
                                       value="<?= e($old['sort_order']) ?>" min="0">
                                <label class="form-label" for="sort_order">Sort Order</label>
                                <div class="form-text">Lower = appears first on profile</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Font Awesome Icon</label>
                            <div class="input-group">
                                <span class="input-group-text" id="iconPreviewWrap">
                                    <i id="iconPreview" class="<?= e($old['field_icon']) ?>"></i>
                                </span>
                                <input type="text" name="field_icon" id="field_icon" class="form-control font-monospace"
                                       value="<?= e($old['field_icon']) ?>"
                                       placeholder="fas fa-tag">
                            </div>
                            <div class="form-text">
                                Use any
                                <a href="https://fontawesome.com/icons" target="_blank" rel="noopener">Font Awesome</a>
                                class, e.g. <code>fab fa-twitter</code>, <code>fas fa-globe</code>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_active" name="is_active"
                                       <?= $old['is_active'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Active (visible on public profiles)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Create Field
                </button>
                <a href="<?= BASE_URL ?>admin/fields/" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom fw-semibold">
                <i class="fas fa-eye me-2 text-info"></i>Preview
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">How this field appears on a user profile:</p>
                <div class="border rounded p-3 bg-light">
                    <dt class="text-muted small fw-normal mb-1">
                        <i id="previewIcon" class="<?= e($old['field_icon']) ?> me-1"></i>
                        <span id="previewLabel"><?= e($old['field_label'] ?: 'Field Label') ?></span>
                    </dt>
                    <dd class="fw-semibold mb-0">Sample value</dd>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const labelEl   = document.getElementById('field_label');
    const nameEl    = document.getElementById('field_name');
    const iconEl    = document.getElementById('field_icon');
    const prevIcon  = document.getElementById('iconPreview');
    const prevIcon2 = document.getElementById('previewIcon');
    const prevLabel = document.getElementById('previewLabel');

    labelEl.addEventListener('input', () => {
        prevLabel.textContent = labelEl.value || 'Field Label';
        if (nameEl.dataset.manual !== '1') {
            nameEl.value = labelEl.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
        }
    });

    nameEl.addEventListener('input', () => nameEl.dataset.manual = '1');

    iconEl.addEventListener('input', () => {
        const cls = iconEl.value.trim() || 'fas fa-tag';
        prevIcon.className  = cls;
        prevIcon2.className = cls + ' me-1';
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
