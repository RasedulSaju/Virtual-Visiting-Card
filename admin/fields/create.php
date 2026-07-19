<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$errors = [];
$old    = [
    'field_name'      => '',
    'field_label'     => '',
    'field_type'      => 'text',
    'field_icon'      => 'fas fa-tag',
    'field_options'   => '',
    'edit_permission' => 'user',
    'lock_after_set'  => '0',
    'is_repeatable'   => '0',
    'group_key'       => '',
    'group_label'     => '',
    'sort_order'      => '0',
    'is_active'       => '1',
    'is_public'       => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old['field_name']      = trim(strtolower(preg_replace('/\s+/', '_', $_POST['field_name']  ?? '')));
    $old['field_label']     = trim($_POST['field_label'] ?? '');
    $old['field_type']      = $_POST['field_type']       ?? 'text';
    $old['field_icon']      = trim($_POST['field_icon']  ?? 'fas fa-tag');
    $old['field_options']   = trim($_POST['field_options'] ?? '');
    $old['edit_permission'] = $_POST['edit_permission']  ?? 'user';
    $old['lock_after_set']  = isset($_POST['lock_after_set']) ? '1' : '0';
    $old['is_repeatable']   = isset($_POST['is_repeatable']) ? '1' : '0';
    $old['group_key']       = trim(strtolower(preg_replace('/\s+/', '_', $_POST['group_key'] ?? '')));
    $old['group_label']     = trim($_POST['group_label'] ?? '');
    $old['sort_order']      = (string)(int)($_POST['sort_order'] ?? 0);
    $old['is_active']       = isset($_POST['is_active']) ? '1' : '0';
    $old['is_public']       = isset($_POST['is_public']) ? '1' : '0';

    if ($old['field_name'] === '') {
        $errors[] = 'Machine name is required.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $old['field_name'])) {
        $errors[] = 'Machine name: lowercase letters, numbers and underscores only.';
    }

    if ($old['field_label'] === '') $errors[] = 'Label is required.';

    if (!in_array($old['field_type'], ['text', 'url', 'textarea', 'date', 'select'], true)) {
        $errors[] = 'Invalid field type.';
    }

    if ($old['field_type'] === 'select' && trim($old['field_options']) === '') {
        $errors[] = 'Dropdown fields need at least one option (one per line).';
    }

    if (!in_array($old['edit_permission'], ['user', 'admin'], true)) {
        $old['edit_permission'] = 'user';
    }

    // A grouped field cannot also be independently repeatable — the group itself repeats
    if ($old['group_key'] !== '') {
        $old['is_repeatable'] = '0';
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
                'INSERT INTO profile_fields
                    (field_name, field_label, field_type, field_icon, field_options,
                     edit_permission, lock_after_set, is_repeatable, group_key, group_label,
                     sort_order, is_active, is_public)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $old['field_name'], $old['field_label'], $old['field_type'],
                $old['field_icon'], $old['field_options'] ?: null,
                $old['edit_permission'], (int)$old['lock_after_set'], (int)$old['is_repeatable'],
                $old['group_key'] ?: null, $old['group_label'] ?: null,
                (int)$old['sort_order'], (int)$old['is_active'], (int)$old['is_public'],
            ]);

            flash('success', 'Field "' . $old['field_label'] . '" created.');
            redirect('admin/fields/');
        } catch (PDOException $ex) {
            error_log('[AdminCreateField] ' . $ex->getMessage());
            $errors[] = 'Database error.';
        }
    }
}

