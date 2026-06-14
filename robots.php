<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');

echo buildRobotsTxt();
