</main><!-- /main.container -->

<!-- ── Footer ─────────────────────────────────────────────────── -->
<footer class="cms-footer mt-auto py-4">
    <div class="container">
        <div class="row align-items-start g-3">
            <div class="col-md-4">
                <div class="fw-bold mb-1">
                    <i class="fas fa-layer-group me-1 text-primary"></i><?= e(siteName()) ?>
                </div>
                <small class="text-muted">© <?= date('Y') ?> <?= e(siteName()) ?>. All rights reserved.</small>
            </div>
            <div class="col-md-4">
                <p class="fw-semibold small mb-2">Explore</p>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= BASE_URL ?>members" class="text-muted text-decoration-none small">
                        <i class="fas fa-users me-1"></i>Members</a></li>
                    <?php foreach (getNavPages() as $_fp): ?>
                    <li><a href="<?= BASE_URL . e($_fp['slug']) ?>" class="text-muted text-decoration-none small">
                        <i class="fas fa-file-alt me-1"></i><?= e($_fp['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <p class="fw-semibold small mb-2">Account</p>
                <ul class="list-unstyled mb-0">
                    <?php if (isLoggedIn()): ?>
                    <li><a href="<?= BASE_URL . e($_SESSION['username'] ?? '') ?>" class="text-muted text-decoration-none small">
                        <i class="fas fa-id-card me-1"></i>My Profile</a></li>
                    <li><a href="<?= BASE_URL ?>edit-profile" class="text-muted text-decoration-none small">
                        <i class="fas fa-pen me-1"></i>Edit Profile</a></li>
                    <li><a href="<?= BASE_URL ?>change-password" class="text-muted text-decoration-none small">
                        <i class="fas fa-lock me-1"></i>Change Password</a></li>
                    <li><a href="<?= BASE_URL ?>logout" class="text-muted text-decoration-none small">
                        <i class="fas fa-sign-out-alt me-1"></i>Sign Out</a></li>
                    <?php else: ?>
                    <li><a href="<?= BASE_URL ?>login" class="text-muted text-decoration-none small">
                        <i class="fas fa-sign-in-alt me-1"></i>Login</a></li>
                    <?php if (getSetting('registration_open', '1') === '1'): ?>
                    <li><a href="<?= BASE_URL ?>register" class="text-muted text-decoration-none small">
                        <i class="fas fa-user-plus me-1"></i>Sign Up</a></li>
                    <?php endif; ?>
                    <li><a href="<?= BASE_URL ?>forgot-password" class="text-muted text-decoration-none small">
                        <i class="fas fa-key me-1"></i>Forgot Password</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr class="my-3 opacity-25">
        <div class="text-center">
            <a href="<?= BASE_URL ?>sitemap.xml" class="text-muted text-decoration-none small me-3">
                <i class="fas fa-sitemap me-1"></i>Sitemap
            </a>
            <a href="<?= BASE_URL ?>robots.txt" class="text-muted text-decoration-none small">
                <i class="fas fa-robot me-1"></i>robots.txt
            </a>
        </div>
    </div>
</footer>

<!-- MDB 5 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.umd.min.js"></script>
<!-- Custom -->
<script src="<?= BASE_URL ?>assets/js/custom.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Navbar collapse (mobile toggle) ──────────────────────
    const toggler = document.getElementById('navbarTogglerBtn');
    const navMain = document.getElementById('navMain');
    if (toggler && navMain) {
        toggler.addEventListener('click', () => {
            navMain.classList.toggle('show');
        });
        // Close on outside click
        document.addEventListener('click', e => {
            if (!toggler.contains(e.target) && !navMain.contains(e.target)) {
                navMain.classList.remove('show');
            }
        });
    }

    // ── Desktop dropdown (user menu) ─────────────────────────
    const dropToggle = document.getElementById('userDropDesktop');
    const dropMenu   = document.getElementById('userDropMenu');
    if (dropToggle && dropMenu) {
        dropToggle.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = dropMenu.classList.contains('show');
            dropMenu.classList.toggle('show', !isOpen);
            dropToggle.setAttribute('aria-expanded', String(!isOpen));
        });
        document.addEventListener('click', e => {
            if (!dropToggle.contains(e.target) && !dropMenu.contains(e.target)) {
                dropMenu.classList.remove('show');
                dropToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── Alert auto-dismiss (success/info after 5s) ────────────
    document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // ── Confirm dangerous actions ─────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
    });

});
</script>
</body>
</html>
