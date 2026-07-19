<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// Load full user record
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user) {
    flash('error', 'Session invalid. Please log in again.');
    redirect('login');
}

// Gate: only admin or users with permission
if (!isAdmin() && !(bool)$user['can_edit_profile']) {
    $pageTitle = 'Profile Editing Disabled';
    $metaRobots = 'noindex,nofollow';
    require __DIR__ . '/templates/layout_header.php';
    ?>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm text-center p-5 mt-4">
                <i class="fas fa-ban fa-3x text-danger mb-3"></i>
                <h4 class="fw-bold">Editing Disabled</h4>
                <p class="text-muted">An administrator has disabled profile editing for your account.</p>
                <a href="<?= BASE_URL . e($user['username']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-user me-1"></i> View Profile
                </a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/templates/layout_footer.php';
    exit;
}

// Load field definitions (active, not admin-only — those are managed by admins)
function _loadEditableFields(PDO $pdo, int $userId): array
{
    $fStmt = $pdo->prepare(
        "SELECT id, field_name, field_label, field_type, field_icon, field_options,
                is_public, edit_permission, lock_after_set, is_repeatable, group_key, group_label, sort_order
         FROM   profile_fields
         WHERE  is_active = 1
         ORDER  BY sort_order ASC"
    );
    $fStmt->execute();
    $fields = $fStmt->fetchAll();

    $vStmt = $pdo->prepare('SELECT field_id, instance, field_value FROM user_field_values WHERE user_id = ?');
    $vStmt->execute([$userId]);
    $valuesByField = [];
    foreach ($vStmt->fetchAll() as $v) {
        $valuesByField[(int)$v['field_id']][(int)$v['instance']] = $v['field_value'];
    }

    foreach ($fields as &$f) {
        $f['values'] = $valuesByField[(int)$f['id']] ?? [];
        ksort($f['values']);
    }
    unset($f);

    return $fields;
}

