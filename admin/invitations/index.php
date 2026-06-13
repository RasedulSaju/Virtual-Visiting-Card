<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../../mailer.php';

$pdo    = getDB();
$errors = [];
$inviteLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'send') {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        }

        if (empty($errors)) {
            // Check not already a registered user
            $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(?)');
            $exists->execute([$email]);
            if ((int)$exists->fetchColumn() > 0) {
                $errors[] = 'This email is already registered.';
            }
        }

        if (empty($errors)) {
            // Check for active (unused, non-expired) invite
            $active = $pdo->prepare(
                'SELECT COUNT(*) FROM invitations
                 WHERE LOWER(email) = LOWER(?) AND used = 0 AND expires_at > NOW()'
            );
            $active->execute([$email]);
            if ((int)$active->fetchColumn() > 0) {
                $errors[] = 'An active invitation for this email already exists.';
            }
        }

        if (empty($errors)) {
            try {
                $token      = bin2hex(random_bytes(32));
                $expires    = date('Y-m-d H:i:s', strtotime('+48 hours'));
                $inviteLink = BASE_URL . 'register?invite=' . $token;

                $pdo->prepare(
                    'INSERT INTO invitations (email, token, invited_by, expires_at)
                     VALUES (?, ?, ?, ?)'
                )->execute([$email, $token, (int)$_SESSION['user_id'], $expires]);

                // Attempt real email via SMTP
                $emailSent = false;
                try {
                    $mailer = new Mailer();
                    $html   = Mailer::buildHtml(
                        'You\'ve been invited to join ' . APP_NAME,
                        '<p>You have been invited to create an account on <strong>' . e(APP_NAME) . '</strong>.</p>
                         <p>This invitation link expires in <strong>48 hours</strong>.</p>',
                        $inviteLink,
                        'Accept Invitation'
                    );
                    $emailSent = $mailer->send($email, $email, 'Invitation to join ' . APP_NAME, $html);
                } catch (Exception $ex) {
                    error_log('[Invitations/Mailer] ' . $ex->getMessage());
                }

                if ($emailSent) {
                    $inviteLink = null; // hide link in UI — real email was sent
                    flash('success', 'Invitation email sent to ' . $email . '.');
                } else {
                    flash('success', 'Invitation created for ' . $email . '. Copy the link below.');
                }
            } catch (PDOException $ex) {
                error_log('[AdminInvite] ' . $ex->getMessage());
                $errors[] = 'Database error.';
            }
        }
    } elseif ($action === 'revoke') {
        $invId = (int)($_POST['inv_id'] ?? 0);
        if ($invId) {
            $pdo->prepare('DELETE FROM invitations WHERE id = ? AND used = 0')->execute([$invId]);
            flash('success', 'Invitation revoked.');
            redirect('admin/invitations/');
        }
    }
}

// Load all invitations with inviter info
$invitations = $pdo->query(
    'SELECT i.*, u.username AS invited_by_name
     FROM invitations i
     LEFT JOIN users u ON i.invited_by = u.id
     ORDER BY i.created_at DESC
     LIMIT 100'
)->fetchAll();

$pageTitle = 'Invitations';
$activeNav = 'invitations';
require_once __DIR__ . '/../layout_header.php';
?>

<div class="row g-4">
    <!-- Send Invite -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom fw-semibold">
                <i class="fas fa-paper-plane me-2 text-primary"></i>Send Invitation
            </div>
            <div class="card-body p-4">
                <?php if ($errors): ?>
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul></div>
                <?php endif; ?>

                <?php if ($inviteLink): ?>
                <div class="alert alert-success">
                    <p class="fw-semibold mb-1">
                        <i class="fas fa-check-circle me-1"></i> Link Generated
                    </p>
                    <p class="small mb-2">Share this link with the invitee (expires in 48 hours):</p>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm font-monospace small"
                               id="inviteLinkInput" value="<?= e($inviteLink) ?>" readonly>
                        <button class="btn btn-outline-secondary btn-sm"
                                onclick="document.getElementById('inviteLinkInput').select();document.execCommand('copy')"
                                type="button" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="text-warning small mt-1 mb-0">
                        <i class="fas fa-flask me-1"></i>Dev mode — in production send via SMTP.
                    </p>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="send">
                    <div class="form-outline mb-3">
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($_POST['email'] ?? '') ?>" required>
                        <label class="form-label" for="email">Email Address</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-envelope me-1"></i> Generate Invite Link
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <p class="small text-muted mb-0">
                    <i class="fas fa-info-circle me-1 text-info"></i>
                    Invite links bypass the registration-closed setting and expire after <strong>48 hours</strong>.
                    Each link is single-use.
                </p>
            </div>
        </div>
    </div>

    <!-- Invitation List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom fw-semibold">
                <i class="fas fa-list me-2 text-secondary"></i>All Invitations
                <span class="badge bg-secondary ms-1"><?= count($invitations) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Invited By</th>
                            <th>Expires</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($invitations)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No invitations yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($invitations as $inv):
                        $expired  = strtotime($inv['expires_at']) < time();
                        $statusCls = $inv['used'] ? 'success' : ($expired ? 'secondary' : 'warning');
                        $statusLbl = $inv['used'] ? 'Used' : ($expired ? 'Expired' : 'Pending');
                    ?>
                    <tr>
                        <td><?= e($inv['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $statusCls ?>-subtle text-<?= $statusCls ?>">
                                <?= $statusLbl ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= e($inv['invited_by_name'] ?? '—') ?></td>
                        <td class="small text-muted <?= $expired && !$inv['used'] ? 'text-danger' : '' ?>">
                            <?= date('M j, Y H:i', strtotime($inv['expires_at'])) ?>
                        </td>
                        <td class="text-end">
                            <?php if (!$inv['used'] && !$expired): ?>
                            <button type="button" class="btn btn-xs btn-sm btn-outline-secondary me-1"
                                    onclick="copyInvite('<?= e(BASE_URL . 'register?invite=' . $inv['token']) ?>')"
                                    title="Copy link">
                                <i class="fas fa-copy"></i>
                            </button>
                            <form method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action"  value="revoke">
                                <input type="hidden" name="inv_id" value="<?= (int)$inv['id'] ?>">
                                <button type="submit" class="btn btn-xs btn-sm btn-outline-danger"
                                        data-confirm="Revoke this invitation?"
                                        title="Revoke">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function copyInvite(url) {
    navigator.clipboard?.writeText(url).then(() => alert('Link copied!'))
        .catch(() => prompt('Copy this link:', url));
}
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
