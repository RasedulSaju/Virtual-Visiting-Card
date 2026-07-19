<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$pdo     = getDB();
$fieldId = (int)($_GET['id'] ?? 0);

if (!$fieldId) {
    flash('error', 'Invalid field ID.');
    redirect('admin/fields/');
}

$fStmt = $pdo->prepare('SELECT * FROM profile_fields WHERE id = ? LIMIT 1');
$fStmt->execute([$fieldId]);
$field = $fStmt->fetch();

if (!$field) {
    flash('error', 'Field not found.');
    redirect('admin/fields/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $fieldName      = trim(strtolower(preg_replace('/\s+/', '_', $_POST['field_name']  ?? '')));
    $fieldLabel     = trim($_POST['field_label'] ?? '');
    $fieldType      = $_POST['field_type']       ?? 'text';
    $fieldIcon      = trim($_POST['field_icon']  ?? 'fas fa-tag') ?: 'fas fa-tag';
    $fieldOptions   = trim($_POST['field_options'] ?? '');
    $editPermission = $_POST['edit_permission']  ?? 'user';
    $lockAfterSet   = isset($_POST['lock_after_set']) ? 1 : 0;
    $isRepeatable   = isset($_POST['is_repeatable']) ? 1 : 0;
    $groupKey       = trim(strtolower(preg_replace('/\s+/', '_', $_POST['group_key'] ?? '')));
    $groupLabel     = trim($_POST['group_label'] ?? '');
    $sortOrder      = (int)($_POST['sort_order'] ?? 0);
    $isActive       = isset($_POST['is_active']) ? 1 : 0;
    $isPublic       = isset($_POST['is_public']) ? 1 : 0;

    if ($fieldName === '') {
        $errors[] = 'Machine name is required.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $fieldName)) {
        $errors[] = 'Machine name: lowercase letters, numbers and underscores only.';
    }

    if ($fieldLabel === '') $errors[] = 'Label is required.';

    if (!in_array($fieldType, ['text', 'url', 'textarea', 'date', 'select'], true)) {
        $errors[] = 'Invalid field type.';
    }

    if ($fieldType === 'select' && $fieldOptions === '') {
        $errors[] = 'Dropdown fields need at least one option (one per line).';
    }

    if (!in_array($editPermission, ['user', 'admin'], true)) {
        $editPermission = 'user';
    }

    if ($groupKey !== '') {
        $isRepeatable = 0;
    }

    // Unique name check against other fields
    if (empty($errors) && $fieldName !== $field['field_name']) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM profile_fields WHERE field_name = ? AND id != ?');
        $dup->execute([$fieldName, $fieldId]);
        if ((int)$dup->fetchColumn() > 0) {
            $errors[] = 'Another field already uses this machine name.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare(
                'UPDATE profile_fields
                 SET field_name=?, field_label=?, field_type=?, field_icon=?, field_options=?,
                     edit_permission=?, lock_after_set=?, is_repeatable=?, group_key=?, group_label=?,
                     sort_order=?, is_active=?, is_public=?
                 WHERE id=?'
            )->execute([
                $fieldName, $fieldLabel, $fieldType, $fieldIcon, $fieldOptions ?: null,
                $editPermission, $lockAfterSet, $isRepeatable, $groupKey ?: null, $groupLabel ?: null,
                $sortOrder, $isActive, $isPublic, $fieldId,
            ]);

            flash('success', 'Field updated.');
            redirect('admin/fields/edit.php?id=' . $fieldId);
        } catch (PDOException $ex) {
            error_log('[AdminEditField] ' . $ex->getMessage());
            $errors[] = 'Database error.';
        }
    }

    // Rebuild for re-render
    $field = array_merge($field, [
        'field_name'      => $fieldName,  'field_label'    => $fieldLabel,
        'field_type'      => $fieldType,  'field_icon'     => $fieldIcon,
        'field_options'   => $fieldOptions,
        'edit_permission' => $editPermission, 'lock_after_set' => $lockAfterSet,
        'is_repeatable'   => $isRepeatable, 'group_key'    => $groupKey,
        'group_label'     => $groupLabel,
        'sort_order'      => $sortOrder,  'is_active'      => $isActive,
        'is_public'       => $isPublic,
    ]);
}

// Count how many users have a value for this field
$usageStmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_field_values WHERE field_id = ? AND field_value != ''");
$usageStmt->execute([$fieldId]);
$usageCount = (int)$usageStmt->fetchColumn();

$existingGroups = $pdo->prepare(
    "SELECT DISTINCT group_key, group_label FROM profile_fields WHERE group_key IS NOT NULL AND id != ?"
);
$existingGroups->execute([$fieldId]);
$existingGroups = $existingGroups->fetchAll();