// Existing group keys, for the datalist suggestion
$existingGroups = getDB()->query(
    "SELECT DISTINCT group_key, group_label FROM profile_fields WHERE group_key IS NOT NULL"
)->fetchAll();

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
                                <div class="form-text">e.g. "Blood Group", "Position"</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="field_name" name="field_name" class="form-control font-monospace"
                                       value="<?= e($old['field_name']) ?>" required
                                       pattern="[a-z0-9_]+" placeholder="e.g. blood_group">
                                <label class="form-label" for="field_name">Machine Name *</label>
                                <div class="form-text">Unique key — lowercase, underscores only</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Field Type</label>
                            <select name="field_type" id="field_type" class="form-select">
                                <option value="text"     <?= $old['field_type'] === 'text'     ? 'selected' : '' ?>>Text (single line)</option>
                                <option value="url"      <?= $old['field_type'] === 'url'      ? 'selected' : '' ?>>URL (validated link)</option>
                                <option value="textarea" <?= $old['field_type'] === 'textarea' ? 'selected' : '' ?>>Textarea (multiline)</option>
                                <option value="date"     <?= $old['field_type'] === 'date'     ? 'selected' : '' ?>>Date</option>
                                <option value="select"   <?= $old['field_type'] === 'select'   ? 'selected' : '' ?>>Dropdown (fixed choices)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Who Sets the Value?</label>
                            <select name="edit_permission" class="form-select">
                                <option value="user"  <?= $old['edit_permission'] === 'user'  ? 'selected' : '' ?>>Member (self-service)</option>
                                <option value="admin" <?= $old['edit_permission'] === 'admin' ? 'selected' : '' ?>>Admin only</option>
                            </select>
                        </div>

                        <div class="col-12" id="optionsWrap" style="<?= $old['field_type'] === 'select' ? '' : 'display:none;' ?>">
                            <div class="form-outline">
                                <textarea id="field_options" name="field_options" class="form-control" rows="4"
                                          placeholder="A+&#10;A-&#10;B+&#10;B-&#10;O+&#10;O-&#10;AB+&#10;AB-"><?= e($old['field_options']) ?></textarea>
                                <label class="form-label" for="field_options">Dropdown Options *</label>
                                <div class="form-text">One option per line — this becomes the dropdown list, e.g. for Blood Group.</div>
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
                                class, e.g. <code>fab fa-twitter</code>, <code>fas fa-tint</code>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="number" id="sort_order" name="sort_order" class="form-control"
                                       value="<?= e($old['sort_order']) ?>" min="0">
                                <label class="form-label" for="sort_order">Sort Order</label>
                                <div class="form-text">Lower = appears first on profile</div>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_active" name="is_active"
                                       <?= $old['is_active'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active (collected from users)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_public" name="is_public"
                                       <?= $old['is_public'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_public">
                                    Public <small class="text-muted">(visible to visitors)</small>
                                </label>
                            </div>
                            <div class="form-text">Off = only the profile owner and admins can see it.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="lock_after_set" name="lock_after_set"
                                       <?= $old['lock_after_set'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="lock_after_set">
                                    Lock once set <small class="text-muted">(member can't edit again)</small>
                                </label>
                            </div>
                            <div class="form-text">Good for Date of Birth, Date of Joining — set once, then fixed. Admins can always override.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-layer-group me-2 text-primary"></i>Multiple Values
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Use this for people who have more than one of something —
                        e.g. two phone numbers, or several Position + Company pairs.
                    </p>
                    <div class="form-check form-switch mb-3" id="repeatableWrap">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="is_repeatable" name="is_repeatable"
                               <?= $old['is_repeatable'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_repeatable">
                            Allow multiple values <small class="text-muted">(e.g. Phone Number, Cell Phone)</small>
                        </label>
                    </div>

                    <hr class="my-3">

                    <p class="text-muted small mb-2">
                        <strong>Group with other fields</strong> — fields sharing the same Group Key
                        repeat together as a set. Use this for "Position" + "Company" pairs.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="group_key" name="group_key" class="form-control font-monospace"
                                       value="<?= e($old['group_key']) ?>" list="existingGroups"
                                       placeholder="e.g. employment">
                                <label class="form-label" for="group_key">Group Key</label>
                                <div class="form-text">Leave empty if this field doesn't repeat as part of a group.</div>
                            </div>
                            <datalist id="existingGroups">
                                <?php foreach ($existingGroups as $g): ?>
                                <option value="<?= e($g['group_key']) ?>"><?= e($g['group_label']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="group_label" name="group_label" class="form-control"
                                       value="<?= e($old['group_label']) ?>" placeholder="e.g. Employment">
                                <label class="form-label" for="group_label">Group Heading</label>
                                <div class="form-text">Shown above each repeated set on the profile.</div>
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
    const typeEl    = document.getElementById('field_type');
    const optsWrap  = document.getElementById('optionsWrap');
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

    typeEl.addEventListener('change', () => {
        optsWrap.style.display = typeEl.value === 'select' ? '' : 'none';
    });

    // Repeatable and Group Key are mutually exclusive
    const repeatable = document.getElementById('is_repeatable');
    const groupKey   = document.getElementById('group_key');
    groupKey.addEventListener('input', () => {
        if (groupKey.value.trim() !== '') {
            repeatable.checked = false;
            repeatable.disabled = true;
        } else {
            repeatable.disabled = false;
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
