<?php
/** @var array $page — set by index.php router */
if (empty($page)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <article class="card border-0 shadow-sm">
            <div class="page-article-header card-header border-0 p-4 pb-3">
                <h1 class="page-title mb-1"><?= e($page['title']) ?></h1>
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    Last updated <?= date('F j, Y', strtotime($page['updated_at'])) ?>
                </small>
            </div>
            <div class="card-body p-4 page-content">
                <?= $page['content'] /* Admin-generated HTML — intentionally not escaped */ ?>
            </div>
        </article>
    </div>
</div>
