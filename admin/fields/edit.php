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

    $fieldName  = trim(strtolower(preg_replace('/\s+/', '_', $_POST['field_name']  ?? '')));
    $fieldLabel = trim($_POST['field_label'] ?? '');
    $fieldType  = $_POST['field_type']       ?? 'text';
    $fieldIcon  = trim($_POST['field_icon']  ?? 'fas fa-tag') ?: 'fas fa-tag';
    $sortOrder  = (int)($_POST['sort_order'] ?? 0);
    $isActive   = isset($_POST['is_active']) ? 1 : 0;
    $isPublic   = isset($_POST['is_public']) ? 1 : 0;

    if ($fieldName === '') {
        $errors[] = 'Machine name is required.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $fieldName)) {
        $errors[] = 'Machine name: lowercase letters, numbers and underscores only.';
    }

    if ($fieldLabel === '') $errors[] = 'Label is required.';

    if (!in_array($fieldType, ['text', 'url', 'textarea', 'date'], true)) {
        $errors[] = 'Invalid field type.';
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
                 SET field_name=?, field_label=?, field_type=?, field_icon=?, sort_order=?, is_active=?, is_public=?
                 WHERE id=?'
            )->execute([$fieldName, $fieldLabel, $fieldType, $fieldIcon, $sortOrder, $isActive, $isPublic, $fieldId]);

            flash('success', 'Field updated.');
            redirect('admin/fields/edit.php?id=' . $fieldId);
        } catch (PDOException $ex) {
            error_log('[AdminEditField] ' . $ex->getMessage());
            $errors[] = 'Database error.';
        }
    }

    // Rebuild for re-render
    $field = array_merge($field, [
        'field_name'  => $fieldName,  'field_label' => $fieldLabel,
        'field_type'  => $fieldType,  'field_icon'  => $fieldIcon,
        'sort_order'  => $sortOrder,  'is_active'   => $isActive,
        'is_public'   => $isPublic,
    ]);
}

// Count how many users have a value for this field
$usageCount = (int)$pdo->prepare(
    "SELECT COUNT(*) FROM user_field_values WHERE field_id = ? AND field_value != ''"
)->execute([$fieldId]) ? 0 : 0;
$usageStmt = $pdo->prepare("SELECT COUNT(*) FROM user_field_values WHERE field_id = ? AND field_value != ''");
$usageStmt->execute([$fieldId]);
$usageCount = (int)$usageStmt->fetchColumn();

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
                            <select name="field_type" class="form-select">
                                <option value="text"     <?= $field['field_type'] === 'text'     ? 'selected' : '' ?>>Text</option>
                                <option value="url"      <?= $field['field_type'] === 'url'      ? 'selected' : '' ?>>URL</option>
                                <option value="textarea" <?= $field['field_type'] === 'textarea' ? 'selected' : '' ?>>Textarea</option>
                                <option value="date"     <?= $field['field_type'] === 'date'     ? 'selected' : '' ?>>Date</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="number" id="sort_order" name="sort_order" class="form-control"
                                       value="<?= (int)$field['sort_order'] ?>" min="0">
                                <label class="form-label" for="sort_order">Sort Order</label>
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
                    </div>
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
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
