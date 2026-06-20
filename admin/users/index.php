<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

$pdo = getDB();

// ── Bulk action handler ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $bulkAction = $_POST['bulk_action'] ?? '';
    $ids        = array_map('intval', (array)($_POST['ids'] ?? []));
    $ids        = array_filter($ids); // remove zeros
    // Never act on self
    $ids = array_filter($ids, fn($id) => $id !== (int)$_SESSION['user_id']);

    // Regular admins can never target superadmin accounts, even via tampered POST data
    if (!isSuperAdmin() && $ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $saStmt = $pdo->prepare("SELECT id FROM users WHERE id IN ($placeholders) AND role = 'superadmin'");
        $saStmt->execute(array_values($ids));
        $superadminIds = array_column($saStmt->fetchAll(), 'id');
        $ids = array_diff($ids, $superadminIds);
    }

    if (empty($ids)) {
        flash('error', 'No eligible users selected.');
        redirect('admin/users/');
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        switch ($bulkAction) {
            case 'delete':
                // Fetch images for cleanup
                $imgStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id IN ($placeholders)");
                $imgStmt->execute($ids);
                foreach ($imgStmt->fetchAll() as $row) {
                    deleteProfileImage($row['profile_image']);
                }
                $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($ids);
                flash('success', count($ids) . ' user(s) deleted.');
                break;

            case 'enable_edit':
                $pdo->prepare("UPDATE users SET can_edit_profile = 1 WHERE id IN ($placeholders)")
                    ->execute($ids);
                flash('success', 'Profile editing enabled for ' . count($ids) . ' user(s).');
                break;

            case 'disable_edit':
                $pdo->prepare("UPDATE users SET can_edit_profile = 0 WHERE id IN ($placeholders)")
                    ->execute($ids);
                flash('success', 'Profile editing disabled for ' . count($ids) . ' user(s).');
                break;

            case 'set_user':
                $pdo->prepare("UPDATE users SET role = 'user' WHERE id IN ($placeholders)")
                    ->execute($ids);
                flash('success', 'Role set to User for ' . count($ids) . ' account(s).');
                break;

            default:
                flash('error', 'Unknown bulk action.');
        }
    } catch (PDOException $e) {
        error_log('[AdminBulkUsers] ' . $e->getMessage());
        flash('error', 'Bulk action failed.');
    }

    redirect('admin/users/');
}

// ── List ──────────────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$curPage = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$offset  = ($curPage - 1) * $perPage;

$conditions = [];
$params     = [];

// Superadmin accounts are invisible to regular admins
if (!isSuperAdmin()) {
    $conditions[] = "role != 'superadmin'";
}

