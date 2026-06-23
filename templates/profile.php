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
$viewerIsAdmin = isAdmin();
$canEdit       = $isSelf && ($viewerIsAdmin || (bool)$profileUser['can_edit_profile']);
$isResigned    = ($profileUser['account_status'] ?? 'active') === 'resigned';
$roleLabel     = match ($profileUser['role'] ?? 'user') {
    'superadmin' => 'Administrator',
    'admin'      => 'Administrator',
    default      => 'Member',
};
$roleBadgeCls  = in_array($profileUser['role'] ?? 'user', ['admin', 'superadmin'], true) ? 'bg-danger' : 'bg-primary';

// Visible fields: public fields for everyone, private fields only for owner/admin
$visibleFields = array_filter($profileFields, function ($f) use ($isSelf, $viewerIsAdmin) {
    if (trim($f['field_value']) === '') return false;
    if ((int)($f['is_public'] ?? 1) === 0) {
        return $isSelf || $viewerIsAdmin;
    }
    return true;
});
?>

<?php if ($isResigned): ?>
<div class="resigned-watermark-wrap">
    <div class="resigned-stamp">RESIGNED</div>
</div>
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-4">
    <i class="fas fa-info-circle"></i>
    <span>This person is no longer associated with <?= e(siteName()) ?>. Profile information may be outdated.</span>
</div>
<?php endif; ?>

<div class="row g-4 <?= $isResigned ? 'resigned-profile' : '' ?>">
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
                <span class="badge <?= $roleBadgeCls ?> mb-2"><?= $roleLabel ?></span>
                <?php if ($isResigned): ?>
                    <span class="badge bg-secondary mb-2 ms-1">Resigned</span>
                <?php endif; ?>
                <?php if ($profileUser['bio'] && !$isResigned): ?>
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
                <?php if ($viewerIsAdmin && !$isSelf): ?>
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
        <?php if ($isResigned && !$viewerIsAdmin && !$isSelf): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">Profile details are hidden — this person is no longer active.</p>
                </div>
            </div>
        <?php elseif (empty($visibleFields)): ?>
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
                        <?php foreach ($visibleFields as $i => $field):
                            $val      = e($field['field_value']);
                            $isPriv   = (int)($field['is_public'] ?? 1) === 0;
                            $delay    = ($i % 4) * 100; // stagger 0, 100, 200, 300ms
                        ?>
                        <div class="col-sm-6 col-xl-4"
                             data-mdb-animation-start="onScroll"
                             data-mdb-animation="fade-in"
                             data-mdb-animation-delay="<?= $delay ?>"
                             data-mdb-animation-duration="500">
                            <dt class="text-muted small fw-normal mb-1">
                                <i class="<?= e($field['field_icon']) ?> me-1"></i><?= e($field['field_label']) ?>
                                <?php if ($isPriv): ?>
                                    <i class="fas fa-lock ms-1 text-warning" title="Private — only visible to you and admins" style="font-size:.7rem;"></i>
                                <?php endif; ?>
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
                                <?php elseif ($field['field_type'] === 'date'): ?>
                                    <?= e(date('F j, Y', strtotime($field['field_value']))) ?>
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