$profileFields = _loadEditableFields($pdo, $userId);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $bio      = trim($_POST['bio'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');

    // Handle profile image upload
    $newImage = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        try {
            $newImage = uploadProfileImage($_FILES['profile_image'], $userId);
            if ($user['profile_image'] !== DEFAULT_AVATAR) {
                deleteProfileImage($user['profile_image']);
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare('UPDATE users SET bio = ?, full_name = ?, profile_image = ? WHERE id = ?')
                ->execute([$bio, $fullName !== '' ? $fullName : null, $newImage, $userId]);

            $incoming = $_POST['field'] ?? []; // field[<id>][<instance>] => value

            foreach ($profileFields as $field) {
                // Admin-only fields are NEVER writable here, no matter what's in POST
                if ($field['edit_permission'] === 'admin') {
                    continue;
                }

                // Lock-after-set: if a non-empty value already exists, ignore submitted data entirely
                $hasExistingValue = false;
                foreach ($field['values'] as $v) {
                    if (trim((string)$v) !== '') { $hasExistingValue = true; break; }
                }
                if ((int)$field['lock_after_set'] === 1 && $hasExistingValue) {
                    continue;
                }

                $fieldId = (int)$field['id'];
                $submittedValues = $incoming[$fieldId] ?? [];
                if (!is_array($submittedValues)) {
                    $submittedValues = [$submittedValues];
                }

                // Clean, non-empty, re-indexed values only
                $cleanValues = [];
                foreach ($submittedValues as $sv) {
                    $sv = trim((string)$sv);
                    if ($sv !== '') $cleanValues[] = $sv;
                }

                // Replace all stored rows for this field with the clean set
                $pdo->prepare('DELETE FROM user_field_values WHERE user_id = ? AND field_id = ?')
                    ->execute([$userId, $fieldId]);

                if ($cleanValues) {
                    $ins = $pdo->prepare(
                        'INSERT INTO user_field_values (user_id, field_id, instance, field_value) VALUES (?, ?, ?, ?)'
                    );
                    foreach (array_values($cleanValues) as $idx => $val) {
                        $ins->execute([$userId, $fieldId, $idx, $val]);
                    }
                }
            }

            flash('success', 'Profile updated successfully.');
            redirect('edit-profile');
        } catch (PDOException $e) {
            error_log('[EditProfile] ' . $e->getMessage());
            $errors[] = 'Database error. Please try again.';
        }
    }

    // Refresh user record and fields after update
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    $profileFields = _loadEditableFields($pdo, $userId);
}

// Split fields for rendering: standalone vs grouped, admin-only vs member-editable
$standaloneFields = [];
$groups           = []; // group_key => ['label'=>..., 'fields'=>[...]]
$adminOnlyFields  = [];

foreach ($profileFields as $f) {
    if ($f['edit_permission'] === 'admin') {
        $adminOnlyFields[] = $f;
        continue;
    }
    if (!empty($f['group_key'])) {
        $gk = $f['group_key'];
        if (!isset($groups[$gk])) {
            $groups[$gk] = ['label' => $f['group_label'] ?: ucfirst(str_replace('_', ' ', $gk)), 'fields' => []];
        }
        $groups[$gk]['fields'][] = $f;
    } else {
        $standaloneFields[] = $f;
    }
}

$pageTitle = 'Edit Profile';
$metaRobots = 'noindex,nofollow';
require __DIR__ . '/templates/layout_header.php';

// ── Field renderer (single input) ─────────────────────────────
function _fieldInput(array $field, string $name, string $value, string $idSuffix = ''): string
{
    $id  = 'f_' . (int)$field['id'] . $idSuffix;
    $out = '<div class="form-outline flex-grow-1">';

    if ($field['field_type'] === 'textarea') {
        $out .= '<textarea id="' . e($id) . '" name="' . e($name) . '" class="form-control" rows="2" placeholder=" ">'
              . e($value) . '</textarea>';
    } elseif ($field['field_type'] === 'date') {
        $out .= '<input type="date" id="' . e($id) . '" name="' . e($name) . '" class="form-control" value="'
              . e($value) . '" placeholder=" ">';
    } elseif ($field['field_type'] === 'select') {
        $opts = preg_split('/\r?\n/', (string)$field['field_options']);
        $out .= '<select id="' . e($id) . '" name="' . e($name) . '" class="form-select">';
        $out .= '<option value="">— Select —</option>';
        foreach ($opts as $opt) {
            $opt = trim($opt);
            if ($opt === '') continue;
            $sel = $opt === $value ? ' selected' : '';
            $out .= '<option value="' . e($opt) . '"' . $sel . '>' . e($opt) . '</option>';
        }
        $out .= '</select>';
        return $out . '</div>'; // selects don't use floating label pattern
    } else {
        $type = $field['field_type'] === 'url' ? 'url' : 'text';
        $out .= '<input type="' . $type . '" id="' . e($id) . '" name="' . e($name) . '" class="form-control" value="'
              . e($value) . '" placeholder="' . ($field['field_type'] === 'url' ? 'https://' : ' ') . '">';
    }

    $out .= '<label class="form-label" for="' . e($id) . '">'
          . '<i class="' . e($field['field_icon']) . ' me-1"></i>' . e($field['field_label']) . '</label>';
    $out .= '</div>';
    return $out;
}
?>
<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex align-items-center mb-4">
            <img src="<?= avatarUrl($user['profile_image']) ?>"
                 class="rounded-circle border me-3" width="56" height="56"
                 style="object-fit:cover;" alt="Avatar">
            <div>
                <h2 class="mb-0 fw-bold"><?= e(displayName($user)) ?></h2>
                <a href="<?= BASE_URL . e($user['username']) ?>" class="small text-muted text-decoration-none">
                    <i class="fas fa-external-link-alt me-1"></i>View public profile
                </a>
            </div>
        </div>

        <?= renderFlash() ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>

            <!-- Profile Picture -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-camera me-2 text-primary"></i>Profile Picture
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-4 flex-wrap">
                        <img src="<?= avatarUrl($user['profile_image']) ?>"
                             id="avatarPreview"
                             class="rounded-circle border" width="100" height="100"
                             style="object-fit:cover;" alt="Current avatar">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control mb-1" id="profile_image"
                                   name="profile_image" accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">JPG, PNG or GIF · Max 2 MB</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Name & Bio -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-id-card me-2 text-primary"></i>About You
                </div>
                <div class="card-body p-4">
                    <div class="form-outline mb-3">
                        <input type="text" id="full_name" name="full_name" class="form-control"
                               value="<?= e($user['full_name'] ?? '') ?>" placeholder=" ">
                        <label class="form-label" for="full_name">Full Name</label>
                        <div class="form-text">Shown as your profile title instead of your username. Leave empty to show your username.</div>
                    </div>
                    <div class="form-outline">
                        <textarea id="bio" name="bio" class="form-control" rows="4"><?= e($user['bio'] ?? '') ?></textarea>
                        <label class="form-label" for="bio">Bio</label>
                    </div>
                </div>
            </div>

            <!-- Admin-controlled fields (read-only) -->
            <?php if ($adminOnlyFields): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-building me-2 text-primary"></i>Company Details
                    <small class="text-muted fw-normal">— set by your administrator</small>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php foreach ($adminOnlyFields as $af):
                            $val = trim((string)($af['values'][0] ?? ''));
                        ?>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" class="form-control" value="<?= e($val ?: '—') ?>" placeholder=" " readonly disabled>
                                <label class="form-label">
                                    <i class="<?= e($af['field_icon']) ?> me-1"></i><?= e($af['field_label']) ?>
                                    <i class="fas fa-shield-alt ms-1 text-primary" style="font-size:.7rem;" title="Set by admin"></i>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Grouped repeating sets (e.g. Position + Company) -->
            <?php foreach ($groups as $gk => $group): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-layer-group me-2 text-primary"></i><?= e($group['label']) ?></span>
                    <button type="button" class="btn btn-sm btn-outline-primary group-add-btn" data-group="<?= e($gk) ?>">
                        <i class="fas fa-plus me-1"></i>Add
                    </button>
                </div>
                <div class="card-body p-4">
                    <div id="group-<?= e($gk) ?>-rows">
                        <?php
                        // Determine existing instance count for this group
                        $maxInst = 0;
                        foreach ($group['fields'] as $gf) {
                            foreach ($gf['values'] as $inst => $v) {
                                if (trim((string)$v) !== '') $maxInst = max($maxInst, $inst + 1);
                            }
                        }
                        $rowCount = max(1, $maxInst); // always show at least 1 row
                        for ($inst = 0; $inst < $rowCount; $inst++):
                        ?>
                        <div class="group-row border rounded p-3 mb-2 position-relative">
                            <?php if ($rowCount > 1 || $inst > 0): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 remove-row-btn">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                            <div class="row g-3">
                                <?php foreach ($group['fields'] as $gf):
                                    $val = (string)($gf['values'][$inst] ?? '');
                                    $name = 'field[' . (int)$gf['id'] . '][]';
                                ?>
                                <div class="col-md-6">
                                    <?= _fieldInput($gf, $name, $val, '_' . $inst) ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Standalone fields -->
            <?php if ($standaloneFields): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom fw-semibold">
                    <i class="fas fa-list-alt me-2 text-primary"></i>Profile Details
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php foreach ($standaloneFields as $field):
                            $isPrivateField = (int)($field['is_public'] ?? 1) === 0;
                            $isLocked       = (int)$field['lock_after_set'] === 1;
                            $hasValue       = false;
                            foreach ($field['values'] as $v) { if (trim((string)$v) !== '') { $hasValue = true; break; } }
                            $lockedNow      = $isLocked && $hasValue;
                            $fid            = (int)$field['id'];
                        ?>

                        <?php if ((int)$field['is_repeatable'] === 1): ?>
                            <!-- Repeatable standalone field -->
                            <div class="col-12">
                                <label class="form-label fw-semibold d-flex align-items-center justify-content-between">
                                    <span>
                                        <i class="<?= e($field['field_icon']) ?> me-1"></i><?= e($field['field_label']) ?>
                                        <?php if ($isPrivateField): ?>
                                            <i class="fas fa-lock ms-1 text-warning" style="font-size:.7rem;"></i>
                                        <?php endif; ?>
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-primary repeat-add-btn" data-field="<?= $fid ?>">
                                        <i class="fas fa-plus me-1"></i>Add
                                    </button>
                                </label>
                                <div id="repeat-<?= $fid ?>-rows">
                                    <?php
                                    $vals = $field['values'];
                                    if (empty($vals)) $vals = [0 => ''];
                                    $ri = 0;
                                    foreach ($vals as $v):
                                        $ri++;
                                    ?>
                                    <div class="d-flex align-items-start gap-2 mb-2 repeat-row">
                                        <?= _fieldInput($field, 'field[' . $fid . '][]', (string)$v, '_' . $ri) ?>
                                        <?php if ($ri > 1 || count($vals) > 1): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn mt-1">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Single-value standalone field -->
                            <div class="col-md-6">
                                <?php if ($lockedNow): ?>
                                    <div class="form-outline">
                                        <input type="text" class="form-control"
                                               value="<?= e((string)($field['values'][0] ?? '')) ?>" placeholder=" " readonly disabled>
                                        <label class="form-label">
                                            <i class="<?= e($field['field_icon']) ?> me-1"></i><?= e($field['field_label']) ?>
                                            <i class="fas fa-lock ms-1 text-muted" style="font-size:.7rem;" title="Locked — set once"></i>
                                        </label>
                                    </div>
                                    <div class="form-text">This can only be set once. Contact an admin to change it.</div>
                                <?php else: ?>
                                    <?= _fieldInput($field, 'field[' . $fid . '][]', (string)($field['values'][0] ?? '')) ?>
                                    <?php if ($isLocked): ?>
                                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>You can only set this once.</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($isPrivateField): ?>
                                    <div class="form-text">Private — only you and admins can see this.</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<script>
document.getElementById('profile_image')?.addEventListener('change', function () {
    if (this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
        reader.readAsDataURL(this.files[0]);
    }
});

// ── Repeatable standalone field: add / remove rows ────────────
document.querySelectorAll('.repeat-add-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const fid  = btn.dataset.field;
        const wrap = document.getElementById('repeat-' + fid + '-rows');
        const first = wrap.querySelector('.repeat-row');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input, textarea, select').forEach(el => el.value = '');
        if (!clone.querySelector('.remove-row-btn')) {
            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'btn btn-sm btn-outline-danger remove-row-btn mt-1';
            rm.innerHTML = '<i class="fas fa-times"></i>';
            clone.appendChild(rm);
        }
        wrap.appendChild(clone);
        bindRemove(clone.querySelector('.remove-row-btn'));
    });
});

// ── Grouped fields: add / remove row sets ─────────────────────
document.querySelectorAll('.group-add-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const gk   = btn.dataset.group;
        const wrap = document.getElementById('group-' + gk + '-rows');
        const first = wrap.querySelector('.group-row');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input, textarea, select').forEach(el => el.value = '');
        if (!clone.querySelector('.remove-row-btn')) {
            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 remove-row-btn';
            rm.innerHTML = '<i class="fas fa-times"></i>';
            clone.appendChild(rm);
        }
        wrap.appendChild(clone);
        bindRemove(clone.querySelector('.remove-row-btn'));
    });
});

function bindRemove(btn) {
    if (!btn) return;
    btn.addEventListener('click', () => {
        btn.closest('.repeat-row, .group-row')?.remove();
    });
}
document.querySelectorAll('.remove-row-btn').forEach(bindRemove);
</script>

<?php require __DIR__ . '/templates/layout_footer.php'; ?>
