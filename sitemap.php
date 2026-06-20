<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

$pdo = getDB();

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

// If the whole site is set to noindex, return an empty sitemap
$globalNoindex = getSetting('seo_global_noindex', '0') === '1';

$pages = $globalNoindex ? [] : $pdo->query(
    "SELECT slug, updated_at FROM pages
     WHERE meta_robots NOT LIKE '%noindex%'
     ORDER BY nav_order ASC"
)->fetchAll();

$users = $globalNoindex ? [] : $pdo->query(
    "SELECT username, created_at FROM users
     WHERE meta_robots NOT LIKE '%noindex%'
     ORDER BY created_at DESC"
)->fetchAll();

$now = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    <?php if (!$globalNoindex): ?>
    <!-- Static system pages -->
    <url>
        <loc><?= e(BASE_URL) ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
        <lastmod><?= $now ?></lastmod>
    </url>
    <url>
        <loc><?= e(BASE_URL . 'members') ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
        <lastmod><?= $now ?></lastmod>
    </url>
    <?php endif; ?>

    <!-- CMS Pages -->
    <?php foreach ($pages as $p): ?>
    <url>
        <loc><?= e(BASE_URL . $p['slug']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        <lastmod><?= date('Y-m-d', strtotime($p['updated_at'])) ?></lastmod>
    </url>
    <?php endforeach; ?>

    <!-- User Profiles -->
    <?php foreach ($users as $u): ?>
    <url>
        <loc><?= e(BASE_URL . $u['username']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
        <lastmod><?= date('Y-m-d', strtotime($u['created_at'])) ?></lastmod>
    </url>
    <?php endforeach; ?>

</urlset>
