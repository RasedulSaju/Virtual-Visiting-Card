<?php
/** @var array $profileUser   — set by index.php router */
/** @var array $profileFields — set by index.php router */
if (empty($profileUser)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$profileFields = $profileFields ?? [];
$isSelf        = isLoggedIn() && (int)$_SESSION['user_id'] === (int)$profileUser['id'];
$canEdit       = $isSelf && (isAdmin() || (bool)$profileUser['can_edit_profile']);
?>

<div class="row g-4">
    <!-- ── Left Column: Profile Card ──────────────────────── -->
    <div class="col-md-4 col-lg-3">
        <div class="card border-0 shadow-sm text-center profile-card">
            <div class="profile-card-banner"></div>
            <div class="card-body pt-0">
                <div class="profile-avatar-wrap">
                    <img src="<?= avatarUrl($profileUser['profile_image']) ?>"
                         class="profile-avatar rounded-circle border-4 border-white"
                         width="100" height="100"
                         style="object-fit:cover;" alt="<?= e($profileUser['username']) ?>">
                </div>
                <h2 class="h5 fw-bold mb-0 mt-2"><?= e($profileUser['username']) ?></h2>
                <span class="badge <?= $profileUser['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> mb-2">
                    <?= $profileUser['role'] === 'admin' ? 'Administrator' : 'Member' ?>
                </span>
                <?php if ($profileUser['bio']): ?>
                    <p class="text-muted small text-wrap px-2 mb-3">
                        <?= nl2br(e($profileUser['bio'])) ?>
                    </p>
                <?php endif; ?>
                <small class="text-muted d-block mb-3">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Joined <?= date('M Y', strtotime($profileUser['created_at'])) ?>
                </small>
                <?php if ($canEdit): ?>
                    <a href="<?= BASE_URL ?>edit-profile" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-pen me-1"></i> Edit Profile
                    </a>
                <?php endif; ?>
                <?php if (isAdmin() && !$isSelf): ?>
                    <a href="<?= BASE_URL ?>admin/users/edit.php?id=<?= (int)$profileUser['id'] ?>"
                       class="btn btn-sm btn-outline-secondary w-100 mt-1">
                        <i class="fas fa-cog me-1"></i> Admin Edit
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Right Column: Fields ───────────────────────────── -->
    <div class="col-md-8 col-lg-9">
        <?php
        // Separate fields with values from empties
        $filledFields = array_filter($profileFields, fn($f) => trim($f['field_value']) !== '');
        ?>

        <?php if (empty($filledFields)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-id-badge fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">This profile has no additional information yet.</p>
                    <?php if ($canEdit): ?>
                        <a href="<?= BASE_URL ?>edit-profile" class="btn btn-primary btn-sm mt-3">
                            <i class="fas fa-plus me-1"></i> Add Details
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom fw-semibold p-4 pb-2">
                    <i class="fas fa-address-card me-2 text-primary"></i>Details
                </div>
                <div class="card-body p-4">
                    <dl class="profile-fields row g-3 mb-0">
                        <?php foreach ($filledFields as $field):
                            $val = e($field['field_value']);
                        ?>
                        <div class="col-sm-6 col-xl-4">
                            <dt class="text-muted small fw-normal mb-1">
                                <i class="<?= e($field['field_icon']) ?> me-1"></i><?= e($field['field_label']) ?>
                            </dt>
                            <dd class="fw-semibold mb-0 text-break">
                                <?php if ($field['field_type'] === 'url'): ?>
                                    <a href="<?= $val ?>" target="_blank" rel="noopener noreferrer"
                                       class="text-decoration-none">
                                        <?= truncate($val, 40) ?>
                                        <i class="fas fa-external-link-alt ms-1 small text-muted"></i>
                                    </a>
                                <?php elseif ($field['field_type'] === 'textarea'): ?>
                                    <span class="text-wrap"><?= nl2br($val) ?></span>
                                <?php else: ?>
                                    <?= $val ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
