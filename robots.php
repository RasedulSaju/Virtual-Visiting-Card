<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');
?>
User-agent: *
Allow: /

# Block admin panel
Disallow: /admin/

# Block auth & account pages
Disallow: /login
Disallow: /logout
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /change-password
Disallow: /edit-profile

# Block setup
Disallow: /setup.php

# Sitemap
Sitemap: <?= BASE_URL ?>sitemap.xml
