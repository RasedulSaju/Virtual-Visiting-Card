<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5 text-center py-5">
        <div class="display-1 fw-black text-primary mb-3" style="font-size:7rem;line-height:1;">404</div>
        <h2 class="fw-bold mb-2">Page Not Found</h2>
        <p class="text-muted mb-4">
            The page you're looking for doesn't exist, has been moved,<br>
            or the URL was typed incorrectly.
        </p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> Home
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Go Back
            </button>
        </div>
    </div>
</div>