if ($search !== '') {
    $conditions[] = '(username LIKE ? OR email LIKE ?)';
    $params[]     = "%$search%";
    $params[]     = "%$search%";
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = (int)ceil($total / $perPage);

$listStmt = $pdo->prepare(
    "SELECT id, username, email, role, can_edit_profile, account_status, created_at
     FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"
);
$listStmt->execute($params);
$users = $listStmt->fetchAll();

$pageTitle = 'Users';
$activeNav = 'users';
require_once __DIR__ . '/../layout_header.php';
?>

<form method="POST" id="bulkForm">
    <?= csrfField() ?>

    <!-- Toolbar -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <!-- Search -->
        <div class="d-flex gap-2">
            <input type="text" form="searchForm" name="q" class="form-control form-control-sm"
                   placeholder="Search…" value="<?= e($search) ?>" style="width:200px;">
            <button form="searchForm" class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
            </button>
            <?php if ($search): ?>
                <a href="<?= BASE_URL ?>admin/users/" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Bulk action controls (shown only when rows selected) -->
        <div class="d-flex align-items-center gap-2 ms-auto bulk-controls" style="display:none!important;">
            <span class="text-muted small bulk-count-label">0 selected</span>
            <select name="bulk_action" class="form-select form-select-sm" style="width:auto;">
                <option value="">— Bulk Action —</option>
                <option value="enable_edit">Enable Profile Editing</option>
                <option value="disable_edit">Disable Profile Editing</option>
                <option value="set_user">Set Role → User</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button type="submit" class="btn btn-sm btn-danger" id="bulkApplyBtn"
                    onclick="return confirmBulk()">
                <i class="fas fa-bolt me-1"></i>Apply
            </button>
        </div>

        <a href="<?= BASE_URL ?>admin/users/create.php" class="btn btn-primary btn-sm ms-auto">
            <i class="fas fa-user-plus me-1"></i> New User
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" class="form-check-input" id="selectAll"
                                   title="Select all">
                        </th>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-center">Can Edit</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u):
                    $isSelf = (int)$u['id'] === (int)$_SESSION['user_id'];
                ?>
                    <tr class="<?= $isSelf ? 'table-primary' : '' ?>">
                        <td>
                            <?php if (!$isSelf): ?>
                                <input type="checkbox" class="form-check-input row-check"
                                       name="ids[]" value="<?= (int)$u['id'] ?>">
                            <?php else: ?>
                                <span title="Cannot select yourself">
                                    <i class="fas fa-user-circle text-primary"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= (int)$u['id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL . e($u['username']) ?>" target="_blank"
                               class="fw-semibold text-decoration-none">
                                <?= e($u['username']) ?>
                            </a>
                        </td>
                        <td class="small text-muted"><?= e($u['email']) ?></td>
                        <td>
                            <span class="badge <?= in_array($u['role'], ['admin','superadmin'], true) ? 'bg-danger' : 'bg-primary' ?>">
                                <?= e($u['role']) ?>
                            </span>
                            <?php if ($u['account_status'] === 'resigned'): ?>
                                <span class="badge bg-secondary">Resigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($u['can_edit_profile']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>admin/users/edit.php?id=<?= (int)$u['id'] ?>"
                               class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <?php if (!$isSelf): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="quickDelete(<?= (int)$u['id'] ?>, '<?= e($u['username']) ?>')"
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-danger disabled" title="Cannot delete yourself">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <small class="text-muted"><?= $total ?> user<?= $total !== 1 ? 's' : '' ?></small>
            <nav><ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i === $curPage ? 'active' : '' ?>">
                    <a class="page-link" href="?p=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</form>

<!-- Hidden single-delete form -->
<form method="POST" action="<?= BASE_URL ?>admin/users/delete.php" id="deleteOneForm">
    <?= csrfField() ?>
    <input type="hidden" name="id" id="deleteOneId">
</form>

<!-- Search form (GET, separate from POST bulk form) -->
<form method="GET" id="searchForm" style="display:none;"></form>

<script>
const selfId = <?= (int)$_SESSION['user_id'] ?>;

// Select all
document.getElementById('selectAll').addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
    updateBulkBar();
});

document.querySelectorAll('.row-check').forEach(c => {
    c.addEventListener('change', updateBulkBar);
});

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-check:checked');
    const bar = document.querySelector('.bulk-controls');
    const label = document.querySelector('.bulk-count-label');
    bar.style.setProperty('display', checked.length ? 'flex' : 'none', 'important');
    label.textContent = checked.length + ' selected';
    document.getElementById('selectAll').indeterminate =
        checked.length > 0 && checked.length < document.querySelectorAll('.row-check').length;
}

function confirmBulk() {
    const action = document.querySelector('[name="bulk_action"]').value;
    const count  = document.querySelectorAll('.row-check:checked').length;
    if (!action) { alert('Please choose a bulk action first.'); return false; }
    if (action === 'delete') {
        return confirm('Permanently delete ' + count + ' user(s)? This cannot be undone.');
    }
    return confirm('Apply "' + action + '" to ' + count + ' user(s)?');
}

function quickDelete(id, name) {
    if (!confirm('Delete user "' + name + '"? This cannot be undone.')) return;
    document.getElementById('deleteOneId').value = id;
    document.getElementById('deleteOneForm').submit();
}
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
