        </div><!-- /admin-content-inner -->
    </main><!-- /admin-content -->
</div><!-- /admin-wrapper -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.umd.min.js"></script>
<?php
$_mdbProDir  = dirname(__DIR__) . '/assets/mdb-pro';
$_mdbProJs   = $_mdbProDir . '/mdb.min.js';
$_proModules = array_unique($proModules ?? []);

if (file_exists($_mdbProJs)): ?>
<script src="<?= BASE_URL ?>assets/mdb-pro/mdb.min.js"></script>
<?php endif;
foreach ($_proModules as $_mod):
    $_modJs = $_mdbProDir . '/modules/' . $_mod . '.min.js';
    if (file_exists($_modJs)): ?>
<script src="<?= BASE_URL ?>assets/mdb-pro/modules/<?= e($_mod) ?>.min.js"></script>
<?php endif; endforeach; ?>
<script src="<?= BASE_URL ?>assets/js/custom.js"></script>
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
