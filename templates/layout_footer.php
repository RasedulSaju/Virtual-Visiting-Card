</main><!-- /main.container -->

<!-- ── Footer ────────────────────────────────────────────────── -->
<footer class="cms-footer mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <span class="fw-semibold"><?= e(APP_NAME) ?></span>
                <span class="text-muted ms-2 small">© <?= date('Y') ?></span>
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                <a href="<?= BASE_URL ?>about-us" class="text-muted text-decoration-none small me-3">About</a>
                <a href="<?= BASE_URL ?>login" class="text-muted text-decoration-none small">Login</a>
            </div>
        </div>
    </div>
</footer>

<!-- MDB 5 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.umd.min.js"></script>
<!-- Custom -->
<script src="<?= BASE_URL ?>assets/js/custom.js"></script>
<script>
    // Init MDB floating labels
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.form-outline').forEach(el => new mdb.Input(el).init());
    });
</script>
</body>
</html>