$pageTitle = 'Edit Field: ' . $field['field_label'];
$activeNav = 'fields';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>admin/fields/" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div>
        <h2 class="h5 mb-0 fw-bold">Edit Field</h2>
        <small class="text-muted"><?= $usageCount ?> user<?= $usageCount !== 1 ? 's have' : ' has' ?> a value for this field</small>
    </div>
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
                                       value="<?= e($field['field_label']) ?>" required>
                                <label class="form-label" for="field_label">Display Label *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="field_name" name="field_name"
                                       class="form-control font-monospace"
                                       value="<?= e($field['field_name']) ?>" required>
                                <label class="form-label" for="field_name">Machine Name *</label>
                            </div>
                            <?php if ($usageCount > 0): ?>
                            <div class="alert alert-warning py-1 px-2 mt-1 mb-0 small">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Changing the machine name doesn't affect stored user data.
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Field Type</label>
                            <select name="field_type" id="field_type" class="form-select">
                                <option value="text"     <?= $field['field_type'] === 'text'     ? 'selected' : '' ?>>Text</option>
                                <option value="url"      <?= $field['field_type'] === 'url'      ? 'selected' : '' ?>>URL</option>
                                <option value="textarea" <?= $field['field_type'] === 'textarea' ? 'selected' : '' ?>>Textarea</option>
                                <option value="date"     <?= $field['field_type'] === 'date'     ? 'selected' : '' ?>>Date</option>
                                <option value="select"   <?= $field['field_type'] === 'select'   ? 'selected' : '' ?>>Dropdown</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Who Sets the Value?</label>
                            <select name="edit_permission" class="form-select">
                                <option value="user"  <?= $field['edit_permission'] === 'user'  ? 'selected' : '' ?>>Member (self-service)</option>
                                <option value="admin" <?= $field['edit_permission'] === 'admin' ? 'selected' : '' ?>>Admin only</option>
                            </select>
                            <div class="form-text">Admin-only fields show read-only to the member.</div>
                        </div>

                        <div class="col-12" id="optionsWrap" style="<?= $field['field_type'] === 'select' ? '' : 'display:none;' ?>">
                            <div class="form-outline">
                                <textarea id="field_options" name="field_options" class="form-control" rows="4"
                                          placeholder="A+&#10;A-&#10;B+&#10;B-"><?= e($field['field_options'] ?? '') ?></textarea>
                                <label class="form-label" for="field_options">Dropdown Options *</label>
                                <div class="form-text">One option per line.</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Font Awesome Icon</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i id="iconPreview" class="<?= e($field['field_icon']) ?>"></i>
                                </span>
                                <input type="text" name="field_icon" id="field_icon"
                                       class="form-control font-monospace"
                                       value="<?= e($field['field_icon']) ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="number" id="sort_order" name="sort_order" class="form-control"
                                       value="<?= (int)$field['sort_order'] ?>" min="0">
                                <label class="form-label" for="sort_order">Sort Order</label>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_active" name="is_active"
                                       <?= $field['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="is_public" name="is_public"
                                       <?= $field['is_public'] ? 'checked' : '' ?>>
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
                                       <?= $field['lock_after_set'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="lock_after_set">
                                    Lock once set <small class="text-muted">(member can't edit again)</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-layer-group me-2 text-primary"></i>Multiple Values
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="is_repeatable" name="is_repeatable"
                               <?= $field['is_repeatable'] ? 'checked' : '' ?>
                               <?= !empty($field['group_key']) ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="is_repeatable">
                            Allow multiple values <small class="text-muted">(e.g. Phone Number, Cell Phone)</small>
                        </label>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="group_key" name="group_key" class="form-control font-monospace"
                                       value="<?= e($field['group_key'] ?? '') ?>" list="existingGroups">
                                <label class="form-label" for="group_key">Group Key</label>
                                <div class="form-text">Fields sharing this key repeat together as a set.</div>
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
                                       value="<?= e($field['group_label'] ?? '') ?>">
                                <label class="form-label" for="group_label">Group Heading</label>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($field['group_key'])): ?>
                    <div class="alert alert-info py-2 mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        This field is part of the <strong><?= e($field['group_key']) ?></strong> group.
                        "Allow multiple values" is disabled — the whole group repeats together instead.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
                <a href="<?= BASE_URL ?>admin/fields/" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('field_icon').addEventListener('input', function () {
    document.getElementById('iconPreview').className = this.value.trim() || 'fas fa-tag';
});
document.getElementById('field_type').addEventListener('change', function () {
    document.getElementById('optionsWrap').style.display = this.value === 'select' ? '' : 'none';
});
document.getElementById('group_key').addEventListener('input', function () {
    const repeatable = document.getElementById('is_repeatable');
    if (this.value.trim() !== '') {
        repeatable.checked = false;
        repeatable.disabled = true;
    } else {
        repeatable.disabled = false;
    }
});
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
