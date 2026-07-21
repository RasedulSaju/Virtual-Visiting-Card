        </div><!-- /admin-content-inner -->
    </main><!-- /admin-content -->
</div><!-- /admin-wrapper -->

<?php
// ── MDB JS — Pro (if uploaded) > bundled free (in-repo) > CDN (last resort) ──
$_mdbProDir  = dirname(__DIR__) . '/assets/mdb-pro';
$_mdbFreeDir = dirname(__DIR__) . '/assets/mdb-free';
$_mdbProJs   = $_mdbProDir . '/mdb.min.js';
$_mdbFreeJs  = $_mdbFreeDir . '/mdb.min.js';

if (file_exists($_mdbProJs)): ?>
<script src="<?= assetUrl('assets/mdb-pro/mdb.min.js') ?>"></script>
<?php elseif (file_exists($_mdbFreeJs)): ?>
<script src="<?= assetUrl('assets/mdb-free/mdb.min.js') ?>"></script>
<?php else: ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.umd.min.js"></script>
<?php endif;

$_proModules = array_unique($proModules ?? []);
foreach ($_proModules as $_mod):
    $_modJs = $_mdbProDir . '/modules/' . $_mod . '.min.js';
    if (file_exists($_modJs)): ?>
<script src="<?= assetUrl('assets/mdb-pro/modules/' . $_mod . '.min.js') ?>"></script>
<?php endif; endforeach; ?>
<script src="<?= assetUrl('assets/js/custom.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // User dropdown (manual — MDB auto-init unreliable)
    const drop = document.getElementById('adminUserDrop');
    const menu = document.getElementById('adminUserMenu');
    if (drop && menu) {
        drop.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            menu.classList.toggle('show');
        });
        document.addEventListener('click', e => {
            if (!drop.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('show');
            }
        });
    }

    // Sidebar toggle
    const sidebar = document.getElementById('adminSidebar');
    const content = document.getElementById('adminContent');
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });

    // Auto-collapse on mobile after click
    if (window.innerWidth < 768) {
        sidebar.classList.add('collapsed');
        content.classList.add('expanded');
        document.querySelectorAll('.admin-nav-link').forEach(link => {
            link.addEventListener('click', () => {
                sidebar.classList.add('collapsed');
                content.classList.add('expanded');
            });
        });
    }

    // Delete confirmation
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', e => {
            if (!confirm(btn.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
</body>
</html>
