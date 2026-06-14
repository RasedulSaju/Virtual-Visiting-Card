        </div><!-- /admin-content-inner -->
    </main><!-- /admin-content -->
</div><!-- /admin-wrapper -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.umd.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/custom.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // MDB floating labels
    document.querySelectorAll('.form-outline').forEach(el => new mdb.Input(el).init());

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
